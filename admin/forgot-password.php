<?php
/**
 * Forgot Password Page
 * 
 * Handles password reset via OTP:
 * 1. User enters email address
 * 2. OTP is generated and sent via email
 * 3. User enters OTP for verification
 * 4. On success, redirected to reset password
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

// OTP Configuration
define('OTP_LENGTH', 6);
define('OTP_EXPIRY_MINUTES', 15);
define('MAX_OTP_ATTEMPTS', 3);

// ============================================
// SMTP CONFIGURATION - UPDATE THESE VALUES!
// ============================================
define('SMTP_HOST', 'smtp.gmail.com');           // SMTP server
define('SMTP_USERNAME', 'henryboppeebensonjr137@gmail.com'); // Your email address
define('SMTP_PASSWORD', 'raqwzvxwamcubyyk');    // Gmail App Password (NOT your regular password)
define('SMTP_PORT', 587);                        // TLS port
define('SMTP_FROM_EMAIL', 'noreply@cmmi.org');
define('SMTP_FROM_NAME', 'CMMI Admin');

// Initialize variables
$error_message = '';
$success_message = '';
$step = 'email'; // Steps: 'email', 'otp', 'success'

// Check if we're in OTP verification step
if (isset($_SESSION['reset_email']) && isset($_SESSION['reset_otp_hash'])) {
    $step = 'otp';
}

// Generate OTP
function generateOTP($length = 6) {
    $otp = '';
    for ($i = 0; $i < $length; $i++) {
        $otp .= random_int(0, 9);
    }
    return $otp;
}

// Get OTP Email HTML Body
function getOTPEmailBody($otp, $username) {
    return "
    <html>
    <head>
        <title>Password Reset OTP</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #1a2b4a; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
            .otp-box { background: #1a2b4a; color: white; font-size: 32px; letter-spacing: 8px; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px; font-weight: bold; }
            .footer { background: #eee; padding: 15px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 8px 8px; }
            .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin-top: 15px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Password Reset Request</h1>
            </div>
            <div class='content'>
                <p>Hello <strong>{$username}</strong>,</p>
                <p>We received a request to reset your password for the CMMI Admin Panel. Use the following One-Time Password (OTP) to proceed:</p>
                
                <div class='otp-box'>{$otp}</div>
                
                <p><strong>This OTP will expire in " . OTP_EXPIRY_MINUTES . " minutes.</strong></p>
                
                <div class='warning'>
                    <strong>Security Notice:</strong> If you did not request this password reset, please ignore this email. Your password will remain unchanged.
                </div>
            </div>
            <div class='footer'>
                <p>This is an automated message from Christ Mission Ministries Inc.</p>
                <p>Please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

// Send OTP via email using PHPMailer
function sendOTPEmail($email, $otp, $username) {
    // Check if PHPMailer is available via Composer
    $phpmailerPath = __DIR__ . '/../vendor/autoload.php';
    
    if (file_exists($phpmailerPath)) {
        require_once $phpmailerPath;
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;
            
            // Recipients
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($email);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset OTP - CMMI Admin';
            $mail->Body    = getOTPEmailBody($otp, $username);
            $mail->AltBody = "Hello {$username}, Your OTP for password reset is: {$otp}. This code expires in " . OTP_EXPIRY_MINUTES . " minutes.";
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("PHPMailer Error: " . $mail->ErrorInfo);
            return false;
        }
    } else {
        // PHPMailer not installed - show helpful error
        error_log("PHPMailer not found. Install it via: composer require phpmailer/phpmailer");
        
        // For development: Return true and show OTP on screen (REMOVE IN PRODUCTION!)
        // This allows testing the flow without email
        return 'dev_mode';
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Step 1: Email submission
    if (isset($_POST['submit_email'])) {
        $email = trim($_POST['email']);
        
        if (empty($email)) {
            $error_message = 'Please enter your email address.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Please enter a valid email address.';
        } else {
            // Check if email exists in database
            try {
                $stmt = $pdo->prepare("SELECT id, username, email, is_active FROM admins WHERE email = ?");
                $stmt->execute([$email]);
                $admin = $stmt->fetch();
                
                if ($admin) {
                    // Check if account is active
                    if (!$admin['is_active']) {
                        $error_message = 'This account has been deactivated. Please contact the administrator.';
                    } else {
                        // Generate OTP
                        $otp = generateOTP(OTP_LENGTH);
                        $otp_hash = password_hash($otp, PASSWORD_DEFAULT);
                        $otp_expiry = time() + (OTP_EXPIRY_MINUTES * 60);
                        
                        // Store in session
                        $_SESSION['reset_email'] = $email;
                        $_SESSION['reset_admin_id'] = $admin['id'];
                        $_SESSION['reset_username'] = $admin['username'];
                        $_SESSION['reset_otp_hash'] = $otp_hash;
                        $_SESSION['reset_otp_expiry'] = $otp_expiry;
                        $_SESSION['reset_otp_attempts'] = 0;
                        
                        // Send OTP email
                        $email_result = sendOTPEmail($email, $otp, $admin['username']);
                        
                        if ($email_result === true) {
                            $step = 'otp';
                            $success_message = 'A verification code has been sent to your email address.';
                        } elseif ($email_result === 'dev_mode') {
                            // Development mode - show OTP on screen
                            $step = 'otp';
                            $success_message = 'A verification code has been sent to your email address.';
                            // DEVELOPMENT ONLY - Remove this line in production!
                            $success_message .= '<br><small style="color:#856404; background:#fff3cd; padding:5px 10px; border-radius:4px; display:inline-block; margin-top:10px;">[DEV MODE] Your OTP is: <strong>' . $otp . '</strong></small>';
                        } else {
                            $error_message = 'Failed to send verification email. Please try again or contact support.';
                        }
                    }
                } else {
                    // Don't reveal if email exists or not (security) - or be user-friendly
                    $error_message = 'No account found with this email address.';
                }
            } catch (PDOException $e) {
                $error_message = 'An error occurred. Please try again.';
                error_log("Database Error: " . $e->getMessage());
            }
        }
    }
    
    // Step 2: OTP verification
    if (isset($_POST['verify_otp'])) {
        $entered_otp = preg_replace('/\s+/', '', $_POST['otp'] ?? '');
        
        // Check if session data exists
        if (!isset($_SESSION['reset_otp_hash']) || !isset($_SESSION['reset_otp_expiry'])) {
            $error_message = 'Session expired. Please start again.';
            $step = 'email';
            // Clear session data
            unset($_SESSION['reset_email'], $_SESSION['reset_admin_id'], $_SESSION['reset_username'], 
                  $_SESSION['reset_otp_hash'], $_SESSION['reset_otp_expiry'], $_SESSION['reset_otp_attempts']);
        }
        // Check if OTP has expired
        elseif (time() > $_SESSION['reset_otp_expiry']) {
            $error_message = 'The verification code has expired. Please request a new one.';
            $step = 'email';
            // Clear session data
            unset($_SESSION['reset_email'], $_SESSION['reset_admin_id'], $_SESSION['reset_username'], 
                  $_SESSION['reset_otp_hash'], $_SESSION['reset_otp_expiry'], $_SESSION['reset_otp_attempts']);
        }
        // Check max attempts
        elseif ($_SESSION['reset_otp_attempts'] >= MAX_OTP_ATTEMPTS) {
            $error_message = 'Too many incorrect attempts. Please request a new verification code.';
            $step = 'email';
            // Clear session data
            unset($_SESSION['reset_email'], $_SESSION['reset_admin_id'], $_SESSION['reset_username'], 
                  $_SESSION['reset_otp_hash'], $_SESSION['reset_otp_expiry'], $_SESSION['reset_otp_attempts']);
        }
        // Validate OTP
        elseif (empty($entered_otp)) {
            $error_message = 'Please enter the verification code.';
            $step = 'otp';
        }
        elseif (strlen($entered_otp) !== OTP_LENGTH) {
            $error_message = 'Please enter a valid ' . OTP_LENGTH . '-digit code.';
            $_SESSION['reset_otp_attempts']++;
            $step = 'otp';
        }
        elseif (!password_verify($entered_otp, $_SESSION['reset_otp_hash'])) {
            $_SESSION['reset_otp_attempts']++;
            $remaining = MAX_OTP_ATTEMPTS - $_SESSION['reset_otp_attempts'];
            
            if ($remaining > 0) {
                $error_message = 'Invalid verification code. ' . $remaining . ' attempt(s) remaining.';
                $step = 'otp';
            } else {
                $error_message = 'Too many incorrect attempts. Please request a new verification code.';
                $step = 'email';
                // Clear session data
                unset($_SESSION['reset_email'], $_SESSION['reset_admin_id'], $_SESSION['reset_username'], 
                      $_SESSION['reset_otp_hash'], $_SESSION['reset_otp_expiry'], $_SESSION['reset_otp_attempts']);
            }
        }
        else {
            // OTP is valid - allow password reset
            $_SESSION['password_reset_verified'] = true;
            $_SESSION['password_reset_admin_id'] = $_SESSION['reset_admin_id'];
            $_SESSION['password_reset_email'] = $_SESSION['reset_email'];
            
            // Clear OTP session data
            unset($_SESSION['reset_email'], $_SESSION['reset_admin_id'], $_SESSION['reset_username'], 
                  $_SESSION['reset_otp_hash'], $_SESSION['reset_otp_expiry'], $_SESSION['reset_otp_attempts']);
            
            // Redirect to reset password page
            header("Location: reset-password.php");
            exit();
        }
    }
    
    // Resend OTP
    if (isset($_POST['resend_otp'])) {
        if (isset($_SESSION['reset_email']) && isset($_SESSION['reset_admin_id'])) {
            // Generate new OTP
            $otp = generateOTP(OTP_LENGTH);
            $otp_hash = password_hash($otp, PASSWORD_DEFAULT);
            $otp_expiry = time() + (OTP_EXPIRY_MINUTES * 60);
            
            // Update session
            $_SESSION['reset_otp_hash'] = $otp_hash;
            $_SESSION['reset_otp_expiry'] = $otp_expiry;
            $_SESSION['reset_otp_attempts'] = 0;
            
            // Send OTP email
            $email_result = sendOTPEmail($_SESSION['reset_email'], $otp, $_SESSION['reset_username']);
            
            if ($email_result === true) {
                $success_message = 'A new verification code has been sent to your email.';
            } elseif ($email_result === 'dev_mode') {
                $success_message = 'A new verification code has been sent to your email.';
                // DEVELOPMENT ONLY - Remove this line in production!
                $success_message .= '<br><small style="color:#856404; background:#fff3cd; padding:5px 10px; border-radius:4px; display:inline-block; margin-top:10px;">[DEV MODE] Your OTP is: <strong>' . $otp . '</strong></small>';
            } else {
                $error_message = 'Failed to send verification email. Please try again.';
            }
            $step = 'otp';
        } else {
            $error_message = 'Session expired. Please start again.';
            $step = 'email';
        }
    }
}

// Cancel reset
if (isset($_GET['cancel'])) {
    unset($_SESSION['reset_email'], $_SESSION['reset_admin_id'], $_SESSION['reset_username'], 
          $_SESSION['reset_otp_hash'], $_SESSION['reset_otp_expiry'], $_SESSION['reset_otp_attempts']);
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
    <title>Forgot Password | Christ Mission Ministries Inc</title>
    
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
        
        .alert-info {
            background: #e7f3ff;
            color: #0c5460;
            border: 1px solid #b8daff;
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
        
        .input-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #7a9cc6;
            font-size: 1.1rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 3rem;
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
        
        .form-help {
            margin-top: 0.5rem;
            font-size: 0.8rem;
            color: #666;
        }
        
        /* OTP Input */
        .otp-input-container {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin: 1.5rem 0;
        }
        
        .otp-input {
            width: 50px;
            height: 60px;
            border: 2px solid #e0e5eb;
            border-radius: 8px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .otp-input:focus {
            outline: none;
            border-color: #7a9cc6;
            box-shadow: 0 0 0 3px rgba(122, 156, 198, 0.15);
        }
        
        .otp-input.filled {
            border-color: #28a745;
            background: #f0fff4;
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
        
        .btn-secondary {
            background: #e0e5eb;
            color: #333;
            margin-top: 0.75rem;
        }
        
        .btn-secondary:hover {
            background: #d0d5db;
        }
        
        .btn-link {
            background: none;
            color: #7a9cc6;
            padding: 0.5rem;
            font-size: 0.9rem;
        }
        
        .btn-link:hover {
            text-decoration: underline;
            transform: none;
            box-shadow: none;
        }
        
        /* Timer */
        .otp-timer {
            text-align: center;
            margin: 1rem 0;
            font-size: 0.9rem;
            color: #666;
        }
        
        .otp-timer .time {
            font-weight: 600;
            color: #7a9cc6;
        }
        
        .otp-timer.expired .time {
            color: #dc3545;
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
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }
        
        .email-display strong {
            color: #333;
            font-size: 1rem;
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
        
        /* Responsive */
        @media (max-width: 480px) {
            .reset-header {
                padding: 1.5rem;
            }
            
            .reset-body {
                padding: 1.5rem;
            }
            
            .otp-input {
                width: 42px;
                height: 52px;
                font-size: 1.25rem;
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
            <div class="icon">
                <?php if ($step === 'email'): ?>
                    <i class="fas fa-envelope"></i>
                <?php else: ?>
                    <i class="fas fa-shield-alt"></i>
                <?php endif; ?>
            </div>
            <h1>
                <?php if ($step === 'email'): ?>
                    Forgot Password
                <?php else: ?>
                    Verify OTP
                <?php endif; ?>
            </h1>
            <p>
                <?php if ($step === 'email'): ?>
                    Enter your email to receive a verification code
                <?php else: ?>
                    Enter the code sent to your email
                <?php endif; ?>
            </p>
        </div>
        
        <!-- Body -->
        <div class="reset-body">
            <!-- Progress Steps -->
            <div class="progress-steps">
                <div class="step-indicator">
                    <div class="step-dot <?php echo $step === 'email' ? 'active' : 'completed'; ?>">
                        <?php echo $step === 'email' ? '1' : '<i class="fas fa-check"></i>'; ?>
                    </div>
                </div>
                <div class="step-line <?php echo $step !== 'email' ? 'completed' : ''; ?>"></div>
                <div class="step-indicator">
                    <div class="step-dot <?php echo $step === 'otp' ? 'active' : ''; ?>">2</div>
                </div>
                <div class="step-line"></div>
                <div class="step-indicator">
                    <div class="step-dot">3</div>
                </div>
            </div>
            
            <!-- Messages -->
            <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error_message; ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success_message; ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($step === 'email'): ?>
            <!-- Step 1: Email Entry -->
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               class="form-control" 
                               placeholder="Enter your registered email"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               required 
                               autofocus>
                    </div>
                    <p class="form-help">We'll send a verification code to this email.</p>
                </div>
                
                <button type="submit" name="submit_email" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Send Verification Code
                </button>
            </form>
            
            <?php else: ?>
            <!-- Step 2: OTP Verification -->
            <div class="email-display">
                <p>Code sent to:</p>
                <strong><?php echo htmlspecialchars($_SESSION['reset_email'] ?? ''); ?></strong>
            </div>
            
            <form method="POST" action="" id="otpForm">
                <input type="hidden" name="otp" id="otpHidden">
                
                <div class="otp-input-container">
                    <input type="text" class="otp-input" maxlength="1" data-index="0" inputmode="numeric" pattern="[0-9]" autofocus>
                    <input type="text" class="otp-input" maxlength="1" data-index="1" inputmode="numeric" pattern="[0-9]">
                    <input type="text" class="otp-input" maxlength="1" data-index="2" inputmode="numeric" pattern="[0-9]">
                    <input type="text" class="otp-input" maxlength="1" data-index="3" inputmode="numeric" pattern="[0-9]">
                    <input type="text" class="otp-input" maxlength="1" data-index="4" inputmode="numeric" pattern="[0-9]">
                    <input type="text" class="otp-input" maxlength="1" data-index="5" inputmode="numeric" pattern="[0-9]">
                </div>
                
                <div class="otp-timer" id="otpTimer">
                    Code expires in: <span class="time" id="timerDisplay">--:--</span>
                </div>
                
                <button type="submit" name="verify_otp" id="verifyBtn" class="btn btn-primary">
                    <i class="fas fa-check-circle"></i> Verify Code
                </button>
            </form>
            
            <!-- Resend OTP -->
            <form method="POST" action="" style="margin-top: 1rem;">
                <button type="submit" name="resend_otp" class="btn btn-link" id="resendBtn">
                    <i class="fas fa-redo"></i> Resend Code
                </button>
            </form>
            
            <?php endif; ?>
            
            <!-- Footer -->
            <div class="reset-footer">
                <a href="?cancel=1">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            </div>
        </div>
    </div>
    
    <?php if ($step === 'otp'): ?>
    <script>
        // OTP Input Handling - NO AUTO-SUBMIT
        const otpInputs = document.querySelectorAll('.otp-input');
        const otpHidden = document.getElementById('otpHidden');
        const otpForm = document.getElementById('otpForm');
        
        // Update hidden input with all OTP values
        function updateHiddenInput() {
            let otp = '';
            otpInputs.forEach(input => {
                otp += input.value;
            });
            otpHidden.value = otp;
            
            // Add visual feedback for filled inputs
            otpInputs.forEach(input => {
                if (input.value) {
                    input.classList.add('filled');
                } else {
                    input.classList.remove('filled');
                }
            });
        }
        
        otpInputs.forEach((input, index) => {
            // Handle input
            input.addEventListener('input', (e) => {
                const value = e.target.value;
                
                // Only allow numbers
                if (!/^\d*$/.test(value)) {
                    e.target.value = '';
                    return;
                }
                
                // Move to next input if value entered
                if (value && index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }
                
                updateHiddenInput();
                
                // NO AUTO-SUBMIT - User must click the Verify button
            });
            
            // Handle backspace
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && index > 0) {
                    otpInputs[index - 1].focus();
                }
                
                // Allow Enter key to submit form only if all digits entered
                if (e.key === 'Enter') {
                    e.preventDefault();
                    updateHiddenInput();
                    if (otpHidden.value.length === 6) {
                        otpForm.submit();
                    }
                }
            });
            
            // Handle paste
            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const pasteData = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
                
                pasteData.split('').forEach((char, i) => {
                    if (otpInputs[i]) {
                        otpInputs[i].value = char;
                    }
                });
                
                updateHiddenInput();
                
                // Focus the last filled input
                const nextIndex = Math.min(pasteData.length, otpInputs.length - 1);
                otpInputs[nextIndex].focus();
                
                // NO AUTO-SUBMIT after paste
            });
            
            // Select all on focus
            input.addEventListener('focus', (e) => {
                e.target.select();
            });
        });
        
        // Form submit handler - ensure hidden input is updated
        otpForm.addEventListener('submit', (e) => {
            updateHiddenInput();
            
            // Validate that all 6 digits are entered
            if (otpHidden.value.length !== 6) {
                e.preventDefault();
                alert('Please enter all 6 digits of the verification code.');
                return false;
            }
        });
        
        // Timer
        const expiryTime = <?php echo isset($_SESSION['reset_otp_expiry']) ? $_SESSION['reset_otp_expiry'] : 0; ?>;
        const timerDisplay = document.getElementById('timerDisplay');
        const timerContainer = document.getElementById('otpTimer');
        
        function updateTimer() {
            const now = Math.floor(Date.now() / 1000);
            const remaining = expiryTime - now;
            
            if (remaining <= 0) {
                timerDisplay.textContent = 'Expired';
                timerContainer.classList.add('expired');
                return;
            }
            
            const minutes = Math.floor(remaining / 60);
            const seconds = remaining % 60;
            timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            setTimeout(updateTimer, 1000);
        }
        
        updateTimer();
    </script>
    <?php endif; ?>
</body>
</html>
