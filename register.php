<?php
require_once 'includes/db_connect.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        $error_message = "All required fields must be filled.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } elseif (!empty($phone) && (strlen($phone) != 10 || !ctype_digit($phone))) {
        $error_message = "Phone number must be exactly 10 digits.";
    } else {
        // Check if username or email already exists
        $check_query = "SELECT user_id FROM users WHERE username = ? OR email = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = "Username or email already exists.";
        } else {
            // Hash password and insert user
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $insert_query = "INSERT INTO users (username, email, password_hash, first_name, last_name, phone, address) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("sssssss", $username, $email, $password_hash, $first_name, $last_name, $phone, $address);
            
            if ($insert_stmt->execute()) {
                // Get the newly created user's ID
                $user_id = $conn->insert_id;
                
                // Automatically log in the user
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
                $_SESSION['role'] = 'customer'; // Default role for new users
                
                // Redirect to homepage
                header('Location: index.php');
                exit();
            } else {
                $error_message = "Registration failed. Please try again.";
            }
        }
    }
}

include 'includes/header.php';
?>

<!-- Professional styling for register page with existing color scheme -->
<style>
.register-background {
    background-image: url("/PROJECTS/Mine/assets/images/register-bg.jpg?v=<?php echo time(); ?>") !important;
    background-size: cover !important;
    background-position: center center !important;
    background-repeat: no-repeat !important;
    background-attachment: fixed !important;
    min-height: 100vh !important;
    width: 100% !important;
    position: relative !important;
    margin: 0 !important;
    padding: 0 !important;
    overflow: hidden !important; /* Remove scroll */
}

/* Fallback if image doesn't load */
.register-background {
    background-image: url("/PROJECTS/Mine/assets/images/register-bg.jpg?v=<?php echo time(); ?>"), linear-gradient(-45deg, #1e3c72, #2a5298, #667eea, #764ba2) !important;
}

/* Reduce overlay darkness for better visibility */
.register-background::before {
    content: '' !important;
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    background: rgba(0, 0, 0, 0.1) !important; /* Even lighter overlay */
    z-index: 1 !important;
}

.register-background .main-content {
    position: relative !important;
    z-index: 2 !important;
}

/* Ensure the container takes full width */
.container.register-background {
    max-width: 100% !important;
    padding: 0 !important;
    margin: 0 !important;
}

/* Professional card styling with existing color scheme */
.register-card {
    background: rgba(255, 255, 255, 0.5) !important; /* More transparency */
    backdrop-filter: blur(5px) !important; /* Reduced blur for clarity */
    border: none !important;
    border-radius: 12px !important;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important; /* Lighter shadow */
    overflow: hidden !important;
    max-width: 550px !important;
    margin: 20px auto !important;
    transform: translateY(0) !important;
    animation: slideUp 0.6s ease-out forwards !important;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.register-card-header {
    background: linear-gradient(120deg, rgba(45, 45, 45, 0.7), rgba(255, 193, 7, 0.7)) !important; /* More transparent header */
    color: white !important;
    padding: 25px 30px !important;
    text-align: center !important;
    border-bottom: none !important;
}

.register-card-header h2 {
    font-weight: 600 !important;
    margin-bottom: 8px !important;
    font-size: 28px !important;
}

.register-card-header p {
    color: #e0e0e0 !important;
    font-size: 16px !important;
    margin: 0 !important;
}

.register-card-body {
    padding: 30px !important;
}

.register-card-footer {
    background: rgba(245, 245, 245, 0.4) !important; /* More transparency */
    padding: 20px 30px !important;
    text-align: center !important;
    border-top: 1px solid rgba(238, 238, 238, 0.3) !important; /* More transparent border */
}

/* Form styling */
.form-group {
    margin-bottom: 22px !important;
}

.form-label {
    font-weight: 500 !important;
    color: #444 !important;
    margin-bottom: 8px !important;
    font-size: 15px !important;
}

.form-control {
    border: 2px solid #e1e1e1 !important;
    border-radius: 8px !important;
    padding: 14px 16px !important;
    font-size: 16px !important;
    transition: all 0.3s !important;
    background: rgba(255, 255, 255, 0.5) !important; /* More transparency */
}

.form-control:focus {
    border-color: #FFC107 !important; /* Using existing accent color */
    box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.1) !important;
    background: white !important;
}

.btn-register {
    background: linear-gradient(120deg, rgba(45, 45, 45, 0.8), rgba(255, 193, 7, 0.8)) !important; /* More transparent button */
    border: none !important;
    color: white !important;
    padding: 14px !important;
    font-size: 17px !important;
    font-weight: 600 !important;
    border-radius: 8px !important;
    transition: all 0.3s !important;
    width: 100% !important;
    letter-spacing: 0.5px !important;
    text-transform: uppercase !important;
}

.btn-register:hover {
    background: linear-gradient(120deg, rgba(26, 26, 26, 0.95), rgba(230, 172, 0, 0.95)) !important; /* Slightly darker yellow with RGBA */
    transform: translateY(-2px) !important;
    box-shadow: 0 7px 14px rgba(0, 0, 0, 0.15) !important;
}

.alert {
    border-radius: 8px !important;
    padding: 15px 20px !important;
    margin-bottom: 25px !important;
    font-size: 15px !important;
}

.alert-error {
    background: #ffecec !important;
    border-left: 4px solid #ff4d4d !important;
    color: #c33 !important;
}

.alert-success {
    background: #e8f5e9 !important;
    border-left: 4px solid #4caf50 !important;
    color: #2e7d32 !important;
}

/* Link styling */
.card-footer a {
    color: #FFC107 !important; /* Using existing accent color */
    font-weight: 600 !important;
    text-decoration: none !important;
    transition: all 0.3s !important;
}

.card-footer a:hover {
    color: #e6ac00 !important; /* Slightly darker yellow */
    text-decoration: underline !important;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .register-card {
        margin: 15px !important;
        max-width: none !important;
    }
    
    .register-card-header {
        padding: 20px !important;
    }
    
    .register-card-body {
        padding: 20px !important;
    }
}

/* Form row for side-by-side fields */
.form-row {
    display: flex !important;
    gap: 15px !important;
}

.form-row .form-group {
    flex: 1 !important;
}

@media (max-width: 768px) {
    .form-row {
        flex-direction: column !important;
        gap: 0 !important;
    }
}
</style>

<div class="container register-background">
    <div class="main-content">
        <div class="grid">
            <div class="register-card">
                <div class="register-card-header">
                    <h2>Create Account</h2>
                    <p>Join our community and start renting quality tools today</p>
                </div>
                <div class="register-card-body">
                    <?php if ($error_message): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" id="registerForm">
                        <div class="form-row">
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
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="username">Username *</label>
                            <input type="text" id="username" name="username" class="form-control" 
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="email">Email Address *</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" class="form-control" 
                                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" 
                                       maxlength="10" pattern="[0-9]{10}" title="Please enter a 10-digit phone number">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="address">Address</label>
                                <input type="text" id="address" name="address" class="form-control" 
                                       value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="password">Password *</label>
                                <input type="password" id="password" name="password" class="form-control" required>
                                <small style="color: #666; font-size: 13px;">Minimum 6 characters</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="confirm_password">Confirm Password *</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-register">
                            <i class="fas fa-user-plus"></i> Create Account
                        </button>
                    </form>
                </div>
                <div class="register-card-footer">
                    <p>Already have an account? <a href="login.php">Sign in here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Ensure phone number is exactly 10 digits
document.getElementById('phone').addEventListener('input', function(e) {
    // Remove any non-digit characters
    let value = e.target.value.replace(/\D/g, '');
    // Limit to 10 digits
    if (value.length > 10) {
        value = value.substring(0, 10);
    }
    e.target.value = value;
});

// Form validation
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const phone = document.getElementById('phone').value;
    if (phone && phone.length !== 10) {
        alert('Please enter a valid 10-digit phone number.');
        e.preventDefault();
        return false;
    }
});
</script>

<?php include 'includes/footer.php'; ?>