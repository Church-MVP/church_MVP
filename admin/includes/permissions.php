<?php
/**
 * Admin Permissions System
 * 
 * Defines roles and their permissions
 * 
 * ROLES:
 * - super_admin: Full access to everything
 * - admin: Can manage content but not users or critical settings
 * - content_manager: Can add/edit content but not delete
 * - viewer: Read-only access
 */

// Define all available permissions
$all_permissions = [
    // User Management
    'view_users',
    'create_users',
    'edit_users',
    'delete_users',
    
    // Sermons
    'view_sermons',
    'create_sermons',
    'edit_sermons',
    'delete_sermons',
    
    // Events
    'view_events',
    'create_events',
    'edit_events',
    'delete_events',
    
    // Announcements
    'view_announcements',
    'create_announcements',
    'edit_announcements',
    'delete_announcements',
    'toggle_announcements',
    
    // Donations
    'view_donations',
    
    // Settings
    'view_settings',
    'edit_settings',
];

// Define role permissions
$role_permissions = [
    'super_admin' => [
        'label' => 'Super Administrator',
        'description' => 'Full access to all features including user management',
        'permissions' => [
            'view_users', 'create_users', 'edit_users', 'delete_users',
            'view_sermons', 'create_sermons', 'edit_sermons', 'delete_sermons',
            'view_events', 'create_events', 'edit_events', 'delete_events',
            'view_announcements', 'create_announcements', 'edit_announcements', 'delete_announcements', 'toggle_announcements',
            'view_donations',
            'view_settings', 'edit_settings',
        ],
    ],
    
    'admin' => [
        'label' => 'Administrator',
        'description' => 'Can manage all content but cannot manage users or critical settings',
        'permissions' => [
            'view_sermons', 'create_sermons', 'edit_sermons', 'delete_sermons',
            'view_events', 'create_events', 'edit_events', 'delete_events',
            'view_announcements', 'create_announcements', 'edit_announcements', 'delete_announcements', 'toggle_announcements',
            'view_donations',
            'view_settings',
        ],
    ],
    
    'content_manager' => [
        'label' => 'Content Manager',
        'description' => 'Can create and edit content but cannot delete',
        'permissions' => [
            'view_sermons', 'create_sermons', 'edit_sermons',
            'view_events', 'create_events', 'edit_events',
            'view_announcements', 'create_announcements', 'edit_announcements', 'toggle_announcements',
            'view_donations',
        ],
    ],
    
    'viewer' => [
        'label' => 'Viewer',
        'description' => 'Read-only access to view content',
        'permissions' => [
            'view_sermons',
            'view_events',
            'view_announcements',
            'view_donations',
        ],
    ],
];

/**
 * Check if current admin has a specific permission
 * 
 * @param string $permission Permission to check
 * @return bool
 */
function has_permission($permission) {
    global $role_permissions;
    
    if (!isset($_SESSION['admin_role'])) {
        return false;
    }
    
    $role = $_SESSION['admin_role'];
    
    if (!isset($role_permissions[$role])) {
        return false;
    }
    
    return in_array($permission, $role_permissions[$role]['permissions']);
}

/**
 * Check if current admin has any of the specified permissions
 * 
 * @param array $permissions Array of permissions to check
 * @return bool
 */
function has_any_permission($permissions) {
    foreach ($permissions as $permission) {
        if (has_permission($permission)) {
            return true;
        }
    }
    return false;
}

/**
 * Require a specific permission or redirect
 * 
 * @param string $permission Permission required
 * @param string $redirect_url URL to redirect if no permission
 */
function require_permission($permission, $redirect_url = 'dashboard.php') {
    if (!has_permission($permission)) {
        $_SESSION['error_message'] = 'You do not have permission to access this feature.';
        header("Location: $redirect_url");
        exit();
    }
}

/**
 * Get role label
 * 
 * @param string $role Role name
 * @return string
 */
function get_role_label($role) {
    global $role_permissions;
    return $role_permissions[$role]['label'] ?? ucfirst($role);
}

/**
 * Get role description
 * 
 * @param string $role Role name
 * @return string
 */
function get_role_description($role) {
    global $role_permissions;
    return $role_permissions[$role]['description'] ?? '';
}

/**
 * Get all available roles
 * 
 * @return array
 */
function get_all_roles() {
    global $role_permissions;
    return array_keys($role_permissions);
}

/**
 * Check if current user is super admin
 * 
 * @return bool
 */
function is_super_admin() {
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'super_admin';
}
?>