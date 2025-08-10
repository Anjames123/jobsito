<?php
require_once '../config.php';
requireAdmin();

$pdo = getDBConnection();
$message = '';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $app_id = (int)$_POST['app_id'];
    $new_status = $_POST['status'];
    
    if (in_array($new_status, ['pending', 'interview', 'approved', 'rejected'])) {
        $stmt = $pdo->prepare("UPDATE applications SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $success = $stmt->execute([$new_status, $app_id]);
        $message = $success ? 'Application status updated successfully!' : 'Failed to update status.';
    }
}

// Get filters
$status_filter = $_GET['status'] ?? '';
$job_filter = $_GET['job_id'] ?? '';
$search = $_GET['search'] ?? '';

// Build query with filters
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "a.status = ?";
    $params[] = $status_filter;
}

if ($job_filter) {
    $where_conditions[] = "a.job_id = ?";
    $params[] = $job_filter;
}

if ($search) {
    $where_conditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR j.title LIKE ? OR j.company LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term, $search_term]);
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get applications with job and user details
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

// Get jobs for filter dropdown
$jobs = $pdo->query("SELECT id, title, company FROM jobs ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

// Get status counts
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
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h1><i class="bi bi-file-earmark-text"></i> Manage Applications</h1>
                <p class="text-muted">Review and update application statuses</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Status Filter Tabs -->
        <div class="row mb-4">
            <div class="col-12">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link <?php echo !$status_filter ? 'active' : ''; ?>" href="applications.php">
                            All Applications <span class="badge bg-light text-dark"><?php echo $status_counts['all']; ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $status_filter === 'pending' ? 'active' : ''; ?>" href="?status=pending">
                            <i class="bi bi-clock"></i> Pending <span class="badge bg-light text-dark"><?php echo $status_counts['pending']; ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $status_filter === 'interview' ? 'active' : ''; ?>" href="?status=interview">
                            <i class="bi bi-calendar-event"></i> Interview <span class="badge bg-light text-dark"><?php echo $status_counts['interview']; ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $status_filter === 'approved' ? 'active' : ''; ?>" href="?status=approved">
                            <i class="bi bi-check-circle"></i> Approved <span class="badge bg-light text-dark"><?php echo $status_counts['approved']; ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>" href="?status=rejected">
                            <i class="bi bi-x-circle"></i> Rejected <span class="badge bg-light text-dark"><?php echo $status_counts['rejected']; ?></span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?php echo sanitize($search); ?>" 
                                       placeholder="Search by name, email, job title, or company">
                            </div>
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

        <!-- Applications List -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Applications (<?php echo count($applications); ?>)</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($applications)): ?>
                            <div class="p-4 text-center">
                                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                <p class="mt-2 text-muted">No applications found</p>
                            </div>
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
                                            <td>
                                                <div>
                                                    <?php echo date('M j, Y', strtotime($app['applied_at'])); ?>
                                                    <br><small class="text-muted">
                                                        <?php echo date('g:i A', strtotime($app['applied_at'])); ?>
                                                    </small>
                                                </div>
                                            </td>
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
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" 
                                                            onclick="viewApplication(<?php echo $app['id']; ?>)" title="View Details">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <?php if ($app['resume_path']): ?>
                                                        <a href="../<?php echo sanitize($app['resume_path']); ?>" 
                                                           class="btn btn-outline-secondary" target="_blank" title="Download Resume">
                                                            <i class="bi bi-download"></i>
                                                        </a>
                                                    <?php endif; ?>
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

    <!-- Application Details Modal -->
    <div class="modal fade" id="applicationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Application Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="applicationContent">
                    <!-- Content will be loaded here -->
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

        function viewApplication(appId) {
            const app = applications.find(a => a.id == appId);
            if (!app) return;

            const statusColors = {
                'pending': '#ffc107',
                'interview': '#17a2b8',
                'approved': '#28a745',
                'rejected': '#dc3545'
            };

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
                    <div class="col-md-6">
                        <strong>Applied Date:</strong><br>
                        <span class="text-muted">${new Date(app.applied_at).toLocaleString()}</span>
                    </div>
                    <div class="col-md-6">
                        <strong>Status:</strong><br>
                        <span class="badge" style="background-color: ${statusColors[app.status]}; font-size: 0.9rem; padding: 0.5rem 1rem;">
                            ${app.status.charAt(0).toUpperCase() + app.status.slice(1)}
                        </span>
                    </div>
                </div>

                ${app.cover_letter ? `
                <div class="mt-4">
                    <h6><i class="bi bi-file-text"></i> Cover Letter</h6>
                    <div class="border rounded p-3 bg-light">
                        <p class="mb-0" style="white-space: pre-wrap;">${app.cover_letter}</p>
                    </div>
                </div>
                ` : ''}

                ${app.resume_path ? `
                <div class="mt-4">
                    <h6><i class="bi bi-file-earmark-pdf"></i> Resume</h6>
                    <a href="../${app.resume_path}" class="btn btn-outline-primary" target="_blank">
                        <i class="bi bi-download"></i> Download Resume
                    </a>
                </div>
                ` : ''}

                <div class="mt-4">
                    <h6><i class="bi bi-clock-history"></i> Timeline</h6>
                    <div class="border rounded p-3 bg-light">
                        <div class="mb-2">
                            <strong>Applied:</strong> ${new Date(app.applied_at).toLocaleString()}
                        </div>
                        <div>
                            <strong>Last Updated:</strong> ${new Date(app.updated_at).toLocaleString()}
                        </div>
                    </div>
                </div>
            `;

            new bootstrap.Modal(document.getElementById('applicationModal')).show();
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
                }, index * 50);
            });
        });
    </script>

    <style>
        .status-pending { background-color: #ffc107 !important; color: #212529 !important; border: none; }
        .status-interview { background-color: #17a2b8 !important; color: white !important; border: none; }
        .status-approved { background-color: #28a745 !important; color: white !important; border: none; }
        .status-rejected { background-color: #dc3545 !important; color: white !important; border: none; }
        
        .form-select.status-pending option { background: white; color: black; }
        .form-select.status-interview option { background: white; color: black; }
        .form-select.status-approved option { background: white; color: black; }
        .form-select.status-rejected option { background: white; color: black; }
    </style>
</body>
</html>