<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo ($_GET['action'] ?? 'dashboard') === 'dashboard' ? 'active' : ''; ?>" href="?action=dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($_GET['action'] ?? '') === 'create_backup' ? 'active' : ''; ?>" href="?action=create_backup">
                    <i class="fas fa-plus-circle"></i>
                    Create Backup
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($_GET['action'] ?? '') === 'manage_backups' ? 'active' : ''; ?>" href="?action=manage_backups">
                    <i class="fas fa-database"></i>
                    Manage Backups
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($_GET['action'] ?? '') === 'settings' ? 'active' : ''; ?>" href="?action=settings">
                    <i class="fas fa-cog"></i>
                    Settings
                </a>
            </li>
        </ul>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Scheduled Jobs</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <?php
            // Show backup jobs in the sidebar
            $db = getDbConnection();
            $stmt = $db->query("SELECT id, name FROM backup_jobs LIMIT 5");
            $jobs = $stmt->fetchAll();
            
            foreach ($jobs as $job):
            ?>
                <li class="nav-item">
                    <a class="nav-link" href="?action=manage_backups&job_id=<?php echo $job['id']; ?>">
                        <i class="fas fa-clock"></i>
                        <?php echo htmlspecialchars($job['name']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
            
            <?php if (empty($jobs)): ?>
                <li class="nav-item">
                    <a class="nav-link text-muted" href="?action=settings#backup-jobs">
                        <i class="fas fa-plus"></i>
                        Create Job
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>