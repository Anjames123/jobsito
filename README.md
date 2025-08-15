# Job Portal System

A complete job portal web application built with PHP, HTML, CSS, and JavaScript. This system allows administrators to post job positions and applicants to apply for jobs, upload credentials, and track their application status.

## Features

### For Applicants
- **User Registration & Login** - Secure account creation and authentication
- **Job Browsing** - Search and filter available job positions
- **Job Applications** - Apply for jobs with cover letter and resume upload
- **Application Tracking** - Monitor application status (Pending, Interview, Approved, Rejected)
- **Dashboard** - Personal dashboard showing all applications and statistics
- **File Upload** - Upload resume/CV in PDF, DOC, or DOCX format (max 5MB)

### For Administrators
- **Admin Dashboard** - Overview of all job portal activity
- **Job Management** - Create, edit, activate/deactivate, and delete job posts
- **Application Review** - Review all applications with detailed filtering
- **Status Management** - Update application status and communicate with applicants
- **Statistics** - Track jobs, applications, and user engagement

### Technical Features
- **Responsive Design** - Works seamlessly on desktop and mobile devices
- **Secure File Handling** - Safe file upload with validation and storage
- **Search & Filtering** - Real-time job search and application filtering
- **Form Validation** - Client-side and server-side form validation
- **Session Management** - Secure user sessions and authentication
- **Modern UI** - Bootstrap-based responsive design with custom styling

## Technology Stack

- **Backend:** PHP 7.4+ with PDO for database operations
- **Frontend:** HTML5, CSS3, JavaScript (ES6+), Bootstrap 5
- **Database:** MySQL/MariaDB
- **File Storage:** Local file system with secure uploads
- **Icons:** Bootstrap Icons

## Installation

### Prerequisites
- Web server (Apache/Nginx)
- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.2+
- PDO PHP extension enabled

### Setup Steps

1. **Clone or Download** the project files to your web server directory

2. **Database Setup**
   ```sql
   -- Import the database.sql file
   mysql -u username -p database_name < database.sql
   ```

3. **Configure Database Connection**
   Edit `config.php` and update the database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'job_portal');
   ```

4. **Set Directory Permissions**
   ```bash
   chmod 755 uploads/
   chown www-data:www-data uploads/
   ```

5. **Access the Application**
   - Public site: `http://yourserver/job-portal/`
   - Admin panel: `http://yourserver/job-portal/admin/`

## Default Login Credentials

### Administrator Account
- **Username:** `admin`
- **Password:** `admin123`

*Note: Change the admin password after first login for security*

## File Structure

```
job-portal/
├── admin/                  # Administrator panel
│   ├── index.php          # Admin dashboard
│   ├── jobs.php           # Job management
│   └── applications.php   # Application management
├── uploads/               # File upload directory
├── js/                   # JavaScript files
│   └── app.js            # Main application JavaScript
├── config.php            # Database and app configuration
├── database.sql          # Database schema and sample data
├── style.css             # Main stylesheet
├── index.php             # Public home page
├── login.php             # User login
├── register.php          # User registration
├── dashboard.php         # User dashboard
├── apply.php             # Job application form
├── logout.php            # Logout handler
└── README.md             # This file
```

## Database Schema

### Tables Overview
- **users** - User accounts (both applicants and admins)
- **jobs** - Job postings with all details
- **applications** - Job applications linking users to jobs

### Key Relationships
- Applications belong to both a user and a job
- Jobs are created by admin users
- File uploads are linked to specific applications

## Usage Guide

### For Applicants

1. **Register** - Create an account with basic information
2. **Browse Jobs** - Search available positions by keyword or location
3. **Apply** - Submit applications with cover letter and resume
4. **Track Status** - Monitor application progress in your dashboard

### For Administrators

1. **Login** - Use admin credentials to access the admin panel
2. **Post Jobs** - Create job listings with all required details
3. **Review Applications** - View and process incoming applications
4. **Update Status** - Change application status to keep applicants informed
5. **Manage Jobs** - Edit, activate, or deactivate job postings

## Security Features

- **Password Hashing** - All passwords are securely hashed using PHP's password_hash()
- **SQL Injection Prevention** - PDO prepared statements used throughout
- **XSS Protection** - All user input is sanitized before display
- **File Upload Security** - Strict file type and size validation
- **Session Security** - Secure session management and timeout handling
- **Admin Protection** - Role-based access control for admin functions

## Customization

### Styling
- Modify `style.css` to change colors, fonts, and layout
- Bootstrap classes can be overridden for custom styling
- Responsive breakpoints can be adjusted in CSS

### Configuration
- Update `config.php` for database and file upload settings
- Modify allowed file types in the configuration
- Adjust file size limits as needed

### Functionality
- Add new application status types by updating the database enum
- Extend user profiles with additional fields
- Add email notifications for status changes

## Browser Support

- Chrome 90+
- Firefox 85+
- Safari 14+
- Edge 90+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Troubleshooting

### Common Issues

**File Upload Errors**
- Check directory permissions on `uploads/` folder
- Verify PHP file upload settings in php.ini
- Ensure web server has write access

**Database Connection Errors**
- Verify database credentials in config.php
- Check if MySQL service is running
- Confirm database exists and is accessible

**Login Issues**
- Clear browser cache and cookies
- Check session configuration in PHP
- Verify user exists in database

### Performance Optimization

- Enable gzip compression on web server
- Optimize database queries for large datasets
- Implement caching for frequently accessed data
- Consider CDN for static assets

## Support

For technical support or feature requests:
1. Check this README for common solutions
2. Review the code comments for implementation details
3. Test with the provided sample data

## License

This project is provided as-is for educational and commercial use.

## Version History

- **v1.0** - Initial release with core functionality
  - User registration and authentication
  - Job posting and application system
  - Admin panel with full management features
  - Responsive design with mobile support
  - Secure file upload system

---

**Note:** This is a complete, production-ready job portal system. All features are fully implemented and tested. The code includes comprehensive security measures and follows PHP best practices.
