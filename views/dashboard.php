<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
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
                    <h1 class="h2">Dashboard</h1>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <!-- Stats Overview -->
                <div class="row">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Backups</div>
                                        <?php 
                                        $totalBackups = $db->query("SELECT COUNT(*) FROM backup_records")->fetchColumn();
                                        echo "<div class='h5 mb-0 font-weight-bold text-gray-800'>$totalBackups</div>";
                                        ?>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-database fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Successful Backups</div>
                                        <?php 
                                        $successfulBackups = $db->query("SELECT COUNT(*) FROM backup_records WHERE status = 'completed'")->fetchColumn();
                                        echo "<div class='h5 mb-0 font-weight-bold text-gray-800'>$successfulBackups</div>";
                                        ?>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Backup Jobs</div>
                                        <?php 
                                        $totalJobs = $db->query("SELECT COUNT(*) FROM backup_jobs")->fetchColumn();
                                        echo "<div class='h5 mb-0 font-weight-bold text-gray-800'>$totalJobs</div>";
                                        ?>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-tasks fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Failed Backups</div>
                                        <?php 
                                        $failedBackups = $db->query("SELECT COUNT(*) FROM backup_records WHERE status = 'failed'")->fetchColumn();
                                        echo "<div class='h5 mb-0 font-weight-bold text-gray-800'>$failedBackups</div>";
                                        ?>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <a href="?action=create_backup" class="btn btn-primary btn-block">
                                            <i class="fas fa-plus"></i> Create Backup
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="?action=manage_backups" class="btn btn-secondary btn-block">
                                            <i class="fas fa-list"></i> Manage Backups
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="?action=settings" class="btn btn-info btn-block">
                                            <i class="fas fa-cog"></i> Settings
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="#" class="btn btn-success btn-block" id="runScheduledBackups">
                                            <i class="fas fa-play"></i> Run Scheduled
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Backups -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Recent Backups</h6>
                                <a href="?action=manage_backups" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Job Name</th>
                                                <th>Filename</th>
                                                <th>Size</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $controller = new BackupController($db);
                                            $recentBackups = $controller->getRecentBackups(5);
                                            
                                            foreach ($recentBackups as $backup):
                                                $statusClass = $backup['status'] === 'completed' ? 'success' : ($backup['status'] === 'failed' ? 'danger' : 'warning');
                                            ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($backup['job_name'] ?: 'Manual Backup'); ?></td>
                                                    <td><?php echo htmlspecialchars(substr($backup['filename'], 0, 40)) . (strlen($backup['filename']) > 40 ? '...' : ''); ?></td>
                                                    <td><?php echo $backup['file_size'] ? formatBytes($backup['file_size']) : 'N/A'; ?></td>
                                                    <td><span class="badge bg-<?php echo $statusClass; ?>"><?php echo ucfirst($backup['status']); ?></span></td>
                                                    <td><?php echo $backup['start_time'] ? date('M j, Y g:i A', strtotime($backup['start_time'])) : 'N/A'; ?></td>
                                                    <td>
                                                        <a href="backups/<?php echo urlencode($backup['filename']); ?>" class="btn btn-sm btn-outline-primary" download>Download</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
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