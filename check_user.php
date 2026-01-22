<?php
// Test script to verify the admin user exists and check password
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

try {
    $db = getDbConnection();
    
    // Query the admin user from database
    $stmt = $db->prepare("SELECT id, username, password, role, created_at FROM users WHERE username = ?");
    $stmt->execute(['admin']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "Admin user found in database:\n";
        echo "ID: " . $user['id'] . "\n";
        echo "Username: " . $user['username'] . "\n";
        echo "Role: " . $user['role'] . "\n";
        echo "Created: " . $user['created_at'] . "\n";
        
        // Test password verification with "password123"
        $testPassword1 = "password123";
        $testPassword2 = "admin123";
        
        echo "\nTesting password verification:\n";
        echo "Testing with 'password123': " . (password_verify($testPassword1, $user['password']) ? "PASS" : "FAIL") . "\n";
        echo "Testing with 'admin123': " . (password_verify($testPassword2, $user['password']) ? "PASS" : "FAIL") . "\n";
    } else {
        echo "No admin user found in the database. The database may not be properly initialized.\n";
    }
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}