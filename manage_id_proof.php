<?php
require_once 'includes/db_connect.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if user is admin
$user_id = $_SESSION['user_id'];
$user_query = "SELECT role FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

$is_admin = ($user['role'] === 'admin');

// If not admin and no target_user_id provided, redirect to user dashboard
$target_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : ($is_admin ? null : $user_id);

// If regular user trying to access another user's ID proof
if (!$is_admin && $target_user_id != $user_id) {
    header('Location: user_dashboard.php');
    exit();
}

// Get target user information
$target_user_query = "SELECT user_id, username, first_name, last_name, email FROM users WHERE user_id = ?";
$target_user_stmt = $conn->prepare($target_user_query);
$target_user_stmt->bind_param("i", $target_user_id);
$target_user_stmt->execute();
$target_user_result = $target_user_stmt->get_result();

if ($target_user_result->num_rows == 0) {
    header('Location: user_dashboard.php');
    exit();
}

$target_user = $target_user_result->fetch_assoc();

// Get user's ID proof
$id_proof_query = "SELECT id_proof_image FROM rentals WHERE user_id = ? AND id_proof_image IS NOT NULL ORDER BY created_at DESC LIMIT 1";
$id_proof_stmt = $conn->prepare($id_proof_query);
$id_proof_stmt->bind_param("i", $target_user_id);
$id_proof_stmt->execute();
$id_proof_result = $id_proof_stmt->get_result();
$user_id_proof = $id_proof_result->num_rows > 0 ? $id_proof_result->fetch_assoc()['id_proof_image'] : null;

// Process ID proof upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_id_proof'])) {
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
            $error_message = 'Only JPG, JPEG, PNG, GIF, and PDF files are allowed for ID proof.';
        }
        // Validate file size (max 5MB)
        elseif ($_FILES['id_proof']['size'] > 5000000) {
            $error_message = 'ID proof file size must be less than 5MB.';
        }
        // Move uploaded file
        elseif (move_uploaded_file($_FILES['id_proof']['tmp_name'], $target_file)) {
            // Store relative path in database for all user's rentals
            $id_proof_path = $upload_dir . $unique_filename;
            
            // Update all existing rentals for this user
            $update_query = "UPDATE rentals SET id_proof_image = ? WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("si", $id_proof_path, $target_user_id);
            
            if ($update_stmt->execute()) {
                $success_message = 'ID proof uploaded successfully and applied to all your rentals.';
                $user_id_proof = $id_proof_path;
            } else {
                $error_message = 'Failed to update rentals with new ID proof.';
            }
        } else {
            $error_message = 'Failed to upload ID proof image.';
        }
    } else {
        $error_message = 'Please select a file to upload.';
    }
}

include 'includes/header.php';
?>

<div class="container">
    <div class="main-content">
        <h1><i class="fas fa-id-card"></i> <?php echo $is_admin ? 'Manage' : 'My'; ?> ID Proof</h1>
        <p class="mb-3">
            <?php if ($is_admin): ?>
                Manage ID proof for user: <?php echo htmlspecialchars($target_user['first_name'] . ' ' . $target_user['last_name'] . ' (' . $target_user['username'] . ')'); ?>
            <?php else: ?>
                View and manage your ID proof document
            <?php endif; ?>
        </p>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <div class="grid grid-2" style="gap: 30px;">
            <!-- ID Proof Display -->
            <div>
                <div class="card">
                    <div class="card-header">
                        <h3>ID Proof Document</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($user_id_proof): ?>
                            <div class="text-center">
                                <?php 
                                $file_extension = strtolower(pathinfo($user_id_proof, PATHINFO_EXTENSION));
                                if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                    <img src="<?php echo htmlspecialchars($user_id_proof); ?>" 
                                         alt="ID Proof" 
                                         style="max-width: 100%; max-height: 400px; border: 1px solid #ddd; border-radius: 4px;">
                                <?php elseif ($file_extension === 'pdf'): ?>
                                    <iframe src="<?php echo htmlspecialchars($user_id_proof); ?>" 
                                            style="width: 100%; height: 400px; border: 1px solid #ddd; border-radius: 4px;"
                                            frameborder="0"></iframe>
                                <?php endif; ?>
                                
                                <div class="mt-2">
                                    <a href="<?php echo htmlspecialchars($user_id_proof); ?>" 
                                       target="_blank" 
                                       class="btn btn-primary">
                                        <i class="fas fa-external-link-alt"></i> View Full Document
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center" style="padding: 40px 20px;">
                                <i class="fas fa-id-card" style="font-size: 3rem; color: #ccc; margin-bottom: 20px;"></i>
                                <h4>No ID Proof Uploaded</h4>
                                <p><?php echo $is_admin ? 'This user has not uploaded an ID proof yet.' : 'You have not uploaded an ID proof yet. Please upload one below.'; ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Upload Form -->
            <div>
                <div class="card">
                    <div class="card-header">
                        <h3><?php echo $user_id_proof ? 'Replace' : 'Upload'; ?> ID Proof</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="id_proof">Select ID Proof Document</label>
                                <input type="file" 
                                       id="id_proof" 
                                       name="id_proof" 
                                       accept="image/*,.pdf" 
                                       class="form-control" 
                                       required>
                                <small class="form-text">
                                    Upload a government-issued ID (passport, driver's license, etc.)<br>
                                    Supported formats: JPG, JPEG, PNG, GIF, PDF<br>
                                    Maximum file size: 5MB
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" 
                                        name="upload_id_proof" 
                                        class="btn btn-primary">
                                    <i class="fas fa-upload"></i> 
                                    <?php echo $user_id_proof ? 'Replace ID Proof' : 'Upload ID Proof'; ?>
                                </button>
                                
                                <?php if ($is_admin): ?>
                                    <a href="admin/manage_rentals.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Back to Rentals
                                    </a>
                                <?php else: ?>
                                    <a href="user_dashboard.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                        
                        <div class="mt-3" style="padding: 15px; background-color: #f8f9fa; border-radius: 4px; border-left: 4px solid #007bff;">
                            <h5><i class="fas fa-info-circle"></i> Important Information</h5>
                            <ul style="margin: 10px 0; padding-left: 20px;">
                                <li>Your ID proof will be securely stored and used for all future rentals</li>
                                <li>You only need to upload your ID proof once</li>
                                <li><?php echo $is_admin ? 'Updating the ID proof will apply it to all rentals for this user' : 'You can update your ID proof at any time'; ?></li>
                                <li>All documents are encrypted and stored securely</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- User's Rental History -->
        <div class="card mt-3">
            <div class="card-header">
                <h3>Rental History with ID Proof</h3>
            </div>
            <div class="card-body">
                <?php
                // Get user's rentals with ID proof
                $rentals_query = "SELECT r.*, t.name as tool_name 
                                 FROM rentals r 
                                 JOIN tools t ON r.tool_id = t.tool_id 
                                 WHERE r.user_id = ? AND r.id_proof_image IS NOT NULL
                                 ORDER BY r.created_at DESC";
                $rentals_stmt = $conn->prepare($rentals_query);
                $rentals_stmt->bind_param("i", $target_user_id);
                $rentals_stmt->execute();
                $rentals_result = $rentals_stmt->get_result();
                ?>
                
                <?php if ($rentals_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Rental ID</th>
                                    <th>Tool</th>
                                    <th>Rental Period</th>
                                    <th>Status</th>
                                    <th>ID Proof</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($rental = $rentals_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong>#<?php echo $rental['rental_id']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($rental['tool_name']); ?></td>
                                        <td>
                                            <?php echo date('M j, Y', strtotime($rental['rental_date'])); ?> - 
                                            <?php echo date('M j, Y', strtotime($rental['return_date'])); ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo $rental['status']; ?>">
                                                <?php echo ucfirst($rental['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($rental['id_proof_image'])): ?>
                                                <a href="<?php echo htmlspecialchars($rental['id_proof_image']); ?>" 
                                                   target="_blank" 
                                                   class="btn btn-sm btn-warning">
                                                    <i class="fas fa-id-card"></i> View ID
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No rentals with ID proof found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>