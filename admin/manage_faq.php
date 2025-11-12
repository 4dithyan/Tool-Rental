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
    if (isset($_POST['add_faq'])) {
        // Add new FAQ
        $question = trim($_POST['question']);
        $answer = trim($_POST['answer']);
        $category = trim($_POST['category']);
        $sort_order = intval($_POST['sort_order']);
        
        if (!empty($question) && !empty($answer)) {
            $insert_query = "INSERT INTO faq (question, answer, category, sort_order) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("sssi", $question, $answer, $category, $sort_order);
            
            if ($stmt->execute()) {
                // Redirect to prevent resubmission on refresh
                header('Location: manage_faq.php?success=FAQ added successfully!');
                exit();
            } else {
                $error_message = "Failed to add FAQ.";
            }
        } else {
            $error_message = "Please fill all required fields.";
        }
    } elseif (isset($_POST['update_faq'])) {
        // Update existing FAQ
        $faq_id = $_POST['faq_id'];
        $question = trim($_POST['question']);
        $answer = trim($_POST['answer']);
        $category = trim($_POST['category']);
        $sort_order = intval($_POST['sort_order']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (!empty($question) && !empty($answer)) {
            $update_query = "UPDATE faq SET question=?, answer=?, category=?, sort_order=?, is_active=? WHERE id=?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("sssiii", $question, $answer, $category, $sort_order, $is_active, $faq_id);
            
            if ($stmt->execute()) {
                // Redirect to prevent resubmission on refresh
                header('Location: manage_faq.php?success=FAQ updated successfully!');
                exit();
            } else {
                $error_message = "Failed to update FAQ.";
            }
        } else {
            $error_message = "Please fill all required fields.";
        }
    } elseif (isset($_POST['delete_faq'])) {
        // Delete FAQ
        $faq_id = $_POST['faq_id'];
        
        $delete_query = "DELETE FROM faq WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $faq_id);
        
        if ($stmt->execute()) {
            // Redirect to prevent resubmission on refresh
            header('Location: manage_faq.php?success=FAQ deleted successfully!');
            exit();
        } else {
            $error_message = "Failed to delete FAQ.";
        }
    }
}

// Check for success message from redirect
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}

// Get FAQ for editing if edit parameter is set
$editing_faq = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $edit_query = "SELECT * FROM faq WHERE id = ?";
    $edit_stmt = $conn->prepare($edit_query);
    $edit_stmt->bind_param("i", $edit_id);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result();
    if ($edit_result->num_rows == 1) {
        $editing_faq = $edit_result->fetch_assoc();
    }
}

// Get all FAQ items
$faq_query = "SELECT * FROM faq ORDER BY sort_order ASC, created_at DESC";
$faq_result = $conn->query($faq_query);

include '../includes/header.php';
?>

<div class="container">
    <div class="main-content">
        <h1><i class="fas fa-question-circle"></i> Manage FAQ</h1>
        <p class="mb-3">Add, edit, and manage your FAQ items</p>
        
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
        
        <!-- Add/Edit FAQ Form -->
        <div class="card mb-3">
            <div class="card-header">
                <h3><?php echo $editing_faq ? 'Edit FAQ' : 'Add New FAQ'; ?></h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?php if ($editing_faq): ?>
                        <input type="hidden" name="faq_id" value="<?php echo $editing_faq['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label class="form-label" for="question">Question *</label>
                        <input type="text" id="question" name="question" class="form-control" 
                               value="<?php echo $editing_faq ? htmlspecialchars($editing_faq['question']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="answer">Answer *</label>
                        <textarea id="answer" name="answer" class="form-control" rows="4" required><?php echo $editing_faq ? htmlspecialchars($editing_faq['answer']) : ''; ?></textarea>
                    </div>
                    
                    <div class="grid grid-2">
                        <div class="form-group">
                            <label class="form-label" for="category">Category</label>
                            <input type="text" id="category" name="category" class="form-control" 
                                   value="<?php echo $editing_faq ? htmlspecialchars($editing_faq['category']) : 'General'; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="sort_order">Sort Order</label>
                            <input type="number" id="sort_order" name="sort_order" class="form-control" min="0" 
                                   value="<?php echo $editing_faq ? $editing_faq['sort_order'] : '0'; ?>">
                        </div>
                    </div>
                    
                    <?php if ($editing_faq): ?>
                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" name="is_active" value="1" <?php echo ($editing_faq['is_active'] == 1) ? 'checked' : ''; ?>>
                            Active
                        </label>
                    </div>
                    <?php endif; ?>
                    
                    <div class="d-flex gap-2">
                        <?php if ($editing_faq): ?>
                            <button type="submit" name="update_faq" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update FAQ
                            </button>
                            <a href="manage_faq.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        <?php else: ?>
                            <button type="submit" name="add_faq" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add FAQ
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- FAQ List -->
        <div class="card">
            <div class="card-header">
                <h3>All FAQ Items</h3>
            </div>
            <div class="card-body">
                <?php if ($faq_result->num_rows > 0): ?>
                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th width="50">ID</th>
                                    <th>Question</th>
                                    <th>Category</th>
                                    <th width="100">Sort Order</th>
                                    <th width="100">Status</th>
                                    <th width="150">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($faq = $faq_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $faq['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars(substr($faq['question'], 0, 100)); ?><?php echo strlen($faq['question']) > 100 ? '...' : ''; ?></strong>
                                            <br>
                                            <small><?php echo htmlspecialchars(substr($faq['answer'], 0, 150)); ?><?php echo strlen($faq['answer']) > 150 ? '...' : ''; ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($faq['category']); ?></td>
                                        <td><?php echo $faq['sort_order']; ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $faq['is_active'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $faq['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="manage_faq.php?edit=<?php echo $faq['id']; ?>" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem;">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this FAQ item?');">
                                                    <input type="hidden" name="faq_id" value="<?php echo $faq['id']; ?>">
                                                    <button type="submit" name="delete_faq" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;">
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
                    <p class="text-center">No FAQ items found. Add your first FAQ above!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>