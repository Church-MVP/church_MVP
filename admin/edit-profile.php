<?php
/**
 * Edit Profile
 * 
 * Allows the current admin user to update their profile information
 */

// Set page title
$page_title = 'Edit Profile';

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

// Fetch current user's profile data
$stmt = $pdo->prepare("SELECT * FROM {$table_name} WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['error_message'] = 'User profile not found.';
    header('Location: dashboard.php');
    exit();
}

// Get current values with fallbacks for different column names
$username = $user['username'] ?? $user['name'] ?? $user['admin_name'] ?? '';
$email = $user['email'] ?? $user['admin_email'] ?? '';
$first_name = $user['first_name'] ?? '';
$last_name = $user['last_name'] ?? '';
$full_name = $user['full_name'] ?? '';
$phone = $user['phone'] ?? '';
$bio = $user['bio'] ?? '';

// Determine which columns exist in the table
$columns = array_keys($user);
$has_first_name = in_array('first_name', $columns);
$has_last_name = in_array('last_name', $columns);
$has_full_name = in_array('full_name', $columns);
$has_phone = in_array('phone', $columns);
$has_bio = in_array('bio', $columns);
$has_email = in_array('email', $columns) || in_array('admin_email', $columns);
$email_column = in_array('email', $columns) ? 'email' : (in_array('admin_email', $columns) ? 'admin_email' : null);
$username_column = in_array('username', $columns) ? 'username' : (in_array('name', $columns) ? 'name' : (in_array('admin_name', $columns) ? 'admin_name' : null));

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Get form data
    $new_username = trim($_POST['username'] ?? '');
    $new_email = trim($_POST['email'] ?? '');
    $new_first_name = trim($_POST['first_name'] ?? '');
    $new_last_name = trim($_POST['last_name'] ?? '');
    $new_full_name = trim($_POST['full_name'] ?? '');
    $new_phone = trim($_POST['phone'] ?? '');
    $new_bio = trim($_POST['bio'] ?? '');
    
    // Validate username
    if (empty($new_username)) {
        $errors[] = 'Username is required.';
    } elseif (strlen($new_username) < 3) {
        $errors[] = 'Username must be at least 3 characters long.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $new_username)) {
        $errors[] = 'Username can only contain letters, numbers, and underscores.';
    } else {
        // Check if username is taken by another user
        $stmt = $pdo->prepare("SELECT id FROM {$table_name} WHERE {$username_column} = ? AND id != ?");
        $stmt->execute([$new_username, $_SESSION['admin_id']]);
        if ($stmt->fetch()) {
            $errors[] = 'This username is already taken.';
        }
    }
    
    // Validate email
    if ($has_email && !empty($new_email)) {
        if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } else {
            // Check if email is taken by another user
            $stmt = $pdo->prepare("SELECT id FROM {$table_name} WHERE {$email_column} = ? AND id != ?");
            $stmt->execute([$new_email, $_SESSION['admin_id']]);
            if ($stmt->fetch()) {
                $errors[] = 'This email address is already in use.';
            }
        }
    }
    
    // Validate phone (if provided)
    if ($has_phone && !empty($new_phone)) {
        $cleaned_phone = preg_replace('/[^0-9+\-\s()]/', '', $new_phone);
        if (strlen($cleaned_phone) < 7) {
            $errors[] = 'Please enter a valid phone number.';
        }
    }
    
    // If no errors, update the profile
    if (empty($errors)) {
        try {
            // Build dynamic update query based on available columns
            $update_fields = [];
            $update_values = [];
            
            // Username
            if ($username_column) {
                $update_fields[] = "{$username_column} = ?";
                $update_values[] = $new_username;
            }
            
            // Email
            if ($email_column) {
                $update_fields[] = "{$email_column} = ?";
                $update_values[] = $new_email;
            }
            
            // First name
            if ($has_first_name) {
                $update_fields[] = "first_name = ?";
                $update_values[] = $new_first_name;
            }
            
            // Last name
            if ($has_last_name) {
                $update_fields[] = "last_name = ?";
                $update_values[] = $new_last_name;
            }
            
            // Full name
            if ($has_full_name) {
                $update_fields[] = "full_name = ?";
                $update_values[] = $new_full_name;
            }
            
            // Phone
            if ($has_phone) {
                $update_fields[] = "phone = ?";
                $update_values[] = $new_phone;
            }
            
            // Bio
            if ($has_bio) {
                $update_fields[] = "bio = ?";
                $update_values[] = $new_bio;
            }
            
            // Updated at timestamp
            if (in_array('updated_at', $columns)) {
                $update_fields[] = "updated_at = NOW()";
            }
            
            // Add user ID for WHERE clause
            $update_values[] = $_SESSION['admin_id'];
            
            // Execute update
            $sql = "UPDATE {$table_name} SET " . implode(', ', $update_fields) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($update_values);
            
            // Update session username if changed
            if ($new_username !== $username) {
                $_SESSION['admin_username'] = $new_username;
            }
            
            $_SESSION['success_message'] = 'Profile updated successfully!';
            header('Location: view-profile.php');
            exit();
            
        } catch (PDOException $e) {
            $errors[] = 'Database error: Unable to update profile. Please try again.';
        }
    }
}
?>

<!-- Page Header -->
<div class="page-header">
    <h2><i class="fas fa-user-edit"></i> Edit Profile</h2>
    <p>Update your account information</p>
</div>

<!-- Error Messages -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <div>
            <strong>Please fix the following errors:</strong>
            <ul style="margin: 0.5rem 0 0 1.25rem; padding: 0;">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<div class="profile-edit-container">
    <form method="POST" action="" class="profile-form">
        <!-- Profile Preview Card -->
        <div class="profile-preview-card">
            <div class="preview-avatar">
                <?php echo strtoupper(substr($username, 0, 1)); ?>
            </div>
            <div class="preview-info">
                <h3><?php echo htmlspecialchars($username); ?></h3>
                <span><?php echo get_role_label($user['role'] ?? $admin_role); ?></span>
            </div>
        </div>
        
        <!-- Account Information -->
        <div class="form-card">
            <div class="form-card-header">
                <h3><i class="fas fa-user"></i> Account Information</h3>
            </div>
            <div class="form-card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">
                            Username <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            class="form-control" 
                            value="<?php echo htmlspecialchars($_POST['username'] ?? $username); ?>"
                            required
                            minlength="3"
                            pattern="[a-zA-Z0-9_]+"
                            title="Username can only contain letters, numbers, and underscores"
                        >
                        <small class="form-help">Letters, numbers, and underscores only. Minimum 3 characters.</small>
                    </div>
                    
                    <?php if ($has_email): ?>
                    <div class="form-group">
                        <label for="email">
                            Email Address
                        </label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-control" 
                            value="<?php echo htmlspecialchars($_POST['email'] ?? $email); ?>"
                        >
                        <small class="form-help">We'll never share your email with anyone.</small>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label>Role</label>
                    <input 
                        type="text" 
                        class="form-control" 
                        value="<?php echo htmlspecialchars(get_role_label($user['role'] ?? $admin_role)); ?>"
                        disabled
                    >
                    <small class="form-help">Contact a Super Admin to change your role.</small>
                </div>
            </div>
        </div>
        
        <!-- Personal Information -->
        <?php if ($has_first_name || $has_last_name || $has_full_name || $has_phone): ?>
        <div class="form-card">
            <div class="form-card-header">
                <h3><i class="fas fa-id-card"></i> Personal Information</h3>
            </div>
            <div class="form-card-body">
                <?php if ($has_full_name): ?>
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input 
                        type="text" 
                        id="full_name" 
                        name="full_name" 
                        class="form-control" 
                        value="<?php echo htmlspecialchars($_POST['full_name'] ?? $full_name); ?>"
                    >
                </div>
                <?php endif; ?>
                
                <?php if ($has_first_name || $has_last_name): ?>
                <div class="form-row">
                    <?php if ($has_first_name): ?>
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input 
                            type="text" 
                            id="first_name" 
                            name="first_name" 
                            class="form-control" 
                            value="<?php echo htmlspecialchars($_POST['first_name'] ?? $first_name); ?>"
                        >
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($has_last_name): ?>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input 
                            type="text" 
                            id="last_name" 
                            name="last_name" 
                            class="form-control" 
                            value="<?php echo htmlspecialchars($_POST['last_name'] ?? $last_name); ?>"
                        >
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($has_phone): ?>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        class="form-control" 
                        value="<?php echo htmlspecialchars($_POST['phone'] ?? $phone); ?>"
                        placeholder="+1 (555) 123-4567"
                    >
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Bio Section -->
        <?php if ($has_bio): ?>
        <div class="form-card">
            <div class="form-card-header">
                <h3><i class="fas fa-quote-left"></i> About Me</h3>
            </div>
            <div class="form-card-body">
                <div class="form-group">
                    <label for="bio">Bio</label>
                    <textarea 
                        id="bio" 
                        name="bio" 
                        class="form-control" 
                        rows="4"
                        placeholder="Write a short bio about yourself..."
                    ><?php echo htmlspecialchars($_POST['bio'] ?? $bio); ?></textarea>
                    <small class="form-help">A brief description about yourself. This may be visible to other admins.</small>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Form Actions -->
        <div class="form-actions">
            <a href="view-profile.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </div>
    </form>
</div>

<style>
/* Edit Profile Page Styles */
.profile-edit-container {
    max-width: 800px;
    margin: 0 auto;
}

.profile-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

/* Profile Preview Card */
.profile-preview-card {
    background: linear-gradient(135deg, var(--admin-primary) 0%, #2a4270 100%);
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1.25rem;
    color: var(--admin-white);
}

.preview-avatar {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--admin-accent), #5a7db0);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    font-weight: 700;
    border: 3px solid rgba(255, 255, 255, 0.3);
    flex-shrink: 0;
}

.preview-info h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.preview-info span {
    font-size: 0.9rem;
    opacity: 0.85;
}

/* Form Cards */
.form-card {
    background: var(--admin-white);
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--admin-border);
    overflow: hidden;
}

.form-card-header {
    background: var(--admin-light);
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--admin-border);
}

.form-card-header h3 {
    color: var(--admin-primary);
    font-size: 1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
}

.form-card-header h3 i {
    color: var(--admin-accent);
    font-size: 0.9rem;
}

.form-card-body {
    padding: 1.5rem;
}

/* Form Elements */
.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.25rem;
}

.form-group {
    margin-bottom: 1.25rem;
}

.form-group:last-child {
    margin-bottom: 0;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--admin-text);
    font-size: 0.9rem;
}

.form-group label .required {
    color: var(--admin-danger);
}

.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--admin-border);
    border-radius: 6px;
    font-size: 0.95rem;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
    font-family: inherit;
}

.form-control:focus {
    outline: none;
    border-color: var(--admin-accent);
    box-shadow: 0 0 0 3px rgba(122, 156, 198, 0.15);
}

.form-control:disabled {
    background-color: var(--admin-light);
    cursor: not-allowed;
    color: var(--admin-text-light);
}

textarea.form-control {
    resize: vertical;
    min-height: 100px;
}

.form-help {
    display: block;
    margin-top: 0.375rem;
    font-size: 0.8rem;
    color: var(--admin-text-light);
}

/* Form Actions */
.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding-top: 0.5rem;
}

.form-actions .btn {
    padding: 0.75rem 1.5rem;
    font-size: 0.95rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

/* Alert Styling */
.alert ul {
    margin-bottom: 0;
}

.alert ul li {
    margin-bottom: 0.25rem;
}

.alert ul li:last-child {
    margin-bottom: 0;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .profile-preview-card {
        padding: 1.25rem;
    }
    
    .preview-avatar {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }
    
    .preview-info h3 {
        font-size: 1.1rem;
    }
    
    .form-card-body {
        padding: 1.25rem;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column-reverse;
    }
    
    .form-actions .btn {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .profile-preview-card {
        flex-direction: column;
        text-align: center;
    }
    
    .form-card-header {
        padding: 0.875rem 1rem;
    }
    
    .form-card-body {
        padding: 1rem;
    }
}
</style>

<?php include 'includes/admin-footer.php'; ?>