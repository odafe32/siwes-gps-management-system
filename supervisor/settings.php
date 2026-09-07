<?php
session_start();
require_once '../backend/config/db.php';
require_once '../backend/config/session.php';

// Check if user is logged in and has supervisor role
if (!isLoggedIn() || !hasRole('supervisor')) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        try {
            $current_password = trim($_POST['current_password']);
            $new_password = trim($_POST['new_password']);
            $confirm_password = trim($_POST['confirm_password']);
            
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                throw new Exception('All password fields are required');
            }
            
            if ($new_password !== $confirm_password) {
                throw new Exception('New passwords do not match');
            }
            
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user_data = $stmt->fetch();
            
            if (!$user_data || !password_verify($current_password, $user_data['password'])) {
                throw new Exception('Current password is incorrect');
            }
            
            // Update password
            require_once '../backend/models/User.php';
            $result = User::changePassword($pdo, $user_id, $new_password);
            
            if ($result) {
                $message = "Password updated successfully!";
                $messageType = "success";
            } else {
                throw new Exception('Failed to update password');
            }
            
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
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
    <title>Settings - SIWES Supervisor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/supervisor-styles.css">
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
                    <div class="user-name"><?php echo htmlspecialchars($user['full_name'] ?? 'Supervisor'); ?></div>
                    <div class="user-role">Supervisor</div>
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
            
            <!-- Security Settings -->
            <div class="card fade-in-up mb-4">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-shield-alt me-2"></i>
                        Security Settings
                    </h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                        <span class="password-toggle" onclick="togglePassword('current_password')">
                                            <i class="fas fa-eye"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        <span class="password-toggle" onclick="togglePassword('new_password')">
                                            <i class="fas fa-eye"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-check-double"></i></span>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        <span class="password-toggle" onclick="togglePassword('confirm_password')">
                                            <i class="fas fa-eye"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>
                            Update Password
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Notification Settings -->
            <div class="card fade-in-up mb-4">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-bell me-2"></i>
                        Notification Settings
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="emailNotifications" checked>
                                <label class="form-check-label" for="emailNotifications">Email Notifications</label>
                                <p class="text-muted small">Receive email notifications for new log submissions</p>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="reminderNotifications" checked>
                                <label class="form-check-label" for="reminderNotifications">Reminder Notifications</label>
                                <p class="text-muted small">Receive reminders for pending reviews</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="systemNotifications" checked>
                                <label class="form-check-label" for="systemNotifications">System Notifications</label>
                                <p class="text-muted small">Receive notifications about system updates</p>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="studentActivityNotifications">
                                <label class="form-check-label" for="studentActivityNotifications">Student Activity Notifications</label>
                                <p class="text-muted small">Receive notifications about student activities</p>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-primary" onclick="showToast('Notification settings saved!', 'success')">
                        <i class="fas fa-save me-2"></i>
                        Save Notification Settings
                    </button>
                </div>
            </div>
            
            <!-- Account Management -->
            <div class="card fade-in-up">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user-cog me-2"></i>
                        Account Management
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h6>Export Data</h6>
                            <p class="text-muted small">Download your supervision data</p>
                            <button class="btn btn-outline-success btn-sm" onclick="showToast('Export feature coming soon!', 'info')">
                                <i class="fas fa-download me-1"></i>
                                Export
                            </button>
                        </div>
                        <div class="col-md-4">
                            <h6>Account Backup</h6>
                            <p class="text-muted small">Create account backup</p>
                            <button class="btn btn-outline-info btn-sm" onclick="showToast('Backup feature coming soon!', 'info')">
                                <i class="fas fa-cloud-upload-alt me-1"></i>
                                Backup
                            </button>
                        </div>
                        <div class="col-md-4">
                            <h6>Delete Account</h6>
                            <p class="text-muted small">Permanently delete account</p>
                            <button class="btn btn-outline-danger btn-sm" onclick="confirmDeleteAccount()">
                                <i class="fas fa-trash me-1"></i>
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
        <div id="toast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="fas fa-info-circle me-2 text-primary"></i>
                <strong class="me-auto">Notification</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="toastMessage"></div>
        </div>
    </div>

    <!-- Delete Account Modal -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteAccountModalLabel">Confirm Account Deletion</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete your account? This action cannot be undone and all your data will be permanently removed.</p>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="confirmDelete">
                        <label class="form-check-label" for="confirmDelete">
                            I understand that this action is irreversible
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="deleteAccountBtn" disabled>
                        <i class="fas fa-trash me-2"></i>
                        Delete Account
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('sidebar-collapsed');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        });
        
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('sidebar-collapsed');
        });
        
        document.getElementById('sidebarOverlay').addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('collapsed');
            document.getElementById('sidebarOverlay').classList.remove('active');
        });
        
        // Toggle password visibility
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.parentElement.querySelector('.password-toggle i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Show toast notification
        function showToast(message, type = 'info') {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toastMessage');
            const toastHeader = document.querySelector('.toast-header i');
            
            // Set message
            toastMessage.textContent = message;
            
            // Set icon based on type
            toastHeader.className = 'fas me-2';
            switch(type) {
                case 'success':
                    toastHeader.classList.add('fa-check-circle', 'text-success');
                    break;
                case 'error':
                    toastHeader.classList.add('fa-exclamation-circle', 'text-danger');
                    break;
                case 'warning':
                    toastHeader.classList.add('fa-exclamation-triangle', 'text-warning');
                    break;
                default:
                    toastHeader.classList.add('fa-info-circle', 'text-primary');
            }
            
            // Show toast
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
        }
        
        // Delete account confirmation
        function confirmDeleteAccount() {
            const modal = new bootstrap.Modal(document.getElementById('deleteAccountModal'));
            modal.show();
        }
        
        // Enable/disable delete button based on checkbox
        document.getElementById('confirmDelete').addEventListener('change', function() {
            document.getElementById('deleteAccountBtn').disabled = !this.checked;
        });
        
        // Delete account button
        document.getElementById('deleteAccountBtn').addEventListener('click', function() {
            showToast('Account deletion feature coming soon!', 'info');
            bootstrap.Modal.getInstance(document.getElementById('deleteAccountModal')).hide();
        });
        
        // Logout function
        function logout() {
            window.location.href = '../backend/api/auth.php?action=logout';
        }
    </script>
</body>
</html>