<?php
session_start();
require_once '../backend/config/db.php';
require_once '../backend/config/session.php';

// Check if user is logged in and has admin/coordinator role
if (!isLoggedIn() || (!hasRole('admin') && !hasRole('coordinator'))) {
    header('Location: login.php');
    exit();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => ''];
    
    try {
        switch ($action) {
            case 'create_backup':
                $backupName = $_POST['backup_name'] ?? 'backup_' . date('Y-m-d_H-i-s');
                $backupPath = '../backups/' . $backupName . '.sql';
                
                // Create backups directory if it doesn't exist
                if (!is_dir('../backups')) {
                    mkdir('../backups', 0755, true);
                }
                
                // Get database configuration
                $host = 'localhost:3306';
                $dbname = 'siwes_logbook';
                $username = 'root';
                $password = '';
                
                // Create backup using mysqldump
                $command = "mysqldump -h localhost -P 3306 -u $username -p$password $dbname > $backupPath";
                $output = [];
                $returnCode = 0;
                
                exec($command, $output, $returnCode);
                
                if ($returnCode === 0 && file_exists($backupPath)) {
                    // Save backup info to database
                    $stmt = $pdo->prepare("
                        INSERT INTO backup_logs (backup_name, file_path, file_size, created_by, created_at) 
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $backupName,
                        $backupPath,
                        filesize($backupPath),
                        $_SESSION['user_id'] ?? 0
                    ]);
                    
                    $response['success'] = true;
                    $response['message'] = 'Backup created successfully';
                    $response['backup_name'] = $backupName;
                } else {
                    $response['message'] = 'Failed to create backup. Please check database credentials.';
                }
                break;
                
            case 'restore_backup':
                $backupId = $_POST['backup_id'] ?? 0;
                
                // Get backup file path
                $stmt = $pdo->prepare("SELECT file_path FROM backup_logs WHERE id = ?");
                $stmt->execute([$backupId]);
                $backup = $stmt->fetch();
                
                if ($backup && file_exists($backup['file_path'])) {
                    $host = 'localhost:3306';
                    $dbname = 'siwes_logbook';
                    $username = 'root';
                    $password = '';
                    
                    // Restore backup using mysql
                    $command = "mysql -h localhost -P 3306 -u $username -p$password $dbname < " . $backup['file_path'];
                    $output = [];
                    $returnCode = 0;
                    
                    exec($command, $output, $returnCode);
                    
                    if ($returnCode === 0) {
                        $response['success'] = true;
                        $response['message'] = 'Database restored successfully';
                    } else {
                        $response['message'] = 'Failed to restore backup. Please check database credentials.';
                    }
                } else {
                    $response['message'] = 'Backup file not found';
                }
                break;
                
            case 'delete_backup':
                $backupId = $_POST['backup_id'] ?? 0;
                
                // Get backup file path
                $stmt = $pdo->prepare("SELECT file_path FROM backup_logs WHERE id = ?");
                $stmt->execute([$backupId]);
                $backup = $stmt->fetch();
                
                if ($backup) {
                    // Delete file
                    if (file_exists($backup['file_path'])) {
                        unlink($backup['file_path']);
                    }
                    
                    // Delete from database
                    $stmt = $pdo->prepare("DELETE FROM backup_logs WHERE id = ?");
                    $stmt->execute([$backupId]);
                    
                    $response['success'] = true;
                    $response['message'] = 'Backup deleted successfully';
                } else {
                    $response['message'] = 'Backup not found';
                }
                break;
                
            case 'download_backup':
                $backupId = $_POST['backup_id'] ?? 0;
                
                // Get backup file path
                $stmt = $pdo->prepare("SELECT file_path, backup_name FROM backup_logs WHERE id = ?");
                $stmt->execute([$backupId]);
                $backup = $stmt->fetch();
                
                if ($backup && file_exists($backup['file_path'])) {
                    $response['success'] = true;
                    $response['download_url'] = 'download_backup.php?id=' . $backupId;
                    $response['message'] = 'Download ready';
                } else {
                    $response['message'] = 'Backup file not found';
                }
                break;
                
            default:
                $response['message'] = 'Invalid action';
        }
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit();
}

// Create backup_logs table if it doesn't exist
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS backup_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            backup_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_size BIGINT NOT NULL,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_created_at (created_at)
        )
    ");
} catch (PDOException $e) {
    // Table might already exist
}

// Get all backups
try {
    $stmt = $pdo->query("
        SELECT bl.*, u.username as created_by_name
        FROM backup_logs bl
        LEFT JOIN users u ON bl.created_by = u.id
        ORDER BY bl.created_at DESC
    ");
    $backups = $stmt->fetchAll();
    
    // Get database size
    $stmt = $pdo->query("
        SELECT 
            ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS db_size_mb
        FROM information_schema.tables 
        WHERE table_schema = 'siwes_logbook'
    ");
    $dbSize = $stmt->fetch()['db_size_mb'] ?? 0;
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $backups = [];
    $dbSize = 0;
}

// Calculate total backup size
$totalBackupSize = 0;
foreach ($backups as $backup) {
    $totalBackupSize += $backup['file_size'];
}
$totalBackupSizeMB = round($totalBackupSize / 1024 / 1024, 2);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup & Restore - SIWES Logbook</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/admin-styles.css">
    
    <style>
        .backup-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary-color);
        }
        
        .backup-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .backup-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .backup-info {
            flex: 1;
        }
        
        .backup-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .backup-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.875rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        
        .backup-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.875rem;
        }
        
        .create-backup-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .warning-card {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .warning-card .text-warning {
            color: #856404 !important;
        }
        
        .file-size {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .backup-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .backup-status.available {
            background: #d4edda;
            color: #155724;
        }
        
        .backup-status.missing {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <!-- Include Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <?php 
        $pageTitle = 'Backup & Restore';
        include 'includes/header.php'; 
        ?>
        
        <!-- Page Content -->
        <div class="page-content">
            <!-- Error Display -->
            <?php if (isset($error)): ?>
                <div class="alert alert-danger fade-in-up">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Warning -->
            <div class="warning-card">
                <h6 class="text-warning mb-2">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Important Notice
                </h6>
                <p class="text-warning mb-0">
                    Database backup and restore operations require proper database credentials and file system permissions. 
                    Make sure to test backups regularly and keep them in a secure location.
                </p>
            </div>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card fade-in-up">
                    <div class="stat-number"><?php echo count($backups); ?></div>
                    <div class="stat-label">Total Backups</div>
                </div>
                <div class="stat-card fade-in-up">
                    <div class="stat-number"><?php echo $dbSize; ?> MB</div>
                    <div class="stat-label">Database Size</div>
                </div>
                <div class="stat-card fade-in-up">
                    <div class="stat-number"><?php echo $totalBackupSizeMB; ?> MB</div>
                    <div class="stat-label">Total Backup Size</div>
                </div>
                <div class="stat-card fade-in-up">
                    <div class="stat-number"><?php echo count(array_filter($backups, function($b) { return file_exists($b['file_path']); })); ?></div>
                    <div class="stat-label">Available Backups</div>
                </div>
            </div>
            
            <!-- Create Backup -->
            <div class="create-backup-card fade-in-up">
                <h3 class="mb-3">
                    <i class="fas fa-plus-circle me-2"></i>
                    Create New Backup
                </h3>
                
                <form id="createBackupForm">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="backup_name" class="form-label">Backup Name</label>
                                <input type="text" class="form-control" id="backup_name" name="backup_name" 
                                       value="backup_<?php echo date('Y-m-d_H-i-s'); ?>" required>
                                <div class="form-text">Leave empty for auto-generated name</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-download me-2"></i>
                                    Create Backup
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Backups List -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>
                    <i class="fas fa-database me-2"></i>
                    Backup History
                </h3>
                <div class="text-muted">
                    Showing <?php echo count($backups); ?> backups
                </div>
            </div>
            
            <div id="backupsList">
                <?php if (empty($backups)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-database fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No backups found</h5>
                        <p class="text-muted">Create your first backup to get started.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($backups as $backup): ?>
                        <?php $fileExists = file_exists($backup['file_path']); ?>
                        <div class="backup-card fade-in-up">
                            <div class="backup-header">
                                <div class="backup-info">
                                    <div class="backup-name">
                                        <?php echo htmlspecialchars($backup['backup_name']); ?>
                                        <span class="backup-status <?php echo $fileExists ? 'available' : 'missing'; ?>">
                                            <?php echo $fileExists ? 'Available' : 'Missing'; ?>
                                        </span>
                                    </div>
                                    <div class="backup-meta">
                                        <span>
                                            <i class="fas fa-user me-1"></i>
                                            <?php echo htmlspecialchars($backup['created_by_name'] ?? 'Unknown'); ?>
                                        </span>
                                        <span>
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo date('M j, Y g:i A', strtotime($backup['created_at'])); ?>
                                        </span>
                                        <span>
                                            <i class="fas fa-hdd me-1"></i>
                                            <span class="file-size"><?php echo round($backup['file_size'] / 1024 / 1024, 2); ?> MB</span>
                                        </span>
                                    </div>
                                </div>
                                <div class="backup-actions">
                                    <?php if ($fileExists): ?>
                                        <button class="btn btn-outline-primary btn-sm" onclick="downloadBackup(<?php echo $backup['id']; ?>)">
                                            <i class="fas fa-download"></i>
                                        </button>
                                        <button class="btn btn-outline-warning btn-sm" onclick="restoreBackup(<?php echo $backup['id']; ?>)">
                                            <i class="fas fa-upload"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn btn-outline-danger btn-sm" onclick="deleteBackup(<?php echo $backup['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/admin-scripts.js"></script>
    <script>
        // Create backup form
        document.getElementById('createBackupForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'create_backup');
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating...';
            submitBtn.disabled = true;
            
            fetch('backup-restore.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                showToast('An error occurred', 'error');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
        
        // Download backup
        function downloadBackup(backupId) {
            const formData = new FormData();
            formData.append('action', 'download_backup');
            formData.append('backup_id', backupId);
            
            fetch('backup-restore.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.open(data.download_url, '_blank');
                    showToast('Download started', 'success');
                } else {
                    showToast(data.message, 'error');
                }
            });
        }
        
        // Restore backup
        function restoreBackup(backupId) {
            if (confirm('Are you sure you want to restore this backup? This will overwrite the current database and cannot be undone.')) {
                const formData = new FormData();
                formData.append('action', 'restore_backup');
                formData.append('backup_id', backupId);
                
                fetch('backup-restore.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showToast(data.message, 'error');
                    }
                });
            }
        }
        
        // Delete backup
        function deleteBackup(backupId) {
            if (confirm('Are you sure you want to delete this backup? This action cannot be undone.')) {
                const formData = new FormData();
                formData.append('action', 'delete_backup');
                formData.append('backup_id', backupId);
                
                fetch('backup-restore.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast(data.message, 'error');
                    }
                });
            }
        }
    </script>
</body>
</html>
