<?php
/**
 * Admin Header
 * 
 * Fully Responsive Admin Navigation
 * - Dynamic header with page title and user info
 * - Collapsible sidebar for mobile devices
 * - Profile dropdown menu
 * - Touch-friendly navigation
 * 
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

// Check if current page belongs to a specific section
function is_section_page($page, $section) {
    $section_pages = [
        'donations' => ['manage-donations.php', 'add-campaign.php', 'edit-campaign.php', 'view-donation.php'],
        'events' => ['manage-events.php', 'add-event.php', 'edit-event.php'],
        'sermons' => ['manage-sermons.php', 'add-sermon.php', 'edit-sermon.php'],
        'announcements' => ['manage-announcements.php', 'add-announcement.php', 'edit-announcement.php'],
        'content' => ['content.php', 'add-post.php', 'edit-post.php'],
        'users' => ['manage-users.php', 'add-user.php', 'edit-user.php']
    ];
    
    return isset($section_pages[$section]) && in_array($page, $section_pages[$section]);
}

// Get greeting based on time of day
function get_greeting() {
    $hour = date('H');
    if ($hour < 12) {
        return 'Good morning';
    } elseif ($hour < 17) {
        return 'Good afternoon';
    } else {
        return 'Good evening';
    }
}

$greeting = get_greeting();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#1a2b4a">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title><?php echo isset($page_title) ? $page_title . ' - Admin' : 'Admin Dashboard'; ?> | Christ Mission Ministries Inc</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Admin CSS -->
    <link rel="stylesheet" href="assets/css/admin-style.css">
    
    <!-- Profile Dropdown Styles -->
    <style>
        /* Profile Dropdown Container */
        .profile-dropdown {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        /* Profile Toggle Button */
        .profile-toggle {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            padding: 0.375rem;
            border-radius: 8px;
            transition: background-color 0.3s ease;
            border: none;
            background: transparent;
        }
        
        .profile-toggle:hover {
            background-color: var(--admin-light);
        }
        
        .profile-toggle .user-info {
            text-align: right;
        }
        
        .profile-toggle .user-info p {
            font-weight: 600;
            color: var(--admin-text);
            font-size: 0.875rem;
            line-height: 1.3;
            margin: 0;
        }
        
        .profile-toggle .user-info small {
            color: var(--admin-text-light);
            font-size: 0.75rem;
        }
        
        .profile-toggle .user-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--admin-accent), var(--admin-primary));
            color: var(--admin-white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            font-weight: 600;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(122, 156, 198, 0.3);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .profile-toggle:hover .user-avatar {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(122, 156, 198, 0.4);
        }
        
        .profile-toggle .dropdown-indicator {
            font-size: 0.7rem;
            color: var(--admin-text-light);
            transition: transform 0.3s ease;
            margin-left: 0.25rem;
        }
        
        .profile-dropdown.open .profile-toggle .dropdown-indicator {
            transform: rotate(180deg);
        }
        
        /* Profile Dropdown Menu */
        .profile-menu {
            position: absolute;
            top: calc(100% + 0.5rem);
            right: 0;
            background: var(--admin-white);
            border-radius: 10px;
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.12), 0 2px 8px rgba(0, 0, 0, 0.08);
            min-width: 220px;
            z-index: 1002;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: opacity 0.3s ease, visibility 0.3s ease, transform 0.3s ease;
            border: 1px solid var(--admin-border);
            overflow: hidden;
        }
        
        .profile-dropdown.open .profile-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        /* Profile Menu Header */
        .profile-menu-header {
            padding: 1rem;
            background: var(--admin-light);
            border-bottom: 1px solid var(--admin-border);
            text-align: center;
        }
        
        .profile-menu-header .avatar-large {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--admin-accent), var(--admin-primary));
            color: var(--admin-white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0 auto 0.75rem;
            box-shadow: 0 4px 12px rgba(122, 156, 198, 0.3);
        }
        
        .profile-menu-header h4 {
            color: var(--admin-primary);
            font-size: 0.95rem;
            font-weight: 600;
            margin: 0 0 0.25rem;
        }
        
        .profile-menu-header span {
            color: var(--admin-text-light);
            font-size: 0.8rem;
        }
        
        /* Profile Menu Items */
        .profile-menu-items {
            padding: 0.5rem 0;
        }
        
        .profile-menu-items a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--admin-text);
            text-decoration: none;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }
        
        .profile-menu-items a:hover {
            background-color: var(--admin-light);
            color: var(--admin-primary);
        }
        
        .profile-menu-items a i {
            width: 18px;
            text-align: center;
            color: var(--admin-accent);
            font-size: 0.9rem;
        }
        
        .profile-menu-items a:hover i {
            color: var(--admin-primary);
        }
        
        /* Divider */
        .profile-menu-divider {
            height: 1px;
            background: var(--admin-border);
            margin: 0.5rem 0;
        }
        
        /* Logout Item */
        .profile-menu-items a.logout-link {
            color: var(--admin-danger);
        }
        
        .profile-menu-items a.logout-link i {
            color: var(--admin-danger);
        }
        
        .profile-menu-items a.logout-link:hover {
            background-color: #fff5f5;
            color: #c82333;
        }
        
        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .profile-toggle .user-info {
                display: none;
            }
            
            .profile-toggle .dropdown-indicator {
                display: none;
            }
            
            .profile-toggle .user-avatar {
                width: 38px;
                height: 38px;
                font-size: 1rem;
            }
            
            .profile-menu {
                right: -0.5rem;
                min-width: 200px;
            }
            
            .profile-menu-header .avatar-large {
                width: 50px;
                height: 50px;
                font-size: 1.25rem;
            }
        }
        
        @media (max-width: 480px) {
            .profile-menu {
                position: fixed;
                top: auto;
                bottom: 0;
                left: 0;
                right: 0;
                border-radius: 16px 16px 0 0;
                min-width: 100%;
                transform: translateY(100%);
            }
            
            .profile-dropdown.open .profile-menu {
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        
        <!-- Sidebar Overlay (for mobile) -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        
        <!-- Profile Dropdown Overlay (for mobile) -->
        <div class="sidebar-overlay" id="profileOverlay" style="z-index: 1001;"></div>
        
        <!-- Sidebar Navigation -->
        <aside class="admin-sidebar" id="adminSidebar">
            
            <!-- Close Button (Mobile) -->
            <button class="sidebar-close" id="sidebarClose" aria-label="Close sidebar">
                <i class="fas fa-times"></i>
            </button>
            
            <!-- Sidebar Header with Logo -->
            <div class="sidebar-header">
                <img src="../assets/images/logo.jpeg" alt="CMMI Logo">
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
                
                <!-- Admin Users Dropdown -->
                <?php if (has_permission('view_users')): ?>
                <li class="has-dropdown <?php echo is_section_page($current_page, 'users') ? 'open' : ''; ?>">
                    <a href="#" class="<?php echo is_section_page($current_page, 'users') ? 'active' : ''; ?>" onclick="toggleDropdown(this); return false;">
                        <span class="menu-item-left">
                            <i class="fas fa-users"></i>
                            <span>Admin Users</span>
                        </span>
                        <i class="fas fa-chevron-down dropdown-arrow"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a href="manage-users.php" class="<?php echo ($current_page == 'manage-users.php') ? 'active' : ''; ?>">
                                <i class="fas fa-list"></i>
                                <span>Manage Users</span>
                            </a>
                        </li>
                        <li>
                            <a href="add-user.php" class="<?php echo ($current_page == 'add-user.php') ? 'active' : ''; ?>">
                                <i class="fas fa-user-plus"></i>
                                <span>Add User</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>
                
                <!-- Content Management Dropdown -->
                <?php if (can_access_content()): ?>
                <li class="has-dropdown <?php echo is_section_page($current_page, 'content') ? 'open' : ''; ?>">
                    <a href="#" class="<?php echo is_section_page($current_page, 'content') ? 'active' : ''; ?>" onclick="toggleDropdown(this); return false;">
                        <span class="menu-item-left">
                            <i class="fas fa-newspaper"></i>
                            <span>Content</span>
                        </span>
                        <i class="fas fa-chevron-down dropdown-arrow"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a href="content.php" class="<?php echo ($current_page == 'content.php') ? 'active' : ''; ?>">
                                <i class="fas fa-list"></i>
                                <span>Content</span>
                            </a>
                        </li>
                        <li>
                            <a href="add-post.php" class="<?php echo ($current_page == 'add-post.php') ? 'active' : ''; ?>">
                                <i class="fas fa-plus-circle"></i>
                                <span>Add Post</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>
                
                <!-- Sermons Dropdown -->
                <?php if (has_permission('view_sermons')): ?>
                <li class="has-dropdown <?php echo is_section_page($current_page, 'sermons') ? 'open' : ''; ?>">
                    <a href="#" class="<?php echo is_section_page($current_page, 'sermons') ? 'active' : ''; ?>" onclick="toggleDropdown(this); return false;">
                        <span class="menu-item-left">
                            <i class="fas fa-bible"></i>
                            <span>Sermons</span>
                        </span>
                        <i class="fas fa-chevron-down dropdown-arrow"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a href="manage-sermons.php" class="<?php echo ($current_page == 'manage-sermons.php') ? 'active' : ''; ?>">
                                <i class="fas fa-list"></i>
                                <span>Manage Sermons</span>
                            </a>
                        </li>
                        <li>
                            <a href="add-sermon.php" class="<?php echo ($current_page == 'add-sermon.php') ? 'active' : ''; ?>">
                                <i class="fas fa-plus-circle"></i>
                                <span>Add Sermon</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>
                
                <!-- Events Dropdown -->
                <?php if (has_permission('view_events')): ?>
                <li class="has-dropdown <?php echo is_section_page($current_page, 'events') ? 'open' : ''; ?>">
                    <a href="#" class="<?php echo is_section_page($current_page, 'events') ? 'active' : ''; ?>" onclick="toggleDropdown(this); return false;">
                        <span class="menu-item-left">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Events</span>
                        </span>
                        <i class="fas fa-chevron-down dropdown-arrow"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a href="manage-events.php" class="<?php echo ($current_page == 'manage-events.php') ? 'active' : ''; ?>">
                                <i class="fas fa-list"></i>
                                <span>Manage Events</span>
                            </a>
                        </li>
                        <li>
                            <a href="add-event.php" class="<?php echo ($current_page == 'add-event.php') ? 'active' : ''; ?>">
                                <i class="fas fa-plus-circle"></i>
                                <span>Add Event</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>
                
                <!-- Announcements Dropdown -->
                <?php if (has_permission('view_announcements')): ?>
                <li class="has-dropdown <?php echo is_section_page($current_page, 'announcements') ? 'open' : ''; ?>">
                    <a href="#" class="<?php echo is_section_page($current_page, 'announcements') ? 'active' : ''; ?>" onclick="toggleDropdown(this); return false;">
                        <span class="menu-item-left">
                            <i class="fas fa-bullhorn"></i>
                            <span>Announcements</span>
                        </span>
                        <i class="fas fa-chevron-down dropdown-arrow"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a href="manage-announcements.php" class="<?php echo ($current_page == 'manage-announcements.php') ? 'active' : ''; ?>">
                                <i class="fas fa-list"></i>
                                <span>Manage Announcements</span>
                            </a>
                        </li>
                        <li>
                            <a href="add-announcement.php" class="<?php echo ($current_page == 'add-announcement.php') ? 'active' : ''; ?>">
                                <i class="fas fa-plus-circle"></i>
                                <span>Add Announcement</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>
                
                <!-- Donations Dropdown -->
                <?php if (has_permission('view_donations')): ?>
                <li class="has-dropdown <?php echo is_section_page($current_page, 'donations') ? 'open' : ''; ?>">
                    <a href="#" class="<?php echo is_section_page($current_page, 'donations') ? 'active' : ''; ?>" onclick="toggleDropdown(this); return false;">
                        <span class="menu-item-left">
                            <i class="fas fa-hand-holding-usd"></i>
                            <span>Donations</span>
                        </span>
                        <i class="fas fa-chevron-down dropdown-arrow"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a href="manage-donations.php" class="<?php echo ($current_page == 'manage-donations.php') ? 'active' : ''; ?>">
                                <i class="fas fa-list"></i>
                                <span>Manage Donations</span>
                            </a>
                        </li>
                        <li>
                            <a href="add-campaign.php" class="<?php echo ($current_page == 'add-campaign.php') ? 'active' : ''; ?>">
                                <i class="fas fa-plus-circle"></i>
                                <span>Add Campaign</span>
                            </a>
                        </li>
                    </ul>
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
                <li class="menu-separator">
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
                <p>&copy; <?php echo date('Y'); ?> Christ Mission Ministries Inc</p>
            </div>
        </aside>
        
        <!-- Main Content Area -->
        <main class="admin-main">
            <!-- Top Bar / Dynamic Header -->
            <header class="admin-topbar">
                <!-- Mobile Sidebar Toggle -->
                <button class="mobile-sidebar-toggle" id="mobileSidebarToggle" aria-label="Toggle sidebar menu">
                    <i class="fas fa-bars"></i>
                </button>
                
                <!-- Page Title & Welcome Message -->
                <div class="topbar-title">
                    <h1><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Dashboard'; ?></h1>
                    <p><?php echo $greeting; ?>, <?php echo htmlspecialchars($admin_username); ?>!</p>
                </div>
                
                <!-- Profile Dropdown -->
                <div class="profile-dropdown" id="profileDropdown">
                    <button class="profile-toggle" id="profileToggle" aria-label="Profile menu" aria-expanded="false">
                        <div class="user-info">
                            <p><?php echo htmlspecialchars($admin_username); ?></p>
                            <small><?php echo get_role_label($admin_role); ?></small>
                        </div>
                        <div class="user-avatar" title="<?php echo htmlspecialchars($admin_username); ?>">
                            <?php echo strtoupper(substr($admin_username, 0, 1)); ?>
                        </div>
                        <i class="fas fa-chevron-down dropdown-indicator"></i>
                    </button>
                    
                    <!-- Profile Dropdown Menu -->
                    <div class="profile-menu" id="profileMenu">
                        <!-- Menu Header with Profile Info -->
                        <div class="profile-menu-header">
                            <div class="avatar-large">
                                <?php echo strtoupper(substr($admin_username, 0, 1)); ?>
                            </div>
                            <h4><?php echo htmlspecialchars($admin_username); ?></h4>
                            <span><?php echo get_role_label($admin_role); ?></span>
                        </div>
                        
                        <!-- Menu Items -->
                        <div class="profile-menu-items">
                            <a href="view-profile.php">
                                <i class="fas fa-user"></i>
                                <span>View Profile</span>
                            </a>
                            <a href="edit-profile.php">
                                <i class="fas fa-user-edit"></i>
                                <span>Edit Profile</span>
                            </a>
                            <a href="change-password.php">
                                <i class="fas fa-key"></i>
                                <span>Change Password</span>
                            </a>
                            
                            <div class="profile-menu-divider"></div>
                            
                            <a href="logout.php" class="logout-link">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Logout</span>
                            </a>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Content Area -->
            <div class="admin-content">

<!-- Admin Navigation JavaScript -->
<script>
/**
 * Toggle dropdown menu in sidebar
 */
function toggleDropdown(element) {
    const parentLi = element.closest('.has-dropdown');
    parentLi.classList.toggle('open');
}

/**
 * Sidebar toggle functionality
 */
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const mobileToggle = document.getElementById('mobileSidebarToggle');
    const sidebarClose = document.getElementById('sidebarClose');
    
    // Open sidebar
    function openSidebar() {
        sidebar.classList.add('active');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    // Close sidebar
    function closeSidebar() {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    // Toggle button click
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function(e) {
            e.preventDefault();
            if (sidebar.classList.contains('active')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }
    
    // Close button click
    if (sidebarClose) {
        sidebarClose.addEventListener('click', function(e) {
            e.preventDefault();
            closeSidebar();
        });
    }
    
    // Overlay click
    if (overlay) {
        overlay.addEventListener('click', function() {
            closeSidebar();
        });
    }
    
    // Close sidebar on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (sidebar.classList.contains('active')) {
                closeSidebar();
            }
            // Also close profile dropdown
            closeProfileDropdown();
        }
    });
    
    // Close sidebar when clicking a link (on mobile)
    const sidebarLinks = sidebar.querySelectorAll('.sidebar-menu a:not([onclick])');
    sidebarLinks.forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                closeSidebar();
            }
        });
    });
    
    // Handle window resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth > 768) {
                closeSidebar();
                closeProfileDropdown();
            }
        }, 250);
    });
    
    // Close dropdowns when clicking outside (for desktop)
    document.addEventListener('click', function(event) {
        if (window.innerWidth > 768) {
            const dropdowns = document.querySelectorAll('.has-dropdown');
            dropdowns.forEach(function(dropdown) {
                if (!dropdown.contains(event.target)) {
                    const hasActiveSubItem = dropdown.querySelector('.dropdown-menu .active');
                    if (!hasActiveSubItem) {
                        dropdown.classList.remove('open');
                    }
                }
            });
        }
    });
    
    // Touch support for mobile
    let touchStartX = 0;
    let touchEndX = 0;
    
    document.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
    }, { passive: true });
    
    document.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    }, { passive: true });
    
    function handleSwipe() {
        const swipeThreshold = 100;
        const swipeDistance = touchEndX - touchStartX;
        
        if (swipeDistance > swipeThreshold && touchStartX < 50 && !sidebar.classList.contains('active')) {
            openSidebar();
        }
        
        if (swipeDistance < -swipeThreshold && sidebar.classList.contains('active')) {
            closeSidebar();
        }
    }
    
    // ========================================
    // Profile Dropdown Functionality
    // ========================================
    
    const profileDropdown = document.getElementById('profileDropdown');
    const profileToggle = document.getElementById('profileToggle');
    const profileMenu = document.getElementById('profileMenu');
    const profileOverlay = document.getElementById('profileOverlay');
    
    function openProfileDropdown() {
        profileDropdown.classList.add('open');
        profileToggle.setAttribute('aria-expanded', 'true');
        if (window.innerWidth <= 480) {
            profileOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }
    
    function closeProfileDropdown() {
        profileDropdown.classList.remove('open');
        profileToggle.setAttribute('aria-expanded', 'false');
        profileOverlay.classList.remove('active');
        if (!sidebar.classList.contains('active')) {
            document.body.style.overflow = '';
        }
    }
    
    // Toggle profile dropdown on click
    if (profileToggle) {
        profileToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (profileDropdown.classList.contains('open')) {
                closeProfileDropdown();
            } else {
                openProfileDropdown();
            }
        });
    }
    
    // Close profile dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (profileDropdown && !profileDropdown.contains(e.target)) {
            closeProfileDropdown();
        }
    });
    
    // Close profile dropdown when clicking overlay (mobile)
    if (profileOverlay) {
        profileOverlay.addEventListener('click', function() {
            closeProfileDropdown();
        });
    }
    
    // Close profile dropdown when clicking a menu item
    if (profileMenu) {
        const menuLinks = profileMenu.querySelectorAll('a');
        menuLinks.forEach(function(link) {
            link.addEventListener('click', function() {
                closeProfileDropdown();
            });
        });
    }
});
</script>