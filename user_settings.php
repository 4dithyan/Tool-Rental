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

include 'includes/header.php';
?>

<div class="container">
    <div class="main-content">
        <h1><i class="fas fa-user-cog"></i> Account Settings</h1>
        <p class="mb-3">Manage your account information and preferences</p>
        
        <div class="grid grid-2" style="gap: 30px;">
            <!-- Account Information -->
            <div>
                <div class="card">
                    <div class="card-header">
                        <h3>Personal Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <strong>Name:</strong> 
                            <div><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                        </div>
                        <div class="form-group">
                            <strong>Email:</strong> 
                            <div><?php echo htmlspecialchars($user['email']); ?></div>
                        </div>
                        <div class="form-group">
                            <strong>Phone:</strong> 
                            <div><?php echo htmlspecialchars($user['phone'] ?: 'Not provided'); ?></div>
                        </div>
                        <div class="form-group">
                            <strong>Address:</strong> 
                            <div><?php echo htmlspecialchars($user['address'] ?: 'Not provided'); ?></div>
                        </div>
                        <div class="form-group">
                            <strong>Member since:</strong> 
                            <div><?php echo date('M j, Y', strtotime($user['created_at'])); ?></div>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle"></i> 
                            To update your information, please contact our support team.
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Account Actions -->
            <div>
                <div class="card mb-3">
                    <div class="card-header">
                        <h3>Account Actions</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; gap: 10px;">
                            <a href="manage_id_proof.php" class="btn btn-primary">
                                <i class="fas fa-id-card"></i> Manage ID Proof
                            </a>
                            <a href="user_dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-tachometer-alt"></i> Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Security Information -->
                <div class="card">
                    <div class="card-header">
                        <h3>Security</h3>
                    </div>
                    <div class="card-body">
                        <p style="color: #666; margin-bottom: 15px;">
                            For security reasons, password changes must be requested through our support team.
                        </p>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> 
                            If you suspect your account has been compromised, please contact support immediately.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>