<?php
// Debug script to check path resolution
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';

echo "UPLOAD_PATH constant: " . UPLOAD_PATH . "\n";
echo "Real path of UPLOAD_PATH: " . realpath(UPLOAD_PATH) . "\n";
echo "Directory of this script: " . __DIR__ . "\n";
echo "Directory of config: " . dirname(__DIR__) . "\n";

// Test the path validation
$testPath = UPLOAD_PATH;
echo "Testing validateBackupPath for UPLOAD_PATH: " . ($testPath) . "\n";
$result = validateBackupPath($testPath);
if ($result) {
    echo "✓ Path validation passed\n";
    echo "Resolved path: $result\n";
} else {
    echo "✗ Path validation failed\n";
}

// Test permissions
echo "\nTesting directory permissions:\n";
echo "UPLOAD_PATH exists: " . (file_exists(UPLOAD_PATH) ? "Yes" : "No") . "\n";
echo "UPLOAD_PATH is writable: " . (is_writable(UPLOAD_PATH) ? "Yes" : "No") . "\n";
echo "UPLOAD_PATH is readable: " . (is_readable(UPLOAD_PATH) ? "Yes" : "No") . "\n";
echo "UPLOAD_PATH is dir: " . (is_dir(UPLOAD_PATH) ? "Yes" : "No") . "\n";

// Test dirname of UPLOAD_PATH
$destDir = dirname($testPath);
echo "\nTesting dirname of UPLOAD_PATH: $destDir\n";
echo "dirname exists: " . (file_exists($destDir) ? "Yes" : "No") . "\n";
echo "dirname is writable: " . (is_writable($destDir) ? "Yes" : "No") . "\n";

// Also test a sample backup file path
$samplePath = UPLOAD_PATH . "test_backup_2025-01-01-12-00-00.tar.gz";
$sampleDir = dirname($samplePath);
echo "\nTesting sample backup path: $samplePath\n";
echo "Sample dirname: $sampleDir\n";
echo "Sample dirname is writable: " . (is_writable($sampleDir) ? "Yes" : "No") . "\n";