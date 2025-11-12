<?php
require_once 'includes/db_connect.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user information
$user_query = "SELECT * FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

// Get rental statistics
$stats_queries = [
    'total_rentals' => "SELECT COUNT(*) as count FROM rentals WHERE user_id = ?",
    'active_rentals' => "SELECT COUNT(*) as count FROM rentals WHERE user_id = ? AND status = 'active'",
    'overdue_rentals' => "SELECT COUNT(*) as count FROM rentals WHERE user_id = ? AND status = 'overdue'",
    'total_spent' => "SELECT SUM(total_amount + late_fine + damage_fine) as total FROM rentals WHERE user_id = ?"
];

$stats = [];
foreach ($stats_queries as $key => $query) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats[$key] = $result->fetch_assoc();
}

// Get recent rentals with payment information
$rentals_query = "SELECT r.*, t.name as tool_name, t.image_url, t.actual_price,
                  DATEDIFF(CURDATE(), r.return_date) as days_overdue
                  FROM rentals r 
                  JOIN tools t ON r.tool_id = t.tool_id 
                  WHERE r.user_id = ? 
                  ORDER BY r.created_at DESC 
                  LIMIT 10";
$rentals_stmt = $conn->prepare($rentals_query);
$rentals_stmt->bind_param("i", $user_id);
$rentals_stmt->execute();
$rentals_result = $rentals_stmt->get_result();

// Check for rentals that can be reviewed
$reviewable_query = "SELECT r.rental_id, r.tool_id, t.name as tool_name 
                     FROM rentals r 
                     JOIN tools t ON r.tool_id = t.tool_id 
                     LEFT JOIN reviews rv ON r.rental_id = rv.rental_id 
                     WHERE r.user_id = ? AND r.status = 'returned' AND rv.review_id IS NULL
                     ORDER BY r.actual_return_date DESC";
$reviewable_stmt = $conn->prepare($reviewable_query);
$reviewable_stmt->bind_param("i", $user_id);
$reviewable_stmt->execute();
$reviewable_result = $reviewable_stmt->get_result();

include 'includes/header.php';
?>

<div class="container">
    <div class="main-content">
        <h1><i class="fas fa-user-circle"></i> My Dashboard</h1>
        <p class="mb-3">Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>! Manage your rentals and account here.</p>
        
        <!-- User Statistics -->
        <div class="grid grid-4 mb-3">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-handshake" style="font-size: 2rem; color: var(--accent-yellow); margin-bottom: 10px;"></i>
                    <h3><?php echo $stats['total_rentals']['count']; ?></h3>
                    <p>Total Rentals</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-clock" style="font-size: 2rem; color: var(--accent-yellow); margin-bottom: 10px;"></i>
                    <h3><?php echo $stats['active_rentals']['count']; ?></h3>
                    <p>Active Rentals</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-exclamation-triangle" style="font-size: 2rem; color: var(--accent-yellow); margin-bottom: 10px;"></i>
                    <h3 style="color: <?php echo $stats['overdue_rentals']['count'] > 0 ? '#DC3545' : 'inherit'; ?>">
                        <?php echo $stats['overdue_rentals']['count']; ?>
                    </h3>
                    <p>Overdue Items</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-rupee-sign" style="font-size: 2rem; color: var(--accent-yellow); margin-bottom: 10px;"></i>
                    <h3>₹<?php echo number_format($stats['total_spent']['total'] ?: 0, 2); ?></h3>
                    <p>Total Spent</p>
                </div>
            </div>
        </div>
        
        <div class="grid grid-2" style="gap: 30px;">
            <!-- Recent Rentals -->
            <div>
                <!-- ID Proof Status Note -->
                <?php
                // Check if user has uploaded ID proof
                $id_proof_query = "SELECT id_proof_image FROM rentals WHERE user_id = ? AND id_proof_image IS NOT NULL LIMIT 1";
                $id_proof_stmt = $conn->prepare($id_proof_query);
                $id_proof_stmt->bind_param("i", $user_id);
                $id_proof_stmt->execute();
                $id_proof_result = $id_proof_stmt->get_result();
                $has_id_proof = $id_proof_result->num_rows > 0;
                ?>
                
                <?php if (!$has_id_proof): ?>
                    <div class="card mb-3" style="border-left: 4px solid #ffc107; background-color: #fff3cd;">
                        <div class="card-body">
                            <h5 style="color: #856404; margin-top: 0;">
                                <i class="fas fa-exclamation-circle"></i> ID Proof Required
                            </h5>
                            <p style="color: #856404; margin-bottom: 10px;">
                                For your first rental, you'll need to upload a government-issued ID proof during checkout.
                                This will be stored securely for future rentals.
                            </p>
                            <a href="browse_tools.php" class="btn btn-warning" style="color: #212529;">
                                <i class="fas fa-shopping-cart"></i> Start Renting
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card mb-3" style="border-left: 4px solid #28a745; background-color: #d4edda;">
                        <div class="card-body">
                            <h5 style="color: #155724; margin-top: 0;">
                                <i class="fas fa-check-circle"></i> ID Proof Verified
                            </h5>
                            <p style="color: #155724; margin-bottom: 10px;">
                                Your ID proof has been verified and stored in our system. You won't need to upload it again for future rentals.
                            </p>
                            <a href="manage_id_proof.php" class="btn btn-primary">
                                <i class="fas fa-id-card"></i> View/Update ID Proof
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h3>My Rental History</h3>
                    </div>
                    <div class="card-body rental-history-scroll">
                        <?php if ($rentals_result->num_rows > 0): ?>
                            <?php while ($rental = $rentals_result->fetch_assoc()): ?>
                                <div class="card mb-2" style="box-shadow: none; border: 1px solid var(--border-gray);">
                                    <div class="card-body">
                                        <div style="display: flex; gap: 15px;">
                                            <img src="<?php echo $rental['image_url'] ?: 'https://images.unsplash.com/photo-1572981779307-38b8cabb2407?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=100&q=80'; ?>" 
                                                 alt="<?php echo htmlspecialchars($rental['tool_name']); ?>" 
                                                 style="width: 80px; height: 80px; object-fit: cover; border-radius: var(--border-radius);"
                                                 onerror="this.src='https://images.unsplash.com/photo-1572981779307-38b8cabb2407?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=100&q=80'">
                                            
                                            <div style="flex: 1;">
                                                <h5><?php echo htmlspecialchars($rental['tool_name']); ?></h5>
                                                <div style="font-size: 0.9rem; color: #666; margin: 5px 0;">
                                                    <strong>Rental ID:</strong> #<?php echo $rental['rental_id']; ?>
                                                </div>
                                                <div style="font-size: 0.9rem; color: #666; margin: 5px 0;">
                                                    <strong>Period:</strong> 
                                                    <?php echo date('M j, Y', strtotime($rental['rental_date'])); ?>
                                                    <?php if ($rental['rental_time']): ?>
                                                        at <?php echo date('g:i A', strtotime($rental['rental_time'])); ?>
                                                    <?php endif; ?> - 
                                                    <?php echo date('M j, Y', strtotime($rental['return_date'])); ?>
                                                    <?php if ($rental['return_time']): ?>
                                                        at <?php echo date('g:i A', strtotime($rental['return_time'])); ?>
                                                    <?php endif; ?>
                                                    <?php if ($rental['actual_return_date']): ?>
                                                        <br><strong>Returned:</strong> <?php echo date('M j, Y', strtotime($rental['actual_return_date'])); ?>
                                                        <?php if ($rental['actual_return_time']): ?>
                                                            at <?php echo date('g:i A', strtotime($rental['actual_return_time'])); ?>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                    <br><strong>Quantity:</strong> <?php echo $rental['quantity']; ?>
                                                </div>
                                                <div style="margin: 10px 0;">
                                                    <span class="badge badge-<?php echo $rental['status']; ?>">
                                                        <?php echo ucfirst($rental['status']); ?>
                                                    </span>
                                                    
                                                    <?php if ($rental['status'] == 'active' && $rental['days_overdue'] > 0): ?>
                                                        <span class="badge" style="background-color: #DC3545; color: white; margin-left: 5px;">
                                                            <?php echo $rental['days_overdue']; ?> days overdue
                                                        </span>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Payment Method Badge -->
                                                    <?php if ($rental['payment_method'] === 'cod'): ?>
                                                        <span class="badge" style="background-color: #17a2b8; color: white; margin-left: 5px;">
                                                            COD
                                                        </span>
                                                        <span class="badge" style="background-color: #ffc107; color: black; margin-left: 5px;">
                                                            Deposit: ₹<?php echo number_format($rental['deposit_amount'], 2); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge" style="background-color: #28a745; color: white; margin-left: 5px;">
                                                            Full Payment
                                                        </span>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Proof Image Badge -->
                                                    <?php if (!empty($rental['proof_image'])): ?>
                                                        <a href="<?php echo htmlspecialchars($rental['proof_image']); ?>" target="_blank" class="badge" style="background-color: #6f42c1; color: white; margin-left: 5px; text-decoration: none;">
                                                            <i class="fas fa-image"></i> View Proof
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <!-- ID Proof Badge -->
                                                    <?php if (!empty($rental['id_proof_image'])): ?>
                                                        <a href="<?php echo htmlspecialchars($rental['id_proof_image']); ?>" target="_blank" class="badge" style="background-color: #fd7e14; color: white; margin-left: 5px; text-decoration: none;">
                                                            <i class="fas fa-id-card"></i> ID Verified
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Cancel Button for Active Rentals -->
                                                    <?php if ($rental['status'] == 'active'): ?>
                                                        <button onclick="cancelRental(<?php echo $rental['rental_id']; ?>, '<?php echo htmlspecialchars($rental['tool_name'], ENT_QUOTES); ?>')" 
                                                                class="btn-cancel-rental" 
                                                                style="background: #DC3545; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; margin-left: 10px; cursor: pointer;"
                                                                title="Cancel this rental">
                                                            <i class="fas fa-times"></i> Cancel
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                                <div style="font-weight: bold;">
                                                    Total: ₹<?php echo number_format($rental['total_amount'] + $rental['late_fine'] + $rental['damage_fine'], 2); ?>
                                                    <?php if ($rental['late_fine'] > 0 || $rental['damage_fine'] > 0): ?>
                                                        <small style="color: #DC3545;">
                                                            (Base: ₹<?php echo number_format($rental['total_amount'], 2); ?>
                                                            <?php if ($rental['late_fine'] > 0): ?>
                                                                + Late: ₹<?php echo number_format($rental['late_fine'], 2); ?>
                                                            <?php endif; ?>
                                                            <?php if ($rental['damage_fine'] > 0): ?>
                                                                + Damage: ₹<?php echo number_format($rental['damage_fine'], 2); ?>
                                                            <?php endif; ?>
                                                            )
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-center">No rentals found. <a href="browse_tools.php">Start renting tools</a>!</p>
                        <?php endif; ?>
                    </div>
                </div>

<!-- Location Map -->
<div class="card mb-3">
    <div class="card-header">
        <h3><i class="fas fa-map-marker-alt"></i> Our Location</h3>
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
            <p class="text-center mt-2"><i class="fas fa-map-marker-alt"></i> Place Rajakumari</p>
        </div>
    </div>
</div>
            </div>
        </div>
    </div>

<style>
/* Rental History Scroll */
.rental-history-scroll {
    max-height: 500px;
    overflow-y: auto;
    padding-right: 10px;
}

/* Custom scrollbar for rental history */
.rental-history-scroll::-webkit-scrollbar {
    width: 8px;
}

.rental-history-scroll::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.rental-history-scroll::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.rental-history-scroll::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

@media (max-width: 768px) {
    .grid.grid-2,
    .grid.grid-4 {
        grid-template-columns: 1fr;
    }
    
    .card .card-body > div[style*="display: flex"] {
        flex-direction: column;
    }
    
    .card .card-body img {
        width: 100%;
        height: 150px;
        margin-bottom: 10px;
    }
    
    /* Remove scroll on mobile for better usability */
    .rental-history-scroll {
        max-height: none;
        overflow-y: visible;
    }
}

/* Cancel button hover effect */
.btn-cancel-rental:hover {
    background: #c82333 !important;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    transition: all 0.2s ease;
}

/* Loading state for cancel button */
.btn-cancel-rental:disabled {
    background: #6c757d !important;
    cursor: not-allowed;
    opacity: 0.7;
}
</style>

<script>
function cancelRental(rentalId, toolName) {
    if (!confirm(`Are you sure you want to cancel the rental for "${toolName}"?\n\nThis action cannot be undone. The tool will be made available for other customers.`)) {
        return;
    }
    
    const button = document.querySelector(`button[onclick*="${rentalId}"]`);
    const originalText = button.innerHTML;
    
    // Show loading state
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cancelling...';
    
    // Create form data
    const formData = new FormData();
    formData.append('rental_id', rentalId);
    
    // Send AJAX request
    fetch('modules/cancel_rental.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            alert(`Success! ${data.message}`);
            
            // Reload the page to show updated data
            window.location.reload();
        } else {
            // Show error message
            alert(`Error: ${data.message}`);
            
            // Restore button
            button.disabled = false;
            button.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while cancelling the rental. Please try again.');
        
        // Restore button
        button.disabled = false;
        button.innerHTML = originalText;
    });
}
</script>

<?php include 'includes/footer.php'; ?>
