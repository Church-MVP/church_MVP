<?php
/**
 * Admin Header
 * 
 * Include authentication check and admin navigation
 * This file must be included at the top of every admin page
 */

// Include database connection
require_once '../includes/db.php';

// Include authentication check
require_once '../includes/auth.php';

// Include permissions system
require_once 'includes/permissions.php';

// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Define which roles can access content management
function can_access_content() {
    $content_roles = ['admin', 'super_admin', 'content_manager', 'viewer'];
    return in_array($_SESSION['admin_role'] ?? '', $content_roles);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo isset($page_title) ? $page_title . ' - Admin' : 'Admin Dashboard'; ?> | Christ Mission Ministries Inc</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- Admin CSS -->
    <link rel="stylesheet" href="assets/css/admin-style.css">
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar Navigation -->
        <aside class="admin-sidebar">
            <!-- Sidebar Header with Logo -->
            <div class="sidebar-header">
                <img src="../assets/images/logo.jpeg" alt="Christ Mission Ministries Inc">
                <h3>Admin Panel</h3>
            </div>
            
            <!-- Sidebar Menu -->
            <ul class="sidebar-menu">
                <!-- Dashboard -->
                <li>
                    <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <!-- Admin Users -->
                <?php if (has_permission('view_users')): ?>
                <li>
                    <a href="manage-users.php" class="<?php echo (in_array($current_page, ['manage-users.php', 'add-user.php', 'edit-user.php'])) ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>Admin Users</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <!-- Content Management -->
                <?php if (can_access_content()): ?>
                <li>
                    <a href="content.php" class="<?php echo (in_array($current_page, ['content.php', 'add-post.php', 'edit-post.php'])) ? 'active' : ''; ?>">
                        <i class="fas fa-newspaper"></i>
                        <span>Content</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <!-- Sermons -->
                <?php if (has_permission('view_sermons')): ?>
                <li>
                    <a href="manage-sermons.php" class="<?php echo (in_array($current_page, ['manage-sermons.php', 'add-sermon.php', 'edit-sermon.php'])) ? 'active' : ''; ?>">
                        <i class="fas fa-bible"></i>
                        <span>Sermons</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <!-- Events -->
                <?php if (has_permission('view_events')): ?>
                <li>
                    <a href="manage-events.php" class="<?php echo (in_array($current_page, ['manage-events.php', 'add-event.php', 'edit-event.php'])) ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Events</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <!-- Announcements -->
                <?php if (has_permission('view_announcements')): ?>
                <li>
                    <a href="manage-announcements.php" class="<?php echo (in_array($current_page, ['manage-announcements.php', 'add-announcement.php', 'edit-announcement.php'])) ? 'active' : ''; ?>">
                        <i class="fas fa-bullhorn"></i>
                        <span>Announcements</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <!-- Donations -->
                <?php if (has_permission('view_donations')): ?>
                <li>
                    <a href="donations.php" class="<?php echo ($current_page == 'donations.php') ? 'active' : ''; ?>">
                        <i class="fas fa-hand-holding-usd"></i>
                        <span>Donations</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <!-- Site Settings -->
                <?php if (has_permission('view_settings')): ?>
                <li>
                    <a href="settings.php" class="<?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i>
                        <span>Site Settings</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <!-- Separator -->
                <li style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">
                    <a href="../index.php" target="_blank">
                        <i class="fas fa-external-link-alt"></i>
                        <span>View Website</span>
                    </a>
                </li>
                
                <!-- Logout -->
                <li>
                    <a href="logout.php" style="color: #ff6b6b;">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
            
            <!-- Sidebar Footer -->
            <div class="sidebar-footer">
                <p>&copy; <?php echo date('Y'); ?> CMMI</p>
            </div>
        </aside>
        
        <!-- Main Content Area -->
        <main class="admin-main">
            <!-- Top Bar -->
            <header class="admin-topbar">
                <!-- Mobile Sidebar Toggle -->
                <button class="mobile-sidebar-toggle" id="mobileSidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                
                <!-- Page Title -->
                <div class="topbar-title">
                    <h1><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h1>
                    <p>Welcome back, <?php echo htmlspecialchars($admin_username); ?>!</p>
                </div>
                
                <!-- User Info -->
                <div class="topbar-user">
                    <div class="user-info">
                        <p><?php echo htmlspecialchars($admin_username); ?></p>
                        <small><?php echo get_role_label($admin_role); ?></small>
                    </div>
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($admin_username, 0, 1)); ?>
                    </div>
                    <a href="logout.php" class="btn-logout">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </header>
            
            <!-- Content Area -->
            <div class="admin-content">