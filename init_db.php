<?php
/**
 * Database Initialization Script for Automatic Data Backup System
 * 
 * This script creates the required database tables and sets up initial data.
 */

require_once 'config/config.php';

echo "Starting database initialization...\n";

try {
    // Connect to MySQL without specifying a database first
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    
    // Create the database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    echo "Database '" . DB_NAME . "' created or already exists.\n";
    
    // Select the database
    $pdo->exec("USE " . DB_NAME);
    
    // Create users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'user') DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL
        )
    ");
    echo "Users table created.\n";
    
    // Create backup_jobs table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS backup_jobs (
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
        )
    ");
    echo "Backup jobs table created.\n";
    
    // Create backup_records table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS backup_records (
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
        )
    ");
    echo "Backup records table created.\n";
    
    // Create system_settings table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS system_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            description TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "System settings table created.\n";
    
    // Create notifications table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type ENUM('info', 'warning', 'error', 'success') DEFAULT 'info',
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            user_id INT,
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "Notifications table created.\n";
    
    // Insert or update default admin user (password: password123)
    $passwordHash = password_hash('password123', PASSWORD_DEFAULT);
    
    // Check if user already exists
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $checkStmt->execute(['admin']);
    $existingUser = $checkStmt->fetch();
    
    if ($existingUser) {
        // Update existing user's password
        $updateStmt = $pdo->prepare("UPDATE users SET password = ?, email = ? WHERE username = ?");
        $updateStmt->execute([$passwordHash, 'admin@example.com', 'admin']);
        echo "Existing admin user updated with new password (username: admin, password: password123).\n";
    } else {
        // Insert new user
        $insertStmt = $pdo->prepare("
            INSERT INTO users (username, email, password, role) 
            VALUES (?, ?, ?, ?)
        ");
        $insertStmt->execute(['admin', 'admin@example.com', $passwordHash, 'admin']);
        echo "Default admin user created (username: admin, password: password123).\n";
    }
    
    // Insert default system settings
    $settings = [
        ['backup_storage_limit', '100', 'Maximum storage space for backups in GB'],
        ['notification_email', '', 'Email address for backup notifications'],
        ['encryption_enabled', '1', 'Enable encryption for backups'],
        ['compression_enabled', '1', 'Enable compression for backups'],
        ['retention_period', '30', 'Default number of days to retain backups'],
        ['backup_timeout', '3600', 'Backup timeout in seconds'],
        ['smtp_host', '', 'SMTP server host'],
        ['smtp_port', '587', 'SMTP server port'],
        ['smtp_username', '', 'SMTP username'],
        ['smtp_password', '', 'SMTP password'],
        ['from_email', 'noreply@backup-system.local', 'From email address'],
        ['from_name', 'Backup System', 'From name']
    ];
    
    $settingStmt = $pdo->prepare("
        INSERT IGNORE INTO system_settings (setting_key, setting_value, description) 
        VALUES (?, ?, ?)
    ");
    
    foreach ($settings as $setting) {
        $settingStmt->execute($setting);
    }
    echo "Default system settings created.\n";
    
    // Create indexes
    try {
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_backup_records_job_id ON backup_records(job_id)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_backup_records_status ON backup_records(status)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_backup_records_created_at ON backup_records(start_time)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_backup_jobs_is_active ON backup_jobs(is_active)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_notifications_user_id ON notifications(user_id)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_notifications_is_read ON notifications(is_read)");
        echo "Database indexes created.\n";
    } catch (Exception $e) {
        echo "Warning: Could not create indexes - " . $e->getMessage() . "\n";
    }
    
    echo "\nDatabase initialization completed successfully!\n";
    echo "You can now access the system with:\n";
    echo "Username: admin\n";
    echo "Password: password123\n";
    
} catch (PDOException $e) {
    die("Database initialization failed: " . $e->getMessage() . "\n");
}