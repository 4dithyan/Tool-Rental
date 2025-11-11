<?php
require_once 'includes/db_connect.php';
require_once 'includes/settings_helper.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get cart items with tool details
$cart_query = "SELECT c.cart_id, c.session_id, c.user_id, c.tool_id, c.rental_start_date, c.rental_end_date, c.quantity, c.added_at,
                      t.name, t.daily_rate, t.image_url, t.quantity_available,
                      DATEDIFF(c.rental_end_date, c.rental_start_date) as rental_days
                      FROM cart c 
                      JOIN tools t ON c.tool_id = t.tool_id 
                      WHERE c.user_id = ? 
                      ORDER BY c.added_at DESC";
$stmt = $conn->prepare($cart_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_result = $stmt->get_result();

$cart_items = [];
$total_amount = 0;
$has_unavailable = false;

while ($item = $cart_result->fetch_assoc()) {
    $item['subtotal'] = $item['daily_rate'] * $item['rental_days'] * $item['quantity'];
    $total_amount += $item['subtotal'];
    
    // Check if enough units are available
    if ($item['quantity_available'] < $item['quantity']) {
        $has_unavailable = true;
    }
    
    $cart_items[] = $item;
}

// Redirect if cart is empty
if (empty($cart_items)) {
    header('Location: view_cart.php');
    exit();
}

// Redirect if has unavailable items
if ($has_unavailable) {
    header('Location: view_cart.php');
    exit();
}

// Get user details
$user_query = "SELECT * FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

// Check if user has previously uploaded ID proof
$id_proof_query = "SELECT id_proof_image FROM rentals WHERE user_id = ? AND id_proof_image IS NOT NULL ORDER BY created_at DESC LIMIT 1";
$id_proof_stmt = $conn->prepare($id_proof_query);
$id_proof_stmt->bind_param("i", $user_id);
$id_proof_stmt->execute();
$id_proof_result = $id_proof_stmt->get_result();
$has_id_proof = $id_proof_result->num_rows > 0;
$user_id_proof = $has_id_proof ? $id_proof_result->fetch_assoc()['id_proof_image'] : null;

// Process checkout
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn->begin_transaction();
    
    try {
        // Update user address if provided
        if (!empty($_POST['address'])) {
            $address = trim($_POST['address']);
            $update_address_query = "UPDATE users SET address = ? WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_address_query);
            $update_stmt->bind_param("si", $address, $user_id);
            $update_stmt->execute();
            $user['address'] = $address;
        }
        
        // Update user phone number if provided
        if (isset($_POST['phone'])) {
            $phone = trim($_POST['phone']);
            // Only update if phone number is provided and different from current
            if ($phone !== $user['phone']) {
                $update_phone_query = "UPDATE users SET phone = ? WHERE user_id = ?";
                $update_stmt = $conn->prepare($update_phone_query);
                $update_stmt->bind_param("si", $phone, $user_id);
                $update_stmt->execute();
                $user['phone'] = $phone;
            }
        }
        
        // Handle ID proof upload if provided or use existing one
        $id_proof_image_path = $user_id_proof; // Default to existing ID proof
        if (!$has_id_proof) {
            // For first-time users, ID proof is mandatory
            if (!isset($_FILES['id_proof']) || $_FILES['id_proof']['error'] != 0) {
                throw new Exception('ID proof is required for your first rental. Please upload a valid ID document.');
            }
            
            // Handle new ID proof upload
            if (isset($_FILES['id_proof']) && $_FILES['id_proof']['error'] == 0) {
                $upload_dir = 'uploads/id_proofs/';
                
                // Create uploads directory if it doesn't exist
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Generate unique filename
                $file_extension = pathinfo($_FILES['id_proof']['name'], PATHINFO_EXTENSION);
                $unique_filename = 'id_proof_' . uniqid() . '_' . time() . '.' . $file_extension;
                $target_file = $upload_dir . $unique_filename;
                
                // Validate file type (only allow images and PDF)
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
                if (!in_array(strtolower($file_extension), $allowed_types)) {
                    throw new Exception('Only JPG, JPEG, PNG, GIF, and PDF files are allowed for ID proof.');
                }
                
                // Validate file size (max 5MB)
                if ($_FILES['id_proof']['size'] > 5000000) {
                    throw new Exception('ID proof file size must be less than 5MB.');
                }
                
                // Move uploaded file
                if (!move_uploaded_file($_FILES['id_proof']['tmp_name'], $target_file)) {
                    throw new Exception('Failed to upload ID proof image.');
                }
                
                // Store relative path in database
                $id_proof_image_path = $upload_dir . $unique_filename;
            }
        }
        
        // Get payment method
        $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'full';
        
        // Calculate deposit amount for COD (20% of total)
        $deposit_amount = 0;
        if ($payment_method === 'cod') {
            $deposit_amount = $total_amount * 0.20;
        }
        
        $all_successful = true;
        $rental_ids = [];
        
        // Create rental records for each cart item
        foreach ($cart_items as $item) {
            // Double-check availability with improved logic
            $check_query = "SELECT quantity_available FROM tools WHERE tool_id = ? FOR UPDATE";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("i", $item['tool_id']);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $current_tool = $check_result->fetch_assoc();
            
            // Allow rental if there are enough tools available
            if ($current_tool['quantity_available'] < $item['quantity']) {
                throw new Exception("Tool '{$item['name']}' only has {$current_tool['quantity_available']} units available, but you requested {$item['quantity']}");
            }
            
            // Create rental record with ID proof and time information
            $rental_start_time = date('H:i:s'); // Set to current time
            $rental_end_time = '18:00:00'; // Default end time
            
            $rental_query = "INSERT INTO rentals (user_id, tool_id, rental_date, rental_time, return_date, return_time, quantity, total_amount, payment_method, deposit_amount, address_updated, id_proof_image) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $rental_stmt = $conn->prepare($rental_query);
            $address_updated = !empty($_POST['address']) ? 1 : 0;
            $rental_stmt->bind_param("iissssiissds", $user_id, $item['tool_id'], 
                                   $item['rental_start_date'], $rental_start_time, $item['rental_end_date'], $rental_end_time, 
                                   $item['quantity'], $item['subtotal'], $payment_method, $deposit_amount, $address_updated, $id_proof_image_path);
            
            if (!$rental_stmt->execute()) {
                throw new Exception("Failed to create rental for '{$item['name']}'");
            }
            
            $rental_ids[] = $conn->insert_id;
            
            // Update tool quantity - decrease by the rented quantity
            $update_query = "UPDATE tools SET quantity_available = quantity_available - ? WHERE tool_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("ii", $item['quantity'], $item['tool_id']);
            
            if (!$update_stmt->execute()) {
                throw new Exception("Failed to update availability for '{$item['name']}'");
            }
        }
        
        // Clear cart
        $clear_cart_query = "DELETE FROM cart WHERE user_id = ?";
        $clear_stmt = $conn->prepare($clear_cart_query);
        $clear_stmt->bind_param("i", $user_id);
        
        if (!$clear_stmt->execute()) {
            throw new Exception("Failed to clear cart");
        }
        
        $conn->commit();
        $success_message = "Checkout successful! Your rentals have been confirmed.";
        
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = $e->getMessage();
    }
}

include 'includes/header.php';
?>

<div class="container">
    <div class="main-content" <?php if (!empty($success_message)) echo 'style="min-height:0; padding-bottom:0;"'; ?> >
        <h1><i class="fas fa-credit-card"></i> Confirm Rental</h1>
        <p class="mb-3">Review your order details and confirm your rental</p>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
            <div class="card">
                <div class="card-body text-center">
                    <h3>Thank you for your rental!</h3>
                    <p>Your rental confirmation details have been recorded. You can view your rentals in your dashboard.</p>
                    <div class="d-flex gap-2 justify-center mt-3">
                        <a href="user_dashboard.php" class="btn btn-primary">
                            <i class="fas fa-list"></i> View My Rentals
                        </a>
                        <a href="browse_tools.php" class="btn btn-secondary">
                            <i class="fas fa-search"></i> Browse More Tools
                        </a>
                    </div>
                </div>
            </div>
            <!-- Small pickup note with location -->
            <div class="card" style="max-width:1000px; margin:20px auto 0 auto;">
                <div class="card-body text-center" style="font-weight:600;">
                    <div>So , You can now collect your Tools from our Shop</div>
                    <div style="margin-top:6px; font-weight:400; font-size:0.95rem; color:#666;">
                       <div class="card-body">
                                <div class="map-responsive">
                                    <iframe
                                        src="https://www.google.com/maps?q=9.9716198,77.1808363&z=17&output=embed"
                                        width="100%"
                                        height="300"
                                        style="border:0; border-radius:12px;"
                                        allowfullscreen=""
                                        loading="lazy"
                                        referrerpolicy="no-referrer-when-downgrade"
                                        title="Shop Location Map"
                                    ></iframe>
                                </div>
                            </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="checkout-landscape">
                    <!-- Left Column - Order Details with Scroll -->
                    <div class="checkout-column">
                        <!-- Order Summary with Scroll -->
                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-list"></i> Rental Details</h3>
                            </div>
                            <div class="card-body rental-details-scroll">
                                <?php foreach ($cart_items as $item): ?>
                                    <div class="rental-item">
                                        <div class="rental-header">
                                            <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                            <div class="rental-price">₹<?php echo number_format($item['daily_rate'], 2); ?><span>/day</span></div>
                                        </div>
                                        <div class="rental-details">
                                            <div class="detail-grid">
                                                <div class="detail-item">
                                                    <span class="label">Start Date</span>
                                                    <span class="value"><?php echo date('Y-m-d', strtotime($item['rental_start_date'])); ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="label">End Date</span>
                                                    <span class="value"><?php echo date('Y-m-d', strtotime($item['rental_end_date'])); ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="label">Days</span>
                                                    <span class="value"><?php echo $item['rental_days']; ?> days</span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="label">Rate</span>
                                                    <span class="value">₹<?php echo number_format($item['daily_rate'], 2); ?>/day</span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="label">Quantity</span>
                                                    <span class="value"><?php echo $item['quantity']; ?></span>
                                                </div>
                                                <div class="detail-item total">
                                                    <span class="label">Total Amount</span>
                                                    <span class="value">₹<?php echo number_format($item['subtotal'], 2); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <div class="order-total-section">
                                    <div class="total-row">
                                        <span>Total Amount</span>
                                        <span class="total-amount">₹<?php echo number_format($total_amount, 2); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Customer Information -->
                        <div class="card mt-2">
                            <div class="card-header">
                                <h3><i class="fas fa-user"></i> Delivery Information</h3>
                            </div>
                            <div class="card-body">
                                <div class="customer-info-grid">
                                    <div class="info-item">
                                        <label class="info-label">Username</label>
                                        <div class="info-value"><?php echo htmlspecialchars($user['username']); ?></div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <label class="info-label">Phone Number</label>
                                        <input type="tel" name="phone" class="form-control" placeholder="Enter your phone number" value="<?php echo htmlspecialchars($user['phone'] ?: ''); ?>">
                                        <small class="info-note"><i class="fas fa-info-circle"></i> You can update your phone number for this rental</small>
                                    </div>
                                    
                                    <div class="info-item full-width">
                                        <label class="info-label">Delivery Address</label>
                                        <textarea name="address" class="form-control" placeholder="Enter your delivery address"><?php echo htmlspecialchars($user['address'] ?: ''); ?></textarea>
                                        <small class="info-note"><i class="fas fa-info-circle"></i> You can update your address for this rental</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pickup message and Map: placed under Delivery Address in left column -->

                        <div class="card mt-2" style="max-width:700px;">
                            <div class="card-header">
                                <h4><i class="fas fa-map-marker-alt"></i> Shop Location</h4>
                            </div>
                            <div class="card-body">
                                <div class="map-responsive">
                                    <iframe
                                        src="https://www.google.com/maps?q=9.9716198,77.1808363&z=17&output=embed"
                                        width="100%"
                                        height="300"
                                        style="border:0; border-radius:12px;"
                                        allowfullscreen=""
                                        loading="lazy"
                                        referrerpolicy="no-referrer-when-downgrade"
                                        title="Shop Location Map"
                                    ></iframe>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Column - Payment and Confirmation -->
                    <div class="checkout-column">
                        <!-- Payment Method -->
                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-credit-card"></i> Payment Method</h3>
                            </div>
                            <div class="card-body">
                                <div class="payment-options">
                                    <label class="payment-option">
                                        <input type="radio" name="payment_method" value="full" checked>
                                        <div class="payment-option-content">
                                            <div class="payment-header">
                                                <div>
                                                    <h4>Full Payment</h4>
                                                    <p>Pay the full amount now</p>
                                                </div>
                                            </div>
                                            <div class="payment-amount">
                                                ₹<?php echo number_format($total_amount, 2); ?>
                                            </div>
                                            <div class="payment-benefit">Balance: ₹0.00</div>
                                        </div>
                                    </label>
                                    
                                    <label class="payment-option">
                                        <input type="radio" name="payment_method" value="cod">
                                        <div class="payment-option-content">
                                            <div class="payment-header">
                                                <div>
                                                    <h4>Cash on Delivery</h4>
                                                    <p>Pay 20% deposit now</p>
                                                </div>
                                            </div>
                                            <div class="payment-amount">
                                                ₹<?php echo number_format($total_amount * 0.20, 2); ?>
                                            </div>
                                            <div class="payment-benefit">Balance: ₹<?php echo number_format($total_amount * 0.80, 2); ?></div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- ID Proof Upload (only shown if user hasn't uploaded yet) -->
                        <?php if (!$has_id_proof): ?>
                            <div class="card mt-2">
                                <div class="card-header">
                                    <h4><i class="fas fa-id-card"></i> ID Proof Upload</h4>
                                </div>
                                <div class="card-body">
                                    <div class="id-proof-section">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i>
                                            <strong>Important:</strong> Your ID proof will be securely stored and automatically used for all future rentals.
                                        </div>
                                        
                                        <p>Upload Government-Issued ID Proof</p>
                                        <p>You can upload a photo or document as proof of identity (passport, driver's license, etc.)</p>
                                        
                                        <div class="form-group">
                                            <input type="file" id="idProof" name="id_proof" accept="image/*,.pdf" class="form-control" required>
                                            <small class="form-text">Supported formats: JPG, JPEG, PNG, GIF, PDF (Max 5MB)</small>
                                        </div>
                                        
                                        <div class="id-proof-preview" id="idProofPreview" style="display: none;">
                                            <h5><i class="fas fa-eye"></i> Preview</h5>
                                            <img id="idProofImage" src="" alt="ID Proof Preview">
                                            <p id="idProofFileName"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="card mt-2">
                                <div class="card-header">
                                    <h4><i class="fas fa-id-card"></i> ID Proof Status</h4>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle"></i>
                                        <strong>ID Proof Already Uploaded</strong>
                                        <p>Your ID proof is already on file and will be used for this rental.</p>
                                        <a href="manage_id_proof.php" class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye"></i> View/Update ID Proof
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Rental Agreement - Rearranged -->
                        <div class="card mt-2">
                            <div class="card-header">
                                <h4><i class="fas fa-file-contract"></i> Rental Agreement</h4>
                            </div>
                            <div class="card-body">
                                <div class="agreement-content">
                                    <p>Please review and agree to the following rental terms:</p>
                                    <ul>
                                        <li>Late Fee: ₹<?php echo number_format(get_late_fee_per_day(), 2); ?> per day for overdue returns</li>
                                        <li>Damage Fee: <?php echo number_format(get_damage_fee_percentage(), 2); ?>% of the tool's value for damages</li>
                                        <li>All tools must be returned in the same condition as received</li>
                                        <li>Loss of tool will result in full replacement cost</li>
                                    </ul>
                                </div>
                                
                                <div class="agreement-checkbox">
                                    <label>
                                        <input type="checkbox" required>
                                        <span>I agree to the rental terms and conditions (₹<?php echo number_format(get_late_fee_per_day(), 2); ?>/day late fee, <?php echo number_format(get_damage_fee_percentage(), 2); ?>% damage fee)</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="action-buttons mt-2">
                            <button type="submit" class="btn btn-primary btn-large">
                                <i class="fas fa-check-circle"></i> Confirm Rental - ₹<?php echo number_format($total_amount, 2); ?>
                            </button>
                            
                            <a href="view_cart.php" class="btn btn-secondary btn-large">
                                <i class="fas fa-arrow-left"></i> Back to Cart
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<style>
/* Enable smooth scrolling */
html {
    scroll-behavior: smooth;
}

/* New Landscape Layout */
.checkout-landscape {
    display: flex;
    gap: 30px;
    flex-wrap: wrap;
}

.checkout-column {
    flex: 1;
    min-width: 300px;
}

/* Scrollable container for rental details */
.rental-details-scroll {
    max-height: 400px;
    overflow-y: auto;
    padding-right: 10px;
}

/* Custom scrollbar for rental details */
.rental-details-scroll::-webkit-scrollbar {
    width: 8px;
}

.rental-details-scroll::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.rental-details-scroll::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.rental-details-scroll::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Rental Item */
.rental-item {
    border: 1px solid var(--border-gray);
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    background-color: white;
}

.rental-item:last-child {
    margin-bottom: 0;
}

.rental-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--border-gray);
}

.rental-header h4 {
    margin: 0;
    font-size: 1.2rem;
    color: #212529;
}

.rental-price {
    font-weight: bold;
    color: var(--accent-yellow);
    font-size: 1.2rem;
}

.rental-price span {
    font-size: 0.9rem;
    color: #666;
    font-weight: normal;
}

.rental-details {
    margin-top: 15px;
}

.detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #e9ecef;
}

.detail-item:last-child {
    border-bottom: none;
}

.detail-item.total {
    font-weight: bold;
    font-size: 1.1rem;
    border-top: 1px solid var(--border-gray);
    margin-top: 5px;
    padding-top: 10px;
}

.detail-item.total .value {
    color: var(--accent-yellow);
}

.label {
    color: #666;
    font-weight: 500;
}

.value {
    font-weight: 500;
    text-align: right;
}

.order-total-section {
    border-top: 2px solid var(--border-gray);
    padding-top: 20px;
    margin-top: 10px;
}

.total-row {
    display: flex;
    justify-content: space-between;
    font-size: 1.4rem;
    font-weight: bold;
}

.total-amount {
    color: var(--accent-yellow);
}

/* Customer Info */
.customer-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.info-item {
    margin-bottom: 15px;
}

.info-item.full-width {
    grid-column: 1 / -1;
}

.info-label {
    display: block;
    font-weight: 600;
    color: #495057;
    margin-bottom: 5px;
    font-size: 0.9rem;
}

.info-value {
    padding: 12px 15px;
    background: #f1f3f5;
    border-radius: 6px;
    border: 1px solid #e9ecef;
    font-size: 1rem;
    min-height: 45px;
    display: flex;
    align-items: center;
}

.form-control {
    display: block;
    width: 100%;
    padding: 12px 15px;
    font-size: 1rem;
    line-height: 1.5;
    color: #495057;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;
    margin-bottom: 5px;
}

.form-control:focus {
    color: #495057;
    background-color: #fff;
    border-color: #1976d2;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(25, 118, 210, 0.25);
}

.info-note {
    color: #6c757d;
    font-size: 0.85rem;
    margin-top: 5px;
    display: block;
}

/* Payment Options */
.payment-options {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-bottom: 25px;
}

.payment-option {
    display: block;
    border: 2px solid var(--border-gray);
    border-radius: 8px;
    padding: 15px;
    cursor: pointer;
    transition: all 0.2s;
}

.payment-option:hover {
    border-color: #1976d2;
    background-color: #f8f9fa;
}

.payment-option input {
    display: none;
}

.payment-option input:checked + .payment-option-content {
    border-color: #1976d2;
    background-color: #e3f2fd;
}

.payment-option-content {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.payment-header {
    display: flex;
    align-items: center;
    gap: 15px;
}

.payment-header i {
    font-size: 1.5rem;
    color: #1976d2;
    width: 40px;
    text-align: center;
}

.payment-header h4 {
    margin: 0;
    font-size: 1.1rem;
    color: #212529;
}

.payment-header p {
    margin: 0;
    font-size: 0.9rem;
    color: #666;
}

.payment-amount {
    font-size: 1.4rem;
    font-weight: bold;
    color: var(--accent-yellow);
    text-align: center;
    margin: 5px 0;
}

.payment-benefit {
    font-size: 0.9rem;
    color: #28a745;
    font-weight: 500;
    text-align: center;
}

/* Payment Summary */
.payment-summary {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    border: 1px solid #e9ecef;
}

.payment-summary h4 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #212529;
    display: flex;
    align-items: center;
    gap: 10px;
}

.summary-details {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #e9ecef;
}

.summary-row:last-child {
    border-bottom: none;
}

.summary-row.highlight {
    font-weight: bold;
    font-size: 1.1rem;
}

.summary-row.highlight span:last-child {
    color: var(--accent-yellow);
}

/* Agreement */
.agreement-content {
    margin-bottom: 15px;
}

.agreement-content ul {
    padding-left: 20px;
    margin: 10px 0;
}

.agreement-content li {
    margin-bottom: 8px;
    font-size: 0.95rem;
}

.agreement-checkbox {
    padding-top: 15px;
    border-top: 1px solid var(--border-gray);
}

.agreement-checkbox label {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    cursor: pointer;
    font-size: 0.95rem;
}

.agreement-checkbox input {
    margin-top: 3px;
}

/* ID Proof */
.id-proof-section {
    text-align: center;
}

.instruction {
    margin-bottom: 20px;
    padding: 15px;
    background: #fff3cd;
    border-radius: 6px;
    border: 1px solid #ffeaa7;
    color: #856404;
    font-size: 0.95rem;
}

.id-proof-preview {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid var(--border-gray);
}

.id-proof-preview h5 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #212529;
}

.id-proof-preview img {
    max-width: 100%;
    max-height: 200px;
    border-radius: 6px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 10px;
}

.id-proof-preview p {
    margin: 0;
    font-size: 0.9rem;
    color: #666;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin: 20px 0 0 0;
}

.btn-large {
    padding: 15px 20px;
    font-size: 1.1rem;
    justify-content: center;
}

/* Responsive adjustments */
@media (max-width: 992px) {
    .checkout-landscape {
        flex-direction: column;
    }
    
    .customer-info-grid {
        grid-template-columns: 1fr;
    }
    
    .detail-grid {
        grid-template-columns: 1fr;
    }
    
    .payment-options {
        flex-direction: column;
    }
    
    /* Remove scroll on mobile */
    .rental-details-scroll {
        max-height: none;
        overflow-y: visible;
    }
}

@media (max-width: 768px) {
    .checkout-column {
        min-width: 100%;
    }
    
    .rental-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .action-buttons {
        gap: 10px;
    }
    
    .btn-large {
        padding: 12px 15px;
        font-size: 1rem;
    }
    
    .customer-info-grid {
        grid-template-columns: 1fr;
    }
    
    .payment-header {
        flex-direction: column;
        text-align: center;
        gap: 10px;
    }
}
</style>

<script>
// Update the displayed deposit amount when payment method changes
document.addEventListener('DOMContentLoaded', function() {
    const paymentMethodRadios = document.querySelectorAll('input[name="payment_method"]');
    const totalAmount = <?php echo $total_amount; ?>;
    const paymentMethodText = document.getElementById('payment-method-text');
    const amountPaidNow = document.getElementById('amount-paid-now');
    const remainingAmount = document.getElementById('remaining-amount');
    
    // Initialize with default values (Full Payment selected)
    updatePaymentInfo('full', totalAmount);
    
    paymentMethodRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const depositAmount = this.value === 'cod' ? (totalAmount * 0.20).toFixed(2) : '0.00';
            const depositText = this.value === 'cod' ? 
                `Pay 20% deposit now (₹${depositAmount})` : 
                'Pay the full amount now';
            
            // Update the description text
            const description = this.closest('label').querySelector('div div:last-child');
            if (description) {
                description.textContent = depositText;
            }
            
            // Update payment summary
            updatePaymentInfo(this.value, totalAmount);
        });
    });
    
    function updatePaymentInfo(method, total) {
        if (method === 'cod') {
            const deposit = (total * 0.20).toFixed(2);
            const remaining = (total * 0.80).toFixed(2);
            
            paymentMethodText.textContent = 'Cash on Delivery';
            amountPaidNow.textContent = `₹${deposit}`;
            remainingAmount.textContent = `₹${remaining}`;
        } else {
            paymentMethodText.textContent = 'Full Payment';
            amountPaidNow.textContent = `₹${total.toFixed(2)}`;
            remainingAmount.textContent = '₹0.00';
        }
    }
    
    // Handle ID proof preview
    const idProofInput = document.getElementById('idProof');
    const idProofPreview = document.getElementById('idProofPreview');
    const idProofImage = document.getElementById('idProofImage');
    const idProofFileName = document.getElementById('idProofFileName');
    
    if (idProofInput) {
        idProofInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Check if file is an image
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        idProofImage.src = e.target.result;
                        idProofImage.style.display = 'block';
                        idProofFileName.textContent = file.name;
                        idProofPreview.style.display = 'block';
                    }
                    reader.readAsDataURL(file);
                } else {
                    // For PDF or other file types, show file name only
                    idProofImage.style.display = 'none';
                    idProofFileName.textContent = file.name;
                    idProofPreview.style.display = 'block';
                }
            } else {
                idProofPreview.style.display = 'none';
            }
        });
    }
});
</script>

<!-- map moved into left column under Delivery Address -->

<?php include 'includes/footer.php'; ?>