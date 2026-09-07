<?php
class Evaluation {
    public static function create($pdo, $data) {
        $stmt = $pdo->prepare("
            INSERT INTO evaluations (
                student_id, supervisor_id, evaluation_type, period_reference,
                punctuality_rating, technical_skill_rating, communication_rating, 
                attitude_rating, final_recommendation, digital_signature
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['student_id'],
            $data['supervisor_id'],
            $data['evaluation_type'],
            $data['period_reference'],
            $data['punctuality_rating'],
            $data['technical_skill_rating'],
            $data['communication_rating'],
            $data['attitude_rating'],
            $data['final_recommendation'] ?? null,
            $data['digital_signature'] ?? null
        ]);
    }
    
    public static function getByStudentId($pdo, $studentId) {
        $stmt = $pdo->prepare("
            SELECT e.*, u.name as student_name, s.name as supervisor_name
            FROM evaluations e 
            JOIN users u ON e.student_id = u.id 
            JOIN users s ON e.supervisor_id = s.id
            WHERE e.student_id = ? 
            ORDER BY e.created_at DESC
        ");
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    }
    
    public static function getBySupervisorId($pdo, $supervisorId) {
        $stmt = $pdo->prepare("
            SELECT e.*, u.name as student_name, u.matric_number, u.department, u.student_id
            FROM evaluations e 
            JOIN users u ON e.student_id = u.id 
            WHERE e.supervisor_id = ? 
            ORDER BY e.created_at DESC
        ");
        $stmt->execute([$supervisorId]);
        return $stmt->fetchAll();
    }
    
    public static function getByType($pdo, $studentId, $evaluationType) {
        $stmt = $pdo->prepare("
            SELECT e.*, u.name as student_name, s.name as supervisor_name
            FROM evaluations e 
            JOIN users u ON e.student_id = u.id 
            JOIN users s ON e.supervisor_id = s.id
            WHERE e.student_id = ? AND e.evaluation_type = ?
            ORDER BY e.created_at DESC
        ");
        $stmt->execute([$studentId, $evaluationType]);
        return $stmt->fetchAll();
    }
    
    public static function getById($pdo, $evaluationId) {
        $stmt = $pdo->prepare("
            SELECT e.*, u.name as student_name, u.matric_number, u.department, u.student_id, s.name as supervisor_name
            FROM evaluations e 
            JOIN users u ON e.student_id = u.id 
            JOIN users s ON e.supervisor_id = s.id
            WHERE e.id = ?
        ");
        $stmt->execute([$evaluationId]);
        return $stmt->fetch();
    }
    
    public static function getStatsByStudent($pdo, $studentId) {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_evaluations,
                AVG(punctuality_rating) as avg_punctuality,
                AVG(technical_skill_rating) as avg_technical,
                AVG(communication_rating) as avg_communication,
                AVG(attitude_rating) as avg_attitude,
                AVG((punctuality_rating + technical_skill_rating + communication_rating + attitude_rating) / 4) as overall_average
            FROM evaluations 
            WHERE student_id = ?
        ");
        $stmt->execute([$studentId]);
        return $stmt->fetch();
    }
    
    public static function getStatsForAdmin($pdo) {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_evaluations,
                COUNT(DISTINCT student_id) as total_students,
                COUNT(DISTINCT supervisor_id) as total_supervisors,
                AVG(punctuality_rating) as avg_punctuality,
                AVG(technical_skill_rating) as avg_technical,
                AVG(communication_rating) as avg_communication,
                AVG(attitude_rating) as avg_attitude,
                AVG((punctuality_rating + technical_skill_rating + communication_rating + attitude_rating) / 4) as overall_average
            FROM evaluations
        ");
        $stmt->execute();
        return $stmt->fetch();
    }
    
    public static function getAverageRating($pdo, $studentId) {
        $stmt = $pdo->prepare("
            SELECT AVG((punctuality_rating + technical_skill_rating + communication_rating + attitude_rating) / 4) as average_rating
            FROM evaluations 
            WHERE student_id = ?
        ");
        $stmt->execute([$studentId]);
        $result = $stmt->fetch();
        return round($result['average_rating'], 2);
    }
    
    public static function getRatingDescription($rating) {
        if ($rating >= 4.5) return 'Excellent';
        if ($rating >= 4.0) return 'Very Good';
        if ($rating >= 3.5) return 'Good';
        if ($rating >= 3.0) return 'Satisfactory';
        if ($rating >= 2.5) return 'Fair';
        return 'Needs Improvement';
    }
    
    public static function getTypeDescription($type) {
        $types = [
            'weekly' => 'Weekly Evaluation',
            'monthly' => 'Monthly Evaluation',
            'final' => 'Final Evaluation'
        ];
        return $types[$type] ?? 'Unknown';
    }
    
    public static function existsForPeriod($pdo, $studentId, $evaluationType, $periodReference) {
        $stmt = $pdo->prepare("
            SELECT id FROM evaluations 
            WHERE student_id = ? AND evaluation_type = ? AND period_reference = ?
        ");
        $stmt->execute([$studentId, $evaluationType, $periodReference]);
        return $stmt->rowCount() > 0;
    }
}
?> 