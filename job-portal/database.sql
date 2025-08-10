-- Job Portal Database Schema
CREATE DATABASE job_portal;
USE job_portal;

-- Users table (for both admin and applicants)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Jobs table
CREATE TABLE jobs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    company VARCHAR(100) NOT NULL,
    location VARCHAR(100),
    salary_range VARCHAR(50),
    description TEXT NOT NULL,
    requirements TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Applications table
CREATE TABLE applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    job_id INT NOT NULL,
    user_id INT NOT NULL,
    cover_letter TEXT,
    resume_path VARCHAR(255),
    status ENUM('pending', 'interview', 'approved', 'rejected') DEFAULT 'pending',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_application (job_id, user_id)
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password, first_name, last_name, is_admin) 
VALUES ('admin', 'admin@jobportal.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 1);

-- Sample jobs
INSERT INTO jobs (title, company, location, salary_range, description, requirements, created_by) VALUES
('Software Developer', 'Tech Corp', 'New York', '$70,000 - $90,000', 'Develop and maintain web applications using modern frameworks.', 'Bachelor degree in Computer Science, 2+ years experience with JavaScript, React, PHP', 1),
('Marketing Manager', 'Marketing Plus', 'Los Angeles', '$60,000 - $80,000', 'Lead marketing campaigns and manage social media presence.', 'Bachelor degree in Marketing, 3+ years experience in digital marketing', 1),
('Data Analyst', 'Data Solutions', 'Chicago', '$55,000 - $75,000', 'Analyze business data and create reports for decision making.', 'Bachelor degree in Statistics or related field, SQL knowledge, Excel expertise', 1);