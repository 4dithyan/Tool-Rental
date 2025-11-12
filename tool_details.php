<?php
require_once 'includes/db_connect.php';

// Get tool ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: browse_tools.php');
    exit();
}

$tool_id = intval($_GET['id']);

// Get tool details with category and reviews
$tool_query = "SELECT t.*, c.category_name,
               AVG(r.rating) as avg_rating,
               COUNT(r.review_id) as review_count
               FROM tools t 
               LEFT JOIN categories c ON t.category_id = c.category_id 
               LEFT JOIN reviews r ON t.tool_id = r.tool_id
               WHERE t.tool_id = ? AND t.status = 'active'
               GROUP BY t.tool_id";

$stmt = $conn->prepare($tool_query);
$stmt->bind_param("i", $tool_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header('Location: browse_tools.php');
    exit();
}

$tool = $result->fetch_assoc();

// Get tool reviews
$reviews_query = "SELECT r.*, u.first_name, u.last_name 
                  FROM reviews r 
                  JOIN users u ON r.user_id = u.user_id 
                  WHERE r.tool_id = ? 
                  ORDER BY r.created_at DESC 
                  LIMIT 10";
$reviews_stmt = $conn->prepare($reviews_query);
$reviews_stmt->bind_param("i", $tool_id);
$reviews_stmt->execute();
$reviews_result = $reviews_stmt->get_result();

// Get similar tools from same category
$similar_query = "SELECT t.*, AVG(r.rating) as avg_rating 
                  FROM tools t 
                  LEFT JOIN reviews r ON t.tool_id = r.tool_id
                  WHERE t.category_id = ? AND t.tool_id != ? AND t.status = 'active' 
                  GROUP BY t.tool_id
                  ORDER BY t.created_at DESC 
                  LIMIT 4";
$similar_stmt = $conn->prepare($similar_query);
$similar_stmt->bind_param("ii", $tool['category_id'], $tool_id);
$similar_stmt->execute();
$similar_result = $similar_stmt->get_result();

include 'includes/header.php';
?>

<div class="container">
    <div class="main-content">
        <!-- Breadcrumb -->
        <nav style="margin-bottom: 20px;">
            <a href="index.php" style="color: var(--primary-gray); text-decoration: none;">Home</a>
            <span style="margin: 0 10px; color: #666;"> > </span>
            <a href="browse_tools.php" style="color: var(--primary-gray); text-decoration: none;">Browse Tools</a>
            <span style="margin: 0 10px; color: #666;"> > </span>
            <span style="color: #666;"><?php echo htmlspecialchars($tool['name']); ?></span>
        </nav>
        
        <div class="grid grid-2" style="gap: 40px;">
            <!-- Tool Image and Gallery -->
            <div>
                <div class="card">
                    <img src="<?php echo $tool['image_url'] ?: 'https://images.unsplash.com/photo-1572981779307-38b8cabb2407?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=600&q=80'; ?>" 
                         alt="<?php echo htmlspecialchars($tool['name']); ?>" 
                         style="width: 100%; height: 400px; object-fit: cover;"
                         onerror="this.src='https://images.unsplash.com/photo-1572981779307-38b8cabb2407?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=600&q=80'">
                </div>
                
                <!-- Tool Features -->
                <div class="card mt-2">
                    <div class="card-header">
                        <h3>Tool Features</h3>
                    </div>
                    <div class="card-body">
                        <div class="grid grid-2">
                            <div>
                                <strong>Category:</strong><br>
                                <span class="badge badge-active"><?php echo htmlspecialchars($tool['category_name'] ?: 'Uncategorized'); ?></span>
                            </div>
                            <?php if ($tool['brand']): ?>
                            <div>
                                <strong>Brand:</strong><br>
                                <?php echo htmlspecialchars($tool['brand']); ?>
                            </div>
                            <?php endif; ?>
                            <?php if ($tool['model']): ?>
                            <div>
                                <strong>Model:</strong><br>
                                <?php echo htmlspecialchars($tool['model']); ?>
                            </div>
                            <?php endif; ?>
                            <div>
                                <strong>Availability:</strong><br>
                                <?php if ($tool['quantity_available'] > 0): ?>
                                    <span style="color: #28A745;">
                                        <i class="fas fa-check-circle"></i> 
                                        <?php echo $tool['quantity_available']; ?> available
                                    </span>
                                <?php else: ?>
                                    <span style="color: #DC3545;">
                                        <i class="fas fa-times-circle"></i> 
                                        Out of stock
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tool Details and Rental Form -->
            <div>
                <h1><?php echo htmlspecialchars($tool['name']); ?></h1>
                
                <!-- Rating Display -->
                <?php if ($tool['avg_rating']): ?>
                    <div style="margin-bottom: 20px;">
                        <div class="star-rating" data-rating="<?php echo round($tool['avg_rating']); ?>">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star"><?php echo ($i <= round($tool['avg_rating'])) ? '★' : '☆'; ?></span>
                            <?php endfor; ?>
                        </div>
                        <span style="margin-left: 10px; color: #666;">
                            <?php echo number_format($tool['avg_rating'], 1); ?> out of 5 
                            (<?php echo $tool['review_count']; ?> reviews)
                        </span>
                    </div>
                <?php endif; ?>
                
                <!-- Pricing -->
                <div class="card mb-2">
                    <div class="card-body">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h2 style="color: var(--accent-yellow); margin-bottom: 5px;">
                                    ₹<?php echo number_format($tool['daily_rate'], 2); ?>
                                    <span style="font-size: 1rem; color: #666;">/day</span>
                                </h2>
                                <small style="color: #666;">
                                    Actual price: ₹<?php echo number_format($tool['actual_price'], 2); ?>
                                </small>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 0.9rem; color: #666;">Save up to</div>
                                <div style="font-size: 1.2rem; font-weight: bold; color: #28A745;">
                                    <?php echo round((1 - ($tool['daily_rate'] / $tool['actual_price'])) * 100); ?>%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Rental Form -->
                <?php if ($tool['quantity_available'] > 0): ?>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="card">
                            <div class="card-header">
                                <h3>Rent This Tool</h3>
                            </div>
                            <div class="card-body">
                                <form id="rentalForm" onsubmit="return addToCart(<?php echo $tool_id; ?>, '<?php echo htmlspecialchars($tool['name']); ?>');">
                                    <div class="grid grid-2">
                                        <div class="form-group">
                                            <label class="form-label" for="rental_start_date">Start Date</label>
                                            <input type="date" id="rental_start_date" name="rental_start_date" class="form-control" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-label" for="rental_end_date">End Date</label>
                                            <input type="date" id="rental_end_date" name="rental_end_date" class="form-control" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label" for="quantity">Quantity</label>
                                        <select id="quantity" name="quantity" class="form-control" required>
                                            <?php for ($i = 1; $i <= min(5, $tool['quantity_available']); $i++): ?>
                                                <option value="<?php echo $i; ?>"><?php echo $i; ?> unit<?php echo $i > 1 ? 's' : ''; ?></option>
                                            <?php endfor; ?>
                                        </select>
                                        <small style="color: #666;">Available: <?php echo $tool['quantity_available']; ?> unit<?php echo $tool['quantity_available'] > 1 ? 's' : ''; ?></small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <div id="rental_total" style="font-size: 1.1rem; font-weight: bold; color: var(--primary-gray);">
                                            Select dates to see total price
                                        </div>
                                    </div>
                                    
                                    <input type="hidden" id="daily_rate" value="<?php echo $tool['daily_rate']; ?>">
                                    
                                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card">
                            <div class="card-body text-center">
                                <p>Please <a href="login.php">login</a> to rent this tool.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Currently Unavailable</strong><br>
                                This tool is currently out of stock. Please check back later or contact us for availability updates.
                            </div>
                            
                            <!-- Check for future availability -->
                            <?php
                            // Check if there are any upcoming returns that might make this tool available
                            $future_returns_query = "SELECT r.rental_id, r.return_date 
                                                    FROM rentals r 
                                                    WHERE r.tool_id = ? 
                                                    AND r.status = 'active' 
                                                    AND r.return_date >= CURDATE() 
                                                    ORDER BY r.return_date ASC 
                                                    LIMIT 3";
                            $future_returns_stmt = $conn->prepare($future_returns_query);
                            $future_returns_stmt->bind_param("i", $tool_id);
                            $future_returns_stmt->execute();
                            $future_returns_result = $future_returns_stmt->get_result();
                            
                            if ($future_returns_result->num_rows > 0) {
                                echo "<p><strong>Potentially Available Dates:</strong></p>";
                                echo "<ul>";
                                while ($return = $future_returns_result->fetch_assoc()) {
                                    echo "<li>Available after " . date('M j, Y', strtotime($return['return_date'])) . "</li>";
                                }
                                echo "</ul>";
                            }
                            ?>
                            
                            <button onclick="notifyWhenAvailable(<?php echo $tool_id; ?>)" class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-bell"></i> Notify Me When Available
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Tool Description -->
                <div class="card mt-2">
                    <div class="card-header">
                        <h3>Description</h3>
                    </div>
                    <div class="card-body">
                        <p><?php echo nl2br(htmlspecialchars($tool['description'])); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Reviews Section -->
        <div class="card mt-3">
            <div class="card-header">
                <h3>Customer Reviews (<?php echo $tool['review_count']; ?>)</h3>
            </div>
            <div class="card-body">
                <?php if ($reviews_result->num_rows > 0): ?>
                    <div class="grid" style="gap: 20px;">
                        <?php while ($review = $reviews_result->fetch_assoc()): ?>
                            <div class="card" style="box-shadow: none; border: 1px solid var(--border-gray);">
                                <div class="card-body">
                                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                                        <div>
                                            <strong><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name'][0] . '.'); ?></strong>
                                            <div class="star-rating" data-rating="<?php echo $review['rating']; ?>" style="margin-top: 5px;">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <span class="star"><?php echo ($i <= $review['rating']) ? '★' : '☆'; ?></span>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <small style="color: #666;">
                                            <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
                                        </small>
                                    </div>
                                    <?php if ($review['review_text']): ?>
                                        <p style="margin-bottom: 0;"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center" style="color: #666;">No reviews yet. Be the first to review this tool!</p>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php
                    // Check if current user has rented and reviewed this tool
                    $user_review_query = "SELECT rv.review_id FROM reviews rv 
                                         JOIN rentals r ON rv.rental_id = r.rental_id 
                                         WHERE rv.user_id = ? AND r.tool_id = ? AND r.status = 'returned'";
                    $user_review_stmt = $conn->prepare($user_review_query);
                    $user_review_stmt->bind_param("ii", $_SESSION['user_id'], $tool_id);
                    $user_review_stmt->execute();
                    $user_review_result = $user_review_stmt->get_result();
                    
                    if ($user_review_result->num_rows > 0) {
                        // User has reviewed this tool, show edit button
                        $user_review = $user_review_result->fetch_assoc();
                        echo '<div class="mt-3 text-center">';
                        echo '<a href="edit_review.php?review_id=' . $user_review['review_id'] . '" class="btn btn-primary">';
                        echo '<i class="fas fa-edit"></i> Edit Your Review';
                        echo '</a>';
                        echo '</div>';
                    } else {
                        // Check if user has rented this tool and it's returned
                        $user_rental_query = "SELECT rental_id FROM rentals 
                                             WHERE user_id = ? AND tool_id = ? AND status = 'returned'";
                        $user_rental_stmt = $conn->prepare($user_rental_query);
                        $user_rental_stmt->bind_param("ii", $_SESSION['user_id'], $tool_id);
                        $user_rental_stmt->execute();
                        $user_rental_result = $user_rental_stmt->get_result();
                        
                        if ($user_rental_result->num_rows > 0) {
                            // User has returned this tool but hasn't reviewed it yet
                            $user_rental = $user_rental_result->fetch_assoc();
                            echo '<div class="mt-3 text-center">';
                            echo '<a href="add_review.php?rental_id=' . $user_rental['rental_id'] . '" class="btn btn-primary">';
                            echo '<i class="fas fa-star"></i> Add Your Review';
                            echo '</a>';
                            echo '</div>';
                        }
                    }
                    ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Similar Tools -->
        <?php if ($similar_result->num_rows > 0): ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h3>Similar Tools</h3>
                </div>
                <div class="card-body">
                    <div class="grid grid-4">
                        <?php while ($similar = $similar_result->fetch_assoc()): ?>
                            <div class="tool-card">
                                <img src="<?php echo $similar['image_url'] ?: 'https://images.unsplash.com/photo-1572981779307-38b8cabb2407?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=400&q=80'; ?>" 
                                     alt="<?php echo htmlspecialchars($similar['name']); ?>" class="tool-image"
                                     onerror="this.src='https://images.unsplash.com/photo-1572981779307-38b8cabb2407?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=400&q=80'">
                                <div class="tool-info">
                                    <div class="tool-name"><?php echo htmlspecialchars($similar['name']); ?></div>
                                    <div class="tool-price">₹<?php echo number_format($similar['daily_rate'], 2); ?>/day</div>
                                    <a href="tool_details.php?id=<?php echo $similar['tool_id']; ?>" class="btn btn-secondary" style="width: 100%;">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Star rating styles */
.star-rating {
    display: inline-block;
}

.star-rating .star {
    color: #FFC107;
    font-size: 1rem;
    margin-right: 2px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .grid.grid-2 {
        grid-template-columns: 1fr;
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
    const quantity = document.getElementById('quantity').value;
    
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
    formData.append('quantity', quantity);
    
    fetch('add_to_cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`${toolName} (${quantity} unit${quantity > 1 ? 's' : ''}) added to cart successfully!`);
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
</script>

<?php include 'includes/footer.php'; ?>