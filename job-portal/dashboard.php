<?php
require_once 'config.php';
requireLogin();

$pdo = getDBConnection();

// Get user's applications with job details
$stmt = $pdo->prepare("
    SELECT a.*, j.title, j.company, j.location, j.salary_range 
    FROM applications a 
    JOIN jobs j ON a.job_id = j.id 
    WHERE a.user_id = ? 
    ORDER BY a.applied_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats = [
    'total' => count($applications),
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
    <title>My Applications - Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-briefcase"></i> Job Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-house"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-person-circle"></i> My Applications
                        </a>
                    </li>
                    <?php if (isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/index.php">
                            <i class="bi bi-gear"></i> Admin Panel
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <span class="nav-link">Welcome, <?php echo sanitize($_SESSION['first_name']); ?>!</span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <!-- Page Header -->
            <div class="col-12 mb-4">
                <h1><i class="bi bi-person-circle"></i> My Job Applications</h1>
                <p class="text-muted">Track the status of your job applications</p>
            </div>

            <!-- Statistics Cards -->
            <div class="col-12 mb-4">
                <div class="row">
                    <div class="col-md-2 col-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['total']; ?></div>
                            <div>Total Applications</div>
                        </div>
                    </div>
                    <div class="col-md-2 col-6 mb-3">
                        <div class="card text-center" style="background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%); color: white;">
                            <div class="card-body">
                                <div class="stat-number"><?php echo $stats['pending']; ?></div>
                                <div>Pending</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 col-6 mb-3">
                        <div class="card text-center" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); color: white;">
                            <div class="card-body">
                                <div class="stat-number"><?php echo $stats['interview']; ?></div>
                                <div>Interviews</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 col-6 mb-3">
                        <div class="card text-center" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white;">
                            <div class="card-body">
                                <div class="stat-number"><?php echo $stats['approved']; ?></div>
                                <div>Approved</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 col-6 mb-3">
                        <div class="card text-center" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white;">
                            <div class="card-body">
                                <div class="stat-number"><?php echo $stats['rejected']; ?></div>
                                <div>Rejected</div>
                            </div>
                        </div>
                    </div>
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

            <!-- Applications List -->
            <div class="col-12">
                <?php if (empty($applications)): ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-inbox" style="font-size: 4rem; color: #6c757d;"></i>
                            <h4 class="mt-3">No Applications Yet</h4>
                            <p class="text-muted">You haven't applied for any jobs yet. Start exploring available positions!</p>
                            <a href="index.php" class="btn btn-primary">
                                <i class="bi bi-search"></i> Browse Jobs
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-list-ul"></i> Your Applications</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
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
                                        <?php foreach ($applications as $app): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo sanitize($app['title']); ?></strong>
                                            </td>
                                            <td>
                                                <i class="bi bi-building"></i> <?php echo sanitize($app['company']); ?>
                                            </td>
                                            <td>
                                                <i class="bi bi-geo-alt"></i> <?php echo sanitize($app['location'] ?: 'N/A'); ?>
                                            </td>
                                            <td>
                                                <?php if ($app['salary_range']): ?>
                                                    <i class="bi bi-cash"></i> <?php echo sanitize($app['salary_range']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Not specified</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $app['status']; ?>">
                                                    <?php
                                                    $statusIcons = [
                                                        'pending' => 'clock',
                                                        'interview' => 'calendar-event',
                                                        'approved' => 'check-circle',
                                                        'rejected' => 'x-circle'
                                                    ];
                                                    ?>
                                                    <i class="bi bi-<?php echo $statusIcons[$app['status']]; ?>"></i>
                                                    <?php echo ucfirst($app['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar"></i>
                                                    <?php echo date('M j, Y', strtotime($app['applied_at'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="viewApplication(<?php echo $app['id']; ?>)">
                                                    <i class="bi bi-eye"></i> View
                                                </button>
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

    <!-- Application Details Modal -->
    <div class="modal fade" id="applicationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Application Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="applicationContent">
                    <!-- Application details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const applications = <?php echo json_encode($applications); ?>;

        function viewApplication(applicationId) {
            const app = applications.find(a => a.id == applicationId);
            if (!app) return;

            const statusIcons = {
                'pending': 'clock',
                'interview': 'calendar-event',
                'approved': 'check-circle',
                'rejected': 'x-circle'
            };

            const statusColors = {
                'pending': '#ffc107',
                'interview': '#17a2b8',
                'approved': '#28a745',
                'rejected': '#dc3545'
            };

            document.getElementById('applicationContent').innerHTML = `
                <div class="row">
                    <div class="col-md-8">
                        <h5><i class="bi bi-briefcase"></i> ${app.title}</h5>
                        <p class="text-muted mb-3">
                            <i class="bi bi-building"></i> ${app.company}
                            <span class="ms-3"><i class="bi bi-geo-alt"></i> ${app.location || 'Location not specified'}</span>
                            ${app.salary_range ? '<span class="ms-3"><i class="bi bi-cash"></i> ' + app.salary_range + '</span>' : ''}
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <span class="badge" style="background-color: ${statusColors[app.status]}; font-size: 0.9rem; padding: 0.5rem 1rem;">
                            <i class="bi bi-${statusIcons[app.status]}"></i> ${app.status.charAt(0).toUpperCase() + app.status.slice(1)}
                        </span>
                    </div>
                </div>
                
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
                        <span class="text-muted">${new Date(app.updated_at).toLocaleDateString('en-US', { 
                            year: 'numeric', 
                            month: 'long', 
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        })}</span>
                    </div>
                </div>

                ${app.cover_letter ? `
                <div class="mt-4">
                    <strong>Cover Letter:</strong>
                    <div class="border rounded p-3 mt-2 bg-light">
                        <p class="mb-0">${app.cover_letter.replace(/\n/g, '<br>')}</p>
                    </div>
                </div>
                ` : ''}

                ${app.resume_path ? `
                <div class="mt-4">
                    <strong>Resume:</strong><br>
                    <a href="${app.resume_path}" class="btn btn-outline-primary mt-2" target="_blank">
                        <i class="bi bi-download"></i> Download Resume
                    </a>
                </div>
                ` : ''}

                <div class="mt-4">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Status Information:</strong><br>
                        ${getStatusMessage(app.status)}
                    </div>
                </div>
            `;

            new bootstrap.Modal(document.getElementById('applicationModal')).show();
        }

        function getStatusMessage(status) {
            const messages = {
                'pending': 'Your application is being reviewed by the employer. We\'ll notify you of any updates.',
                'interview': 'Congratulations! You\'ve been selected for an interview. The employer will contact you soon with details.',
                'approved': 'Excellent news! Your application has been approved. Congratulations on your new opportunity!',
                'rejected': 'Unfortunately, your application was not selected this time. Keep applying - the right opportunity is waiting for you!'
            };
            return messages[status] || 'Status update pending.';
        }

        // Add fade-in animation to table rows
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    row.style.transition = 'all 0.4s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>