<?php
/**
 * Test script for Automatic Data Backup System
 */

require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';

// Test encryption functions
echo "Testing encryption functions...\n";

$testData = "This is a test string for encryption.";
$encrypted = encryptData($testData);
$decrypted = decryptData($encrypted);

if ($decrypted === $testData) {
    echo "✓ Encryption/decryption test passed\n";
} else {
    echo "✗ Encryption/decryption test failed\n";
}

// Test file encryption functions
$tempFile = createSecureTempFile();
if ($tempFile) {
    file_put_contents($tempFile, $testData);
    $encryptedFile = $tempFile . '.enc';
    
    if (encryptFile($tempFile, $encryptedFile)) {
        $decryptedFile = $tempFile . '.dec';
        
        if (decryptFile($encryptedFile, $decryptedFile)) {
            $decryptedContent = file_get_contents($decryptedFile);
            
            if ($decryptedContent === $testData) {
                echo "✓ File encryption/decryption test passed\n";
            } else {
                echo "✗ File decryption failed - content mismatch\n";
            }
            
            unlink($decryptedFile);
        } else {
            echo "✗ File decryption failed\n";
        }
        
        unlink($encryptedFile);
    } else {
        echo "✗ File encryption failed\n";
    }
    
    unlink($tempFile);
} else {
    echo "✗ Could not create temporary file for testing\n";
}

// Test database connection
echo "\nTesting database connection...\n";
try {
    $db = getDbConnection();
    $stmt = $db->query("SELECT 1");
    $result = $stmt->fetch();
    
    if ($result) {
        echo "✓ Database connection test passed\n";
    } else {
        echo "✗ Database query test failed\n";
    }
} catch (Exception $e) {
    echo "✗ Database connection test failed: " . $e->getMessage() . "\n";
}

// Test directory creation
echo "\nTesting directory permissions...\n";
$testDir = TEMP_PATH . 'test_dir_' . time();
if (mkdir($testDir, 0755, true)) {
    if (is_dir($testDir)) {
        rmdir($testDir);
        echo "✓ Directory creation/deletion test passed\n";
    } else {
        echo "✗ Directory creation failed\n";
    }
} else {
    echo "✗ Could not create test directory\n";
}

// Test configuration validation
echo "\nTesting configuration validation...\n";
try {
    validateConfig();
    echo "✓ Configuration validation passed\n";
} catch (Exception $e) {
    echo "✗ Configuration validation failed: " . $e->getMessage() . "\n";
}

// Test path validation
echo "\nTesting path validation...\n";
if (validateBackupPath(dirname(__DIR__))) {
    echo "✓ Path validation test passed\n";
} else {
    echo "✗ Path validation test failed\n";
}

echo "\nAll tests completed!\n";