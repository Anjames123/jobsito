<?php
require_once 'config.php';
requireLogin();

$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;

if (!$job_id) {
    header('Location: index.php');
    exit();
}

$pdo = getDBConnection();

// Get job details
$stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ? AND is_active = 1");
$stmt->execute([$job_id]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    header('Location: index.php');
    exit();
}

// Check if user has already applied
$stmt = $pdo->prepare("SELECT id FROM applications WHERE job_id = ? AND user_id = ?");
$stmt->execute([$job_id, $_SESSION['user_id']]);
$existing_application = $stmt->fetch();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$existing_application) {
    $cover_letter = sanitize($_POST['cover_letter']);
    $resume_path = '';
    
    // Handle file upload
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['resume'];
        $file_size = $file['size'];
        $file_tmp = $file['tmp_name'];
        $file_name = $file['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Validate file
        if ($file_size > MAX_FILE_SIZE) {
            $error = 'File size must be less than 5MB.';
        } elseif (!in_array($file_ext, ALLOWED_EXTENSIONS)) {
            $error = 'Only PDF, DOC, and DOCX files are allowed.';
        } else {
            // Generate unique filename
            $new_filename = 'resume_' . $_SESSION['user_id'] . '_' . $job_id . '_' . time() . '.' . $file_ext;
            $upload_path = UPLOAD_DIR . $new_filename;
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $resume_path = $upload_path;
            } else {
                $error = 'Failed to upload resume. Please try again.';
            }
        }
    }
    
    if (!$error) {
        // Insert application
        $stmt = $pdo->prepare("INSERT INTO applications (job_id, user_id, cover_letter, resume_path) VALUES (?, ?, ?, ?)");
        
        if ($stmt->execute([$job_id, $_SESSION['user_id'], $cover_letter, $resume_path])) {
            $success = 'Application submitted successfully! You can track its status in your dashboard.';
        } else {
            $error = 'Failed to submit application. Please try again.';
            // Clean up uploaded file if application failed
            if ($resume_path && file_exists($resume_path)) {
                unlink($resume_path);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for <?php echo sanitize($job['title']); ?> - Job Portal</title>
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
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="bi bi-house"></i> Home
                </a>
                <a class="nav-link" href="dashboard.php">
                    <i class="bi bi-person-circle"></i> My Applications
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <!-- Job Details -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-briefcase"></i> Job Details</h5>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo sanitize($job['title']); ?></h5>
                        <h6 class="card-subtitle mb-3 text-muted">
                            <i class="bi bi-building"></i> <?php echo sanitize($job['company']); ?>
                        </h6>
                        
                        <div class="mb-3">
                            <small class="text-muted">
                                <i class="bi bi-geo-alt"></i> <?php echo sanitize($job['location'] ?: 'Location not specified'); ?>
                            </small>
                            <?php if ($job['salary_range']): ?>
                            <br><small class="text-muted">
                                <i class="bi bi-cash"></i> <?php echo sanitize($job['salary_range']); ?>
                            </small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Description:</strong>
                            <p class="small mt-1"><?php echo nl2br(sanitize($job['description'])); ?></p>
                        </div>
                        
                        <?php if ($job['requirements']): ?>
                        <div class="mb-3">
                            <strong>Requirements:</strong>
                            <p class="small mt-1"><?php echo nl2br(sanitize($job['requirements'])); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <small class="text-muted">
                            <i class="bi bi-calendar"></i> Posted: <?php echo date('M j, Y', strtotime($job['created_at'])); ?>
                        </small>
                    </div>
                </div>
            </div>

            <!-- Application Form -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-send"></i> Submit Application</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($existing_application): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> 
                                You have already applied for this position. 
                                <a href="dashboard.php" class="alert-link">Check your application status</a>
                            </div>
                        <?php elseif ($success): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                                <div class="mt-3">
                                    <a href="dashboard.php" class="btn btn-primary">
                                        <i class="bi bi-person-circle"></i> View My Applications
                                    </a>
                                    <a href="index.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-search"></i> Browse More Jobs
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" enctype="multipart/form-data" id="applicationForm">
                                <div class="mb-4">
                                    <label for="cover_letter" class="form-label">
                                        <i class="bi bi-file-text"></i> Cover Letter
                                        <small class="text-muted">(Optional but recommended)</small>
                                    </label>
                                    <textarea class="form-control" id="cover_letter" name="cover_letter" rows="8" 
                                              placeholder="Tell us why you're interested in this position and why you'd be a great fit..."></textarea>
                                    <div class="form-text">
                                        Tip: Mention specific skills from the job requirements and how your experience matches what they're looking for.
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="resume" class="form-label">
                                        <i class="bi bi-file-earmark-pdf"></i> Resume/CV
                                        <small class="text-danger">*Required</small>
                                    </label>
                                    <div class="upload-area" onclick="document.getElementById('resume').click()">
                                        <i class="bi bi-cloud-upload" style="font-size: 2rem; color: #667eea;"></i>
                                        <p class="mt-2 mb-0">Click to upload your resume</p>
                                        <small class="text-muted">PDF, DOC, or DOCX (Max 5MB)</small>
                                    </div>
                                    <input type="file" class="form-control mt-2" id="resume" name="resume" 
                                           accept=".pdf,.doc,.docx" required style="display: none;">
                                    <div id="file-info" class="mt-2" style="display: none;">
                                        <div class="alert alert-success">
                                            <i class="bi bi-file-check"></i> 
                                            <span id="file-name"></span>
                                            <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="removeFile()">
                                                <i class="bi bi-x"></i> Remove
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="consent" required>
                                        <label class="form-check-label" for="consent">
                                            I consent to the processing of my personal data for recruitment purposes
                                        </label>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="index.php" class="btn btn-outline-secondary me-md-2">
                                        <i class="bi bi-arrow-left"></i> Back to Jobs
                                    </a>
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
                                        <i class="bi bi-send"></i> Submit Application
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Application Tips -->
                <?php if (!$existing_application && !$success): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h6><i class="bi bi-lightbulb"></i> Application Tips</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="bi bi-file-text"></i> Cover Letter Tips:</h6>
                                <ul class="small">
                                    <li>Address the hiring manager by name if possible</li>
                                    <li>Mention specific skills from the job requirements</li>
                                    <li>Highlight relevant achievements with numbers</li>
                                    <li>Keep it concise (3-4 paragraphs)</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="bi bi-file-earmark-pdf"></i> Resume Tips:</h6>
                                <ul class="small">
                                    <li>Use a clean, professional format</li>
                                    <li>Include relevant keywords from the job posting</li>
                                    <li>Quantify your achievements where possible</li>
                                    <li>Keep it to 1-2 pages maximum</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // File upload handling
        document.getElementById('resume').addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const fileSize = file.size / 1024 / 1024; // Convert to MB
                const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                
                if (fileSize > 5) {
                    alert('File size must be less than 5MB');
                    this.value = '';
                    return;
                }
                
                if (!allowedTypes.includes(file.type)) {
                    alert('Only PDF, DOC, and DOCX files are allowed');
                    this.value = '';
                    return;
                }
                
                document.getElementById('file-name').textContent = file.name;
                document.getElementById('file-info').style.display = 'block';
                document.querySelector('.upload-area').style.display = 'none';
            }
        });

        function removeFile() {
            document.getElementById('resume').value = '';
            document.getElementById('file-info').style.display = 'none';
            document.querySelector('.upload-area').style.display = 'block';
        }

        // Drag and drop functionality
        const uploadArea = document.querySelector('.upload-area');
        
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                document.getElementById('resume').files = files;
                document.getElementById('resume').dispatchEvent(new Event('change'));
            }
        });

        // Form submission
        document.getElementById('applicationForm').addEventListener('submit', function() {
            document.getElementById('submitBtn').innerHTML = '<i class="bi bi-arrow-clockwise spin"></i> Submitting...';
            document.getElementById('submitBtn').disabled = true;
        });

        // Character counter for cover letter
        const coverLetter = document.getElementById('cover_letter');
        if (coverLetter) {
            const counter = document.createElement('div');
            counter.className = 'form-text text-end';
            counter.id = 'char-counter';
            coverLetter.parentNode.appendChild(counter);
            
            coverLetter.addEventListener('input', function() {
                const length = this.value.length;
                counter.textContent = `${length} characters`;
                
                if (length > 500) {
                    counter.className = 'form-text text-end text-success';
                } else {
                    counter.className = 'form-text text-end text-muted';
                }
            });
        }
    </script>

    <style>
        .spin {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</body>
</html>