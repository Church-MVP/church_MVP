<?php
/**
 * Admin Login Page
 * 
 * Handles admin authentication
 * Supports login with either USERNAME or EMAIL
 */

// Start session
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard.php");
    exit();
}

// Include database connection
require_once '../includes/db.php';

// Initialize variables
$error_message = '';
$success_message = '';

// Check for timeout message
if (isset($_GET['timeout'])) {
    $error_message = 'Your session has expired. Please login again.';
}

// Check for logout message
if (isset($_GET['logout'])) {
    $success_message = 'You have been successfully logged out.';
}

// Check for password reset success message
if (isset($_GET['password_reset'])) {
    $success_message = 'Your password has been reset successfully. Please login with your new password.';
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login_identifier = sanitize_input($_POST['login_identifier']); // Can be username OR email
    $password = $_POST['password']; // Don't sanitize password (may contain special chars)
    
    // Validation
    if (empty($login_identifier) || empty($password)) {
        $error_message = 'Please enter your username/email and password.';
    } else {
        try {
            // Check if input is an email or username
            $is_email = filter_var($login_identifier, FILTER_VALIDATE_EMAIL);
            
            // Query database - search by email OR username
            if ($is_email) {
                // Login with email
                $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
            } else {
                // Login with username
                $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
            }
            $stmt->execute([$login_identifier]);
            $admin = $stmt->fetch();
            
            // If not found by primary method, try the other
            if (!$admin) {
                if ($is_email) {
                    // Email not found, try as username (in case username looks like email)
                    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
                } else {
                    // Username not found, try as email
                    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
                }
                $stmt->execute([$login_identifier]);
                $admin = $stmt->fetch();
            }
            
            // Verify password
            if ($admin && password_verify($password, $admin['password'])) {
                // Check if account is active
                if (isset($admin['is_active']) && !$admin['is_active']) {
                    $error_message = 'Your account has been deactivated. Please contact the administrator.';
                } else {
                    // Password is correct - create session
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['admin_role'] = $admin['role'] ?? 'viewer';
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['last_activity'] = time();
                    
                    // Update last login time
                    try {
                        $stmt = $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
                        $stmt->execute([$admin['id']]);
                    } catch (PDOException $e) {
                        // Ignore error, non-critical
                    }
                    
                    // Redirect to dashboard
                    header("Location: dashboard.php");
                    exit();
                }
            } else {
                $error_message = 'Invalid username/email or password.';
            }
        } catch (PDOException $e) {
            $error_message = 'An error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Login | Christ Mission Ministries Inc</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Open Sans', sans-serif;
            background: linear-gradient(135deg, #1a2b4a 0%, #7a9cc6 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .login-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
        }
        
        .login-header {
            background: #1a2b4a;
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
        }
        
        .login-header img {
            height: 80px;
            width: auto;
            margin-bottom: 1rem;
        }
        
        .login-header h1 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .login-header p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.95rem;
        }
        
        .login-body {
            padding: 2.5rem 2rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
            font-size: 0.95rem;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #7a9cc6;
            font-size: 1.1rem;
            pointer-events: none;
            transition: color 0.3s ease;
        }
        
        .form-control {
            width: 100%;
            padding: 0.875rem 3rem 0.875rem 3rem;
            border: 2px solid #e0e5eb;
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #7a9cc6;
            box-shadow: 0 0 0 3px rgba(122, 156, 198, 0.1);
        }
        
        .form-control:focus + .input-icon,
        .form-control:not(:placeholder-shown) + .input-icon {
            color: #1a2b4a;
        }
        
        .form-hint {
            display: block;
            margin-top: 0.375rem;
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        /* Password Toggle Button */
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #7a9cc6;
            font-size: 1.1rem;
            cursor: pointer;
            padding: 0.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.3s ease;
            z-index: 2;
        }
        
        .password-toggle:hover {
            color: #1a2b4a;
        }
        
        .password-toggle:focus {
            outline: none;
            color: #1a2b4a;
        }
        
        .form-options {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 1.5rem;
        }
        
        .forgot-password-link {
            color: #7a9cc6;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }
        
        .forgot-password-link:hover {
            color: #5a7db0;
            text-decoration: underline;
        }
        
        .btn-login {
            width: 100%;
            padding: 1rem;
            background: #7a9cc6;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-login:hover {
            background: #6688b3;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(122, 156, 198, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e0e5eb;
        }
        
        .login-footer a {
            color: #7a9cc6;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        /* Dynamic icon change based on input */
        .input-icon-dynamic {
            transition: all 0.3s ease;
        }
        
        @media (max-width: 480px) {
            .login-header {
                padding: 2rem 1.5rem;
            }
            
            .login-body {
                padding: 2rem 1.5rem;
            }
            
            .login-header h1 {
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Login Header -->
        <div class="login-header">
            <img src="../assets/images/logo.jpeg" alt="Christ Mission Ministries Inc">
            <h1>Admin Panel</h1>
            <p>Christ Mission Ministries Inc</p>
        </div>
        
        <!-- Login Form -->
        <div class="login-body">
            <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <!-- Username or Email -->
                <div class="form-group">
                    <label for="login_identifier">Username or Email</label>
                    <div class="input-group">
                        <input type="text" 
                               id="login_identifier" 
                               name="login_identifier" 
                               class="form-control" 
                               placeholder="Enter username or email"
                               value="<?php echo isset($_POST['login_identifier']) ? htmlspecialchars($_POST['login_identifier']) : ''; ?>"
                               required 
                               autofocus
                               autocomplete="username">
                        <i class="fas fa-user input-icon input-icon-dynamic" id="loginIcon"></i>
                    </div>
                    <span class="form-hint">
                        <i class="fas fa-info-circle"></i> You can use either your username or email address
                    </span>
                </div>
                
                <!-- Password -->
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-group">
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-control" 
                               placeholder="Enter your password"
                               required
                               autocomplete="current-password">
                        <i class="fas fa-lock input-icon"></i>
                        <button type="button" 
                                class="password-toggle" 
                                id="passwordToggle"
                                aria-label="Toggle password visibility"
                                tabindex="-1">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Forgot Password Link -->
                <div class="form-options">
                    <a href="forgot-password.php" class="forgot-password-link">
                        <i class="fas fa-key"></i> Forgot Password?
                    </a>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login to Dashboard
                </button>
            </form>
            
            <!-- Login Footer -->
            <div class="login-footer">
                <a href="../index.php">
                    <i class="fas fa-arrow-left"></i> Back to Website
                </a>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginInput = document.getElementById('login_identifier');
            const loginIcon = document.getElementById('loginIcon');
            const passwordInput = document.getElementById('password');
            const toggleButton = document.getElementById('passwordToggle');
            const toggleIcon = document.getElementById('toggleIcon');
            
            // Dynamic icon change based on input type (email vs username)
            loginInput.addEventListener('input', function() {
                const value = this.value;
                const isEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
                
                if (isEmail) {
                    loginIcon.classList.remove('fa-user');
                    loginIcon.classList.add('fa-envelope');
                } else {
                    loginIcon.classList.remove('fa-envelope');
                    loginIcon.classList.add('fa-user');
                }
            });
            
            // Password Visibility Toggle
            toggleButton.addEventListener('click', function() {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    toggleIcon.classList.remove('fa-eye');
                    toggleIcon.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    toggleIcon.classList.remove('fa-eye-slash');
                    toggleIcon.classList.add('fa-eye');
                }
                passwordInput.focus();
            });
            
            // Prevent form submission when clicking toggle
            toggleButton.addEventListener('mousedown', function(e) {
                e.preventDefault();
            });
            
            // Trigger icon check on page load (for autofill)
            if (loginInput.value) {
                loginInput.dispatchEvent(new Event('input'));
            }
        });
    </script>
</body>
</html>