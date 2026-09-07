<?php
// Get current page for active navigation highlighting
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-logo">
            <i class="fas fa-graduation-cap"></i>
            SIWES Admin
        </a>
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <nav class="sidebar-nav">
        <!-- Main Navigation -->
        <div class="nav-section">
            <div class="nav-section-title">Main</div>
        </div>
        <div class="nav-item">
            <a href="dashboard.php" class="nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
        </div>

        <div class="nav-item">
            <a href="organizations.php" class="nav-link <?php echo ($current_page === 'organizations' || $current_page === 'organization-add' || $current_page === 'organization-edit') ? 'active' : ''; ?>">
                <i class="fas fa-building"></i>
                Organizations
            </a>
        </div>

        <div class="nav-item">
            <a href="studentmanagement.php" class="nav-link <?php echo $current_page === "studentmanagement" ? "active" : ""; ?>">
                <i class="fas fa-user-graduate"></i>
                Student Management
            </a>
        </div>
        <div class="nav-item">
            <a href="supervisormanagement.php" class="nav-link <?php echo $current_page === "supervisormanagement" ? "active" : ""; ?>">
                <i class="fas fa-user-tie"></i>
                Supervisor Management
            </a>
        </div>
        <div class="nav-item">
            <a href="logbook-management.php" class="nav-link <?php echo $current_page === "logbook-management" ? "active" : ""; ?>">
                <i class="fas fa-book"></i>
                Logbook Management
            </a>
        </div>
    
        <div class="nav-item">
            <a href="notifications.php" class="nav-link <?php echo $current_page === "notifications" ? "active" : ""; ?>">
                <i class="fas fa-bell"></i>
                Notifications
            </a>
        </div>
        <div class="nav-item">
            <a href="backup-restore.php" class="nav-link <?php echo $current_page === "backup-restore" ? "active" : ""; ?>">
                <i class="fas fa-database"></i>
                Backup & Restore
            </a>
        </div>
        <div class="nav-item">
            <a href="usermanagement.php" class="nav-link <?php echo $current_page === 'usermanagement' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                User Management
            </a>
        </div>

        <!-- Management Section -->
        <div class="nav-section">
            <div class="nav-section-title">Management</div>
        </div>
        
        <div class="nav-item">
            <a href="reports.php" class="nav-link <?php echo $current_page === 'reports' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i>
                Reports
            </a>
        </div>
        <div class="nav-item">
            <a href="gps-monitoring.php" class="nav-link <?php echo $current_page === 'gps-monitoring' ? 'active' : ''; ?>">
                <i class="fas fa-map-marker-alt"></i>
                GPS Monitoring
            </a>
        </div>
        <div class="nav-item">
            <a href="communications.php" class="nav-link <?php echo $current_page === 'communications' ? 'active' : ''; ?>">
                <i class="fas fa-comments"></i>
                Communications
            </a>
        </div>

        <!-- System Tools Section -->
        <div class="nav-section">
            <div class="nav-section-title">System Tools</div>
        </div>
        
        <div class="nav-item">
            <a href="settings.php" class="nav-link <?php echo $current_page === 'settings' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                Settings
            </a>
        </div>

        <!-- Account Section -->
        <div class="nav-section">
            <div class="nav-section-title">Account</div>
        </div>
        
        <div class="nav-item">
            <a href="logout.php" class="nav-link <?php echo $current_page === 'logout' ? 'active' : ''; ?>">

                <i class="fas fa-power-off"></i>

                LOGOUT
            </a>
        </div>
    </nav>
</div>

<!-- Overlay for mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div> 




