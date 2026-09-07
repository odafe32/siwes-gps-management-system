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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        try {
            $name = trim($_POST['full_name']);
            $email = trim($_POST['email']);
            $department = trim($_POST['department']);
            $institution = trim($_POST['institution']);
            
            if (empty($name) || empty($email)) {
                throw new Exception('Name and email are required');
            }
            
            $data = [
                'name' => $name,
                'email' => $email,
                'department' => $department,
                'institution' => $institution
            ];
            
            require_once '../backend/models/User.php';
            $result = User::updateProfile($pdo, $user_id, $data);
            
            if ($result) {
                $_SESSION['name'] = $name;
                $message = "Profile updated successfully!";
                $messageType = "success";
            } else {
                throw new Exception('Failed to update profile');
            }
            
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

// Get supervision statistics
try {
    // Count students assigned to this supervisor
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE supervisor_id = ? AND role = 'student'");
    $stmt->execute([$user_id]);
    $totalStudents = $stmt->fetch()['total'] ?? 0;

    // Count log entries pending review
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM log_entries
                          JOIN users ON log_entries.student_id = users.id
                          WHERE users.supervisor_id = ? AND log_entries.status = 'pending'");
    $stmt->execute([$user_id]);
    $pendingReviews = $stmt->fetch()['count'] ?? 0;

    // Count approved log entries
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM log_entries
                          JOIN users ON log_entries.student_id = users.id
                          WHERE users.supervisor_id = ? AND log_entries.status = 'approved'");
    $stmt->execute([$user_id]);
    $approvedEntries = $stmt->fetch()['count'] ?? 0;
    
} catch (PDOException $e) {
    $totalStudents = 0;
    $pendingReviews = 0;
    $approvedEntries = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - SIWES Supervisor</title>
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
                <h1 class="page-title">Profile</h1>
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
            
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h5 class="card-title mb-0">My Students</h5>
                                <div class="stats-icon bg-info">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                            <h3 class="stats-number"><?php echo $totalStudents; ?></h3>
                            <p class="text-muted">Students under supervision</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h5 class="card-title mb-0">Pending Reviews</h5>
                                <div class="stats-icon bg-warning">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                            <h3 class="stats-number"><?php echo $pendingReviews; ?></h3>
                            <p class="text-muted">Logs awaiting your review</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h5 class="card-title mb-0">Approved Logs</h5>
                                <div class="stats-icon bg-success">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                            <h3 class="stats-number"><?php echo $approvedEntries; ?></h3>
                            <p class="text-muted">Total approved log entries</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Profile Information -->
            <div class="card fade-in-up mb-4">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user-circle me-2"></i>
                        Profile Information
                    </h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="full_name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                           value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
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
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="department" class="form-label">Department</label>
                                    <input type="text" class="form-control" id="department" name="department" 
                                           value="<?php echo htmlspecialchars($user['department'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="institution" class="form-label">Institution</label>
                                    <input type="text" class="form-control" id="institution" name="institution" 
                                           value="<?php echo htmlspecialchars($user['institution'] ?? ''); ?>">
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('sidebar-collapsed');
        });
        
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('sidebar-collapsed');
        });
        
        // Mobile sidebar overlay
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('sidebarOverlay').classList.toggle('active');
        });
        
        document.getElementById('sidebarOverlay').addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('collapsed');
            document.getElementById('sidebarOverlay').classList.remove('active');
        });
        
        // Logout function
        function logout() {
            window.location.href = '../backend/auth/logout.php';
        }
    </script>
</body>
</html>