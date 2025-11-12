<?php
require_once '../includes/db_connect.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_admin'])) {
        // Add new admin
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        
        // Validation
        if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
            $error_message = "All fields are required.";
        } elseif ($password !== $confirm_password) {
            $error_message = "Passwords do not match.";
        } elseif (strlen($password) < 6) {
            $error_message = "Password must be at least 6 characters long.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Invalid email format.";
        } else {
            // Check if username or email already exists
            $check_query = "SELECT user_id FROM users WHERE username = ? OR email = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("ss", $username, $email);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $error_message = "Username or email already exists.";
            } else {
                // Create new admin user
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $insert_query = "INSERT INTO users (username, email, password_hash, first_name, last_name, role, created_at) 
                               VALUES (?, ?, ?, ?, ?, 'admin', NOW())";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bind_param("sssss", $username, $email, $password_hash, $first_name, $last_name);
                
                if ($insert_stmt->execute()) {
                    // Redirect to prevent resubmission on refresh
                    header('Location: manage_admins.php?success=Admin user ' . urlencode($username) . ' created successfully!');
                    exit();
                } else {
                    $error_message = "Failed to create admin user.";
                }
            }
        }
    } elseif (isset($_POST['toggle_status'])) {
        // Status toggle feature requires status column - show message
        $error_message = "Status management requires database update. Please add 'status' column to users table first.";
    } elseif (isset($_POST['reset_password'])) {
        // Reset admin password
        $user_id = $_POST['user_id'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_new_password'];
        
        if (empty($new_password) || $new_password !== $confirm_password) {
            $error_message = "Password fields are required and must match.";
        } elseif (strlen($new_password) < 6) {
            $error_message = "Password must be at least 6 characters long.";
        } else {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password_hash = ? WHERE user_id = ? AND role = 'admin'";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("si", $password_hash, $user_id);
            
            if ($update_stmt->execute()) {
                // Redirect to prevent resubmission on refresh
                header('Location: manage_admins.php?success=Password reset successfully for admin user.');
                exit();
            } else {
                $error_message = "Failed to reset password.";
            }
        }
    }
}

// Check for success message from redirect
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}

// Get all admin users
$admins_query = "SELECT user_id, username, email, first_name, last_name, created_at 
                FROM users WHERE role = 'admin' ORDER BY created_at DESC";
$admins_result = $conn->query($admins_query);

include '../includes/header.php';
?>

<div class="container">
    <div class="main-content">
        <h1><i class="fas fa-user-shield"></i> Manage Admin Users</h1>
        <p class="mb-3">Add and manage administrator accounts</p>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Add New Admin Form -->
        <div class="card mb-3">
            <div class="card-header">
                <h3>Add New Administrator</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="grid grid-2">
                        <div class="form-group">
                            <label class="form-label" for="username">Username *</label>
                            <input type="text" id="username" name="username" class="form-control" 
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="email">Email *</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="first_name">First Name *</label>
                            <input type="text" id="first_name" name="first_name" class="form-control" 
                                   value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="last_name">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" class="form-control" 
                                   value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="password">Password *</label>
                            <input type="password" id="password" name="password" class="form-control" 
                                   minlength="6" required>
                            <small style="color: #666;">Minimum 6 characters</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="confirm_password">Confirm Password *</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                                   minlength="6" required>
                        </div>
                    </div>
                    
                    <button type="submit" name="add_admin" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Create Admin User
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Current Admin Users -->
        <div class="card">
            <div class="card-header">
                <h3>Current Administrators (<?php echo $admins_result->num_rows; ?>)</h3>
            </div>
            <div class="card-body">
                <?php if ($admins_result->num_rows > 0): ?>
                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($admin = $admins_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($admin['username']); ?></strong>
                                            <?php if ($admin['user_id'] == $_SESSION['user_id']): ?>
                                                <small style="color: var(--accent-yellow); font-weight: bold;">(You)</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($admin['created_at'])); ?></td>
                                        <td>
                                            <?php if ($admin['user_id'] != $_SESSION['user_id']): ?>
                                                <div class="d-flex gap-1" style="flex-direction: column;">
                                                    <!-- Status management disabled until status column is added -->
                                                    <small style="color: #666; padding: 4px 8px; background: #f8f9fa; border-radius: 4px; text-align: center;">
                                                        <i class="fas fa-info-circle"></i> Add status column for full management
                                                    </small>
                                                    
                                                    <!-- Reset Password Button -->
                                                    <button onclick="showPasswordReset(<?php echo $admin['user_id']; ?>, '<?php echo htmlspecialchars($admin['username']); ?>')" 
                                                            class="btn btn-secondary" 
                                                            style="padding: 4px 8px; font-size: 0.8rem; width: 100%;">
                                                        <i class="fas fa-key"></i> Reset Password
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <small style="color: #666;">Current User</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No admin users found.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Navigation -->
        <div class="card mt-3">
            <div class="card-body">
                <h4>Quick Navigation</h4>
                <div class="d-flex gap-2">
                    <a href="admin_dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="manage_tools.php" class="btn btn-primary">
                        <i class="fas fa-tools"></i> Manage Tools
                    </a>
                    <a href="manage_rentals.php" class="btn btn-primary">
                        <i class="fas fa-list"></i> Manage Rentals
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Password Reset Modal -->
<div id="passwordResetModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 8px; min-width: 400px;">
        <h3>Reset Password</h3>
        <form method="POST" id="passwordResetForm">
            <input type="hidden" id="resetUserId" name="user_id">
            <p>Reset password for admin: <strong id="resetUsername"></strong></p>
            
            <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" minlength="6" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_new_password" class="form-control" minlength="6" required>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" name="reset_password" class="btn btn-primary">
                    <i class="fas fa-key"></i> Reset Password
                </button>
                <button type="button" onclick="hidePasswordReset()" class="btn btn-secondary">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showPasswordReset(userId, username) {
    document.getElementById('resetUserId').value = userId;
    document.getElementById('resetUsername').textContent = username;
    document.getElementById('passwordResetModal').style.display = 'block';
}

function hidePasswordReset() {
    document.getElementById('passwordResetModal').style.display = 'none';
    document.getElementById('passwordResetForm').reset();
}

// Close modal when clicking outside
document.getElementById('passwordResetModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hidePasswordReset();
    }
});
</script>

<?php include '../includes/footer.php'; ?>