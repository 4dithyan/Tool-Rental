<?php
require_once 'includes/db_connect.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

// Check if rental_id is provided
if (!isset($_GET['rental_id']) || !is_numeric($_GET['rental_id'])) {
    header('Location: user_dashboard.php');
    exit();
}

$rental_id = intval($_GET['rental_id']);

// Verify rental belongs to user and is eligible for review
$rental_query = "SELECT r.*, t.name as tool_name, t.image_url 
                FROM rentals r 
                JOIN tools t ON r.tool_id = t.tool_id 
                WHERE r.rental_id = ? AND r.user_id = ? AND r.status = 'returned'";
$rental_stmt = $conn->prepare($rental_query);
$rental_stmt->bind_param("ii", $rental_id, $user_id);
$rental_stmt->execute();
$rental_result = $rental_stmt->get_result();

if ($rental_result->num_rows == 0) {
    header('Location: user_dashboard.php');
    exit();
}

$rental = $rental_result->fetch_assoc();

// Check if review already exists
$existing_review_query = "SELECT review_id FROM reviews WHERE rental_id = ? AND user_id = ?";
$existing_stmt = $conn->prepare($existing_review_query);
$existing_stmt->bind_param("ii", $rental_id, $user_id);
$existing_stmt->execute();
$existing_result = $existing_stmt->get_result();

if ($existing_result->num_rows > 0) {
    // Get the existing review ID and redirect to edit page
    $existing_review = $existing_result->fetch_assoc();
    header('Location: edit_review.php?review_id=' . $existing_review['review_id']);
    exit();
}

// Process review submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rating = intval($_POST['rating']);
    $review_text = trim($_POST['review_text']);
    
    if ($rating < 1 || $rating > 5) {
        $error_message = "Please select a valid rating (1-5 stars).";
    } else {
        $insert_query = "INSERT INTO reviews (user_id, tool_id, rental_id, rating, review_text) VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("iiiis", $user_id, $rental['tool_id'], $rental_id, $rating, $review_text);
        
        if ($insert_stmt->execute()) {
            $success_message = "Thank you for your review! It has been submitted successfully.";
        } else {
            $error_message = "Failed to submit review. Please try again.";
        }
    }
}

include 'includes/header.php';
?>

<div class="container">
    <div class="main-content">
        <h1><i class="fas fa-star"></i> Add Review</h1>
        <p class="mb-3">Share your experience with this tool to help other customers</p>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
            <div class="card">
                <div class="card-body text-center">
                    <h3>Review Submitted!</h3>
                    <p>Thank you for taking the time to review this tool. Your feedback helps our community make better choices.</p>
                    <div class="d-flex gap-2 justify-center mt-3">
                        <a href="user_dashboard.php" class="btn btn-primary">
                            <i class="fas fa-tachometer-alt"></i> Back to Dashboard
                        </a>
                        <a href="tool_details.php?id=<?php echo $rental['tool_id']; ?>" class="btn btn-secondary">
                            <i class="fas fa-info-circle"></i> View Tool Details
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Tool Information -->
            <div class="card mb-3">
                <div class="card-body">
                    <div style="display: flex; gap: 20px; align-items: center;">
                        <img src="<?php echo $rental['image_url'] ?: 'https://images.unsplash.com/photo-1572981779307-38b8cabb2407?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=150&q=80'; ?>" 
                             alt="<?php echo htmlspecialchars($rental['tool_name']); ?>" 
                             style="width: 120px; height: 120px; object-fit: cover; border-radius: var(--border-radius);"
                             onerror="this.src='https://images.unsplash.com/photo-1572981779307-38b8cabb2407?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=150&q=80'">
                        
                        <div>
                            <h3><?php echo htmlspecialchars($rental['tool_name']); ?></h3>
                            <div style="margin: 10px 0; color: #666;">
                                <strong>Rental Period:</strong><br>
                                <?php echo date('M j, Y', strtotime($rental['rental_date'])); ?> - 
                                <?php echo date('M j, Y', strtotime($rental['return_date'])); ?>
                            </div>
                            <div style="margin: 10px 0; color: #666;">
                                <strong>Returned on:</strong> 
                                <?php echo date('M j, Y', strtotime($rental['actual_return_date'])); ?>
                            </div>
                            <div style="margin: 10px 0; color: #666;">
                                <strong>Rental ID:</strong> #<?php echo $rental['rental_id']; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Review Form -->
            <div class="card">
                <div class="card-header">
                    <h3>Your Review</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <!-- Rating Selection -->
                        <div class="form-group">
                            <label class="form-label">Overall Rating *</label>
                            <div style="margin: 15px 0;">
                                <div class="star-rating-input" style="font-size: 2rem;">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star-input" onclick="setRating(<?php echo $i; ?>)" style="cursor: pointer; color: #E9ECEF; margin-right: 5px;" data-rating="<?php echo $i; ?>">
                                            ☆
                                        </span>
                                    <?php endfor; ?>
                                </div>
                                <input type="hidden" id="rating" name="rating" value="" required>
                                <div id="rating-text" style="margin-top: 10px; font-weight: bold; color: var(--primary-gray);"></div>
                            </div>
                        </div>
                        
                        <!-- Review Text -->
                        <div class="form-group">
                            <label class="form-label" for="review_text">Your Experience (Optional)</label>
                            <textarea id="review_text" name="review_text" class="form-control" rows="5" 
                                      placeholder="Tell other customers about your experience with this tool. Was it easy to use? Did it work well for your project? Any tips for other users?"></textarea>
                            <small style="color: #666;">Share details about the tool's condition, performance, and your overall experience</small>
                        </div>
                        
                        <!-- Review Guidelines -->
                        <div style="background: var(--light-gray); padding: 15px; border-radius: var(--border-radius); margin: 20px 0;">
                            <h5>Review Guidelines</h5>
                            <ul style="margin: 10px 0; padding-left: 20px; font-size: 0.9rem;">
                                <li>Be honest and helpful to other customers</li>
                                <li>Focus on the tool's performance and condition</li>
                                <li>Mention any issues you encountered</li>
                                <li>Keep your review appropriate and respectful</li>
                                <li>Reviews cannot be edited once submitted</li>
                            </ul>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-star"></i> Submit Review
                            </button>
                            <a href="user_dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.star-input:hover {
    color: #FFC107 !important;
}

.star-input.active {
    color: #FFC107 !important;
}

@media (max-width: 768px) {
    .card .card-body > div[style*="display: flex"] {
        flex-direction: column;
        text-align: center;
    }
    
    .card .card-body img {
        width: 100%;
        height: 200px;
        margin-bottom: 15px;
    }
}
</style>

<script>
const ratingTexts = {
    1: "Poor - Tool didn't meet expectations",
    2: "Fair - Tool had significant issues",
    3: "Good - Tool worked as expected",
    4: "Very Good - Tool performed well",
    5: "Excellent - Outstanding tool performance"
};

function setRating(rating) {
    const stars = document.querySelectorAll('.star-input');
    const ratingInput = document.getElementById('rating');
    const ratingText = document.getElementById('rating-text');
    
    // Set the rating value
    ratingInput.value = rating;
    
    // Update star display
    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.add('active');
            star.innerHTML = '★';
        } else {
            star.classList.remove('active');
            star.innerHTML = '☆';
        }
    });
    
    // Update rating text
    ratingText.textContent = ratingTexts[rating];
}

// Add hover effects
document.addEventListener('DOMContentLoaded', function() {
    const stars = document.querySelectorAll('.star-input');
    
    stars.forEach((star, index) => {
        star.addEventListener('mouseenter', function() {
            const hoverRating = index + 1;
            stars.forEach((s, i) => {
                if (i < hoverRating) {
                    s.style.color = '#FFC107';
                    s.innerHTML = '★';
                } else {
                    s.style.color = '#E9ECEF';
                    s.innerHTML = '☆';
                }
            });
        });
        
        star.addEventListener('mouseleave', function() {
            const currentRating = parseInt(document.getElementById('rating').value) || 0;
            stars.forEach((s, i) => {
                if (i < currentRating) {
                    s.style.color = '#FFC107';
                    s.innerHTML = '★';
                } else {
                    s.style.color = '#E9ECEF';
                    s.innerHTML = '☆';
                }
            });
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>