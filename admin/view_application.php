<?php
require_once '../config/database.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

// Get application details
$stmt = $pdo->prepare("
    SELECT a.*, j.title as job_title, j.company, u.username, u.email
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN users u ON a.user_id = u.id
    WHERE a.id = ?
");
$stmt->execute([$_GET['id']]);
$application = $stmt->fetch();

if (!$application) {
    header("Location: dashboard.php");
    exit();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $newStatus = $_POST['status'];
    $notes = $_POST['notes'] ?? '';

    try {
        // Update application status
        $stmt = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $_GET['id']]);

        // Add status history
        $stmt = $pdo->prepare("
            INSERT INTO application_status_history (application_id, status, notes)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$_GET['id'], $newStatus, $notes]);

        header("Location: view_application.php?id=" . $_GET['id']);
        exit();
    } catch (PDOException $e) {
        $error = "Failed to update status";
    }
}

// Get status history
$stmt = $pdo->prepare("
    SELECT * FROM application_status_history 
    WHERE application_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$_GET['id']]);
$statusHistory = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Application - Job Portal</title>
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
                        <a class="nav-link" href="../logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Application Details</h4>
                    </div>
                    <div class="card-body">
                        <h5><?php echo htmlspecialchars($application['job_title']); ?></h5>
                        <h6 class="text-muted"><?php echo htmlspecialchars($application['company']); ?></h6>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h6>Applicant Information</h6>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($application['username']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($application['email']); ?></p>
                            <p><strong>Applied:</strong> <?php echo date('F j, Y', strtotime($application['applied_at'])); ?></p>
                        </div>

                        <div class="mb-4">
                            <h6>Documents</h6>
                            <?php if ($application['resume_path']): ?>
                                <p><a href="<?php echo htmlspecialchars($application['resume_path']); ?>" 
                                      class="btn btn-sm btn-primary" target="_blank">View Resume</a></p>
                            <?php endif; ?>
                            
                            <?php if ($application['cover_letter_path']): ?>
                                <p><a href="<?php echo htmlspecialchars($application['cover_letter_path']); ?>" 
                                      class="btn btn-sm btn-primary" target="_blank">View Cover Letter</a></p>
                            <?php endif; ?>
                        </div>

                        <div class="mb-4">
                            <h6>Current Status</h6>
                            <span class="application-status status-<?php echo $application['status']; ?>">
                                <?php echo ucwords(str_replace('_', ' ', $application['status'])); ?>
                            </span>
                        </div>

                        <form method="POST" action="" class="mt-4">
                            <div class="mb-3">
                                <label for="status" class="form-label">Update Status</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="pending">Pending</option>
                                    <option value="under_review">Under Review</option>
                                    <option value="called_for_interview">Called for Interview</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">Update Status</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Status History</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <?php foreach ($statusHistory as $history): ?>
                                <div class="timeline-item mb-3">
                                    <span class="application-status status-<?php echo $history['status']; ?>">
                                        <?php echo ucwords(str_replace('_', ' ', $history['status'])); ?>
                                    </span>
                                    <small class="text-muted d-block">
                                        <?php echo date('M j, Y g:i A', strtotime($history['created_at'])); ?>
                                    </small>
                                    <?php if ($history['notes']): ?>
                                        <p class="mt-2 mb-0"><?php echo nl2br(htmlspecialchars($history['notes'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
