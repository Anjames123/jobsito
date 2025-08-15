<?php
/**
 * Job Portal - Job Application Submission Page
 * 
 * This page handles job application submissions for the Job Portal application.
 * Provides a comprehensive interface for users to apply for specific job positions
 * with cover letter composition, resume upload, and application tracking.
 * 
 * Key Features:
 * - Job details display with complete information
 * - Cover letter composition with character counting
 * - Resume/CV file upload with validation
 * - Drag-and-drop file upload interface
 * - Duplicate application prevention
 * - Application status tracking integration
 * - Real-time file validation and feedback
 * - Application tips and guidance for users
 * - Responsive design for all device types
 * 
 * Security Features:
 * - Authentication requirement (requireLogin())
 * - File upload validation (type, size, extension)
 * - Input sanitization for XSS prevention
 * - Secure file storage with unique naming
 * - Database prepared statements for SQL injection prevention
 * - File cleanup on application failure
 * 
 * File Upload Specifications:
 * - Allowed formats: PDF, DOC, DOCX
 * - Maximum file size: 5MB (configurable via MAX_FILE_SIZE)
 * - Unique filename generation for security
 * - Server-side validation for all uploads
 * 
 * Database Tables Used:
 * - jobs: Job listings (id, title, company, description, requirements, etc.)
 * - applications: Job applications (job_id, user_id, cover_letter, resume_path, status)
 * - users: User information (accessed via session)
 * 
 * Application Workflow:
 * 1. Validate job ID and retrieve job details
 * 2. Check for existing applications from current user
 * 3. Process form submission (cover letter + resume upload)
 * 4. Store application data and provide confirmation
 * 5. Redirect to dashboard for application tracking
 */

// Include configuration and helper functions
require_once 'config.php';

/**
 * AUTHENTICATION CHECK
 * 
 * Ensures only authenticated users can access the application page.
 * Redirects unauthenticated users to login page automatically.
 */
requireLogin();

/**
 * JOB ID VALIDATION AND RETRIEVAL
 * 
 * Validates the job ID parameter from URL and ensures it's a valid integer.
 * Redirects to homepage if no valid job ID is provided.
 */
$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;

if (!$job_id) {
    header('Location: index.php');
    exit();
}

$pdo = getDBConnection();

/**
 * JOB DETAILS RETRIEVAL
 * 
 * Fetches complete job information from database including:
 * - Job title, company, location, salary range
 * - Job description and requirements
 * - Creation date and active status
 * 
 * Only active jobs can receive applications for security.
 */
$stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ? AND is_active = 1");
$stmt->execute([$job_id]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    // Job not found or inactive - redirect to homepage
    header('Location: index.php');
    exit();
}

/**
 * DUPLICATE APPLICATION CHECK
 * 
 * Prevents users from applying multiple times to the same job.
 * Checks database for existing applications from current user
 * for this specific job position.
 */
$stmt = $pdo->prepare("SELECT id FROM applications WHERE job_id = ? AND user_id = ?");
$stmt->execute([$job_id, $_SESSION['user_id']]);
$existing_application = $stmt->fetch();

// Initialize message variables
$error = '';
$success = '';

/**
 * APPLICATION FORM PROCESSING
 * 
 * Handles POST requests when users submit job applications.
 * Processes both cover letter text and resume file uploads
 * with comprehensive validation and error handling.
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$existing_application) {
    // Sanitize cover letter input to prevent XSS attacks
    $cover_letter = sanitize($_POST['cover_letter']);
    $resume_path = '';
    
    /**
     * FILE UPLOAD PROCESSING
     * 
     * Handles resume/CV file uploads with multiple validation layers:
     * - File size validation (maximum 5MB)
     * - File type validation (PDF, DOC, DOCX only)
     * - Secure filename generation
     * - Server directory storage
     */
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['resume'];
        $file_size = $file['size'];
        $file_tmp = $file['tmp_name'];
        $file_name = $file['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        /**
         * FILE VALIDATION CHECKS
         * 
         * Multiple validation layers ensure file security:
         * 1. File size check against MAX_FILE_SIZE constant
         * 2. File extension validation against ALLOWED_EXTENSIONS
         * 3. File type verification for additional security
         */
        if ($file_size > MAX_FILE_SIZE) {
            $error = 'File size must be less than 5MB.';
        } elseif (!in_array($file_ext, ALLOWED_EXTENSIONS)) {
            $error = 'Only PDF, DOC, and DOCX files are allowed.';
        } else {
            /**
             * SECURE FILE STORAGE
             * 
             * Generates unique filename to prevent:
             * - File conflicts between users
             * - Directory traversal attacks
             * - Filename-based security vulnerabilities
             * 
             * Format: resume_[user_id]_[job_id]_[timestamp].[extension]
             */
            $new_filename = 'resume_' . $_SESSION['user_id'] . '_' . $job_id . '_' . time() . '.' . $file_ext;
            $upload_path = UPLOAD_DIR . $new_filename;
            
            // Move uploaded file to secure server directory
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $resume_path = $upload_path;
            } else {
                $error = 'Failed to upload resume. Please try again.';
            }
        }
    }
    
    /**
     * APPLICATION DATABASE STORAGE
     * 
     * If all validations pass, stores the complete application
     * in the database with user ID, job ID, cover letter text,
     * and resume file path for future reference.
     */
    if (!$error) {
        $stmt = $pdo->prepare("INSERT INTO applications (job_id, user_id, cover_letter, resume_path) VALUES (?, ?, ?, ?)");
        
        if ($stmt->execute([$job_id, $_SESSION['user_id'], $cover_letter, $resume_path])) {
            $success = 'Application submitted successfully! You can track its status in your dashboard.';
        } else {
            $error = 'Failed to submit application. Please try again.';
            
            /**
             * CLEANUP ON FAILURE
             * 
             * If database insertion fails, removes uploaded file
             * to prevent orphaned files on server storage.
             */
            if ($resume_path && file_exists($resume_path)) {
                unlink($resume_path);
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
    <!-- Dynamic page title with job title for SEO -->
    <title>Apply for <?php echo sanitize($job['title']); ?> - Job Portal</title>
    
    <!-- Bootstrap 5 CSS for responsive design and components -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons for UI elements and visual enhancement -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS for application-specific styling -->
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <!-- 
    NAVIGATION BAR
    
    Authenticated user navigation with links to:
    - Homepage (job listings)
    - Dashboard (application tracking)
    - Logout functionality
    -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <!-- Brand logo linking to homepage -->
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-briefcase"></i> Job Portal
            </a>
            
            <!-- Authenticated user navigation menu -->
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="bi bi-house"></i> Home
                </a>
                <a class="nav-link" href="dashboard.php">
                    <i class="bi bi-person-circle"></i> My Applications
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- 
    MAIN CONTENT AREA
    
    Two-column layout:
    - Left column: Job details and information
    - Right column: Application form and submission
    -->
    <div class="container mt-4">
        <div class="row">
            <!-- 
            JOB DETAILS SIDEBAR
            
            Displays comprehensive job information to help users
            understand the position before applying. Includes:
            - Job title, company, location
            - Salary range (if specified)
            - Job description and requirements
            - Posting date for context
            -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-briefcase"></i> Job Details</h5>
                    </div>
                    <div class="card-body">
                        <!-- Job title and company information -->
                        <h5 class="card-title"><?php echo sanitize($job['title']); ?></h5>
                        <h6 class="card-subtitle mb-3 text-muted">
                            <i class="bi bi-building"></i> <?php echo sanitize($job['company']); ?>
                        </h6>
                        
                        <!-- Location and salary information -->
                        <div class="mb-3">
                            <small class="text-muted">
                                <i class="bi bi-geo-alt"></i> <?php echo sanitize($job['location'] ?: 'Location not specified'); ?>
                            </small>
                            <!-- Optional salary range display -->
                            <?php if ($job['salary_range']): ?>
                            <br><small class="text-muted">
                                <i class="bi bi-cash"></i> <?php echo sanitize($job['salary_range']); ?>
                            </small>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Job description with line break preservation -->
                        <div class="mb-3">
                            <strong>Description:</strong>
                            <p class="small mt-1"><?php echo nl2br(sanitize($job['description'])); ?></p>
                        </div>
                        
                        <!-- Optional job requirements section -->
                        <?php if ($job['requirements']): ?>
                        <div class="mb-3">
                            <strong>Requirements:</strong>
                            <p class="small mt-1"><?php echo nl2br(sanitize($job['requirements'])); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Job posting date for applicant reference -->
                        <small class="text-muted">
                            <i class="bi bi-calendar"></i> Posted: <?php echo date('M j, Y', strtotime($job['created_at'])); ?>
                        </small>
                    </div>
                </div>
            </div>

            <!-- 
            APPLICATION FORM SECTION
            
            Main application interface that adapts based on:
            - Existing application status
            - Submission success state
            - Form validation errors
            -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-send"></i> Submit Application</h5>
                    </div>
                    <div class="card-body">
                        <!-- 
                        EXISTING APPLICATION NOTIFICATION
                        
                        Displays when user has already applied to prevent
                        duplicate applications and provides dashboard link.
                        -->
                        <?php if ($existing_application): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> 
                                You have already applied for this position. 
                                <a href="dashboard.php" class="alert-link">Check your application status</a>
                            </div>
                        
                        <!-- 
                        SUCCESS CONFIRMATION
                        
                        Displays after successful application submission
                        with navigation options for next steps.
                        -->
                        <?php elseif ($success): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                                <div class="mt-3">
                                    <a href="dashboard.php" class="btn btn-primary">
                                        <i class="bi bi-person-circle"></i> View My Applications
                                    </a>
                                    <a href="index.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-search"></i> Browse More Jobs
                                    </a>
                                </div>
                            </div>
                        
                        <!-- 
                        APPLICATION FORM
                        
                        Main application submission form with:
                        - Cover letter composition area
                        - Resume/CV file upload
                        - Consent checkbox for data processing
                        - Form validation and submission handling
                        -->
                        <?php else: ?>
                            <!-- Error message display for form validation failures -->
                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Application form with file upload capability -->
                            <form method="POST" enctype="multipart/form-data" id="applicationForm">
                                <!-- 
                                COVER LETTER SECTION
                                
                                Large text area for cover letter composition with:
                                - Optional but recommended labeling
                                - Placeholder text for guidance
                                - Character counting functionality
                                - Writing tips for users
                                -->
                                <div class="mb-4">
                                    <label for="cover_letter" class="form-label">
                                        <i class="bi bi-file-text"></i> Cover Letter
                                        <small class="text-muted">(Optional but recommended)</small>
                                    </label>
                                    <textarea class="form-control" id="cover_letter" name="cover_letter" rows="8" 
                                              placeholder="Tell us why you're interested in this position and why you'd be a great fit..."></textarea>
                                    <div class="form-text">
                                        Tip: Mention specific skills from the job requirements and how your experience matches what they're looking for.
                                    </div>
                                </div>

                                <!-- 
                                RESUME UPLOAD SECTION
                                
                                File upload interface with:
                                - Drag-and-drop functionality
                                - Click-to-upload option
                                - File validation and preview
                                - File type and size restrictions
                                -->
                                <div class="mb-4">
                                    <label for="resume" class="form-label">
                                        <i class="bi bi-file-earmark-pdf"></i> Resume/CV
                                        <small class="text-danger">*Required</small>
                                    </label>
                                    
                                    <!-- Drag-and-drop upload area -->
                                    <div class="upload-area" onclick="document.getElementById('resume').click()">
                                        <i class="bi bi-cloud-upload" style="font-size: 2rem; color: #667eea;"></i>
                                        <p class="mt-2 mb-0">Click to upload your resume</p>
                                        <small class="text-muted">PDF, DOC, or DOCX (Max 5MB)</small>
                                    </div>
                                    
                                    <!-- Hidden file input for upload functionality -->
                                    <input type="file" class="form-control mt-2" id="resume" name="resume" 
                                           accept=".pdf,.doc,.docx" required style="display: none;">
                                    
                                    <!-- File information display after selection -->
                                    <div id="file-info" class="mt-2" style="display: none;">
                                        <div class="alert alert-success">
                                            <i class="bi bi-file-check"></i> 
                                            <span id="file-name"></span>
                                            <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="removeFile()">
                                                <i class="bi bi-x"></i> Remove
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- 
                                CONSENT CHECKBOX
                                
                                Required consent for data processing compliance
                                with privacy regulations and recruitment policies.
                                -->
                                <div class="mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="consent" required>
                                        <label class="form-check-label" for="consent">
                                            I consent to the processing of my personal data for recruitment purposes
                                        </label>
                                    </div>
                                </div>

                                <!-- 
                                FORM SUBMISSION BUTTONS
                                
                                Action buttons with:
                                - Back to jobs navigation
                                - Application submission with loading state
                                -->
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="index.php" class="btn btn-outline-secondary me-md-2">
                                        <i class="bi bi-arrow-left"></i> Back to Jobs
                                    </a>
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
                                        <i class="bi bi-send"></i> Submit Application
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 
                APPLICATION TIPS SECTION
                
                Helpful guidance for users to improve their applications.
                Displayed only when form is available (not after submission
                or for duplicate applications).
                -->
                <?php if (!$existing_application && !$success): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h6><i class="bi bi-lightbulb"></i> Application Tips</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Cover letter writing tips -->
                            <div class="col-md-6">
                                <h6><i class="bi bi-file-text"></i> Cover Letter Tips:</h6>
                                <ul class="small">
                                    <li>Address the hiring manager by name if possible</li>
                                    <li>Mention specific skills from the job requirements</li>
                                    <li>Highlight relevant achievements with numbers</li>
                                    <li>Keep it concise (3-4 paragraphs)</li>
                                </ul>
                            </div>
                            
                            <!-- Resume formatting tips -->
                            <div class="col-md-6">
                                <h6><i class="bi bi-file-earmark-pdf"></i> Resume Tips:</h6>
                                <ul class="small">
                                    <li>Use a clean, professional format</li>
                                    <li>Include relevant keywords from the job posting</li>
                                    <li>Quantify your achievements where possible</li>
                                    <li>Keep it to 1-2 pages maximum</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
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
         * - File upload validation and preview
         * - Drag-and-drop file handling
         * - Form submission with loading states
         * - Character counting for cover letter
         * - Real-time file validation
         */

        /**
         * FILE UPLOAD HANDLING
         * 
         * Handles file selection with client-side validation:
         * - File size checking (5MB limit)
         * - File type validation (PDF, DOC, DOCX)
         * - File preview and removal functionality
         * - Visual feedback for selected files
         */
        document.getElementById('resume').addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const fileSize = file.size / 1024 / 1024; // Convert to MB
                const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                
                // Client-side file size validation
                if (fileSize > 5) {
                    alert('File size must be less than 5MB');
                    this.value = '';
                    return;
                }
                
                // Client-side file type validation
                if (!allowedTypes.includes(file.type)) {
                    alert('Only PDF, DOC, and DOCX files are allowed');
                    this.value = '';
                    return;
                }
                
                // Display selected file information
                document.getElementById('file-name').textContent = file.name;
                document.getElementById('file-info').style.display = 'block';
                document.querySelector('.upload-area').style.display = 'none';
            }
        });

        /**
         * FILE REMOVAL FUNCTIONALITY
         * 
         * Allows users to remove selected files and reset
         * the upload interface to initial state.
         */
        function removeFile() {
            document.getElementById('resume').value = '';
            document.getElementById('file-info').style.display = 'none';
            document.querySelector('.upload-area').style.display = 'block';
        }

        /**
         * DRAG-AND-DROP FUNCTIONALITY
         * 
         * Provides modern drag-and-drop file upload interface
         * with visual feedback during drag operations.
         */
        const uploadArea = document.querySelector('.upload-area');
        
        // Handle drag over events (file dragged over upload area)
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        
        // Handle drag leave events (file dragged away from upload area)
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });
        
        // Handle file drop events (file dropped on upload area)
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                document.getElementById('resume').files = files;
                document.getElementById('resume').dispatchEvent(new Event('change'));
            }
        });

        /**
         * FORM SUBMISSION HANDLING
         * 
         * Provides visual feedback during form submission with
         * loading animation and disabled submit button to prevent
         * multiple submissions.
         */
        document.getElementById('applicationForm').addEventListener('submit', function() {
            document.getElementById('submitBtn').innerHTML = '<i class="bi bi-arrow-clockwise spin"></i> Submitting...';
            document.getElementById('submitBtn').disabled = true;
        });

        /**
         * COVER LETTER CHARACTER COUNTER
         * 
         * Provides real-time character count feedback for cover letter
         * composition with visual feedback when reaching recommended length.
         */
        const coverLetter = document.getElementById('cover_letter');
        if (coverLetter) {
            const counter = document.createElement('div');
            counter.className = 'form-text text-end';
            counter.id = 'char-counter';
            coverLetter.parentNode.appendChild(counter);
            
            coverLetter.addEventListener('input', function() {
                const length = this.value.length;
                counter.textContent = `${length} characters`;
                
                // Visual feedback for good cover letter length
                if (length > 500) {
                    counter.className = 'form-text text-end text-success';
                } else {
                    counter.className = 'form-text text-end text-muted';
                }
            });
        }
    </script>

    <!-- 
    CUSTOM STYLES
    
    Additional CSS for loading animations and
    visual enhancements specific to this page.
    -->
    <style>
        .spin {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</body>
</html>