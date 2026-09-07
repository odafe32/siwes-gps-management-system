<?php
session_start();
require_once '../backend/config/db.php';
require_once '../backend/config/session.php';

// Check if user is logged in and has student role
if (!isLoggedIn() || !hasRole('student')) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        try {
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            
            if (empty($name) || empty($email)) {
                throw new Exception('Name and email are required');
            }
            
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $stmt->execute([$name, $email, $user_id]);
            
            $_SESSION['name'] = $name;
            $message = "Profile updated successfully!";
            $messageType = "success";
            
        } catch (Exception $e) {
            $message = "Error updating profile: " . $e->getMessage();
            $messageType = "error";
        }
    }
}

// Get current user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $user = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - SIWES Logbook</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/student-styles.css">
</head>
<body>
    <!-- Include Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="header">
            <div class="header-left">
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">Settings</h1>
            </div>
            
            <div class="user-menu">
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Student'); ?></div>
                    <div class="user-role">Student</div>
                </div>
                <button class="logout-btn" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </button>
            </div>
        </header>
        
        <!-- Page Content -->
        <div class="page-content">
            <!-- Messages -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?> fade-in-up">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Profile Settings -->
            <div class="card fade-in-up">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user me-2"></i>
                        Profile Settings
                    </h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>
                            Update Profile
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Account Settings -->
            <div class="card fade-in-up">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-cog me-2"></i>
                        Account Settings
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Change Password</h5>
                            <p class="text-muted">Update your account password</p>
                            <button class="btn btn-outline-primary" onclick="showToast('Password change feature coming soon!', 'info')">
                                <i class="fas fa-key me-2"></i>
                                Change Password
                            </button>
                        </div>
                        <div class="col-md-6">
                            <h5>Notification Settings</h5>
                            <p class="text-muted">Manage your notification preferences</p>
                            <button class="btn btn-outline-primary" onclick="showToast('Notification settings coming soon!', 'info')">
                                <i class="fas fa-bell me-2"></i>
                                Manage Notifications
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Data Management -->
            <div class="card fade-in-up">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-database me-2"></i>
                        Data Management
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h6>Export Data</h6>
                            <p class="text-muted small">Download your log entries</p>
                            <button class="btn btn-outline-success btn-sm" onclick="showToast('Export feature coming soon!', 'info')">
                                <i class="fas fa-download me-1"></i>
                                Export
                            </button>
                        </div>
                        <div class="col-md-4">
                            <h6>Backup Account</h6>
                            <p class="text-muted small">Create account backup</p>
                            <button class="btn btn-outline-info btn-sm" onclick="showToast('Backup feature coming soon!', 'info')">
                                <i class="fas fa-cloud-upload-alt me-1"></i>
                                Backup
                            </button>
                        </div>
                        <div class="col-md-4">
                            <h6>Delete Account</h6>
                            <p class="text-muted small">Permanently delete account</p>
                            <button class="btn btn-outline-danger btn-sm" onclick="deleteAccount()">
                                <i class="fas fa-trash me-1"></i>
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/student-scripts.js"></script>
    <script>
        function deleteAccount() {
            if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
                showToast('Account deletion feature coming soon!', 'info');
            }
        }
    </script>
</body>
</html> 