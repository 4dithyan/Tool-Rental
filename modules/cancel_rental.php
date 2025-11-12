<?php
require_once '../includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Please log in to cancel rentals']);
    exit();
}

// Check if rental ID is provided
if (!isset($_POST['rental_id']) || !is_numeric($_POST['rental_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid rental ID']);
    exit();
}

$rental_id = intval($_POST['rental_id']);
$user_id = $_SESSION['user_id'];

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Get rental details and verify ownership
    $rental_query = "SELECT r.*, t.name as tool_name, t.quantity_available 
                     FROM rentals r 
                     JOIN tools t ON r.tool_id = t.tool_id 
                     WHERE r.rental_id = ? AND r.user_id = ? AND r.status = 'active'";
    $rental_stmt = $conn->prepare($rental_query);
    $rental_stmt->bind_param("ii", $rental_id, $user_id);
    $rental_stmt->execute();
    $rental_result = $rental_stmt->get_result();
    
    if ($rental_result->num_rows === 0) {
        throw new Exception('Rental not found or cannot be cancelled');
    }
    
    $rental = $rental_result->fetch_assoc();
    
    // Update rental status to cancelled with time information
    $cancel_query = "UPDATE rentals SET 
                     status = 'cancelled', 
                     actual_return_date = CURDATE(),
                     actual_return_time = CURTIME()
                     WHERE rental_id = ?";
    $cancel_stmt = $conn->prepare($cancel_query);
    $cancel_stmt->bind_param("i", $rental_id);
    
    if (!$cancel_stmt->execute()) {
        throw new Exception('Failed to cancel rental');
    }
    
    // Restore tool quantity - FIXED QUERY
    $restore_query = "UPDATE tools SET quantity_available = quantity_available + ? WHERE tool_id = ?";
    $restore_stmt = $conn->prepare($restore_query);
    $restore_stmt->bind_param("ii", $rental['quantity'], $rental['tool_id']);
    
    if (!$restore_stmt->execute()) {
        throw new Exception('Failed to restore tool quantity');
    }
    
    // Commit transaction
    $conn->commit();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Rental cancelled successfully! ' . $rental['tool_name'] . ' is now available for other customers.',
        'rental_id' => $rental_id,
        'tool_name' => $rental['tool_name']
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>