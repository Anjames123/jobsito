<?php
/**
 * Job Portal - Admin Job Management System
 * 
 * Comprehensive job management interface for administrators providing complete
 * CRUD (Create, Read, Update, Delete) operations for job postings. Features
 * include job creation, editing, status management, and application tracking
 * with advanced data validation and user-friendly interface design.
 * 
 * Key Features:
 * - Job posting creation with rich form validation
 * - Real-time job editing with pre-populated forms
 * - Status toggle (active/inactive) for job visibility control
 * - Safe job deletion with application dependency checking
 * - Application count tracking for each job position
 * - Pending applications monitoring for workflow management
 * - Responsive interface for desktop and mobile management
 * 
 * Job Management Operations:
 * - Add: Create new job postings with complete details
 * - Edit: Modify existing job information and requirements
 * - Toggle Status: Activate/deactivate jobs for application control
 * - Delete: Remove jobs without applications (safety measure)
 * - View Applications: Direct access to job-specific applications
 * 
 * Security Features:
 * - Admin authentication requirement (requireAdmin())
 * - Input sanitization for all form data
 * - Prepared statements for database security
 * - Application dependency validation before deletion
 * - XSS prevention through data sanitization
 * 
 * Database Tables Used:
 * - jobs: Job postings with details, status, and creator tracking
 * - applications: Application submissions linked to specific jobs
 * - users: Admin user identification and permissions
 * 
 * Form Validation:
 * - Required fields: title, company, description
 * - Optional fields: location, salary_range, requirements
 * - Status control: is_active checkbox for visibility
 * - Creator tracking: created_by field for audit trail
 * 
 * @author Your Name
 * @version 1.0
 * @since 2024
 */

// Include configuration and require admin authentication
require_once '../config.php';

/**
 * ADMIN AUTHENTICATION CHECK
 * 
 * Ensures only administrators can access the job management
 * interface. Redirects unauthorized users to appropriate
 * login page with proper security enforcement.
 */
requireAdmin();

$pdo = getDBConnection();
$message = '';
$error = '';

/**
 * FORM SUBMISSION PROCESSING
 * 
 * Handles all job management operations through POST requests
 * with comprehensive validation and error handling. Supports
 * multiple actions through a unified form processing system.
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            /**
             * ADD AND EDIT JOB OPERATIONS
             * 
             * Unified processing for both job creation and editing
             * with comprehensive form validation and data sanitization.
             */
            case 'add':
            case 'edit':
                // Sanitize all form inputs for security
                $title = sanitize($_POST['title']);
                $company = sanitize($_POST['company']);
                $location = sanitize($_POST['location']);
                $salary_range = sanitize($_POST['salary_range']);
                $description = sanitize($_POST['description']);
                $requirements = sanitize($_POST['requirements']);
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                /**
                 * FORM VALIDATION
                 * 
                 * Validates required fields to ensure data integrity
                 * and provide clear feedback for missing information.
                 */
                if (empty($title) || empty($company) || empty($description)) {
                    $error = 'Please fill in all required fields.';
                } else {
                    if ($_POST['action'] == 'add') {
                        /**
                         * JOB CREATION PROCESS
                         * 
                         * Inserts new job posting with admin user tracking
                         * for audit trail and responsibility assignment.
                         */
                        $stmt = $pdo->prepare("INSERT INTO jobs (title, company, location, salary_range, description, requirements, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $success = $stmt->execute([$title, $company, $location, $salary_range, $description, $requirements, $is_active, $_SESSION['user_id']]);
                        $message = $success ? 'Job posted successfully!' : 'Failed to post job.';
                    } else {
                        /**
                         * JOB UPDATE PROCESS
                         * 
                         * Updates existing job information with validation
                         * and maintains data integrity through prepared statements.
                         */
                        $job_id = (int)$_POST['job_id'];
                        $stmt = $pdo->prepare("UPDATE jobs SET title = ?, company = ?, location = ?, salary_range = ?, description = ?, requirements = ?, is_active = ? WHERE id = ?");
                        $success = $stmt->execute([$title, $company, $location, $salary_range, $description, $requirements, $is_active, $job_id]);
                        $message = $success ? 'Job updated successfully!' : 'Failed to update job.';
                    }
                }
                break;
                
            /**
             * STATUS TOGGLE OPERATION
             * 
             * Toggles job active status for visibility control
             * allowing administrators to pause applications
             * without deleting job postings.
             */
            case 'toggle_status':
                $job_id = (int)$_POST['job_id'];
                $stmt = $pdo->prepare("UPDATE jobs SET is_active = NOT is_active WHERE id = ?");
                $success = $stmt->execute([$job_id]);
                $message = $success ? 'Job status updated!' : 'Failed to update status.';
                break;
                
            /**
             * JOB DELETION OPERATION
             * 
             * Safe job deletion with application dependency checking
             * to prevent data integrity issues and inform administrators
             * of jobs with existing applications.
             */
            case 'delete':
                $job_id = (int)$_POST['job_id'];
                
                /**
                 * APPLICATION DEPENDENCY CHECK
                 * 
                 * Prevents deletion of jobs with existing applications
                 * to maintain data integrity and application history.
                 */
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE job_id = ?");
                $stmt->execute([$job_id]);
                $app_count = $stmt->fetchColumn();
                
                if ($app_count > 0) {
                    $error = 'Cannot delete job with existing applications. Deactivate it instead.';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM jobs WHERE id = ?");
                    $success = $stmt->execute([$job_id]);
                    $message = $success ? 'Job deleted successfully!' : 'Failed to delete job.';
                }
                break;
        }
    }
}

/**
 * EDIT MODE DATA RETRIEVAL
 * 
 * Retrieves job data for editing when edit action is requested
 * with proper ID validation and data preparation for form
 * pre-population and user convenience.
 */
$edit_job = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $edit_job = $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * JOBS LIST RETRIEVAL WITH STATISTICS
 * 
 * Comprehensive query retrieving all jobs with application
 * statistics including total applications and pending counts
 * for administrative overview and decision-making support.
 */
$stmt = $pdo->prepare("
    SELECT j.*, 
           COUNT(a.id) as application_count,
           COUNT(CASE WHEN a.status = 'pending' THEN 1 END) as pending_count
    FROM jobs j 
    LEFT JOIN applications a ON j.id = a.job_id 
    GROUP BY j.id 
    ORDER BY j.created_at DESC
");
$stmt->execute();
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Jobs - Job Portal Admin</title>
    
    <!-- Bootstrap 5 CSS for responsive admin interface -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons for admin interface elements -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS with admin-specific styling -->
    <link href="../style.css" rel="stylesheet">
</head>
<body>
    <!-- 
    ADMIN NAVIGATION BAR
    
    Administrative navigation with links to all admin functions
    and clear identification of current job management page.
    -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <!-- Admin brand identification -->
            <a class="navbar-brand" href="../index.php">
                <i class="bi bi-briefcase"></i> Job Portal - Admin
            </a>
            
            <!-- Admin navigation menu -->
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="../index.php"><i class="bi bi-house"></i> Public Site</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="jobs.php"><i class="bi bi-briefcase"></i> Manage Jobs</a></li>
                    <li class="nav-item"><a class="nav-link" href="applications.php"><i class="bi bi-file-earmark-text"></i> Applications</a></li>
                    <li class="nav-item"><a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <!-- 
            JOB FORM SECTION
            
            Unified form for both job creation and editing with
            dynamic interface adaptation based on operation mode.
            Provides comprehensive input fields with validation
            and user-friendly feedback systems.
            -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <!-- Dynamic header based on operation mode -->
                        <h5><i class="bi bi-<?php echo $edit_job ? 'pencil' : 'plus-circle'; ?>"></i> 
                            <?php echo $edit_job ? 'Edit Job' : 'Post New Job'; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Success message display -->
                        <?php if ($message): ?>
                            <div class="alert alert-success"><i class="bi bi-check-circle"></i> <?php echo $message; ?></div>
                        <?php endif; ?>
                        
                        <!-- Error message display -->
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?></div>
                        <?php endif; ?>

                        <!-- Job management form with dynamic action handling -->
                        <form method="POST">
                            <!-- Hidden fields for operation control -->
                            <input type="hidden" name="action" value="<?php echo $edit_job ? 'edit' : 'add'; ?>">
                            <?php if ($edit_job): ?>
                                <input type="hidden" name="job_id" value="<?php echo $edit_job['id']; ?>">
                            <?php endif; ?>

                            <!-- 
                            JOB TITLE FIELD
                            
                            Primary identifier for job position with
                            required validation and pre-population for editing.
                            -->
                            <div class="mb-3">
                                <label for="title" class="form-label">Job Title *</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?php echo $edit_job ? sanitize($edit_job['title']) : ''; ?>" required>
                            </div>

                            <!-- 
                            COMPANY NAME FIELD
                            
                            Employer identification with required validation
                            for job attribution and applicant information.
                            -->
                            <div class="mb-3">
                                <label for="company" class="form-label">Company *</label>
                                <input type="text" class="form-control" id="company" name="company" 
                                       value="<?php echo $edit_job ? sanitize($edit_job['company']) : ''; ?>" required>
                            </div>

                            <!-- 
                            LOCATION FIELD
                            
                            Optional geographic information with placeholder
                            guidance for consistent data entry formatting.
                            -->
                            <div class="mb-3">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" class="form-control" id="location" name="location" 
                                       value="<?php echo $edit_job ? sanitize($edit_job['location']) : ''; ?>" 
                                       placeholder="e.g. New York, Remote">
                            </div>

                            <!-- 
                            SALARY RANGE FIELD
                            
                            Optional compensation information with format
                            guidance for consistent salary representation.
                            -->
                            <div class="mb-3">
                                <label for="salary_range" class="form-label">Salary Range</label>
                                <input type="text" class="form-control" id="salary_range" name="salary_range" 
                                       value="<?php echo $edit_job ? sanitize($edit_job['salary_range']) : ''; ?>" 
                                       placeholder="e.g. $50,000 - $70,000">
                            </div>

                            <!-- 
                            JOB DESCRIPTION FIELD
                            
                            Required detailed job information with textarea
                            for comprehensive position description and
                            character counting for content management.
                            -->
                            <div class="mb-3">
                                <label for="description" class="form-label">Job Description *</label>
                                <textarea class="form-control" id="description" name="description" rows="4" required><?php echo $edit_job ? sanitize($edit_job['description']) : ''; ?></textarea>
                            </div>

                            <!-- 
                            REQUIREMENTS FIELD
                            
                            Optional job requirements and qualifications
                            with textarea for detailed skill and experience
                            specifications with character counting.
                            -->
                            <div class="mb-3">
                                <label for="requirements" class="form-label">Requirements</label>
                                <textarea class="form-control" id="requirements" name="requirements" rows="3"><?php echo $edit_job ? sanitize($edit_job['requirements']) : ''; ?></textarea>
                            </div>

                            <!-- 
                            ACTIVE STATUS CHECKBOX
                            
                            Job visibility control with default active state
                            for new jobs and current state preservation for edits.
                            -->
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                           <?php echo (!$edit_job || $edit_job['is_active']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">
                                        Active (visible to applicants)
                                    </label>
                                </div>
                            </div>

                            <!-- 
                            FORM ACTION BUTTONS
                            
                            Dynamic submit button with operation-specific text
                            and cancel option for edit mode with proper navigation.
                            -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-<?php echo $edit_job ? 'check' : 'plus-circle'; ?>"></i>
                                    <?php echo $edit_job ? 'Update Job' : 'Post Job'; ?>
                                </button>
                                <?php if ($edit_job): ?>
                                    <a href="jobs.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-x"></i> Cancel
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 
            JOBS LIST SECTION
            
            Comprehensive table displaying all jobs with statistics,
            status indicators, and management actions for efficient
            administrative oversight and control.
            -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <!-- Header with job count and status statistics -->
                        <h5 class="mb-0"><i class="bi bi-list"></i> All Jobs (<?php echo count($jobs); ?>)</h5>
                        <div>
                            <span class="badge bg-success"><?php echo count(array_filter($jobs, fn($j) => $j['is_active'])); ?> Active</span>
                            <span class="badge bg-secondary"><?php echo count(array_filter($jobs, fn($j) => !$j['is_active'])); ?> Inactive</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <!-- No jobs state display -->
                        <?php if (empty($jobs)): ?>
                            <div class="p-4 text-center">
                                <i class="bi bi-briefcase" style="font-size: 3rem; color: #ccc;"></i>
                                <p class="mt-2 text-muted">No jobs posted yet</p>
                            </div>
                        
                        <!-- Jobs data table -->
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Job Details</th>
                                            <th>Applications</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($jobs as $job): ?>
                                        <!-- Row with conditional styling for inactive jobs -->
                                        <tr class="<?php echo $job['is_active'] ? '' : 'text-muted'; ?>">
                                            <!-- 
                                            JOB DETAILS COLUMN
                                            
                                            Comprehensive job information display including
                                            title, company, location, and salary with
                                            proper formatting and icon indicators.
                                            -->
                                            <td>
                                                <strong><?php echo sanitize($job['title']); ?></strong>
                                                <br>
                                                <small>
                                                    <i class="bi bi-building"></i> <?php echo sanitize($job['company']); ?>
                                                    <?php if ($job['location']): ?>
                                                        • <i class="bi bi-geo-alt"></i> <?php echo sanitize($job['location']); ?>
                                                    <?php endif; ?>
                                                </small>
                                                <?php if ($job['salary_range']): ?>
                                                    <br><small class="text-success">
                                                        <i class="bi bi-cash"></i> <?php echo sanitize($job['salary_range']); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            
                                            <!-- 
                                            APPLICATIONS STATISTICS COLUMN
                                            
                                            Visual badges showing total applications
                                            and pending count for workload assessment
                                            and priority management.
                                            -->
                                            <td class="text-center">
                                                <div class="badge bg-primary"><?php echo $job['application_count']; ?> Total</div>
                                                <?php if ($job['pending_count'] > 0): ?>
                                                    <div class="badge bg-warning mt-1"><?php echo $job['pending_count']; ?> Pending</div>
                                                <?php endif; ?>
                                            </td>
                                            
                                            <!-- 
                                            STATUS INDICATOR COLUMN
                                            
                                            Color-coded status badges for quick
                                            visual assessment of job visibility.
                                            -->
                                            <td>
                                                <?php if ($job['is_active']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            
                                            <!-- 
                                            CREATION DATE COLUMN
                                            
                                            Job posting date for timeline tracking
                                            and content freshness assessment.
                                            -->
                                            <td>
                                                <small><?php echo date('M j, Y', strtotime($job['created_at'])); ?></small>
                                            </td>
                                            
                                            <!-- 
                                            ACTIONS COLUMN
                                            
                                            Management action buttons with conditional
                                            display based on job status and applications.
                                            -->
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <!-- Edit job button -->
                                                    <a href="jobs.php?action=edit&id=<?php echo $job['id']; ?>" 
                                                       class="btn btn-outline-primary" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    
                                                    <!-- Status toggle button -->
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                        <button type="submit" 
                                                                class="btn btn-outline-<?php echo $job['is_active'] ? 'warning' : 'success'; ?>" 
                                                                title="<?php echo $job['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                            <i class="bi bi-<?php echo $job['is_active'] ? 'pause' : 'play'; ?>"></i>
                                                        </button>
                                                    </form>
                                                    
                                                    <!-- Conditional action button (view applications or delete) -->
                                                    <?php if ($job['application_count'] > 0): ?>
                                                        <a href="applications.php?job_id=<?php echo $job['id']; ?>" 
                                                           class="btn btn-outline-info" title="View Applications">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <button class="btn btn-outline-danger" 
                                                                onclick="deleteJob(<?php echo $job['id']; ?>)" title="Delete">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 
    DELETE CONFIRMATION MODAL
    
    Safety confirmation dialog for job deletion with clear
    warning message and action confirmation to prevent
    accidental data loss.
    -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this job? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="job_id" id="deleteJobId">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JavaScript for interactive components -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        /**
         * CLIENT-SIDE ENHANCEMENT FUNCTIONALITY
         * 
         * JavaScript functions for improved user experience
         * including modal management, form enhancements,
         * and interactive features for the job management interface.
         */

        /**
         * DELETE CONFIRMATION FUNCTION
         * 
         * Shows confirmation modal for job deletion with
         * job ID passing for form submission handling.
         * 
         * @param {number} jobId - The ID of the job to delete
         */
        function deleteJob(jobId) {
            document.getElementById('deleteJobId').value = jobId;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        /**
         * FORM ENHANCEMENT INITIALIZATION
         * 
         * Sets up user experience improvements including
         * auto-focus and character counting for better
         * form interaction and content management.
         */
        
        // Auto-focus title field for immediate input
        document.getElementById('title').focus();

        /**
         * CHARACTER COUNTER IMPLEMENTATION
         * 
         * Adds real-time character counting to textarea fields
         * for content length awareness and writing guidance.
         */
        ['description', 'requirements'].forEach(fieldId => {
            const field = document.getElementById(fieldId);
            const counter = document.createElement('div');
            counter.className = 'form-text text-end';
            field.parentNode.appendChild(counter);
            
            /**
             * COUNTER UPDATE FUNCTION
             * 
             * Updates character count display in real-time
             * as user types in textarea fields.
             */
            function updateCounter() {
                counter.textContent = field.value.length + ' characters';
            }
            
            // Attach event listener and initialize counter
            field.addEventListener('input', updateCounter);
            updateCounter();
        });
    </script>
</body>
</html>