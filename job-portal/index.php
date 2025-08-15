<?php
/**
 * Job Portal - Main Landing Page
 * 
 * This is the primary interface for the Job Portal application that displays
 * all available job listings with search, filtering, and detailed viewing capabilities.
 * 
 * Features:
 * - Dynamic job listing display from database
 * - Real-time search and filtering (JavaScript-powered)
 * - User authentication integration
 * - Role-based access control (user/admin/guest)
 * - Responsive design with Bootstrap 5
 * - Modal-based job detail viewing
 * - Secure database interactions with PDO
 * 
 * Dependencies:
 * - config.php: Database configuration and helper functions
 * - Bootstrap 5.1.3: CSS framework and components
 * - Bootstrap Icons 1.8.1: Icon library
 * - style.css: Custom styling
 * 
 * Database Tables Used:
 * - jobs: Job listings (id, title, company, location, salary_range, description, requirements, is_active, created_by, created_at)
 * - users: User accounts for authentication
 * - applications: Job application tracking
 * 
 * @author Your Name
 * @version 1.0
 * @since 2024
 */

// Include database configuration and helper functions
require_once 'config.php';

/**
 * Fetch all active job listings from database
 * 
 * Uses prepared statement for security to prevent SQL injection.
 * Orders results by creation date (newest first) to show recent postings.
 * Only retrieves jobs where is_active = 1 to hide inactive/expired listings.
 */
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
    
    <!-- Bootstrap 5 CSS Framework for responsive design and components -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons for UI elements (briefcase, person, search, etc.) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS for additional styling and theme customization -->
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <!-- 
    NAVIGATION BAR
    
    Features:
    - Responsive collapse menu for mobile devices
    - Dynamic content based on user authentication status
    - Role-based menu items (admin panel for admin users)
    - Brand logo with briefcase icon
    -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <!-- Brand logo and name -->
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-briefcase"></i> Job Portal
            </a>
            
            <!-- Mobile menu toggle button -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Navigation menu items -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isLoggedIn()): ?>
                        <!-- Authenticated user menu items -->
                        
                        <!-- User dashboard link for viewing applications -->
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="bi bi-person-circle"></i> My Applications
                            </a>
                        </li>
                        
                        <!-- Admin panel link (only visible to admin users) -->
                        <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/index.php">
                                <i class="bi bi-gear"></i> Admin Panel
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <!-- Welcome message with user's first name -->
                        <li class="nav-item">
                            <span class="nav-link">Welcome, <?php echo sanitize($_SESSION['first_name']); ?>!</span>
                        </li>
                        
                        <!-- Logout link -->
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </li>
                    <?php else: ?>
                        <!-- Guest user menu items -->
                        
                        <!-- Login link -->
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </a>
                        </li>
                        
                        <!-- Registration link -->
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

    <!-- 
    HERO SECTION
    
    Welcome banner with call-to-action messaging to engage visitors
    and communicate the site's purpose immediately.
    -->
    <section class="hero-section">
        <div class="container">
            <h1>Find Your Dream Job</h1>
            <p>Discover amazing opportunities and take the next step in your career</p>
        </div>
    </section>

    <!-- MAIN CONTENT AREA -->
    <div class="container mt-5">
        <!-- 
        SEARCH AND FILTER SECTION
        
        Provides real-time search functionality without page reloads.
        Features:
        - Keyword search across job title, company, and description
        - Location-based filtering with dynamic dropdown population
        - Instant results as user types (JavaScript-powered)
        -->
        <div class="row mb-4">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title text-center mb-3">
                            <i class="bi bi-search"></i> Search Jobs
                        </h5>
                        <div class="row">
                            <!-- Keyword search input -->
                            <div class="col-md-6 mb-3">
                                <input type="text" class="form-control" id="searchKeyword" 
                                       placeholder="Search by keyword, company, or title">
                            </div>
                            
                            <!-- Location filter dropdown (populated dynamically by JavaScript) -->
                            <div class="col-md-4 mb-3">
                                <select class="form-control" id="locationFilter">
                                    <option value="">All Locations</option>
                                    <!-- Options populated by JavaScript from job data -->
                                </select>
                            </div>
                            
                            <!-- Search trigger button -->
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

        <!-- 
        JOB LISTINGS SECTION
        
        Displays all available job positions in a responsive grid layout.
        Each job card contains essential information and action buttons.
        Layout adapts from 3 columns (large screens) to 1 column (mobile).
        -->
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">Available Positions</h2>
                <div id="jobListings" class="row">
                    <?php if (empty($jobs)): ?>
                        <!-- Empty state when no jobs are available -->
                        <div class="col-12">
                            <div class="alert alert-info text-center">
                                <i class="bi bi-info-circle"></i> No jobs available at the moment. Please check back later.
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Loop through each job and create a card -->
                        <?php foreach ($jobs as $job): ?>
                        <div class="col-md-6 col-lg-4 mb-4 job-item" data-job='<?php echo json_encode($job); ?>'>
                            <!-- 
                            JOB CARD
                            
                            Each card contains:
                            - Job title and company information
                            - Location and salary (if available)
                            - Truncated description (120 characters)
                            - Posted date
                            - Action buttons (Apply/View Details)
                            
                            The data-job attribute stores complete job data for JavaScript filtering.
                            -->
                            <div class="card job-card h-100">
                                <div class="card-body">
                                    <!-- Job title -->
                                    <h5 class="card-title"><?php echo sanitize($job['title']); ?></h5>
                                    
                                    <!-- Company name with building icon -->
                                    <h6 class="card-subtitle mb-2 text-muted">
                                        <i class="bi bi-building"></i> <?php echo sanitize($job['company']); ?>
                                    </h6>
                                    
                                    <!-- Job metadata (location and salary) -->
                                    <div class="job-meta">
                                        <!-- Location with geo icon -->
                                        <span><i class="bi bi-geo-alt"></i> <?php echo sanitize($job['location'] ?: 'Location not specified'); ?></span>
                                        
                                        <!-- Salary range (only if provided) -->
                                        <?php if ($job['salary_range']): ?>
                                        <span><i class="bi bi-cash"></i> <?php echo sanitize($job['salary_range']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Truncated job description (first 120 characters) -->
                                    <p class="card-text"><?php echo substr(sanitize($job['description']), 0, 120) . '...'; ?></p>
                                    
                                    <!-- Card footer with date and action buttons -->
                                    <div class="mt-auto">
                                        <!-- Posted date -->
                                        <small class="text-muted">
                                            <i class="bi bi-calendar"></i> Posted: <?php echo date('M j, Y', strtotime($job['created_at'])); ?>
                                        </small>
                                        
                                        <!-- Action buttons -->
                                        <div class="mt-2">
                                            <?php if (isLoggedIn()): ?>
                                                <!-- Apply button for authenticated users -->
                                                <a href="apply.php?job_id=<?php echo $job['id']; ?>" class="btn btn-primary">
                                                    <i class="bi bi-send"></i> Apply Now
                                                </a>
                                            <?php else: ?>
                                                <!-- Login prompt for guest users with redirect -->
                                                <a href="login.php?redirect=apply.php?job_id=<?php echo $job['id']; ?>" class="btn btn-outline-primary">
                                                    <i class="bi bi-box-arrow-in-right"></i> Login to Apply
                                                </a>
                                            <?php endif; ?>
                                            
                                            <!-- View details button (opens modal) -->
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

    <!-- 
    JOB DETAILS MODAL
    
    Bootstrap modal for displaying complete job information without page navigation.
    Content is populated dynamically by JavaScript when "View Details" is clicked.
    
    Features:
    - Complete job description and requirements
    - Company and location details
    - Posted date
    - Context-aware apply button
    -->
    <div class="modal fade" id="jobModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <!-- Modal header with job title -->
                <div class="modal-header">
                    <h5 class="modal-title" id="modalJobTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <!-- Modal body with job details (populated by JavaScript) -->
                <div class="modal-body" id="modalJobContent">
                    <!-- Job details will be loaded here by viewJobDetails() function -->
                </div>
                
                <!-- Modal footer with action buttons -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <div id="modalApplyButton">
                        <!-- Apply button populated by JavaScript based on authentication status -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="bg-dark text-white text-center py-4 mt-5">
        <div class="container">
            <p>&copy; 2024 Job Portal. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap JavaScript for interactive components (modals, collapse menu, etc.) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        /**
         * CLIENT-SIDE JAVASCRIPT FUNCTIONALITY
         * 
         * This section handles all interactive features without requiring page reloads:
         * - Real-time job search and filtering
         * - Dynamic location dropdown population
         * - Job details modal management
         * - Event handling for user interactions
         */

        /**
         * Store all jobs data in JavaScript for client-side filtering
         * 
         * This eliminates the need for server requests during search/filter operations,
         * providing instant results and reducing server load.
         */
        let allJobs = <?php echo json_encode($jobs); ?>;

        /**
         * Populate location filter dropdown with unique job locations
         * 
         * Extracts all unique, non-empty location values from the jobs data
         * and creates option elements for the location filter dropdown.
         * This is done dynamically to ensure the filter always reflects
         * available locations in the current job listings.
         */
        function populateLocationFilter() {
            const locationFilter = document.getElementById('locationFilter');
            
            // Extract unique locations, filtering out empty/null values
            const locations = [...new Set(allJobs.map(job => job.location).filter(Boolean))];
            
            // Create option elements for each location
            locations.forEach(location => {
                const option = document.createElement('option');
                option.value = location;
                option.textContent = location;
                locationFilter.appendChild(option);
            });
        }

        /**
         * Filter jobs based on search criteria
         * 
         * Performs client-side filtering of job listings based on:
         * - Keyword search (searches in title, company, and description)
         * - Location selection
         * 
         * Shows/hides job cards dynamically without page reload.
         * Provides instant feedback as user types or changes filters.
         */
        function filterJobs() {
            // Get current search criteria
            const keyword = document.getElementById('searchKeyword').value.toLowerCase();
            const location = document.getElementById('locationFilter').value;
            
            // Get all job card elements
            const jobItems = document.querySelectorAll('.job-item');
            
            // Check each job against filter criteria
            jobItems.forEach(item => {
                // Parse job data from data attribute
                const jobData = JSON.parse(item.dataset.job);
                
                // Check keyword match (case-insensitive search in title, company, description)
                const matchesKeyword = !keyword || 
                    jobData.title.toLowerCase().includes(keyword) ||
                    jobData.company.toLowerCase().includes(keyword) ||
                    jobData.description.toLowerCase().includes(keyword);
                
                // Check location match (exact match or "All Locations")
                const matchesLocation = !location || jobData.location === location;
                
                // Show/hide job card based on filter results
                if (matchesKeyword && matchesLocation) {
                    item.style.display = 'block';
                    item.classList.add('fade-in'); // Add animation class
                } else {
                    item.style.display = 'none';
                }
            });
        }

        /**
         * Display detailed job information in modal
         * 
         * Finds the specified job by ID and populates the modal with
         * complete job details including description, requirements,
         * and context-aware action buttons.
         * 
         * @param {number} jobId - The ID of the job to display
         */
        function viewJobDetails(jobId) {
            // Find job data by ID
            const job = allJobs.find(j => j.id == jobId);
            if (!job) return; // Exit if job not found

            // Set modal title
            document.getElementById('modalJobTitle').textContent = job.title;
            
            // Generate modal content with complete job details
            document.getElementById('modalJobContent').innerHTML = `
                <!-- Company and location information -->
                <div class="mb-3">
                    <h6><i class="bi bi-building"></i> ${job.company}</h6>
                    <p class="text-muted mb-2">
                        <i class="bi bi-geo-alt"></i> ${job.location || 'Location not specified'}
                        ${job.salary_range ? '<span class="ms-3"><i class="bi bi-cash"></i> ' + job.salary_range + '</span>' : ''}
                    </p>
                </div>
                
                <!-- Full job description -->
                <div class="mb-3">
                    <h6>Job Description</h6>
                    <p>${job.description}</p>
                </div>
                
                <!-- Requirements (if available) -->
                ${job.requirements ? '<div class="mb-3"><h6>Requirements</h6><p>' + job.requirements + '</p></div>' : ''}
                
                <!-- Posted date -->
                <div class="text-muted">
                    <small><i class="bi bi-calendar"></i> Posted: ${new Date(job.created_at).toLocaleDateString()}</small>
                </div>
            `;

            // Generate context-aware apply button based on authentication status
            const isLoggedIn = <?php echo json_encode(isLoggedIn()); ?>;
            document.getElementById('modalApplyButton').innerHTML = isLoggedIn 
                ? `<a href="apply.php?job_id=${job.id}" class="btn btn-primary"><i class="bi bi-send"></i> Apply Now</a>`
                : `<a href="login.php?redirect=apply.php?job_id=${job.id}" class="btn btn-outline-primary"><i class="bi bi-box-arrow-in-right"></i> Login to Apply</a>`;

            // Show the modal using Bootstrap's modal component
            new bootstrap.Modal(document.getElementById('jobModal')).show();
        }

        /**
         * EVENT LISTENERS
         * 
         * Set up real-time event handling for interactive features:
         * - Real-time search as user types in keyword field
         * - Instant filtering when location selection changes
         */
        
        // Real-time search - triggers filtering on every keystroke
        document.getElementById('searchKeyword').addEventListener('input', filterJobs);
        
        // Location filter - triggers filtering when selection changes
        document.getElementById('locationFilter').addEventListener('change', filterJobs);

        /**
         * INITIALIZATION
         * 
         * Set up the page when DOM is fully loaded:
         * - Populate location filter with available locations
         * - Ensure all interactive elements are ready
         */
        document.addEventListener('DOMContentLoaded', function() {
            populateLocationFilter();
        });
    </script>
</body>
</html>