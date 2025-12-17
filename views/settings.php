<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">System Settings</h1>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-12">
                        <div class="nav nav-tabs" id="settingsTab" role="tablist">
                            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">General</button>
                            <button class="nav-link" id="backup-jobs-tab" data-bs-toggle="tab" data-bs-target="#backup-jobs" type="button" role="tab">Backup Jobs</button>
                            <button class="nav-link" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications" type="button" role="tab">Notifications</button>
                        </div>
                        
                        <div class="tab-content" id="settingsTabContent">
                            <!-- General Settings Tab -->
                            <div class="tab-pane fade show active" id="general" role="tabpanel">
                                <div class="card shadow mt-3">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">General Settings</h6>
                                    </div>
                                    <div class="card-body">
                                        <form method="post" action="?action=settings&settings_action=update_general">
                                            <div class="mb-3">
                                                <label for="backup_storage_limit" class="form-label">Backup Storage Limit (GB)</label>
                                                <input type="number" class="form-control" id="backup_storage_limit" name="backup_storage_limit" value="100">
                                                <div class="form-text">Maximum storage space allowed for backups</div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="retention_period" class="form-label">Default Retention Period (Days)</label>
                                                <input type="number" class="form-control" id="retention_period" name="retention_period" value="30">
                                                <div class="form-text">Default number of days to keep backups</div>
                                            </div>
                                            
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" id="encryption_enabled" name="encryption_enabled" checked>
                                                <label class="form-check-label" for="encryption_enabled">Enable Encryption by Default</label>
                                            </div>
                                            
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" id="compression_enabled" name="compression_enabled" checked>
                                                <label class="form-check-label" for="compression_enabled">Enable Compression by Default</label>
                                            </div>
                                            
                                            <button type="submit" class="btn btn-primary">Save Settings</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Backup Jobs Tab -->
                            <div class="tab-pane fade" id="backup-jobs" role="tabpanel">
                                <div class="card shadow mt-3">
                                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                        <h6 class="m-0 font-weight-bold text-primary">Backup Jobs</h6>
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addJobModal">
                                            <i class="fas fa-plus"></i> Add Job
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Description</th>
                                                        <th>Schedule</th>
                                                        <th>Source</th>
                                                        <th>Active</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $controller = new BackupController(getDbConnection());
                                                    $jobs = $controller->getAllJobs();
                                                    
                                                    foreach ($jobs as $job):
                                                    ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($job['name']); ?></td>
                                                            <td><?php echo htmlspecialchars(substr($job['description'], 0, 50)) . (strlen($job['description']) > 50 ? '...' : ''); ?></td>
                                                            <td><?php echo ucfirst($job['schedule_type']); ?><?php echo $job['schedule_value'] ? ' (' . $job['schedule_value'] . ')' : ''; ?></td>
                                                            <td><?php echo htmlspecialchars($job['source_path']); ?></td>
                                                            <td>
                                                                <?php if ($job['is_active']): ?>
                                                                    <span class="badge bg-success">Active</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-secondary">Inactive</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <button class="btn btn-sm btn-outline-primary" onclick="editJob(<?php echo $job['id']; ?>)">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteJob(<?php echo $job['id']; ?>)">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                    
                                                    <?php if (empty($jobs)): ?>
                                                        <tr>
                                                            <td colspan="6" class="text-center">No backup jobs configured</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Notifications Tab -->
                            <div class="tab-pane fade" id="notifications" role="tabpanel">
                                <div class="card shadow mt-3">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">Notification Settings</h6>
                                    </div>
                                    <div class="card-body">
                                        <form method="post" action="?action=settings&settings_action=update_notification">
                                            <div class="mb-3">
                                                <label for="notification_email" class="form-label">Notification Email</label>
                                                <input type="email" class="form-control" id="notification_email" name="notification_email" value="">
                                                <div class="form-text">Email address to receive backup notifications</div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="smtp_host" class="form-label">SMTP Host</label>
                                                <input type="text" class="form-control" id="smtp_host" name="smtp_host" value="">
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="smtp_port" class="form-label">SMTP Port</label>
                                                        <input type="number" class="form-control" id="smtp_port" name="smtp_port" value="587">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="smtp_encryption" class="form-label">Encryption</label>
                                                        <select class="form-select" id="smtp_encryption" name="smtp_encryption">
                                                            <option value="">None</option>
                                                            <option value="tls" selected>TLS</option>
                                                            <option value="ssl">SSL</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="smtp_username" class="form-label">SMTP Username</label>
                                                <input type="text" class="form-control" id="smtp_username" name="smtp_username" value="">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="smtp_password" class="form-label">SMTP Password</label>
                                                <input type="password" class="form-control" id="smtp_password" name="smtp_password">
                                            </div>
                                            
                                            <button type="submit" class="btn btn-primary">Save Notification Settings</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add/Edit Job Modal -->
    <div class="modal fade" id="addJobModal" tabindex="-1" aria-labelledby="addJobModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addJobModalLabel"><span id="jobModalTitle">Add</span> Backup Job</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" id="jobForm" action="?action=settings&settings_action=manage_job">
                    <div class="modal-body">
                        <input type="hidden" id="jobId" name="job_id" value="">
                        
                        <div class="mb-3">
                            <label for="jobName" class="form-label">Job Name</label>
                            <input type="text" class="form-control" id="jobName" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="jobDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="jobDescription" name="description" rows="2"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sourcePath" class="form-label">Source Path</label>
                                    <input type="text" class="form-control" id="sourcePath" name="source_path" required>
                                    <div class="form-text">Path to backup (e.g., /home/user/data/)</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="destinationPath" class="form-label">Destination Path</label>
                                    <input type="text" class="form-control" id="destinationPath" name="destination_path" value="<?php echo UPLOAD_PATH; ?>">
                                    <div class="form-text">Where to store backups</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="scheduleType" class="form-label">Schedule Type</label>
                                    <select class="form-select" id="scheduleType" name="schedule_type">
                                        <option value="manual">Manual Only</option>
                                        <option value="hourly">Hourly</option>
                                        <option value="daily" selected>Daily</option>
                                        <option value="weekly">Weekly</option>
                                        <option value="monthly">Monthly</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="scheduleValue" class="form-label">Schedule Value</label>
                                    <input type="text" class="form-control" id="scheduleValue" name="schedule_value" placeholder="e.g., 2AM, Monday, 1st">
                                    <div class="form-text">Specific time/configuration for schedule</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3 form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="encryptionEnabled" name="encryption_enabled">
                                    <label class="form-check-label" for="encryptionEnabled">Enable Encryption</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3 form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="compressionEnabled" name="compression_enabled" checked>
                                    <label class="form-check-label" for="compressionEnabled">Enable Compression</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="retentionDays" class="form-label">Retention Days</label>
                            <input type="number" class="form-control" id="retentionDays" name="retention_days" value="30" min="1">
                        </div>
                        
                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="isActive" name="is_active" checked>
                            <label class="form-check-label" for="isActive">Active Job</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="jobSubmitBtn">Create Job</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editJob(jobId) {
            // In a real implementation, you would fetch job data via AJAX
            // For now, we'll just populate the form with placeholder values
            document.getElementById('jobId').value = jobId;
            document.getElementById('jobModalTitle').textContent = 'Edit';
            document.getElementById('jobSubmitBtn').textContent = 'Update Job';
            
            // Placeholder - in real app, fetch data via AJAX
            document.getElementById('jobName').value = 'Updated Job Name';
            document.getElementById('sourcePath').value = '/path/to/source';
            document.getElementById('destinationPath').value = '<?php echo UPLOAD_PATH; ?>';
            
            var jobModal = new bootstrap.Modal(document.getElementById('addJobModal'));
            jobModal.show();
        }
        
        function deleteJob(jobId) {
            if (confirm('Are you sure you want to delete this backup job?')) {
                // In a real implementation, this would be an AJAX call
                window.location.href = '?action=settings&settings_action=delete_job&id=' + jobId;
            }
        }
        
        // Reset the form when the modal is closed
        document.getElementById('addJobModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('jobId').value = '';
            document.getElementById('jobForm').reset();
            document.getElementById('jobModalTitle').textContent = 'Add';
            document.getElementById('jobSubmitBtn').textContent = 'Create Job';
        });
    </script>
</body>
</html>