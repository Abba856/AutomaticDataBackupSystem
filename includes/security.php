<?php
/**
 * Security utilities for Automatic Data Backup System
 */

// Security headers
function setSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// Input validation functions
function validateFilePath($path) {
    // Prevent directory traversal
    $realPath = realpath($path);
    // Get the application root directory (two levels up from includes)
    $basePath = realpath(__DIR__ . '/..'); // This will be the AutomaticDataBackup directory
    
    if ($realPath === false || strpos($realPath, $basePath) !== 0) {
        return false;
    }
    
    return $realPath;
}

function validateBackupPath($path) {
    // Additional validation for backup paths - don't block .. in paths since realpath() handles traversal
    if (preg_match('/[<>:"|?*]/', $path)) {
        return false;
    }
    
    return validateFilePath($path);
}

// Generate secure encryption key
function generateSecureKey($length = 32) {
    return bin2hex(random_bytes($length));
}

// Check permissions for backup operations
function checkBackupPermissions($sourcePath, $destPath) {
    // Check if source exists and is readable
    if (!file_exists($sourcePath)) {
        return ['valid' => false, 'error' => "Source path does not exist: $sourcePath"];
    }

    if (!is_readable($sourcePath)) {
        return ['valid' => false, 'error' => "Source path is not readable: $sourcePath"];
    }

    // Check if destination path is a directory (ends with /) or a file path
    if (substr($destPath, -1) === '/') {
        // It's a directory path, check if it's writable
        $destDir = rtrim($destPath, '/');
        if (!is_writable($destDir)) {
            return ['valid' => false, 'error' => "Destination directory is not writable: $destDir"];
        }
    } else {
        // It's a file path, check if the parent directory is writable
        $destDir = dirname($destPath);
        if (!is_writable($destDir)) {
            return ['valid' => false, 'error' => "Destination directory is not writable: $destDir"];
        }
    }

    return ['valid' => true, 'error' => null];
}

// Validate configuration values
function validateConfig() {
    // Ensure required configuration values are set
    $required = ['DB_HOST', 'DB_USER', 'DB_NAME', 'ENCRYPTION_KEY'];
    foreach ($required as $config) {
        if (!defined($config) || empty(constant($config))) {
            throw new Exception("Missing required configuration: $config");
        }
    }
    
    // Validate encryption key strength
    if (strlen(ENCRYPTION_KEY) < 32) {
        throw new Exception("Encryption key is too short. Use at least 32 characters.");
    }
}

// Sanitize user input specifically for backup operations
function sanitizeBackupInput($input) {
    // Remove any potential harmful characters
    $sanitized = preg_replace('/[^\w\s\/_.-]/', '', $input);
    return trim($sanitized);
}

// Create a secure temporary file
function createSecureTempFile($prefix = 'backup_') {
    $tempDir = TEMP_PATH;
    $filename = $prefix . bin2hex(random_bytes(16)) . '.tmp';
    $fullPath = $tempDir . $filename;
    
    // Create the file with restricted permissions
    $handle = fopen($fullPath, 'w');
    if ($handle) {
        fclose($handle);
        chmod($fullPath, 0600); // Read/write for owner only
        return $fullPath;
    }
    
    return false;
}

// Verify file integrity
function verifyFileIntegrity($filePath, $expectedChecksum) {
    if (!file_exists($filePath)) {
        return false;
    }
    
    $actualChecksum = hash_file('sha256', $filePath);
    return hash_equals($actualChecksum, $expectedChecksum);
}

// Check available disk space before backup
function checkDiskSpace($path, $requiredSpace) {
    $freeSpace = disk_free_space(dirname($path));
    return $freeSpace > $requiredSpace * 1.1; // 10% buffer
}

// Rate limiting for backup operations
class RateLimiter {
    private $db;
    private $maxRequests;
    private $timeWindow; // in seconds
    
    public function __construct($database, $maxRequests = 5, $timeWindow = 3600) { // 5 requests per hour
        $this->db = $database;
        $this->maxRequests = $maxRequests;
        $this->timeWindow = $timeWindow;
    }
    
    public function isAllowed($userId, $action = 'backup') {
        $cutoffTime = date('Y-m-d H:i:s', time() - $this->timeWindow);
        
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM backup_records 
            WHERE start_time > ? AND status = 'completed'
        ");
        $stmt->execute([$cutoffTime]);
        $result = $stmt->fetch();
        
        return $result['count'] < $this->maxRequests;
    }
    
    public function logRequest($userId, $action = 'backup') {
        // In a real implementation, this would log the request
    }
}