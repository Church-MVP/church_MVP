<?php
/**
 * Database Connection File
 * 
 * This file establishes a secure connection to the MySQL database
 * using PDO (PHP Data Objects) for better security and flexibility.
 * 
 * PDO benefits:
 * - Prepared statements prevent SQL injection
 * - Works with multiple database types
 * - Better error handling
 */

// Database configuration constants
define('DB_HOST', 'localhost');      // Database server (usually localhost for local development)
define('DB_NAME', 'church_mvp');     // Database name we created
define('DB_USER', 'root');           // Database username (default for XAMPP/WAMP)
define('DB_PASS', '');               // Database password (empty for local development)
define('DB_CHARSET', 'utf8mb4');     // Character set for proper encoding

/**
 * Create PDO database connection
 * 
 * DSN (Data Source Name) format: mysql:host=HOST;dbname=DATABASE;charset=CHARSET
 */
try {
    // Create new PDO instance
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            // Set error mode to exceptions for better error handling
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            
            // Return associative arrays by default
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            
            // Disable emulated prepared statements for better security
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
} catch (PDOException $e) {
    /**
     * If connection fails, display user-friendly error
     * In production, log this error instead of displaying it
     */
    die("Database Connection Failed: " . $e->getMessage());
}

/**
 * Helper function to sanitize user input
 * Prevents XSS (Cross-Site Scripting) attacks
 * 
 * @param string $data - Raw user input
 * @return string - Sanitized output
 */
function sanitize_input($data) {
    $data = trim($data);                    // Remove whitespace
    $data = stripslashes($data);            // Remove backslashes
    $data = htmlspecialchars($data);        // Convert special characters to HTML entities
    return $data;
}

/**
 * Helper function to redirect users
 * 
 * @param string $location - URL to redirect to
 */
function redirect($location) {
    header("Location: " . $location);
    exit();
}

/**
 * Helper function to display formatted date
 * 
 * @param string $date - Date from database
 * @return string - Formatted date
 */
function format_date($date) {
    return date('F d, Y', strtotime($date));
}

/**
 * Helper function to display formatted date and time
 * 
 * @param string $datetime - DateTime from database
 * @return string - Formatted date and time
 */
function format_datetime($datetime) {
    return date('F d, Y g:i A', strtotime($datetime));
}
?>