<?php
session_start();
require_once '../backend/config/db.php';
require_once '../backend/config/session.php';

// Check if user is logged in and has student role
if (!isLoggedIn() || !hasRole('student')) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';

try {
    // Get statistics
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM log_entries WHERE student_id = ?");
    $stmt->execute([$user_id]);
    $totalEntries = $stmt->fetch()['total'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM log_entries WHERE student_id = ? AND status = 'approved'");
    $stmt->execute([$user_id]);
    $approvedEntries = $stmt->fetch()['count'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM log_entries WHERE student_id = ? AND status = 'pending'");
    $stmt->execute([$user_id]);
    $pendingEntries = $stmt->fetch()['count'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM log_entries WHERE student_id = ? AND status = 'rejected'");
    $stmt->execute([$user_id]);
    $rejectedEntries = $stmt->fetch()['count'];
    
    // Get monthly statistics for the last 6 months
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
        FROM log_entries 
        WHERE student_id = ? 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
    ");
    $stmt->execute([$user_id]);
    $monthlyStats = $stmt->fetchAll();
    
    // Get recent entries for detailed report
    $stmt = $pdo->prepare("
        SELECT * FROM log_entries 
        WHERE student_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $recentEntries = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $totalEntries = 0;
    $approvedEntries = 0;
    $pendingEntries = 0;
    $rejectedEntries = 0;
    $monthlyStats = [];
    $recentEntries = [];
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
    <link rel="stylesheet" href="assets/student-styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        .report-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        
        .report-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .export-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }
        
        .export-btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .export-btn:hover {
            transform: translateY(-1px);
            text-decoration: none;
        }
        
        .btn-export-pdf {
            background: #dc3545;
            color: white;
        }
        
        .btn-export-pdf:hover {
            background: #c82333;
            color: white;
        }
        
        .btn-export-excel {
            background: #28a745;
            color: white;
        }
        
        .btn-export-excel:hover {
            background: #218838;
            color: white;
        }
        
        .btn-print {
            background: #17a2b8;
            color: white;
        }
        
        .btn-print:hover {
            background: #138496;
            color: white;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin: 1rem 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .stat-header {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.75rem;
        }
        
        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-right: 0.75rem;
        }
        
        .stat-icon.primary {
            background: var(--primary-color);
            color: white;
        }
        
        .stat-icon.success {
            background: var(--success-color);
            color: white;
        }
        
        .stat-icon.warning {
            background: var(--warning-color);
            color: white;
        }
        
        .stat-icon.danger {
            background: var(--danger-color);
            color: white;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .stat-label {
            font-size: 0.875rem;
            opacity: 0.9;
            color: var(--text-color);
        }
        
        .stats-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .stats-table th {
            background: var(--primary-color);
            color: white;
            font-weight: 600;
            padding: 1rem;
        }
        
        .stats-table td {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .stats-table tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-approved {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }
        
        .status-pending {
            background: rgba(255, 193, 7, 0.1);
            color: var(--warning-color);
        }
        
        .status-rejected {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
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
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Student'); ?></div>
                    <div class="user-role">Student</div>
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
            <?php if ($error): ?>
                <div class="alert alert-danger fade-in-up">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Export Buttons -->
            <div class="report-card fade-in-up">
                <h3 class="report-title">
                    <i class="fas fa-download"></i>
                    Export Reports
                </h3>
                <div class="export-buttons">
                    <a href="#" class="export-btn btn-export-pdf" onclick="exportToPDF()">
                        <i class="fas fa-file-pdf"></i>
                        Export to PDF
                    </a>
                    <a href="#" class="export-btn btn-export-excel" onclick="exportToExcel()">
                        <i class="fas fa-file-excel"></i>
                        Export to Excel
                    </a>
                    <a href="#" class="export-btn btn-print" onclick="printReport()">
                        <i class="fas fa-print"></i>
                        Print Report
                    </a>
                </div>
            </div>
            
            <!-- Statistics Overview -->
            <div class="stats-grid">
                <div class="stat-card fade-in-up">
                    <div class="stat-header">
                        <div class="stat-icon primary">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="stat-number"><?php echo $totalEntries; ?></div>
                    </div>
                    <div class="stat-label">Total Entries</div>
                </div>
                
                <div class="stat-card success fade-in-up">
                    <div class="stat-header">
                        <div class="stat-icon success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-number"><?php echo $approvedEntries; ?></div>
                    </div>
                    <div class="stat-label">Approved</div>
                </div>
                
                <div class="stat-card warning fade-in-up">
                    <div class="stat-header">
                        <div class="stat-icon warning">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-number"><?php echo $pendingEntries; ?></div>
                    </div>
                    <div class="stat-label">Pending</div>
                </div>
                
                <div class="stat-card danger fade-in-up">
                    <div class="stat-header">
                        <div class="stat-icon danger">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-number"><?php echo $rejectedEntries; ?></div>
                    </div>
                    <div class="stat-label">Rejected</div>
                </div>
            </div>
            
            <!-- Charts Section -->
            <div class="row">
                <div class="col-lg-6">
                    <div class="report-card fade-in-up">
                        <h3 class="report-title">
                            <i class="fas fa-chart-pie"></i>
                            Status Distribution
                        </h3>
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="report-card fade-in-up">
                        <h3 class="report-title">
                            <i class="fas fa-chart-line"></i>
                            Monthly Activity
                        </h3>
                        <div class="chart-container">
                            <canvas id="monthlyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Entries Table -->
            <div class="report-card fade-in-up">
                <h3 class="report-title">
                    <i class="fas fa-table"></i>
                    Recent Entries
                </h3>
                
                <?php if (!empty($recentEntries)): ?>
                    <div class="table-responsive">
                        <table class="table stats-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Activity</th>
                                    <th>Status</th>
                                    <th>Location</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentEntries as $entry): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y', strtotime($entry['created_at'])); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars(substr($entry['activity'], 0, 50)) . (strlen($entry['activity']) > 50 ? '...' : ''); ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $entry['status']; ?>">
                                                <?php echo ucfirst($entry['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (isset($entry['latitude']) && isset($entry['longitude']) && $entry['latitude'] && $entry['longitude']): ?>
                                                <i class="fas fa-map-marker-alt text-muted"></i>
                                                <?php 
                                                    $location_text = "Lat: {$entry['latitude']}, Long: {$entry['longitude']}";
                                                    echo htmlspecialchars(substr($location_text, 0, 30)) . (strlen($location_text) > 30 ? '...' : '');
                                                ?>
                                            <?php else: ?>
                                                <span class="text-muted">No location</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewEntry(<?php echo $entry['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5>No Entries Found</h5>
                        <p class="text-muted">Start creating log entries to see your reports here.</p>
                        <a href="log-entry.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>
                            Create First Entry
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/student-scripts.js"></script>
    <script>
        // Chart data
        const statusData = {
            labels: ['Approved', 'Pending', 'Rejected'],
            datasets: [{
                data: [<?php echo $approvedEntries; ?>, <?php echo $pendingEntries; ?>, <?php echo $rejectedEntries; ?>],
                backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                borderWidth: 0
            }]
        };
        
        const monthlyData = {
            labels: <?php echo json_encode(array_column($monthlyStats, 'month')); ?>,
            datasets: [{
                label: 'Total Entries',
                data: <?php echo json_encode(array_column($monthlyStats, 'count')); ?>,
                borderColor: '#1a4d2e',
                backgroundColor: 'rgba(26, 77, 46, 0.1)',
                tension: 0.4
            }]
        };
        
        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            // Status Chart
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            new Chart(statusCtx, {
                type: 'doughnut',
                data: statusData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
            
            // Monthly Chart
            const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
            new Chart(monthlyCtx, {
                type: 'line',
                data: monthlyData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
        
        // Export functions
        function exportToPDF() {
            showToast('PDF export feature coming soon!', 'info');
        }
        
        function exportToExcel() {
            showToast('Excel export feature coming soon!', 'info');
        }
        
        function printReport() {
            showToast('Print feature coming soon!', 'info');
        }
        
        function viewEntry(entryId) {
            showToast('View entry feature coming soon!', 'info');
        }
    </script>
</body>
</html>