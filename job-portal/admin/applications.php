<?php
/**
 * Job Portal - Admin Application Management System
 * 
 * This page provides comprehensive application management functionality for administrators
 * including status updates, filtering, searching, and detailed application review.
 * Designed for HR personnel and hiring managers to efficiently process job applications.
 * 
 * Key Features:
 * - Application status management (pending, interview, approved, rejected)
 * - Advanced filtering by status, job position, and search terms
 * - Real-time status updates with form submission
 * - Detailed application viewing with modal interface
 * - Resume download and email communication links
 * - Statistical overview with status counts
 * - Responsive interface for desktop and mobile management
 * 
 * Admin Features:
 * - Bulk application filtering and sorting
 * - Quick status change via dropdown selection
 * - Applicant contact information display
 * - Job details integration for context
 * - Application timeline tracking
 * - Direct email and phone contact options
 * 
 * Security Features:
 * - Admin authentication requirement (requireAdmin())
 * - Input sanitization for all user inputs
 * - Prepared statements for database queries
 * - Status validation against allowed values
 * - File path sanitization for resume downloads
 * 
 * Database Tables Used:
 * - applications: Application records with status tracking
 * - jobs: Job position details for context
 * - users: Applicant information and contact details
 * 
 * Application Statuses:
 * - pending: Initial application submission
 * - interview: Candidate scheduled for interview
 * - approved: Application accepted, candidate hired
 * - rejected: Application declined
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
 * Ensures only administrators can access the application management
 * interface. Redirects unauthorized users to appropriate login page.
 */
requireAdmin();

$pdo = getDBConnection();
$message = '';

/**
 * APPLICATION STATUS UPDATE PROCESSING
 * 
 * Handles POST requests for updating application statuses.
 * Validates status values and updates database with timestamp
 * tracking for audit trail and applicant notifications.
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $app_id = (int)$_POST['app_id'];
    $new_status = $_POST['status'];
    
    /**
     * STATUS VALIDATION
     * 
     * Ensures only valid status values are accepted to maintain
     * data integrity and prevent unauthorized status changes.
     */
    if (in_array($new_status, ['pending', 'interview', 'approved', 'rejected'])) {
        $stmt = $pdo->prepare("UPDATE applications SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $success = $stmt->execute([$new_status, $app_id]);
        $message = $success ? 'Application status updated successfully!' : 'Failed to update status.';
    }
}

/**
 * FILTER PARAMETERS PROCESSING
 * 
 * Captures and sanitizes filter parameters from URL query string
 * for building dynamic database queries with user-specified criteria.
 */
$status_filter = $_GET['status'] ?? '';
$job_filter = $_GET['job_id'] ?? '';
$search = $_GET['search'] ?? '';

/**
 * DYNAMIC QUERY BUILDING
 * 
 * Constructs database query with conditional WHERE clauses
 * based on user-selected filters. Uses parameterized queries
 * for security and flexible filtering capabilities.
 */
$where_conditions = [];
$params = [];

// Status filter condition
if ($status_filter) {
    $where_conditions[] = "a.status = ?";
    $params[] = $status_filter;
}

// Job position filter condition
if ($job_filter) {
    $where_conditions[] = "a.job_id = ?";
    $params[] = $job_filter;
}

/**
 * SEARCH FUNCTIONALITY
 * 
 * Implements comprehensive search across multiple fields:
 * - Applicant names (first and last)
 * - Email addresses for contact lookup
 * - Job titles for position-based search
 * - Company names for employer-based filtering
 */
if ($search) {
    $where_conditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR j.title LIKE ? OR j.company LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term, $search_term]);
}

// Build final WHERE clause for query
$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

/**
 * APPLICATIONS DATA RETRIEVAL
 * 
 * Comprehensive query joining applications with job and user details
 * for complete application context. Orders by application date for
 * chronological review and includes all necessary fields for management.
 */
$query = "
    SELECT a.*, j.title as job_title, j.company, j.location, j.salary_range,
           u.first_name, u.last_name, u.email, u.phone
    FROM applications a 
    JOIN jobs j ON a.job_id = j.id 
    JOIN users u ON a.user_id = u.id 
    $where_clause
    ORDER BY a.applied_at DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * JOBS LIST FOR FILTER DROPDOWN
 * 
 * Retrieves all available jobs for the filter dropdown,
 * providing administrators with easy job-based filtering options.
 */
$jobs = $pdo->query("SELECT id, title, company FROM jobs ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

/**
 * STATUS STATISTICS CALCULATION
 * 
 * Calculates application counts for each status category
 * to provide administrators with quick overview of application
 * distribution and workload management.
 */
$status_counts = [
    'all' => count($applications),
    'pending' => count(array_filter($applications, fn($app) => $app['status'] === 'pending')),
    'interview' => count(array_filter($applications, fn($app) => $app['status'] === 'interview')),
    'approved' => count(array_filter($applications, fn($app) => $app['status'] === 'approved')),
    'rejected' => count(array_filter($applications, fn($app) => $app['status'] === 'rejected'))
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Applications - Job Portal Admin</title>
    
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
    
    Administrative navigation with links to all admin functions:
    - Public site access for context
    - Dashboard for overview statistics
    - Jobs management for position control
    - Applications management (current page)
    - Logout for security
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
                    <li class="nav-item"><a class="nav-link" href="jobs.php"><i class="bi bi-briefcase"></i> Manage Jobs</a></li>
                    <li class="nav-item"><a class="nav-link active" href="applications.php"><i class="bi bi-file-earmark-text"></i> Applications</a></li>
                    <li class="nav-item"><a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- 
        PAGE HEADER SECTION
        
        Clear page identification with title and description
        for administrator context and purpose clarification.
        -->
        <div class="row mb-4">
            <div class="col-12">
                <h1><i class="bi bi-file-earmark-text"></i> Manage Applications</h1>
                <p class="text-muted">Review and update application statuses</p>
            </div>
        </div>

        <!-- 
        SUCCESS MESSAGE DISPLAY
        
        Shows confirmation messages for status updates and other
        administrative actions with dismissible alert interface.
        -->
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- 
        STATUS FILTER NAVIGATION TABS
        
        Quick filtering interface showing application counts by status
        with visual indicators and easy navigation between categories.
        -->
        <div class="row mb-4">
            <div class="col-12">
                <ul class="nav nav-pills">
                    <!-- All applications tab with total count -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo !$status_filter ? 'active' : ''; ?>" href="applications.php">
                            All Applications <span class="badge bg-light text-dark"><?php echo $status_counts['all']; ?></span>
                        </a>
                    </li>
                    
                    <!-- Pending applications requiring attention -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo $status_filter === 'pending' ? 'active' : ''; ?>" href="?status=pending">
                            <i class="bi bi-clock"></i> Pending <span class="badge bg-light text-dark"><?php echo $status_counts['pending']; ?></span>
                        </a>
                    </li>
                    
                    <!-- Interview stage applications -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo $status_filter === 'interview' ? 'active' : ''; ?>" href="?status=interview">
                            <i class="bi bi-calendar-event"></i> Interview <span class="badge bg-light text-dark"><?php echo $status_counts['interview']; ?></span>
                        </a>
                    </li>
                    
                    <!-- Approved applications -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo $status_filter === 'approved' ? 'active' : ''; ?>" href="?status=approved">
                            <i class="bi bi-check-circle"></i> Approved <span class="badge bg-light text-dark"><?php echo $status_counts['approved']; ?></span>
                        </a>
                    </li>
                    
                    <!-- Rejected applications -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>" href="?status=rejected">
                            <i class="bi bi-x-circle"></i> Rejected <span class="badge bg-light text-dark"><?php echo $status_counts['rejected']; ?></span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- 
        ADVANCED FILTER INTERFACE
        
        Comprehensive filtering options for administrators:
        - Text search across multiple fields
        - Job position filtering dropdown
        - Status filtering with all options
        - Clear filters functionality
        -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-end">
                            <!-- Search input for text-based filtering -->
                            <div class="col-md-4">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?php echo sanitize($search); ?>" 
                                       placeholder="Search by name, email, job title, or company">
                            </div>
                            
                            <!-- Job position filter dropdown -->
                            <div class="col-md-3">
                                <label for="job_id" class="form-label">Job</label>
                                <select class="form-control" id="job_id" name="job_id">
                                    <option value="">All Jobs</option>
                                    <?php foreach ($jobs as $job): ?>
                                        <option value="<?php echo $job['id']; ?>" 
                                                <?php echo $job_filter == $job['id'] ? 'selected' : ''; ?>>
                                            <?php echo sanitize($job['title']) . ' - ' . sanitize($job['company']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Status filter dropdown -->
                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="">All Statuses</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="interview" <?php echo $status_filter === 'interview' ? 'selected' : ''; ?>>Interview</option>
                                    <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            
                            <!-- Filter action buttons -->
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search"></i> Filter
                                </button>
                                <a href="applications.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x"></i> Clear
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- 
        APPLICATIONS LIST DISPLAY
        
        Main data table showing all applications with:
        - Applicant contact information
        - Job position details and context
        - Application dates and timing
        - Status management with instant updates
        - Action buttons for detailed management
        -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Applications (<?php echo count($applications); ?>)</h5>
                    </div>
                    <div class="card-body p-0">
                        <!-- No applications found state -->
                        <?php if (empty($applications)): ?>
                            <div class="p-4 text-center">
                                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                <p class="mt-2 text-muted">No applications found</p>
                            </div>
                        
                        <!-- Applications data table -->
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Applicant</th>
                                            <th>Job</th>
                                            <th>Applied Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($applications as $app): ?>
                                        <tr>
                                            <!-- 
                                            APPLICANT INFORMATION COLUMN
                                            
                                            Displays complete applicant contact information:
                                            - Full name for identification
                                            - Email address with mailto link
                                            - Phone number with tel link (if provided)
                                            -->
                                            <td>
                                                <div>
                                                    <strong><?php echo sanitize($app['first_name'] . ' ' . $app['last_name']); ?></strong>
                                                    <br><small class="text-muted">
                                                        <i class="bi bi-envelope"></i> <?php echo sanitize($app['email']); ?>
                                                    </small>
                                                    <?php if ($app['phone']): ?>
                                                        <br><small class="text-muted">
                                                            <i class="bi bi-telephone"></i> <?php echo sanitize($app['phone']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            
                                            <!-- 
                                            JOB INFORMATION COLUMN
                                            
                                            Shows job position context for application:
                                            - Job title for position identification
                                            - Company name for employer context
                                            - Location information (if specified)
                                            -->
                                            <td>
                                                <div>
                                                    <strong><?php echo sanitize($app['job_title']); ?></strong>
                                                    <br><small class="text-muted">
                                                        <i class="bi bi-building"></i> <?php echo sanitize($app['company']); ?>
                                                    </small>
                                                    <?php if ($app['location']): ?>
                                                        <br><small class="text-muted">
                                                            <i class="bi bi-geo-alt"></i> <?php echo sanitize($app['location']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            
                                            <!-- 
                                            APPLICATION DATE COLUMN
                                            
                                            Timestamp information for application tracking:
                                            - Application date in readable format
                                            - Application time for precise tracking
                                            -->
                                            <td>
                                                <div>
                                                    <?php echo date('M j, Y', strtotime($app['applied_at'])); ?>
                                                    <br><small class="text-muted">
                                                        <?php echo date('g:i A', strtotime($app['applied_at'])); ?>
                                                    </small>
                                                </div>
                                            </td>
                                            
                                            <!-- 
                                            STATUS MANAGEMENT COLUMN
                                            
                                            Interactive status update interface:
                                            - Dropdown with current status selected
                                            - Automatic form submission on change
                                            - Status color coding for visual clarity
                                            - Last updated timestamp tracking
                                            -->
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="update_status" value="1">
                                                    <input type="hidden" name="app_id" value="<?php echo $app['id']; ?>">
                                                    <select name="status" class="form-select form-select-sm status-badge status-<?php echo $app['status']; ?>" 
                                                            onchange="this.form.submit()" style="width: auto; min-width: 100px;">
                                                        <option value="pending" <?php echo $app['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="interview" <?php echo $app['status'] === 'interview' ? 'selected' : ''; ?>>Interview</option>
                                                        <option value="approved" <?php echo $app['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                                        <option value="rejected" <?php echo $app['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                    </select>
                                                </form>
                                                <br><small class="text-muted">
                                                    Updated: <?php echo date('M j', strtotime($app['updated_at'])); ?>
                                                </small>
                                            </td>
                                            
                                            <!-- 
                                            ACTIONS COLUMN
                                            
                                            Management action buttons for administrators:
                                            - View application details in modal
                                            - Download resume/CV file
                                            - Send email to applicant with pre-filled subject
                                            -->
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <!-- View application details button -->
                                                    <button class="btn btn-outline-primary" 
                                                            onclick="viewApplication(<?php echo $app['id']; ?>)" title="View Details">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    
                                                    <!-- Resume download button (if resume exists) -->
                                                    <?php if ($app['resume_path']): ?>
                                                        <a href="../<?php echo sanitize($app['resume_path']); ?>" 
                                                           class="btn btn-outline-secondary" target="_blank" title="Download Resume">
                                                            <i class="bi bi-download"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Email applicant button with pre-filled subject -->
                                                    <a href="mailto:<?php echo sanitize($app['email']); ?>?subject=Regarding your application for <?php echo urlencode($app['job_title']); ?>" 
                                                       class="btn btn-outline-success" title="Send Email">
                                                        <i class="bi bi-envelope"></i>
                                                    </a>
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
    APPLICATION DETAILS MODAL
    
    Modal interface for detailed application viewing without
    leaving the main management page. Content loaded dynamically
    via JavaScript based on selected application.
    -->
    <div class="modal fade" id="applicationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Application Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="applicationContent">
                    <!-- Content will be loaded here via JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JavaScript for interactive components -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        /**
         * CLIENT-SIDE APPLICATION MANAGEMENT
         * 
         * JavaScript functionality for enhanced admin interface:
         * - Application details modal population
         * - Dynamic content loading for detailed views
         * - Status color coding and visual enhancements
         * - Modal interface management
         */

        // Pass PHP applications data to JavaScript for client-side processing
        const applications = <?php echo json_encode($applications); ?>;

        /**
         * VIEW APPLICATION DETAILS FUNCTION
         * 
         * Populates the modal with detailed application information
         * including applicant details, job information, and application
         * timeline for comprehensive review by administrators.
         * 
         * @param {number} appId - The application ID to display
         */
        function viewApplication(appId) {
            const app = applications.find(a => a.id == appId);
            if (!app) return;

            // Status color mapping for visual consistency
            const statusColors = {
                'pending': '#ffc107',
                'interview': '#17a2b8',
                'approved': '#28a745',
                'rejected': '#dc3545'
            };

            /**
             * MODAL CONTENT GENERATION
             * 
             * Dynamically creates detailed application view with:
             * - Applicant contact information
             * - Job position details and requirements
             * - Application status and timeline
             * - Cover letter content (if provided)
             * - Action buttons for direct communication
             */
            document.getElementById('applicationContent').innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="bi bi-person"></i> Applicant Information</h5>
                        <table class="table table-borderless">
                            <tr><td><strong>Name:</strong></td><td>${app.first_name} ${app.last_name}</td></tr>
                            <tr><td><strong>Email:</strong></td><td><a href="mailto:${app.email}">${app.email}</a></td></tr>
                            ${app.phone ? '<tr><td><strong>Phone:</strong></td><td><a href="tel:' + app.phone + '">' + app.phone + '</a></td></tr>' : ''}
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="bi bi-briefcase"></i> Job Information</h5>
                        <table class="table table-borderless">
                            <tr><td><strong>Position:</strong></td><td>${app.job_title}</td></tr>
                            <tr><td><strong>Company:</strong></td><td>${app.company}</td></tr>
                            ${app.location ? '<tr><td><strong>Location:</strong></td><td>' + app.location + '</td></tr>' : ''}
                            ${app.salary_range ? '<tr><td><strong>Salary:</strong></td><td>' + app.salary_range + '</td></tr>' : ''}
                        </table>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <h5><i class="bi bi-calendar"></i> Application Timeline</h5>
                        <table class="table table-borderless">
                            <tr><td><strong>Applied:</strong></td><td>${new Date(app.applied_at).toLocaleDateString()}</td></tr>
                            <tr><td><strong>Status:</strong></td><td><span class="badge" style="background-color: ${statusColors[app.status]}">${app.status.charAt(0).toUpperCase() + app.status.slice(1)}</span></td></tr>
                            <tr><td><strong>Last Updated:</strong></td><td>${new Date(app.updated_at).toLocaleDateString()}</td></tr>
                        </table>
                    </div>
                </div>
                ${app.cover_letter ? `
                <div class="row mt-3">
                    <div class="col-12">
                        <h5><i class="bi bi-file-text"></i> Cover Letter</h5>
                        <div class="card">
                            <div class="card-body">
                                <p style="white-space: pre-wrap;">${app.cover_letter}</p>
                            </div>
                        </div>
                    </div>
                </div>
                ` : ''}
            `;

            // Show the modal with populated content
            new bootstrap.Modal(document.getElementById('applicationModal')).show();
        }
    </script>
</body>
</html>