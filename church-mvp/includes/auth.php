<?php
/**
 * Authentication Check File
 * 
 * This file verifies if an admin user is logged in.
 * Include this at the top of any admin page that requires authentication.
 * 
 * Security features:
 * - Session-based authentication
 * - Prevents unauthorized access to admin pages
 * - Redirects non-authenticated users to login
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if admin is logged in
 * 
 * We verify two things:
 * 1. Session variable 'admin_id' exists
 * 2. Session variable 'admin_logged_in' is true
 * 
 * This double-check adds an extra layer of security
 */
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_logged_in'] !== true) {
    // Admin not logged in - redirect to login page
    header("Location: login.php");
    exit();
}

/**
 * Optional: Session timeout feature
 * Automatically log out admin after 30 minutes of inactivity
 * 
 * Uncomment the code below to enable this feature
 */

/*
// Set timeout duration (30 minutes = 1800 seconds)
$timeout_duration = 1800;

// Check if last activity timestamp exists
if (isset($_SESSION['last_activity'])) {
    // Calculate how long ago the last activity was
    $elapsed_time = time() - $_SESSION['last_activity'];
    
    // If more than timeout duration has passed, log out the admin
    if ($elapsed_time > $timeout_duration) {
        // Destroy session
        session_unset();
        session_destroy();
        
        // Redirect to login with timeout message
        header("Location: login.php?timeout=1");
        exit();
    }
}

// Update last activity timestamp
$_SESSION['last_activity'] = time();
*/

/**
 * Get current admin's information
 * This makes admin data available throughout protected pages
 */
$admin_id = $_SESSION['admin_id'];
$admin_username = $_SESSION['admin_username'];
$admin_role = $_SESSION['admin_role'] ?? 'viewer';
?>