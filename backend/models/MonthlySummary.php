<?php
class MonthlySummary {
    public static function create($pdo, $data) {
        $stmt = $pdo->prepare("
            INSERT INTO monthly_summaries (
                student_id, month_number, learning_outcomes, 
                innovations_initiatives, general_reflections, status
            ) VALUES (?, ?, ?, ?, ?, 'pending')
        ");
        return $stmt->execute([
            $data['student_id'],
            $data['month_number'],
            $data['learning_outcomes'],
            $data['innovations_initiatives'] ?? null,
            $data['general_reflections'] ?? null
        ]);
    }
    
    public static function getByStudentId($pdo, $studentId) {
        $stmt = $pdo->prepare("
            SELECT ms.*, u.name as student_name 
            FROM monthly_summaries ms 
            JOIN users u ON ms.student_id = u.id 
            WHERE ms.student_id = ? 
            ORDER BY ms.month_number DESC
        ");
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    }
    
    public static function getByMonth($pdo, $studentId, $monthNumber) {
        $stmt = $pdo->prepare("
            SELECT ms.*, u.name as student_name 
            FROM monthly_summaries ms 
            JOIN users u ON ms.student_id = u.id 
            WHERE ms.student_id = ? AND ms.month_number = ?
        ");
        $stmt->execute([$studentId, $monthNumber]);
        return $stmt->fetch();
    }
    
    public static function getPendingForSupervisor($pdo) {
        $stmt = $pdo->prepare("
            SELECT ms.*, u.name as student_name, u.matric_number, u.department, u.student_id
            FROM monthly_summaries ms 
            JOIN users u ON ms.student_id = u.id 
            WHERE ms.status = 'pending'
            ORDER BY ms.month_number DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public static function updateStatus($pdo, $summaryId, $status, $supervisorId, $comment = null) {
        $stmt = $pdo->prepare("
            UPDATE monthly_summaries 
            SET status = ?, supervisor_id = ?, supervisor_comment = ? 
            WHERE id = ?
        ");
        return $stmt->execute([$status, $supervisorId, $comment, $summaryId]);
    }
    
    public static function getById($pdo, $summaryId) {
        $stmt = $pdo->prepare("
            SELECT ms.*, u.name as student_name, u.matric_number, u.department, u.student_id
            FROM monthly_summaries ms 
            JOIN users u ON ms.student_id = u.id 
            WHERE ms.id = ?
        ");
        $stmt->execute([$summaryId]);
        return $stmt->fetch();
    }
    
    public static function existsForMonth($pdo, $studentId, $monthNumber) {
        $stmt = $pdo->prepare("
            SELECT id FROM monthly_summaries 
            WHERE student_id = ? AND month_number = ?
        ");
        $stmt->execute([$studentId, $monthNumber]);
        return $stmt->rowCount() > 0;
    }
    
    public static function getStatsByStudent($pdo, $studentId) {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                MAX(month_number) as current_month
            FROM monthly_summaries 
            WHERE student_id = ?
        ");
        $stmt->execute([$studentId]);
        return $stmt->fetch();
    }
    
    public static function getStatsForAdmin($pdo) {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_summaries,
                COUNT(DISTINCT student_id) as total_students,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
            FROM monthly_summaries
        ");
        $stmt->execute();
        return $stmt->fetch();
    }
    
    public static function checkMonthlyCompletion($pdo, $studentId, $monthNumber) {
        // Check if student has completed 4 weeks for the month
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT week_number) as week_count 
            FROM log_entries 
            WHERE student_id = ? AND MONTH(date) = ? AND YEAR(date) = YEAR(CURDATE())
        ");
        $stmt->execute([$studentId, $monthNumber]);
        $result = $stmt->fetch();
        
        return $result['week_count'] >= 4;
    }
    
    public static function getMonthName($monthNumber) {
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];
        return $months[$monthNumber] ?? 'Unknown';
    }
}
?> 