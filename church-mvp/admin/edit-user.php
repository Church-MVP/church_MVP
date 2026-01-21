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
                            <input type="password" 
                                   id="new_password" 
                                   name="new_password" 
                                   class="form-control" 
                                   minlength="6"
                                   placeholder="Leave blank to keep current password">
                            <small style="color: #666; display: block; margin-top: 0.25rem;">
                                Only fill if you want to change the password
                            </small>
                        </div>
                        
                        <!-- Confirm Password -->
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   class="form-control" 
                                   minlength="6"
                                   placeholder="Re-enter new password">
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
// Password match validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword && confirmPassword && newPassword !== confirmPassword) {
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