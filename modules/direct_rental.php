<?php
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to rent tools']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$tool_id = intval($_POST['tool_id']);
$rental_start_date = $_POST['rental_start_date'];
$rental_end_date = $_POST['rental_end_date'];
$rental_start_time = date('H:i:s'); // Set to current time
$rental_end_time = '18:00:00'; // Default end time
$address = isset($_POST['address']) ? trim($_POST['address']) : '';
$payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'full';
$total_amount = floatval($_POST['total_amount']);
$rental_days = intval($_POST['rental_days']);
$user_id = $_SESSION['user_id'];

// Validate inputs
if (empty($tool_id) || empty($rental_start_date) || empty($rental_end_date)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
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

// Validate payment method
if (!in_array($payment_method, ['full', 'cod'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment method']);
    exit();
}

// Calculate deposit amount based on payment method
$deposit_amount = 0;
if ($payment_method === 'cod') {
    $deposit_amount = $total_amount * 0.20; // 20% deposit for COD
} else {
    $deposit_amount = $total_amount; // Full payment
}

// Handle proof image upload if provided
$proof_image_path = null;
if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] == 0) {
    $upload_dir = '../uploads/proofs/';
    
    // Create uploads directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $file_extension = pathinfo($_FILES['proof_image']['name'], PATHINFO_EXTENSION);
    $unique_filename = 'proof_' . uniqid() . '_' . time() . '.' . $file_extension;
    $target_file = $upload_dir . $unique_filename;
    
    // Validate file type (only allow images)
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array(strtolower($file_extension), $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Only JPG, JPEG, PNG, and GIF files are allowed for proof images.']);
        exit();
    }
    
    // Validate file size (max 5MB)
    if ($_FILES['proof_image']['size'] > 5000000) {
        echo json_encode(['success' => false, 'message' => 'Proof image file size must be less than 5MB.']);
        exit();
    }
    
    // Move uploaded file
    if (move_uploaded_file($_FILES['proof_image']['tmp_name'], $target_file)) {
        // Store relative path in database
        $proof_image_path = 'uploads/proofs/' . $unique_filename;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload proof image.']);
        exit();
    }
}

// Get tool details and check availability - FIXED QUERY
$tool_query = "SELECT tool_id, name, quantity_available, status FROM tools WHERE tool_id = ? AND status = 'active'";
$tool_stmt = $conn->prepare($tool_query);
$tool_stmt->bind_param("i", $tool_id);
$tool_stmt->execute();
$tool_result = $tool_stmt->get_result();

if ($tool_result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Tool not found or is not active']);
    exit();
}

$tool = $tool_result->fetch_assoc();

// Check if tool is actually available (quantity > 0)
if ($tool['quantity_available'] <= 0) {
    echo json_encode(['success' => false, 'message' => 'Tool is out of stock']);
    exit();
}

// Check for conflicts with existing rentals
// Count how many units are already rented for the selected dates
// Fixed logic: More precise date overlap detection
$rental_conflict_query = "SELECT COUNT(*) as conflicting_rentals FROM rentals 
                         WHERE tool_id = ? AND status = 'active'
                         AND (rental_date < ? AND return_date > ?)";
$conflict_stmt = $conn->prepare($rental_conflict_query);
$conflict_stmt->bind_param("iss", $tool_id, $rental_end_date, $rental_start_date);
$conflict_stmt->execute();
$conflict_result = $conflict_stmt->get_result();
$conflict_data = $conflict_result->fetch_assoc();
$conflicting_rentals = $conflict_data['conflicting_rentals'];

// Check if all available units are already rented for the selected dates
// Fixed logic: Allow rental if there are still units available after accounting for conflicts
if ($conflicting_rentals >= $tool['quantity_available']) {
    error_log("BLOCKING DIRECT RENTAL: Tool {$tool['name']} - All {$tool['quantity_available']} units are rented ($conflicting_rentals conflicts found)");
    // Updated message to be more clear
    echo json_encode(['success' => false, 'message' => "All {$tool['quantity_available']} units of this tool are already rented for the selected dates. Please choose different dates or another tool."]);
    exit();
} else {
    error_log("ALLOWING DIRECT RENTAL: Tool {$tool['name']} - {$tool['quantity_available']} units available, $conflicting_rentals conflicts found");
}

// Start transaction for rental creation
$conn->begin_transaction();

try {
    // Double-check availability with lock
    $check_query = "SELECT quantity_available, name FROM tools WHERE tool_id = ? FOR UPDATE";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $tool_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $current_tool = $check_result->fetch_assoc();

    if ($current_tool['quantity_available'] <= 0) {
        throw new Exception("'{$current_tool['name']}' is now out of stock. Another user may have just rented the last available unit.");
    }

    // Update user address if provided
    if (!empty($address)) {
        $address_query = "UPDATE users SET address = ? WHERE user_id = ?";
        $address_stmt = $conn->prepare($address_query);
        $address_stmt->bind_param("si", $address, $user_id);
        $address_stmt->execute();
    }

    // Create rental record with payment method, deposit amount, proof image, and time information
    if ($proof_image_path) {
        $rental_query = "INSERT INTO rentals (user_id, tool_id, rental_date, rental_time, return_date, return_time, quantity, total_amount, payment_method, deposit_amount, proof_image) 
                        VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?)";
        $rental_stmt = $conn->prepare($rental_query);
        $rental_stmt->bind_param("iisssisssss", $user_id, $tool_id, $rental_start_date, $rental_start_time, $rental_end_date, $rental_end_time, $total_amount, $payment_method, $deposit_amount, $proof_image_path);
    } else {
        $rental_query = "INSERT INTO rentals (user_id, tool_id, rental_date, rental_time, return_date, return_time, quantity, total_amount, payment_method, deposit_amount) 
                        VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, ?)";
        $rental_stmt = $conn->prepare($rental_query);
        $rental_stmt->bind_param("iisssissss", $user_id, $tool_id, $rental_start_date, $rental_start_time, $rental_end_date, $rental_end_time, $total_amount, $payment_method, $deposit_amount);
    }
    
    if (!$rental_stmt->execute()) {
        throw new Exception("Failed to create rental");
    }

    $rental_id = $conn->insert_id;

    // Update tool quantity
    $update_query = "UPDATE tools SET quantity_available = quantity_available - 1 WHERE tool_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("i", $tool_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception("Failed to update tool availability");
    }

    $conn->commit();
    
    // Prepare success message based on payment method
    $message = "Tool rented successfully!";
    if ($payment_method === 'cod') {
        $message .= " A deposit of ₹" . number_format($deposit_amount, 2) . " has been charged to your account.";
    } else {
        $message .= " The full amount of ₹" . number_format($total_amount, 2) . " has been charged to your account.";
    }
    
    if ($proof_image_path) {
        $message .= " Proof image uploaded successfully.";
    }
    
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'rental_id' => $rental_id,
        'total_amount' => $total_amount,
        'deposit_amount' => $deposit_amount,
        'rental_days' => $rental_days,
        'payment_method' => $payment_method
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>