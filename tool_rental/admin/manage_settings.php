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
    if (isset($_POST['update_settings'])) {
        $late_fee = floatval($_POST['late_fee_per_day']);
        $damage_fee = floatval($_POST['damage_fee_percentage']);
        
        // Update late fee setting
        $update_late_fee_query = "INSERT INTO settings (setting_name, setting_value, description) 
                                  VALUES ('late_fee_per_day', ?, 'Late return fee per day per tool (in INR)')
                                  ON DUPLICATE KEY UPDATE setting_value = ?";
        $stmt1 = $conn->prepare($update_late_fee_query);
        $stmt1->bind_param("ss", $late_fee, $late_fee);
        
        // Update damage fee setting
        $update_damage_fee_query = "INSERT INTO settings (setting_name, setting_value, description) 
                                    VALUES ('damage_fee_percentage', ?, 'Damage fee as percentage of tool actual price')
                                    ON DUPLICATE KEY UPDATE setting_value = ?";
        $stmt2 = $conn->prepare($update_damage_fee_query);
        $stmt2->bind_param("ss", $damage_fee, $damage_fee);
        
        if ($stmt1->execute() && $stmt2->execute()) {
            // Redirect to prevent resubmission on refresh
            header('Location: manage_settings.php?success=Settings updated successfully!');
            exit();
        } else {
            $error_message = "Failed to update settings.";
        }
    }
}

// Check for success message from redirect
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}

// Get current settings
$settings_query = "SELECT setting_name, setting_value FROM settings WHERE setting_name IN ('late_fee_per_day', 'damage_fee_percentage')";
$settings_result = $conn->query($settings_query);

$settings = [];
while ($setting = $settings_result->fetch_assoc()) {
    $settings[$setting['setting_name']] = $setting['setting_value'];
}

$late_fee_per_day = isset($settings['late_fee_per_day']) ? $settings['late_fee_per_day'] : '50';
$damage_fee_percentage = isset($settings['damage_fee_percentage']) ? $settings['damage_fee_percentage'] : '20';

include '../includes/header.php';
?>

<div class="container">
    <div class="main-content">
        <h1><i class="fas fa-cog"></i> Manage Settings</h1>
        <p class="mb-3">Configure rental fee settings</p>
        
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
        
        <!-- Settings Form -->
        <div class="card">
            <div class="card-header">
                <h3>Rental Fee Settings</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="grid grid-2">
                        <div class="form-group">
                            <label class="form-label" for="late_fee_per_day">Late Fee Per Day (₹)</label>
                            <input type="number" id="late_fee_per_day" name="late_fee_per_day" class="form-control" 
                                   value="<?php echo htmlspecialchars($late_fee_per_day); ?>" min="0" step="0.01" required>
                            <small class="form-text">The fee charged per day for late returns</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="damage_fee_percentage">Damage Fee Percentage (%)</label>
                            <input type="number" id="damage_fee_percentage" name="damage_fee_percentage" class="form-control" 
                                   value="<?php echo htmlspecialchars($damage_fee_percentage); ?>" min="0" max="100" step="0.1" required>
                            <small class="form-text">Percentage of tool's actual price charged for damage</small>
                        </div>
                    </div>
                    
                    <button type="submit" name="update_settings" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Settings
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Current Settings Display -->
        <div class="card mt-3">
            <div class="card-header">
                <h3>Current Settings</h3>
            </div>
            <div class="card-body">
                <div class="grid grid-2">
                    <div>
                        <strong>Late Fee Per Day:</strong>
                        <div class="mt-1">₹<?php echo number_format($late_fee_per_day, 2); ?></div>
                    </div>
                    <div>
                        <strong>Damage Fee Percentage:</strong>
                        <div class="mt-1"><?php echo number_format($damage_fee_percentage, 2); ?>%</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>