<?php
session_start();
require_once '../backend/config/db.php';
require_once '../backend/config/session.php';

// Check if user is logged in and has admin/coordinator role
if (!isLoggedIn() || (!hasRole('admin') && !hasRole('coordinator'))) {
    header('Location: login.php');
    exit();
}

// Get report statistics with error handling
try {
    // Total students
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
    $totalStudents = $stmt->fetch()['count'];
    
    // Total supervisors
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'supervisor'");
    $totalSupervisors = $stmt->fetch()['count'];
    
    // Total log entries
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM log_entries");
    $totalLogs = $stmt->fetch()['count'];
    
    // Pending logs
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM log_entries WHERE status = 'pending'");
    $pendingLogs = $stmt->fetch()['count'];
    
    // Approved logs
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM log_entries WHERE status = 'approved'");
    $approvedLogs = $stmt->fetch()['count'];
    
    // Rejected logs
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM log_entries WHERE status = 'rejected'");
    $rejectedLogs = $stmt->fetch()['count'];
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    // Set default values if database query fails
    $totalStudents = 0;
    $totalSupervisors = 0;
    $totalLogs = 0;
    $pendingLogs = 0;
    $approvedLogs = 0;
    $rejectedLogs = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - SIWES Logbook</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/admin-styles.css">
    
    <!-- Additional reports-specific styles -->
    <style>
        .report-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 4px solid var(--primary-color);
        }
        
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .report-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        
        .report-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        
        .report-icon.primary {
            background: var(--primary-color);
        }
        
        .report-icon.success {
            background: var(--success-color);
        }
        
        .report-icon.warning {
            background: var(--warning-color);
        }
        
        .report-icon.danger {
            background: var(--danger-color);
        }
        
        .report-icon.info {
            background: var(--info-color);
        }
        
        .report-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .report-label {
            font-size: 0.875rem;
            color: #6c757d;
            font-weight: 500;
        }
        
        .report-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.875rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .view-btn {
            background: var(--info-color);
            color: white;
        }
        
        .view-btn:hover {
            background: #138496;
            color: white;
        }
        
        .download-btn {
            background: var(--success-color);
            color: white;
        }
        
        .download-btn:hover {
            background: #218838;
            color: white;
        }
        
        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .reports-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Include Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="header">
            <div class="header-left">
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
            </button>
                <h1 class="page-title">Reports</h1>
            </div>
            
            <div class="user-menu">
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></div>
                    <div class="user-role"><?php echo ucfirst($_SESSION['role'] ?? 'admin'); ?></div>
                </div>
                <button class="logout-btn" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </button>
            </div>
        </header>
        
        <!-- Page Content -->
        <div class="page-content">
            <!-- Error Display -->
            <?php if (isset($error)): ?>
                <div class="alert alert-danger fade-in-up">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h2 class="mb-1">Reports & Analytics</h2>
                    <p class="text-muted mb-0">Generate and view system reports</p>
                </div>
            </div>
            
            <!-- Reports Grid -->
            <div class="reports-grid">
                <!-- User Statistics Report -->
                <div class="report-card fade-in-up">
                    <div class="report-header">
                        <div class="report-icon primary">
                            <i class="fas fa-users"></i>
                    </div>
                        <div class="report-number"><?php echo $totalStudents + $totalSupervisors; ?></div>
                    </div>
                    <div class="report-label">Total Users</div>
                    <div class="report-actions">
                        <a href="#" class="action-btn view-btn" onclick="generateUserReport()">
                            <i class="fas fa-eye"></i>
                            View
                        </a>
                        <a href="#" class="action-btn download-btn" onclick="downloadUserReport()">
                            <i class="fas fa-download"></i>
                            Download
                        </a>
            </div>
        </div>

                <!-- Log Entries Report -->
                <div class="report-card fade-in-up">
                    <div class="report-header">
                        <div class="report-icon info">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="report-number"><?php echo $totalLogs; ?></div>
                    </div>
                    <div class="report-label">Total Log Entries</div>
                    <div class="report-actions">
                        <a href="#" class="action-btn view-btn" onclick="generateLogReport()">
                            <i class="fas fa-eye"></i>
                            View
                        </a>
                        <a href="#" class="action-btn download-btn" onclick="downloadLogReport()">
                            <i class="fas fa-download"></i>
                            Download
                        </a>
                </div>
            </div>
            
                <!-- Pending Reviews Report -->
                <div class="report-card fade-in-up">
                    <div class="report-header">
                        <div class="report-icon warning">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="report-number"><?php echo $pendingLogs; ?></div>
                    </div>
                    <div class="report-label">Pending Reviews</div>
                    <div class="report-actions">
                        <a href="#" class="action-btn view-btn" onclick="generatePendingReport()">
                            <i class="fas fa-eye"></i>
                            View
                        </a>
                        <a href="#" class="action-btn download-btn" onclick="downloadPendingReport()">
                            <i class="fas fa-download"></i>
                            Download
                        </a>
            </div>
        </div>

                <!-- Approved Reports -->
                <div class="report-card fade-in-up">
                    <div class="report-header">
                        <div class="report-icon success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="report-number"><?php echo $approvedLogs; ?></div>
                    </div>
                    <div class="report-label">Approved Entries</div>
                    <div class="report-actions">
                        <a href="#" class="action-btn view-btn" onclick="generateApprovedReport()">
                            <i class="fas fa-eye"></i>
                            View
                        </a>
                        <a href="#" class="action-btn download-btn" onclick="downloadApprovedReport()">
                            <i class="fas fa-download"></i>
                            Download
                        </a>
                </div>
            </div>
            
                <!-- Rejected Reports -->
                <div class="report-card fade-in-up">
                    <div class="report-header">
                        <div class="report-icon danger">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="report-number"><?php echo $rejectedLogs; ?></div>
                    </div>
                    <div class="report-label">Rejected Entries</div>
                    <div class="report-actions">
                        <a href="#" class="action-btn view-btn" onclick="generateRejectedReport()">
                            <i class="fas fa-eye"></i>
                            View
                        </a>
                        <a href="#" class="action-btn download-btn" onclick="downloadRejectedReport()">
                            <i class="fas fa-download"></i>
                            Download
                        </a>
                    </div>
                </div>
                
                <!-- System Activity Report -->
                <div class="report-card fade-in-up">
                    <div class="report-header">
                        <div class="report-icon primary">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="report-number">-</div>
                        </div>
                    <div class="report-label">System Activity</div>
                    <div class="report-actions">
                        <a href="#" class="action-btn view-btn" onclick="generateActivityReport()">
                            <i class="fas fa-eye"></i>
                            View
                        </a>
                        <a href="#" class="action-btn download-btn" onclick="downloadActivityReport()">
                            <i class="fas fa-download"></i>
                            Download
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/admin-scripts.js"></script>
    <script>
        // Report generation functions
        function generateUserReport() {
            showToast('User report generation will be implemented in the next update', 'info');
        }
        
        function downloadUserReport() {
            showToast('User report download will be implemented in the next update', 'info');
        }
        
        function generateLogReport() {
            showToast('Log report generation will be implemented in the next update', 'info');
        }
        
        function downloadLogReport() {
            showToast('Log report download will be implemented in the next update', 'info');
        }
        
        function generatePendingReport() {
            showToast('Pending report generation will be implemented in the next update', 'info');
        }
        
        function downloadPendingReport() {
            showToast('Pending report download will be implemented in the next update', 'info');
        }
        
        function generateApprovedReport() {
            showToast('Approved report generation will be implemented in the next update', 'info');
        }
        
        function downloadApprovedReport() {
            showToast('Approved report download will be implemented in the next update', 'info');
        }
        
        function generateRejectedReport() {
            showToast('Rejected report generation will be implemented in the next update', 'info');
        }
        
        function downloadRejectedReport() {
            showToast('Rejected report download will be implemented in the next update', 'info');
        }
        
        function generateActivityReport() {
            showToast('Activity report generation will be implemented in the next update', 'info');
        }
        
        function downloadActivityReport() {
            showToast('Activity report download will be implemented in the next update', 'info');
        }
    </script>
</body>
</html> 