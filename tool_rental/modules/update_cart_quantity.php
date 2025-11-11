<?php
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to update cart items']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$cart_id = intval($_POST['cart_id']);
$tool_id = intval($_POST['tool_id']);
$new_quantity = intval($_POST['quantity']);
$user_id = $_SESSION['user_id'];

// Validate inputs
if (empty($cart_id) || empty($tool_id) || $new_quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

// Get current cart item
$cart_query = "SELECT c.*, t.quantity_available FROM cart c JOIN tools t ON c.tool_id = t.tool_id WHERE c.cart_id = ? AND c.user_id = ?";
$cart_stmt = $conn->prepare($cart_query);
$cart_stmt->bind_param("ii", $cart_id, $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();

if ($cart_result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Cart item not found']);
    exit();
}

$cart_item = $cart_result->fetch_assoc();

// Check if enough units are available
if ($new_quantity > $cart_item['quantity_available']) {
    echo json_encode(['success' => false, 'message' => "Only {$cart_item['quantity_available']} units available for this tool"]);
    exit();
}

// Check for conflicts with existing rentals for the selected dates
$rental_conflict_query = "SELECT COUNT(*) as conflicting_rentals FROM rentals 
                         WHERE tool_id = ? AND status = 'active'
                         AND (rental_date < ? AND return_date > ?)";
$conflict_stmt = $conn->prepare($rental_conflict_query);
$conflict_stmt->bind_param("iss", $tool_id, $cart_item['rental_end_date'], $cart_item['rental_start_date']);
$conflict_stmt->execute();
$conflict_result = $conflict_stmt->get_result();
$conflict_data = $conflict_result->fetch_assoc();
$conflicting_rentals = $conflict_data['conflicting_rentals'];

// Calculate available units after accounting for conflicts
$available_units = $cart_item['quantity_available'] - $conflicting_rentals;

if ($new_quantity > $available_units) {
    echo json_encode(['success' => false, 'message' => "Only $available_units units of this tool are available for the selected dates"]);
    exit();
}

// Update the quantity in cart
$update_query = "UPDATE cart SET quantity = ? WHERE cart_id = ? AND user_id = ?";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param("iii", $new_quantity, $cart_id, $user_id);

if ($update_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => "Quantity updated to $new_quantity unit(s)"]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update cart item']);
}
?>