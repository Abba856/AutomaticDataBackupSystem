<?php
/**
 * Automatic Data Backup System - Main Index
 */

// Start session
session_start();

// Include configuration
require_once 'config/config.php';

// Include core classes
require_once 'includes/functions.php';
require_once 'includes/core.php';

// Initialize the application
$app = new AutomaticBackupSystem();
$app->init();

// Route the request
$app->route();

?>