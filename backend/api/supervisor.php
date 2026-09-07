<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../models/User.php';
require_once '../models/LogEntry.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

requireRole('supervisor');

$data = json_decode(file_get_contents('php://input'), true);

if ($data['action'] === 'dashboard') {
    $user = User::findById($pdo, $_SESSION['user_id']);
    $pendingLogs = LogEntry::getPendingForSupervisor($pdo, $_SESSION['user_id']);
    $pendingCount = count($pendingLogs);

    // Approved logs count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as cnt FROM log_entries le
        JOIN users u ON u.id = le.student_id
        WHERE u.supervisor_id = ? AND le.status = 'approved'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $approvedCount = $stmt->fetch()['cnt'];

    // Rejected logs count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as cnt FROM log_entries le
        JOIN users u ON u.id = le.student_id
        WHERE u.supervisor_id = ? AND le.status = 'rejected'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $rejectedCount = $stmt->fetch()['cnt'];

    // Student count
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM users WHERE supervisor_id = ? AND role = 'student'");
    $stmt->execute([$_SESSION['user_id']]);
    $studentCount = $stmt->fetch()['cnt'];

    echo json_encode([
        'success' => true,
        'name' => $user['username'] ?? $user['full_name'] ?? 'Supervisor',
        'pending_logs' => $pendingLogs,
        'approved_count' => $approvedCount,
        'rejected_count' => $rejectedCount,
        'student_count' => $studentCount
    ]);
    exit;
}

if ($data['action'] === 'get_pending_logs') {
    $student_id = isset($data['student_id']) ? $data['student_id'] : null;
    $logs = LogEntry::getPendingForSupervisor($pdo, $_SESSION['user_id'], $student_id);
    echo json_encode(['success' => true, 'logs' => $logs]);
    exit;
}

if ($data['action'] === 'get_log_details') {
    $log = LogEntry::getById($pdo, $data['log_id']);
    if ($log) {
        echo json_encode(['success' => true, 'log' => $log]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Log not found']);
    }
    exit;
}

if ($data['action'] === 'approve_log') {
    $ok = LogEntry::updateStatus($pdo, $data['log_id'], 'approved', $_SESSION['user_id'], $data['comment']);
    
    if ($ok) {
        echo json_encode(['success' => true, 'message' => 'Log approved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to approve log']);
    }
    exit;
}

if ($data['action'] === 'reject_log') {
    $ok = LogEntry::updateStatus($pdo, $data['log_id'], 'rejected', $_SESSION['user_id'], $data['comment']);
    
    if ($ok) {
        echo json_encode(['success' => true, 'message' => 'Log rejected successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to reject log']);
    }
    exit;
}

if ($data['action'] === 'get_profile') {
    $user = User::findById($pdo, $_SESSION['user_id']);
    unset($user['password']);
    echo json_encode(['success' => true, 'profile' => $user]);
    exit;
}

if ($data['action'] === 'update_profile') {
    $ok = User::updateProfile($pdo, $_SESSION['user_id'], [
        'name' => $data['name'],
        'email' => $data['email'],
        'department' => $data['department'],
        'institution' => $data['institution']
    ]);
    
    if ($ok) {
        $_SESSION['name'] = $data['name'];
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
    }
    exit;
}

if ($data['action'] === 'get_assigned_students') {
    $students = User::getStudentsForSupervisor($pdo, $_SESSION['user_id']);
    
    // Get additional data for each student
    foreach ($students as &$student) {
        // Get pending logs count
        $stmt = $pdo->prepare("SELECT COUNT(*) as pending_count FROM log_entries WHERE student_id = ? AND status = 'pending'");
        $stmt->execute([$student['id']]);
        $pendingCount = $stmt->fetch();
        $student['pending_logs'] = $pendingCount['pending_count'];
        
        // Check if student has any logs (active status)
        $stmt = $pdo->prepare("SELECT COUNT(*) as log_count FROM log_entries WHERE student_id = ?");
        $stmt->execute([$student['id']]);
        $logCount = $stmt->fetch();
        $student['active'] = $logCount['log_count'] > 0;
    }
    
    echo json_encode([
        'success' => true, 
        'students' => $students,
        'total' => count($students),
        'active' => count(array_filter($students, function($s) { return $s['active']; })),
        'inactive' => count(array_filter($students, function($s) { return !$s['active']; }))
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>