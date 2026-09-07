<?php
session_start();
require_once '../backend/config/db.php';
require_once '../backend/config/session.php';

// Check if user is logged in and has admin/coordinator role
if (!isLoggedIn() || (!hasRole('admin') && !hasRole('coordinator'))) {
    http_response_code(403);
    die('Access denied');
}

$backupId = $_GET['id'] ?? 0;

if (!$backupId) {
    http_response_code(400);
    die('Invalid backup ID');
}

try {
    // Get backup file path
    $stmt = $pdo->prepare("SELECT file_path, backup_name FROM backup_logs WHERE id = ?");
    $stmt->execute([$backupId]);
    $backup = $stmt->fetch();
    
    if (!$backup) {
        http_response_code(404);
        die('Backup not found');
    }
    
    $filePath = $backup['file_path'];
    $backupName = $backup['backup_name'];
    
    if (!file_exists($filePath)) {
        http_response_code(404);
        die('Backup file not found');
    }
    
    // Set headers for file download
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $backupName . '.sql"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');
    
    // Output file
    readfile($filePath);
    exit();
    
} catch (Exception $e) {
    http_response_code(500);
    die('Error: ' . $e->getMessage());
}
?>
