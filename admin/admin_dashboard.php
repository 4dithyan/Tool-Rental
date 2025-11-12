<?php
require_once '../includes/db_connect.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get dashboard statistics
$stats_queries = [
    'total_tools' => "SELECT COUNT(*) as count FROM tools",
    'active_tools' => "SELECT COUNT(*) as count FROM tools WHERE status = 'active'",
    'total_categories' => "SELECT COUNT(*) as count FROM categories",
    'total_users' => "SELECT COUNT(*) as count FROM users WHERE role = 'customer'",
    'total_admins' => "SELECT COUNT(*) as count FROM users WHERE role = 'admin'",
    'active_rentals' => "SELECT COUNT(*) as count FROM rentals WHERE status = 'active'",
    'overdue_rentals' => "SELECT COUNT(*) as count FROM rentals WHERE status = 'overdue'",
    'total_revenue' => "SELECT SUM(total_amount + late_fine + damage_fine) as total FROM rentals WHERE status IN ('returned', 'active')"
];

$stats = [];
foreach ($stats_queries as $key => $query) {
    $result = $conn->query($query);
    $stats[$key] = $result->fetch_assoc();
}

// Get revenue data for the last 6 months
$revenue_query = "SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    SUM(total_amount + late_fine + damage_fine) as revenue
                  FROM rentals 
                  WHERE status IN ('returned', 'active') 
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                  GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                  ORDER BY month";
$revenue_result = $conn->query($revenue_query);
$revenue_data = [];
while ($row = $revenue_result->fetch_assoc()) {
    $revenue_data[$row['month']] = $row['revenue'];
}

// Get category distribution
$category_query = "SELECT c.category_name, COUNT(t.tool_id) as tool_count 
                   FROM categories c 
                   LEFT JOIN tools t ON c.category_id = t.category_id 
                   GROUP BY c.category_id, c.category_name";
$category_result = $conn->query($category_query);
$category_data = [];
while ($row = $category_result->fetch_assoc()) {
    $category_data[$row['category_name']] = $row['tool_count'];
}

// Get recent rentals (check if payment_method column exists)
$columns_query = "SHOW COLUMNS FROM rentals LIKE 'payment_method'";
$columns_result = $conn->query($columns_query);

$recent_rentals_query = "";
if ($columns_result->num_rows > 0) {
    // Payment method columns exist
    $recent_rentals_query = "SELECT r.*, u.first_name, u.last_name, t.name as tool_name 
                            FROM rentals r 
                            JOIN users u ON r.user_id = u.user_id 
                            JOIN tools t ON r.tool_id = t.tool_id 
                            ORDER BY r.created_at DESC 
                            LIMIT 10";
} else {
    // Payment method columns don't exist yet
    $recent_rentals_query = "SELECT r.*, u.first_name, u.last_name, t.name as tool_name 
                            FROM rentals r 
                            JOIN users u ON r.user_id = u.user_id 
                            JOIN tools t ON r.tool_id = t.tool_id 
                            ORDER BY r.created_at DESC 
                            LIMIT 10";
}
$recent_rentals = $conn->query($recent_rentals_query);

// Get tools needing attention (low stock or maintenance)
$attention_tools_query = "SELECT * FROM tools 
                         WHERE quantity_available <= 1 OR status = 'maintenance' 
                         ORDER BY quantity_available ASC, status DESC";
$attention_tools = $conn->query($attention_tools_query);

// Get payment method distribution (only if columns exist)
$payment_method_data = [];
$cod_stats = ['cod_count' => 0, 'total_deposits' => 0, 'avg_deposit' => 0];

if ($columns_result->num_rows > 0) {
    $payment_method_query = "SELECT payment_method, COUNT(*) as count FROM rentals WHERE payment_method IS NOT NULL GROUP BY payment_method";
    $payment_method_result = $conn->query($payment_method_query);
    while ($row = $payment_method_result->fetch_assoc()) {
        $payment_method_data[$row['payment_method']] = $row['count'];
    }

    // Get COD deposit statistics
    $cod_stats_query = "SELECT 
                        COUNT(*) as cod_count,
                        SUM(deposit_amount) as total_deposits,
                        AVG(deposit_amount) as avg_deposit
                        FROM rentals 
                        WHERE payment_method = 'cod'";
    $cod_stats_result = $conn->query($cod_stats_query);
    $cod_stats = $cod_stats_result->fetch_assoc();
    
    // Initialize with default values if no data
    if (empty($cod_stats['cod_count'])) {
        $cod_stats = ['cod_count' => 0, 'total_deposits' => 0, 'avg_deposit' => 0];
    }
}

include '../includes/header.php';
?>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container">
    <div class="main-content">
        <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
        <p class="mb-3">Welcome to the Tool-Kart administration panel</p>
        
        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
            </div>
            <div class="card-body">
                <div class="d-flex gap-2 flex-wrap">
                    <a href="manage_tools.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Tool
                    </a>
                    <a href="manage_categories.php" class="btn btn-secondary">
                        <i class="fas fa-tags"></i> Manage Categories
                    </a>
                    <a href="manage_rentals.php" class="btn btn-primary">
                        <i class="fas fa-list"></i> View All Rentals
                    </a>
                    <a href="manage_faq.php" class="btn btn-secondary">
                        <i class="fas fa-question-circle"></i> Manage FAQ
                    </a>
                    <a href="generate_report.php" class="btn btn-secondary">
                        <i class="fas fa-chart-bar"></i> Generate Report
                    </a>
                    <?php if ($columns_result->num_rows == 0): ?>
                        <a href="../update_db_columns.php" class="btn btn-warning">
                            <i class="fas fa-database"></i> Update Database
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if ($columns_result->num_rows == 0): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> 
                <strong>Database Update Required:</strong> 
                The payment method features require a database update. 
                <a href="../update_db_columns.php">Click here to update the database</a> 
                to enable payment method tracking.
            </div>
        <?php endif; ?>
        
        <!-- Statistics Overview -->
        <div class="grid grid-4 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-tools" style="font-size: 2rem; color: var(--accent-yellow); margin-bottom: 10px;"></i>
                    <h3><?php echo $stats['total_tools']['count']; ?></h3>
                    <p>Total Tools</p>
                    <small><?php echo $stats['active_tools']['count']; ?> Active</small>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-users" style="font-size: 2rem; color: var(--accent-yellow); margin-bottom: 10px;"></i>
                    <h3><?php echo $stats['total_users']['count']; ?></h3>
                    <p>Customers</p>
                    <small><?php echo $stats['total_admins']['count']; ?> Admins</small>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-handshake" style="font-size: 2rem; color: var(--accent-yellow); margin-bottom: 10px;"></i>
                    <h3><?php echo $stats['active_rentals']['count']; ?></h3>
                    <p>Active Rentals</p>
                    <small><?php echo $stats['overdue_rentals']['count']; ?> Overdue</small>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-rupee-sign" style="font-size: 2rem; color: var(--accent-yellow); margin-bottom: 10px;"></i>
                    <h3>₹<?php echo number_format($stats['total_revenue']['total'] ?: 0, 2); ?></h3>
                    <p>Total Revenue</p>
                </div>
            </div>
        </div>
        
        <!-- Charts Section -->
        <div class="grid grid-1 mb-4">
            <!-- Revenue Chart -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-line"></i> Revenue Trend (Last 6 Months)</h3>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="300"></canvas>
                </div>
            </div>
            
            <!-- Rental Status Distribution - REMOVED AS PER USER REQUEST -->
            
        </div>
        
        <div class="grid grid-2">
            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> Recent Activity</h3>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h4>Recent Rentals</h4>
                        <?php if ($recent_rentals->num_rows > 0): ?>
                            <div style="overflow-x: auto; max-height: 200px;">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Customer</th>
                                            <th>Tool</th>
                                            <th>Status</th>
                                            <?php if ($columns_result->num_rows > 0): ?>
                                                <th>Payment</th>
                                            <?php endif; ?>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($rental = $recent_rentals->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars(substr($rental['first_name'], 0, 1) . '. ' . $rental['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars(substr($rental['tool_name'], 0, 15)) . (strlen($rental['tool_name']) > 15 ? '...' : ''); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $rental['status']; ?>">
                                                        <?php echo ucfirst($rental['status']); ?>
                                                    </span>
                                                </td>
                                                <?php if ($columns_result->num_rows > 0): ?>
                                                    <td>
                                                        <?php if (isset($rental['payment_method']) && $rental['payment_method'] === 'cod'): ?>
                                                            <span class="badge" style="background-color: #17a2b8;">
                                                                COD
                                                            </span>
                                                        <?php elseif (isset($rental['payment_method']) && $rental['payment_method'] === 'full'): ?>
                                                            <span class="badge" style="background-color: #28a745;">
                                                                Full
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge" style="background-color: #6c757d;">
                                                                N/A
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endif; ?>
                                                <td>₹<?php echo number_format($rental['total_amount'], 0); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-muted">No recent rentals</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Tools Needing Attention -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-exclamation-triangle"></i> Tools Needing Attention</h3>
                </div>
                <div class="card-body">
                    <?php if ($attention_tools->num_rows > 0): ?>
                        <div style="overflow-x: auto; max-height: 250px;">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Tool Name</th>
                                        <th>Status</th>
                                        <th>Stock</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($tool = $attention_tools->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(substr($tool['name'], 0, 20)) . (strlen($tool['name']) > 20 ? '...' : ''); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $tool['status']; ?>">
                                                    <?php echo ucfirst($tool['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($tool['quantity_available'] <= 1): ?>
                                                    <span style="color: #DC3545; font-weight: bold;">
                                                        <?php echo $tool['quantity_available']; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <?php echo $tool['quantity_available']; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="manage_tools.php?edit=<?php echo $tool['tool_id']; ?>" class="btn btn-secondary btn-sm">
                                                    Edit
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-2">
                            <a href="manage_tools.php" class="btn btn-secondary btn-sm">Manage All Tools</a>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-success">All tools are in good condition!</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Users -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-user-friends"></i> Recent Users</h3>
                </div>
                <div class="card-body">
                    <?php 
                    // Get recent users
                    $recent_users_query = "SELECT user_id, username, first_name, last_name, email, created_at FROM users WHERE role = 'customer' ORDER BY created_at DESC LIMIT 5";
                    $recent_users_result = $conn->query($recent_users_query);
                    
                    if ($recent_users_result->num_rows > 0): ?>
                        <div style="overflow-x: auto; max-height: 250px;">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($user = $recent_users_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <a href="manage_users.php" class="btn btn-secondary btn-sm">
                                                    <i class="fas fa-eye"></i> View All
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted">No users found</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Initialize Charts -->
<script>
// Revenue Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: [
            <?php 
            $months = [];
            for ($i = 5; $i >= 0; $i--) {
                $months[] = date('Y-m', strtotime("-$i months"));
            }
            foreach ($months as $month) {
                echo "'" . date('M Y', strtotime($month)) . "',";
            }
            ?>
        ],
        datasets: [{
            label: 'Revenue (₹)',
            data: [
                <?php 
                foreach ($months as $month) {
                    echo isset($revenue_data[$month]) ? $revenue_data[$month] . ',' : '0,';
                }
                ?>
            ],
            borderColor: '#FFC107',
            backgroundColor: 'rgba(255, 193, 7, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₹' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

// Rental Status Chart - REMOVED AS PER USER REQUEST

// Payment Method Chart (only initialize if columns exist)

</script>

<?php include '../includes/footer.php'; ?>