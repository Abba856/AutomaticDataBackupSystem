<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Backups - <?php echo APP_NAME; ?></title>
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
                    <h1 class="h2">Manage Backups</h1>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <?php
                $controller = new BackupController(getDbConnection());
                $jobId = isset($_GET['job_id']) ? (int)$_GET['job_id'] : null;

                // Get filters
                $search = isset($_GET['search']) ? trim($_GET['search']) : '';
                $statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

                // Get backups based on filters
                $backups = $controller->getAllBackups($jobId);

                // Apply search filter
                if (!empty($search)) {
                    $filteredBackups = [];
                    foreach ($backups as $backup) {
                        if (stripos($backup['filename'], $search) !== false ||
                            stripos($backup['job_name'], $search) !== false) {
                            $filteredBackups[] = $backup;
                        }
                    }
                    $backups = $filteredBackups;
                }

                // Apply status filter
                if (!empty($statusFilter)) {
                    $filteredBackups = [];
                    foreach ($backups as $backup) {
                        if ($backup['status'] === $statusFilter) {
                            $filteredBackups[] = $backup;
                        }
                    }
                    $backups = $filteredBackups;
                }

                $jobs = $controller->getAllJobs();
                ?>

                <div class="row mb-3">
                    <div class="col-md-8">
                        <form method="GET" class="row g-2">
                            <input type="hidden" name="action" value="manage_backups">

                            <div class="col-md-4">
                                <select name="job_id" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Backup Jobs</option>
                                    <?php foreach ($jobs as $job): ?>
                                        <option value="<?php echo $job['id']; ?>" <?php echo ($jobId == $job['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($job['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <select name="status" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Statuses</option>
                                    <option value="pending" <?php echo ($statusFilter == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="running" <?php echo ($statusFilter == 'running') ? 'selected' : ''; ?>>Running</option>
                                    <option value="completed" <?php echo ($statusFilter == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                    <option value="failed" <?php echo ($statusFilter == 'failed') ? 'selected' : ''; ?>>Failed</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>" onchange="this.form.submit()">
                                    <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
                                </div>
                            </div>

                            <?php if ($search || $statusFilter || $jobId): ?>
                                <div class="col-md-2">
                                    <a href="?action=manage_backups" class="btn btn-sm btn-outline-secondary">Clear</a>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>

                    <div class="col-md-4 text-end">
                        <a href="?action=create_backup" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create New Backup
                        </a>
                    </div>
                </div>

                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center mb-2">
                    <div class="mb-2 mb-md-0">
                        <small class="text-muted">
                            Showing <?php echo count($backups); ?> of <?php
                                $allBackupsCount = count($controller->getAllBackups($jobId));
                                echo $allBackupsCount;
                            ?> backup(s)
                        </small>
                    </div>
                </div>

                <div class="card shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Job Name</th>
                                        <th>Filename</th>
                                        <th>Size</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Duration</th>
                                        <th>Encrypted</th>
                                        <th>Compressed</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($backups)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center">No backups found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($backups as $backup):
                                            $statusClass = $backup['status'] === 'completed' ? 'success' : ($backup['status'] === 'failed' ? 'danger' : 'warning');
                                            $duration = $backup['duration_seconds'] ?
                                                ($backup['duration_seconds'] > 60 ?
                                                    round($backup['duration_seconds']/60, 1).' min' :
                                                    $backup['duration_seconds'].' sec') : 'N/A';
                                            $encryptedStatus = $backup['encrypted'] ? '<i class="fas fa-lock text-success" title="Encrypted"></i>' : '<i class="fas fa-lock-open text-muted" title="Not Encrypted"></i>';
                                            $compressedStatus = $backup['compressed'] ? '<i class="fas fa-compress-arrows-alt text-success" title="Compressed"></i>' : '<i class="fas fa-compress-arrows-alt text-muted" title="Not Compressed"></i>';
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($backup['job_name'] ?: 'Manual Backup'); ?></td>
                                                <td>
                                                    <small title="<?php echo htmlspecialchars($backup['filename']); ?>">
                                                        <?php echo htmlspecialchars(substr($backup['filename'], 0, 50)) . (strlen($backup['filename']) > 50 ? '...' : ''); ?>
                                                    </small>
                                                </td>
                                                <td><?php echo $backup['file_size'] ? formatBytes($backup['file_size']) : 'N/A'; ?></td>
                                                <td><span class="badge bg-<?php echo $statusClass; ?>"><?php echo ucfirst($backup['status']); ?></span></td>
                                                <td><?php echo $backup['start_time'] ? date('M j, Y g:i A', strtotime($backup['start_time'])) : 'N/A'; ?></td>
                                                <td><?php echo $duration; ?></td>
                                                <td><?php echo $encryptedStatus; ?></td>
                                                <td><?php echo $compressedStatus; ?></td>
                                                <td>
                                                    <?php if ($backup['status'] === 'completed'): ?>
                                                        <a href="backups/<?php echo urlencode($backup['filename']); ?>" class="btn btn-sm btn-outline-primary" title="Download" download>
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                        <a href="#" class="btn btn-sm btn-outline-danger" title="Delete" onclick="confirmDelete(<?php echo $backup['id']; ?>, '<?php echo addslashes(htmlspecialchars($backup['filename'])); ?>')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this backup?</p>
                    <p class="fw-bold" id="backupFileName"></p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a id="deleteConfirmBtn" href="#" class="btn btn-danger">Delete</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(backupId, fileName) {
            document.getElementById('backupFileName').textContent = fileName;
            document.getElementById('deleteConfirmBtn').href = '?action=manage_backups&delete=1&id=' + backupId;
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }
    </script>
</body>
</html>