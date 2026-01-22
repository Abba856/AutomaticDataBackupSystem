<?php
/**
 * Automatic Data Backup System - Scheduled Jobs Runner
 * 
 * This script is intended to be run via cron job to execute scheduled backups.
 * 
 * Example crontab entry to run every hour:
 * 0 * * * * /usr/bin/php /path/to/your/AutomaticDataBackup/scheduler.php
 */

require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/core.php';
require_once 'controllers/backup_controller.php';

class BackupScheduler
{
    private $db;
    private $controller;
    
    public function __construct()
    {
        $this->db = getDbConnection();
        $this->controller = new BackupController($this->db);
    }
    
    public function run()
    {
        try {
            echo "Starting backup scheduler at " . date('Y-m-d H:i:s') . "\n";
            
            // Clean up old backups based on retention policy
            $this->cleanupOldBackups();
            
            // Execute any pending scheduled backups
            $this->executeScheduledBackups();
            
            echo "Backup scheduler completed at " . date('Y-m-d H:i:s') . "\n";
            
        } catch (Exception $e) {
            error_log("Backup scheduler error: " . $e->getMessage());
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
    
    private function executeScheduledBackups()
    {
        $currentTime = date('H:i');
        $currentDay = date('w'); // 0 = Sunday, 1 = Monday, etc
        $currentDate = date('j'); // Day of the month
        
        // Get all active scheduled backup jobs
        $stmt = $this->db->query("
            SELECT * FROM backup_jobs 
            WHERE is_active = 1 
            AND (schedule_type != 'manual' OR schedule_type IS NOT NULL)
        ");
        
        $jobs = $stmt->fetchAll();
        
        foreach ($jobs as $job) {
            $shouldRun = $this->shouldJobRun($job, $currentTime, $currentDay, $currentDate);
            
            if ($shouldRun) {
                echo "Executing backup job: " . $job['name'] . "\n";
                
                // Create a backup for this job
                $result = $this->controller->createBackup($job['id']);
                
                if ($result && $result['success']) {
                    echo "Backup job '" . $job['name'] . "' completed successfully\n";
                    logActivity("Scheduled backup completed: " . $job['name']);
                } else {
                    echo "Backup job '" . $job['name'] . "' failed\n";
                    logActivity("Scheduled backup failed: " . $job['name'] . " - " . ($result['error'] ?? 'Unknown error'));
                }
            }
        }
    }
    
    private function shouldJobRun($job, $currentTime, $currentDay, $currentDate)
    {
        switch ($job['schedule_type']) {
            case 'hourly':
                return true; // Run every hour at the scheduled time
                
            case 'daily':
                // Check if the schedule_value matches current time (or if it's empty/anytime)
                if (empty($job['schedule_value']) || $job['schedule_value'] == $currentTime) {
                    return true;
                }
                break;
                
            case 'weekly':
                // Check if the schedule_value matches current day (e.g., "Monday", "2", etc)
                $scheduledDay = $job['schedule_value'];
                if ($scheduledDay == $currentDay || strtolower($scheduledDay) == strtolower(date('l'))) {
                    // Also check if a specific time is set
                    if (strpos($scheduledDay, ' ') !== false) {
                        // If the schedule_value includes a time (e.g., "Monday 02:00")
                        $parts = explode(' ', $scheduledDay);
                        $day = $parts[0];
                        $time = $parts[1] ?? '';
                        if (strtolower($day) == strtolower(date('l')) && $time == $currentTime) {
                            return true;
                        }
                    } else {
                        return true; // Just match the day
                    }
                }
                break;
                
            case 'monthly':
                // Check if the schedule_value matches current date (day of month)
                $scheduledDate = $job['schedule_value'];
                if ($scheduledDate == $currentDate || empty($job['schedule_value'])) {
                    return true;
                }
                break;
        }
        
        return false;
    }
    
    private function cleanupOldBackups()
    {
        // This function runs cleanup based on retention settings
        echo "Cleaning up old backups...\n";
        
        $stmt = $this->db->query("
            SELECT id, name, retention_days 
            FROM backup_jobs 
            WHERE is_active = 1 
            AND retention_days > 0
        ");
        
        $jobs = $stmt->fetchAll();
        
        foreach ($jobs as $job) {
            $this->controller->cleanOldBackupsForJob($job['id'], $job['retention_days']);
        }
        
        // Also run general cleanup for manual backups that have retention settings
        cleanOldBackups();
        
        echo "Backup cleanup completed.\n";
    }
}

// Run the scheduler
$scheduler = new BackupScheduler();
$scheduler->run();