<?php
session_start();
require_once '../backend/config/db.php';
require_once '../backend/config/session.php';

// Check if user is logged in and has student role
if (!isLoggedIn() || !hasRole('student')) {
    header('Location: login.php');
    exit();
}

// Get student statistics with error handling
try {
$user_id = $_SESSION['user_id'];
    
    // Total logs
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM log_entries WHERE student_id = ?");
    $stmt->execute([$user_id]);
    $totalLogs = $stmt->fetch()['count'];
    
    // Pending logs
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM log_entries WHERE student_id = ? AND status = 'pending'");
$stmt->execute([$user_id]);
    $pendingLogs = $stmt->fetch()['count'];

    // Approved logs
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM log_entries WHERE student_id = ? AND status = 'approved'");
$stmt->execute([$user_id]);
    $approvedLogs = $stmt->fetch()['count'];

    // Rejected logs
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM log_entries WHERE student_id = ? AND status = 'rejected'");
$stmt->execute([$user_id]);
    $rejectedLogs = $stmt->fetch()['count'];

    // Recent activities
    $stmt = $pdo->prepare("SELECT * FROM log_entries WHERE student_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
    $recentActivities = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    // Set default values if database query fails
    $totalLogs = 0;
    $pendingLogs = 0;
    $approvedLogs = 0;
    $rejectedLogs = 0;
    $recentActivities = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - SIWES Logbook</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/student-styles.css">
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
                <h1 class="page-title">Dashboard</h1>
            </div>
            
            <div class="user-menu">
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Student'); ?></div>
                    <div class="user-role">Student</div>
                </div>
                <button class="logout-btn" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </button>
        </div>
        </header>
        
        <!-- Dashboard Content -->
        <div class="page-content">
            <!-- Error Display -->
            <?php if (isset($error)): ?>
                <div class="alert alert-danger fade-in-up">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Welcome Section -->
            <div class="welcome-section fade-in-up">
                <h2 class="welcome-title">Welcome back, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Student'); ?>!</h2>
                <p class="welcome-subtitle">Track your SIWES progress and manage your log entries.</p>
            </div>
            
            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card fade-in-up">
                    <div class="stat-header">
                        <div class="stat-icon primary">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="stat-number"><?php echo $totalLogs; ?></div>
                    </div>
                    <div class="stat-label">Total Log Entries</div>
                </div>
                
                <div class="stat-card warning fade-in-up">
                    <div class="stat-header">
                        <div class="stat-icon warning">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-number"><?php echo $pendingLogs; ?></div>
                    </div>
                    <div class="stat-label">Pending Review</div>
                        </div>
                
                <div class="stat-card success fade-in-up">
                    <div class="stat-header">
                        <div class="stat-icon success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-number"><?php echo $approvedLogs; ?></div>
                    </div>
                    <div class="stat-label">Approved</div>
                </div>
                
                <div class="stat-card danger fade-in-up">
                    <div class="stat-header">
                        <div class="stat-icon danger">
                            <i class="fas fa-times-circle"></i>
                            </div>
                        <div class="stat-number"><?php echo $rejectedLogs; ?></div>
                                    </div>
                    <div class="stat-label">Rejected</div>
                                    </div>
                                </div>
            
            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Recent Activities -->
                <div class="recent-activities fade-in-up">
                    <h3 class="section-title">
                        <i class="fas fa-history"></i>
                        Recent Activities
                    </h3>
                    
                    <?php if (!empty($recentActivities)): ?>
                        <?php foreach ($recentActivities as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon <?php echo $activity['status'] === 'approved' ? 'success' : ($activity['status'] === 'rejected' ? 'danger' : 'warning'); ?>">
                                    <i class="fas fa-<?php echo $activity['status'] === 'approved' ? 'check' : ($activity['status'] === 'rejected' ? 'times' : 'clock'); ?>"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title">
                                        Log Entry - <?php echo ucfirst($activity['status']); ?>
                                    </div>
                                    <div class="activity-time">
                                        <?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="activity-item">
                            <div class="activity-content">
                                <div class="activity-title">No recent activities</div>
                                <div class="activity-time">Start by creating your first log entry</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Quick Actions -->
                <div class="quick-actions fade-in-up">
                    <h3 class="section-title">
                        <i class="fas fa-bolt"></i>
                        Quick Actions
                    </h3>
                    
                    <a href="log-entry.php" class="action-btn">
                            <i class="fas fa-plus-circle"></i>
                        New Log Entry
                    </a>
                    
                    <a href="history.php" class="action-btn">
                            <i class="fas fa-history"></i>
                        View History
                    </a>
                    
                    <a href="profile.php" class="action-btn">
                        <i class="fas fa-user"></i>
                        Update Profile
                    </a>
                    
                    <a href="reports.php" class="action-btn">
                        <i class="fas fa-chart-bar"></i>
                        Generate Reports
                    </a>
                    
                    <a href="settings.php" class="action-btn">
                        <i class="fas fa-cog"></i>
                        Settings
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/student-scripts.js"></script>
</body>
</html> 