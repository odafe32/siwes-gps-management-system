<?php
// Get current page for active navigation highlighting
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-logo">
            <i class="fas fa-user-tie"></i>
            <span>SIWES Supervisor</span>
        </a>
        <button class="sidebar-toggle" id="sidebarToggle" title="Toggle Sidebar">
            <i class="fas fa-chevron-left"></i>
        </button>
    </div>
    
    <nav class="sidebar-nav">
        <div class="nav-item">
            <a href="dashboard.php" class="nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>" data-title="Dashboard">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="map.php" class="nav-link <?php echo $current_page === 'map' ? 'active' : ''; ?>" data-title="Live Map">
                <i class="fas fa-map-marked-alt"></i>
                <span>Live Map</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="review.php" class="nav-link <?php echo $current_page === 'review' ? 'active' : ''; ?>" data-title="Review Logs">
                <i class="fas fa-clipboard-check"></i>
                <span>Review Logs</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="students.php" class="nav-link <?php echo $current_page === 'students' ? 'active' : ''; ?>" data-title="My Students">
                <i class="fas fa-users"></i>
                <span>My Students</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="alerts.php" class="nav-link <?php echo $current_page === 'alerts' ? 'active' : ''; ?>" data-title="Geofence Alerts">
                <i class="fas fa-bell"></i>
                <span>Geofence Alerts</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="reports.php" class="nav-link <?php echo $current_page === 'reports' ? 'active' : ''; ?>" data-title="Reports">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="profile.php" class="nav-link <?php echo $current_page === 'profile' ? 'active' : ''; ?>" data-title="Profile">
                <i class="fas fa-user-circle"></i>
                <span>Profile</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="settings.php" class="nav-link <?php echo $current_page === 'settings' ? 'active' : ''; ?>" data-title="Settings">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </div>
        <div class="nav-item mt-auto">
            <a href="#" class="nav-link" onclick="logout()" data-title="Logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </nav>
</div>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>