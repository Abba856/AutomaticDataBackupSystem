<?php
/**
 * Backup Controller for Automatic Data Backup System
 */

class BackupController
{
    private $db;
    
    public function __construct($database)
    {
        $this->db = $database;
    }
    
    public function createBackup($jobId = null)
    {
        try {
            if ($jobId) {
                // Create backup from specific job
                $job = $this->getJobById($jobId);
                if (!$job) {
                    throw new Exception("Backup job not found");
                }
                return $this->performBackup($job);
            } else {
                // Create backup from POST data
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    throw new Exception("Invalid request method");
                }
                
                $jobData = [
                    'name' => $_POST['name'] ?? 'Manual Backup',
                    'source_path' => $_POST['source_path'] ?? '',
                    'destination_path' => $_POST['destination_path'] ?? UPLOAD_PATH,
                    'encryption_enabled' => isset($_POST['encryption_enabled']),
                    'compression_enabled' => isset($_POST['compression_enabled']),
                    'retention_days' => intval($_POST['retention_days'] ?? MAX_BACKUP_RETENTION_DAYS)
                ];
                
                return $this->performBackup($jobData);
            }
        } catch (Exception $e) {
            error_log("Backup creation failed: " . $e->getMessage());
            $_SESSION['error'] = "Backup creation failed: " . $e->getMessage();
            return false;
        }
    }
    
    private function performBackup($job)
    {
        $recordId = null;
        
        try {
            // Validate inputs
            $sourcePath = $job['source_path'];
            $destPath = $job['destination_path'];
            
            // Security validation
            $validation = checkBackupPermissions($sourcePath, $destPath);
            if (!$validation['valid']) {
                throw new Exception($validation['error']);
            }
            
            // Additional path validation
            if (!validateBackupPath($sourcePath)) {
                throw new Exception("Invalid source path: $sourcePath");
            }
            
            // Validate destination path - ensure it's in allowed paths
            if (!validateBackupPath(dirname($destPath))) {
                throw new Exception("Invalid destination path: $destPath");
            }
            
            // Ensure destination directory exists and is writable
            $destDir = dirname($destPath);
            if (!is_dir($destDir)) {
                throw new Exception("Destination directory does not exist: $destDir");
            }
            
            if (!is_writable($destDir)) {
                throw new Exception("Destination directory is not writable: $destDir");
            }
            
            // Rate limiting check
            if (ENABLE_RATE_LIMITING) {
                $rateLimiter = new RateLimiter($this->db, MAX_BACKUPS_PER_HOUR, 3600);
                if (!$rateLimiter->isAllowed($_SESSION['user_id'])) {
                    throw new Exception("Rate limit exceeded. Please wait before creating another backup.");
                }
            }
            
            // Start backup record
            $recordId = $this->createBackupRecord($job['id'] ?? null, 'running');
            
            $startTime = microtime(true);
            $startTimestamp = date('Y-m-d H:i:s');
            
            // Generate backup filename
            $timestamp = date('Y-m-d-H-i-s');
            $filename = basename($sourcePath) . "_backup_$timestamp.tar.gz";
            $fullPath = $destPath . $filename;
            
            // Check disk space before creating backup
            $estimatedSize = $this->estimateSize($sourcePath);
            if (!checkDiskSpace($fullPath, $estimatedSize)) {
                throw new Exception("Insufficient disk space for backup operation.");
            }
            
            // Perform the backup
            if ($job['compression_enabled']) {
                $cmd = "tar -czf " . escapeshellarg($fullPath) . " -C " . escapeshellarg(dirname($sourcePath)) . " " . escapeshellarg(basename($sourcePath));
            } else {
                $cmd = "cp -r " . escapeshellarg($sourcePath) . " " . escapeshellarg($fullPath);
            }
            
            $output = [];
            $returnCode = 0;
            exec($cmd, $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new Exception("Backup command failed with return code: $returnCode");
            }
            
            // Check if file was created successfully
            if (!file_exists($fullPath)) {
                throw new Exception("Backup file was not created at: $fullPath");
            }
            
            $fileSize = filesize($fullPath);
            
            // Encrypt if enabled
            $encrypted = false;
            if ($job['encryption_enabled']) {
                $encryptedFilename = $fullPath . '.enc';
                
                if (!encryptFile($fullPath, $encryptedFilename)) {
                    throw new Exception("Encryption failed");
                }
                
                // Delete unencrypted file
                unlink($fullPath);
                
                $fullPath = $encryptedFilename;
                $filename .= '.enc';
                $encrypted = true;
                $fileSize = filesize($fullPath);
            }
            
            // Calculate checksum
            $checksum = hash_file('sha256', $fullPath);
            
            // Verify file integrity
            if (!verifyFileIntegrity($fullPath, $checksum)) {
                throw new Exception("File integrity check failed for: $filename");
            }
            
            // Update backup record
            $endTime = microtime(true);
            $duration = round($endTime - $startTime);
            
            $this->updateBackupRecord(
                $recordId,
                [
                    'filename' => $filename,
                    'file_size' => $fileSize,
                    'status' => 'completed',
                    'start_time' => $startTimestamp,
                    'end_time' => date('Y-m-d H:i:s'),
                    'duration_seconds' => $duration,
                    'encrypted' => $encrypted,
                    'compressed' => $job['compression_enabled'],
                    'checksum' => $checksum
                ]
            );
            
            // Log success
            logActivity("Backup completed successfully: $filename (Job: {$job['name']})");
            
            $_SESSION['success'] = "Backup created successfully: $filename";
            
            // Clean old backups if retention is enabled
            if (isset($job['retention_days']) && $job['retention_days'] > 0) {
                $this->cleanOldBackupsForJob($job['id'] ?? null, $job['retention_days']);
            }
            
            return [
                'success' => true,
                'filename' => $filename,
                'file_size' => $fileSize,
                'duration' => $duration
            ];
            
        } catch (Exception $e) {
            // Update backup record as failed
            if ($recordId) {
                $this->updateBackupRecord(
                    $recordId,
                    [
                        'status' => 'failed',
                        'end_time' => date('Y-m-d H:i:s'),
                        'notes' => $e->getMessage()
                    ]
                );
            }
            
            error_log("Backup failed: " . $e->getMessage());
            $_SESSION['error'] = "Backup failed: " . $e->getMessage();
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function estimateSize($path) {
        $size = 0;
        
        if (is_file($path)) {
            return filesize($path);
        }
        
        if (is_dir($path)) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        }
        
        return $size;
    }
    
    private function createBackupRecord($jobId, $status = 'pending')
    {
        $stmt = $this->db->prepare("
            INSERT INTO backup_records (job_id, filename, file_size, status, start_time) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $jobId,
            '', // Will be updated later
            0,  // Will be updated later
            $status
        ]);
        
        return $this->db->lastInsertId();
    }
    
    private function updateBackupRecord($recordId, $updates)
    {
        $setParts = [];
        $params = [];
        
        foreach ($updates as $field => $value) {
            $setParts[] = "$field = ?";
            $params[] = $value;
        }
        
        $sql = "UPDATE backup_records SET " . implode(', ', $setParts) . " WHERE id = ?";
        $params[] = $recordId;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }
    
    private function getJobById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM backup_jobs WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getAllJobs()
    {
        $stmt = $this->db->query("SELECT bj.*, u.username as created_by_name 
                                 FROM backup_jobs bj 
                                 LEFT JOIN users u ON bj.created_by = u.id 
                                 ORDER BY bj.created_at DESC");
        return $stmt->fetchAll();
    }
    
    public function getRecentBackups($limit = 10)
    {
        $stmt = $this->db->query("
            SELECT br.*, bj.name as job_name 
            FROM backup_records br 
            LEFT JOIN backup_jobs bj ON br.job_id = bj.id 
            ORDER BY br.start_time DESC 
            LIMIT $limit
        ");
        return $stmt->fetchAll();
    }
    
    public function getAllBackups($jobId = null)
    {
        $sql = "SELECT br.*, bj.name as job_name 
                FROM backup_records br 
                LEFT JOIN backup_jobs bj ON br.job_id = bj.id";
        
        $params = [];
        if ($jobId) {
            $sql .= " WHERE br.job_id = ?";
            $params = [$jobId];
        }
        
        $sql .= " ORDER BY br.start_time DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function deleteBackup($recordId)
    {
        try {
            // Get the filename first
            $stmt = $this->db->prepare("SELECT filename FROM backup_records WHERE id = ?");
            $stmt->execute([$recordId]);
            $record = $stmt->fetch();
            
            if ($record) {
                // Delete the actual file
                $filepath = UPLOAD_PATH . $record['filename'];
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
                
                // Delete the record
                $stmt = $this->db->prepare("DELETE FROM backup_records WHERE id = ?");
                $result = $stmt->execute([$recordId]);
                
                if ($result) {
                    logActivity("Backup deleted: {$record['filename']}");
                    return true;
                }
            }
        } catch (Exception $e) {
            error_log("Failed to delete backup: " . $e->getMessage());
        }
        
        return false;
    }
    
    private function cleanOldBackupsForJob($jobId, $retentionDays)
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-$retentionDays days"));
        
        $stmt = $this->db->prepare("
            SELECT id, filename 
            FROM backup_records 
            WHERE DATE(start_time) < ? AND (? IS NULL OR job_id = ?)
        ");
        $stmt->execute([$cutoffDate, $jobId, $jobId]);
        $oldRecords = $stmt->fetchAll();
        
        foreach ($oldRecords as $record) {
            // Delete the actual file
            $filepath = UPLOAD_PATH . $record['filename'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            
            // Delete the record
            $deleteStmt = $this->db->prepare("DELETE FROM backup_records WHERE id = ?");
            $deleteStmt->execute([$record['id']]);
        }
    }
    
    public function createJob($jobData)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO backup_jobs 
                (name, description, source_path, destination_path, schedule_type, schedule_value, 
                 encryption_enabled, retention_days, compression_enabled, is_active, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $jobData['name'],
                $jobData['description'] ?? '',
                $jobData['source_path'],
                $jobData['destination_path'] ?? UPLOAD_PATH,
                $jobData['schedule_type'] ?? 'daily',
                $jobData['schedule_value'] ?? '',
                $jobData['encryption_enabled'] ?? 0,
                $jobData['retention_days'] ?? MAX_BACKUP_RETENTION_DAYS,
                $jobData['compression_enabled'] ?? 1,
                $jobData['is_active'] ?? 1,
                $_SESSION['user_id']
            ]);
            
            if ($result) {
                logActivity("Backup job created: {$jobData['name']}");
                return $this->db->lastInsertId();
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Failed to create backup job: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateJob($jobId, $jobData)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE backup_jobs 
                SET name = ?, description = ?, source_path = ?, destination_path = ?, 
                    schedule_type = ?, schedule_value = ?, encryption_enabled = ?, 
                    retention_days = ?, compression_enabled = ?, is_active = ?
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $jobData['name'],
                $jobData['description'] ?? '',
                $jobData['source_path'],
                $jobData['destination_path'] ?? UPLOAD_PATH,
                $jobData['schedule_type'] ?? 'daily',
                $jobData['schedule_value'] ?? '',
                $jobData['encryption_enabled'] ?? 0,
                $jobData['retention_days'] ?? MAX_BACKUP_RETENTION_DAYS,
                $jobData['compression_enabled'] ?? 1,
                $jobData['is_active'] ?? 1,
                $jobId
            ]);
            
            if ($result) {
                logActivity("Backup job updated: {$jobData['name']} (ID: $jobId)");
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Failed to update backup job: " . $e->getMessage());
            return false;
        }
    }
    
    public function deleteJob($jobId)
    {
        try {
            // Delete associated backup records first
            $stmt = $this->db->prepare("DELETE FROM backup_records WHERE job_id = ?");
            $stmt->execute([$jobId]);
            
            // Then delete the job
            $stmt = $this->db->prepare("DELETE FROM backup_jobs WHERE id = ?");
            $result = $stmt->execute([$jobId]);
            
            if ($result) {
                logActivity("Backup job deleted (ID: $jobId)");
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Failed to delete backup job: " . $e->getMessage());
            return false;
        }
    }
}