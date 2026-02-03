<?php
/**
 * Edit Admin User
 * 
 * Edit existing admin user details and role
 * Only accessible by super_admin
 */

// Set page title
$page_title = 'Edit User';

// Include admin header
include 'includes/admin-header.php';

// Check permission
require_permission('edit_users');

// Initialize messages
$success_message = '';
$error_message = '';

// Get user ID from URL
if (!isset($_GET['id'])) {
    header("Location: manage-users.php");
    exit();
}

$user_id = (int)$_GET['id'];

// Fetch user data
try {
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header("Location: manage-users.php");
        exit();
    }
} catch (PDOException $e) {
    $error_message = 'Error fetching user data.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize inputs
    $email = sanitize_input($_POST['email']);
    $full_name = sanitize_input($_POST['full_name']);
    $role = sanitize_input($_POST['role']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $change_password = !empty($_POST['new_password']);
    
    // Validation
    if (empty($email) || empty($role)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } elseif (!in_array($role, get_all_roles())) {
        $error_message = 'Invalid role selected.';
    } elseif ($change_password && strlen($_POST['new_password']) < 6) {
        $error_message = 'New password must be at least 6 characters long.';
    } elseif ($change_password && $_POST['new_password'] !== $_POST['confirm_password']) {
        $error_message = 'Passwords do not match.';
    } else {
        try {
            if ($change_password) {
                // Update with new password
                $hashed_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE admins SET email = ?, full_name = ?, role = ?, is_active = ?, password = ? WHERE id = ?");
                $stmt->execute([
                    $email,
                    $full_name,
                    $role,
                    $is_active,
                    $hashed_password,
                    $user_id
                ]);
            } else {
                // Update without changing password
                $stmt = $pdo->prepare("UPDATE admins SET email = ?, full_name = ?, role = ?, is_active = ? WHERE id = ?");
                $stmt->execute([
                    $email,
                    $full_name,
                    $role,
                    $is_active,
                    $user_id
                ]);
            }
            
            $success_message = 'User updated successfully!';
            
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
        } catch (PDOException $e) {
            $error_message = 'Error updating user. Please try again.';
        }
    }
}
?>

<!-- Page Header -->
<div class="page-header">
    <h2><i class="fas fa-user-edit"></i> Edit User</h2>
    <p>Update admin user information and permissions</p>
</div>

<!-- Success/Error Messages -->
<?php if (!empty($success_message)): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
    <a href="manage-users.php" style="margin-left: 1rem;">Back to all users</a>
</div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
</div>
<?php endif; ?>

<!-- Password Strength Styles -->
<style>
/* Password Input Wrapper */
.password-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.password-input-wrapper .form-control {
    padding-right: 45px;
}

.password-toggle-btn {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #7a9cc6;
    cursor: pointer;
    padding: 5px;
    font-size: 1rem;
    transition: color 0.3s ease;
    z-index: 2;
}

.password-toggle-btn:hover {
    color: #1a2b4a;
}

/* Radial Strength Meter */
.password-strength-container {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-top: 0.75rem;
    padding: 0.75rem;
    background: #fff;
    border-radius: 8px;
    border: 1px solid #e0e5eb;
}

.radial-progress {
    position: relative;
    width: 60px;
    height: 60px;
    flex-shrink: 0;
}

.radial-progress svg {
    transform: rotate(-90deg);
    width: 60px;
    height: 60px;
}

.radial-progress .progress-bg {
    fill: none;
    stroke: #e9ecef;
    stroke-width: 6;
}

.radial-progress .progress-bar {
    fill: none;
    stroke-width: 6;
    stroke-linecap: round;
    stroke-dasharray: 157; /* 2 * PI * 25 (radius) */
    stroke-dashoffset: 157;
    transition: stroke-dashoffset 0.5s ease, stroke 0.5s ease;
}

.radial-progress .progress-bar.weak {
    stroke: #dc3545;
}

.radial-progress .progress-bar.fair {
    stroke: #fd7e14;
}

.radial-progress .progress-bar.medium {
    stroke: #ffc107;
}

.radial-progress .progress-bar.good {
    stroke: #20c997;
}

.radial-progress .progress-bar.strong {
    stroke: #28a745;
}

.radial-progress .progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 0.7rem;
    font-weight: 700;
    text-align: center;
    line-height: 1.2;
}

.radial-progress .progress-text.weak { color: #dc3545; }
.radial-progress .progress-text.fair { color: #fd7e14; }
.radial-progress .progress-text.medium { color: #ffc107; }
.radial-progress .progress-text.good { color: #20c997; }
.radial-progress .progress-text.strong { color: #28a745; }

/* Strength Details */
.strength-details {
    flex: 1;
}

.strength-label {
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.strength-label.weak { color: #dc3545; }
.strength-label.fair { color: #fd7e14; }
.strength-label.medium { color: #ffc107; }
.strength-label.good { color: #20c997; }
.strength-label.strong { color: #28a745; }

.strength-requirements {
    display: flex;
    flex-wrap: wrap;
    gap: 0.375rem;
}

.requirement {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.7rem;
    padding: 0.2rem 0.5rem;
    border-radius: 12px;
    background: #f8f9fa;
    color: #6c757d;
    transition: all 0.3s ease;
}

.requirement i {
    font-size: 0.6rem;
}

.requirement.met {
    background: #d4edda;
    color: #155724;
}

.requirement.met i {
    color: #28a745;
}

/* Password Match Indicator */
.password-match-indicator {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.5rem;
    font-size: 0.85rem;
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.password-match-indicator.hidden {
    display: none;
}

.password-match-indicator.match {
    background: #d4edda;
    color: #155724;
}

.password-match-indicator.no-match {
    background: #f8d7da;
    color: #721c24;
}

.password-match-indicator i {
    font-size: 1rem;
}

/* Responsive */
@media (max-width: 768px) {
    .password-strength-container {
        flex-direction: column;
        text-align: center;
    }
    
    .strength-requirements {
        justify-content: center;
    }
}
</style>

<!-- User Form -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-user"></i> Edit User: <?php echo htmlspecialchars($user['username']); ?></h3>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <!-- Left Column -->
                <div>
                    <!-- Username (Read-only) -->
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" 
                               id="username" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($user['username']); ?>"
                               disabled
                               style="background-color: #f8f9fa; cursor: not-allowed;">
                        <small style="color: #666; display: block; margin-top: 0.25rem;">
                            Username cannot be changed
                        </small>
                    </div>
                    
                    <!-- Email -->
                    <div class="form-group">
                        <label for="email">Email Address <span style="color: red;">*</span></label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($user['email']); ?>"
                               required>
                    </div>
                    
                    <!-- Full Name -->
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" 
                               id="full_name" 
                               name="full_name" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($user['full_name']); ?>">
                    </div>
                    
                    <!-- Role -->
                    <div class="form-group">
                        <label for="role">User Role <span style="color: red;">*</span></label>
                        <select id="role" name="role" class="form-control" required <?php echo ($user['id'] == $admin_id) ? 'disabled' : ''; ?>>
                            <?php foreach (get_all_roles() as $role_key): ?>
                            <option value="<?php echo $role_key; ?>" <?php echo ($user['role'] == $role_key) ? 'selected' : ''; ?>>
                                <?php echo get_role_label($role_key); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($user['id'] == $admin_id): ?>
                        <small style="color: #666; display: block; margin-top: 0.25rem;">
                            You cannot change your own role
                        </small>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Active Status -->
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" 
                                   id="is_active" 
                                   name="is_active" 
                                   value="1"
                                   <?php echo $user['is_active'] ? 'checked' : ''; ?>
                                   <?php echo ($user['id'] == $admin_id) ? 'disabled' : ''; ?>
                                   style="width: 20px; height: 20px;">
                            <span>Account is active (user can log in)</span>
                        </label>
                        <?php if ($user['id'] == $admin_id): ?>
                        <small style="color: #666; display: block; margin-top: 0.25rem;">
                            You cannot deactivate your own account
                        </small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div>
                    <!-- Change Password Section -->
                    <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 6px; margin-bottom: 1rem;">
                        <h4 style="font-size: 1.1rem; margin-bottom: 1rem; color: #333;">
                            <i class="fas fa-key"></i> Change Password (Optional)
                        </h4>
                        
                        <!-- New Password -->
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <div class="password-input-wrapper">
                                <input type="password" 
                                       id="new_password" 
                                       name="new_password" 
                                       class="form-control" 
                                       minlength="6"
                                       placeholder="Leave blank to keep current password"
                                       autocomplete="new-password">
                                <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('new_password', this)" aria-label="Toggle password visibility">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            
                            <!-- Radial Password Strength Meter -->
                            <div class="password-strength-container" id="strengthContainer" style="display: none;">
                                <div class="radial-progress">
                                    <svg viewBox="0 0 56 56">
                                        <circle class="progress-bg" cx="28" cy="28" r="25"></circle>
                                        <circle class="progress-bar" id="strengthBar" cx="28" cy="28" r="25"></circle>
                                    </svg>
                                    <div class="progress-text" id="strengthPercent">0%</div>
                                </div>
                                <div class="strength-details">
                                    <div class="strength-label" id="strengthLabel">Enter a password</div>
                                    <div class="strength-requirements">
                                        <span class="requirement" id="req-length">
                                            <i class="fas fa-circle"></i> 8+ chars
                                        </span>
                                        <span class="requirement" id="req-upper">
                                            <i class="fas fa-circle"></i> Uppercase
                                        </span>
                                        <span class="requirement" id="req-lower">
                                            <i class="fas fa-circle"></i> Lowercase
                                        </span>
                                        <span class="requirement" id="req-number">
                                            <i class="fas fa-circle"></i> Number
                                        </span>
                                        <span class="requirement" id="req-special">
                                            <i class="fas fa-circle"></i> Special
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <small style="color: #666; display: block; margin-top: 0.5rem;">
                                Only fill if you want to change the password
                            </small>
                        </div>
                        
                        <!-- Confirm Password -->
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <div class="password-input-wrapper">
                                <input type="password" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       class="form-control" 
                                       minlength="6"
                                       placeholder="Re-enter new password"
                                       autocomplete="new-password">
                                <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('confirm_password', this)" aria-label="Toggle password visibility">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            
                            <!-- Password Match Indicator -->
                            <div class="password-match-indicator hidden" id="matchIndicator">
                                <i class="fas fa-check-circle"></i>
                                <span id="matchText">Passwords match</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Account Info -->
                    <div style="background: #e3f2fd; padding: 1rem; border-radius: 4px; border-left: 4px solid #2196f3;">
                        <h4 style="font-size: 0.95rem; margin-bottom: 0.5rem; color: #1976d2;">
                            <i class="fas fa-info-circle"></i> Account Information
                        </h4>
                        <p style="font-size: 0.85rem; color: #666; margin-bottom: 0.5rem;">
                            <strong>Created:</strong> <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                        </p>
                        <p style="font-size: 0.85rem; color: #666; margin: 0;">
                            <strong>Last Login:</strong> 
                            <?php 
                            if ($user['last_login']) {
                                echo date('M d, Y g:i A', strtotime($user['last_login']));
                            } else {
                                echo 'Never';
                            }
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div style="display: flex; gap: 1rem; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e0e5eb;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update User
                </button>
                <a href="manage-users.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                
                <?php if (has_permission('delete_users') && $user['id'] != $admin_id): ?>
                <a href="manage-users.php?delete=<?php echo $user_id; ?>" 
                   class="btn btn-danger btn-delete" 
                   data-item="<?php echo htmlspecialchars($user['username']); ?>"
                   style="margin-left: auto;">
                    <i class="fas fa-trash"></i> Delete User
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<script>
// Toggle Password Visibility
function togglePasswordVisibility(inputId, button) {
    const input = document.getElementById(inputId);
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

// Password Strength Calculator
function calculatePasswordStrength(password) {
    let score = 0;
    const requirements = {
        length: password.length >= 8,
        upper: /[A-Z]/.test(password),
        lower: /[a-z]/.test(password),
        number: /[0-9]/.test(password),
        special: /[^A-Za-z0-9]/.test(password)
    };
    
    // Base score from requirements
    if (requirements.length) score += 20;
    if (requirements.upper) score += 20;
    if (requirements.lower) score += 20;
    if (requirements.number) score += 20;
    if (requirements.special) score += 20;
    
    // Bonus for length
    if (password.length >= 12) score += 10;
    if (password.length >= 16) score += 10;
    
    // Cap at 100
    score = Math.min(score, 100);
    
    // Determine strength level
    let level, label;
    if (score === 0) {
        level = 'none';
        label = 'Enter a password';
    } else if (score < 30) {
        level = 'weak';
        label = 'Weak';
    } else if (score < 50) {
        level = 'fair';
        label = 'Fair';
    } else if (score < 70) {
        level = 'medium';
        label = 'Medium';
    } else if (score < 90) {
        level = 'good';
        label = 'Good';
    } else {
        level = 'strong';
        label = 'Strong';
    }
    
    return { score, level, label, requirements };
}

// Update Radial Progress
function updateStrengthMeter(password) {
    const container = document.getElementById('strengthContainer');
    const progressBar = document.getElementById('strengthBar');
    const percentText = document.getElementById('strengthPercent');
    const labelText = document.getElementById('strengthLabel');
    
    // Show/hide container
    if (password.length === 0) {
        container.style.display = 'none';
        return;
    }
    container.style.display = 'flex';
    
    const { score, level, label, requirements } = calculatePasswordStrength(password);
    
    // Update progress bar (circumference = 2 * PI * 25 ≈ 157)
    const circumference = 157;
    const offset = circumference - (circumference * score / 100);
    progressBar.style.strokeDashoffset = offset;
    
    // Update classes
    progressBar.className = 'progress-bar ' + level;
    percentText.className = 'progress-text ' + level;
    labelText.className = 'strength-label ' + level;
    
    // Update text
    percentText.textContent = score + '%';
    labelText.textContent = label;
    
    // Update requirement indicators
    updateRequirement('req-length', requirements.length);
    updateRequirement('req-upper', requirements.upper);
    updateRequirement('req-lower', requirements.lower);
    updateRequirement('req-number', requirements.number);
    updateRequirement('req-special', requirements.special);
}

function updateRequirement(id, met) {
    const element = document.getElementById(id);
    const icon = element.querySelector('i');
    
    if (met) {
        element.classList.add('met');
        icon.className = 'fas fa-check';
    } else {
        element.classList.remove('met');
        icon.className = 'fas fa-circle';
    }
}

// Password Match Check
function checkPasswordMatch() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const indicator = document.getElementById('matchIndicator');
    const icon = indicator.querySelector('i');
    const text = document.getElementById('matchText');
    
    if (confirmPassword.length === 0) {
        indicator.classList.add('hidden');
        return;
    }
    
    indicator.classList.remove('hidden');
    
    if (newPassword === confirmPassword) {
        indicator.classList.remove('no-match');
        indicator.classList.add('match');
        icon.className = 'fas fa-check-circle';
        text.textContent = 'Passwords match';
    } else {
        indicator.classList.remove('match');
        indicator.classList.add('no-match');
        icon.className = 'fas fa-times-circle';
        text.textContent = 'Passwords do not match';
    }
}

// Event Listeners
document.getElementById('new_password').addEventListener('input', function() {
    updateStrengthMeter(this.value);
    checkPasswordMatch();
});

document.getElementById('confirm_password').addEventListener('input', function() {
    checkPasswordMatch();
    
    // Also validate for form submission
    const newPassword = document.getElementById('new_password').value;
    if (newPassword && this.value && newPassword !== this.value) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<?php
// Include admin footer
include 'includes/admin-footer.php';
?>