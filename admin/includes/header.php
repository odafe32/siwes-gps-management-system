<?php
// Get notification count for current user
$unreadNotificationCount = 0;
if (isset($_SESSION['user_id']) && isset($pdo)) {
    try {
        $notificationPath = __DIR__ . '/../../backend/models/Notification.php';
        if (file_exists($notificationPath)) {
            require_once $notificationPath;
            $unreadNotificationCount = Notification::getUnreadCount($pdo, $_SESSION['user_id']);
        }
    } catch (Exception $e) {
        // Handle error silently
        $unreadNotificationCount = 0;
        error_log("Notification error: " . $e->getMessage());
    }
}
?>

<header class="header">
    <div class="header-left">
        <button class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="page-title"><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
    </div>
    
    <div class="header-right">
        <!-- Notifications -->
        <div class="notification-dropdown">
            <button class="notification-btn" id="notificationBtn" onclick="toggleNotifications()">
                <i class="fas fa-bell"></i>
                <?php if ($unreadNotificationCount > 0): ?>
                    <span class="notification-badge"><?php echo $unreadNotificationCount; ?></span>
                <?php endif; ?>
            </button>
            
            <div class="notification-dropdown-content" id="notificationDropdown">
                <div class="notification-header">
                    <h6>Notifications</h6>
                    <a href="notifications.php" class="view-all-link">View All</a>
                </div>
                <div class="notification-list" id="notificationList">
                    <div class="notification-loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        Loading notifications...
                    </div>
                </div>
            </div>
        </div>
        
        <!-- User Menu -->
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
    </div>
</header>

<style>
.header-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.notification-dropdown {
    position: relative;
}

.notification-btn {
    position: relative;
    background: none;
    border: none;
    font-size: 1.2rem;
    color: #6c757d;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.notification-btn:hover {
    background: #f8f9fa;
    color: var(--primary-color);
}

.notification-badge {
    position: absolute;
    top: 0;
    right: 0;
    background: var(--danger-color);
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}

.notification-dropdown-content {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    width: 350px;
    max-height: 400px;
    overflow: hidden;
    z-index: 1000;
    display: none;
    margin-top: 0.5rem;
}

.notification-dropdown-content.show {
    display: block;
}

.notification-header {
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notification-header h6 {
    margin: 0;
    color: var(--primary-color);
    font-weight: 600;
}

.view-all-link {
    color: var(--primary-color);
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
}

.view-all-link:hover {
    text-decoration: underline;
}

.notification-list {
    max-height: 300px;
    overflow-y: auto;
}

.notification-item {
    padding: 1rem;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.notification-item:hover {
    background: #f8f9fa;
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-item.unread {
    background: #f8f9ff;
    border-left: 3px solid var(--warning-color);
}

.notification-item-title {
    font-weight: 600;
    color: var(--primary-color);
    margin-bottom: 0.25rem;
    font-size: 0.875rem;
}

.notification-item-message {
    color: #6c757d;
    font-size: 0.8rem;
    margin-bottom: 0.5rem;
    line-height: 1.4;
}

.notification-item-time {
    color: #adb5bd;
    font-size: 0.75rem;
}

.notification-loading {
    padding: 2rem;
    text-align: center;
    color: #6c757d;
}

.notification-empty {
    padding: 2rem;
    text-align: center;
    color: #6c757d;
}

.notification-empty i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

@media (max-width: 768px) {
    .notification-dropdown-content {
        width: 300px;
        right: -50px;
    }
}
</style>

<script>
// Notification functionality
function toggleNotifications() {
    const dropdown = document.getElementById('notificationDropdown');
    dropdown.classList.toggle('show');
    
    if (dropdown.classList.contains('show')) {
        loadRecentNotifications();
    }
}

function loadRecentNotifications() {
    const notificationList = document.getElementById('notificationList');
    
    fetch('api/notifications.php?action=get_recent&limit=5')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayNotifications(data.notifications);
            } else {
                notificationList.innerHTML = '<div class="notification-empty"><i class="fas fa-bell-slash"></i><div>No notifications</div></div>';
            }
        })
        .catch(error => {
            notificationList.innerHTML = '<div class="notification-empty"><i class="fas fa-exclamation-triangle"></i><div>Error loading notifications</div></div>';
        });
}

function displayNotifications(notifications) {
    const notificationList = document.getElementById('notificationList');
    
    if (notifications.length === 0) {
        notificationList.innerHTML = '<div class="notification-empty"><i class="fas fa-bell-slash"></i><div>No notifications</div></div>';
        return;
    }
    
    let html = '';
    notifications.forEach(notification => {
        const timeAgo = getTimeAgo(notification.created_at);
        const unreadClass = notification.is_read ? '' : 'unread';
        
        html += `
            <div class="notification-item ${unreadClass}" onclick="markAsRead(${notification.id})">
                <div class="notification-item-title">${notification.title}</div>
                <div class="notification-item-message">${notification.message}</div>
                <div class="notification-item-time">${timeAgo}</div>
            </div>
        `;
    });
    
    notificationList.innerHTML = html;
}

function getTimeAgo(dateString) {
    const now = new Date();
    const date = new Date(dateString);
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) return 'Just now';
    if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + 'm ago';
    if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + 'h ago';
    return Math.floor(diffInSeconds / 86400) + 'd ago';
}

function markAsRead(notificationId) {
    fetch('api/notifications.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=mark_as_read&notification_id=${notificationId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI
            loadRecentNotifications();
            updateNotificationBadge();
        }
    });
}

function updateNotificationBadge() {
    fetch('api/notifications.php?action=get_unread_count')
        .then(response => response.json())
        .then(data => {
            const badge = document.querySelector('.notification-badge');
            if (data.count > 0) {
                if (badge) {
                    badge.textContent = data.count;
                } else {
                    const btn = document.getElementById('notificationBtn');
                    btn.innerHTML = '<i class="fas fa-bell"></i><span class="notification-badge">' + data.count + '</span>';
                }
            } else {
                const btn = document.getElementById('notificationBtn');
                btn.innerHTML = '<i class="fas fa-bell"></i>';
            }
        });
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('notificationDropdown');
    const btn = document.getElementById('notificationBtn');
    
    if (!dropdown.contains(event.target) && !btn.contains(event.target)) {
        dropdown.classList.remove('show');
    }
});

// Update notification count every 30 seconds
setInterval(updateNotificationBadge, 30000);
</script>
