<?php
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] == 'admin') {
    echo json_encode(['success' => false, 'message' => 'Please login to set notifications']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$tool_id = isset($input['tool_id']) ? intval($input['tool_id']) : 0;
$notification_type = isset($input['notification_type']) ? $input['notification_type'] : 'availability';

// Validate inputs
if (!$tool_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid tool ID']);
    exit();
}

// Check if tool exists and get tool name
$tool_query = "SELECT tool_id, name FROM tools WHERE tool_id = ?";
$tool_stmt = $conn->prepare($tool_query);
$tool_stmt->bind_param("i", $tool_id);
$tool_stmt->execute();
$tool_result = $tool_stmt->get_result();

if ($tool_result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Tool not found']);
    exit();
}

$tool = $tool_result->fetch_assoc();

// Check if notification already exists for this user and tool
// For availability requests, check for the specific request type
$check_notification_type = $notification_type;
if ($notification_type == 'availability') {
    $check_notification_type = 'availability_request';
}
$check_query = "SELECT id FROM notifications WHERE user_id = ? AND tool_id = ? AND notification_type = ? AND is_read = 0";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("iis", $user_id, $tool_id, $check_notification_type);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'You are already subscribed to notifications for ' . $tool['name']]);
    exit();
}

// Create notification
// For availability requests, use a different notification type to distinguish from actual notifications
if ($notification_type == 'availability') {
    $notification_type = 'availability_request';
}
$insert_query = "INSERT INTO notifications (user_id, tool_id, notification_type, is_read) VALUES (?, ?, ?, 0)";
$insert_stmt = $conn->prepare($insert_query);
$insert_stmt->bind_param("iis", $user_id, $tool_id, $notification_type);

if ($insert_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'You have been added to the notification list for ' . $tool['name'] . '. You will be notified when it becomes available.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to set notification']);
}
?>