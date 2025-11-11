<?php
require_once 'includes/db_connect.php';
require_once 'includes/settings_helper.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

error_log("View Cart - User ID: " . ($_SESSION['user_id'] ?? 'not set'));


$user_id = $_SESSION['user_id'];

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

// Calculate total
$total_amount = 0;
$cart_items = [];
while ($item = $cart_result->fetch_assoc()) {
    $item['subtotal'] = $item['daily_rate'] * $item['rental_days'] * $item['quantity'];
    $total_amount += $item['subtotal'];
    $cart_items[] = $item;
}

include 'includes/header.php';
?>

<div class="container">
    <div class="main-content">
        <h1><i class="fas fa-shopping-cart"></i> Shopping Cart</h1>
        <p class="mb-3">Review your selected tools and proceed to checkout</p>
        
        <?php if (!empty($cart_items)): ?>
            <div class="grid grid-2" style="gap: 30px;">
                <!-- Cart Items -->
                <div>
                    <?php foreach ($cart_items as $item): ?>
                        <div class="card mb-2">
                            <div class="card-body">
                                <div style="display: flex; gap: 20px;">
                                    <img src="<?php echo $item['image_url'] ?: 'https://images.unsplash.com/photo-1572981779307-38b8cabb2407?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=200&q=80'; ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                         style="width: 120px; height: 120px; object-fit: cover; border-radius: var(--border-radius);"
                                         onerror="this.src='https://images.unsplash.com/photo-1572981779307-38b8cabb2407?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=200&q=80'">
                                    
                                    <div style="flex: 1;">
                                        <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                        
                                        <div style="margin: 10px 0;">
                                            <strong>Rental Period:</strong><br>
                                            <?php echo date('M j, Y', strtotime($item['rental_start_date'])); ?> - 
                                            <?php echo date('M j, Y', strtotime($item['rental_end_date'])); ?>
                                            <br>
                                            <small style="color: #666;">(<?php echo $item['rental_days']; ?> days)</small>
                                        </div>
                                        
                                        <div style="margin: 10px 0;">
                                            <strong>Quantity:</strong> <?php echo $item['quantity']; ?>
                                        </div>
                                        
                                        <div style="margin: 10px 0;">
                                            <strong>Daily Rate:</strong> ₹<?php echo number_format($item['daily_rate'], 2); ?>
                                            <br>
                                            <strong>Subtotal:</strong> 
                                            <span style="color: var(--accent-yellow); font-size: 1.1rem; font-weight: bold;">
                                                ₹<?php echo number_format($item['subtotal'], 2); ?>
                                            </span>
                                        </div>
                                        
                                        <!-- Check availability -->
                                        <?php if ($item['quantity_available'] < $item['quantity']): ?>
                                            <div class="alert alert-error" style="margin: 10px 0; padding: 10px;">
                                                <i class="fas fa-exclamation-triangle"></i> 
                                                Only <?php echo $item['quantity_available']; ?> unit<?php echo $item['quantity_available'] > 1 ? 's' : ''; ?> available. Please reduce quantity.
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="d-flex gap-2" style="margin-top: 15px;">
                                            <a href="tool_details.php?id=<?php echo $item['tool_id']; ?>" class="btn btn-secondary">
                                                <i class="fas fa-info-circle"></i> View Details
                                            </a>
                                            <button onclick="updateCartQuantity(<?php echo $item['cart_id']; ?>, <?php echo $item['tool_id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>')" class="btn btn-primary">
                                                <i class="fas fa-edit"></i> Edit Quantity
                                            </button>
                                            <button onclick="removeFromCart(<?php echo $item['cart_id']; ?>)" class="btn btn-danger">
                                                <i class="fas fa-trash"></i> Remove
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Order Summary -->
                <div>
                    <div class="card" style="position: sticky; top: 90px;">
                        <div class="card-header">
                            <h3>Order Summary</h3>
                        </div>
                        <div class="card-body">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <span>Items (<?php echo count($cart_items); ?>):</span>
                                <span>₹<?php echo number_format($total_amount, 2); ?></span>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; margin-bottom: 15px; padding-top: 15px; border-top: 1px solid var(--border-gray); font-weight: bold; font-size: 1.1rem;">
                                <span>Total:</span>
                                <span style="color: var(--accent-yellow);">₹<?php echo number_format($total_amount, 2); ?></span>
                            </div>
                            
                            <!-- Check if all items are available -->
                            <?php 
                            $has_unavailable = false;
                            foreach ($cart_items as $item) {
                                if ($item['quantity_available'] < $item['quantity']) {
                                    $has_unavailable = true;
                                    break;
                                }
                            }
                            ?>
                            
                            <?php if (!$has_unavailable): ?>
                                <a href="checkout.php" class="btn btn-primary" style="width: 100%;">
                                    <i class="fas fa-credit-card"></i> Proceed to Checkout
                                </a>
                            <?php else: ?>
                                <button class="btn btn-primary" style="width: 100%; opacity: 0.5;" disabled>
                                    <i class="fas fa-exclamation-triangle"></i> Some items unavailable
                                </button>
                                <small style="color: #DC3545; display: block; margin-top: 10px; text-align: center;">
                                    Please remove unavailable items to continue
                                </small>
                            <?php endif; ?>
                            
                            <a href="browse_tools.php" class="btn btn-secondary" style="width: 100%; margin-top: 10px;">
                                <i class="fas fa-arrow-left"></i> Continue Shopping
                            </a>
                        </div>
                    </div>
                    
                    <!-- Rental Terms -->
                    <div class="card mt-2">
                        <div class="card-header">
                            <h4>Rental Terms</h4>
                        </div>
                        <div class="card-body">
                            <ul style="padding-left: 20px; margin: 0;">
                                <li>Tools must be returned in the same condition</li>
                                <li>Late return fees apply at ₹<?php echo number_format(get_late_fee_per_day(), 2); ?>/day per tool</li>
                                <li>Damage fees are <?php echo number_format(get_damage_fee_percentage(), 2); ?>% of tool's actual price</li>
                                <li>All rentals are subject to availability</li>
                                <li>Valid ID required for all rentals</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Empty Cart -->
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-shopping-cart" style="font-size: 4rem; color: var(--border-gray); margin-bottom: 20px;"></i>
                    <h3>Your cart is empty</h3>
                    <p>Add some tools to get started with your project!</p>
                    <a href="browse_tools.php" class="btn btn-primary">
                        <i class="fas fa-search"></i> Browse Tools
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
@media (max-width: 768px) {
    .grid.grid-2 {
        grid-template-columns: 1fr;
    }
    
    .card .card-body > div[style*="display: flex"] {
        flex-direction: column;
    }
    
    .card .card-body img {
        width: 100%;
        height: 200px;
        margin-bottom: 15px;
    }
}
</style>

<script>
// Set minimum date for rental start date to today
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('rental_start_date').setAttribute('min', today);
    document.getElementById('rental_end_date').setAttribute('min', today);
    
    // Add event listeners for date changes
    document.getElementById('rental_start_date').addEventListener('change', calculateTotal);
    document.getElementById('rental_end_date').addEventListener('change', calculateTotal);
});

function calculateTotal() {
    const startDate = document.getElementById('rental_start_date').value;
    const endDate = document.getElementById('rental_end_date').value;
    const dailyRate = parseFloat(document.getElementById('daily_rate').value);
    const totalElement = document.getElementById('rental_total');
    
    if (startDate && endDate) {
        const start = new Date(startDate);
        const end = new Date(endDate);
        
        if (end >= start) {
            const timeDiff = end.getTime() - start.getTime();
            const days = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1; // Include both start and end dates
            const total = days * dailyRate;
            
            totalElement.innerHTML = `Total for ${days} day(s): <span style="color: var(--accent-yellow);">₹${total.toFixed(2)}</span>`;
        } else {
            totalElement.innerHTML = '<span style="color: #DC3545;">End date must be after start date</span>';
        }
    }
}

function addToCart(toolId, toolName) {
    const startDate = document.getElementById('rental_start_date').value;
    const endDate = document.getElementById('rental_end_date').value;
    
    if (!startDate || !endDate) {
        alert('Please select both start and end dates.');
        return false;
    }
    
    const start = new Date(startDate);
    const end = new Date(endDate);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    if (start < today) {
        alert('Start date cannot be in the past.');
        return false;
    }
    
    if (end < start) {
        alert('End date must be after start date.');
        return false;
    }
    
    // Add to cart via AJAX
    const formData = new FormData();
    formData.append('tool_id', toolId);
    formData.append('rental_start_date', startDate);
    formData.append('rental_end_date', endDate);
    
    fetch('add_to_cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`${toolName} added to cart successfully!`);
            window.location.href = 'view_cart.php';
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while adding to cart.');
    });
    
    return false;
}

function notifyWhenAvailable(toolId) {
    // Make an AJAX call to save the notification
    fetch('notify_when_available.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'tool_id=' + toolId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
        } else {
            if (data.message.includes('logged in')) {
                if (confirm('You need to be logged in to subscribe to notifications. Would you like to log in now?')) {
                    window.location.href = 'login.php';
                }
            } else {
                alert(data.message);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while subscribing to notifications.');
    });
}

function removeFromCart(cartId) {
    if (!confirm('Are you sure you want to remove this item from your cart?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('cart_id', cartId);
    
    fetch('modules/remove_from_cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while removing the item.');
    });
}

function updateCartQuantity(cartId, toolId, toolName) {
    const newQuantity = prompt('Enter the new quantity (1-5):', '1');
    
    if (newQuantity === null) {
        return; // User cancelled
    }
    
    const quantity = parseInt(newQuantity);
    
    if (isNaN(quantity) || quantity < 1 || quantity > 5) {
        alert('Please enter a valid quantity between 1 and 5.');
        return;
    }
    
    const formData = new FormData();
    formData.append('cart_id', cartId);
    formData.append('tool_id', toolId);
    formData.append('quantity', quantity);
    
    fetch('modules/update_cart_quantity.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the quantity.');
    });
}
</script>

<?php include 'includes/footer.php'; ?>