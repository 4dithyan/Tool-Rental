<?php
require_once '../includes/db_connect.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get report parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Format period for display
if ($start_date == $end_date) {
    $period_text = 'Date: ' . date('M j, Y', strtotime($start_date));
} else {
    $period_text = 'Period: ' . date('M j, Y', strtotime($start_date)) . ' to ' . date('M j, Y', strtotime($end_date));
}

// Generate report data
$report_data = [];

// Get rental statistics
$rental_stats_query = "SELECT 
    COUNT(*) as total_rentals,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_rentals,
    SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END) as completed_rentals,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_rentals,
    SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_rentals,
    SUM(total_amount) as total_revenue,
    SUM(late_fine) as total_late_fines,
    SUM(damage_fine) as total_damage_fines
FROM rentals 
WHERE rental_date BETWEEN ? AND ?";
$rental_stmt = $conn->prepare($rental_stats_query);
$rental_stmt->bind_param("ss", $start_date, $end_date);
$rental_stmt->execute();
$rental_stats = $rental_stmt->get_result()->fetch_assoc();

// Get detailed rental records
$rental_records_query = "SELECT 
    r.rental_id,
    u.first_name,
    u.last_name,
    t.name as tool_name,
    r.rental_date,
    r.return_date,
    r.actual_return_date,
    r.quantity,
    r.total_amount,
    r.payment_method,
    r.status,
    r.late_fine,
    r.damage_fine
FROM rentals r
JOIN users u ON r.user_id = u.user_id
JOIN tools t ON r.tool_id = t.tool_id
WHERE r.rental_date BETWEEN ? AND ?
ORDER BY r.rental_date DESC";
$rental_records_stmt = $conn->prepare($rental_records_query);
$rental_records_stmt->bind_param("ss", $start_date, $end_date);
$rental_records_stmt->execute();
$rental_records = $rental_records_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$report_data = [
    'period' => $period_text,
    'start_date' => $start_date,
    'end_date' => $end_date,
    'rental_stats' => $rental_stats,
    'rental_records' => $rental_records
];

// If PDF export is requested
if (isset($_GET['export']) && $_GET['export'] == 'pdf') {
    // Set headers for PDF download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="rental_report_' . date('Y-m-d') . '.pdf"');
    
    // For now, we'll just output a simple text version
    // In a real implementation, you would use a library like TCPDF or FPDF
    echo "Rental Report - " . $report_data['period'] . "\n";
    echo "Generated on: " . date('Y-m-d H:i:s') . "\n\n";
    
    echo "Rental Statistics:\n";
    echo "Total Rentals: " . $report_data['rental_stats']['total_rentals'] . "\n";
    echo "Active Rentals: " . $report_data['rental_stats']['active_rentals'] . "\n";
    echo "Completed Rentals: " . $report_data['rental_stats']['completed_rentals'] . "\n";
    echo "Cancelled Rentals: " . $report_data['rental_stats']['cancelled_rentals'] . "\n";
    echo "Overdue Rentals: " . $report_data['rental_stats']['overdue_rentals'] . "\n";
    echo "Total Revenue (₹): " . number_format($report_data['rental_stats']['total_revenue'] ?: 0, 2) . "\n";
    echo "Late Fines (₹): " . number_format($report_data['rental_stats']['total_late_fines'] ?: 0, 2) . "\n";
    echo "Damage Fines (₹): " . number_format($report_data['rental_stats']['total_damage_fines'] ?: 0, 2) . "\n\n";
    
    echo "Detailed Rental Records:\n";
    echo "ID\tUser\tTool\tRental Date\tReturn Date\tActual Return\tQuantity\tAmount (₹)\tPayment\tStatus\n";
    foreach ($report_data['rental_records'] as $record) {
        echo $record['rental_id'] . "\t" . 
             $record['first_name'] . ' ' . $record['last_name'] . "\t" . 
             $record['tool_name'] . "\t" . 
             $record['rental_date'] . "\t" . 
             $record['return_date'] . "\t" . 
             ($record['actual_return_date'] ?: 'N/A') . "\t" . 
             $record['quantity'] . "\t" . 
             number_format($record['total_amount'], 2) . "\t" . 
             ($record['payment_method'] ?: 'N/A') . "\t" . 
             $record['status'] . "\n";
    }
    
    exit();
}

include '../includes/header.php';
?>

<div class="container">
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1><i class="fas fa-chart-bar"></i> Rental Report</h1>
            <div class="d-flex gap-2">
                <button onclick="printReport()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Print Report
                </button>
                <a href="?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&export=pdf" class="btn btn-secondary">
                    <i class="fas fa-download"></i> Download PDF
                </a>
                <a href="admin_dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h3>Report Filters</h3>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($start_date); ?>">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($end_date); ?>">
                    </div>
                    
                    <div class="col-md-4 align-self-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Report Content (will be printed) -->
        <div id="report-content">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Report Period: <strong><?php echo $report_data['period']; ?></strong>
            </div>
            
            <!-- Detailed Rental Records -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-file-invoice"></i> Rental Records</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($report_data['rental_records'])): ?>
                        <div style="overflow-x: auto;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User Name</th>
                                        <th>Tool</th>
                                        <th>Rental Date</th>
                                        <th>Return Date</th>
                                        <th>Actual Return</th>
                                        <th>Quantity</th>
                                        <th>Amount (₹)</th>
                                        <th>Payment Method</th>
                                        <th>Status</th>
                                        <th>Late Fine (₹)</th>
                                        <th>Damage Fine (₹)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($report_data['rental_records'] as $record): ?>
                                        <tr>
                                            <td><?php echo $record['rental_id']; ?></td>
                                            <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($record['tool_name']); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($record['rental_date'])); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($record['return_date'])); ?></td>
                                            <td><?php echo !empty($record['actual_return_date']) ? date('M j, Y', strtotime($record['actual_return_date'])) : 'N/A'; ?></td>
                                            <td><?php echo $record['quantity']; ?></td>
                                            <td>₹<?php echo number_format($record['total_amount'], 2); ?></td>
                                            <td>
                                                <?php if ($record['payment_method'] === 'cod'): ?>
                                                    <span class="badge" style="background-color: #17a2b8;">COD</span>
                                                <?php elseif ($record['payment_method'] === 'full'): ?>
                                                    <span class="badge" style="background-color: #28a745;">Full</span>
                                                <?php else: ?>
                                                    <span class="badge" style="background-color: #6c757d;">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo $record['status']; ?>">
                                                    <?php echo ucfirst($record['status']); ?>
                                                </span>
                                            </td>
                                            <td>₹<?php echo number_format($record['late_fine'], 2); ?></td>
                                            <td>₹<?php echo number_format($record['damage_fine'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted">No rental records found for this period.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function printReport() {
    // Get the report content
    var reportContent = document.getElementById('report-content').innerHTML;
    
    // Create a new window for printing
    var printWindow = window.open('', '_blank');
    
    // Write the report content to the new window
    printWindow.document.write(`
        <html>
            <head>
                <title>Rental Report</title>
                <style>
                    body { 
                        font-family: Arial, sans-serif; 
                        margin: 20px; 
                    }
                    table { 
                        width: 100%; 
                        border-collapse: collapse; 
                        margin-top: 20px; 
                    }
                    th, td { 
                        border: 1px solid #ddd; 
                        padding: 8px; 
                        text-align: left; 
                    }
                    th { 
                        background-color: #f2f2f2; 
                    }
                    .alert {
                        padding: 15px;
                        margin-bottom: 20px;
                        border: 1px solid transparent;
                        border-radius: 4px;
                        background-color: #d1ecf1;
                        border-color: #bee5eb;
                        color: #0c5460;
                    }
                    .card {
                        border: 1px solid #ddd;
                        border-radius: 4px;
                        margin-bottom: 20px;
                    }
                    .card-header {
                        padding: 10px 15px;
                        border-bottom: 1px solid #ddd;
                        background-color: #f5f5f5;
                    }
                    .card-body {
                        padding: 15px;
                    }
                    .badge {
                        display: inline-block;
                        padding: 0.25em 0.4em;
                        font-size: 75%;
                        font-weight: 700;
                        line-height: 1;
                        text-align: center;
                        white-space: nowrap;
                        vertical-align: baseline;
                        border-radius: 0.25rem;
                        color: #fff;
                    }
                </style>
            </head>
            <body>
                <h2>Rental Report</h2>
                ${reportContent}
                <p style="margin-top: 30px; text-align: center; font-style: italic;">
                    Generated on: <?php echo date('Y-m-d H:i:s'); ?>
                </p>
            </body>
        </html>
    `);
    
    // Close the document and print
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
    printWindow.close();
}
</script>

<style>
.stat-card {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.stat-value {
    font-size: 2rem;
    font-weight: bold;
    color: var(--accent-yellow);
    margin-bottom: 5px;
}

.stat-label {
    font-size: 0.9rem;
    color: #666;
}

@media print {
    body * {
        visibility: hidden;
    }
    #report-content, #report-content * {
        visibility: visible;
    }
    #report-content {
        position: absolute;
        left: 0;
        top: 0;
    }
}
</style>

<?php include '../includes/footer.php'; ?>