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
    if (isset($_POST['reset_password'])) {
        // Reset user password
        $user_id = $_POST['user_id'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_new_password'];
        
        if (empty($new_password) || $new_password !== $confirm_password) {
            $error_message = "Password fields are required and must match.";
        } elseif (strlen($new_password) < 6) {
            $error_message = "Password must be at least 6 characters long.";
        } else {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password_hash = ? WHERE user_id = ? AND role = 'customer'";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("si", $password_hash, $user_id);
            
            if ($update_stmt->execute()) {
                $success_message = "Password reset successfully for user.";
            } else {
                $error_message = "Failed to reset password.";
            }
        }
    } elseif (isset($_POST['delete_user'])) {
        // Delete user
        $user_id = $_POST['user_id'];
        
        // Check if user has any active rentals
        $check_rentals_query = "SELECT COUNT(*) as count FROM rentals WHERE user_id = ? AND status IN ('active', 'overdue')";
        $check_stmt = $conn->prepare($check_rentals_query);
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $rental_result = $check_stmt->get_result();
        $rental_data = $rental_result->fetch_assoc();
        
        if ($rental_data['count'] > 0) {
            $error_message = "Cannot delete user with active rentals. Please resolve rentals first.";
        } else {
            // Delete user
            $delete_query = "DELETE FROM users WHERE user_id = ? AND role = 'customer'";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param("i", $user_id);
            
            if ($delete_stmt->execute()) {
                $success_message = "User deleted successfully.";
            } else {
                $error_message = "Failed to delete user.";
            }
        }
    }
}

// Get all customer users
$users_query = "SELECT user_id, username, email, first_name, last_name, phone, address, created_at 
                FROM users WHERE role = 'customer' ORDER BY created_at DESC";
$users_result = $conn->query($users_query);

include '../includes/header.php';
?>

<div class="container">
    <div class="main-content">
        <h1><i class="fas fa-users"></i> Manage Users</h1>
        <p class="mb-3">View and manage customer accounts</p>
        
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
        
        <!-- Current Users -->
        <div class="card">
            <div class="card-header">
                <h3>All Customers (<?php echo $users_result->num_rows; ?>)</h3>
            </div>
            <div class="card-body">
                <?php if ($users_result->num_rows > 0): ?>
                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Address</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($user = $users_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars(substr($user['address'] ?? 'N/A', 0, 30)) . (strlen($user['address'] ?? '') > 30 ? '...' : ''); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <div class="d-flex gap-1" style="flex-direction: column;">
                                                <!-- Reset Password Button -->
                                                <button onclick="showPasswordReset(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" 
                                                        class="btn btn-secondary" 
                                                        style="padding: 4px 8px; font-size: 0.8rem; width: 100%;">
                                                    <i class="fas fa-key"></i> Reset Password
                                                </button>
                                                <!-- Delete User Button -->
                                                <button onclick="showDeleteConfirm(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" 
                                                        class="btn btn-danger" 
                                                        style="padding: 4px 8px; font-size: 0.8rem; width: 100%;">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No customer users found.</p>
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
            <p>Reset password for user: <strong id="resetUsername"></strong></p>
            
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

function showDeleteConfirm(userId, username) {
    if (confirm('Are you sure you want to delete user "' + username + '"? This action cannot be undone.')) {
        // Create a form dynamically
        var form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        // Add user_id field
        var userIdField = document.createElement('input');
        userIdField.type = 'hidden';
        userIdField.name = 'user_id';
        userIdField.value = userId;
        form.appendChild(userIdField);
        
        // Add delete_user field
        var deleteField = document.createElement('input');
        deleteField.type = 'hidden';
        deleteField.name = 'delete_user';
        deleteField.value = '1';
        form.appendChild(deleteField);
        
        // Submit the form
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modal when clicking outside
document.getElementById('passwordResetModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hidePasswordReset();
    }
});
</script>

<?php include '../includes/footer.php'; ?>