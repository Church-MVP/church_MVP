<?php
/**
 * Reset Password Page
 * 
 * Allows users to set a new password after OTP verification.
 * This page is only accessible after successful OTP verification
 * from the forgot-password.php flow.
 * 
 * Flow: forgot-password.php (OTP) → reset-password.php (new password) → login.php
 */

// Start session
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard.php");
    exit();
}

// Check if user has verified OTP
if (!isset($_SESSION['password_reset_verified']) || $_SESSION['password_reset_verified'] !== true) {
    // Not verified - redirect to forgot password
    $_SESSION['error_message'] = 'Please complete the verification process first.';
    header("Location: forgot-password.php");
    exit();
}

// Include database connection
require_once '../includes/db.php';

// Get admin ID and email from session
$admin_id = $_SESSION['password_reset_admin_id'] ?? null;
$admin_email = $_SESSION['password_reset_email'] ?? null;

if (!$admin_id || !$admin_email) {
    // Missing session data - redirect to forgot password
    unset($_SESSION['password_reset_verified'], $_SESSION['password_reset_admin_id'], $_SESSION['password_reset_email']);
    $_SESSION['error_message'] = 'Session expired. Please start the password reset process again.';
    header("Location: forgot-password.php");
    exit();
}

// Determine which table to use
$table_name = 'admins';
try {
    $test = $pdo->query("SELECT 1 FROM admins LIMIT 1");
} catch (PDOException $e) {
    try {
        $test = $pdo->query("SELECT 1 FROM admin_users LIMIT 1");
        $table_name = 'admin_users';
    } catch (PDOException $e2) {
        $error_message = 'Database error. Please try again later.';
    }
}

// Verify admin still exists and is active
try {
    $stmt = $pdo->prepare("SELECT id, username, email, is_active FROM {$table_name} WHERE id = ? AND email = ?");
    $stmt->execute([$admin_id, $admin_email]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        unset($_SESSION['password_reset_verified'], $_SESSION['password_reset_admin_id'], $_SESSION['password_reset_email']);
        $_SESSION['error_message'] = 'Account not found. Please try again.';
        header("Location: forgot-password.php");
        exit();
    }
    
    if (!$admin['is_active']) {
        unset($_SESSION['password_reset_verified'], $_SESSION['password_reset_admin_id'], $_SESSION['password_reset_email']);
        $_SESSION['error_message'] = 'This account has been deactivated. Please contact the administrator.';
        header("Location: forgot-password.php");
        exit();
    }
} catch (PDOException $e) {
    $error_message = 'Database error. Please try again.';
}

// Initialize variables
$error_message = $error_message ?? '';
$success_message = '';
$password_updated = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    $errors = [];
    
    if (empty($new_password)) {
        $errors[] = 'New password is required.';
    } elseif (strlen($new_password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    } elseif (!preg_match('/[A-Z]/', $new_password)) {
        $errors[] = 'Password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[a-z]/', $new_password)) {
        $errors[] = 'Password must contain at least one lowercase letter.';
    } elseif (!preg_match('/[0-9]/', $new_password)) {
        $errors[] = 'Password must contain at least one number.';
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    
    if (empty($errors)) {
        try {
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Determine password field name
            $password_field = 'password';
            
            // Update password in database
            $sql = "UPDATE {$table_name} SET {$password_field} = ?";
            
            // Check if updated_at column exists
            try {
                $check = $pdo->query("SHOW COLUMNS FROM {$table_name} LIKE 'updated_at'");
                if ($check->rowCount() > 0) {
                    $sql .= ", updated_at = NOW()";
                }
            } catch (PDOException $e) {
                // Column doesn't exist, continue without it
            }
            
            $sql .= " WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$hashed_password, $admin_id]);
            
            // Clear all reset session data
            unset($_SESSION['password_reset_verified'], $_SESSION['password_reset_admin_id'], $_SESSION['password_reset_email']);
            
            // Set success flag
            $password_updated = true;
            $success_message = 'Your password has been reset successfully!';
            
        } catch (PDOException $e) {
            $error_message = 'Failed to update password. Please try again.';
            error_log("Password Reset Error: " . $e->getMessage());
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}

// Cancel reset
if (isset($_GET['cancel'])) {
    unset($_SESSION['password_reset_verified'], $_SESSION['password_reset_admin_id'], $_SESSION['password_reset_email']);
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Reset Password | Christ Mission Ministries Inc</title>
    
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
        
        .reset-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
        }
        
        .reset-header {
            background: #1a2b4a;
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .reset-header .icon {
            width: 70px;
            height: 70px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.75rem;
        }
        
        .reset-header .icon.success {
            background: rgba(40, 167, 69, 0.2);
        }
        
        .reset-header h1 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .reset-header p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
        }
        
        .reset-body {
            padding: 2rem;
        }
        
        /* Progress Steps */
        .progress-steps {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
            gap: 0.5rem;
        }
        
        .step-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .step-dot {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e0e5eb;
            color: #999;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .step-dot.active {
            background: #7a9cc6;
            color: white;
        }
        
        .step-dot.completed {
            background: #28a745;
            color: white;
        }
        
        .step-line {
            width: 40px;
            height: 3px;
            background: #e0e5eb;
            border-radius: 2px;
        }
        
        .step-line.completed {
            background: #28a745;
        }
        
        /* Alerts */
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }
        
        .alert i {
            margin-top: 2px;
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
        
        /* Form Elements */
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
        
        .input-group i.input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #7a9cc6;
            font-size: 1.1rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.875rem 3rem;
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
        
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            padding: 0.25rem;
        }
        
        .password-toggle:hover {
            color: #7a9cc6;
        }
        
        /* Password Strength */
        .password-strength {
            margin-top: 0.75rem;
        }
        
        .strength-bar {
            height: 6px;
            background: #e0e5eb;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }
        
        .strength-fill {
            height: 100%;
            width: 0;
            transition: all 0.3s ease;
            border-radius: 3px;
        }
        
        .strength-fill.weak { width: 25%; background: #dc3545; }
        .strength-fill.fair { width: 50%; background: #ffc107; }
        .strength-fill.good { width: 75%; background: #17a2b8; }
        .strength-fill.strong { width: 100%; background: #28a745; }
        
        .strength-text {
            font-size: 0.8rem;
            color: #666;
        }
        
        /* Password Requirements */
        .password-requirements {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 6px;
        }
        
        .password-requirements p {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .password-requirements ul {
            list-style: none;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
        }
        
        .password-requirements li {
            font-size: 0.8rem;
            color: #999;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .password-requirements li i {
            font-size: 0.6rem;
        }
        
        .password-requirements li.valid {
            color: #28a745;
        }
        
        .password-requirements li.valid i:before {
            content: "\f00c";
        }
        
        /* Password Match Indicator */
        .password-match {
            margin-top: 0.5rem;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .password-match.match {
            color: #28a745;
        }
        
        .password-match.no-match {
            color: #dc3545;
        }
        
        /* Buttons */
        .btn {
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        
        .btn-primary {
            background: #7a9cc6;
            color: white;
        }
        
        .btn-primary:hover {
            background: #6688b3;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(122, 156, 198, 0.3);
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.3);
        }
        
        /* Success State */
        .success-container {
            text-align: center;
            padding: 1rem 0;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background: #d4edda;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.5rem;
            color: #28a745;
        }
        
        .success-container h2 {
            color: #28a745;
            margin-bottom: 0.75rem;
        }
        
        .success-container p {
            color: #666;
            margin-bottom: 1.5rem;
        }
        
        /* Footer */
        .reset-footer {
            text-align: center;
            padding-top: 1.5rem;
            border-top: 1px solid #e0e5eb;
        }
        
        .reset-footer a {
            color: #7a9cc6;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .reset-footer a:hover {
            text-decoration: underline;
        }
        
        /* Email Display */
        .email-display {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 6px;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .email-display p {
            color: #666;
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
        }
        
        .email-display strong {
            color: #333;
            font-size: 1rem;
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .reset-header {
                padding: 1.5rem;
            }
            
            .reset-body {
                padding: 1.5rem;
            }
            
            .password-requirements ul {
                grid-template-columns: 1fr;
            }
            
            .step-line {
                width: 25px;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <!-- Header -->
        <div class="reset-header">
            <div class="icon <?php echo $password_updated ? 'success' : ''; ?>">
                <?php if ($password_updated): ?>
                    <i class="fas fa-check"></i>
                <?php else: ?>
                    <i class="fas fa-key"></i>
                <?php endif; ?>
            </div>
            <h1>
                <?php if ($password_updated): ?>
                    Password Reset Complete
                <?php else: ?>
                    Create New Password
                <?php endif; ?>
            </h1>
            <p>
                <?php if ($password_updated): ?>
                    Your password has been updated successfully
                <?php else: ?>
                    Enter your new password below
                <?php endif; ?>
            </p>
        </div>
        
        <!-- Body -->
        <div class="reset-body">
            <!-- Progress Steps -->
            <div class="progress-steps">
                <div class="step-indicator">
                    <div class="step-dot completed">
                        <i class="fas fa-check"></i>
                    </div>
                </div>
                <div class="step-line completed"></div>
                <div class="step-indicator">
                    <div class="step-dot completed">
                        <i class="fas fa-check"></i>
                    </div>
                </div>
                <div class="step-line <?php echo $password_updated ? 'completed' : ''; ?>"></div>
                <div class="step-indicator">
                    <div class="step-dot <?php echo $password_updated ? 'completed' : 'active'; ?>">
                        <?php echo $password_updated ? '<i class="fas fa-check"></i>' : '3'; ?>
                    </div>
                </div>
            </div>
            
            <?php if ($password_updated): ?>
            <!-- Success State -->
            <div class="success-container">
                <div class="success-icon">
                    <i class="fas fa-check"></i>
                </div>
                <h2>Success!</h2>
                <p>Your password has been reset successfully. You can now log in with your new password.</p>
                <a href="login.php" class="btn btn-success">
                    <i class="fas fa-sign-in-alt"></i> Go to Login
                </a>
            </div>
            
            <?php else: ?>
            <!-- Messages -->
            <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error_message; ?></span>
            </div>
            <?php endif; ?>
            
            <!-- Account Info -->
            <div class="email-display">
                <p>Resetting password for:</p>
                <strong><?php echo htmlspecialchars($admin_email); ?></strong>
            </div>
            
            <!-- Password Reset Form -->
            <form method="POST" action="" id="resetForm">
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock input-icon"></i>
                        <input 
                            type="password" 
                            id="new_password" 
                            name="new_password" 
                            class="form-control" 
                            placeholder="Enter new password"
                            required
                            minlength="8"
                            onkeyup="checkPasswordStrength(this.value)"
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    
                    <!-- Password Strength Indicator -->
                    <div class="password-strength">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                        <span class="strength-text" id="strengthText">Password strength</span>
                    </div>
                    
                    <!-- Password Requirements -->
                    <div class="password-requirements">
                        <p><i class="fas fa-info-circle"></i> Password must contain:</p>
                        <ul>
                            <li id="req-length"><i class="fas fa-circle"></i> At least 8 characters</li>
                            <li id="req-upper"><i class="fas fa-circle"></i> One uppercase letter</li>
                            <li id="req-lower"><i class="fas fa-circle"></i> One lowercase letter</li>
                            <li id="req-number"><i class="fas fa-circle"></i> One number</li>
                        </ul>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock input-icon"></i>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            class="form-control" 
                            placeholder="Confirm new password"
                            required
                            onkeyup="checkPasswordMatch()"
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-match" id="passwordMatch"></div>
                </div>
                
                <button type="submit" name="reset_password" class="btn btn-primary" id="submitBtn">
                    <i class="fas fa-save"></i> Reset Password
                </button>
            </form>
            
            <!-- Footer -->
            <div class="reset-footer">
                <a href="?cancel=1">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (!$password_updated): ?>
    <script>
        // Toggle password visibility
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const button = input.nextElementSibling;
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Check password strength
        function checkPasswordStrength(password) {
            const fill = document.getElementById('strengthFill');
            const text = document.getElementById('strengthText');
            
            let strength = 0;
            
            // Length check
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            
            // Character type checks
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            // Update requirements
            document.getElementById('req-length').classList.toggle('valid', password.length >= 8);
            document.getElementById('req-upper').classList.toggle('valid', /[A-Z]/.test(password));
            document.getElementById('req-lower').classList.toggle('valid', /[a-z]/.test(password));
            document.getElementById('req-number').classList.toggle('valid', /[0-9]/.test(password));
            
            // Update strength indicator
            fill.className = 'strength-fill';
            if (password.length === 0) {
                text.textContent = 'Password strength';
            } else if (strength < 3) {
                fill.classList.add('weak');
                text.textContent = 'Weak';
            } else if (strength < 4) {
                fill.classList.add('fair');
                text.textContent = 'Fair';
            } else if (strength < 5) {
                fill.classList.add('good');
                text.textContent = 'Good';
            } else {
                fill.classList.add('strong');
                text.textContent = 'Strong';
            }
            
            // Also check password match if confirm field has value
            checkPasswordMatch();
        }
        
        // Check if passwords match
        function checkPasswordMatch() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (confirmPassword.length === 0) {
                matchDiv.innerHTML = '';
                matchDiv.className = 'password-match';
            } else if (newPassword === confirmPassword) {
                matchDiv.innerHTML = '<i class="fas fa-check-circle"></i> Passwords match';
                matchDiv.className = 'password-match match';
            } else {
                matchDiv.innerHTML = '<i class="fas fa-times-circle"></i> Passwords do not match';
                matchDiv.className = 'password-match no-match';
            }
        }
        
        // Form validation before submit
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            // Check all requirements
            if (newPassword.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long.');
                return false;
            }
            
            if (!/[A-Z]/.test(newPassword)) {
                e.preventDefault();
                alert('Password must contain at least one uppercase letter.');
                return false;
            }
            
            if (!/[a-z]/.test(newPassword)) {
                e.preventDefault();
                alert('Password must contain at least one lowercase letter.');
                return false;
            }
            
            if (!/[0-9]/.test(newPassword)) {
                e.preventDefault();
                alert('Password must contain at least one number.');
                return false;
            }
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match.');
                return false;
            }
            
            return true;
        });
    </script>
    <?php endif; ?>
</body>
</html>