<?php
require_once '../config.php';
requireAdmin();

$pdo = getDBConnection();

// Get statistics
$stats = [
    'total_jobs' => $pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn(),
    'active_jobs' => $pdo->query("SELECT COUNT(*) FROM jobs WHERE is_active = 1")->fetchColumn(),
    'total_applications' => $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn(),
    'pending_applications' => $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'pending'")->fetchColumn(),
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0")->fetchColumn()
];

// Get recent applications
$stmt = $pdo->prepare("
    SELECT a.*, j.title as job_title, j.company, u.first_name, u.last_name, u.email 
    FROM applications a 
    JOIN jobs j ON a.job_id = j.id 
    JOIN users u ON a.user_id = u.id 
    ORDER BY a.applied_at DESC 
    LIMIT 10
");
$stmt->execute();
$recent_applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent jobs
$stmt = $pdo->prepare("SELECT * FROM jobs ORDER BY created_at DESC LIMIT 5");
$stmt->execute();
$recent_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="bi bi-briefcase"></i> Job Portal - Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">
                            <i class="bi bi-house"></i> Public Site
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="jobs.php">
                            <i class="bi bi-briefcase"></i> Manage Jobs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="applications.php">
                            <i class="bi bi-file-earmark-text"></i> Applications
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h1><i class="bi bi-speedometer2"></i> Admin Dashboard</h1>
                <p class="text-muted">Overview of job portal activity</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-2 col-6 mb-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_jobs']; ?></div>
                    <div>Total Jobs</div>
                </div>
            </div>
            <div class="col-md-2 col-6 mb-3">
                <div class="card text-center" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white;">
                    <div class="card-body">
                        <div class="stat-number"><?php echo $stats['active_jobs']; ?></div>
                        <div>Active Jobs</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-6 mb-3">
                <div class="card text-center" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); color: white;">
                    <div class="card-body">
                        <div class="stat-number"><?php echo $stats['total_applications']; ?></div>
                        <div>Applications</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-6 mb-3">
                <div class="card text-center" style="background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%); color: white;">
                    <div class="card-body">
                        <div class="stat-number"><?php echo $stats['pending_applications']; ?></div>
                        <div>Pending</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-6 mb-3">
                <div class="card text-center" style="background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%); color: white;">
                    <div class="card-body">
                        <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                        <div>Users</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-6 mb-3">
                <div class="card text-center border-2 border-primary">
                    <div class="card-body">
                        <a href="jobs.php?action=add" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i><br>
                            Add New Job
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Applications -->
            <div class="col-md-8 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-file-earmark-text"></i> Recent Applications</h5>
                        <a href="applications.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recent_applications)): ?>
                            <div class="p-3 text-center text-muted">
                                <i class="bi bi-inbox"></i> No applications yet
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Applicant</th>
                                            <th>Job</th>
                                            <th>Status</th>
                                            <th>Applied</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_applications as $app): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo sanitize($app['first_name'] . ' ' . $app['last_name']); ?></strong>
                                                <br><small class="text-muted"><?php echo sanitize($app['email']); ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo sanitize($app['job_title']); ?></strong>
                                                <br><small class="text-muted"><?php echo sanitize($app['company']); ?></small>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $app['status']; ?>">
                                                    <?php echo ucfirst($app['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small><?php echo date('M j, Y', strtotime($app['applied_at'])); ?></small>
                                            </td>
                                            <td>
                                                <a href="applications.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
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

            <!-- Recent Jobs & Quick Actions -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-briefcase"></i> Recent Jobs</h5>
                        <a href="jobs.php" class="btn btn-sm btn-outline-primary">Manage</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_jobs)): ?>
                            <div class="text-center text-muted">
                                <i class="bi bi-briefcase"></i> No jobs posted yet
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_jobs as $job): ?>
                            <div class="border-bottom pb-2 mb-2">
                                <h6 class="mb-1"><?php echo sanitize($job['title']); ?></h6>
                                <small class="text-muted">
                                    <?php echo sanitize($job['company']); ?> • 
                                    <?php echo date('M j', strtotime($job['created_at'])); ?>
                                    <?php if (!$job['is_active']): ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </small>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="jobs.php?action=add" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Post New Job
                            </a>
                            <a href="applications.php?status=pending" class="btn btn-warning">
                                <i class="bi bi-clock"></i> Review Pending Apps
                            </a>
                            <a href="applications.php?status=interview" class="btn btn-info">
                                <i class="bi bi-calendar-event"></i> Interview Scheduled
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add fade-in animation
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.4s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>