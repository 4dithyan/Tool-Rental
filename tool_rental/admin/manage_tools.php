<?php
require_once '../includes/db_connect.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Function to handle image upload
function handleImageUpload($file) {
    $upload_dir = '../uploads/tools/';
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    // Check file type
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, GIF, and WebP images are allowed.'];
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        return ['success' => false, 'error' => 'File too large. Maximum size is 5MB.'];
    }
    
    // Generate unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $unique_name = 'tool_' . time() . '_' . uniqid() . '.' . $file_extension;
    $upload_path = $upload_dir . $unique_name;
    
    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return ['success' => true, 'url' => 'uploads/tools/' . $unique_name];
    } else {
        return ['success' => false, 'error' => 'Failed to upload file.'];
    }
}

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_tool'])) {
        // Add new tool
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $category_id = $_POST['category_id'];
        $daily_rate = floatval($_POST['daily_rate']);
        $actual_price = floatval($_POST['actual_price']);
        $quantity_available = intval($_POST['quantity_available']);
        $brand = trim($_POST['brand']);
        $model = trim($_POST['model']);
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_common = isset($_POST['is_common']) ? 1 : 0;
        
        // Handle image upload
        $image_url = '';
        if (isset($_FILES['tool_image']) && $_FILES['tool_image']['error'] == UPLOAD_ERR_OK) {
            $upload_result = handleImageUpload($_FILES['tool_image']);
            if ($upload_result['success']) {
                $image_url = $upload_result['url'];
            } else {
                $error_message = $upload_result['error'];
            }
        } elseif (!empty($_POST['image_url'])) {
            $image_url = trim($_POST['image_url']);
        }
        
        if (!empty($name) && !empty($description) && $daily_rate > 0 && $actual_price > 0 && empty($error_message)) {
            $insert_query = "INSERT INTO tools (name, description, category_id, daily_rate, actual_price, quantity_available, brand, model, image_url, is_featured, is_common) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("ssiddisssii", $name, $description, $category_id, $daily_rate, $actual_price, $quantity_available, $brand, $model, $image_url, $is_featured, $is_common);
            
            if ($stmt->execute()) {
                // Redirect to prevent resubmission on refresh
                header('Location: manage_tools.php?success=Tool added successfully!');
                exit();
            } else {
                $error_message = "Failed to add tool.";
            }
        } elseif (empty($error_message)) {
            $error_message = "Please fill all required fields.";
        }
    } elseif (isset($_POST['update_tool'])) {
        // Update existing tool
        $tool_id = $_POST['tool_id'];
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $category_id = $_POST['category_id'];
        $daily_rate = floatval($_POST['daily_rate']);
        $actual_price = floatval($_POST['actual_price']);
        $quantity_available = intval($_POST['quantity_available']);
        $brand = trim($_POST['brand']);
        $model = trim($_POST['model']);
        $status = $_POST['status'];
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_common = isset($_POST['is_common']) ? 1 : 0;
        
        // Handle image upload or keep existing
        $image_url = $_POST['existing_image_url']; // Keep existing image by default
        if (isset($_FILES['tool_image']) && $_FILES['tool_image']['error'] == UPLOAD_ERR_OK) {
            $upload_result = handleImageUpload($_FILES['tool_image']);
            if ($upload_result['success']) {
                // Delete old image if it exists and is in uploads folder
                if (!empty($image_url) && strpos($image_url, 'uploads/tools/') === 0) {
                    $old_image_path = '../' . $image_url;
                    if (file_exists($old_image_path)) {
                        unlink($old_image_path);
                    }
                }
                $image_url = $upload_result['url'];
            } else {
                $error_message = $upload_result['error'];
            }
        } elseif (!empty($_POST['image_url'])) {
            $image_url = trim($_POST['image_url']);
        }
        
        if (empty($error_message)) {
            $update_query = "UPDATE tools SET name=?, description=?, category_id=?, daily_rate=?, actual_price=?, 
                            quantity_available=?, brand=?, model=?, image_url=?, status=?, is_featured=?, is_common=? WHERE tool_id=?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ssiddissssiii", $name, $description, $category_id, $daily_rate, $actual_price, 
                             $quantity_available, $brand, $model, $image_url, $status, $is_featured, $is_common, $tool_id);
            
            if ($stmt->execute()) {
                // Redirect to prevent resubmission on refresh
                header('Location: manage_tools.php?success=Tool updated successfully!');
                exit();
            } else {
                $error_message = "Failed to update tool.";
            }
        }
    } elseif (isset($_POST['delete_tool'])) {
        // Delete tool
        $tool_id = $_POST['tool_id'];
        
        // Check if tool has active rentals
        $check_query = "SELECT COUNT(*) as count FROM rentals WHERE tool_id = ? AND status = 'active'";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("i", $tool_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $active_rentals = $result->fetch_assoc()['count'];
        
        if ($active_rentals > 0) {
            $error_message = "Cannot delete tool with active rentals.";
        } else {
            $delete_query = "DELETE FROM tools WHERE tool_id = ?";
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param("i", $tool_id);
            
            if ($stmt->execute()) {
                // Redirect to prevent resubmission on refresh
                header('Location: manage_tools.php?success=Tool deleted successfully!');
                exit();
            } else {
                $error_message = "Failed to delete tool.";
            }
        }
    }
}

// Check for success message from redirect
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}

// Get tool for editing if edit parameter is set
$editing_tool = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $edit_query = "SELECT * FROM tools WHERE tool_id = ?";
    $edit_stmt = $conn->prepare($edit_query);
    $edit_stmt->bind_param("i", $edit_id);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result();
    if ($edit_result->num_rows == 1) {
        $editing_tool = $edit_result->fetch_assoc();
    }
}

// Get all categories for dropdown
$categories_query = "SELECT * FROM categories ORDER BY category_name";
$categories_result = $conn->query($categories_query);

// Get all tools
$tools_query = "SELECT t.*, c.category_name FROM tools t 
                LEFT JOIN categories c ON t.category_id = c.category_id 
                ORDER BY t.created_at DESC";
$tools_result = $conn->query($tools_query);

include '../includes/header.php';
?>

<div class="container">
    <div class="main-content">
        <h1><i class="fas fa-tools"></i> Manage Tools</h1>
        <p class="mb-3">Add, edit, and manage your tool inventory</p>
        
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
        
        <!-- Add/Edit Tool Form -->
        <div class="card mb-3">
            <div class="card-header">
                <h3><?php echo $editing_tool ? 'Edit Tool' : 'Add New Tool'; ?></h3>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <?php if ($editing_tool): ?>
                        <input type="hidden" name="tool_id" value="<?php echo $editing_tool['tool_id']; ?>">
                    <?php endif; ?>
                    
                    <div class="grid grid-2">
                        <div class="form-group">
                            <label class="form-label" for="name">Tool Name *</label>
                            <input type="text" id="name" name="name" class="form-control" 
                                   value="<?php echo $editing_tool ? htmlspecialchars($editing_tool['name']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="category_id">Category *</label>
                            <select id="category_id" name="category_id" class="form-control form-select" required>
                                <option value="">Select Category</option>
                                <?php 
                                $categories_result->data_seek(0); // Reset result pointer
                                while ($category = $categories_result->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $category['category_id']; ?>" 
                                            <?php echo ($editing_tool && $editing_tool['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="daily_rate">Daily Rate (₹) *</label>
                            <input type="number" id="daily_rate" name="daily_rate" class="form-control" step="0.01" min="0" 
                                   value="<?php echo $editing_tool ? $editing_tool['daily_rate'] : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="actual_price">Actual Price (₹) *</label>
                            <input type="number" id="actual_price" name="actual_price" class="form-control" step="0.01" min="0" 
                                   value="<?php echo $editing_tool ? $editing_tool['actual_price'] : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="quantity_available">Quantity Available *</label>
                            <input type="number" id="quantity_available" name="quantity_available" class="form-control" min="0" 
                                   value="<?php echo $editing_tool ? $editing_tool['quantity_available'] : '1'; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="brand">Brand</label>
                            <input type="text" id="brand" name="brand" class="form-control" 
                                   value="<?php echo $editing_tool ? htmlspecialchars($editing_tool['brand']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="model">Model</label>
                            <input type="text" id="model" name="model" class="form-control" 
                                   value="<?php echo $editing_tool ? htmlspecialchars($editing_tool['model']) : ''; ?>">
                        </div>
                        
                        <?php if ($editing_tool): ?>
                        <div class="form-group">
                            <label class="form-label" for="status">Status</label>
                            <select id="status" name="status" class="form-control form-select">
                                <option value="active" <?php echo ($editing_tool['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($editing_tool['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                <option value="maintenance" <?php echo ($editing_tool['status'] == 'maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Tool Flags</label>
                            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                                <label style="display: flex; align-items: center; gap: 5px;">
                                    <input type="checkbox" name="is_featured" value="1" <?php echo ($editing_tool['is_featured'] == 1) ? 'checked' : ''; ?>>
                                    <span>Featured Tool</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 5px;">
                                    <input type="checkbox" name="is_common" value="1" <?php echo ($editing_tool['is_common'] == 1) ? 'checked' : ''; ?>>
                                    <span>Common Tool</span>
                                </label>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="description">Description *</label>
                        <textarea id="description" name="description" class="form-control" rows="3" required><?php echo $editing_tool ? htmlspecialchars($editing_tool['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Tool Image</label>
                        
                        <?php if ($editing_tool && !empty($editing_tool['image_url'])): ?>
                            <div style="margin-bottom: 15px;">
                                <p><strong>Current Image:</strong></p>
                                <img src="../<?php echo htmlspecialchars($editing_tool['image_url']); ?>" 
                                     alt="Current tool image" 
                                     style="max-width: 200px; max-height: 200px; border: 1px solid #ddd; border-radius: 5px;" 
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                <div style="display: none; padding: 20px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 5px; text-align: center; color: #666;">
                                    <i class="fas fa-image"></i><br>Image not found
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; align-items: end;">
                            <div>
                                <label class="form-label" for="tool_image">Upload New Image</label>
                                <input type="file" id="tool_image" name="tool_image" class="form-control" 
                                       accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" 
                                       onchange="previewImage(this)">
                                <small style="color: #666;">JPG, PNG, GIF, WebP (max 5MB)</small>
                            </div>
                            
                            <div>
                                <label class="form-label" for="image_url">Or Image URL</label>
                                <input type="url" id="image_url" name="image_url" class="form-control" 
                                       value="<?php echo (!$editing_tool || empty($editing_tool['image_url'])) ? '' : htmlspecialchars($editing_tool['image_url']); ?>" 
                                       placeholder="https://example.com/image.jpg">
                                <small style="color: #666;">Alternative to file upload</small>
                            </div>
                        </div>
                        
                        <?php if ($editing_tool): ?>
                            <input type="hidden" name="existing_image_url" value="<?php echo htmlspecialchars($editing_tool['image_url']); ?>">
                        <?php endif; ?>
                        
                        <!-- Image Preview -->
                        <div id="imagePreview" style="margin-top: 15px; display: none;">
                            <p><strong>Preview:</strong></p>
                            <img id="previewImg" src="" alt="Preview" 
                                 style="max-width: 200px; max-height: 200px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <?php if ($editing_tool): ?>
                            <button type="submit" name="update_tool" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Tool
                            </button>
                            <a href="manage_tools.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        <?php else: ?>
                            <button type="submit" name="add_tool" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Tool
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Tools List -->
        <div class="card">
            <div class="card-header">
                <h3>All Tools</h3>
            </div>
            <div class="card-body">
                <?php if ($tools_result->num_rows > 0): ?>
                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Daily Rate</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>Flags</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($tool = $tools_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($tool['image_url'])): ?>
                                                <img src="../<?php echo htmlspecialchars($tool['image_url']); ?>" 
                                                     alt="<?php echo htmlspecialchars($tool['name']); ?>" 
                                                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 5px; border: 1px solid #ddd;" 
                                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                                <div style="display: none; width: 60px; height: 60px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 5px; display: flex; align-items: center; justify-content: center; color: #666; font-size: 12px;">
                                                    <i class="fas fa-image"></i>
                                                </div>
                                            <?php else: ?>
                                                <div style="width: 60px; height: 60px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 5px; display: flex; align-items: center; justify-content: center; color: #666;">
                                                    <i class="fas fa-image"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($tool['name']); ?></strong>
                                            <?php if ($tool['brand']): ?>
                                                <br><small><?php echo htmlspecialchars($tool['brand'] . ' ' . $tool['model']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($tool['category_name'] ?: 'Uncategorized'); ?></td>
                                        <td>₹<?php echo number_format($tool['daily_rate'], 2); ?></td>
                                        <td>
                                            <?php if ($tool['quantity_available'] <= 1): ?>
                                                <span style="color: #DC3545; font-weight: bold;">
                                                    <?php echo $tool['quantity_available']; ?>
                                                </span>
                                            <?php else: ?>
                                                <?php echo $tool['quantity_available']; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo $tool['status']; ?>">
                                                <?php echo ucfirst($tool['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($tool['is_featured']): ?>
                                                <span class="badge badge-active" style="margin-right: 5px;">Featured</span>
                                            <?php endif; ?>
                                            <?php if ($tool['is_common']): ?>
                                                <span class="badge badge-active">Common</span>
                                            <?php endif; ?>
                                            <?php if (!$tool['is_featured'] && !$tool['is_common']): ?>
                                                <span class="badge badge-inactive">None</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="manage_tools.php?edit=<?php echo $tool['tool_id']; ?>" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem;">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this tool?');">
                                                    <input type="hidden" name="tool_id" value="<?php echo $tool['tool_id']; ?>">
                                                    <button type="submit" name="delete_tool" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No tools found. Add your first tool above!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        }
        
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.style.display = 'none';
    }
}

// Clear URL field when file is selected
document.getElementById('tool_image').addEventListener('change', function() {
    if (this.files && this.files[0]) {
        document.getElementById('image_url').value = '';
    }
});

// Clear file field when URL is entered
document.getElementById('image_url').addEventListener('input', function() {
    if (this.value.trim()) {
        document.getElementById('tool_image').value = '';
        document.getElementById('imagePreview').style.display = 'none';
    }
});
</script>