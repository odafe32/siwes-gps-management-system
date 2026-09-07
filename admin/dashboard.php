<?php
session_start();
require_once '../backend/config/db.php';
require_once '../backend/config/session.php';

// Check if user is logged in and has admin/coordinator role
if (!isLoggedIn() || (!hasRole('admin') && !hasRole('coordinator'))) {
    header('Location: login.php');
    exit();
}

// Get dashboard statistics with error handling
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
    
    // Recent activities
    $stmt = $pdo->query("SELECT le.*, u.full_name as student_name FROM log_entries le 
    JOIN users u ON le.student_id = u.id 
                         ORDER BY le.created_at DESC LIMIT 5");
    $recentActivities = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    // Set default values if database query fails
    $totalStudents = 0;
    $totalSupervisors = 0;
    $totalLogs = 0;
    $pendingLogs = 0;
    $approvedLogs = 0;
    $rejectedLogs = 0;
    $recentActivities = [];
}

// Get system-wide geofence breach alerts
try {
    $stmt = $pdo->query("
        SELECT n.*, u.full_name as supervisor_name
        FROM notifications n
        LEFT JOIN users u ON u.id = n.user_id
        WHERE n.type = 'geofence_breach'
        ORDER BY n.created_at DESC LIMIT 10
    ");
    $systemBreaches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $unreadSystemBreaches = array_filter($systemBreaches, function($a) { return !$a['is_read']; });
    $systemBreachCount = count($unreadSystemBreaches);
} catch (PDOException $e) {
    $systemBreaches = [];
    $systemBreachCount = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SIWES Logbook</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/admin-styles.css">
    
    <!-- Additional dashboard-specific styles -->
    <style>
        .welcome-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .welcome-section::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            transform: translate(50%, -50%);
        }
        
        .welcome-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .welcome-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 4px solid var(--primary-color);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .stat-card.success {
            border-left-color: var(--success-color);
        }
        
        .stat-card.warning {
            border-left-color: var(--warning-color);
        }
        
        .stat-card.danger {
            border-left-color: var(--danger-color);
        }
        
        .stat-card.info {
            border-left-color: var(--info-color);
        }
        
        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        
        .stat-icon.primary {
            background: var(--primary-color);
        }
        
        .stat-icon.success {
            background: var(--success-color);
        }
        
        .stat-icon.warning {
            background: var(--warning-color);
        }
        
        .stat-icon.danger {
            background: var(--danger-color);
        }
        
        .stat-icon.info {
            background: var(--info-color);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: #6c757d;
            font-weight: 500;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .recent-activities {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1rem;
            color: white;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.25rem;
        }
        
        .activity-time {
            font-size: 0.875rem;
            color: #6c757d;
        }
        
        .quick-actions {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .action-btn {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            width: 100%;
            padding: 1rem;
            margin-bottom: 0.75rem;
            background: var(--light-color);
            border: none;
            border-radius: 8px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .action-btn:hover {
            background: var(--primary-color);
            color: white;
            transform: translateX(5px);
        }
        
        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
        <?php 
        $pageTitle = 'Dashboard';
        include 'includes/header.php'; 
        ?>
        
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
                <h2 class="welcome-title">Welcome back, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?>!</h2>
                <p class="welcome-subtitle">Here's what's happening with your SIWES system today.</p>
            </div>
            
            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card fade-in-up">
                    <div class="stat-header">
                        <div class="stat-icon primary">
                            <i class="fas fa-users"></i>
                    </div>
                        <div class="stat-number"><?php echo $totalStudents; ?></div>
                    </div>
                    <div class="stat-label">Total Students</div>
                </div>
                
                <div class="stat-card fade-in-up">
                    <div class="stat-header">
                        <div class="stat-icon success">
                            <i class="fas fa-user-tie"></i>
            </div>
                        <div class="stat-number"><?php echo $totalSupervisors; ?></div>
                    </div>
                    <div class="stat-label">Supervisors</div>
        </div>

                <div class="stat-card fade-in-up">
                    <div class="stat-header">
                        <div class="stat-icon info">
                            <i class="fas fa-clipboard-list"></i>
                    </div>
                        <div class="stat-number"><?php echo $totalLogs; ?></div>
                    </div>
                    <div class="stat-label">Total Logs</div>
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

            <?php if ($systemBreachCount > 0): ?>
            <!-- System-wide Geofence Breach Alerts -->
            <div style="margin-bottom:2rem;">
                <div class="card" style="border-left:5px solid #dc3545;border-radius:12px;overflow:hidden;">
                    <div class="card-header" style="background:#dc3545;color:white;border:none;padding:1rem 1.25rem;">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>System Geofence Breach Alerts (<?php echo $systemBreachCount; ?> unread)</h5>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php foreach ($systemBreaches as $alert): ?>
                            <div style="padding:1rem 1.25rem;border-bottom:1px solid #f0f0f0;display:flex;justify-content:space-between;align-items:start;">
                                <div>
                                    <div style="font-weight:600;color:#dc3545;">
                                        <i class="fas fa-map-marker-times me-1"></i><?php echo htmlspecialchars($alert['title']); ?>
                                        <?php if (!$alert['is_read']): ?>
                                            <span class="badge bg-danger ms-1">NEW</span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="color:#6c757d;font-size:0.9rem;margin-top:0.25rem;"><?php echo htmlspecialchars($alert['message']); ?></div>
                                    <div style="color:#adb5bd;font-size:0.8rem;margin-top:0.25rem;">
                                        <i class="fas fa-clock me-1"></i><?php echo date('M j, Y g:i A', strtotime($alert['created_at'])); ?>
                                        <?php if ($alert['supervisor_name']): ?>
                                            | <i class="fas fa-user-tie me-1"></i>Supervisor: <?php echo htmlspecialchars($alert['supervisor_name']); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

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
                                <div class="activity-icon primary">
                                    <i class="fas fa-file-alt"></i>
                    </div>
                                <div class="activity-content">
                                    <div class="activity-title">
                                        <?php echo htmlspecialchars($activity['student_name']); ?> submitted a log entry
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
                                <div class="activity-time">Check back later for updates</div>
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
                    
                    <a href="manage.php" class="action-btn">
                        <i class="fas fa-users"></i>
                        Manage Users
                    </a>
                    
                    <a href="reports.php" class="action-btn">
                        <i class="fas fa-chart-bar"></i>
                        Generate Reports
                    </a>
                    
                    <a href="gps-monitoring.php" class="action-btn">
                        <i class="fas fa-map-marker-alt"></i>
                        GPS Monitoring
                    </a>
                    
                    <a href="communications.php" class="action-btn">
                        <i class="fas fa-comments"></i>
                        Communications
                    </a>
                    
                    <a href="settings.php" class="action-btn">
                        <i class="fas fa-cog"></i>
                        System Settings
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/admin-scripts.js"></script>
</body>
</html>