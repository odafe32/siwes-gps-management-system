<?php
class WeeklySummary {
    public static function create($pdo, $data) {
        $stmt = $pdo->prepare("
            INSERT INTO weekly_summaries (
                student_id, week_number, summary_of_work, 
                problems_encountered, suggestions_for_improvement, status
            ) VALUES (?, ?, ?, ?, ?, 'pending')
        ");
        return $stmt->execute([
            $data['student_id'],
            $data['week_number'],
            $data['summary_of_work'],
            $data['problems_encountered'] ?? null,
            $data['suggestions_for_improvement'] ?? null
        ]);
    }
    
    public static function getByStudentId($pdo, $studentId) {
        $stmt = $pdo->prepare("
            SELECT ws.*, u.name as student_name 
            FROM weekly_summaries ws 
            JOIN users u ON ws.student_id = u.id 
            WHERE ws.student_id = ? 
            ORDER BY ws.week_number DESC
        ");
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    }
    
    public static function getByWeek($pdo, $studentId, $weekNumber) {
        $stmt = $pdo->prepare("
            SELECT ws.*, u.name as student_name 
            FROM weekly_summaries ws 
            JOIN users u ON ws.student_id = u.id 
            WHERE ws.student_id = ? AND ws.week_number = ?
        ");
        $stmt->execute([$studentId, $weekNumber]);
        return $stmt->fetch();
    }
    
    public static function getPendingForSupervisor($pdo) {
        $stmt = $pdo->prepare("
            SELECT ws.*, u.name as student_name, u.matric_number, u.department, u.student_id
            FROM weekly_summaries ws 
            JOIN users u ON ws.student_id = u.id 
            WHERE ws.status = 'pending'
            ORDER BY ws.week_number DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public static function updateStatus($pdo, $summaryId, $status, $supervisorId, $comment = null) {
        $stmt = $pdo->prepare("
            UPDATE weekly_summaries 
            SET status = ?, supervisor_id = ?, supervisor_comment = ? 
            WHERE id = ?
        ");
        return $stmt->execute([$status, $supervisorId, $comment, $summaryId]);
    }
    
    public static function getById($pdo, $summaryId) {
        $stmt = $pdo->prepare("
            SELECT ws.*, u.name as student_name, u.matric_number, u.department, u.student_id
            FROM weekly_summaries ws 
            JOIN users u ON ws.student_id = u.id 
            WHERE ws.id = ?
        ");
        $stmt->execute([$summaryId]);
        return $stmt->fetch();
    }
    
    public static function existsForWeek($pdo, $studentId, $weekNumber) {
        $stmt = $pdo->prepare("
            SELECT id FROM weekly_summaries 
            WHERE student_id = ? AND week_number = ?
        ");
        $stmt->execute([$studentId, $weekNumber]);
        return $stmt->rowCount() > 0;
    }
    
    public static function getStatsByStudent($pdo, $studentId) {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                MAX(week_number) as current_week
            FROM weekly_summaries 
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
            FROM weekly_summaries
        ");
        $stmt->execute();
        return $stmt->fetch();
    }
    
    public static function checkWeeklyCompletion($pdo, $studentId, $weekNumber) {
        // Check if student has 5 entries for the week (Mon-Fri)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as entry_count 
            FROM log_entries 
            WHERE student_id = ? AND week_number = ?
        ");
        $stmt->execute([$studentId, $weekNumber]);
        $result = $stmt->fetch();
        
        return $result['entry_count'] >= 5;
    }
}
?> 