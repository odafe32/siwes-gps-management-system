<?php
class Notification {
    public static function create($pdo, $data) {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (
                user_id, title, message, type
            ) VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['user_id'],
            $data['title'],
            $data['message'],
            $data['type'] ?? 'info'
        ]);
    }
    
    public static function getByUserId($pdo, $userId, $limit = 10) {
        $stmt = $pdo->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }
    
    public static function getUnreadByUserId($pdo, $userId) {
        $stmt = $pdo->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? AND is_read = FALSE 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    public static function markAsRead($pdo, $notificationId) {
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = TRUE 
            WHERE id = ?
        ");
        return $stmt->execute([$notificationId]);
    }
    
    public static function markAllAsRead($pdo, $userId) {
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = TRUE 
            WHERE user_id = ?
        ");
        return $stmt->execute([$userId]);
    }
    
    public static function getUnreadCount($pdo, $userId) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM notifications 
            WHERE user_id = ? AND is_read = FALSE
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result['count'];
    }
    
    public static function deleteOld($pdo, $days = 30) {
        $stmt = $pdo->prepare("
            DELETE FROM notifications 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        return $stmt->execute([$days]);
    }
    
    // Helper methods for creating specific notifications
    public static function notifyLogApproved($pdo, $studentId, $logId) {
        $stmt = $pdo->prepare("
            SELECT u.username as student_name 
            FROM users u 
            WHERE u.id = ?
        ");
        $stmt->execute([$studentId]);
        $student = $stmt->fetch();
        
        return self::create($pdo, [
            'user_id' => $studentId,
            'title' => 'Log Entry Approved',
            'message' => "Your log entry has been approved by your supervisor.",
            'type' => 'success'
        ]);
    }
    
    public static function notifyLogRejected($pdo, $studentId, $logId, $comment) {
        return self::create($pdo, [
            'user_id' => $studentId,
            'title' => 'Log Entry Rejected',
            'message' => "Your log entry has been rejected. Comment: " . $comment,
            'type' => 'error'
        ]);
    }
    
    public static function notifyWeeklySummaryDue($pdo, $studentId, $weekNumber) {
        return self::create($pdo, [
            'user_id' => $studentId,
            'title' => 'Weekly Summary Due',
            'message' => "Please complete your weekly summary for Week {$weekNumber}.",
            'type' => 'warning'
        ]);
    }
    
    public static function notifyMonthlySummaryDue($pdo, $studentId, $monthNumber) {
        $monthName = date('F', mktime(0, 0, 0, $monthNumber, 1));
        return self::create($pdo, [
            'user_id' => $studentId,
            'title' => 'Monthly Summary Due',
            'message' => "Please complete your monthly summary for {$monthName}.",
            'type' => 'warning'
        ]);
    }
    
    public static function notifyNewLogForSupervisor($pdo, $supervisorId, $studentName) {
        return self::create($pdo, [
            'user_id' => $supervisorId,
            'title' => 'New Log Entry',
            'message' => "{$studentName} has submitted a new log entry for review.",
            'type' => 'info'
        ]);
    }
    
    public static function notifyWeeklySummaryForSupervisor($pdo, $supervisorId, $studentName, $weekNumber) {
        return self::create($pdo, [
            'user_id' => $supervisorId,
            'title' => 'Weekly Summary Submitted',
            'message' => "{$studentName} has submitted their weekly summary for Week {$weekNumber}.",
            'type' => 'info'
        ]);
    }
    
    public static function notifyMonthlySummaryForSupervisor($pdo, $supervisorId, $studentName, $monthNumber) {
        $monthName = date('F', mktime(0, 0, 0, $monthNumber, 1));
        return self::create($pdo, [
            'user_id' => $supervisorId,
            'title' => 'Monthly Summary Submitted',
            'message' => "{$studentName} has submitted their monthly summary for {$monthName}.",
            'type' => 'info'
        ]);
    }
    
    public static function getTypeIcon($type) {
        $icons = [
            'info' => 'fas fa-info-circle',
            'success' => 'fas fa-check-circle',
            'warning' => 'fas fa-exclamation-triangle',
            'error' => 'fas fa-times-circle'
        ];
        return $icons[$type] ?? 'fas fa-bell';
    }
    
    public static function getTypeClass($type) {
        $classes = [
            'info' => 'text-info',
            'success' => 'text-success',
            'warning' => 'text-warning',
            'error' => 'text-danger'
        ];
        return $classes[$type] ?? 'text-secondary';
    }
}
?> 