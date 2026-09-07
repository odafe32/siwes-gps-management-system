<?php
class LogEntry {
    public static function create($pdo, $data) {
        $stmt = $pdo->prepare("
            INSERT INTO log_entries (
                student_id, activity, date, latitude, longitude, location_address, status, accuracy, altitude, heading, speed
            ) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['student_id'],
            $data['activity'],
            $data['date'],
            $data['latitude'] ?? null,
            $data['longitude'] ?? null,
            $data['location_address'] ?? null,
            $data['accuracy'] ?? null,
            $data['altitude'] ?? null,
            $data['heading'] ?? null,
            $data['speed'] ?? null
        ]);
    }
    
    public static function getByStudentId($pdo, $studentId) {
        $stmt = $pdo->prepare("
            SELECT le.*, u.username as student_name 
            FROM log_entries le 
            JOIN users u ON le.student_id = u.id 
            WHERE le.student_id = ? 
            ORDER BY le.date DESC, le.created_at DESC
        ");
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    }
    
    public static function getByWeek($pdo, $studentId, $weekNumber) {
        // Calculate date range for the week
        $startDate = date('Y-m-d', strtotime("+$weekNumber weeks", strtotime($_SESSION['siwes_start_date'] ?? '2026-01-15')));
        $endDate = date('Y-m-d', strtotime("+$weekNumber weeks +6 days", strtotime($_SESSION['siwes_start_date'] ?? '2026-01-15')));
        
        $stmt = $pdo->prepare("
            SELECT le.*, u.username as student_name 
            FROM log_entries le 
            JOIN users u ON le.student_id = u.id 
            WHERE le.student_id = ? AND le.date BETWEEN ? AND ?
            ORDER BY le.date ASC, le.created_at ASC
        ");
        $stmt->execute([$studentId, $startDate, $endDate]);
        return $stmt->fetchAll();
    }
    
    public static function getPendingForSupervisor($pdo, $supervisorId, $studentId = null) {
        $query = "
            SELECT le.*, u.username as student_name, u.matric_number, u.department, u.id as student_id
            FROM log_entries le
            JOIN users u ON le.student_id = u.id
            WHERE le.status = 'pending' AND u.supervisor_id = ?
        ";

        $params = [$supervisorId];

        // If student_id is provided, add it to the query
        if ($studentId) {
            $query .= " AND le.student_id = ?";
            $params[] = $studentId;
        }

        $query .= " ORDER BY le.date DESC, le.created_at DESC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public static function getAll($pdo) {
        $stmt = $pdo->prepare("
            SELECT le.*, u.username as student_name, u.matric_number, u.department, u.id as student_id
            FROM log_entries le
            JOIN users u ON le.student_id = u.id
            ORDER BY le.date DESC, le.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public static function updateStatus($pdo, $logId, $status, $supervisorId, $comment = null) {
        $stmt = $pdo->prepare("
            UPDATE log_entries 
            SET status = ?, supervisor_comment = ? 
            WHERE id = ?
        ");
        return $stmt->execute([$status, $comment, $logId]);
    }
    
    public static function getById($pdo, $logId) {
        $stmt = $pdo->prepare("
            SELECT le.*, u.username as student_name, u.matric_number, u.department, u.id as student_id
            FROM log_entries le
            JOIN users u ON le.student_id = u.id
            WHERE le.id = ?
        ");
        $stmt->execute([$logId]);
        return $stmt->fetch();
    }
    
    public static function getStatsByStudent($pdo, $studentId) {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
            FROM log_entries 
            WHERE student_id = ?
        ");
        $stmt->execute([$studentId]);
        return $stmt->fetch();
    }
    
    public static function getStatsForAdmin($pdo) {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_logs,
                COUNT(DISTINCT student_id) as total_students,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
            FROM log_entries
        ");
        $stmt->execute();
        return $stmt->fetch();
    }
    
    public static function getWeeklyStats($pdo, $studentId) {
        $stmt = $pdo->prepare("
            SELECT 
                WEEK(date) as week_number,
                COUNT(*) as total_entries,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
            FROM log_entries 
            WHERE student_id = ?
            GROUP BY WEEK(date)
            ORDER BY WEEK(date) DESC
        ");
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    }
    
    public static function getRecentEntries($pdo, $studentId, $limit = 5) {
        $stmt = $pdo->prepare("
            SELECT le.*, u.username as student_name 
            FROM log_entries le 
            JOIN users u ON le.student_id = u.id 
            WHERE le.student_id = ? 
            ORDER BY le.date DESC, le.created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$studentId, $limit]);
        return $stmt->fetchAll();
    }
    
    public static function calculateWeekNumber($startDate, $currentDate) {
        $start = new DateTime($startDate);
        $current = new DateTime($currentDate);
        $diff = $start->diff($current);
        return ceil($diff->days / 7);
    }
    
    public static function getDayName($date) {
        return date('l', strtotime($date));
    }
    // GPS Monitoring Methods
    public static function getRecentLocations($pdo, $limit = 50) {
        $stmt = $pdo->prepare("
            SELECT le.*, u.username as student_name, u.matric_number, u.department, u.workplace_name
            FROM log_entries le
            JOIN users u ON le.student_id = u.id
            WHERE le.latitude IS NOT NULL AND le.longitude IS NOT NULL
            ORDER BY le.date DESC, le.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public static function getActiveStudents($pdo) {
        $stmt = $pdo->prepare("
            SELECT DISTINCT u.id, u.username, u.matric_number, u.department, u.workplace_name,
                   MAX(le.date) as last_activity_date,
                   MAX(le.created_at) as last_activity_time,
                   le.latitude as last_latitude,
                   le.longitude as last_longitude,
                   le.activity as last_activity
            FROM users u
            LEFT JOIN log_entries le ON u.id = le.student_id AND le.latitude IS NOT NULL AND le.longitude IS NOT NULL
            WHERE u.role = 'student'
            GROUP BY u.id
            HAVING last_activity_date IS NOT NULL
            ORDER BY last_activity_date DESC, last_activity_time DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>