<?php
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to add items to cart']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$tool_id = intval($_POST['tool_id']);
$rental_start_date = $_POST['rental_start_date'];
$rental_end_date = $_POST['rental_end_date'];
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
$user_id = $_SESSION['user_id'];

// Validate inputs
if (empty($tool_id) || empty($rental_start_date) || empty($rental_end_date)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

// Validate quantity
if ($quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Quantity must be at least 1']);
    exit();
}

// Validate dates
$start_date = new DateTime($rental_start_date);
$end_date = new DateTime($rental_end_date);
$today = new DateTime();
$today->setTime(0, 0, 0);

if ($start_date < $today) {
    echo json_encode(['success' => false, 'message' => 'Start date cannot be in the past']);
    exit();
}

if ($end_date <= $start_date) {
    echo json_encode(['success' => false, 'message' => 'End date must be after start date']);
    exit();
}

// Check tool availability
$tool_query = "SELECT * FROM tools WHERE tool_id = ? AND status = 'active' AND quantity_available >= ?";
$tool_stmt = $conn->prepare($tool_query);
$tool_stmt->bind_param("ii", $tool_id, $quantity);
$tool_stmt->execute();
$tool_result = $tool_stmt->get_result();

if ($tool_result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Not enough units available for this tool']);
    exit();
}

$tool = $tool_result->fetch_assoc();

// Check if tool is already in cart for the same dates
$check_cart_query = "SELECT cart_id, quantity FROM cart 
                     WHERE user_id = ? AND tool_id = ? 
                     AND (rental_start_date < ? AND rental_end_date > ?)";
$check_stmt = $conn->prepare($check_cart_query);
$check_stmt->bind_param("iiss", $user_id, $tool_id, $rental_end_date, $rental_start_date);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    // If tool is already in cart, update the quantity instead of blocking
    $existing_item = $check_result->fetch_assoc();
    $new_quantity = $existing_item['quantity'] + $quantity;
    
    // Check if the new quantity is still available
    if ($new_quantity > $tool['quantity_available']) {
        echo json_encode(['success' => false, 'message' => "Not enough units available. You already have {$existing_item['quantity']} in your cart, and only {$tool['quantity_available']} units are available."]);
        exit();
    }
    
    // Update the quantity in cart
    $update_query = "UPDATE cart SET quantity = ? WHERE cart_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ii", $new_quantity, $existing_item['cart_id']);
    
    if ($update_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => "Updated quantity in cart to $new_quantity units"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update item in cart']);
    }
    exit();
}

// Check for conflicts with existing rentals
// Count how many units are already rented for the selected dates
$rental_conflict_query = "SELECT COUNT(*) as conflicting_rentals FROM rentals 
                         WHERE tool_id = ? AND status = 'active'
                         AND (rental_date < ? AND return_date > ?)";
$conflict_stmt = $conn->prepare($rental_conflict_query);
$conflict_stmt->bind_param("iss", $tool_id, $rental_end_date, $rental_start_date);
$conflict_stmt->execute();
$conflict_result = $conflict_stmt->get_result();
$conflict_data = $conflict_result->fetch_assoc();
$conflicting_rentals = $conflict_data['conflicting_rentals'];

// Debug: Log the availability check
error_log("Tool ID: $tool_id, Available quantity: {$tool['quantity_available']}, Conflicting rentals: $conflicting_rentals, Requested quantity: $quantity");
error_log("User request: $rental_start_date to $rental_end_date");

// Check if enough units are available after accounting for conflicts
$available_units = $tool['quantity_available'] - $conflicting_rentals;
if ($quantity > $available_units) {
    error_log("BLOCKING RENTAL: Requested $quantity units but only $available_units available ($conflicting_rentals conflicts found)");
    echo json_encode(['success' => false, 'message' => "Only $available_units units of this tool are available for the selected dates. Please reduce quantity or choose different dates."]);
    exit();
} else {
    error_log("ALLOWING RENTAL: Tool {$tool['name']} - {$tool['quantity_available']} units available, $conflicting_rentals conflicts found, $quantity requested");
}

// Add to cart with quantity
$session_id = session_id();
$insert_query = "INSERT INTO cart (session_id, user_id, tool_id, rental_start_date, rental_end_date, quantity) 
                VALUES (?, ?, ?, ?, ?, ?)";
$insert_stmt = $conn->prepare($insert_query);
$insert_stmt->bind_param("siissi", $session_id, $user_id, $tool_id, $rental_start_date, $rental_end_date, $quantity);

if ($insert_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => "Added $quantity unit(s) to cart successfully"]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add item to cart']);
}
?>