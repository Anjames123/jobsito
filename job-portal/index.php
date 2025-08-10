<?php
require_once 'config.php';

// Get all active jobs
$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT * FROM jobs WHERE is_active = 1 ORDER BY created_at DESC");
$stmt->execute();
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Portal - Find Your Dream Job</title>
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
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
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
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">
                                <i class="bi bi-person-plus"></i> Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1>Find Your Dream Job</h1>
            <p>Discover amazing opportunities and take the next step in your career</p>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mt-5">
        <!-- Search Section -->
        <div class="row mb-4">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title text-center mb-3">
                            <i class="bi bi-search"></i> Search Jobs
                        </h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <input type="text" class="form-control" id="searchKeyword" placeholder="Search by keyword, company, or title">
                            </div>
                            <div class="col-md-4 mb-3">
                                <select class="form-control" id="locationFilter">
                                    <option value="">All Locations</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <button type="button" class="btn btn-primary w-100" onclick="filterJobs()">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Jobs Section -->
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">Available Positions</h2>
                <div id="jobListings" class="row">
                    <?php if (empty($jobs)): ?>
                        <div class="col-12">
                            <div class="alert alert-info text-center">
                                <i class="bi bi-info-circle"></i> No jobs available at the moment. Please check back later.
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($jobs as $job): ?>
                        <div class="col-md-6 col-lg-4 mb-4 job-item" data-job='<?php echo json_encode($job); ?>'>
                            <div class="card job-card h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo sanitize($job['title']); ?></h5>
                                    <h6 class="card-subtitle mb-2 text-muted">
                                        <i class="bi bi-building"></i> <?php echo sanitize($job['company']); ?>
                                    </h6>
                                    
                                    <div class="job-meta">
                                        <span><i class="bi bi-geo-alt"></i> <?php echo sanitize($job['location'] ?: 'Location not specified'); ?></span>
                                        <?php if ($job['salary_range']): ?>
                                        <span><i class="bi bi-cash"></i> <?php echo sanitize($job['salary_range']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <p class="card-text"><?php echo substr(sanitize($job['description']), 0, 120) . '...'; ?></p>
                                    
                                    <div class="mt-auto">
                                        <small class="text-muted">
                                            <i class="bi bi-calendar"></i> Posted: <?php echo date('M j, Y', strtotime($job['created_at'])); ?>
                                        </small>
                                        <div class="mt-2">
                                            <?php if (isLoggedIn()): ?>
                                                <a href="apply.php?job_id=<?php echo $job['id']; ?>" class="btn btn-primary">
                                                    <i class="bi bi-send"></i> Apply Now
                                                </a>
                                            <?php else: ?>
                                                <a href="login.php?redirect=apply.php?job_id=<?php echo $job['id']; ?>" class="btn btn-outline-primary">
                                                    <i class="bi bi-box-arrow-in-right"></i> Login to Apply
                                                </a>
                                            <?php endif; ?>
                                            <button class="btn btn-outline-secondary" onclick="viewJobDetails(<?php echo $job['id']; ?>)">
                                                <i class="bi bi-eye"></i> View Details
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Job Details Modal -->
    <div class="modal fade" id="jobModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalJobTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalJobContent">
                    <!-- Job details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <div id="modalApplyButton"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-4 mt-5">
        <div class="container">
            <p>&copy; 2024 Job Portal. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Store all jobs data for filtering
        let allJobs = <?php echo json_encode($jobs); ?>;

        // Populate location filter
        function populateLocationFilter() {
            const locationFilter = document.getElementById('locationFilter');
            const locations = [...new Set(allJobs.map(job => job.location).filter(Boolean))];
            
            locations.forEach(location => {
                const option = document.createElement('option');
                option.value = location;
                option.textContent = location;
                locationFilter.appendChild(option);
            });
        }

        // Filter jobs
        function filterJobs() {
            const keyword = document.getElementById('searchKeyword').value.toLowerCase();
            const location = document.getElementById('locationFilter').value;
            
            const jobItems = document.querySelectorAll('.job-item');
            
            jobItems.forEach(item => {
                const jobData = JSON.parse(item.dataset.job);
                const matchesKeyword = !keyword || 
                    jobData.title.toLowerCase().includes(keyword) ||
                    jobData.company.toLowerCase().includes(keyword) ||
                    jobData.description.toLowerCase().includes(keyword);
                
                const matchesLocation = !location || jobData.location === location;
                
                if (matchesKeyword && matchesLocation) {
                    item.style.display = 'block';
                    item.classList.add('fade-in');
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // View job details
        function viewJobDetails(jobId) {
            const job = allJobs.find(j => j.id == jobId);
            if (!job) return;

            document.getElementById('modalJobTitle').textContent = job.title;
            document.getElementById('modalJobContent').innerHTML = `
                <div class="mb-3">
                    <h6><i class="bi bi-building"></i> ${job.company}</h6>
                    <p class="text-muted mb-2">
                        <i class="bi bi-geo-alt"></i> ${job.location || 'Location not specified'}
                        ${job.salary_range ? '<span class="ms-3"><i class="bi bi-cash"></i> ' + job.salary_range + '</span>' : ''}
                    </p>
                </div>
                <div class="mb-3">
                    <h6>Job Description</h6>
                    <p>${job.description}</p>
                </div>
                ${job.requirements ? '<div class="mb-3"><h6>Requirements</h6><p>' + job.requirements + '</p></div>' : ''}
                <div class="text-muted">
                    <small><i class="bi bi-calendar"></i> Posted: ${new Date(job.created_at).toLocaleDateString()}</small>
                </div>
            `;

            const isLoggedIn = <?php echo json_encode(isLoggedIn()); ?>;
            document.getElementById('modalApplyButton').innerHTML = isLoggedIn 
                ? `<a href="apply.php?job_id=${job.id}" class="btn btn-primary"><i class="bi bi-send"></i> Apply Now</a>`
                : `<a href="login.php?redirect=apply.php?job_id=${job.id}" class="btn btn-outline-primary"><i class="bi bi-box-arrow-in-right"></i> Login to Apply</a>`;

            new bootstrap.Modal(document.getElementById('jobModal')).show();
        }

        // Real-time search
        document.getElementById('searchKeyword').addEventListener('input', filterJobs);
        document.getElementById('locationFilter').addEventListener('change', filterJobs);

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            populateLocationFilter();
        });
    </script>
</body>
</html>