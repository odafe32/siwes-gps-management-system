<?php
session_start();
require_once '../backend/config/db.php';
require_once '../backend/config/session.php';

if (!isLoggedIn() || (!hasRole('admin') && !hasRole('coordinator'))) {
    header('Location: login.php?error=Access denied');
    exit();
}

// Fetch all log entries with student information
$stmt = $pdo->prepare("
    SELECT le.*, u.full_name as student_name, u.matric_number, u.department, u.workplace_name FROM log_entries le JOIN users u ON le.student_id = u.id ORDER BY le.date DESC, le.created_at DESC
    LIMIT 100
");
$stmt->execute();
$logEntries = $stmt->fetchAll();

// Handle messages
$message = '';
$messageType = '';
if (isset($_GET['success'])) {
    $message = $_GET['success'];
    $messageType = 'success';
} else if (isset($_GET['error'])) {
    $message = $_GET['error'];
    $messageType = 'danger';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logbook Management - SIWES Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/admin-styles.css">
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
                <h1 class="page-title">Logbook Management</h1>
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
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="page-header">
                <h2>Manage Student Logbooks</h2>
                <div class="btn-group">
                    <button class="btn btn-primary" onclick="exportLogbooks()">
                        <i class="fas fa-download"></i>
                        Export Logbooks
                    </button>
                    <button class="btn btn-outline-primary" onclick="refreshLogbooks()">
                        <i class="fas fa-sync-alt"></i>
                        Refresh
                    </button>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Student</th>
                                    <th>Matric Number</th>
                                    <th>Department</th>
                                    <th>Date</th>
                                    <th>Created</th>
                                    <th>Activity</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logEntries as $entry): ?>
                                <tr>
                                    <td><?= htmlspecialchars($entry['id']) ?></td>
                                    <td><?= htmlspecialchars($entry['student_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($entry['matric_number'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($entry['department'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($entry['date']) ?></td>
                                    <td><?= htmlspecialchars($entry['created_at']) ?></td>
                                    <td><?= htmlspecialchars(substr($entry['activity'], 0, 50)) ?>...</td>
                                    <td>
                                        <?php if ($entry['latitude'] && $entry['longitude']): ?>
                                            <span class="badge bg-success">GPS</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">No GPS</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($entry['status'] === 'approved'): ?>
                                            <span class="badge bg-success">Approved</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-primary" onclick="viewLogEntry(<?= $entry['id'] ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-success" onclick="approveLogEntry(<?= $entry['id'] ?>)">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/admin-scripts.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        });
        
        // View log entry
        function viewLogEntry(id) {
            alert('View log entry with ID: ' + id + ' (Functionality to be implemented)');
        }
        
        // Approve log entry
        function approveLogEntry(id) {
            if (confirm('Are you sure you want to approve this log entry?')) {
                alert('Approve functionality to be implemented');
            }
        }
        
        // Export logbooks
        function exportLogbooks() {
            alert('Export functionality to be implemented');
        }
        
        // Refresh logbooks
        function refreshLogbooks() {
            window.location.reload();
        }
    </script>
</body>
</html>








