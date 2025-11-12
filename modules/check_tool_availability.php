<?php
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

// For date conflict checking, we need start and end dates
$tool_id = isset($_GET['tool_id']) ? intval($_GET['tool_id']) : 0;
$rental_start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$rental_end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

if (!$tool_id || !is_numeric($tool_id)) {
    echo json_encode(['available' => false, 'message' => 'Invalid tool ID']);
    exit();
}

// Check tool availability - FIXED QUERY
$availability_query = "SELECT tool_id, name, quantity_available, status FROM tools WHERE tool_id = ? AND status = 'active'";
$stmt = $conn->prepare($availability_query);
$stmt->bind_param("i", $tool_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(['available' => false, 'message' => 'Tool not found or is not active']);
    exit();
}

$tool = $result->fetch_assoc();

// Check if tool is actually available (quantity > 0)
if ($tool['quantity_available'] <= 0) {
    echo json_encode([
        'available' => false,
        'quantity_available' => 0,
        'status' => $tool['status'],
        'message' => 'Tool is out of stock'
    ]);
    exit();
}

// If we have dates, check for conflicts with existing rentals
if ($rental_start_date && $rental_end_date) {
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
    
    // Calculate available units after accounting for conflicts
    $available_units = $tool['quantity_available'] - $conflicting_rentals;
    
    // Check if there are available units for the selected dates
    if ($available_units <= 0) {
        // Provide more detailed information about the conflict
        echo json_encode([
            'available' => false,
            'quantity_available' => 0,
            'status' => $tool['status'],
            'message' => "All {$tool['quantity_available']} units of this tool are already rented for the selected dates. Please choose different dates or another tool."
        ]);
        exit();
    } else {
        // Tool is available with the number of units that are not conflicting
        echo json_encode([
            'available' => true,
            'quantity_available' => $available_units,
            'status' => $tool['status'],
            'message' => 'Tool is available'
        ]);
        exit();
    }
}

// Tool is available if status is active and quantity > 0, and no date conflicts
echo json_encode([
    'available' => true,
    'quantity_available' => $tool['quantity_available'],
    'status' => $tool['status'],
    'message' => 'Tool is available'
]);
?>