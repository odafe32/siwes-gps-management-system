<?php
// Get current page for active navigation highlighting
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-logo">
            <i class="fas fa-graduation-cap"></i>
            <span>SIWES Student</span>
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
            <a href="log-entry.php" class="nav-link <?php echo $current_page === 'log-entry' ? 'active' : ''; ?>" data-title="New Log Entry">
                <i class="fas fa-plus-circle"></i>
                <span>New Log Entry</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="history.php" class="nav-link <?php echo $current_page === 'history' ? 'active' : ''; ?>" data-title="Log History">
                <i class="fas fa-history"></i>
                <span>Log History</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="profile.php" class="nav-link <?php echo $current_page === 'profile' ? 'active' : ''; ?>" data-title="Profile">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="reports.php" class="nav-link <?php echo $current_page === 'reports' ? 'active' : ''; ?>" data-title="Reports">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
        </div>
        <div class="nav-item">
            <a href="settings.php" class="nav-link <?php echo $current_page === 'settings' ? 'active' : ''; ?>" data-title="Settings">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </div>
    </nav>
</div>

<!-- Overlay for mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div> 