<?php
/**
 * Automatic Data Backup System - Core Functions
 */

class AutomaticBackupSystem
{
    private $db;
    
    public function __construct()
    {
        $this->db = getDbConnection();
    }
    
    public function init()
    {
        // Initialize session and check login status
        $this->checkSession();
        
        // Set timezone
        date_default_timezone_set('UTC');
    }
    
    public function route()
    {
        $action = isset($_GET['action']) ? $_GET['action'] : 'dashboard';
        
        // Sanitize the action to prevent security issues
        $allowedActions = [
            'login', 'authenticate', 'logout', 'dashboard', 
            'create_backup', 'manage_backups', 'settings', 'delete_backup'
        ];
        
        if (!in_array($action, $allowedActions)) {
            $action = 'dashboard';
        }
        
        switch ($action) {
            case 'login':
                $this->showLogin();
                break;
            case 'authenticate':
                $this->authenticate();
                break;
            case 'logout':
                $this->logout();
                break;
            case 'dashboard':
                $this->showDashboard();
                break;
            case 'create_backup':
                $this->createBackup();
                break;
            case 'manage_backups':
                $this->showManageBackups();
                break;
            case 'settings':
                $this->showSettings();
                break;
            default:
                $this->showDashboard();
                break;
        }
    }
    
    private function checkSession()
    {
        if (!isset($_SESSION['user_id']) && !strpos($_SERVER['REQUEST_URI'], 'action=login') && !strpos($_SERVER['REQUEST_URI'], 'action=authenticate')) {
            header('Location: ?action=login');
            exit;
        }
    }
    
    private function showLogin()
    {
        include 'views/login.php';
    }
    
    private function authenticate()
    {
        // Include authentication logic
        require_once 'includes/auth.php';
        authenticateUser();
    }
    
    private function logout()
    {
        session_destroy();
        header('Location: ?action=login');
        exit;
    }
    
    private function showDashboard()
    {
        include 'views/dashboard.php';
    }
    
    private function createBackup()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            include 'controllers/backup_controller.php';
            $controller = new BackupController($this->db);
            $controller->createBackup();
            header('Location: ?action=dashboard');
            exit;
        } else {
            include 'views/create_backup.php';
        }
    }
    
    private function showManageBackups()
    {
        if (isset($_GET['delete']) && isset($_GET['id'])) {
            $this->deleteBackup();
        } else {
            include 'views/manage_backups.php';
        }
    }
    
    private function deleteBackup()
    {
        $backupId = (int)$_GET['id'];
        
        if ($backupId > 0) {
            $controller = new BackupController($this->db);
            $result = $controller->deleteBackup($backupId);
            
            if ($result) {
                $_SESSION['success'] = "Backup deleted successfully.";
            } else {
                $_SESSION['error'] = "Failed to delete backup.";
            }
        } else {
            $_SESSION['error'] = "Invalid backup ID.";
        }
        
        header('Location: ?action=manage_backups');
        exit;
    }
    
    private function showSettings()
    {
        $action = $_GET['settings_action'] ?? '';
        
        switch($action) {
            case 'update_general':
                $this->updateGeneralSettings();
                break;
            case 'update_notification':
                $this->updateNotificationSettings();
                break;
            case 'manage_job':
                $this->manageJob();
                break;
            case 'delete_job':
                $this->deleteJob();
                break;
            default:
                include 'views/settings.php';
                break;
        }
    }
    
    private function updateGeneralSettings()
    {
        // In a real implementation, this would update the database
        $_SESSION['success'] = "General settings updated successfully.";
        header('Location: ?action=settings');
        exit;
    }
    
    private function updateNotificationSettings()
    {
        // In a real implementation, this would update the database
        $_SESSION['success'] = "Notification settings updated successfully.";
        header('Location: ?action=settings');
        exit;
    }
    
    private function manageJob()
    {
        $controller = new BackupController($this->db);
        
        if (isset($_POST['job_id']) && $_POST['job_id']) {
            // Update existing job
            $result = $controller->updateJob($_POST['job_id'], $_POST);
            $message = $result ? "Backup job updated successfully." : "Failed to update backup job.";
        } else {
            // Create new job
            $result = $controller->createJob($_POST);
            $message = $result ? "Backup job created successfully." : "Failed to create backup job.";
        }
        
        $_SESSION['success'] = $message;
        header('Location: ?action=settings');
        exit;
    }
    
    private function deleteJob()
    {
        if (isset($_GET['id'])) {
            $controller = new BackupController($this->db);
            $result = $controller->deleteJob($_POST['id'] ?? $_GET['id']);
            
            if ($result) {
                $_SESSION['success'] = "Backup job deleted successfully.";
            } else {
                $_SESSION['error'] = "Failed to delete backup job.";
            }
        }
        
        header('Location: ?action=settings');
        exit;
    }
}

?>