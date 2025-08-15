<?php
/**
 * Job Portal - User Logout Handler
 * 
 * This script handles secure user session termination for the Job Portal application.
 * Provides a clean, secure logout process that completely destroys the user session
 * and redirects to the homepage for immediate feedback.
 * 
 * Functionality:
 * - Complete session destruction to ensure security
 * - Automatic redirection to homepage after logout
 * - Clean session cleanup without user data remnants
 * - Simple, lightweight implementation for optimal performance
 * 
 * Security Features:
 * - Complete session data removal using session_destroy()
 * - Immediate redirection prevents session data exposure
 * - No sensitive information left in browser memory
 * - Clean logout process following security best practices
 * 
 * Usage:
 * - Called when user clicks logout button/link
 * - Can be linked directly from any authenticated page
 * - Automatically handles session cleanup and user redirection
 * - Works with any session-based authentication system
 * 
 * Integration:
 * - Requires config.php for session management setup
 * - Compatible with login.php and register.php authentication flow
 * - Redirects to index.php (homepage) after successful logout
 * - Can be customized to redirect to different pages if needed
 * 
 * @author Your Name
 * @version 1.0
 * @since 2024
 */

/**
 * CONFIGURATION AND DEPENDENCIES
 * 
 * Include the configuration file to ensure proper session
 * management settings are loaded before attempting to
 * destroy the session.
 */
require_once 'config.php';

/**
 * SESSION DESTRUCTION
 * 
 * Completely destroys the current user session, removing all
 * session data including user ID, username, email, role information,
 * and any other stored session variables. This ensures:
 * 
 * 1. Complete logout security - no session data remains
 * 2. Prevention of session hijacking after logout
 * 3. Clean slate for next user login attempt
 * 4. Compliance with security best practices
 * 
 * The session_destroy() function:
 * - Removes all session data from server storage
 * - Invalidates the session ID
 * - Clears $_SESSION superglobal array
 * - Ensures complete session termination
 */
session_destroy();

/**
 * POST-LOGOUT REDIRECTION
 * 
 * Immediately redirects the user to the homepage (index.php)
 * after successful session destruction. This provides:
 * 
 * 1. Immediate user feedback that logout was successful
 * 2. Prevention of users remaining on authenticated pages
 * 3. Clean user experience with automatic navigation
 * 4. Security by moving user away from protected areas
 * 
 * The header() function sends an HTTP redirect response
 * to the browser, causing automatic navigation to the
 * specified location.
 * 
 * Alternative redirect destinations could include:
 * - login.php (redirect to login page)
 * - Custom logout success page
 * - Previous page (if tracking referrer)
 */
header('Location: index.php');

/**
 * SCRIPT TERMINATION
 * 
 * Immediately terminates script execution after sending
 * the redirect header. This prevents any additional code
 * from running and ensures the redirect is processed
 * cleanly by the browser.
 * 
 * Important: Always use exit() after header() redirects
 * to prevent potential security issues or unexpected
 * script behavior.
 */
exit();