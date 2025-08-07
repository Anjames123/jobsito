<?php
require_once '../config/database.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total_jobs FROM jobs WHERE status = 'active'");
$activeJobs = $stmt->fetch()['total_jobs'];

$stmt = $pdo->query("SELECT COUNT(*) as total_applications FROM applications WHERE status = 'pending'");
$pendingApplications = $stmt->fetch()['total_applications'];

$stmt = $pdo->query("SELECT COUNT(*) as total_interviews FROM applications WHERE status = 'called_for_interview'");
$scheduledInterviews = $stmt->fetch()['total_interviews'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../file.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Admin Dashboard</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="post_job.php">Post New Job</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="mb-4">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
        
        <div class="row dashboard-stats">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h3><?php echo $activeJobs; ?></h3>
                        <p class="mb-0">Active Jobs</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h3><?php echo $pendingApplications; ?></h3>
                        <p class="mb-0">Pending Applications</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h3><?php echo $scheduledInterviews; ?></h3>
                        <p class="mb-0">Scheduled Interviews</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Recent Applications</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Job Title</th>
                                    <th>Applicant</th>
                                    <th>Applied Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->query("
                                    SELECT a.*, j.title as job_title, u.username as applicant_name 
                                    FROM applications a 
                                    JOIN jobs j ON a.job_id = j.id 
                                    JOIN users u ON a.user_id = u.id 
                                    ORDER BY a.applied_at DESC 
                                    LIMIT 10
                                ");
                                while ($row = $stmt->fetch()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['job_title']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['applicant_name']) . "</td>";
                                    echo "<td>" . date('M d, Y', strtotime($row['applied_at'])) . "</td>";
                                    echo "<td><span class='application-status status-" . $row['status'] . "'>" . 
                                         ucwords(str_replace('_', ' ', $row['status'])) . "</span></td>";
                                    echo "<td><a href='view_application.php?id=" . $row['id'] . 
                                         "' class='btn btn-sm btn-primary'>View</a></td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
