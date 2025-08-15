<?php
/**
 * Job Portal - User Registration Page
 * 
 * This page handles new user account creation for the Job Portal application.
 * Includes comprehensive form validation, duplicate checking, secure password
 * hashing, and user-friendly error/success messaging.
 * 
 * Features:
 * - Complete user profile registration (name, username, email, phone)
 * - Comprehensive server-side validation
 * - Client-side password confirmation validation
 * - Duplicate username/email prevention
 * - Secure password hashing with PHP password_hash()
 * - Password visibility toggle for both password fields
 * - Real-time password match verification
 * - Terms of service acceptance requirement
 * - Responsive Bootstrap design
 * - Auto-focus and user experience enhancements
 * 
 * Security Features:
 * - Input sanitization to prevent XSS attacks
 * - Prepared statements to prevent SQL injection
 * - Password strength requirements (minimum 6 characters)
 * - Email format validation
 * - Secure password hashing with PASSWORD_DEFAULT algorithm
 * 
 * Database Tables Used:
 * - users: User account storage (id, username, email, password, first_name, last_name, phone, is_admin, created_at)
 * 
 * Validation Rules:
 * - Required fields: username, email, password, first_name, last_name
 * - Optional fields: phone
 * - Password minimum length: 6 characters
 * - Email format validation
 * - Password confirmation match
 * - Unique username and email enforcement
 * 
 * @author Your Name
 * @version 1.0
 * @since 2024
 */

// Include database configuration and helper functions
require_once 'config.php';

// Initialize message variables
$error = '';
$success = '';

/**
 * FORM PROCESSING
 * 
 * Handles POST request when user submits registration form.
 * Performs comprehensive validation, duplicate checking, and
 * secure user account creation.
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    /**
     * Input sanitization
     * 
     * All user inputs are sanitized using the sanitize() helper function
     * to prevent XSS attacks. Password fields are not sanitized to preserve
     * special characters that users may include in their passwords.
     */
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password']; // Raw password for hashing
    $confirm_password = $_POST['confirm_password']; // Raw password for comparison
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $phone = sanitize($_POST['phone']); // Optional field
    
    /**
     * SERVER-SIDE VALIDATION
     * 
     * Comprehensive validation chain that checks:
     * 1. Required field completion
     * 2. Email format validity
     * 3. Password strength requirements
     * 4. Password confirmation match
     * 5. Username/email uniqueness
     */
    
    // Check required fields completion
    if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        $error = 'Please fill in all required fields.';
    } 
    // Validate email format using PHP's built-in filter
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } 
    // Enforce password strength requirement
    elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } 
    // Verify password confirmation match
    elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } 
    // All validations passed - proceed with database operations
    else {
        $pdo = getDBConnection();
        
        /**
         * Duplicate account prevention
         * 
         * Checks if the provided username or email already exists
         * in the database to enforce uniqueness constraints.
         * Uses prepared statement for security.
         */
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            // Username or email already exists
            $error = 'Username or email already exists.';
        } else {
            /**
             * User account creation
             * 
             * Creates new user account with:
             * - Secure password hashing using PASSWORD_DEFAULT algorithm
             * - Complete profile information storage
             * - Default user role (is_admin = 0)
             * - Automatic timestamp creation
             */
            
            // Hash password securely using PHP's password_hash()
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user into database
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name, phone) VALUES (?, ?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$username, $email, $hashed_password, $first_name, $last_name, $phone])) {
                // Registration successful
                $success = 'Registration successful! You can now login.';
            } else {
                // Database insertion failed
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Job Portal</title>
    
    <!-- Bootstrap 5 CSS Framework for responsive design and components -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons for UI elements (person, envelope, lock icons) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS for additional styling -->
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <!-- 
    NAVIGATION BAR
    
    Simplified navigation for registration page with:
    - Brand logo linking back to homepage
    - Login link for existing users
    -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <!-- Brand logo (returns to homepage) -->
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-briefcase"></i> Job Portal
            </a>
            
            <!-- Login link for existing users -->
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="login.php">
                    <i class="bi bi-box-arrow-in-right"></i> Login
                </a>
            </div>
        </div>
    </nav>

    <!-- 
    MAIN REGISTRATION FORM
    
    Responsive registration form that adapts from full width on mobile
    to constrained width on desktop. Uses Bootstrap card component
    for clean, organized presentation.
    -->
    <div class="container mt-5">
        <div class="row justify-content-center">
            <!-- Responsive column sizing: larger than login form to accommodate more fields -->
            <div class="col-md-8 col-lg-6">
                <div class="card">
                    <!-- Card header with registration title and icon -->
                    <div class="card-header text-center">
                        <h4><i class="bi bi-person-plus"></i> Create Account</h4>
                    </div>
                    
                    <div class="card-body">
                        <!-- 
                        ERROR MESSAGE DISPLAY
                        
                        Shows validation errors or registration failures
                        in a Bootstrap alert component with warning icon.
                        -->
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <!-- 
                        SUCCESS MESSAGE DISPLAY
                        
                        Shows successful registration confirmation with
                        direct link to login page for immediate access.
                        -->
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                                <a href="login.php" class="alert-link">Click here to login</a>
                            </div>
                        <?php endif; ?>

                        <!-- 
                        REGISTRATION FORM
                        
                        Comprehensive form with multiple sections:
                        - Personal information (name fields)
                        - Account credentials (username, email, password)
                        - Contact information (phone - optional)
                        - Terms acceptance (required)
                        
                        Features client-side validation and server-side processing.
                        -->
                        <form method="POST" id="registerForm">
                            <!-- 
                            PERSONAL INFORMATION SECTION
                            
                            First and last name fields in responsive row layout.
                            Required fields marked with asterisk (*).
                            Preserves input values on form submission errors.
                            -->
                            <div class="row">
                                <!-- First name input -->
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                               value="<?php echo isset($_POST['first_name']) ? sanitize($_POST['first_name']) : ''; ?>" required>
                                    </div>
                                </div>

                                <!-- Last name input -->
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                               value="<?php echo isset($_POST['last_name']) ? sanitize($_POST['last_name']) : ''; ?>" required>
                                    </div>
                                </div>
                            </div>

                            <!-- 
                            USERNAME INPUT
                            
                            Unique identifier for user account with helpful text.
                            Includes @ symbol icon for visual context.
                            -->
                            <div class="mb-3">
                                <label for="username" class="form-label">Username *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-at"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo isset($_POST['username']) ? sanitize($_POST['username']) : ''; ?>" required>
                                </div>
                                <div class="form-text">Choose a unique username for your account.</div>
                            </div>

                            <!-- 
                            EMAIL ADDRESS INPUT
                            
                            Email field with built-in HTML5 validation and envelope icon.
                            Used for account verification and login alternative.
                            -->
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>" required>
                                </div>
                            </div>

                            <!-- 
                            PHONE NUMBER INPUT (OPTIONAL)
                            
                            Optional contact field with telephone icon.
                            Preserves value on form errors but not required for registration.
                            -->
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo isset($_POST['phone']) ? sanitize($_POST['phone']) : ''; ?>">
                                </div>
                            </div>

                            <!-- 
                            PASSWORD SECTION
                            
                            Password and confirmation fields in responsive row layout.
                            Both fields include visibility toggle functionality.
                            Includes password strength requirement information.
                            -->
                            <div class="row">
                                <!-- Password input with visibility toggle -->
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password', 'toggleIcon1')">
                                            <i class="bi bi-eye" id="toggleIcon1"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Minimum 6 characters required.</div>
                                </div>

                                <!-- Password confirmation with visibility toggle -->
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password', 'toggleIcon2')">
                                            <i class="bi bi-eye" id="toggleIcon2"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- 
                            TERMS OF SERVICE ACCEPTANCE
                            
                            Required checkbox for legal compliance.
                            Form cannot be submitted without acceptance.
                            -->
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="terms" required>
                                    <label class="form-check-label" for="terms">
                                        I agree to the Terms of Service and Privacy Policy
                                    </label>
                                </div>
                            </div>

                            <!-- Submit button with full width and registration icon -->
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-person-plus"></i> Create Account
                            </button>
                        </form>

                        <!-- Divider line for visual separation -->
                        <hr>
                        
                        <!-- 
                        LOGIN LINK FOR EXISTING USERS
                        
                        Provides navigation for users who already have accounts.
                        Centered text with link to login page.
                        -->
                        <div class="text-center">
                            <p class="mb-0">Already have an account? <a href="login.php">Login here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JavaScript for interactive components -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        /**
         * CLIENT-SIDE FUNCTIONALITY
         * 
         * Provides enhanced user experience features:
         * - Password visibility toggle for both password fields
         * - Real-time password confirmation validation
         * - Form submission validation
         * - Auto-focus on first field
         */

        /**
         * Toggle password visibility for any password field
         * 
         * Generic function that can handle multiple password fields
         * by accepting field ID and icon ID parameters. Switches
         * between 'password' and 'text' input types and updates
         * the corresponding eye icon.
         * 
         * @param {string} fieldId - ID of the password input field
         * @param {string} iconId - ID of the toggle icon element
         */
        function togglePassword(fieldId, iconId) {
            const passwordField = document.getElementById(fieldId);
            const toggleIcon = document.getElementById(iconId);
            
            if (passwordField.type === 'password') {
                // Show password and update icon to 'eye-slash'
                passwordField.type = 'text';
                toggleIcon.className = 'bi bi-eye-slash';
            } else {
                // Hide password and update icon to 'eye'
                passwordField.type = 'password';
                toggleIcon.className = 'bi bi-eye';
            }
        }

        /**
         * Form submission validation
         * 
         * Client-side validation that runs before form submission
         * to catch password mismatch errors immediately. Provides
         * instant feedback and focuses the problematic field.
         */
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault(); // Stop form submission
                alert('Passwords do not match!');
                document.getElementById('confirm_password').focus();
            }
        });

        /**
         * Real-time password confirmation validation
         * 
         * Provides immediate feedback as user types in the password
         * confirmation field. Uses HTML5 custom validity API to
         * show browser-native validation messages.
         */
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                // Set custom validation message for mismatch
                this.setCustomValidity('Passwords do not match');
            } else {
                // Clear validation message when passwords match
                this.setCustomValidity('');
            }
        });

        /**
         * Auto-focus first field on page load
         * 
         * Automatically focuses the first name input field when the page loads,
         * improving user experience by allowing immediate typing without
         * requiring a mouse click.
         */
        document.getElementById('first_name').focus();
    </script>
</body>
</html>