<?php
session_start();
require_once '../backend/config/db.php';
require_once '../backend/config/session.php';
require_once '../backend/models/Notification.php';

// Check if user is logged in and has admin/coordinator role
if (!isLoggedIn() || (!hasRole('admin') && !hasRole('coordinator'))) {
    header('Location: login.php');
    exit();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => ''];
    
    try {
        switch ($action) {
            case 'mark_as_read':
                $notificationId = $_POST['notification_id'] ?? 0;
                if (Notification::markAsRead($pdo, $notificationId)) {
                    $response['success'] = true;
                    $response['message'] = 'Notification marked as read';
                } else {
                    $response['message'] = 'Failed to mark notification as read';
                }
                break;
                
            case 'mark_all_read':
                $userId = $_SESSION['user_id'] ?? 0;
                if (Notification::markAllAsRead($pdo, $userId)) {
                    $response['success'] = true;
                    $response['message'] = 'All notifications marked as read';
                } else {
                    $response['message'] = 'Failed to mark all notifications as read';
                }
                break;
                
            case 'delete_notification':
                $notificationId = $_POST['notification_id'] ?? 0;
                $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
                if ($stmt->execute([$notificationId])) {
                    $response['success'] = true;
                    $response['message'] = 'Notification deleted';
                } else {
                    $response['message'] = 'Failed to delete notification';
                }
                break;
                
            case 'delete_all':
                $userId = $_SESSION['user_id'] ?? 0;
                $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
                if ($stmt->execute([$userId])) {
                    $response['success'] = true;
                    $response['message'] = 'All notifications deleted';
                } else {
                    $response['message'] = 'Failed to delete all notifications';
                }
                break;
                
            case 'create_notification':
                $data = [
                    'user_id' => $_POST['user_id'] ?? 0,
                    'title' => $_POST['title'] ?? '',
                    'message' => $_POST['message'] ?? '',
                    'type' => $_POST['type'] ?? 'info'
                ];
                
                if (Notification::create($pdo, $data)) {
                    $response['success'] = true;
                    $response['message'] = 'Notification created successfully';
                } else {
                    $response['message'] = 'Failed to create notification';
                }
                break;
                
            default:
                $response['message'] = 'Invalid action';
        }
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit();
}

// Get all notifications for admin
try {
    $stmt = $pdo->query("
        SELECT n.*, u.username as user_name, u.role as user_role
        FROM notifications n
        LEFT JOIN users u ON n.user_id = u.id
        ORDER BY n.created_at DESC
    ");
    $notifications = $stmt->fetchAll();
    
    // Get unread count
    $unreadCount = Notification::getUnreadCount($pdo, $_SESSION['user_id'] ?? 0);
    
    // Get all users for creating notifications (excluding coordinators)
    $stmt = $pdo->query("SELECT id, username, role FROM users WHERE role NOT IN ('coordinator', 'admin') ORDER BY username");
    $users = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $notifications = [];
    $unreadCount = 0;
    $users = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - SIWES Logbook</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/admin-styles.css">
    
    <style>
        .notification-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary-color);
        }
        
        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .notification-card.unread {
            background: #f8f9ff;
            border-left-color: var(--warning-color);
        }
        
        .notification-card.success {
            border-left-color: var(--success-color);
        }
        
        .notification-card.error {
            border-left-color: var(--danger-color);
        }
        
        .notification-card.warning {
            border-left-color: var(--warning-color);
        }
        
        .notification-card.info {
            border-left-color: var(--info-color);
        }
        
        .notification-header {
            display: flex;
            justify-content: between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.2rem;
            color: white;
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .notification-message {
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        
        .notification-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.875rem;
            color: #6c757d;
        }
        
        .notification-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
        }
        
        .create-notification-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.875rem;
        }
        
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .filter-tab {
            padding: 0.5rem 1rem;
            border: 2px solid #e9ecef;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .filter-tab.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .filter-tab:hover {
            border-color: var(--primary-color);
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
        $pageTitle = 'Notifications';
        include 'includes/header.php'; 
        ?>
        
        <!-- Page Content -->
        <div class="page-content">
            <!-- Error Display -->
            <?php if (isset($error)): ?>
                <div class="alert alert-danger fade-in-up">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Statistics -->
            <div class="stats-row">
                <div class="stat-card fade-in-up">
                    <div class="stat-number"><?php echo count($notifications); ?></div>
                    <div class="stat-label">Total Notifications</div>
                </div>
                <div class="stat-card fade-in-up">
                    <div class="stat-number"><?php echo $unreadCount; ?></div>
                    <div class="stat-label">Unread</div>
                </div>
                <div class="stat-card fade-in-up">
                    <div class="stat-number"><?php echo count($notifications) - $unreadCount; ?></div>
                    <div class="stat-label">Read</div>
                </div>
            </div>
            
            <!-- Create Notification -->
            <div class="create-notification-card fade-in-up">
                <h3 class="mb-3">
                    <i class="fas fa-plus-circle me-2"></i>
                    Create New Notification
                </h3>
                
                <form id="createNotificationForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="user_id" class="form-label">Send To</label>
                                <select class="form-control" id="user_id" name="user_id" required>
                                    <option value="">Select User</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>">
                                            <?php echo htmlspecialchars($user['full_name'] . ' (' . ucfirst($user['role']) . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="type" class="form-label">Type</label>
                                <select class="form-control" id="type" name="type" required>
                                    <option value="info">Info</option>
                                    <option value="success">Success</option>
                                    <option value="warning">Warning</option>
                                    <option value="error">Error</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="3" required></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>
                        Send Notification
                    </button>
                </form>
            </div>
            
            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <div class="filter-tab active" data-filter="all">All</div>
                <div class="filter-tab" data-filter="unread">Unread</div>
                <div class="filter-tab" data-filter="info">Info</div>
                <div class="filter-tab" data-filter="success">Success</div>
                <div class="filter-tab" data-filter="warning">Warning</div>
                <div class="filter-tab" data-filter="error">Error</div>
            </div>
            
            <!-- Bulk Actions -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <button class="btn btn-outline-primary btn-sm me-2" onclick="markAllAsRead()">
                        <i class="fas fa-check-double me-1"></i>
                        Mark All as Read
                    </button>
                    <button class="btn btn-outline-danger btn-sm" onclick="deleteAllNotifications()">
                        <i class="fas fa-trash me-1"></i>
                        Delete All
                    </button>
                </div>
                <div class="text-muted">
                    Showing <?php echo count($notifications); ?> notifications
                </div>
            </div>
            
            <!-- Notifications List -->
            <div id="notificationsList">
                <?php if (empty($notifications)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No notifications found</h5>
                        <p class="text-muted">Notifications will appear here when they are created.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-card <?php echo $notification['is_read'] ? '' : 'unread'; ?> <?php echo $notification['type']; ?>" 
                             data-type="<?php echo $notification['type']; ?>" 
                             data-read="<?php echo $notification['is_read'] ? 'true' : 'false'; ?>">
                            <div class="notification-header">
                                <div class="notification-icon <?php echo $notification['type']; ?>">
                                    <i class="<?php echo Notification::getTypeIcon($notification['type']); ?>"></i>
                                </div>
                                <div class="notification-content">
                                    <div class="notification-title">
                                        <?php echo htmlspecialchars($notification['title']); ?>
                                        <?php if (!$notification['is_read']): ?>
                                            <span class="badge bg-warning ms-2">New</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="notification-message">
                                        <?php echo htmlspecialchars($notification['message']); ?>
                                    </div>
                                    <div class="notification-meta">
                                        <div>
                                            <i class="fas fa-user me-1"></i>
                                            <?php echo htmlspecialchars($notification['user_name'] ?? 'Unknown User'); ?>
                                            <span class="ms-2">
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                                            </span>
                                        </div>
                                        <div class="notification-actions">
                                            <?php if (!$notification['is_read']): ?>
                                                <button class="btn btn-outline-success btn-sm" onclick="markAsRead(<?php echo $notification['id']; ?>)">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-outline-danger btn-sm" onclick="deleteNotification(<?php echo $notification['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/admin-scripts.js"></script>
    <script>
        // Filter functionality
        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Update active tab
                document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Filter notifications
                const filter = this.dataset.filter;
                const notifications = document.querySelectorAll('.notification-card');
                
                notifications.forEach(notification => {
                    if (filter === 'all') {
                        notification.style.display = 'block';
                    } else if (filter === 'unread') {
                        notification.style.display = notification.dataset.read === 'false' ? 'block' : 'none';
                    } else {
                        notification.style.display = notification.dataset.type === filter ? 'block' : 'none';
                    }
                });
            });
        });
        
        // Create notification form
        document.getElementById('createNotificationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'create_notification');
            
            fetch('notifications.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    this.reset();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                showToast('An error occurred', 'error');
            });
        });
        
        // Mark as read
        function markAsRead(notificationId) {
            const formData = new FormData();
            formData.append('action', 'mark_as_read');
            formData.append('notification_id', notificationId);
            
            fetch('notifications.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message, 'error');
                }
            });
        }
        
        // Mark all as read
        function markAllAsRead() {
            if (confirm('Mark all notifications as read?')) {
                const formData = new FormData();
                formData.append('action', 'mark_all_read');
                
                fetch('notifications.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast(data.message, 'error');
                    }
                });
            }
        }
        
        // Delete notification
        function deleteNotification(notificationId) {
            if (confirm('Delete this notification?')) {
                const formData = new FormData();
                formData.append('action', 'delete_notification');
                formData.append('notification_id', notificationId);
                
                fetch('notifications.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast(data.message, 'error');
                    }
                });
            }
        }
        
        // Delete all notifications
        function deleteAllNotifications() {
            if (confirm('Delete all notifications? This action cannot be undone.')) {
                const formData = new FormData();
                formData.append('action', 'delete_all');
                
                fetch('notifications.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast(data.message, 'error');
                    }
                });
            }
        }
    </script>
</body>
</html>
