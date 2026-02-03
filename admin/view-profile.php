<?php
/**
 * View Profile
 * 
 * Displays the current admin user's profile information
 */

// Set page title
$page_title = 'View Profile';

// Include admin header
include 'includes/admin-header.php';

// Fetch current user's complete profile data
// Using admin_users table (adjust table name if different in your database)
try {
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If admin_users doesn't exist, try 'admins' table
    try {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e2) {
        $_SESSION['error_message'] = 'Unable to fetch profile data. Please contact administrator.';
        header('Location: dashboard.php');
        exit();
    }
}

if (!$user) {
    $_SESSION['error_message'] = 'User profile not found.';
    header('Location: dashboard.php');
    exit();
}

// Format dates safely
$created_at = isset($user['created_at']) && $user['created_at'] ? date('F j, Y', strtotime($user['created_at'])) : 'N/A';
$updated_at = isset($user['updated_at']) && $user['updated_at'] ? date('F j, Y \a\t g:i A', strtotime($user['updated_at'])) : 'N/A';
$last_login = isset($user['last_login']) && $user['last_login'] ? date('F j, Y \a\t g:i A', strtotime($user['last_login'])) : 'Never';

// Get role - check various possible column names
$user_role = $user['role'] ?? $user['user_role'] ?? $admin_role ?? 'admin';
$role_label = get_role_label($user_role);

// Get username - check various possible column names
$username = $user['username'] ?? $user['name'] ?? $user['admin_name'] ?? 'Unknown';
$email = $user['email'] ?? $user['admin_email'] ?? 'Not provided';
?>

<!-- Page Header -->
<div class="page-header">
    <h2><i class="fas fa-user-circle"></i> My Profile</h2>
    <p>View your account information and details</p>
</div>

<!-- Success/Error Messages -->
<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
    </div>
<?php endif; ?>

<div class="profile-container">
    <!-- Profile Card -->
    <div class="profile-card">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar-large">
                <?php echo strtoupper(substr($username, 0, 1)); ?>
            </div>
            <div class="profile-header-info">
                <h3><?php echo htmlspecialchars($username); ?></h3>
                <span class="role-badge role-<?php echo htmlspecialchars($user_role); ?>">
                    <?php echo htmlspecialchars($role_label); ?>
                </span>
                <?php if (isset($user['status'])): ?>
                    <?php if ($user['status'] === 'active' || $user['status'] == 1): ?>
                        <span class="status-indicator active">
                            <i class="fas fa-circle"></i> Active
                        </span>
                    <?php else: ?>
                        <span class="status-indicator inactive">
                            <i class="fas fa-circle"></i> Inactive
                        </span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="status-indicator active">
                        <i class="fas fa-circle"></i> Active
                    </span>
                <?php endif; ?>
            </div>
            <div class="profile-actions">
                <a href="edit-profile.php" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit Profile
                </a>
                <a href="change-password.php" class="btn btn-secondary">
                    <i class="fas fa-key"></i> Change Password
                </a>
            </div>
        </div>
        
        <!-- Profile Details -->
        <div class="profile-details">
            <!-- Account Information Section -->
            <div class="detail-section">
                <h4><i class="fas fa-user"></i> Account Information</h4>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Username</label>
                        <p><?php echo htmlspecialchars($username); ?></p>
                    </div>
                    <div class="detail-item">
                        <label>Email Address</label>
                        <p><?php echo htmlspecialchars($email); ?></p>
                    </div>
                    <div class="detail-item">
                        <label>Role</label>
                        <p><?php echo htmlspecialchars($role_label); ?></p>
                    </div>
                    <div class="detail-item">
                        <label>Account Status</label>
                        <p>
                            <?php if (isset($user['status']) && ($user['status'] === 'active' || $user['status'] == 1)): ?>
                                <span class="badge badge-success">Active</span>
                            <?php elseif (isset($user['status'])): ?>
                                <span class="badge badge-danger">Inactive</span>
                            <?php else: ?>
                                <span class="badge badge-success">Active</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Personal Information Section (if columns exist) -->
            <?php if (isset($user['first_name']) || isset($user['last_name']) || isset($user['phone']) || isset($user['full_name'])): ?>
            <div class="detail-section">
                <h4><i class="fas fa-id-card"></i> Personal Information</h4>
                <div class="detail-grid">
                    <?php if (isset($user['full_name']) && !empty($user['full_name'])): ?>
                    <div class="detail-item">
                        <label>Full Name</label>
                        <p><?php echo htmlspecialchars($user['full_name']); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($user['first_name'])): ?>
                    <div class="detail-item">
                        <label>First Name</label>
                        <p><?php echo htmlspecialchars($user['first_name'] ?: 'Not provided'); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($user['last_name'])): ?>
                    <div class="detail-item">
                        <label>Last Name</label>
                        <p><?php echo htmlspecialchars($user['last_name'] ?: 'Not provided'); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($user['phone'])): ?>
                    <div class="detail-item">
                        <label>Phone Number</label>
                        <p><?php echo htmlspecialchars($user['phone'] ?: 'Not provided'); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Bio Section (if column exists) -->
            <?php if (isset($user['bio']) && !empty($user['bio'])): ?>
            <div class="detail-section">
                <h4><i class="fas fa-quote-left"></i> Bio</h4>
                <div class="bio-content">
                    <p><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Account Activity Section -->
            <div class="detail-section">
                <h4><i class="fas fa-clock"></i> Account Activity</h4>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Member Since</label>
                        <p><?php echo $created_at; ?></p>
                    </div>
                    <div class="detail-item">
                        <label>Last Profile Update</label>
                        <p><?php echo $updated_at; ?></p>
                    </div>
                    <div class="detail-item">
                        <label>Last Login</label>
                        <p><?php echo $last_login; ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Role Permissions Section -->
            <div class="detail-section">
                <h4><i class="fas fa-shield-alt"></i> Role Permissions</h4>
                <p class="section-description"><?php echo get_role_description($user_role); ?></p>
                <div class="permissions-list">
                    <?php
                    global $role_permissions;
                    $permissions = $role_permissions[$user_role]['permissions'] ?? [];
                    
                    // Group permissions by category
                    $permission_groups = [
                        'Users' => ['view_users', 'create_users', 'edit_users', 'delete_users'],
                        'Sermons' => ['view_sermons', 'create_sermons', 'edit_sermons', 'delete_sermons'],
                        'Events' => ['view_events', 'create_events', 'edit_events', 'delete_events'],
                        'Announcements' => ['view_announcements', 'create_announcements', 'edit_announcements', 'delete_announcements', 'toggle_announcements'],
                        'Donations' => ['view_donations'],
                        'Settings' => ['view_settings', 'edit_settings'],
                    ];
                    
                    foreach ($permission_groups as $group_name => $group_permissions):
                        $has_any = false;
                        foreach ($group_permissions as $perm) {
                            if (in_array($perm, $permissions)) {
                                $has_any = true;
                                break;
                            }
                        }
                        if ($has_any):
                    ?>
                    <div class="permission-group">
                        <h5><?php echo $group_name; ?></h5>
                        <div class="permission-badges">
                            <?php foreach ($group_permissions as $perm): ?>
                                <?php if (in_array($perm, $permissions)): ?>
                                    <?php
                                    $perm_action = explode('_', $perm)[0];
                                    $icon = 'fas fa-check';
                                    if ($perm_action === 'view') $icon = 'fas fa-eye';
                                    elseif ($perm_action === 'create') $icon = 'fas fa-plus';
                                    elseif ($perm_action === 'edit') $icon = 'fas fa-edit';
                                    elseif ($perm_action === 'delete') $icon = 'fas fa-trash';
                                    elseif ($perm_action === 'toggle') $icon = 'fas fa-toggle-on';
                                    ?>
                                    <span class="permission-badge">
                                        <i class="<?php echo $icon; ?>"></i>
                                        <?php echo ucfirst($perm_action); ?>
                                    </span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Profile Page Styles */
.profile-container {
    max-width: 900px;
    margin: 0 auto;
}

.profile-card {
    background: var(--admin-white);
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    border: 1px solid var(--admin-border);
}

/* Profile Header */
.profile-header {
    background: linear-gradient(135deg, var(--admin-primary) 0%, #2a4270 100%);
    padding: 2rem;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 1.5rem;
}

.profile-avatar-large {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--admin-accent), #5a7db0);
    color: var(--admin-white);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    font-weight: 700;
    border: 4px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    flex-shrink: 0;
}

.profile-header-info {
    flex: 1;
    min-width: 200px;
}

.profile-header-info h3 {
    color: var(--admin-white);
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.role-badge {
    display: inline-block;
    padding: 0.35rem 0.85rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-right: 0.5rem;
}

.role-badge.role-super_admin {
    background: linear-gradient(135deg, #f093fb, #f5576c);
    color: white;
}

.role-badge.role-admin {
    background: linear-gradient(135deg, #4facfe, #00f2fe);
    color: white;
}

.role-badge.role-content_manager {
    background: linear-gradient(135deg, #43e97b, #38f9d7);
    color: #1a1a1a;
}

.role-badge.role-viewer {
    background: linear-gradient(135deg, #a8edea, #fed6e3);
    color: #1a1a1a;
}

.status-indicator {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.9);
}

.status-indicator i {
    font-size: 0.5rem;
}

.status-indicator.active i {
    color: #4ade80;
}

.status-indicator.inactive i {
    color: #f87171;
}

.profile-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.profile-actions .btn {
    padding: 0.6rem 1.25rem;
    font-size: 0.875rem;
}

/* Profile Details */
.profile-details {
    padding: 1.5rem 2rem 2rem;
}

.detail-section {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--admin-border);
}

.detail-section:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.detail-section h4 {
    color: var(--admin-primary);
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.detail-section h4 i {
    color: var(--admin-accent);
    font-size: 0.9rem;
}

.section-description {
    color: var(--admin-text-light);
    font-size: 0.9rem;
    margin-bottom: 1rem;
    font-style: italic;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.25rem;
}

.detail-item {
    background: var(--admin-light);
    padding: 1rem;
    border-radius: 8px;
}

.detail-item label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--admin-text-light);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.375rem;
}

.detail-item p {
    color: var(--admin-text);
    font-size: 0.95rem;
    margin: 0;
    word-break: break-word;
}

.bio-content {
    background: var(--admin-light);
    padding: 1rem 1.25rem;
    border-radius: 8px;
    border-left: 3px solid var(--admin-accent);
}

.bio-content p {
    color: var(--admin-text);
    font-size: 0.95rem;
    line-height: 1.7;
    margin: 0;
}

/* Permissions List */
.permissions-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.permission-group {
    background: var(--admin-light);
    padding: 1rem;
    border-radius: 8px;
}

.permission-group h5 {
    color: var(--admin-primary);
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px dashed var(--admin-border);
}

.permission-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.permission-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.35rem 0.75rem;
    background: var(--admin-white);
    border: 1px solid var(--admin-border);
    border-radius: 20px;
    font-size: 0.75rem;
    color: var(--admin-text);
}

.permission-badge i {
    color: var(--admin-accent);
    font-size: 0.7rem;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .profile-header {
        padding: 1.5rem;
        flex-direction: column;
        text-align: center;
    }
    
    .profile-header-info {
        text-align: center;
    }
    
    .profile-actions {
        justify-content: center;
        width: 100%;
    }
    
    .profile-details {
        padding: 1.25rem;
    }
    
    .profile-avatar-large {
        width: 80px;
        height: 80px;
        font-size: 2rem;
    }
    
    .detail-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .profile-header {
        padding: 1.25rem;
    }
    
    .profile-header-info h3 {
        font-size: 1.25rem;
    }
    
    .profile-actions {
        flex-direction: column;
    }
    
    .profile-actions .btn {
        width: 100%;
        justify-content: center;
    }
    
    .profile-details {
        padding: 1rem;
    }
    
    .detail-section h4 {
        font-size: 0.95rem;
    }
}
</style>

<?php include 'includes/admin-footer.php'; ?>