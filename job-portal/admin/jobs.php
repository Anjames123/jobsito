<?php
require_once '../config.php';
requireAdmin();

$pdo = getDBConnection();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
            case 'edit':
                $title = sanitize($_POST['title']);
                $company = sanitize($_POST['company']);
                $location = sanitize($_POST['location']);
                $salary_range = sanitize($_POST['salary_range']);
                $description = sanitize($_POST['description']);
                $requirements = sanitize($_POST['requirements']);
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                if (empty($title) || empty($company) || empty($description)) {
                    $error = 'Please fill in all required fields.';
                } else {
                    if ($_POST['action'] == 'add') {
                        $stmt = $pdo->prepare("INSERT INTO jobs (title, company, location, salary_range, description, requirements, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $success = $stmt->execute([$title, $company, $location, $salary_range, $description, $requirements, $is_active, $_SESSION['user_id']]);
                        $message = $success ? 'Job posted successfully!' : 'Failed to post job.';
                    } else {
                        $job_id = (int)$_POST['job_id'];
                        $stmt = $pdo->prepare("UPDATE jobs SET title = ?, company = ?, location = ?, salary_range = ?, description = ?, requirements = ?, is_active = ? WHERE id = ?");
                        $success = $stmt->execute([$title, $company, $location, $salary_range, $description, $requirements, $is_active, $job_id]);
                        $message = $success ? 'Job updated successfully!' : 'Failed to update job.';
                    }
                }
                break;
                
            case 'toggle_status':
                $job_id = (int)$_POST['job_id'];
                $stmt = $pdo->prepare("UPDATE jobs SET is_active = NOT is_active WHERE id = ?");
                $success = $stmt->execute([$job_id]);
                $message = $success ? 'Job status updated!' : 'Failed to update status.';
                break;
                
            case 'delete':
                $job_id = (int)$_POST['job_id'];
                // First check if there are applications for this job
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

// Get job for editing if ID is provided
$edit_job = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $edit_job = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get all jobs
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
                    <li class="nav-item"><a class="nav-link active" href="jobs.php"><i class="bi bi-briefcase"></i> Manage Jobs</a></li>
                    <li class="nav-item"><a class="nav-link" href="applications.php"><i class="bi bi-file-earmark-text"></i> Applications</a></li>
                    <li class="nav-item"><a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <!-- Job Form -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-<?php echo $edit_job ? 'pencil' : 'plus-circle'; ?>"></i> 
                            <?php echo $edit_job ? 'Edit Job' : 'Post New Job'; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-success"><i class="bi bi-check-circle"></i> <?php echo $message; ?></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <input type="hidden" name="action" value="<?php echo $edit_job ? 'edit' : 'add'; ?>">
                            <?php if ($edit_job): ?>
                                <input type="hidden" name="job_id" value="<?php echo $edit_job['id']; ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="title" class="form-label">Job Title *</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?php echo $edit_job ? sanitize($edit_job['title']) : ''; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="company" class="form-label">Company *</label>
                                <input type="text" class="form-control" id="company" name="company" 
                                       value="<?php echo $edit_job ? sanitize($edit_job['company']) : ''; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" class="form-control" id="location" name="location" 
                                       value="<?php echo $edit_job ? sanitize($edit_job['location']) : ''; ?>" 
                                       placeholder="e.g. New York, Remote">
                            </div>

                            <div class="mb-3">
                                <label for="salary_range" class="form-label">Salary Range</label>
                                <input type="text" class="form-control" id="salary_range" name="salary_range" 
                                       value="<?php echo $edit_job ? sanitize($edit_job['salary_range']) : ''; ?>" 
                                       placeholder="e.g. $50,000 - $70,000">
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Job Description *</label>
                                <textarea class="form-control" id="description" name="description" rows="4" required><?php echo $edit_job ? sanitize($edit_job['description']) : ''; ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="requirements" class="form-label">Requirements</label>
                                <textarea class="form-control" id="requirements" name="requirements" rows="3"><?php echo $edit_job ? sanitize($edit_job['requirements']) : ''; ?></textarea>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                           <?php echo (!$edit_job || $edit_job['is_active']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">
                                        Active (visible to applicants)
                                    </label>
                                </div>
                            </div>

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

            <!-- Jobs List -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-list"></i> All Jobs (<?php echo count($jobs); ?>)</h5>
                        <div>
                            <span class="badge bg-success"><?php echo count(array_filter($jobs, fn($j) => $j['is_active'])); ?> Active</span>
                            <span class="badge bg-secondary"><?php echo count(array_filter($jobs, fn($j) => !$j['is_active'])); ?> Inactive</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($jobs)): ?>
                            <div class="p-4 text-center">
                                <i class="bi bi-briefcase" style="font-size: 3rem; color: #ccc;"></i>
                                <p class="mt-2 text-muted">No jobs posted yet</p>
                            </div>
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
                                        <tr class="<?php echo $job['is_active'] ? '' : 'text-muted'; ?>">
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
                                            <td class="text-center">
                                                <div class="badge bg-primary"><?php echo $job['application_count']; ?> Total</div>
                                                <?php if ($job['pending_count'] > 0): ?>
                                                    <div class="badge bg-warning mt-1"><?php echo $job['pending_count']; ?> Pending</div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($job['is_active']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?php echo date('M j, Y', strtotime($job['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="jobs.php?action=edit&id=<?php echo $job['id']; ?>" 
                                                       class="btn btn-outline-primary" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                        <button type="submit" 
                                                                class="btn btn-outline-<?php echo $job['is_active'] ? 'warning' : 'success'; ?>" 
                                                                title="<?php echo $job['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                            <i class="bi bi-<?php echo $job['is_active'] ? 'pause' : 'play'; ?>"></i>
                                                        </button>
                                                    </form>
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

    <!-- Delete Confirmation Modal -->
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteJob(jobId) {
            document.getElementById('deleteJobId').value = jobId;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        // Auto-focus title field
        document.getElementById('title').focus();

        // Character counters
        ['description', 'requirements'].forEach(fieldId => {
            const field = document.getElementById(fieldId);
            const counter = document.createElement('div');
            counter.className = 'form-text text-end';
            field.parentNode.appendChild(counter);
            
            function updateCounter() {
                counter.textContent = field.value.length + ' characters';
            }
            
            field.addEventListener('input', updateCounter);
            updateCounter();
        });
    </script>
</body>
</html>