/**
 * Job Portal Frontend JavaScript
 * Handles dynamic interactions, form validation, and UI enhancements
 */

// Global variables
let currentJobsData = [];

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

/**
 * Initialize the application
 */
function initializeApp() {
    // Add loading animations
    addLoadingAnimations();
    
    // Initialize forms
    initializeForms();
    
    // Initialize tooltips
    initializeTooltips();
    
    // Initialize search functionality
    initializeSearch();
    
    // Initialize file upload handlers
    initializeFileUploads();
    
    // Add smooth scrolling
    addSmoothScrolling();
}

/**
 * Add loading animations to page elements
 */
function addLoadingAnimations() {
    const elements = document.querySelectorAll('.card, .btn, .nav-link');
    
    elements.forEach((element, index) => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            element.style.transition = 'all 0.6s ease';
            element.style.opacity = '1';
            element.style.transform = 'translateY(0)';
        }, index * 50);
    });
}

/**
 * Initialize form validations and enhancements
 */
function initializeForms() {
    // Add real-time validation to forms
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        // Add input validation
        const inputs = form.querySelectorAll('input, textarea, select');
        
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            input.addEventListener('input', function() {
                // Clear error states on input
                this.classList.remove('is-invalid');
                const feedback = this.parentNode.querySelector('.invalid-feedback');
                if (feedback) {
                    feedback.remove();
                }
            });
        });
        
        // Add form submission handler
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                return false;
            }
            
            // Add loading state to submit button
            const submitBtn = this.querySelector('[type="submit"]');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="bi bi-arrow-clockwise spin"></i> Processing...';
                submitBtn.disabled = true;
                
                // Re-enable after 3 seconds (fallback)
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 3000);
            }
        });
    });
}

/**
 * Validate individual form field
 */
function validateField(field) {
    let isValid = true;
    let message = '';
    
    // Remove existing error
    field.classList.remove('is-invalid');
    const existingFeedback = field.parentNode.querySelector('.invalid-feedback');
    if (existingFeedback) {
        existingFeedback.remove();
    }
    
    // Required field validation
    if (field.required && !field.value.trim()) {
        isValid = false;
        message = 'This field is required';
    }
    
    // Email validation
    if (field.type === 'email' && field.value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(field.value)) {
            isValid = false;
            message = 'Please enter a valid email address';
        }
    }
    
    // Password validation
    if (field.type === 'password' && field.value) {
        if (field.value.length < 6) {
            isValid = false;
            message = 'Password must be at least 6 characters';
        }
    }
    
    // Phone validation
    if (field.type === 'tel' && field.value) {
        const phoneRegex = /^[\+]?[\d\s\-\(\)]{10,}$/;
        if (!phoneRegex.test(field.value)) {
            isValid = false;
            message = 'Please enter a valid phone number';
        }
    }
    
    // Show error if invalid
    if (!isValid) {
        field.classList.add('is-invalid');
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.textContent = message;
        field.parentNode.appendChild(feedback);
    }
    
    return isValid;
}

/**
 * Validate entire form
 */
function validateForm(form) {
    let isValid = true;
    const fields = form.querySelectorAll('input[required], textarea[required], select[required]');
    
    fields.forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });
    
    // Password confirmation check
    const password = form.querySelector('input[name="password"]');
    const confirmPassword = form.querySelector('input[name="confirm_password"]');
    
    if (password && confirmPassword && password.value !== confirmPassword.value) {
        confirmPassword.classList.add('is-invalid');
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.textContent = 'Passwords do not match';
        confirmPassword.parentNode.appendChild(feedback);
        isValid = false;
    }
    
    return isValid;
}

/**
 * Initialize Bootstrap tooltips
 */
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Initialize search functionality
 */
function initializeSearch() {
    const searchInput = document.getElementById('searchKeyword');
    const locationFilter = document.getElementById('locationFilter');
    
    if (searchInput) {
        // Add debounced search
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch();
            }, 300);
        });
    }
    
    if (locationFilter) {
        locationFilter.addEventListener('change', performSearch);
    }
}

/**
 * Perform job search and filtering
 */
function performSearch() {
    const searchTerm = document.getElementById('searchKeyword')?.value.toLowerCase() || '';
    const locationFilter = document.getElementById('locationFilter')?.value || '';
    const jobItems = document.querySelectorAll('.job-item');
    
    let visibleCount = 0;
    
    jobItems.forEach(item => {
        const jobData = JSON.parse(item.dataset.job || '{}');
        
        const matchesSearch = !searchTerm || 
            jobData.title?.toLowerCase().includes(searchTerm) ||
            jobData.company?.toLowerCase().includes(searchTerm) ||
            jobData.description?.toLowerCase().includes(searchTerm) ||
            jobData.requirements?.toLowerCase().includes(searchTerm);
        
        const matchesLocation = !locationFilter || jobData.location === locationFilter;
        
        if (matchesSearch && matchesLocation) {
            item.style.display = 'block';
            item.classList.add('fade-in');
            visibleCount++;
        } else {
            item.style.display = 'none';
            item.classList.remove('fade-in');
        }
    });
    
    // Show no results message if needed
    updateSearchResults(visibleCount);
}

/**
 * Update search results display
 */
function updateSearchResults(count) {
    const container = document.getElementById('jobListings');
    let noResultsMsg = container.querySelector('.no-results');
    
    if (count === 0) {
        if (!noResultsMsg) {
            noResultsMsg = document.createElement('div');
            noResultsMsg.className = 'col-12 no-results';
            noResultsMsg.innerHTML = `
                <div class="alert alert-info text-center">
                    <i class="bi bi-search"></i>
                    <h5>No jobs found</h5>
                    <p>Try adjusting your search criteria or browse all available positions.</p>
                    <button class="btn btn-outline-primary" onclick="clearSearch()">
                        <i class="bi bi-arrow-clockwise"></i> Clear Search
                    </button>
                </div>
            `;
            container.appendChild(noResultsMsg);
        }
    } else if (noResultsMsg) {
        noResultsMsg.remove();
    }
}

/**
 * Clear search filters
 */
function clearSearch() {
    const searchInput = document.getElementById('searchKeyword');
    const locationFilter = document.getElementById('locationFilter');
    
    if (searchInput) searchInput.value = '';
    if (locationFilter) locationFilter.value = '';
    
    performSearch();
}

/**
 * Initialize file upload functionality
 */
function initializeFileUploads() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(input => {
        // Add drag and drop functionality
        const uploadArea = input.parentNode.querySelector('.upload-area');
        
        if (uploadArea) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, preventDefaults, false);
                document.body.addEventListener(eventName, preventDefaults, false);
            });
            
            ['dragenter', 'dragover'].forEach(eventName => {
                uploadArea.addEventListener(eventName, highlight, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, unhighlight, false);
            });
            
            uploadArea.addEventListener('drop', handleDrop, false);
        }
        
        // File validation
        input.addEventListener('change', function() {
            validateFile(this);
        });
    });
}

/**
 * Prevent default drag behaviors
 */
function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

/**
 * Highlight drop zone
 */
function highlight(e) {
    e.currentTarget.classList.add('dragover');
}

/**
 * Remove highlight from drop zone
 */
function unhighlight(e) {
    e.currentTarget.classList.remove('dragover');
}

/**
 * Handle file drop
 */
function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    const input = e.currentTarget.parentNode.querySelector('input[type="file"]');
    
    if (input && files.length > 0) {
        input.files = files;
        input.dispatchEvent(new Event('change'));
    }
}

/**
 * Validate uploaded file
 */
function validateFile(input) {
    const file = input.files[0];
    if (!file) return;
    
    const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    const maxSize = 5 * 1024 * 1024; // 5MB
    
    let isValid = true;
    let message = '';
    
    if (!allowedTypes.includes(file.type)) {
        isValid = false;
        message = 'Only PDF, DOC, and DOCX files are allowed';
    } else if (file.size > maxSize) {
        isValid = false;
        message = 'File size must be less than 5MB';
    }
    
    if (!isValid) {
        showNotification(message, 'error');
        input.value = '';
        return false;
    }
    
    // Show file info
    showFileInfo(input, file);
    return true;
}

/**
 * Show file information
 */
function showFileInfo(input, file) {
    const fileInfo = input.parentNode.querySelector('.file-info') || 
                     input.parentNode.querySelector('#file-info');
    
    if (fileInfo) {
        fileInfo.style.display = 'block';
        const fileName = fileInfo.querySelector('.file-name') || 
                        fileInfo.querySelector('#file-name');
        
        if (fileName) {
            fileName.textContent = file.name;
        }
        
        // Hide upload area
        const uploadArea = input.parentNode.querySelector('.upload-area');
        if (uploadArea) {
            uploadArea.style.display = 'none';
        }
    }
}

/**
 * Add smooth scrolling to anchor links
 */
function addSmoothScrolling() {
    const links = document.querySelectorAll('a[href^="#"]');
    
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            const target = document.querySelector(this.getAttribute('href'));
            
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

/**
 * Show notification message
 */
function showNotification(message, type = 'info', duration = 5000) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    
    const icon = {
        'success': 'check-circle',
        'error': 'exclamation-triangle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    }[type] || 'info-circle';
    
    notification.innerHTML = `
        <i class="bi bi-${icon}"></i> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto dismiss after duration
    setTimeout(() => {
        if (notification && notification.parentNode) {
            notification.remove();
        }
    }, duration);
}

/**
 * Format date strings consistently
 */
function formatDate(dateString, options = {}) {
    const date = new Date(dateString);
    const defaultOptions = {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    };
    
    return date.toLocaleDateString('en-US', { ...defaultOptions, ...options });
}

/**
 * Format time strings consistently
 */
function formatTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit'
    });
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

/**
 * Debounce function to limit function calls
 */
function debounce(func, wait, immediate) {
    let timeout;
    return function executedFunction() {
        const context = this;
        const args = arguments;
        
        const later = function() {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        
        if (callNow) func.apply(context, args);
    };
}

/**
 * Add CSS animation class
 */
function addAnimation(element, animationName, callback) {
    element.classList.add(animationName);
    
    element.addEventListener('animationend', function handler() {
        element.classList.remove(animationName);
        element.removeEventListener('animationend', handler);
        if (callback) callback();
    });
}

// Export functions for global use
window.jobPortal = {
    performSearch,
    clearSearch,
    showNotification,
    formatDate,
    formatTime,
    escapeHtml,
    debounce,
    addAnimation
};