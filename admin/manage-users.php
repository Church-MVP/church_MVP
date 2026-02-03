<?php
/**
 * Manage Users
 * 
 * View, edit, activate/deactivate, and delete admin users
 * Only accessible by super_admin
 */

// Set page title
$page_title = 'Manage Users';

// Include admin header
include 'includes/admin-header.php';

// Check permission
require_permission('view_users');

// Initialize messages
$success_message = '';
$error_message = '';

// Handle delete action (only super admin can delete)
if (isset($_GET['delete']) && has_permission('delete_users')) {
    $user_id = (int)$_GET['delete'];
    
    // Prevent deleting yourself
    if ($user_id == $admin_id) {
        $error_message = 'You cannot delete your own account!';
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
            $stmt->execute([$user_id]);
            $success_message = 'User deleted successfully!';
        } catch (PDOException $e) {
            $error_message = 'Error deleting user. Please try again.';
        }
    }
}

// Handle toggle active/inactive
if (isset($_GET['toggle']) && has_permission('edit_users')) {
    $user_id = (int)$_GET['toggle'];
    
    // Prevent deactivating yourself
    if ($user_id == $admin_id) {
        $error_message = 'You cannot deactivate your own account!';
    } else {
        try {
            // Get current status
            $stmt = $pdo->prepare("SELECT is_active FROM admins WHERE id = ?");
            $stmt->execute([$user_id]);
            $current_status = $stmt->fetchColumn();
            
            // Toggle status
            $new_status = $current_status ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE admins SET is_active = ? WHERE id = ?");
            $stmt->execute([$new_status, $user_id]);
            
            $success_message = 'User status updated successfully!';
        } catch (PDOException $e) {
            $error_message = 'Error updating user status.';
        }
    }
}

// Fetch all users
$stmt = $pdo->query("SELECT * FROM admins ORDER BY created_at DESC");
$users = $stmt->fetchAll();
?>

<!-- Page Header -->
<div class="page-header">
    <h2><i class="fas fa-users"></i> Manage Admin Users</h2>
    <p>View and manage admin accounts with different roles and permissions</p>
</div>

<!-- Success/Error Messages -->
<?php if (!empty($success_message)): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
</div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
</div>
<?php endif; ?>

<!-- Add New Button -->
<?php if (has_permission('create_users')): ?>
<div style="margin-bottom: 1.5rem;">
    <a href="add-user.php" class="btn btn-primary">
        <i class="fas fa-user-plus"></i> Add New User
    </a>
</div>
<?php endif; ?>

<!-- Role Legend -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-info-circle"></i> User Roles & Permissions</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
            <?php
            $roles_info = [
                'super_admin' => ['color' => '#dc3545', 'icon' => 'fa-crown'],
                'admin' => ['color' => '#ffc107', 'icon' => 'fa-user-shield'],
                'content_manager' => ['color' => '#17a2b8', 'icon' => 'fa-user-edit'],
                'viewer' => ['color' => '#6c757d', 'icon' => 'fa-eye'],
            ];
            
            foreach (get_all_roles() as $role):
                $color = $roles_info[$role]['color'] ?? '#333';
                $icon = $roles_info[$role]['icon'] ?? 'fa-user';
            ?>
            <div style="padding: 1rem; background: <?php echo $color; ?>15; border-left: 4px solid <?php echo $color; ?>; border-radius: 4px;">
                <h4 style="color: <?php echo $color; ?>; margin-bottom: 0.5rem;">
                    <i class="fas <?php echo $icon; ?>"></i> <?php echo get_role_label($role); ?>
                </h4>
                <p style="font-size: 0.9rem; color: #666; margin: 0;">
                    <?php echo get_role_description($role); ?>
                </p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-list"></i> All Admin Users (<?php echo count($users); ?>)</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr style="<?php echo !$user['is_active'] ? 'opacity: 0.5;' : ''; ?>">
                        <td><?php echo $user['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                            <?php if ($user['id'] == $admin_id): ?>
                                <span class="badge badge-info" style="margin-left: 0.5rem;">You</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($user['full_name'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <?php
                            $role_colors = [
                                'super_admin' => 'danger',
                                'admin' => 'warning',
                                'content_manager' => 'info',
                                'viewer' => 'secondary',
                            ];
                            $badge_color = $role_colors[$user['role']] ?? 'secondary';
                            ?>
                            <span class="badge badge-<?php echo $badge_color; ?>">
                                <?php echo get_role_label($user['role']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($user['is_active']): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            if ($user['last_login']) {
                                echo date('M d, Y g:i A', strtotime($user['last_login']));
                            } else {
                                echo '<span style="color: #999;">Never</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if (has_permission('edit_users') && $user['id'] != $admin_id): ?>
                            <a href="?toggle=<?php echo $user['id']; ?>" 
                               class="btn btn-sm <?php echo $user['is_active'] ? 'btn-secondary' : 'btn-success'; ?>" 
                               title="<?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                <i class="fas fa-<?php echo $user['is_active'] ? 'user-slash' : 'user-check'; ?>"></i>
                            </a>
                            <?php endif; ?>
                            
                            <?php if (has_permission('edit_users')): ?>
                            <a href="edit-user.php?id=<?php echo $user['id']; ?>" 
                               class="btn btn-sm btn-info" 
                               title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php endif; ?>
                            
                            <?php if (has_permission('delete_users') && $user['id'] != $admin_id): ?>
                            <a href="?delete=<?php echo $user['id']; ?>" 
                               class="btn btn-sm btn-danger btn-delete" 
                               data-item="<?php echo htmlspecialchars($user['username']); ?>"
                               title="Delete">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Info Box -->
<div class="alert alert-info" style="margin-top: 1.5rem;">
    <i class="fas fa-info-circle"></i> 
    <strong>Note:</strong> Only Super Administrators can create, edit, and delete admin users. Inactive users cannot log in to the system.
</div>

<?php
// Include admin footer
include 'includes/admin-footer.php';
?>