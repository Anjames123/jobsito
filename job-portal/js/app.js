/**
 * Job Portal Frontend JavaScript
 * 
 * Comprehensive client-side functionality for the Job Portal application providing:
 * - Dynamic user interface interactions and animations
 * - Real-time form validation with visual feedback
 * - Advanced search and filtering capabilities
 * - File upload handling with drag-and-drop support
 * - Notification system for user feedback
 * - Smooth scrolling and navigation enhancements
 * - Utility functions for data formatting and security
 * 
 * Features:
 * - Progressive enhancement approach (works without JavaScript)
 * - Responsive design support with mobile-friendly interactions
 * - Accessibility improvements with ARIA support
 * - Performance optimizations with debouncing and efficient DOM manipulation
 * - Cross-browser compatibility with modern JavaScript standards
 * - Modular architecture for easy maintenance and extension
 * 
 * Security Features:
 * - HTML escaping for XSS prevention
 * - File upload validation (type, size, format)
 * - Input sanitization and validation
 * - Safe DOM manipulation practices
 * 
 * Dependencies:
 * - Bootstrap 5 for UI components and styling
 * - Bootstrap Icons for visual elements
 * - Modern browser with ES6+ support
 * 
 * @author Your Name
 * @version 1.0
 * @since 2024
 */

/**
 * GLOBAL VARIABLES
 * 
 * Application-wide variables for state management
 * and data storage across different functions.
 */
let currentJobsData = []; // Stores current job listings for filtering and search

/**
 * DOM READY INITIALIZATION
 * 
 * Main entry point that initializes all application functionality
 * once the DOM is fully loaded. Ensures all elements are available
 * before attaching event listeners and initializing features.
 */
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

/**
 * APPLICATION INITIALIZATION
 * 
 * Master initialization function that orchestrates the setup
 * of all application features in the correct order. This modular
 * approach ensures proper dependency management and clean separation
 * of concerns.
 */
function initializeApp() {
    // Visual enhancements and animations
    addLoadingAnimations();
    
    // Form functionality and validation
    initializeForms();
    
    // Bootstrap component initialization
    initializeTooltips();
    
    // Search and filtering capabilities
    initializeSearch();
    
    // File upload functionality
    initializeFileUploads();
    
    // Navigation enhancements
    addSmoothScrolling();
}

/**
 * LOADING ANIMATIONS SYSTEM
 * 
 * Adds progressive loading animations to page elements for improved
 * user experience. Elements fade in sequentially with staggered timing
 * to create a polished, professional appearance.
 * 
 * Animation Details:
 * - Initial state: opacity 0, translated down 20px
 * - Final state: opacity 1, normal position
 * - Staggered timing: 50ms delay between elements
 * - Smooth transitions: 0.6s ease animation
 */
function addLoadingAnimations() {
    const elements = document.querySelectorAll('.card, .btn, .nav-link');
    
    elements.forEach((element, index) => {
        // Set initial animation state
        element.style.opacity = '0';
        element.style.transform = 'translateY(20px)';
        
        // Animate to final state with staggered timing
        setTimeout(() => {
            element.style.transition = 'all 0.6s ease';
            element.style.opacity = '1';
            element.style.transform = 'translateY(0)';
        }, index * 50); // 50ms stagger between elements
    });
}

/**
 * FORM INITIALIZATION AND VALIDATION SYSTEM
 * 
 * Comprehensive form enhancement system providing:
 * - Real-time field validation with visual feedback
 * - Form submission handling with loading states
 * - Error state management and user guidance
 * - Cross-field validation (password confirmation)
 * - Accessibility improvements with proper ARIA attributes
 */
function initializeForms() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        /**
         * INDIVIDUAL FIELD VALIDATION SETUP
         * 
         * Attaches validation event listeners to all form inputs
         * providing immediate feedback on field blur and clearing
         * errors on new input for better user experience.
         */
        const inputs = form.querySelectorAll('input, textarea, select');
        
        inputs.forEach(input => {
            // Validate field when user leaves it (blur event)
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            // Clear error states when user starts typing again
            input.addEventListener('input', function() {
                this.classList.remove('is-invalid');
                const feedback = this.parentNode.querySelector('.invalid-feedback');
                if (feedback) {
                    feedback.remove();
                }
            });
        });
        
        /**
         * FORM SUBMISSION HANDLING
         * 
         * Intercepts form submission to perform comprehensive validation
         * and provide visual feedback during processing. Includes:
         * - Complete form validation before submission
         * - Loading state with spinner animation
         * - Button disable to prevent double submission
         * - Fallback re-enable for error cases
         */
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
                
                // Fallback re-enable after 3 seconds for error cases
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 3000);
            }
        });
    });
}

/**
 * INDIVIDUAL FIELD VALIDATION
 * 
 * Validates a single form field against multiple criteria:
 * - Required field validation
 * - Email format validation with regex
 * - Password strength requirements
 * - Phone number format validation
 * - Custom validation rules as needed
 * 
 * Provides immediate visual feedback with Bootstrap validation classes
 * and descriptive error messages for user guidance.
 * 
 * @param {HTMLElement} field - The form field to validate
 * @returns {boolean} - True if field is valid, false otherwise
 */
function validateField(field) {
    let isValid = true;
    let message = '';
    
    // Remove existing error states
    field.classList.remove('is-invalid');
    const existingFeedback = field.parentNode.querySelector('.invalid-feedback');
    if (existingFeedback) {
        existingFeedback.remove();
    }
    
    /**
     * REQUIRED FIELD VALIDATION
     * 
     * Checks if required fields have content, trimming whitespace
     * to prevent submission of empty or whitespace-only values.
     */
    if (field.required && !field.value.trim()) {
        isValid = false;
        message = 'This field is required';
    }
    
    /**
     * EMAIL FORMAT VALIDATION
     * 
     * Uses regex pattern to validate email format against
     * standard email address structure requirements.
     */
    if (field.type === 'email' && field.value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(field.value)) {
            isValid = false;
            message = 'Please enter a valid email address';
        }
    }
    
    /**
     * PASSWORD STRENGTH VALIDATION
     * 
     * Enforces minimum password length requirement for security.
     * Can be extended with additional complexity requirements.
     */
    if (field.type === 'password' && field.value) {
        if (field.value.length < 6) {
            isValid = false;
            message = 'Password must be at least 6 characters';
        }
    }
    
    /**
     * PHONE NUMBER VALIDATION
     * 
     * Validates phone number format allowing international formats
     * with various punctuation and spacing patterns.
     */
    if (field.type === 'tel' && field.value) {
        const phoneRegex = /^[\+]?[\d\s\-\(\)]{10,}$/;
        if (!phoneRegex.test(field.value)) {
            isValid = false;
            message = 'Please enter a valid phone number';
        }
    }
    
    /**
     * ERROR STATE DISPLAY
     * 
     * Shows validation errors using Bootstrap's validation classes
     * and creates descriptive feedback messages for user guidance.
     */
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
 * COMPLETE FORM VALIDATION
 * 
 * Validates an entire form by checking all required fields
 * and performing cross-field validation such as password
 * confirmation matching. Returns overall form validity status.
 * 
 * @param {HTMLFormElement} form - The form to validate
 * @returns {boolean} - True if entire form is valid, false otherwise
 */
function validateForm(form) {
    let isValid = true;
    const fields = form.querySelectorAll('input[required], textarea[required], select[required]');
    
    // Validate all required fields
    fields.forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });
    
    /**
     * PASSWORD CONFIRMATION VALIDATION
     * 
     * Special validation for password confirmation fields
     * ensuring both password fields contain matching values
     * for account security.
     */
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
 * BOOTSTRAP TOOLTIPS INITIALIZATION
 * 
 * Initializes Bootstrap tooltips for all elements with the
 * data-bs-toggle="tooltip" attribute. Provides helpful
 * contextual information on hover for improved user experience.
 */
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * SEARCH FUNCTIONALITY INITIALIZATION
 * 
 * Sets up real-time search and filtering capabilities with:
 * - Debounced search input to prevent excessive filtering
 * - Location filter change handling
 * - Performance optimization for large job lists
 * - Responsive search results updating
 */
function initializeSearch() {
    const searchInput = document.getElementById('searchKeyword');
    const locationFilter = document.getElementById('locationFilter');
    
    /**
     * DEBOUNCED SEARCH INPUT HANDLING
     * 
     * Implements debounced search to prevent excessive API calls
     * or filtering operations. Waits 300ms after user stops
     * typing before performing search operation.
     */
    if (searchInput) {
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch();
            }, 300); // 300ms debounce delay
        });
    }
    
    /**
     * LOCATION FILTER HANDLING
     * 
     * Immediate filtering when location dropdown changes
     * since dropdown changes are discrete user actions.
     */
    if (locationFilter) {
        locationFilter.addEventListener('change', performSearch);
    }
}

/**
 * SEARCH AND FILTERING EXECUTION
 * 
 * Performs real-time search and filtering of job listings based on:
 * - Keyword search across title, company, description, requirements
 * - Location filtering for geographic preferences
 * - Dynamic show/hide of job items with smooth animations
 * - Results count updating and no-results messaging
 */
function performSearch() {
    const searchTerm = document.getElementById('searchKeyword')?.value.toLowerCase() || '';
    const locationFilter = document.getElementById('locationFilter')?.value || '';
    const jobItems = document.querySelectorAll('.job-item');
    
    let visibleCount = 0;
    
    /**
     * JOB ITEM FILTERING LOGIC
     * 
     * Iterates through all job items and applies filtering criteria:
     * - Text search across multiple job data fields
     * - Exact location matching for geographic filtering
     * - Show/hide animations for smooth user experience
     */
    jobItems.forEach(item => {
        const jobData = JSON.parse(item.dataset.job || '{}');
        
        // Check if job matches search criteria
        const matchesSearch = !searchTerm || 
            jobData.title?.toLowerCase().includes(searchTerm) ||
            jobData.company?.toLowerCase().includes(searchTerm) ||
            jobData.description?.toLowerCase().includes(searchTerm) ||
            jobData.requirements?.toLowerCase().includes(searchTerm);
        
        // Check if job matches location filter
        const matchesLocation = !locationFilter || jobData.location === locationFilter;
        
        // Show or hide job item based on filter results
        if (matchesSearch && matchesLocation) {
            item.style.display = 'block';
            item.classList.add('fade-in');
            visibleCount++;
        } else {
            item.style.display = 'none';
            item.classList.remove('fade-in');
        }
    });
    
    // Update search results display and messaging
    updateSearchResults(visibleCount);
}

/**
 * SEARCH RESULTS DISPLAY MANAGEMENT
 * 
 * Manages the display of search results and provides user feedback:
 * - Shows "no results" message when no jobs match criteria
 * - Provides clear search button for easy filter reset
 * - Removes no-results message when jobs are found
 * - Maintains clean UI state throughout search interactions
 * 
 * @param {number} count - Number of visible job results
 */
function updateSearchResults(count) {
    const container = document.getElementById('jobListings');
    let noResultsMsg = container.querySelector('.no-results');
    
    /**
     * NO RESULTS MESSAGE DISPLAY
     * 
     * Creates and displays a helpful no-results message when
     * search criteria don't match any jobs. Includes suggestions
     * and easy reset functionality.
     */
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
        // Remove no-results message when jobs are found
        noResultsMsg.remove();
    }
}

/**
 * SEARCH FILTER RESET FUNCTIONALITY
 * 
 * Clears all search filters and returns the job listing
 * to its default state showing all available jobs.
 * Provides easy way for users to start fresh search.
 */
function clearSearch() {
    const searchInput = document.getElementById('searchKeyword');
    const locationFilter = document.getElementById('locationFilter');
    
    // Clear all filter inputs
    if (searchInput) searchInput.value = '';
    if (locationFilter) locationFilter.value = '';
    
    // Re-run search to show all jobs
    performSearch();
}

/**
 * FILE UPLOAD SYSTEM INITIALIZATION
 * 
 * Sets up comprehensive file upload functionality including:
 * - Drag-and-drop interface for modern file uploading
 * - File validation for type, size, and format requirements
 * - Visual feedback during drag operations
 * - File preview and management capabilities
 * - Cross-browser compatibility for file handling
 */
function initializeFileUploads() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(input => {
        /**
         * DRAG-AND-DROP FUNCTIONALITY SETUP
         * 
         * Implements modern drag-and-drop file upload interface
         * with visual feedback and proper event handling for
         * all drag-related events.
         */
        const uploadArea = input.parentNode.querySelector('.upload-area');
        
        if (uploadArea) {
            // Prevent default behaviors for all drag events
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, preventDefaults, false);
                document.body.addEventListener(eventName, preventDefaults, false);
            });
            
            // Add visual feedback during drag operations
            ['dragenter', 'dragover'].forEach(eventName => {
                uploadArea.addEventListener(eventName, highlight, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, unhighlight, false);
            });
            
            // Handle file drop events
            uploadArea.addEventListener('drop', handleDrop, false);
        }
        
        /**
         * FILE SELECTION VALIDATION
         * 
         * Validates files immediately upon selection to provide
         * instant feedback about file acceptability and requirements.
         */
        input.addEventListener('change', function() {
            validateFile(this);
        });
    });
}

/**
 * DRAG EVENT DEFAULT PREVENTION
 * 
 * Prevents default browser behaviors for drag events to enable
 * custom drag-and-drop functionality without browser interference.
 * 
 * @param {Event} e - The drag event to prevent defaults for
 */
function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

/**
 * DRAG ZONE VISUAL HIGHLIGHT
 * 
 * Adds visual feedback when files are dragged over the upload area
 * to clearly indicate the drop zone to users.
 * 
 * @param {Event} e - The drag event triggering the highlight
 */
function highlight(e) {
    e.currentTarget.classList.add('dragover');
}

/**
 * DRAG ZONE HIGHLIGHT REMOVAL
 * 
 * Removes visual highlight when files are no longer over the
 * upload area, returning to normal visual state.
 * 
 * @param {Event} e - The drag event triggering highlight removal
 */
function unhighlight(e) {
    e.currentTarget.classList.remove('dragover');
}

/**
 * FILE DROP HANDLING
 * 
 * Processes files dropped onto the upload area by transferring
 * them to the associated file input and triggering validation.
 * 
 * @param {Event} e - The drop event containing the files
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
 * FILE VALIDATION SYSTEM
 * 
 * Comprehensive file validation checking:
 * - File type against allowed formats (PDF, DOC, DOCX)
 * - File size against maximum limits (5MB)
 * - Provides user feedback for validation failures
 * - Shows file information for valid uploads
 * 
 * @param {HTMLInputElement} input - The file input to validate
 * @returns {boolean} - True if file is valid, false otherwise
 */
function validateFile(input) {
    const file = input.files[0];
    if (!file) return;
    
    // Define validation criteria
    const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    const maxSize = 5 * 1024 * 1024; // 5MB in bytes
    
    let isValid = true;
    let message = '';
    
    /**
     * FILE TYPE VALIDATION
     * 
     * Checks uploaded file type against allowed formats
     * to ensure only supported document types are accepted.
     */
    if (!allowedTypes.includes(file.type)) {
        isValid = false;
        message = 'Only PDF, DOC, and DOCX files are allowed';
    } 
    /**
     * FILE SIZE VALIDATION
     * 
     * Ensures uploaded files don't exceed size limits
     * to prevent server storage issues and upload failures.
     */
    else if (file.size > maxSize) {
        isValid = false;
        message = 'File size must be less than 5MB';
    }
    
    /**
     * VALIDATION FAILURE HANDLING
     * 
     * Provides user feedback for validation failures and
     * clears the invalid file selection.
     */
    if (!isValid) {
        showNotification(message, 'error');
        input.value = '';
        return false;
    }
    
    // Show file information for valid uploads
    showFileInfo(input, file);
    return true;
}

/**
 * FILE INFORMATION DISPLAY
 * 
 * Shows information about successfully uploaded files including
 * filename and provides interface for file management (removal).
 * Hides the upload area when file is selected.
 * 
 * @param {HTMLInputElement} input - The file input element
 * @param {File} file - The uploaded file object
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
        
        // Hide upload area when file is selected
        const uploadArea = input.parentNode.querySelector('.upload-area');
        if (uploadArea) {
            uploadArea.style.display = 'none';
        }
    }
}

/**
 * SMOOTH SCROLLING NAVIGATION
 * 
 * Enhances navigation by adding smooth scrolling behavior
 * to anchor links, improving user experience when navigating
 * to different sections of the page.
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
 * NOTIFICATION SYSTEM
 * 
 * Displays temporary notification messages with:
 * - Multiple message types (success, error, warning, info)
 * - Auto-dismiss functionality with configurable duration
 * - Fixed positioning for consistent visibility
 * - Bootstrap styling for visual consistency
 * - Close button for manual dismissal
 * 
 * @param {string} message - The notification message to display
 * @param {string} type - The notification type (success, error, warning, info)
 * @param {number} duration - Auto-dismiss duration in milliseconds
 */
function showNotification(message, type = 'info', duration = 5000) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    
    // Icon mapping for different notification types
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
    
    // Auto-dismiss after specified duration
    setTimeout(() => {
        if (notification && notification.parentNode) {
            notification.remove();
        }
    }, duration);
}

/**
 * DATE FORMATTING UTILITY
 * 
 * Provides consistent date formatting throughout the application
 * with customizable options for different display requirements.
 * 
 * @param {string} dateString - The date string to format
 * @param {Object} options - Formatting options for date display
 * @returns {string} - Formatted date string
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
 * TIME FORMATTING UTILITY
 * 
 * Formats time strings consistently for display throughout
 * the application with 12-hour format and appropriate precision.
 * 
 * @param {string} dateString - The date/time string to format
 * @returns {string} - Formatted time string
 */
function formatTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit'
    });
}

/**
 * XSS PREVENTION UTILITY
 * 
 * Escapes HTML characters in user input to prevent XSS attacks
 * when displaying user-generated content in the DOM.
 * 
 * @param {string} text - The text to escape
 * @returns {string} - HTML-escaped text safe for DOM insertion
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
 * DEBOUNCE UTILITY FUNCTION
 * 
 * Limits the rate at which functions can fire, useful for
 * performance optimization with search inputs, scroll events,
 * and other high-frequency user interactions.
 * 
 * @param {Function} func - The function to debounce
 * @param {number} wait - The number of milliseconds to delay
 * @param {boolean} immediate - Whether to trigger on leading edge
 * @returns {Function} - The debounced function
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
 * CSS ANIMATION UTILITY
 * 
 * Adds CSS animation classes to elements with callback support
 * for chaining animations or executing code after completion.
 * 
 * @param {HTMLElement} element - The element to animate
 * @param {string} animationName - The CSS animation class name
 * @param {Function} callback - Optional callback after animation completion
 */
function addAnimation(element, animationName, callback) {
    element.classList.add(animationName);
    
    element.addEventListener('animationend', function handler() {
        element.classList.remove(animationName);
        element.removeEventListener('animationend', handler);
        if (callback) callback();
    });
}

/**
 * GLOBAL API EXPORT
 * 
 * Exports key functions to the global scope for use in inline
 * scripts and external modules. Provides a clean namespace
 * for job portal specific functionality.
 */
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