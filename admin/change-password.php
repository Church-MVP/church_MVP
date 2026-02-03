<?php
/**
 * Change Password & Two-Factor Authentication
 * 
 * Allows admin users to:
 * - Change their password
 * - Enable/Disable 2FA
 * - View 2FA recovery codes
 */

// Set page title
$page_title = 'Security Settings';

// Include admin header
include 'includes/admin-header.php';

// Determine which table to use
$table_name = 'admin_users';
try {
    $test = $pdo->query("SELECT 1 FROM admin_users LIMIT 1");
} catch (PDOException $e) {
    try {
        $test = $pdo->query("SELECT 1 FROM admins LIMIT 1");
        $table_name = 'admins';
    } catch (PDOException $e2) {
        $_SESSION['error_message'] = 'Unable to access user data. Please contact administrator.';
        header('Location: dashboard.php');
        exit();
    }
}

// Check if 2FA columns exist, if not we'll need to inform the user
$has_2fa_columns = false;
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM {$table_name} LIKE 'two_factor_secret'");
    $has_2fa_columns = $stmt->rowCount() > 0;
} catch (PDOException $e) {
    $has_2fa_columns = false;
}

// Fetch current user's data
$stmt = $pdo->prepare("SELECT * FROM {$table_name} WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['error_message'] = 'User not found.';
    header('Location: dashboard.php');
    exit();
}

// Get username
$username = $user['username'] ?? $user['name'] ?? $user['admin_name'] ?? 'User';

// Check current 2FA status
$two_factor_enabled = false;
$two_factor_secret = null;
if ($has_2fa_columns) {
    $two_factor_enabled = !empty($user['two_factor_enabled']) && $user['two_factor_enabled'] == 1;
    $two_factor_secret = $user['two_factor_secret'] ?? null;
}

// Simple TOTP implementation (no external library needed)
class SimpleTOTP {
    private static $base32_chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    
    // Generate a random secret
    public static function generateSecret($length = 16) {
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::$base32_chars[random_int(0, 31)];
        }
        return $secret;
    }
    
    // Base32 decode
    private static function base32Decode($secret) {
        $secret = strtoupper($secret);
        $buffer = 0;
        $bitsLeft = 0;
        $result = '';
        
        for ($i = 0; $i < strlen($secret); $i++) {
            $char = $secret[$i];
            if ($char === '=' || $char === ' ') continue;
            
            $pos = strpos(self::$base32_chars, $char);
            if ($pos === false) continue;
            
            $buffer = ($buffer << 5) | $pos;
            $bitsLeft += 5;
            
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $result .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }
        
        return $result;
    }
    
    // Generate TOTP code
    public static function getCode($secret, $timeSlice = null) {
        if ($timeSlice === null) {
            $timeSlice = floor(time() / 30);
        }
        
        $secretKey = self::base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $time, $secretKey, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $code = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % 1000000;
        
        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }
    
    // Verify TOTP code (with time drift tolerance)
    public static function verifyCode($secret, $code, $discrepancy = 1) {
        $currentTimeSlice = floor(time() / 30);
        
        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            if (self::getCode($secret, $currentTimeSlice + $i) === $code) {
                return true;
            }
        }
        
        return false;
    }
    
    // Generate QR code URL for Google Charts API
    public static function getQRCodeUrl($name, $secret, $issuer = 'ChurchAdmin') {
        $otpauth = 'otpauth://totp/' . urlencode($issuer . ':' . $name) . '?secret=' . $secret . '&issuer=' . urlencode($issuer);
        return 'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=' . urlencode($otpauth);
    }
    
    // Generate recovery codes
    public static function generateRecoveryCodes($count = 8) {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4))) . '-' . strtoupper(bin2hex(random_bytes(4)));
        }
        return $codes;
    }
}

$errors = [];
$success_message = '';
$show_2fa_setup = false;
$new_2fa_secret = null;
$qr_code_url = null;
$recovery_codes = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Handle Password Change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate current password
        if (empty($current_password)) {
            $errors[] = 'Current password is required.';
        } else {
            $password_field = isset($user['password']) ? 'password' : (isset($user['admin_password']) ? 'admin_password' : 'password');
            if (!password_verify($current_password, $user[$password_field])) {
                $errors[] = 'Current password is incorrect.';
            }
        }
        
        // Validate new password
        if (empty($new_password)) {
            $errors[] = 'New password is required.';
        } elseif (strlen($new_password) < 8) {
            $errors[] = 'New password must be at least 8 characters long.';
        } elseif (!preg_match('/[A-Z]/', $new_password)) {
            $errors[] = 'New password must contain at least one uppercase letter.';
        } elseif (!preg_match('/[a-z]/', $new_password)) {
            $errors[] = 'New password must contain at least one lowercase letter.';
        } elseif (!preg_match('/[0-9]/', $new_password)) {
            $errors[] = 'New password must contain at least one number.';
        }
        
        // Confirm password match
        if ($new_password !== $confirm_password) {
            $errors[] = 'New passwords do not match.';
        }
        
        // Update password if no errors
        if (empty($errors)) {
            try {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $password_field = isset($user['password']) ? 'password' : 'admin_password';
                
                $sql = "UPDATE {$table_name} SET {$password_field} = ?";
                if (in_array('updated_at', array_keys($user))) {
                    $sql .= ", updated_at = NOW()";
                }
                $sql .= " WHERE id = ?";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$hashed_password, $_SESSION['admin_id']]);
                
                $success_message = 'Password changed successfully!';
            } catch (PDOException $e) {
                $errors[] = 'Database error. Please try again.';
            }
        }
    }
    
    // Handle 2FA Setup Initiation
    if (isset($_POST['setup_2fa']) && $has_2fa_columns) {
        $new_2fa_secret = SimpleTOTP::generateSecret();
        $qr_code_url = SimpleTOTP::getQRCodeUrl($username, $new_2fa_secret, 'CMMI Admin');
        $show_2fa_setup = true;
        $_SESSION['temp_2fa_secret'] = $new_2fa_secret;
    }
    
    // Handle 2FA Verification & Enable
    if (isset($_POST['verify_2fa']) && $has_2fa_columns) {
        $verification_code = preg_replace('/\s+/', '', $_POST['verification_code'] ?? '');
        $temp_secret = $_SESSION['temp_2fa_secret'] ?? null;
        
        if (empty($verification_code)) {
            $errors[] = 'Please enter the verification code.';
            $show_2fa_setup = true;
            $new_2fa_secret = $temp_secret;
            $qr_code_url = SimpleTOTP::getQRCodeUrl($username, $temp_secret, 'CMMI Admin');
        } elseif (!$temp_secret) {
            $errors[] = '2FA setup session expired. Please try again.';
        } elseif (!SimpleTOTP::verifyCode($temp_secret, $verification_code)) {
            $errors[] = 'Invalid verification code. Please try again.';
            $show_2fa_setup = true;
            $new_2fa_secret = $temp_secret;
            $qr_code_url = SimpleTOTP::getQRCodeUrl($username, $temp_secret, 'CMMI Admin');
        } else {
            // Code verified - enable 2FA
            try {
                $recovery_codes = SimpleTOTP::generateRecoveryCodes();
                $recovery_codes_json = json_encode($recovery_codes);
                
                $stmt = $pdo->prepare("UPDATE {$table_name} SET two_factor_secret = ?, two_factor_enabled = 1, two_factor_recovery_codes = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$temp_secret, $recovery_codes_json, $_SESSION['admin_id']]);
                
                unset($_SESSION['temp_2fa_secret']);
                $two_factor_enabled = true;
                $success_message = 'Two-Factor Authentication has been enabled! Please save your recovery codes.';
                
                // Show recovery codes
                $_SESSION['show_recovery_codes'] = $recovery_codes;
                
            } catch (PDOException $e) {
                $errors[] = 'Database error. Please try again.';
            }
        }
    }
    
    // Handle 2FA Disable
    if (isset($_POST['disable_2fa']) && $has_2fa_columns) {
        $disable_code = preg_replace('/\s+/', '', $_POST['disable_code'] ?? '');
        $disable_password = $_POST['disable_password'] ?? '';
        
        // Verify password
        $password_field = isset($user['password']) ? 'password' : 'admin_password';
        if (!password_verify($disable_password, $user[$password_field])) {
            $errors[] = 'Incorrect password.';
        } elseif (empty($disable_code)) {
            $errors[] = 'Please enter your 2FA code.';
        } elseif (!SimpleTOTP::verifyCode($two_factor_secret, $disable_code)) {
            // Check recovery codes
            $recovery_codes = json_decode($user['two_factor_recovery_codes'] ?? '[]', true);
            if (!in_array($disable_code, $recovery_codes)) {
                $errors[] = 'Invalid 2FA code or recovery code.';
            }
        }
        
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("UPDATE {$table_name} SET two_factor_secret = NULL, two_factor_enabled = 0, two_factor_recovery_codes = NULL, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$_SESSION['admin_id']]);
                
                $two_factor_enabled = false;
                $success_message = 'Two-Factor Authentication has been disabled.';
            } catch (PDOException $e) {
                $errors[] = 'Database error. Please try again.';
            }
        }
    }
}

// Check if we need to show recovery codes
if (isset($_SESSION['show_recovery_codes'])) {
    $recovery_codes = $_SESSION['show_recovery_codes'];
    unset($_SESSION['show_recovery_codes']);
}
?>

<!-- Page Header -->
<div class="page-header">
    <h2><i class="fas fa-shield-alt"></i> Security Settings</h2>
    <p>Manage your password and two-factor authentication</p>
</div>

<!-- Messages -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <div>
            <?php foreach ($errors as $error): ?>
                <p style="margin: 0;"><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php if ($success_message): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php echo htmlspecialchars($success_message); ?>
    </div>
<?php endif; ?>

<!-- Recovery Codes Display -->
<?php if ($recovery_codes): ?>
<div class="recovery-codes-modal">
    <div class="recovery-codes-content">
        <div class="recovery-codes-header">
            <i class="fas fa-key"></i>
            <h3>Save Your Recovery Codes</h3>
        </div>
        <p class="recovery-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Important:</strong> Save these recovery codes in a safe place. You can use them to access your account if you lose your authenticator device. Each code can only be used once.
        </p>
        <div class="recovery-codes-grid">
            <?php foreach ($recovery_codes as $code): ?>
                <div class="recovery-code"><?php echo htmlspecialchars($code); ?></div>
            <?php endforeach; ?>
        </div>
        <div class="recovery-codes-actions">
            <button type="button" class="btn btn-secondary" onclick="copyRecoveryCodes()">
                <i class="fas fa-copy"></i> Copy Codes
            </button>
            <button type="button" class="btn btn-primary" onclick="closeRecoveryCodes()">
                <i class="fas fa-check"></i> I've Saved My Codes
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="security-container">
    <!-- Change Password Section -->
    <div class="security-card">
        <div class="security-card-header">
            <div class="security-icon">
                <i class="fas fa-lock"></i>
            </div>
            <div>
                <h3>Change Password</h3>
                <p>Update your account password</p>
            </div>
        </div>
        <div class="security-card-body">
            <form method="POST" action="" class="password-form">
                <div class="form-group">
                    <label for="current_password">
                        Current Password <span class="required">*</span>
                    </label>
                    <div class="password-input-wrapper">
                        <input 
                            type="password" 
                            id="current_password" 
                            name="current_password" 
                            class="form-control" 
                            required
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('current_password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="new_password">
                        New Password <span class="required">*</span>
                    </label>
                    <div class="password-input-wrapper">
                        <input 
                            type="password" 
                            id="new_password" 
                            name="new_password" 
                            class="form-control" 
                            required
                            minlength="8"
                            onkeyup="checkPasswordStrength(this.value)"
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength" id="passwordStrength">
                        <div class="strength-bar"><div class="strength-fill"></div></div>
                        <span class="strength-text">Password strength</span>
                    </div>
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
                    <label for="confirm_password">
                        Confirm New Password <span class="required">*</span>
                    </label>
                    <div class="password-input-wrapper">
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            class="form-control" 
                            required
                            onkeyup="checkPasswordMatch()"
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-match" id="passwordMatch"></div>
                </div>
                
                <button type="submit" name="change_password" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Password
                </button>
            </form>
        </div>
    </div>
    
    <!-- Two-Factor Authentication Section -->
    <div class="security-card">
        <div class="security-card-header">
            <div class="security-icon <?php echo $two_factor_enabled ? 'enabled' : ''; ?>">
                <i class="fas fa-mobile-alt"></i>
            </div>
            <div>
                <h3>Two-Factor Authentication (2FA)</h3>
                <p>Add an extra layer of security to your account</p>
            </div>
            <?php if ($two_factor_enabled): ?>
                <span class="status-badge enabled"><i class="fas fa-check-circle"></i> Enabled</span>
            <?php else: ?>
                <span class="status-badge disabled"><i class="fas fa-times-circle"></i> Disabled</span>
            <?php endif; ?>
        </div>
        <div class="security-card-body">
            <?php if (!$has_2fa_columns): ?>
                <!-- 2FA Not Available -->
                <div class="twofa-notice">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <h4>2FA Setup Required</h4>
                        <p>Two-factor authentication requires additional database columns. Please run the following SQL to enable this feature:</p>
                        <pre class="sql-code">ALTER TABLE <?php echo $table_name; ?> 
ADD COLUMN two_factor_secret VARCHAR(32) NULL,
ADD COLUMN two_factor_enabled TINYINT(1) DEFAULT 0,
ADD COLUMN two_factor_recovery_codes TEXT NULL;</pre>
                    </div>
                </div>
            <?php elseif ($show_2fa_setup): ?>
                <!-- 2FA Setup Form -->
                <div class="twofa-setup">
                    <div class="setup-steps">
                        <div class="setup-step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h4>Download an Authenticator App</h4>
                                <p>Install one of these apps on your phone:</p>
                                <div class="app-badges">
                                    <span><i class="fab fa-google"></i> Google Authenticator</span>
                                    <span><i class="fab fa-microsoft"></i> Microsoft Authenticator</span>
                                    <span><i class="fas fa-shield-alt"></i> Authy</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="setup-step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h4>Scan the QR Code</h4>
                                <p>Open your authenticator app and scan this QR code:</p>
                                <div class="qr-code-container">
                                    <img src="<?php echo htmlspecialchars($qr_code_url); ?>" alt="2FA QR Code">
                                </div>
                                <p class="manual-entry">
                                    <strong>Can't scan?</strong> Enter this code manually:<br>
                                    <code class="secret-code"><?php echo htmlspecialchars($new_2fa_secret); ?></code>
                                </p>
                            </div>
                        </div>
                        
                        <div class="setup-step">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h4>Verify Setup</h4>
                                <p>Enter the 6-digit code from your authenticator app:</p>
                                <form method="POST" action="" class="verify-form">
                                    <div class="code-input-group">
                                        <input 
                                            type="text" 
                                            name="verification_code" 
                                            class="form-control code-input" 
                                            placeholder="000000"
                                            maxlength="6"
                                            pattern="[0-9]{6}"
                                            autocomplete="off"
                                            required
                                        >
                                        <button type="submit" name="verify_2fa" class="btn btn-success">
                                            <i class="fas fa-check"></i> Verify & Enable
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="setup-cancel">
                        <a href="change-password.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel Setup
                        </a>
                    </div>
                </div>
            <?php elseif ($two_factor_enabled): ?>
                <!-- 2FA Enabled - Show Disable Option -->
                <div class="twofa-enabled">
                    <div class="enabled-info">
                        <i class="fas fa-shield-alt"></i>
                        <div>
                            <h4>Two-Factor Authentication is Active</h4>
                            <p>Your account is protected with an additional layer of security. You'll need to enter a code from your authenticator app when signing in.</p>
                        </div>
                    </div>
                    
                    <div class="disable-section">
                        <h4><i class="fas fa-exclamation-triangle"></i> Disable Two-Factor Authentication</h4>
                        <p>This will remove the extra security from your account. You'll need to verify your identity to proceed.</p>
                        
                        <form method="POST" action="" class="disable-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="disable_password">Your Password</label>
                                    <input 
                                        type="password" 
                                        id="disable_password" 
                                        name="disable_password" 
                                        class="form-control" 
                                        required
                                    >
                                </div>
                                <div class="form-group">
                                    <label for="disable_code">2FA Code or Recovery Code</label>
                                    <input 
                                        type="text" 
                                        id="disable_code" 
                                        name="disable_code" 
                                        class="form-control" 
                                        placeholder="000000"
                                        required
                                    >
                                </div>
                            </div>
                            <button type="submit" name="disable_2fa" class="btn btn-danger" onclick="return confirm('Are you sure you want to disable 2FA? This will make your account less secure.')">
                                <i class="fas fa-shield-alt"></i> Disable 2FA
                            </button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <!-- 2FA Not Enabled - Show Enable Option -->
                <div class="twofa-disabled">
                    <div class="twofa-benefits">
                        <h4>Why enable Two-Factor Authentication?</h4>
                        <ul>
                            <li><i class="fas fa-check-circle"></i> Prevents unauthorized access even if your password is compromised</li>
                            <li><i class="fas fa-check-circle"></i> Required for sensitive operations</li>
                            <li><i class="fas fa-check-circle"></i> Industry-standard security practice</li>
                        </ul>
                    </div>
                    
                    <form method="POST" action="">
                        <button type="submit" name="setup_2fa" class="btn btn-success btn-lg">
                            <i class="fas fa-shield-alt"></i> Enable Two-Factor Authentication
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Back to Profile -->
    <div class="form-actions">
        <a href="view-profile.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Profile
        </a>
    </div>
</div>

<style>
/* Security Settings Styles */
.security-container {
    max-width: 800px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.security-card {
    background: var(--admin-white);
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    border: 1px solid var(--admin-border);
    overflow: hidden;
}

.security-card-header {
    background: var(--admin-light);
    padding: 1.25rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    border-bottom: 1px solid var(--admin-border);
    flex-wrap: wrap;
}

.security-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--admin-accent), #5a7db0);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.security-icon.enabled {
    background: linear-gradient(135deg, var(--admin-success), #1e7e34);
}

.security-card-header h3 {
    margin: 0 0 0.25rem;
    color: var(--admin-primary);
    font-size: 1.1rem;
}

.security-card-header > div:nth-child(2) {
    flex: 1;
}

.security-card-header p {
    margin: 0;
    color: var(--admin-text-light);
    font-size: 0.875rem;
}

.status-badge {
    padding: 0.4rem 0.875rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
}

.status-badge.enabled {
    background: #d4edda;
    color: #155724;
}

.status-badge.disabled {
    background: #f8d7da;
    color: #721c24;
}

.security-card-body {
    padding: 1.5rem;
}

/* Password Form */
.password-form .form-group {
    margin-bottom: 1.25rem;
}

.password-input-wrapper {
    position: relative;
}

.password-input-wrapper .form-control {
    padding-right: 45px;
}

.password-toggle {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--admin-text-light);
    cursor: pointer;
    padding: 5px;
}

.password-toggle:hover {
    color: var(--admin-accent);
}

/* Password Strength */
.password-strength {
    margin-top: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.strength-bar {
    flex: 1;
    height: 6px;
    background: #e9ecef;
    border-radius: 3px;
    overflow: hidden;
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
    font-size: 0.75rem;
    color: var(--admin-text-light);
    min-width: 100px;
}

/* Password Requirements */
.password-requirements {
    margin-top: 0.75rem;
    padding: 0.75rem;
    background: var(--admin-light);
    border-radius: 6px;
    font-size: 0.8rem;
}

.password-requirements p {
    margin: 0 0 0.5rem;
    color: var(--admin-text-light);
}

.password-requirements ul {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.375rem;
}

.password-requirements li {
    color: var(--admin-text-light);
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

.password-requirements li i {
    font-size: 0.5rem;
}

.password-requirements li.valid {
    color: var(--admin-success);
}

.password-requirements li.valid i {
    font-size: 0.75rem;
}

.password-requirements li.valid i:before {
    content: "\f00c";
}

/* Password Match */
.password-match {
    margin-top: 0.375rem;
    font-size: 0.8rem;
}

.password-match.match {
    color: var(--admin-success);
}

.password-match.no-match {
    color: var(--admin-danger);
}

/* 2FA Styles */
.twofa-notice {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: #fff3cd;
    border-radius: 8px;
    color: #856404;
}

.twofa-notice i {
    font-size: 1.5rem;
    flex-shrink: 0;
}

.twofa-notice h4 {
    margin: 0 0 0.5rem;
}

.twofa-notice p {
    margin: 0;
    font-size: 0.9rem;
}

.sql-code {
    background: #1a1a1a;
    color: #4ade80;
    padding: 1rem;
    border-radius: 6px;
    font-size: 0.8rem;
    overflow-x: auto;
    margin-top: 0.75rem;
}

/* 2FA Setup */
.setup-steps {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.setup-step {
    display: flex;
    gap: 1rem;
}

.step-number {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--admin-accent);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    flex-shrink: 0;
}

.step-content {
    flex: 1;
}

.step-content h4 {
    margin: 0 0 0.5rem;
    color: var(--admin-primary);
}

.step-content p {
    margin: 0 0 0.75rem;
    color: var(--admin-text-light);
    font-size: 0.9rem;
}

.app-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.app-badges span {
    padding: 0.5rem 0.875rem;
    background: var(--admin-light);
    border-radius: 6px;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.qr-code-container {
    background: white;
    padding: 1rem;
    border-radius: 8px;
    display: inline-block;
    border: 1px solid var(--admin-border);
}

.qr-code-container img {
    display: block;
}

.manual-entry {
    margin-top: 0.75rem;
}

.secret-code {
    display: inline-block;
    margin-top: 0.375rem;
    padding: 0.5rem 1rem;
    background: var(--admin-light);
    border-radius: 4px;
    font-family: monospace;
    font-size: 1rem;
    letter-spacing: 2px;
    user-select: all;
}

.verify-form {
    margin-top: 0.5rem;
}

.code-input-group {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    flex-wrap: wrap;
}

.code-input {
    max-width: 150px;
    font-size: 1.25rem;
    letter-spacing: 4px;
    text-align: center;
    font-family: monospace;
}

.setup-cancel {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--admin-border);
}

/* 2FA Enabled */
.twofa-enabled .enabled-info {
    display: flex;
    gap: 1rem;
    padding: 1.25rem;
    background: #d4edda;
    border-radius: 8px;
    color: #155724;
    margin-bottom: 1.5rem;
}

.twofa-enabled .enabled-info i {
    font-size: 2rem;
    flex-shrink: 0;
}

.twofa-enabled .enabled-info h4 {
    margin: 0 0 0.375rem;
}

.twofa-enabled .enabled-info p {
    margin: 0;
    font-size: 0.9rem;
}

.disable-section {
    padding: 1.25rem;
    background: #fff5f5;
    border-radius: 8px;
    border: 1px solid #f8d7da;
}

.disable-section h4 {
    color: var(--admin-danger);
    margin: 0 0 0.5rem;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.disable-section > p {
    color: var(--admin-text-light);
    font-size: 0.9rem;
    margin-bottom: 1rem;
}

.disable-form .form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

/* 2FA Disabled */
.twofa-disabled .twofa-benefits {
    margin-bottom: 1.5rem;
}

.twofa-benefits h4 {
    margin: 0 0 0.75rem;
    color: var(--admin-primary);
}

.twofa-benefits ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.twofa-benefits li {
    padding: 0.5rem 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: var(--admin-text);
}

.twofa-benefits li i {
    color: var(--admin-success);
}

/* Recovery Codes Modal */
.recovery-codes-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
    padding: 1rem;
}

.recovery-codes-content {
    background: white;
    border-radius: 12px;
    max-width: 500px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
}

.recovery-codes-header {
    padding: 1.5rem;
    text-align: center;
    background: var(--admin-light);
    border-bottom: 1px solid var(--admin-border);
}

.recovery-codes-header i {
    font-size: 2.5rem;
    color: var(--admin-accent);
    margin-bottom: 0.75rem;
}

.recovery-codes-header h3 {
    margin: 0;
    color: var(--admin-primary);
}

.recovery-warning {
    margin: 1.25rem;
    padding: 1rem;
    background: #fff3cd;
    border-radius: 8px;
    color: #856404;
    font-size: 0.9rem;
    display: flex;
    gap: 0.75rem;
}

.recovery-warning i {
    flex-shrink: 0;
}

.recovery-codes-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
    padding: 0 1.25rem;
}

.recovery-code {
    padding: 0.75rem;
    background: var(--admin-light);
    border-radius: 6px;
    font-family: monospace;
    font-size: 0.9rem;
    text-align: center;
    user-select: all;
}

.recovery-codes-actions {
    padding: 1.25rem;
    display: flex;
    gap: 0.75rem;
    justify-content: center;
    border-top: 1px solid var(--admin-border);
    margin-top: 1.25rem;
}

/* Form Actions */
.form-actions {
    display: flex;
    justify-content: flex-start;
}

/* Required asterisk */
.required {
    color: var(--admin-danger);
}

/* Responsive */
@media (max-width: 768px) {
    .security-card-header {
        flex-direction: column;
        align-items: flex-start;
        text-align: left;
    }
    
    .status-badge {
        margin-top: 0.5rem;
    }
    
    .password-requirements ul {
        grid-template-columns: 1fr;
    }
    
    .setup-step {
        flex-direction: column;
    }
    
    .code-input-group {
        flex-direction: column;
        align-items: stretch;
    }
    
    .code-input {
        max-width: 100%;
    }
    
    .recovery-codes-grid {
        grid-template-columns: 1fr;
    }
    
    .recovery-codes-actions {
        flex-direction: column;
    }
    
    .disable-form .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

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
    const fill = document.querySelector('.strength-fill');
    const text = document.querySelector('.strength-text');
    
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
}

// Check if passwords match
function checkPasswordMatch() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const matchDiv = document.getElementById('passwordMatch');
    
    if (confirmPassword.length === 0) {
        matchDiv.textContent = '';
        matchDiv.className = 'password-match';
    } else if (newPassword === confirmPassword) {
        matchDiv.innerHTML = '<i class="fas fa-check-circle"></i> Passwords match';
        matchDiv.className = 'password-match match';
    } else {
        matchDiv.innerHTML = '<i class="fas fa-times-circle"></i> Passwords do not match';
        matchDiv.className = 'password-match no-match';
    }
}

// Copy recovery codes
function copyRecoveryCodes() {
    const codes = document.querySelectorAll('.recovery-code');
    let text = 'CMMI Admin Recovery Codes:\n\n';
    codes.forEach(code => {
        text += code.textContent + '\n';
    });
    
    navigator.clipboard.writeText(text).then(() => {
        alert('Recovery codes copied to clipboard!');
    }).catch(() => {
        alert('Failed to copy. Please select and copy manually.');
    });
}

// Close recovery codes modal
function closeRecoveryCodes() {
    const modal = document.querySelector('.recovery-codes-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}
</script>

<?php include 'includes/admin-footer.php'; ?>