<?php
class User {
    public static function findByEmail($pdo, $email) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    
    public static function findById($pdo, $id) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public static function getStudentsForSupervisor($pdo, $supervisorId) {
        $stmt = $pdo->prepare("
            SELECT id, username, email, matric_number, student_id, department, institution, 
                   siwes_start_date, siwes_end_date, workplace_name 
            FROM users 
            WHERE role = 'student' AND supervisor_id = ?
        ");
        $stmt->execute([$supervisorId]);
        return $stmt->fetchAll();
    }
    
    public static function create($pdo, $data) {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, matric_number, student_id, department, institution, siwes_start_date, siwes_end_date, workplace_name, password, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $data['name'], 
            $data['email'], 
            $data['matric_number'] ?? null, 
            $data['student_id'] ?? null,
            $data['department'] ?? null,
            $data['institution'] ?? null, 
            $data['siwes_start_date'] ?? null,
            $data['siwes_end_date'] ?? null,
            $data['workplace_name'] ?? null,
            password_hash($data['password'], PASSWORD_DEFAULT), 
            $data['role']
        ]);
    }
    
    public static function getAllStudents($pdo) {
        $stmt = $pdo->prepare("SELECT id, username, email, matric_number, student_id, department, institution, siwes_start_date, siwes_end_date, workplace_name, is_active FROM users WHERE role = 'student'");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public static function getAllSupervisors($pdo) {
        $stmt = $pdo->prepare("SELECT id, username, email, department, institution FROM users WHERE role = 'supervisor'");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public static function updateProfile($pdo, $userId, $data) {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, matric_number = ?, student_id = ?, department = ?, institution = ?, siwes_start_date = ?, siwes_end_date = ?, workplace_name = ? WHERE id = ?");
        return $stmt->execute([
            $data['name'],
            $data['email'],
            $data['matric_number'] ?? null,
            $data['student_id'] ?? null,
            $data['department'] ?? null,
            $data['institution'] ?? null,
            $data['siwes_start_date'] ?? null,
            $data['siwes_end_date'] ?? null,
            $data['workplace_name'] ?? null,
            $userId
        ]);
    }
    
    public static function changePassword($pdo, $userId, $newPassword) {
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([
            password_hash($newPassword, PASSWORD_DEFAULT),
            $userId
        ]);
    }
}
?>