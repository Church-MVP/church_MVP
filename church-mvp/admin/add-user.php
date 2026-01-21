<?php
/**
 * Add New Admin User
 * 
 * Create a new admin user with specific role
 * Only accessible by super_admin
 */

// Set page title
$page_title = 'Add New User';

// Include admin header
include 'includes/admin-header.php';

// Check permission
require_permission('create_users');

// Initialize messages
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize inputs
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $full_name = sanitize_input($_POST['full_name']);
    $password = $_POST['password']; // Don't sanitize password
    $confirm_password = $_POST['confirm_password'];
    $role = sanitize_input($_POST['role']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($role)) {
        $error_message = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error_message = 'Password must be at least 6 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } elseif (!in_array($role, get_all_roles())) {
        $error_message = 'Invalid role selected.';
    } else {
        try {
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $error_message = 'Username already exists. Please choose a different username.';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user into database
                $stmt = $pdo->prepare("INSERT INTO admins (username, password, email, full_name, role, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $username,
                    $hashed_password,
                    $email,
                    $full_name,
                    $role,
                    $is_active,
                    $admin_id
                ]);
                
                $success_message = 'User created successfully!';
                
                // Clear form
                $_POST = [];
            }
        } catch (PDOException $e) {
            $error_message = 'Error creating user. Please try again.';
        }
    }
}
?>

<!-- Page Header -->
<div class="page-header">
    <h2><i class="fas fa-user-plus"></i> Add New Admin User</h2>
    <p>Create a new admin account with specific role and permissions</p>
</div>

<!-- Success/Error Messages -->
<?php if (!empty($success_message)): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
    <a href="manage-users.php" style="margin-left: 1rem;">View all users</a>
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
        <h3><i class="fas fa-user"></i> User Details</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <!-- Left Column -->
                <div>
                    <!-- Username -->
                    <div class="form-group">
                        <label for="username">Username <span style="color: red;">*</span></label>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               class="form-control" 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                               placeholder="e.g., johndoe"
                               pattern="[a-zA-Z0-9_]+"
                               title="Username can only contain letters, numbers, and underscores"
                               required>
                        <small style="color: #666; display: block; margin-top: 0.25rem;">
                            Only letters, numbers, and underscores allowed
                        </small>
                    </div>
                    
                    <!-- Email -->
                    <div class="form-group">
                        <label for="email">Email Address <span style="color: red;">*</span></label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               class="form-control" 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               placeholder="user@example.com"
                               required>
                    </div>
                    
                    <!-- Full Name -->
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" 
                               id="full_name" 
                               name="full_name" 
                               class="form-control" 
                               value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                               placeholder="e.g., John Doe">
                    </div>
                    
                    <!-- Role -->
                    <div class="form-group">
                        <label for="role">User Role <span style="color: red;">*</span></label>
                        <select id="role" name="role" class="form-control" required onchange="updateRoleDescription()">
                            <option value="">-- Select Role --</option>
                            <?php foreach (get_all_roles() as $role_key): ?>
                            <option value="<?php echo $role_key; ?>" <?php echo (isset($_POST['role']) && $_POST['role'] == $role_key) ? 'selected' : ''; ?>>
                                <?php echo get_role_label($role_key); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="role-description" style="margin-top: 0.5rem; padding: 0.75rem; background: #f8f9fa; border-radius: 4px; display: none;">
                            <small style="color: #666;"></small>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div>
                    <!-- Password -->
                    <div class="form-group">
                        <label for="password">Password <span style="color: red;">*</span></label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-control" 
                               minlength="6"
                               placeholder="Minimum 6 characters"
                               required>
                        <small style="color: #666; display: block; margin-top: 0.25rem;">
                            Minimum 6 characters
                        </small>
                    </div>
                    
                    <!-- Confirm Password -->
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password <span style="color: red;">*</span></label>
                        <input type="password" 
                               id="confirm_password" 
                               name="confirm_password" 
                               class="form-control" 
                               minlength="6"
                               placeholder="Re-enter password"
                               required>
                    </div>
                    
                    <!-- Active Status -->
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" 
                                   id="is_active" 
                                   name="is_active" 
                                   value="1"
                                   checked
                                   style="width: 20px; height: 20px;">
                            <span>Account is active (user can log in)</span>
                        </label>
                    </div>
                    
                    <!-- Info Box -->
                    <div style="background: #e3f2fd; padding: 1rem; border-radius: 4px; border-left: 4px solid #2196f3;">
                        <h4 style="font-size: 0.95rem; margin-bottom: 0.5rem; color: #1976d2;">
                            <i class="fas fa-info-circle"></i> Security Note
                        </h4>
                        <p style="font-size: 0.85rem; color: #666; margin: 0;">
                            Always use strong passwords and unique usernames. Users can change their password after first login.
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div style="display: flex; gap: 1rem; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e0e5eb;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create User
                </button>
                <a href="manage-users.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// Role descriptions
const roleDescriptions = <?php echo json_encode(array_map(function($role) {
    return get_role_description($role);
}, array_combine(get_all_roles(), get_all_roles()))); ?>;

function updateRoleDescription() {
    const roleSelect = document.getElementById('role');
    const descDiv = document.getElementById('role-description');
    const role = roleSelect.value;
    
    if (role && roleDescriptions[role]) {
        descDiv.style.display = 'block';
        descDiv.querySelector('small').textContent = roleDescriptions[role];
    } else {
        descDiv.style.display = 'none';
    }
}

// Password match validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if (password !== confirmPassword) {
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