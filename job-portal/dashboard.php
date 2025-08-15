<?php
/**
 * Job Portal Dashboard - User Applications Management
 * 
 * This file displays a user's job applications with statistics and management features.
 * Includes authentication, database queries, responsive UI, and modal interactions.
 */

// Include configuration file containing database settings and helper functions
require_once 'config.php';

// Check if user is authenticated, redirect to login if not
requireLogin();

// Establish PDO database connection using the helper function from config.php
$pdo = getDBConnection();

// Prepare and execute SQL query to get user's applications with job details
// Uses JOIN to combine applications and jobs tables for complete information
$stmt = $pdo->prepare("
    SELECT a.*, j.title, j.company, j.location, j.salary_range 
    FROM applications a 
    JOIN jobs j ON a.job_id = j.id 
    WHERE a.user_id = ? 
    ORDER BY a.applied_at DESC
");
// Execute query with current user's ID from session (prevents SQL injection)
$stmt->execute([$_SESSION['user_id']]);
// Fetch all results as associative array for easy data access
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate application statistics using array functions
// Uses PHP 7.4+ arrow functions for concise filtering
$stats = [
    'total' => count($applications), // Total number of applications
    'pending' => count(array_filter($applications, fn($app) => $app['status'] === 'pending')), // Count pending applications
    'interview' => count(array_filter($applications, fn($app) => $app['status'] === 'interview')), // Count interview scheduled
    'approved' => count(array_filter($applications, fn($app) => $app['status'] === 'approved')), // Count approved applications
    'rejected' => count(array_filter($applications, fn($app) => $app['status'] === 'rejected')) // Count rejected applications
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Set character encoding to UTF-8 for proper text display -->
    <meta charset="UTF-8">
    <!-- Configure viewport for responsive design on mobile devices -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Set page title for browser tab -->
    <title>My Applications - Job Portal</title>
    <!-- Include Bootstrap CSS framework for styling and responsive components -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include Bootstrap Icons for UI iconography -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Include custom CSS file for additional styling -->
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <!-- Main Navigation Bar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <!-- Brand/Logo section with briefcase icon -->
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-briefcase"></i> Job Portal
            </a>
            <!-- Mobile menu toggle button for responsive design -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <!-- Collapsible navigation menu -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <!-- Home navigation link -->
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-house"></i> Home
                        </a>
                    </li>
                    <!-- Current page - My Applications (marked as active) -->
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-person-circle"></i> My Applications
                        </a>
                    </li>
                    <!-- Admin panel link - only visible to administrators -->
                    <?php if (isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/index.php">
                            <i class="bi bi-gear"></i> Admin Panel
                        </a>
                    </li>
                    <?php endif; ?>
                    <!-- Welcome message displaying user's first name from session -->
                    <li class="nav-item">
                        <span class="nav-link">Welcome, <?php echo sanitize($_SESSION['first_name']); ?>!</span>
                    </li>
                    <!-- Logout link -->
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="container mt-4">
        <div class="row">
            <!-- Page Header Section -->
            <div class="col-12 mb-4">
                <h1><i class="bi bi-person-circle"></i> My Job Applications</h1>
                <p class="text-muted">Track the status of your job applications</p>
            </div>

            <!-- Statistics Cards Section -->
            <div class="col-12 mb-4">
                <div class="row">
                    <!-- Total Applications Card -->
                    <div class="col-md-2 col-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['total']; ?></div>
                            <div>Total Applications</div>
                        </div>
                    </div>
                    <!-- Pending Applications Card with yellow gradient background -->
                    <div class="col-md-2 col-6 mb-3">
                        <div class="card text-center" style="background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%); color: white;">
                            <div class="card-body">
                                <div class="stat-number"><?php echo $stats['pending']; ?></div>
                                <div>Pending</div>
                            </div>
                        </div>
                    </div>
                    <!-- Interview Applications Card with blue gradient background -->
                    <div class="col-md-2 col-6 mb-3">
                        <div class="card text-center" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); color: white;">
                            <div class="card-body">
                                <div class="stat-number"><?php echo $stats['interview']; ?></div>
                                <div>Interviews</div>
                            </div>
                        </div>
                    </div>
                    <!-- Approved Applications Card with green gradient background -->
                    <div class="col-md-2 col-6 mb-3">
                        <div class="card text-center" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white;">
                            <div class="card-body">
                                <div class="stat-number"><?php echo $stats['approved']; ?></div>
                                <div>Approved</div>
                            </div>
                        </div>
                    </div>
                    <!-- Rejected Applications Card with red gradient background -->
                    <div class="col-md-2 col-6 mb-3">
                        <div class="card text-center" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white;">
                            <div class="card-body">
                                <div class="stat-number"><?php echo $stats['rejected']; ?></div>
                                <div>Rejected</div>
                            </div>
                        </div>
                    </div>
                    <!-- Action Card to apply for more jobs -->
                    <div class="col-md-2 col-6 mb-3">
                        <div class="card text-center border-2 border-primary">
                            <div class="card-body">
                                <a href="index.php" class="btn btn-primary">
                                    <i class="bi bi-plus-circle"></i><br>
                                    Apply for More Jobs
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Applications List Section -->
            <div class="col-12">
                <!-- Display empty state if no applications exist -->
                <?php if (empty($applications)): ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <!-- Large inbox icon for visual impact -->
                            <i class="bi bi-inbox" style="font-size: 4rem; color: #6c757d;"></i>
                            <h4 class="mt-3">No Applications Yet</h4>
                            <p class="text-muted">You haven't applied for any jobs yet. Start exploring available positions!</p>
                            <!-- Call-to-action button to browse jobs -->
                            <a href="index.php" class="btn btn-primary">
                                <i class="bi bi-search"></i> Browse Jobs
                            </a>
                        </div>
                    </div>
                <!-- Display applications table if applications exist -->
                <?php else: ?>
                    <div class="card">
                        <!-- Table header -->
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-list-ul"></i> Your Applications</h5>
                        </div>
                        <!-- Table body with responsive wrapper -->
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <!-- Table column headers -->
                                    <thead>
                                        <tr>
                                            <th>Job Title</th>
                                            <th>Company</th>
                                            <th>Location</th>
                                            <th>Salary</th>
                                            <th>Status</th>
                                            <th>Applied Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Loop through each application to create table rows -->
                                        <?php foreach ($applications as $app): ?>
                                        <tr>
                                            <!-- Job Title column with sanitized output -->
                                            <td>
                                                <strong><?php echo sanitize($app['title']); ?></strong>
                                            </td>
                                            <!-- Company column with building icon -->
                                            <td>
                                                <i class="bi bi-building"></i> <?php echo sanitize($app['company']); ?>
                                            </td>
                                            <!-- Location column with map icon, shows N/A if empty -->
                                            <td>
                                                <i class="bi bi-geo-alt"></i> <?php echo sanitize($app['location'] ?: 'N/A'); ?>
                                            </td>
                                            <!-- Salary column with conditional display -->
                                            <td>
                                                <?php if ($app['salary_range']): ?>
                                                    <i class="bi bi-cash"></i> <?php echo sanitize($app['salary_range']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Not specified</span>
                                                <?php endif; ?>
                                            </td>
                                            <!-- Status column with dynamic badge and icon -->
                                            <td>
                                                <span class="status-badge status-<?php echo $app['status']; ?>">
                                                    <?php
                                                    // Define status icons mapping
                                                    $statusIcons = [
                                                        'pending' => 'clock',
                                                        'interview' => 'calendar-event',
                                                        'approved' => 'check-circle',
                                                        'rejected' => 'x-circle'
                                                    ];
                                                    ?>
                                                    <!-- Display status icon and text -->
                                                    <i class="bi bi-<?php echo $statusIcons[$app['status']]; ?>"></i>
                                                    <?php echo ucfirst($app['status']); ?>
                                                </span>
                                            </td>
                                            <!-- Applied date column with calendar icon and formatted date -->
                                            <td>
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar"></i>
                                                    <?php echo date('M j, Y', strtotime($app['applied_at'])); ?>
                                                </small>
                                            </td>
                                            <!-- Actions column with view and resume download buttons -->
                                            <td>
                                                <!-- View application details button -->
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="viewApplication(<?php echo $app['id']; ?>)">
                                                    <i class="bi bi-eye"></i> View
                                                </button>
                                                <!-- Resume download button (only if resume exists) -->
                                                <?php if ($app['resume_path']): ?>
                                                <a href="<?php echo sanitize($app['resume_path']); ?>" 
                                                   class="btn btn-sm btn-outline-secondary" target="_blank">
                                                    <i class="bi bi-download"></i> Resume
                                                </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap Modal for Application Details -->
    <div class="modal fade" id="applicationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <!-- Modal header with title and close button -->
                <div class="modal-header">
                    <h5 class="modal-title">Application Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <!-- Modal body - content populated by JavaScript -->
                <div class="modal-body" id="applicationContent">
                    <!-- Application details will be loaded here dynamically -->
                </div>
                <!-- Modal footer with close button -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Bootstrap JavaScript for interactive components -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Convert PHP applications array to JavaScript for client-side use
        const applications = <?php echo json_encode($applications); ?>;

        /**
         * Display application details in modal
         * @param {number} applicationId - The ID of the application to display
         */
        function viewApplication(applicationId) {
            // Find the application in the array by ID
            const app = applications.find(a => a.id == applicationId);
            if (!app) return; // Exit if application not found

            // Define status icons for JavaScript use
            const statusIcons = {
                'pending': 'clock',
                'interview': 'calendar-event',
                'approved': 'check-circle',
                'rejected': 'x-circle'
            };

            // Define status colors for badge styling
            const statusColors = {
                'pending': '#ffc107',
                'interview': '#17a2b8',
                'approved': '#28a745',
                'rejected': '#dc3545'
            };

            // Generate and insert HTML content into modal
            document.getElementById('applicationContent').innerHTML = `
                <div class="row">
                    <!-- Application header with job details -->
                    <div class="col-md-8">
                        <h5><i class="bi bi-briefcase"></i> ${app.title}</h5>
                        <p class="text-muted mb-3">
                            <i class="bi bi-building"></i> ${app.company}
                            <span class="ms-3"><i class="bi bi-geo-alt"></i> ${app.location || 'Location not specified'}</span>
                            ${app.salary_range ? '<span class="ms-3"><i class="bi bi-cash"></i> ' + app.salary_range + '</span>' : ''}
                        </p>
                    </div>
                    <!-- Status badge in top right -->
                    <div class="col-md-4 text-end">
                        <span class="badge" style="background-color: ${statusColors[app.status]}; font-size: 0.9rem; padding: 0.5rem 1rem;">
                            <i class="bi bi-${statusIcons[app.status]}"></i> ${app.status.charAt(0).toUpperCase() + app.status.slice(1)}
                        </span>
                    </div>
                </div>
                
                <!-- Application dates section -->
                <div class="row mt-3">
                    <div class="col-md-6">
                        <strong>Applied Date:</strong><br>
                        <span class="text-muted">${new Date(app.applied_at).toLocaleDateString('en-US', { 
                            year: 'numeric', 
                            month: 'long', 
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        })}</span>
                    </div>
                    <div class="col-md-6">
                        <strong>Last Updated:</strong><br>
                        <span class="text-muted">${new Date(app.updated_at || app.applied_at).toLocaleDateString('en-US', { 
                            year: 'numeric', 
                            month: 'long', 
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        })}</span>
                    </div>
                </div>

                <!-- Cover letter section (if available) -->
                ${app.cover_letter ? `
                    <div class="mt-4">
                        <h6><strong>Cover Letter:</strong></h6>
                        <div class="border rounded p-3 bg-light">
                            <p class="mb-0">${app.cover_letter.replace(/\n/g, '<br>')}</p>
                        </div>
                    </div>
                ` : ''}

                <!-- Resume download section (if available) -->
                ${app.resume_path ? `
                    <div class="mt-4">
                        <h6><strong>Resume:</strong></h6>
                        <a href="${app.resume_path}" class="btn btn-outline-primary" target="_blank">
                            <i class="bi bi-download"></i> Download Resume
                        </a>
                    </div>
                ` : ''}
            `;

            // Show the modal using Bootstrap's modal API
            new bootstrap.Modal(document.getElementById('applicationModal')).show();
        }
    </script>
</body>
</html>