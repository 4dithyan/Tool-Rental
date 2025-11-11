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
    if (isset($_POST['add_category'])) {
        // Add new category
        $category_name = trim($_POST['category_name']);
        $description = trim($_POST['description']);
        
        if (!empty($category_name)) {
            // Check if category already exists
            $check_query = "SELECT category_id FROM categories WHERE category_name = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("s", $category_name);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error_message = "Category already exists.";
            } else {
                $insert_query = "INSERT INTO categories (category_name, description) VALUES (?, ?)";
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param("ss", $category_name, $description);
                
                if ($stmt->execute()) {
                    // Redirect to prevent resubmission on refresh
                    header('Location: manage_categories.php?success=Category added successfully!');
                    exit();
                } else {
                    $error_message = "Failed to add category.";
                }
            }
        } else {
            $error_message = "Category name is required.";
        }
    } elseif (isset($_POST['update_category'])) {
        // Update existing category
        $category_id = $_POST['category_id'];
        $category_name = trim($_POST['category_name']);
        $description = trim($_POST['description']);
        
        if (!empty($category_name)) {
            $update_query = "UPDATE categories SET category_name=?, description=? WHERE category_id=?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ssi", $category_name, $description, $category_id);
            
            if ($stmt->execute()) {
                // Redirect to prevent resubmission on refresh
                header('Location: manage_categories.php?success=Category updated successfully!');
                exit();
            } else {
                $error_message = "Failed to update category.";
            }
        } else {
            $error_message = "Category name is required.";
        }
    } elseif (isset($_POST['delete_category'])) {
        // Delete category
        $category_id = $_POST['category_id'];
        
        // Check if category has tools
        $check_query = "SELECT COUNT(*) as count FROM tools WHERE category_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("i", $category_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $tool_count = $result->fetch_assoc()['count'];
        
        if ($tool_count > 0) {
            $error_message = "Cannot delete category with existing tools. Please move or delete the tools first.";
        } else {
            $delete_query = "DELETE FROM categories WHERE category_id = ?";
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param("i", $category_id);
            
            if ($stmt->execute()) {
                // Redirect to prevent resubmission on refresh
                header('Location: manage_categories.php?success=Category deleted successfully!');
                exit();
            } else {
                $error_message = "Failed to delete category.";
            }
        }
    }
}

// Check for success message from redirect
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}

// Get category for editing if edit parameter is set
$editing_category = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $edit_query = "SELECT * FROM categories WHERE category_id = ?";
    $edit_stmt = $conn->prepare($edit_query);
    $edit_stmt->bind_param("i", $edit_id);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result();
    if ($edit_result->num_rows == 1) {
        $editing_category = $edit_result->fetch_assoc();
    }
}

// Get all categories with tool count
$categories_query = "SELECT c.*, COUNT(t.tool_id) as tool_count 
                    FROM categories c 
                    LEFT JOIN tools t ON c.category_id = t.category_id 
                    GROUP BY c.category_id 
                    ORDER BY c.category_name";
$categories_result = $conn->query($categories_query);

include '../includes/header.php';
?>

<div class="container">
    <div class="main-content">
        <h1><i class="fas fa-tags"></i> Manage Categories</h1>
        <p class="mb-3">Organize your tools by creating and managing categories</p>
        
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
        
        <!-- Add/Edit Category Form -->
        <div class="card mb-3">
            <div class="card-header">
                <h3><?php echo $editing_category ? 'Edit Category' : 'Add New Category'; ?></h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?php if ($editing_category): ?>
                        <input type="hidden" name="category_id" value="<?php echo $editing_category['category_id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label class="form-label" for="category_name">Category Name *</label>
                        <input type="text" id="category_name" name="category_name" class="form-control" 
                               value="<?php echo $editing_category ? htmlspecialchars($editing_category['category_name']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3"><?php echo $editing_category ? htmlspecialchars($editing_category['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <?php if ($editing_category): ?>
                            <button type="submit" name="update_category" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Category
                            </button>
                            <a href="manage_categories.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        <?php else: ?>
                            <button type="submit" name="add_category" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Category
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Categories List -->
        <div class="card">
            <div class="card-header">
                <h3>All Categories</h3>
            </div>
            <div class="card-body">
                <?php if ($categories_result->num_rows > 0): ?>
                    <div class="grid grid-3">
                        <?php while ($category = $categories_result->fetch_assoc()): ?>
                            <div class="card">
                                <div class="card-body">
                                    <h4><?php echo htmlspecialchars($category['category_name']); ?></h4>
                                    <?php if ($category['description']): ?>
                                        <p><?php echo htmlspecialchars($category['description']); ?></p>
                                    <?php endif; ?>
                                    <p><strong><?php echo $category['tool_count']; ?></strong> tools in this category</p>
                                    
                                    <div class="d-flex gap-1 mt-2">
                                        <a href="manage_categories.php?edit=<?php echo $category['category_id']; ?>" class="btn btn-secondary" style="padding: 8px 12px; font-size: 0.9rem;">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <?php if ($category['tool_count'] == 0): ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                                <input type="hidden" name="category_id" value="<?php echo $category['category_id']; ?>">
                                                <button type="submit" name="delete_category" class="btn btn-danger" style="padding: 8px 12px; font-size: 0.9rem;">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-danger" style="padding: 8px 12px; font-size: 0.9rem; opacity: 0.5;" disabled title="Cannot delete category with tools">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center">No categories found. Add your first category above!</p>
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

<?php include '../includes/footer.php'; ?>