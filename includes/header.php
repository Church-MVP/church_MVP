<?php
/**
 * Site Header
 * 
 * This file contains the navigation and header section
 * that appears on all public-facing pages.
 * 
 * Using includes promotes DRY (Don't Repeat Yourself) principle
 * and makes site-wide updates easier.
 */

// Get current page filename to highlight active nav link
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Christ Mission Ministries Inc - Helping You Grow Your Faith">
    <meta name="keywords" content="church, faith, community, worship, sermons">
    <meta name="author" content="Christ Mission Ministries Inc">
    
    <title><?php echo isset($page_title) ? $page_title . ' - Christ Mission Ministries Inc' : 'Christ Mission Ministries Inc'; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container">
            <!-- Logo/Brand -->
            <div class="navbar-brand">
                <a href="index.php">
                    <img src="assets/images/logo.jpeg" alt="Christ Mission Ministries Inc" class="logo-img">
                    <span class="brand-text">Christ Mission Ministries Inc</span>
                </a>
            </div>
            
            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-toggle" id="mobileMenuToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <!-- Navigation Links -->
            <ul class="navbar-menu" id="navbarMenu">
                <li><a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">Home</a></li>
                <li><a href="about.php" class="<?php echo ($current_page == 'about.php') ? 'active' : ''; ?>">About</a></li>
                <li><a href="services.php" class="<?php echo ($current_page == 'services.php') ? 'active' : ''; ?>">Services</a></li>
                <li><a href="live.php" class="<?php echo ($current_page == 'live.php') ? 'active' : ''; ?>">Live Stream</a></li>
                <li><a href="donate.php" class="<?php echo ($current_page == 'donate.php') ? 'active' : ''; ?>">Donate</a></li>
                <li><a href="contact.php" class="<?php echo ($current_page == 'contact.php') ? 'active' : ''; ?>">Contact</a></li>
            </ul>
        </div>
    </nav>