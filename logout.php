<?php
/**
 * Direct logout endpoint for Automatic Data Backup System
 *
 * This file provides a direct logout mechanism that can be called independently
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include configuration and authentication
require_once 'config/config.php';
require_once 'includes/functions.php'; // Need this for logActivity
require_once 'includes/auth.php';

// Call the logout function
logoutUser();
?>