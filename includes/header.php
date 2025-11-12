<?php
// Get cart count for display
$cart_count = 0;
$notification_count = 0;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Get cart count for display
    $cart_count_query = "SELECT COUNT(*) as count FROM cart WHERE user_id = ?";
    $cart_stmt = $conn->prepare($cart_count_query);
    $cart_stmt->bind_param("i", $user_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    $cart_data = $cart_result->fetch_assoc();
    $cart_count = $cart_data ? $cart_data['count'] : 0;
    
    // Get unread notification count for display
    // Only count actual notifications, not requests
    if ($_SESSION['role'] !== 'admin') {
        $notification_count_query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0 AND notification_type != 'availability_request'";
        $notification_stmt = $conn->prepare($notification_count_query);
        $notification_stmt->bind_param("i", $user_id);
        $notification_stmt->execute();
        $notification_result = $notification_stmt->get_result();
        $notification_data = $notification_result->fetch_assoc();
        $notification_count = $notification_data ? $notification_data['count'] : 0;
    }
}

// Get current page for active navigation
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tool-Kart - Professional Tool Rental Service</title>
    <?php
    $base_path = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '../' : '';
    ?>
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Animated Background Pattern -->
    <div class="animated-background"></div>
    
    <header class="header">
        <div class="nav-container">
            <a href="<?php echo (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') ? $base_path . 'admin/admin_dashboard.php' : $base_path . 'index.php'; ?>" class="logo">
                <i class="fas fa-tools"></i> Tool-Kart
            </a>
            
            <nav>
                <ul class="nav-menu">
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                        <!-- Admin Navigation -->
                        <li><a href="<?php echo $base_path; ?>admin/admin_dashboard.php" class="<?php echo ($current_page == 'admin_dashboard.php') ? 'active' : ''; ?>">Dashboard</a></li>
                        <li><a href="<?php echo $base_path; ?>admin/manage_tools.php" class="<?php echo ($current_page == 'manage_tools.php') ? 'active' : ''; ?>">Manage Tools</a></li>
                        <li><a href="<?php echo $base_path; ?>admin/manage_rentals.php" class="<?php echo ($current_page == 'manage_rentals.php') ? 'active' : ''; ?>">Manage Rentals</a></li>
                        <li><a href="<?php echo $base_path; ?>admin/manage_categories.php" class="<?php echo ($current_page == 'manage_categories.php') ? 'active' : ''; ?>">Manage Categories</a></li>
                        <li><a href="<?php echo $base_path; ?>admin/manage_faq.php" class="<?php echo ($current_page == 'manage_faq.php') ? 'active' : ''; ?>">Manage FAQ</a></li>
                    <?php else: ?>
                        <!-- Customer Navigation -->
                        <li><a href="<?php echo $base_path; ?>index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">Home</a></li>
                        <li><a href="<?php echo $base_path; ?>browse_tools.php" class="<?php echo ($current_page == 'browse_tools.php') ? 'active' : ''; ?>">Browse Tools</a></li>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li><a href="<?php echo $base_path; ?>user_dashboard.php" class="<?php echo ($current_page == 'user_dashboard.php') ? 'active' : ''; ?>">My Rentals</a></li>
                        <?php endif; ?>
                        <li><a href="<?php echo $base_path; ?>index.php#about-section" class="<?php echo ($current_page == 'index.php' && strpos($_SERVER['REQUEST_URI'], '#about-section') !== false) ? 'active' : ''; ?>">About</a></li>
                        <li><a href="<?php echo $base_path; ?>index.php#faq-section" class="<?php echo ($current_page == 'index.php' && strpos($_SERVER['REQUEST_URI'], '#faq-section') !== false) ? 'active' : ''; ?>">FAQ</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            
            <div class="nav-actions">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['role'] !== 'admin'): ?>
                        <!-- Notification icon for customers -->
                        <a href="<?php echo $base_path; ?>notifications.php" class="notification-icon">
                            <i class="fas fa-bell"></i>
                            <?php if ($notification_count > 0): ?>
                                <span class="notification-count"><?php echo $notification_count; ?></span>
                            <?php endif; ?>
                        </a>
                        
                        <!-- Show cart only for customers -->
                        <a href="<?php echo $base_path; ?>view_cart.php" class="cart-icon">
                            <i class="fas fa-shopping-cart"></i>
                            <?php if ($cart_count > 0): ?>
                                <span class="cart-count"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                    <div class="d-flex align-items-center gap-3">
                    <div class="user-dropdown">
                        <span class="nav-user">
                            Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?>
                            <?php if ($_SESSION['role'] == 'admin'): ?>
                                <small style="color: var(--accent-yellow); font-weight: bold;">(Admin)</small>
                            <?php endif; ?>
                            <i class="fas fa-chevron-down"></i>
                        </span>
                        <div class="dropdown-content">
                            <?php if ($_SESSION['role'] == 'admin'): ?>
                                <a href="<?php echo $base_path; ?>admin/manage_users.php"><i class="fas fa-users"></i> Manage Users</a>
                                <a href="<?php echo $base_path; ?>admin/manage_admins.php"><i class="fas fa-user-shield"></i> Manage Admins</a>
                                <a href="<?php echo $base_path; ?>admin/manage_settings.php"><i class="fas fa-cog"></i> Manage Settings</a>
                            <?php else: ?>
                                <a href="<?php echo $base_path; ?>user_settings.php"><i class="fas fa-user-cog"></i> Settings</a>
                            <?php endif; ?>
                            <a href="<?php echo $base_path; ?>modules/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                    </div>
                <?php else: ?>
                    <a href="<?php echo $base_path; ?>login.php" class="btn btn-secondary">Login</a>
                    <a href="<?php echo $base_path; ?>register.php" class="btn btn-primary">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    
    <main class="main-content">
<style>
/* Notification Icon Styles */
.notification-icon {
    position: relative;
    display: inline-block;
    color: #666;
    font-size: 1.2rem;
    margin-right: 15px;
    text-decoration: none;
    transition: color 0.3s;
}

.notification-icon:hover {
    color: var(--accent-yellow);
}

.notification-count {
    position: absolute;
    top: -8px;
    right: -8px;
    background-color: var(--accent-yellow);
    color: #fff;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 0.7rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

/* Cart Icon Styles */
.cart-icon {
    position: relative;
    display: inline-block;
    color: #666;
    font-size: 1.2rem;
    margin-right: 15px;
    text-decoration: none;
    transition: color 0.3s;
}

.cart-icon:hover {
    color: var(--accent-yellow);
}

.cart-count {
    position: absolute;
    top: -8px;
    right: -8px;
    background-color: var(--accent-yellow);
    color: #fff;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 0.7rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

/* Make header login button more visible */
.nav-actions .btn-secondary {
    background-color: #f8f9fa;
    color: #2D2D2D;
    border: 2px solid #2D2D2D;
}

.nav-actions .btn-secondary:hover {
    background-color: #2D2D2D;
    color: white;
    border-color: #2D2D2D;
}

/* User Dropdown Styles */
.user-dropdown {
    position: relative;
    display: inline-block;
}

.user-dropdown .nav-user {
    cursor: pointer;
    padding: 8px 12px;
    border-radius: 4px;
    transition: background-color 0.3s;
    margin-top: 5px; /* Move slightly down */
}

.user-dropdown .nav-user:hover {
    background-color: rgba(255, 193, 7, 0.1);
}

.dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    background-color: #fff;
    min-width: 200px;
    box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
    z-index: 1;
    border-radius: 4px;
    overflow: hidden;
}

.dropdown-content a {
    color: #333;
    padding: 12px 16px;
    text-decoration: none;
    display: block;
    transition: background-color 0.3s;
}

.dropdown-content a:hover {
    background-color: #f8f9fa;
}

.dropdown-content a i {
    margin-right: 8px;
    width: 20px;
    text-align: center;
}

.user-dropdown:hover .dropdown-content {
    display: block;
}


</style>