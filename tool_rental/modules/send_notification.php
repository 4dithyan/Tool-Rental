<?php
require_once __DIR__ . '/../includes/db_connect.php';

// Function to send notification to user when admin marks damage or late fees
function sendRentalNotification($rental_id, $notification_type, $message = '') {
    global $conn;
    
    // Get rental details
    $rental_query = "SELECT r.user_id, r.tool_id, t.name as tool_name, r.damage_fine, r.late_fine FROM rentals r 
                     JOIN tools t ON r.tool_id = t.tool_id 
                     WHERE r.rental_id = ?";
    $rental_stmt = $conn->prepare($rental_query);
    $rental_stmt->bind_param("i", $rental_id);
    $rental_stmt->execute();
    $rental_result = $rental_stmt->get_result();
    
    if ($rental_result->num_rows == 0) {
        return false;
    }
    
    $rental = $rental_result->fetch_assoc();
    $user_id = $rental['user_id'];
    $tool_id = $rental['tool_id'];
    
    // Create notification message based on type
    $notification_message = '';
    switch ($notification_type) {
        case 'damage_fine':
            if (!empty($message)) {
                $notification_message = $message;
            } else {
                $notification_message = "Damage fee of ₹" . number_format($rental['damage_fine'], 2) . " has been applied to your rental of '" . $rental['tool_name'] . "'.";
            }
            break;
        case 'late_fine':
            if (!empty($message)) {
                $notification_message = $message;
            } else {
                $notification_message = "Late fee of ₹" . number_format($rental['late_fine'], 2) . " has been applied to your rental of '" . $rental['tool_name'] . "'.";
            }
            break;
        case 'returned':
            $notification_message = "Your rental of '" . $rental['tool_name'] . "' has been marked as returned. Thank you for using our service!";
            break;
        case 'return_reminder':
            $notification_message = "Reminder: Please return '" . $rental['tool_name'] . "' by " . date('M j, Y');
            break;
        case 'availability':
            $notification_message = "The tool '" . $rental['tool_name'] . "' is now available for rental!";
            break;
        default:
            $notification_message = $message ?: "You have a new notification about your rental of '" . $rental['tool_name'] . "'";
    }
    
    // Insert notification
    $insert_query = "INSERT INTO notifications (user_id, tool_id, notification_type, message, is_read) VALUES (?, ?, ?, ?, 0)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("iiss", $user_id, $tool_id, $notification_type, $notification_message);
    
    return $insert_stmt->execute();
}

// Function to send return date reminder notifications
function sendReturnReminderNotifications() {
    global $conn;
    
    // Get rentals that are due tomorrow
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    $rentals_query = "SELECT r.rental_id, r.user_id, r.tool_id, t.name as tool_name 
                      FROM rentals r 
                      JOIN tools t ON r.tool_id = t.tool_id 
                      WHERE r.return_date = ? AND r.status = 'active'";
    $rentals_stmt = $conn->prepare($rentals_query);
    $rentals_stmt->bind_param("s", $tomorrow);
    $rentals_stmt->execute();
    $rentals_result = $rentals_stmt->get_result();
    
    $sent_count = 0;
    while ($rental = $rentals_result->fetch_assoc()) {
        // Insert notification
        $notification_message = "Reminder: Please return '" . $rental['tool_name'] . "' by " . date('M j, Y', strtotime($tomorrow));
        $insert_query = "INSERT INTO notifications (user_id, tool_id, notification_type, message, is_read) VALUES (?, ?, 'return_date', ?, 0)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("iis", $rental['user_id'], $rental['tool_id'], $notification_message);
        
        if ($insert_stmt->execute()) {
            $sent_count++;
        }
    }
    
    return $sent_count;
}

// Function to notify users when a tool becomes available
function notifyToolAvailability($tool_id) {
    global $conn;
    
    // Get tool name
    $tool_query = "SELECT name FROM tools WHERE tool_id = ?";
    $tool_stmt = $conn->prepare($tool_query);
    $tool_stmt->bind_param("i", $tool_id);
    $tool_stmt->execute();
    $tool_result = $tool_stmt->get_result();
    
    if ($tool_result->num_rows == 0) {
        return false;
    }
    
    $tool = $tool_result->fetch_assoc();
    $tool_name = $tool['name'];
    
    // Get all users who want to be notified about this tool's availability
    // Only select users who have requested availability notifications (not the notifications themselves)
    $notifications_query = "SELECT user_id FROM notifications 
                           WHERE tool_id = ? AND notification_type = 'availability_request' AND is_read = 0";
    $notifications_stmt = $conn->prepare($notifications_query);
    $notifications_stmt->bind_param("i", $tool_id);
    $notifications_stmt->execute();
    $notifications_result = $notifications_stmt->get_result();
    
    $notified_count = 0;
    while ($notification = $notifications_result->fetch_assoc()) {
        // Create a new availability notification
        $notification_message = "Great news! The tool '" . $tool_name . "' you requested is now available for rental!";
        $insert_query = "INSERT INTO notifications (user_id, tool_id, notification_type, message, is_read) VALUES (?, ?, 'availability', ?, 0)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("iis", $notification['user_id'], $tool_id, $notification_message);
        
        if ($insert_stmt->execute()) {
            $notified_count++;
        }
    }
    
    return $notified_count;
}
?>