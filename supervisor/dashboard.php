<?php
session_start();
require_once '../backend/config/db.php';
require_once '../backend/config/session.php';

// Check if user is logged in and has supervisor role
if (!isLoggedIn() || !hasRole('supervisor')) {
    header('Location: login.php');
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'] ?? 'Supervisor';

// Get geofence breach alerts for this supervisor
$stmt = $pdo->prepare("
    SELECT n.*, u.full_name as student_name
    FROM notifications n
    LEFT JOIN users u ON u.id = ?
    WHERE n.user_id = ? AND n.type = 'geofence_breach'
    ORDER BY n.created_at DESC LIMIT 10
");
$stmt->execute([$user_id, $user_id]);
$breachAlerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
$unreadBreaches = array_filter($breachAlerts, function($a) { return !$a['is_read']; });
$breachCount = count($unreadBreaches);

// Get dashboard stats directly from DB (no AJAX dependency)
$stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM users WHERE supervisor_id = ? AND role = 'student'");
$stmt->execute([$user_id]);
$statStudentCount = $stmt->fetch()['cnt'];

$stmt = $pdo->prepare("
    SELECT COUNT(*) as cnt FROM log_entries le
    JOIN users u ON u.id = le.student_id
    WHERE u.supervisor_id = ? AND le.status = 'pending'
");
$stmt->execute([$user_id]);
$statPendingCount = $stmt->fetch()['cnt'];

$stmt = $pdo->prepare("
    SELECT COUNT(*) as cnt FROM log_entries le
    JOIN users u ON u.id = le.student_id
    WHERE u.supervisor_id = ? AND le.status = 'approved'
");
$stmt->execute([$user_id]);
$statApprovedCount = $stmt->fetch()['cnt'];

$stmt = $pdo->prepare("
    SELECT COUNT(*) as cnt FROM log_entries le
    JOIN users u ON u.id = le.student_id
    WHERE u.supervisor_id = ? AND le.status = 'rejected'
");
$stmt->execute([$user_id]);
$statRejectedCount = $stmt->fetch()['cnt'];

// Get pending logs for display
$stmt = $pdo->prepare("
    SELECT le.*, u.full_name as student_name
    FROM log_entries le
    JOIN users u ON u.id = le.student_id
    WHERE u.supervisor_id = ? AND le.status = 'pending'
    ORDER BY le.created_at DESC LIMIT 10
");
$stmt->execute([$user_id]);
$pendingLogsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisor Dashboard - SIWES Logbook</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/supervisor-styles.css">
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
                    <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
                    <div class="user-role">Supervisor</div>
                </div>
                <button class="logout-btn" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </button>
            </div>
        </header>

        <!-- Page Content -->
        <div class="page-content">
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="mb-3">Welcome, <span id="welcomeName"><?php echo htmlspecialchars($user_name); ?></span>!</h2>
                    <p class="text-muted">Here's an overview of your supervision activities</p>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h5 class="card-title mb-0">Pending Reviews</h5>
                                <div class="stats-icon bg-warning">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                            <h3 class="stats-number" id="pendingReviews"><?php echo $statPendingCount; ?></h3>
                            <p class="text-muted">Logs awaiting your review</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h5 class="card-title mb-0">Approved Logs</h5>
                                <div class="stats-icon bg-success">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                            <h3 class="stats-number" id="approvedLogs"><?php echo $statApprovedCount; ?></h3>
                            <p class="text-muted">Total approved log entries</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h5 class="card-title mb-0">My Students</h5>
                                <div class="stats-icon bg-info">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                            <h3 class="stats-number" id="totalStudents"><?php echo $statStudentCount; ?></h3>
                            <p class="text-muted">Students under your supervision</p>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($breachCount > 0): ?>
            <!-- Geofence Breach Alerts -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card" style="border-left:5px solid #dc3545;border-radius:12px;">
                        <div class="card-header" style="background:#dc3545;color:white;border-radius:7px 7px 0 0;">
                            <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Geofence Breach Alerts (<?php echo $breachCount; ?> unread)</h5>
                        </div>
                        <div class="card-body" style="padding:0;">
                            <?php foreach ($breachAlerts as $alert): ?>
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
                                        </div>
                                    </div>
                                    <?php if (!$alert['is_read']): ?>
                                        <a href="alerts.php?mark_read=<?php echo $alert['id']; ?>" class="btn btn-sm btn-outline-secondary">Mark as read</a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="card-footer" style="background:#f8f9fa;">
                            <a href="alerts.php" class="btn btn-sm btn-outline-danger"><i class="fas fa-bell me-1"></i>View All Alerts</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Pending Log Entries</h5>
                            <button class="btn btn-sm btn-outline-primary" onclick="loadPendingLogs()">
                                <i class="fas fa-sync-alt me-1"></i>Refresh
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="pendingLogsContainer">
                                <?php if (count($pendingLogsList) > 0): ?>
                                    <?php foreach ($pendingLogsList as $log): ?>
                                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                            <div>
                                                <strong><?php echo htmlspecialchars($log['student_name']); ?></strong>
                                                <div style="font-size:0.85rem;color:#6c757d;">
                                                    <?php echo htmlspecialchars(substr($log['activity_description'] ?? $log['description'] ?? '', 0, 80)); ?>
                                                </div>
                                                <div style="font-size:0.8rem;color:#adb5bd;">
                                                    <?php echo date('M j, Y', strtotime($log['date'] ?? $log['created_at'])); ?>
                                                </div>
                                            </div>
                                            <a href="review.php" class="btn btn-sm btn-outline-primary">Review</a>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted text-center my-3">
                                        <i class="fas fa-check-circle text-success me-1"></i>
                                        No pending logs to review.
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="review.php" class="btn btn-primary">
                                    <i class="fas fa-clipboard-check me-2"></i>Review All Logs
                                </a>
                                <a href="students.php" class="btn btn-outline-primary">
                                    <i class="fas fa-users me-2"></i>View My Students
                                </a>
                                <button class="btn btn-outline-secondary" onclick="loadPendingLogs()">
                                    <i class="fas fa-sync-alt me-2"></i>Refresh Data
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="mb-0">Activity Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-2 d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-clock text-warning me-2"></i>Pending:</span>
                                <span id="pendingCount" class="badge bg-warning"><?php echo $statPendingCount; ?></span>
                            </div>
                            <div class="mb-2 d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-check-circle text-success me-2"></i>Approved:</span>
                                <span id="approvedCount" class="badge bg-success"><?php echo $statApprovedCount; ?></span>
                            </div>
                            <div class="mb-2 d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-times-circle text-danger me-2"></i>Rejected:</span>
                                <span id="rejectedCount" class="badge bg-danger"><?php echo $statRejectedCount; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/supervisor-scripts.js"></script>
    <script>
        // Dashboard specific functions
        function loadDashboardData() {
            // Show loading indicators
            $('#pendingReviews, #pendingCount, #approvedLogs, #approvedCount, #rejectedCount, #totalStudents').html(
                '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>'
            );
            
            // Simplified AJAX for dashboard data
            $.ajax({
                url: '../backend/api/supervisor.php',
                type: 'POST',
                data: JSON.stringify({
                    action: 'dashboard'
                }),
                contentType: 'application/json',
                timeout: 10000, // 10 second timeout
                success: function(response) {
                    try {
                        // Parse response if it's a string
                        const data = typeof response === 'string' ? JSON.parse(response) : response;
                        
                        if (data.success) {
                            // Update stats
                            $('#pendingReviews').text(data.pending_logs.length || 0);
                            $('#pendingCount').text(data.pending_logs.length || 0);
                            $('#approvedLogs').text(data.approved_count || 0);
                            $('#approvedCount').text(data.approved_count || 0);
                            $('#rejectedCount').text(data.rejected_count || 0);
                            $('#totalStudents').text(data.student_count || 0);
                            
                            // Load pending logs
                            loadPendingLogs();
                        } else {
                            throw new Error(data.message || 'Unknown error');
                        }
                    } catch (e) {
                        showToast('Failed to process dashboard data: ' + e.message, 'error');
                        // Reset counters to show error state
                        $('#pendingReviews, #pendingCount, #approvedLogs, #approvedCount, #rejectedCount, #totalStudents').text('-');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading dashboard data:', error);
                    let errorMessage = 'Network error';
                    
                    if (status === 'timeout') {
                        errorMessage = 'Request timed out. Server might be busy.';
                    } else if (xhr.status === 404) {
                        errorMessage = 'API endpoint not found.';
                    } else if (xhr.status >= 500) {
                        errorMessage = 'Server error. Please try again later.';
                    }
                    
                    showToast(errorMessage, 'error');
                    // Reset counters to show error state
                    $('#pendingReviews, #pendingCount, #approvedLogs, #approvedCount, #rejectedCount, #totalStudents').text('-');
                }
            });
        }

        function loadPendingLogs() {
            // Check if student_id is in URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            const studentId = urlParams.get('student_id');
            
            // Update page title if filtering by student
            if (studentId) {
                $('.page-title').text('Student Logs');
                $('.card-header h5').text('Log Entries');
            }
            
            // Show loading indicator
            $('#pendingLogsContainer').html(`
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-2">Loading log entries...</p>
                </div>
            `);
            
            // Simplified AJAX request with better error handling
            $.ajax({
                url: '../backend/api/supervisor.php',
                type: 'POST',
                data: JSON.stringify({
                    action: 'get_pending_logs',
                    student_id: studentId || null
                }),
                contentType: 'application/json',
                timeout: 10000, // 10 second timeout
                success: function(response) {
                    try {
                        // Parse response if it's a string
                        const data = typeof response === 'string' ? JSON.parse(response) : response;
                        
                        if (data.success) {
                            displayPendingLogs(data.logs || []);
                        } else {
                            throw new Error(data.message || 'Unknown error');
                        }
                    } catch (e) {
                        showToast('Failed to process response: ' + e.message, 'error');
                        $('#pendingLogsContainer').html(`
                            <div class="text-center py-5">
                                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                                <p class="text-muted">Failed to load log entries</p>
                                <button class="btn btn-sm btn-outline-primary mt-2" onclick="loadPendingLogs()">Try Again</button>
                            </div>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading pending logs:', error);
                    let errorMessage = 'Network error';
                    
                    if (status === 'timeout') {
                        errorMessage = 'Request timed out. Server might be busy.';
                    } else if (xhr.status === 404) {
                        errorMessage = 'API endpoint not found.';
                    } else if (xhr.status >= 500) {
                        errorMessage = 'Server error. Please try again later.';
                    }
                    
                    showToast(errorMessage, 'error');
                    $('#pendingLogsContainer').html(`
                        <div class="text-center py-5">
                            <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                            <p class="text-muted">${errorMessage}</p>
                            <button class="btn btn-sm btn-outline-primary mt-2" onclick="loadPendingLogs()">Try Again</button>
                        </div>
                    `);
                }
            });
        }

        function displayPendingLogs(logs) {
            const $container = $('#pendingLogsContainer');
            
            if (!logs || logs.length === 0) {
                $container.html(`
                    <div class="text-center py-5">
                        <i class="fas fa-clipboard-check fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No pending log entries to review</p>
                        <p class="small text-muted">All student submissions have been reviewed</p>
                    </div>
                `);
                return;
            }
            
            // Clear container
            $container.empty();
            
            // Build HTML for all cards at once for better performance
            let cardsHtml = '';
            
            // Process each log entry
            $.each(logs, function(index, log) {
                const date = new Date(log.date).toLocaleDateString();
                const time = new Date(log.date).toLocaleTimeString();
                
                // Simplify activity text handling
                let activityText = 'No activity description';
                if (log.activity_description) {
                    activityText = log.activity_description.length > 100 ? 
                        log.activity_description.substring(0, 100) + '...' : 
                        log.activity_description;
                } else if (log.activity) {
                    activityText = log.activity.length > 100 ? 
                        log.activity.substring(0, 100) + '...' : 
                        log.activity;
                }
                
                // Add card HTML to the batch
                cardsHtml += `
                    <div class="card log-entry-card pending mb-3" style="display:none;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="card-title">${log.student_name || 'Student'}</h6>
                                    <p class="card-text text-muted">${log.matric_number || ''} ${log.department ? '• ' + log.department : ''}</p>
                                    <p class="card-text">${activityText}</p>
                                    <small class="text-muted">Submitted: ${date} at ${time}</small>
                                </div>
                                <div>
                                    <a href="review.php?log_id=${log.id}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i> Review
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            // Add all cards to container at once (more efficient)
            $container.html(cardsHtml);
            
            // Fade in cards sequentially for a smooth effect
            $('.log-entry-card').each(function(index) {
                $(this).delay(50 * index).fadeIn(300);
            });
        }
        }

        // Toast notification function with improved jQuery implementation
        function showToast(message, type = 'info') {
            // Create toast container if it doesn't exist
            if ($('#toast-container').length === 0) {
                $('<div>', {
                    id: 'toast-container',
                    class: 'position-fixed top-0 end-0 p-3',
                    css: { 'z-index': 1050 }
                }).appendTo('body');
            }
            
            // Create toast element with unique ID
            const toastId = 'toast-' + Date.now();
            
            // Create toast with jQuery object creation
            const $toast = $('<div>', {
                id: toastId,
                class: `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type} border-0`,
                role: 'alert',
                'aria-live': 'assertive',
                'aria-atomic': 'true'
            });
            
            // Create toast content
            const $toastContent = $('<div>', { class: 'd-flex' });
            const $toastBody = $('<div>', { class: 'toast-body', text: message });
            const $closeButton = $('<button>', {
                type: 'button',
                class: 'btn-close btn-close-white me-2 m-auto',
                'data-bs-dismiss': 'toast',
                'aria-label': 'Close'
            });
            
            // Assemble toast
            $toastContent.append($toastBody, $closeButton);
            $toast.append($toastContent);
            
            // Add toast to container
            $('#toast-container').append($toast);
            
            // Initialize and show toast
            const bsToast = new bootstrap.Toast($toast[0], { delay: 3000 });
            bsToast.show();
            
            // Remove toast after it's hidden
            $toast.on('hidden.bs.toast', function() {
                $(this).remove();
            });
        }
        
        // Initialize dashboard data on page load
        $(document).ready(function() {
            // Initialize tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();
            
            // Load dashboard data
            loadDashboardData();
            
            // Set up refresh button events
            $('.btn-refresh').on('click', function() {
                loadDashboardData();
            });
        });
    </script>
</body>
</html>