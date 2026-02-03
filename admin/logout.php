<?php
/**
 * Admin Logout
 * 
 * Destroys the admin session and redirects to login page
 */

// Start session
session_start();
    
// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page with logout message
header("Location: login.php?logout=1");
exit();
?>