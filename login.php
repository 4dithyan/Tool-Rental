<?php
require_once 'includes/db_connect.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error_message = "Please enter both username and password.";
    } else {
        // Check user credentials
        $query = "SELECT user_id, username, password_hash, first_name, last_name, role FROM users WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password_hash'])) {
                // Login successful
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['role'] = $user['role'];
                
                // Redirect based on role
                if ($user['role'] == 'admin') {
                    header('Location: admin/admin_dashboard.php');
                } else {
                    header('Location: index.php');
                }
                exit();
            } else {
                $error_message = "Invalid password.";
            }
        } else {
            $error_message = "User not found.";
        }
    }
}

include 'includes/header.php';
?>

<!-- Styling for login page with content moved right and scroll removed -->
<style>
.login-background {
    background-image: url("/PROJECTS/Mine/assets/images/login-bg.jpg?v=<?php echo time(); ?>") !important;
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
.login-background {
    background-image: url("/PROJECTS/Mine/assets/images/login-bg.jpg?v=<?php echo time(); ?>"), linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #f5576c) !important;
}

/* Reduce overlay darkness for better visibility */
.login-background::before {
    content: '' !important;
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    background: rgba(0, 0, 0, 0.1) !important; /* Even lighter overlay */
    z-index: 1 !important;
}

.login-background .main-content {
    position: relative !important;
    z-index: 2 !important;
}

/* Ensure the container takes full width */
.container.login-background {
    max-width: 100% !important;
    padding: 0 !important;
    margin: 0 !important;
}

/* Move content slightly to the right */
.login-content-wrapper {
    max-width: 400px !important;
    margin: 0 auto !important;
    margin-left: auto !important;
    margin-right: 25% !important; /* Move content to the right */
    padding: 20px !important;
}

/* Professional card styling with existing color scheme */
.login-card {
    background: rgba(255, 255, 255, 0.5) !important; /* More transparency */
    backdrop-filter: blur(5px) !important; /* Reduced blur for clarity */
    border: none !important;
    border-radius: 12px !important;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important; /* Lighter shadow */
    overflow: hidden !important;
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

.login-card-header {
    background: linear-gradient(120deg, rgba(45, 45, 45, 0.7), rgba(255, 193, 7, 0.7)) !important; /* More transparent header */
    color: white !important;
    padding: 25px 30px !important;
    text-align: center !important;
    border-bottom: none !important;
}

.login-card-header h2 {
    font-weight: 600 !important;
    margin-bottom: 8px !important;
    font-size: 28px !important;
}

.login-card-header p {
    color: #e0e0e0 !important;
    font-size: 16px !important;
    margin: 0 !important;
}

.login-card-body {
    padding: 30px !important;
}

.login-card-footer {
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

.btn-login {
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

.btn-login:hover {
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
    .login-content-wrapper {
        margin-right: auto !important;
        padding: 15px !important;
    }
    
    .login-card-header {
        padding: 20px !important;
    }
    
    .login-card-body {
        padding: 20px !important;
    }
}
</style>

<div class="container login-background">
    <div class="main-content">
        <div class="grid">
            <div class="login-content-wrapper">
                <div class="login-card">
                    <div class="login-card-header">
                        <h2>Welcome Back</h2>
                        <p>Sign in to access your account</p>
                    </div>
                    <div class="login-card-body">
                        <?php if ($error_message): ?>
                            <div class="alert alert-error">
                                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" id="loginForm">
                            <div class="form-group">
                                <label class="form-label" for="username">Username or Email</label>
                                <input type="text" id="username" name="username" class="form-control" 
                                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="password">Password</label>
                                <input type="password" id="password" name="password" class="form-control" required>
                            </div>
                            
                            <button type="submit" class="btn-login">
                                <i class="fas fa-sign-in-alt"></i> Sign In
                            </button>
                        </form>
                    </div>
                    <div class="login-card-footer">
                        <p>Don't have an account? <a href="register.php">Create one here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>