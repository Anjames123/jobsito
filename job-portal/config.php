<?php
/**
 * Job Portal Configuration File
 * 
 * This file contains all database configuration, application settings, 
 * utility functions, and security helpers for the job portal system.
 * Must be included in all pages that require database access or authentication.
 */

// =============================================================================
// DATABASE CONFIGURATION CONSTANTS
// =============================================================================

// Database host - typically 'localhost' for local development
define('DB_HOST', 'localhost');

// Database username - 'root' is default for local MySQL installations
define('DB_USER', 'root');

// Database password - empty string for local development (not recommended for production)
define('DB_PASS', '');

// Database name - the specific database containing job portal tables
define('DB_NAME', 'job_portal');

// =============================================================================
// APPLICATION SETTINGS CONSTANTS
// =============================================================================

// Directory path for storing uploaded files (resumes, documents)
// Trailing slash included for proper path construction
define('UPLOAD_DIR', 'uploads/');

// Maximum allowed file size for uploads (5MB = 5 * 1024 * 1024 bytes)
// Prevents server overload and storage issues
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Array of allowed file extensions for security
// Only PDF and Word documents permitted for resume uploads
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx']);

// =============================================================================
// DATABASE CONNECTION FUNCTION
// =============================================================================

/**
 * Establishes and returns a PDO database connection
 * 
 * Uses the constants defined above to create a MySQL connection.
 * Sets error mode to exceptions for better error handling.
 * 
 * @return PDO Database connection object
 * @throws PDOException If connection fails
 */
function getDBConnection() {
    try {
        // Create new PDO instance with MySQL driver
        // Concatenates host and database name from constants
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        
        // Set error mode to throw exceptions instead of silent failures
        // This enables proper error handling throughout the application
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Return the configured PDO connection object
        return $pdo;
    } catch(PDOException $e) {
        // Terminate script execution with error message if connection fails
        // In production, this should log errors instead of displaying them
        die("Connection failed: " . $e->getMessage());
    }
}

// =============================================================================
// SESSION MANAGEMENT
// =============================================================================

// Check if a session is not already active before starting one
// PHP_SESSION_NONE indicates no session is currently active
// Prevents "session already started" warnings
if (session_status() == PHP_SESSION_NONE) {
    // Start PHP session for user authentication and data storage
    session_start();
}

// =============================================================================
// AUTHENTICATION HELPER FUNCTIONS
// =============================================================================

/**
 * Check if a user is currently logged in
 * 
 * Determines login status by checking for user_id in session.
 * This is the primary method for authentication verification.
 * 
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    // Return true if user_id exists in session array
    return isset($_SESSION['user_id']);
}

/**
 * Check if the current user has administrator privileges
 * 
 * Verifies both that user is logged in and has admin flag set.
 * Uses strict comparison (==) to ensure admin flag equals 1.
 * 
 * @return bool True if user is admin, false otherwise
 */
function isAdmin() {
    // Check if is_admin session variable exists and equals 1
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

/**
 * Enforce login requirement for protected pages
 * 
 * Redirects unauthenticated users to login page.
 * Should be called at the top of any page requiring authentication.
 * Uses exit() to prevent further code execution after redirect.
 */
function requireLogin() {
    // If user is not logged in, redirect to login page
    if (!isLoggedIn()) {
        // Send HTTP redirect header to login page
        header('Location: login.php');
        // Stop script execution to prevent content from being served
        exit();
    }
}

/**
 * Enforce administrator access requirement
 * 
 * First ensures user is logged in, then checks for admin privileges.
 * Redirects non-admin users to main index page.
 * Used to protect administrative functionality.
 */
function requireAdmin() {
    // First ensure user is logged in (calls requireLogin internally)
    requireLogin();
    
    // If user is not admin, redirect to main page
    if (!isAdmin()) {
        // Send HTTP redirect header to main page
        header('Location: index.php');
        // Stop script execution to prevent unauthorized access
        exit();
    }
}

// =============================================================================
// SECURITY AND INPUT SANITIZATION
// =============================================================================

/**
 * Sanitize user input to prevent XSS attacks
 * 
 * Applies multiple sanitization layers:
 * 1. trim() - Removes whitespace from beginning and end
 * 2. strip_tags() - Removes HTML and PHP tags
 * 3. htmlspecialchars() - Converts special characters to HTML entities
 * 
 * @param string $input Raw user input to sanitize
 * @return string Sanitized output safe for display
 */
function sanitize($input) {
    // Apply three levels of sanitization for comprehensive XSS prevention
    return htmlspecialchars(strip_tags(trim($input)));
}

// =============================================================================
// FILE SYSTEM INITIALIZATION
// =============================================================================

// Check if the uploads directory exists, create it if it doesn't
if (!file_exists(UPLOAD_DIR)) {
    // Create directory with full permissions (0777) and recursive flag (true)
    // Recursive flag allows creation of nested directories if needed
    // Note: 0777 permissions should be restricted in production environments
    mkdir(UPLOAD_DIR, 0777, true);
}
?>