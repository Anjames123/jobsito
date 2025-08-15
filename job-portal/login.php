<?php
/**
 * Job Portal - User Login Page
 * 
 * This page handles user authentication for the Job Portal application.
 * Supports login with either username or email address and includes
 * password visibility toggle, redirect functionality, and demo credentials.
 * 
 * Features:
 * - Dual authentication (username or email)
 * - Secure password verification using PHP's password_verify()
 * - Session management for authenticated users
 * - Redirect functionality to preserve user's intended destination
 * - Password visibility toggle for better UX
 * - Demo credentials display for testing
 * - Responsive Bootstrap design
 * - Input validation and error handling
 * 
 * Security Features:
 * - Prepared statements to prevent SQL injection
 * - Password hashing verification
 * - Input sanitization
 * - Session-based authentication
 * 
 * Database Tables Used:
 * - users: User authentication data (id, username, email, password, first_name, last_name, is_admin)
 * 
 * @author Your Name
 * @version 1.0
 * @since 2024
 */

// Include database configuration and helper functions
require_once 'config.php';

// Initialize error message variable
$error = '';

/**
 * Handle redirect functionality
 * 
 * Captures the intended destination from URL parameter to redirect
 * users after successful login. Defaults to dashboard.php if no
 * redirect is specified. This preserves user workflow when they
 * are redirected to login from protected pages.
 */
$redirect = $_GET['redirect'] ?? 'dashboard.php';

/**
 * FORM PROCESSING
 * 
 * Handles POST request when user submits login form.
 * Performs authentication against database and establishes
 * user session on successful login.
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize user input to prevent XSS attacks
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    /**
     * Input validation
     * 
     * Ensures both username/email and password are provided
     * before attempting database lookup.
     */
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        /**
         * Database authentication lookup
         * 
         * Searches for user by either username OR email address.
         * This dual-field search provides flexibility for users
         * who may remember either their username or email.
         */
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        /**
         * Password verification and session establishment
         * 
         * Uses PHP's password_verify() to securely check the provided
         * password against the stored hash. On successful verification,
         * establishes a complete user session with all necessary data.
         */
        if ($user && password_verify($password, $user['password'])) {
            // Establish user session with complete profile data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['is_admin'] = $user['is_admin']; // Role-based access control
            
            // Redirect to intended destination or dashboard
            header('Location: ' . $redirect);
            exit();
        } else {
            // Authentication failed - generic error message for security
            $error = 'Invalid username/email or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Job Portal</title>
    
    <!-- Bootstrap 5 CSS Framework for responsive design and components -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons for UI elements (person, lock, eye icons) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS for additional styling -->
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <!-- 
    NAVIGATION BAR
    
    Simplified navigation for login page with:
    - Brand logo linking back to homepage
    - Registration link for new users
    -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <!-- Brand logo (returns to homepage) -->
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-briefcase"></i> Job Portal
            </a>
            
            <!-- Register link for new users -->
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="register.php">
                    <i class="bi bi-person-plus"></i> Register
                </a>
            </div>
        </div>
    </nav>

    <!-- 
    MAIN LOGIN FORM
    
    Centered login card with responsive design that adapts
    from full width on mobile to constrained width on desktop.
    -->
    <div class="container mt-5">
        <div class="row justify-content-center">
            <!-- Responsive column sizing: full width on mobile, constrained on larger screens -->
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <!-- Card header with login title and icon -->
                    <div class="card-header text-center">
                        <h4><i class="bi bi-box-arrow-in-right"></i> Login</h4>
                    </div>
                    
                    <div class="card-body">
                        <!-- 
                        ERROR MESSAGE DISPLAY
                        
                        Shows validation errors or authentication failures
                        in a Bootstrap alert component with warning icon.
                        -->
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <!-- 
                        LOGIN FORM
                        
                        POST form that submits to same page for processing.
                        Includes input validation, icon decorations, and
                        password visibility toggle functionality.
                        -->
                        <form method="POST">
                            <!-- 
                            USERNAME/EMAIL INPUT
                            
                            Accepts either username or email for flexibility.
                            Includes Bootstrap input group with person icon
                            and preserves input value on form submission errors.
                            -->
                            <div class="mb-3">
                                <label for="username" class="form-label">Username or Email</label>
                                <div class="input-group">
                                    <!-- Person icon for visual context -->
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    
                                    <!-- Input field with preserved value on error -->
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo isset($_POST['username']) ? sanitize($_POST['username']) : ''; ?>" required>
                                </div>
                            </div>

                            <!-- 
                            PASSWORD INPUT WITH VISIBILITY TOGGLE
                            
                            Standard password field with added functionality:
                            - Lock icon for visual context
                            - Toggle button to show/hide password
                            - Eye icon that changes based on visibility state
                            -->
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <!-- Lock icon for visual context -->
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    
                                    <!-- Password input field -->
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    
                                    <!-- Password visibility toggle button -->
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                                        <i class="bi bi-eye" id="toggleIcon"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Submit button with full width and login icon -->
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </button>
                        </form>

                        <!-- Divider line for visual separation -->
                        <hr>
                        
                        <!-- 
                        REGISTRATION LINK
                        
                        Provides path for new users to create accounts.
                        Centered text with link to registration page.
                        -->
                        <div class="text-center">
                            <p class="mb-0">Don't have an account? <a href="register.php">Register here</a></p>
                        </div>

                        <!-- 
                        DEMO CREDENTIALS SECTION
                        
                        For testing and demonstration purposes, shows
                        available demo accounts. This would typically
                        be removed or hidden in production environment.
                        -->
                        <div class="mt-3">
                            <div class="alert alert-info">
                                <strong>Demo Credentials:</strong><br>
                                <small>
                                    Admin: <code>admin</code> / <code>admin123</code><br>
                                    Or register as a regular user
                                </small>
                            </div>
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
         * - Password visibility toggle
         * - Auto-focus on username field
         */

        /**
         * Toggle password visibility
         * 
         * Switches the password input between 'password' and 'text' types
         * to allow users to see their password while typing. Updates the
         * toggle icon to reflect current visibility state.
         * 
         * This improves user experience by allowing password verification
         * while maintaining security by defaulting to hidden state.
         */
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
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
         * Auto-focus username field on page load
         * 
         * Automatically focuses the username input field when the page loads,
         * improving user experience by allowing immediate typing without
         * requiring a mouse click.
         */
        document.getElementById('username').focus();
    </script>
</body>
</html>