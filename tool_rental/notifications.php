<?php
require_once 'includes/db_connect.php';
require_once 'includes/settings_helper.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] == 'admin') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Mark all notifications as read
$mark_read_query = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
$mark_stmt = $conn->prepare($mark_read_query);
$mark_stmt->bind_param("i", $user_id);
$mark_stmt->execute();

// Get all notifications for the user
// Exclude availability requests as they're not actual notifications to display
$notifications_query = "SELECT n.*, t.name as tool_name FROM notifications n 
                       JOIN tools t ON n.tool_id = t.tool_id 
                       WHERE n.user_id = ? AND n.notification_type != 'availability_request'
                       ORDER BY n.created_at DESC";
$notifications_stmt = $conn->prepare($notifications_query);
$notifications_stmt->bind_param("i", $user_id);
$notifications_stmt->execute();
$notifications_result = $notifications_stmt->get_result();

include 'includes/header.php';
?>

<div class="container">
    <div class="main-content">
        <h1><i class="fas fa-bell"></i> Notifications</h1>
        <p class="mb-3">Stay updated with your rental notifications</p>
        
        <?php if ($notifications_result->num_rows > 0): ?>
            <div class="notifications-list">
                <?php while ($notification = $notifications_result->fetch_assoc()): ?>
                    <div class="notification-card <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                        <a href="tool_details.php?id=<?php echo $notification['tool_id']; ?>" class="notification-link">
                            <div class="notification-header">
                                <div class="notification-icon">
                                    <?php if ($notification['notification_type'] == 'availability'): ?>
                                        <i class="fas fa-box-open"></i>
                                    <?php elseif ($notification['notification_type'] == 'return_date'): ?>
                                        <i class="fas fa-calendar-check"></i>
                                    <?php elseif ($notification['notification_type'] == 'returned'): ?>
                                        <i class="fas fa-undo"></i>
                                    <?php elseif ($notification['notification_type'] == 'damage_fine'): ?>
                                        <i class="fas fa-exclamation-triangle"></i>
                                    <?php elseif ($notification['notification_type'] == 'late_fine'): ?>
                                        <i class="fas fa-clock"></i>
                                    <?php elseif ($notification['notification_type'] == 'special_offer'): ?>
                                        <i class="fas fa-tag"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="notification-content">
                                    <h4>
                                        <?php 
                                        switch ($notification['notification_type']) {
                                            case 'availability':
                                                echo "Tool Available: " . htmlspecialchars($notification['tool_name']);
                                                break;
                                            case 'return_date':
                                                echo "Return Reminder: " . htmlspecialchars($notification['tool_name']);
                                                break;
                                            case 'returned':
                                                echo "Rental Returned: " . htmlspecialchars($notification['tool_name']);
                                                break;
                                            case 'damage_fine':
                                                echo "Damage Fee Applied: " . htmlspecialchars($notification['tool_name']);
                                                break;
                                            case 'late_fine':
                                                echo "Late Fee Applied: " . htmlspecialchars($notification['tool_name']);
                                                break;
                                            case 'special_offer':
                                                echo "Special Offer: " . htmlspecialchars($notification['tool_name']);
                                                break;
                                            default:
                                                echo "Notification";
                                        }
                                        ?>
                                    </h4>
                                    <p class="notification-message">
                                        <?php 
                                        // Use custom message if available, otherwise use default messages
                                        if (!empty($notification['message'])) {
                                            echo htmlspecialchars($notification['message']);
                                        } else {
                                            switch ($notification['notification_type']) {
                                                case 'availability':
                                                    echo "The tool '" . htmlspecialchars($notification['tool_name']) . "' is now available for rental.";
                                                    break;
                                                case 'return_date':
                                                    // For return date notifications, we might want to store the return date in the notification
                                                    // For now, we'll just show a generic reminder message
                                                    echo "Reminder: Please return '" . htmlspecialchars($notification['tool_name']) . "' soon.";
                                                    break;
                                                case 'returned':
                                                    echo "Your rental of '" . htmlspecialchars($notification['tool_name']) . "' has been successfully returned. Thank you for using our service!";
                                                    break;
                                                case 'special_offer':
                                                    echo "Special offer on '" . htmlspecialchars($notification['tool_name']) . "' - Check it out now!";
                                                    break;
                                                default:
                                                    echo "You have a new notification.";
                                            }
                                        }
                                        ?>
                                    </p>
                                    <small class="notification-time">
                                        <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-bell-slash" style="font-size: 3rem; color: var(--accent-yellow); margin-bottom: 20px;"></i>
                    <h3>No Notifications</h3>
                    <p>You don't have any notifications at the moment.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.notifications-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.notification-card {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-light);
    padding: 20px;
    border-left: 4px solid var(--border-gray);
    transition: var(--transition);
    position: relative;
}

.notification-card:hover {
    box-shadow: var(--shadow-medium);
    transform: translateY(-2px);
}

.notification-card.unread {
    border-left-color: var(--accent-yellow);
    background-color: #fff8e1;
}

.notification-header {
    display: flex;
    gap: 15px;
}

.notification-icon {
    font-size: 1.5rem;
    color: var(--accent-yellow);
    min-width: 40px;
    text-align: center;
}

.notification-content h4 {
    margin: 0 0 10px 0;
    color: #212529;
}

.notification-message {
    margin: 0 0 10px 0;
    color: #666;
    line-height: 1.5;
}

.notification-time {
    color: #999;
    font-size: 0.85rem;
}

/* Notification Link Styles */
.notification-link {
    text-decoration: none;
    color: inherit;
    display: block;
    position: relative;
}

.notification-link:hover {
    text-decoration: none;
    color: inherit;
}

.notification-link::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 1;
}

.notification-link:hover .notification-card {
    box-shadow: var(--shadow-medium);
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .notification-header {
        flex-direction: column;
    }
    
    .notification-icon {
        margin-bottom: 10px;
    }
}
</style>

<?php include 'includes/footer.php'; ?>