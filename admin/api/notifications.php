<?php
session_start();
require_once '../../backend/config/db.php';
require_once '../../backend/config/session.php';
require_once '../../backend/models/Notification.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$response = ['success' => false, 'message' => ''];

try {
    switch ($action) {
        case 'get_recent':
            $limit = $_GET['limit'] ?? 5;
            $notifications = Notification::getByUserId($pdo, $_SESSION['user_id'], $limit);
            $response['success'] = true;
            $response['notifications'] = $notifications;
            break;
            
        case 'get_unread_count':
            $count = Notification::getUnreadCount($pdo, $_SESSION['user_id']);
            $response['success'] = true;
            $response['count'] = $count;
            break;
            
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
            if (Notification::markAllAsRead($pdo, $_SESSION['user_id'])) {
                $response['success'] = true;
                $response['message'] = 'All notifications marked as read';
            } else {
                $response['message'] = 'Failed to mark all notifications as read';
            }
            break;
            
        default:
            $response['message'] = 'Invalid action';
    }
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
?>
