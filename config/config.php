<?php
/**
 * Automatic Data Backup System - Configuration File
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'automatic_backup_system');

// Application Configuration
define('APP_NAME', 'Automatic Data Backup System');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['SCRIPT_NAME'] ? dirname($_SERVER['SCRIPT_NAME']) : ''));
define('UPLOAD_PATH', dirname(__DIR__) . '/backups/');
define('LOG_PATH', __DIR__ . '/../logs/');
define('TEMP_PATH', __DIR__ . '/../temp/');

// Security Configuration
// IMPORTANT: Change this to a strong, unique key and keep it secret
define('ENCRYPTION_KEY', defined('TESTING') ? 'test_key_32_chars_long_for_testing' : 
    (getenv('BACKUP_ENCRYPTION_KEY') ?: 'your-very-secure-encryption-key-here-32chars'));
define('SESSION_TIMEOUT', 3600); // 1 hour
define('BACKUP_ENCRYPTION', true);

// Email Configuration (for notifications)
define('SMTP_HOST', '');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');

// Default settings
define('DEFAULT_BACKUP_SCHEDULE', 'daily'); // daily, weekly, monthly
define('MAX_BACKUP_RETENTION_DAYS', 30); // Keep backups for 30 days

// Security settings
define('MAX_FILE_UPLOAD_SIZE', 500 * 1024 * 1024); // 500MB max file size
define('ALLOWED_BACKUP_PATHS', [dirname(__DIR__)]); // Restrict backups to specific paths
define('ENABLE_RATE_LIMITING', true);
define('MAX_BACKUPS_PER_HOUR', 10);

// Establish database connection
function getDbConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        die("Database connection failed. Please contact system administrator.");
    }
}

// Create uploads and logs directories if they don't exist
$directories = [UPLOAD_PATH, LOG_PATH, TEMP_PATH];
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true); // More restrictive permissions for security
    }
}

// Include security utilities
require_once __DIR__ . '/../includes/security.php';

// Set security headers
setSecurityHeaders();

// Validate configuration
try {
    validateConfig();
} catch (Exception $e) {
    error_log("Configuration validation failed: " . $e->getMessage());
    die("Configuration error. Please contact system administrator.");
}

?>