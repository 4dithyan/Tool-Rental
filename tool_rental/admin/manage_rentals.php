<?php
require_once '../includes/db_connect.php';
require_once '../includes/settings_helper.php';
require_once '../modules/send_notification.php';

// Function to check and notify users when a tool becomes available
function checkAndNotifyToolAvailability($conn, $tool_id) {
    // Use the new function from send_notification.php
    notifyToolAvailability($tool_id);
}

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['mark_returned'])) {
        $rental_id = intval($_POST['rental_id']);
        $actual_return_date = date('Y-m-d');
        $actual_return_time = date('H:i:s'); // Get current time
        
        // Get rental details
        $rental_query = "SELECT r.*, t.actual_price FROM rentals r 
                        JOIN tools t ON r.tool_id = t.tool_id 
                        WHERE r.rental_id = ?";
        $stmt = $conn->prepare($rental_query);
        $stmt->bind_param("i", $rental_id);
        $stmt->execute();
        $rental_result = $stmt->get_result();
        
        if ($rental_result->num_rows == 1) {
            $rental = $rental_result->fetch_assoc();
            $return_date = new DateTime($rental['return_date']);
            $actual_return = new DateTime($actual_return_date);
            
            // Calculate late fine using the configurable setting
            $late_fine = 0;
            if ($actual_return > $return_date) {
                $days_late = $actual_return->diff($return_date)->days;
                $late_fine = calculate_late_fee($days_late); // Using helper function
            }
            
            // Update rental with actual return time
            $update_query = "UPDATE rentals SET status = 'returned', actual_return_date = ?, actual_return_time = ?, late_fine = ? WHERE rental_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("ssdi", $actual_return_date, $actual_return_time, $late_fine, $rental_id);
            
            if ($update_stmt->execute()) {
                // Increase tool quantity by the rented quantity
                $increase_query = "UPDATE tools SET quantity_available = quantity_available + ? WHERE tool_id = ?";
                $increase_stmt = $conn->prepare($increase_query);
                $increase_stmt->bind_param("ii", $rental['quantity'], $rental['tool_id']);
                $increase_stmt->execute();
                
                // Check if there are notifications for this tool and notify users
                // Always check for notification requests when a tool is returned
                checkAndNotifyToolAvailability($conn, $rental['tool_id']);
                
                // Send notification to user about return
                sendRentalNotification($rental_id, 'returned');
                
                // Send notification to user about late fee if applicable
                if ($late_fine > 0) {
                    sendRentalNotification($rental_id, 'late_fine', "Late fee of ₹" . number_format($late_fine, 2) . " has been applied to your rental.");
                }
                
                $success_message = "Rental marked as returned successfully!";
                if ($late_fine > 0) {
                    $success_message .= " Late fine of ₹" . number_format($late_fine, 2) . " applied.";
                }
            } else {
                $error_message = "Failed to update rental status.";
            }
        }
    } elseif (isset($_POST['apply_damage_fine'])) {
        $rental_id = intval($_POST['rental_id']);
        $damage_percentage = isset($_POST['damage_percentage']) ? floatval($_POST['damage_percentage']) : get_damage_fee_percentage();
        $tool_count = isset($_POST['tool_count']) ? intval($_POST['tool_count']) : 1;
        
        // Get rental details including current damage fine and tool quantity
        $rental_query = "SELECT r.*, t.actual_price, t.quantity_available FROM rentals r 
                        JOIN tools t ON r.tool_id = t.tool_id 
                        WHERE r.rental_id = ?";
        $stmt = $conn->prepare($rental_query);
        $stmt->bind_param("i", $rental_id);
        $stmt->execute();
        $rental_result = $stmt->get_result();
        
        if ($rental_result->num_rows == 1) {
            $rental = $rental_result->fetch_assoc();
            
            // Validate tool count doesn't exceed rented quantity (primary constraint only)
            if ($tool_count > $rental['quantity']) {
                $error_message = "Tool count cannot exceed rented quantity of " . $rental['quantity'] . " tools.";
            } else {
                // Calculate damage fine based on percentage and tool count
                $damage_fine_per_tool = $rental['actual_price'] * ($damage_percentage / 100);
                $additional_damage_fine = $damage_fine_per_tool * $tool_count;
                
                // Add to existing damage fine
                $new_damage_fine = $rental['damage_fine'] + $additional_damage_fine;
                
                $update_query = "UPDATE rentals SET damage_fine = ? WHERE rental_id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("di", $new_damage_fine, $rental_id);
                
                if ($update_stmt->execute()) {
                    // Get the damage fine amount to include in the notification
                    $damage_fine_query = "SELECT damage_fine FROM rentals WHERE rental_id = ?";
                    $damage_fine_stmt = $conn->prepare($damage_fine_query);
                    $damage_fine_stmt->bind_param("i", $rental_id);
                    $damage_fine_stmt->execute();
                    $damage_fine_result = $damage_fine_stmt->get_result();
                    $damage_fine_data = $damage_fine_result->fetch_assoc();
                    $damage_fine_amount = $damage_fine_data['damage_fine'];
                    
                    // Send notification to user about damage fee with amount
                    sendRentalNotification($rental_id, 'damage_fine', "Additional damage fee of ₹" . number_format($additional_damage_fine, 2) . " has been applied to your rental. Total damage fee: ₹" . number_format($damage_fine_amount, 2));
                    
                    $success_message = "Additional damage fine of ₹" . number_format($additional_damage_fine, 2) . " applied successfully for " . $tool_count . " tool(s) at " . number_format($damage_percentage, 2) . "% damage rate! Total damage: ₹" . number_format($new_damage_fine, 2);
                } else {
                    $error_message = "Failed to apply damage fine.";
                }
            }
        } else {
            $error_message = "Rental not found.";
        }
    } elseif (isset($_POST['mark_full_payment'])) {
        $rental_id = intval($_POST['rental_id']);
        
        // Update rental to mark full payment as received
        $update_query = "UPDATE rentals SET full_payment_received = 1 WHERE rental_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("i", $rental_id);
        
        if ($update_stmt->execute()) {
            $success_message = "Full payment marked as received successfully!";
        } else {
            $error_message = "Failed to update payment status.";
        }
    } elseif (isset($_POST['upload_proof'])) {
        $rental_id = intval($_POST['rental_id']);
        
        // Handle proof image upload
        if (isset($_FILES['admin_proof_image']) && $_FILES['admin_proof_image']['error'] == 0) {
            $upload_dir = '../uploads/proofs/';
            
            // Create uploads directory if it doesn't exist
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($_FILES['admin_proof_image']['name'], PATHINFO_EXTENSION);
            $unique_filename = 'admin_proof_' . uniqid() . '_' . time() . '.' . $file_extension;
            $target_file = $upload_dir . $unique_filename;
            
            // Validate file type (only allow images)
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array(strtolower($file_extension), $allowed_types)) {
                $error_message = 'Only JPG, JPEG, PNG, and GIF files are allowed for proof images.';
            } elseif ($_FILES['admin_proof_image']['size'] > 5000000) {
                // Validate file size (max 5MB)
                $error_message = 'Proof image file size must be less than 5MB.';
            } elseif (move_uploaded_file($_FILES['admin_proof_image']['tmp_name'], $target_file)) {
                // Store relative path in database
                $proof_image_path = 'uploads/proofs/' . $unique_filename;
                
                // Update rental record with proof image
                $update_query = "UPDATE rentals SET proof_image = ? WHERE rental_id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("si", $proof_image_path, $rental_id);
                
                if ($update_stmt->execute()) {
                    $success_message = "Proof image uploaded successfully!";
                } else {
                    $error_message = "Failed to save proof image to database.";
                }
            } else {
                $error_message = "Failed to upload proof image.";
            }
        } else {
            $error_message = "Please select a proof image to upload.";
        }
    }
}

// Get rentals with filtering
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$where_conditions = [];
$params = [];
$param_types = '';

if ($filter !== 'all') {
    $where_conditions[] = "r.status = ?";
    $params[] = $filter;
    $param_types .= 's';
}

if (!empty($search)) {
    $where_conditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR t.name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'sss';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Check if payment_method column exists
$columns_query = "SHOW COLUMNS FROM rentals LIKE 'payment_method'";
$columns_result = $conn->query($columns_query);

// Check if full_payment_received column exists
$full_payment_column_query = "SHOW COLUMNS FROM rentals LIKE 'full_payment_received'";
$full_payment_column_result = $conn->query($full_payment_column_query);

$rentals_query = "";
if ($columns_result->num_rows > 0) {
    // Payment method columns exist
    $rentals_query = "SELECT r.*, u.first_name, u.last_name, u.email, u.phone, 
                      t.name as tool_name, t.actual_price, t.quantity_available as tool_quantity_available,
                      DATEDIFF(CURDATE(), r.return_date) as days_overdue
                      FROM rentals r 
                      JOIN users u ON r.user_id = u.user_id 
                      JOIN tools t ON r.tool_id = t.tool_id 
                      $where_clause
                      ORDER BY r.created_at DESC";
} else {
    // Payment method columns don't exist yet
    $rentals_query = "SELECT r.*, u.first_name, u.last_name, u.email, u.phone, 
                      t.name as tool_name, t.actual_price, t.quantity_available as tool_quantity_available,
                      DATEDIFF(CURDATE(), r.return_date) as days_overdue
                      FROM rentals r 
                      JOIN users u ON r.user_id = u.user_id 
                      JOIN tools t ON r.tool_id = t.tool_id 
                      $where_clause
                      ORDER BY r.created_at DESC";
}

$stmt = $conn->prepare($rentals_query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$rentals_result = $stmt->get_result();

include '../includes/header.php';
?>

<style>
/* Rental Management Table Styles */
.rental-management-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background-color: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.rental-management-table th {
    background-color: #f8f9fa;
    padding: 12px 15px;
    text-align: left;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
    white-space: nowrap;
}

.rental-management-table td {
    padding: 12px 15px;
    border-bottom: 1px solid #dee2e6;
    vertical-align: top;
}

.rental-management-table tr:last-child td {
    border-bottom: none;
}

.rental-management-table tr:hover {
    background-color: #f8f9fa;
}

/* Column Widths */
.rental-id-col { width: 8%; }
.customer-col { width: 15%; }
.tool-col { width: 12%; }
.period-col { width: 20%; }
.amount-col { width: 15%; }
.payment-col { width: 10%; }
.status-col { width: 8%; }
.actions-col { width: 12%; }

/* Customer Info */
.customer-name {
    font-weight: 600;
    color: #212529;
    margin-bottom: 3px;
    display: block;
}

.customer-contact {
    font-size: 0.85rem;
    color: #666;
    line-height: 1.4;
}

.customer-contact-item {
    display: block;
    margin-bottom: 2px;
}

.customer-contact-item:last-child {
    margin-bottom: 0;
}

/* Rental Period */
.period-section {
    margin-bottom: 8px;
    padding-bottom: 8px;
    border-bottom: 1px solid #e9ecef;
}

.period-section:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.period-label {
    font-weight: 600;
    color: #212529;
    font-size: 0.9rem;
}

.period-date {
    font-size: 0.85rem;
    color: #495057;
    margin-left: 5px;
}

.period-time {
    font-size: 0.8rem;
    color: #6c757d;
    margin-left: 5px;
}

.overdue-warning {
    color: #dc3545;
    font-weight: 600;
    font-size: 0.85rem;
}

.quantity-info {
    font-size: 0.85rem;
    color: #666;
    margin-top: 5px;
}

/* Amount Details */
.amount-section {
    font-size: 0.85rem;
}

.amount-line {
    margin-bottom: 3px;
}

.amount-line:last-child {
    margin-bottom: 0;
}

.amount-total {
    font-weight: 700;
    color: #212529;
    border-top: 1px solid #dee2e6;
    padding-top: 5px;
    margin-top: 5px;
}

.amount-negative {
    color: #dc3545;
}

.balance-amount {
    font-weight: 700;
    color: #dc3545;
}

/* Payment Badges */
.payment-badge {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
    display: inline-block;
    margin-bottom: 5px;
}

.payment-badge-cod {
    background-color: #17a2b8;
    color: white;
}

.payment-badge-full {
    background-color: #28a745;
    color: white;
}

.payment-badge-pending {
    background-color: #ffc107;
    color: #000;
}

.payment-badge-paid {
    background-color: #28a745;
    color: white;
}

/* Status Badges */
.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 500;
}

.status-badge-active {
    background-color: #28a745;
    color: white;
}

.status-badge-returned {
    background-color: #17a2b8;
    color: white;
}

.status-badge-overdue {
    background-color: #dc3545;
    color: white;
}

.status-badge-cancelled {
    background-color: #6c757d;
    color: white;
}

/* Proof Links */
.proof-links {
    display: flex;
    flex-direction: column;
    gap: 5px;
    margin-top: 8px;
}

.proof-link {
    padding: 3px 6px;
    font-size: 0.75rem;
    border-radius: 3px;
}

/* Action Groups */
.action-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.action-btn {
    padding: 6px 10px;
    font-size: 0.85rem;
    border-radius: 4px;
    text-align: left;
    width: 100%;
    display: flex;
    align-items: center;
    gap: 5px;
}

.action-btn-sm {
    padding: 4px 8px;
    font-size: 0.8rem;
}

.action-btn-primary { background-color: #007bff; color: white; border: none; }
.action-btn-success { background-color: #28a745; color: white; border: none; }
.action-btn-danger { background-color: #dc3545; color: white; border: none; }
.action-btn-warning { background-color: #ffc107; color: #212529; border: none; }
.action-btn-info { background-color: #17a2b8; color: white; border: none; }

.action-btn:hover {
    opacity: 0.9;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* File Upload Forms */
.file-upload-form {
    display: flex;
    gap: 5px;
    align-items: center;
    margin-bottom: 8px;
}

.file-upload-form:last-child {
    margin-bottom: 0;
}

.file-input {
    font-size: 0.8rem;
    padding: 3px;
    width: 140px;
}

.upload-btn {
    padding: 3px 6px;
    font-size: 0.8rem;
    border-radius: 3px;
}

.id-proof-section {
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px solid #e9ecef;
}

.id-proof-header {
    font-size: 0.8rem;
    color: #666;
    margin-bottom: 5px;
    display: block;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .rental-management-table {
        font-size: 0.85rem;
    }
    
    .rental-management-table th,
    .rental-management-table td {
        padding: 8px 10px;
    }
    
    .action-group {
        gap: 5px;
    }
    
    .file-upload-form {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .file-input {
        width: 100%;
        margin-bottom: 5px;
    }
}
</style>

<div class="container">
    <div class="main-content">
        <h1><i class="fas fa-list"></i> Manage Rentals</h1>
        <p class="mb-3">Track and manage all tool rentals</p>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Database Update Notice -->
        <?php if ($columns_result->num_rows == 0): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> 
                <strong>Database Update Required:</strong> 
                Payment method features require a database update. 
                <a href="../update_db_columns.php">Click here to update the database</a> 
                to enable payment method tracking.
            </div>
        <?php endif; ?>
        
        <!-- Full Payment Column Notice -->
        <?php if ($columns_result->num_rows > 0 && $full_payment_column_result->num_rows == 0): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> 
                <strong>Database Update Required:</strong> 
                Full payment tracking requires a database update. 
                <a href="../add_full_payment_column.php">Click here to update the database</a> 
                to enable full payment tracking for COD rentals.
            </div>
        <?php endif; ?>
        
        <!-- Filter and Search -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="grid grid-2" style="align-items: end;">
                    <div class="form-group mb-0">
                        <label class="form-label" for="search">Search Rentals</label>
                        <input type="text" id="search" name="search" class="form-control" 
                               placeholder="Search by customer name or tool..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div class="form-group mb-0">
                            <label class="form-label" for="filter">Filter by Status</label>
                            <select id="filter" name="filter" class="form-control form-select">
                                <option value="all" <?php echo ($filter == 'all') ? 'selected' : ''; ?>>All Rentals</option>
                                <option value="active" <?php echo ($filter == 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="returned" <?php echo ($filter == 'returned') ? 'selected' : ''; ?>>Returned</option>
                                <option value="overdue" <?php echo ($filter == 'overdue') ? 'selected' : ''; ?>>Overdue</option>
                                <option value="cancelled" <?php echo ($filter == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="d-flex gap-1">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <a href="manage_rentals.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Rentals Table -->
        <div class="card">
            <div class="card-header">
                <h3>Rental Records (<?php echo $rentals_result->num_rows; ?> found)</h3>
            </div>
            <div class="card-body">
                <?php if ($rentals_result->num_rows > 0): ?>
                    <div style="overflow-x: auto;">
                        <table class="rental-management-table">
                            <thead>
                                <tr>
                                    <th class="rental-id-col">Rental ID</th>
                                    <th class="customer-col">Customer</th>
                                    <th class="tool-col">Tool</th>
                                    <th class="period-col">Rental Period</th>
                                    <th class="amount-col">Amount</th>
                                    <?php if ($columns_result->num_rows > 0): ?>
                                        <th class="payment-col">Payment</th>
                                    <?php endif; ?>
                                    <th class="status-col">Status</th>
                                    <th class="actions-col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($rental = $rentals_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong>#<?php echo $rental['rental_id']; ?></strong></td>
                                        <td>
                                            <span class="customer-name"><?php echo htmlspecialchars($rental['first_name'] . ' ' . $rental['last_name']); ?></span>
                                            <div class="customer-contact">
                                                <span class="customer-contact-item"><?php echo htmlspecialchars($rental['email']); ?></span>
                                                <?php if ($rental['phone']): ?>
                                                    <span class="customer-contact-item"><?php echo htmlspecialchars($rental['phone']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($rental['tool_name']); ?></strong>
                                            <div class="quantity-info">Qty: <?php echo $rental['quantity']; ?></div>
                                        </td>
                                        <td>
                                            <div class="period-section">
                                                <span class="period-label">Start:</span>
                                                <span class="period-date"><?php echo date('M j, Y', strtotime($rental['rental_date'])); ?></span>
                                                <?php if ($rental['rental_time']): ?>
                                                    <span class="period-time">at <?php echo date('g:i A', strtotime($rental['rental_time'])); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="period-section">
                                                <span class="period-label">Due:</span>
                                                <span class="period-date"><?php echo date('M j, Y', strtotime($rental['return_date'])); ?></span>
                                                <?php if ($rental['return_time']): ?>
                                                    <span class="period-time">at <?php echo date('g:i A', strtotime($rental['return_time'])); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($rental['actual_return_date']): ?>
                                                <div class="period-section">
                                                    <span class="period-label">Returned:</span>
                                                    <span class="period-date"><?php echo date('M j, Y', strtotime($rental['actual_return_date'])); ?></span>
                                                    <?php if ($rental['actual_return_time']): ?>
                                                        <span class="period-time">at <?php echo date('g:i A', strtotime($rental['actual_return_time'])); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php elseif ($rental['days_overdue'] > 0): ?>
                                                <div class="overdue-warning"><?php echo $rental['days_overdue']; ?> days overdue</div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="amount-section">
                                                <div class="amount-line">
                                                    <span>Base:</span>
                                                    <span>₹<?php echo number_format($rental['total_amount'], 2); ?></span>
                                                </div>
                                                <?php if ($rental['late_fine'] > 0): ?>
                                                    <div class="amount-line">
                                                        <span>Late:</span>
                                                        <span class="amount-negative">₹<?php echo number_format($rental['late_fine'], 2); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($rental['damage_fine'] > 0): ?>
                                                    <div class="amount-line">
                                                        <span>Damage:</span>
                                                        <span class="amount-negative">₹<?php echo number_format($rental['damage_fine'], 2); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="amount-line amount-total">
                                                    <span>Total:</span>
                                                    <span>₹<?php echo number_format($rental['total_amount'] + $rental['late_fine'] + $rental['damage_fine'], 2); ?></span>
                                                </div>
                                                                
                                                                <?php 
                                                                // Show pending balance for COD orders
                                                                if ($columns_result->num_rows > 0 && isset($rental['payment_method']) && $rental['payment_method'] === 'cod') {
                                                                    $total_amount = $rental['total_amount'] + $rental['late_fine'] + $rental['damage_fine'];
                                                                    $pending_balance = $total_amount - $rental['deposit_amount'];
                                                                    if ($pending_balance > 0) {
                                                                        echo '<div class="amount-line balance-amount">Balance: ₹' . number_format($pending_balance, 2) . '</div>';
                                                                    }
                                                                }
                                                                ?>
                                                            </div>
                                                        </td>
                                                        <?php if ($columns_result->num_rows > 0): ?>
                                                            <td>
                                                                <?php if (isset($rental['payment_method']) && $rental['payment_method'] === 'cod'): ?>
                                                                    <span class="payment-badge payment-badge-cod">COD</span>
                                                                    <div>Deposit: ₹<?php echo number_format($rental['deposit_amount'], 2); ?></div>
                                                                    <?php if ($full_payment_column_result->num_rows > 0 && isset($rental['full_payment_received']) && $rental['full_payment_received'] == 1): ?>
                                                                        <span class="payment-badge payment-badge-paid">Paid</span>
                                                                    <?php elseif ($full_payment_column_result->num_rows > 0): ?>
                                                                        <span class="payment-badge payment-badge-pending">Pending</span>
                                                                        <?php 
                                                                        // Calculate pending balance for COD orders
                                                                        $total_amount = $rental['total_amount'] + $rental['late_fine'] + $rental['damage_fine'];
                                                                        $pending_balance = $total_amount - $rental['deposit_amount'];
                                                                        if ($pending_balance > 0): ?>
                                                                            <div class="balance-amount">Balance: ₹<?php echo number_format($pending_balance, 2); ?></div>
                                                                        <?php endif; ?>
                                                                    <?php endif; ?>
                                                                <?php else: ?>
                                                                    <span class="payment-badge payment-badge-full">Full Payment</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        <?php endif; ?>
                                                        <td>
                                                            <span class="status-badge status-badge-<?php echo $rental['status']; ?>">
                                                                <?php echo ucfirst($rental['status']); ?>
                                                            </span>
                                                            <?php if (!empty($rental['proof_image']) || !empty($rental['id_proof_image'])): ?>
                                                                <div class="proof-links">
                                                                    <?php if (!empty($rental['proof_image'])): ?>
                                                                        <a href="../<?php echo htmlspecialchars($rental['proof_image']); ?>" target="_blank" class="btn btn-info proof-link">
                                                                            <i class="fas fa-image"></i> Proof
                                                                        </a>
                                                                    <?php endif; ?>
                                                                    <?php if (!empty($rental['id_proof_image'])): ?>
                                                                        <a href="../<?php echo htmlspecialchars($rental['id_proof_image']); ?>" target="_blank" class="btn btn-warning proof-link">
                                                                            <i class="fas fa-id-card"></i> ID
                                                                        </a>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($rental['status'] == 'active'): ?>
                                                                <div class="action-group">
                                                                    <form method="POST" style="display: inline;">
                                                                        <input type="hidden" name="rental_id" value="<?php echo $rental['rental_id']; ?>">
                                                                        <button type="submit" name="mark_returned" class="action-btn action-btn-primary" 
                                                                                onclick="return confirm('Mark this rental as returned?');">
                                                                            <i class="fas fa-check"></i> Return
                                                                        </button>
                                                                    </form>
                                                                    
                                                                    <?php if ($rental['damage_fine'] == 0): ?>
                                                                        <form method="POST" style="display: inline;">
                                                                            <input type="hidden" name="rental_id" value="<?php echo $rental['rental_id']; ?>">
                                                                            <input type="hidden" name="tool_id" value="<?php echo $rental['tool_id']; ?>">
                                                                            <input type="hidden" name="actual_price" value="<?php echo $rental['actual_price']; ?>">
                                                                            <input type="hidden" name="quantity" value="<?php echo $rental['quantity']; ?>">
                                                                            <input type="number" name="damage_percentage" step="0.01" min="0" max="100" placeholder="Damage %" 
                                                                                   style="width: 70px; padding: 4px; margin-right: 5px; border: 1px solid #ccc; border-radius: 4px;" 
                                                                                   title="Enter damage percentage (0-100%)" 
                                                                                   value="<?php echo number_format(get_damage_fee_percentage(), 2, '.', ''); ?>">
                                                                            <?php $max_tools = $rental['quantity']; ?>
                                                                            <input type="number" name="tool_count" id="tool_count_<?php echo $rental['rental_id']; ?>" min="1" max="<?php echo $max_tools; ?>" placeholder="Qty" 
                                                                                   style="width: 50px; padding: 4px; margin-right: 5px; border: 1px solid #ccc; border-radius: 4px;" 
                                                                                   title="Enter number of tools (1-<?php echo $max_tools; ?>)" 
                                                                                   value="1"
                                                                                   oninput="validateToolCount(this, <?php echo $max_tools; ?>)">
                                                                            <button type="submit" name="apply_damage_fine" class="action-btn action-btn-danger" 
                                                                                    onclick="return confirm('Apply damage to ' + this.previousElementSibling.value + ' of <?php echo $max_tools; ?> tools at ' + this.previousElementSibling.previousElementSibling.value + '% damage rate?');">
                                                                                <i class="fas fa-exclamation-triangle"></i> Damage
                                                                            </button>
                                                                        </form>
                                                                        <?php if ($rental['damage_fine'] > 0): ?>
                                                                            <div style="margin-top: 5px; font-size: 0.85rem; color: #666;">
                                                                                Damage applied: ₹<?php echo number_format($rental['damage_fine'], 2); ?>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    
                                                                    <?php endif; ?>
                                                                    
                                                                    <?php // Show "Full Amount Paid" button for COD rentals that haven't been paid yet ?>
                                                                    <?php if ($columns_result->num_rows > 0 && $full_payment_column_result->num_rows > 0 && isset($rental['payment_method']) && $rental['payment_method'] === 'cod' && (!isset($rental['full_payment_received']) || $rental['full_payment_received'] != 1)): ?>
                                                                        <form method="POST" style="display: inline;">
                                                                            <input type="hidden" name="rental_id" value="<?php echo $rental['rental_id']; ?>">
                                                                            <button type="submit" name="mark_full_payment" class="action-btn action-btn-success" 
                                                                                    onclick="return confirm('Mark full payment as received for this COD rental?');">
                                                                                <i class="fas fa-rupee-sign"></i> Full Paid
                                                                            </button>
                                                                        </form>
                                                                    <?php endif; ?>
                                                                    
                                                                    <div class="file-upload-form">
                                                                        <input type="file" name="admin_proof_image" accept="image/*" class="file-input" title="Upload proof image">
                                                                        <button type="submit" name="upload_proof" class="upload-btn action-btn-info" 
                                                                                onclick="return confirm('Upload this proof image for the rental?');"
                                                                                title="Upload proof image">
                                                                            <i class="fas fa-upload"></i>
                                                                        </button>
                                                                    </div>
                                                                    
                                                                    
                                                                </div>
                                                            <?php elseif ($rental['status'] == 'returned'): ?>
                                                                <div class="action-group">
                                                                    <div>
                                                                        <span class="badge" style="background-color: #17a2b8; color: white;">
                                                                            <i class="fas fa-check-circle"></i> Returned
                                                                        </span>
                                                                    </div>
                                                                    
                                                                    <?php // Show "Full Amount Paid" button for returned COD rentals that haven't been paid yet ?>
                                                                    <?php if ($columns_result->num_rows > 0 && $full_payment_column_result->num_rows > 0 && isset($rental['payment_method']) && $rental['payment_method'] === 'cod' && (!isset($rental['full_payment_received']) || $rental['full_payment_received'] != 1)): ?>
                                                                        <form method="POST" style="display: inline;">
                                                                            <input type="hidden" name="rental_id" value="<?php echo $rental['rental_id']; ?>">
                                                                            <button type="submit" name="mark_full_payment" class="action-btn action-btn-success" 
                                                                                    onclick="return confirm('Mark full payment as received for this COD rental?');">
                                                                                <i class="fas fa-rupee-sign"></i> Full Paid
                                                                            </button>
                                                                        </form>
                                                                    <?php endif; ?>
                                                                    
                                                                    <?php if ($rental['damage_fine'] > 0): ?>
                                                                        <div style="margin-top: 5px; font-size: 0.85rem; color: #666;">
                                                                            Damage applied: ₹<?php echo number_format($rental['damage_fine'], 2); ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php else: ?>
                                                                <small style="color: #666;">No actions</small>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-center">No rentals found matching your criteria.</p>
                                <?php endif; ?>
                            </div>
                        </div>
        
        <!-- Quick Navigation -->
        <div class="card mt-3">
            <div class="card-body">
                <h4>Quick Navigation</h4>
                <div class="d-flex gap-2">
                    <a href="admin_dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="manage_tools.php" class="btn btn-primary">
                        <i class="fas fa-tools"></i> Manage Tools
                    </a>
                    <a href="manage_categories.php" class="btn btn-primary">
                        <i class="fas fa-tags"></i> Manage Categories
                    </a>
                </div>
            </div>
        </div>
    </div>
<script>
// Function to validate tool count input
function validateToolCount(input, maxTools) {
    let value = parseInt(input.value) || 0;
    if (value > maxTools) {
        input.value = maxTools;
    } else if (value < 1) {
        input.value = 1;
    }
}
</script>

</div>

<?php include '../includes/footer.php'; ?>
