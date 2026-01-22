<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Backup - <?php echo APP_NAME; ?></title>
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
                    <h1 class="h2">Create New Backup</h1>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Manual Backup Configuration</h6>
                            </div>
                            <div class="card-body">
                                <form method="post" action="?action=create_backup">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Backup Name</label>
                                        <input type="text" class="form-control" id="name" name="name" value="Manual Backup - <?php echo date('Y-m-d H:i:s'); ?>" required>
                                        <div class="form-text">A descriptive name for this backup</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="source_path" class="form-label">Source Path</label>
                                        <input type="text" class="form-control" id="source_path" name="source_path" value="" placeholder="/path/to/backup/" required>
                                        <div class="form-text">The path to backup (e.g., /var/www/html/AutomaticDataBackupSystem/)</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="destination_path" class="form-label">Destination Path</label>
                                        <input type="text" class="form-control" id="destination_path" name="destination_path" value="<?php echo rtrim(UPLOAD_PATH, '/'); ?>/" required>
                                        <div class="form-text">Where to store the backup file (default: <?php echo UPLOAD_PATH; ?>)</div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3 form-check">
                                                <input type="checkbox" class="form-check-input" id="encryption_enabled" name="encryption_enabled">
                                                <label class="form-check-label" for="encryption_enabled">Enable Encryption</label>
                                                <div class="form-text">Encrypt the backup file for security</div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3 form-check">
                                                <input type="checkbox" class="form-check-input" id="compression_enabled" name="compression_enabled" checked>
                                                <label class="form-check-label" for="compression_enabled">Enable Compression</label>
                                                <div class="form-text">Compress the backup to save space</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="retention_days" class="form-label">Retention Period (Days)</label>
                                        <input type="number" class="form-control" id="retention_days" name="retention_days" value="<?php echo MAX_BACKUP_RETENTION_DAYS; ?>" min="1">
                                        <div class="form-text">Number of days to keep this backup before auto-deletion</div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Create Backup Now
                                    </button>
                                    <a href="?action=dashboard" class="btn btn-secondary">Cancel</a>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Scheduled Backup Jobs</h6>
                            </div>
                            <div class="card-body">
                                <p>For regularly scheduled backups, create backup jobs in the Settings section.</p>
                                <a href="?action=settings#backup-jobs" class="btn btn-info">Manage Backup Jobs</a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>