<?php
require_once 'config/database.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user's applications
$stmt = $pdo->prepare("
    SELECT a.*, j.title as job_title, j.company 
    FROM applications a 
    JOIN jobs j ON a.job_id = j.id 
    WHERE a.user_id = ? 
    ORDER BY a.applied_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$applications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Applications - Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="file.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.html">Job Portal</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.html">Browse Jobs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="mb-4">My Applications</h1>

        <?php if (empty($applications)): ?>
            <div class="alert alert-info">
                You haven't applied to any jobs yet. <a href="index.html">Browse available jobs</a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($applications as $app): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($app['job_title']); ?></h5>
                                <h6 class="card-subtitle mb-2 text-muted">
                                    <?php echo htmlspecialchars($app['company']); ?>
                                </h6>
                                
                                <div class="mt-3">
                                    <span class="application-status status-<?php echo $app['status']; ?>">
                                        <?php echo ucwords(str_replace('_', ' ', $app['status'])); ?>
                                    </span>
                                </div>
                                
                                <div class="mt-3">
                                    <small class="text-muted">
                                        Applied on: <?php echo date('F j, Y', strtotime($app['applied_at'])); ?>
                                    </small>
                                </div>

                                <?php if ($app['status'] === 'called_for_interview'): ?>
                                    <div class="alert alert-success mt-3 mb-0">
                                        You've been called for an interview! Check your email for details.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
