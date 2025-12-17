-- 
-- Database Schema for Automatic Data Backup System
-- 

-- Create the database
CREATE DATABASE IF NOT EXISTS automatic_backup_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the database
USE automatic_backup_system;

-- Users table - for authentication
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Backup jobs table - stores backup configurations
CREATE TABLE backup_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    source_path TEXT NOT NULL,
    destination_path TEXT NOT NULL,
    schedule_type ENUM('manual', 'hourly', 'daily', 'weekly', 'monthly') DEFAULT 'daily',
    schedule_value VARCHAR(50),
    encryption_enabled BOOLEAN DEFAULT FALSE,
    retention_days INT DEFAULT 30,
    compression_enabled BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Backup records table - tracks individual backup executions
CREATE TABLE backup_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    filename VARCHAR(500) NOT NULL,
    file_size BIGINT,
    status ENUM('pending', 'running', 'completed', 'failed') DEFAULT 'pending',
    start_time TIMESTAMP NULL,
    end_time TIMESTAMP NULL,
    duration_seconds INT,
    encrypted BOOLEAN DEFAULT FALSE,
    compressed BOOLEAN DEFAULT TRUE,
    checksum VARCHAR(64),
    notes TEXT,
    FOREIGN KEY (job_id) REFERENCES backup_jobs(id) ON DELETE CASCADE
);

-- System settings table - stores application configuration
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Notifications table - stores notification logs
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('info', 'warning', 'error', 'success') DEFAULT 'info',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    user_id INT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password, role) VALUES 
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES 
('backup_storage_limit', '100', 'Maximum storage space for backups in GB'),
('notification_email', '', 'Email address for backup notifications'),
('encryption_enabled', '1', 'Enable encryption for backups'),
('compression_enabled', '1', 'Enable compression for backups'),
('retention_period', '30', 'Default number of days to retain backups'),
('backup_timeout', '3600', 'Backup timeout in seconds'),
('smtp_host', '', 'SMTP server host'),
('smtp_port', '587', 'SMTP server port'),
('smtp_username', '', 'SMTP username'),
('smtp_password', '', 'SMTP password'),
('from_email', 'noreply@backup-system.local', 'From email address'),
('from_name', 'Backup System', 'From name');

-- Indexes for better performance
CREATE INDEX idx_backup_records_job_id ON backup_records(job_id);
CREATE INDEX idx_backup_records_status ON backup_records(status);
CREATE INDEX idx_backup_records_created_at ON backup_records(start_time);
CREATE INDEX idx_backup_jobs_is_active ON backup_jobs(is_active);
CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_notifications_is_read ON notifications(is_read);