<?php
require_once 'includes/db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to subscribe to notifications.']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if tool_id is provided
if (!isset($_POST['tool_id']) || !is_numeric($_POST['tool_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid tool ID.']);
    exit();
}

$tool_id = intval($_POST['tool_id']);

// Check if the tool exists and get tool name
$tool_query = "SELECT name FROM tools WHERE tool_id = ?";
$stmt = $conn->prepare($tool_query);
$stmt->bind_param("i", $tool_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Tool not found.']);
    exit();
}

$tool = $result->fetch_assoc();

// Check if user is already subscribed to notifications for this tool
$check_query = "SELECT id FROM notifications WHERE user_id = ? AND tool_id = ? AND notification_type = 'availability_request' AND is_read = 0";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ii", $user_id, $tool_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'You are already subscribed to notifications for ' . $tool['name']]);
    exit();
}

// Add notification request to database
$insert_query = "INSERT INTO notifications (user_id, tool_id, notification_type, is_read) VALUES (?, ?, 'availability_request', 0)";
$stmt = $conn->prepare($insert_query);
$stmt->bind_param("ii", $user_id, $tool_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true, 
        'message' => 'You have been added to the notification list for ' . $tool['name'] . '. You will be notified when it becomes available.'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to subscribe to notifications. Please try again.']);
}

$stmt->close();
$conn->close();
?>