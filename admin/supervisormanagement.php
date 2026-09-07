<?php
session_start();
require_once '../backend/config/db.php';
require_once '../backend/config/session.php';

if (!isLoggedIn() || (!hasRole('admin') && !hasRole('coordinator'))) {
    header('Location: login.php?error=Access denied');
    exit();
}

// Fetch all supervisors
$stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'supervisor' ORDER BY id DESC");
$stmt->execute();
$supervisors = $stmt->fetchAll();

// Handle messages
$message = '';
$messageType = '';
if (isset($_GET['success'])) {
    $message = $_GET['success'];
    $messageType = 'success';
} else if (isset($_GET['error'])) {
    $message = $_GET['error'];
    $messageType = 'danger';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisor Management - SIWES Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/admin-styles.css">
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
                <h1 class="page-title">Supervisor Management</h1>
            </div>
            
            <div class="user-menu">
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></div>
                    <div class="user-role"><?php echo ucfirst($_SESSION['role'] ?? 'admin'); ?></div>
                </div>
                <button class="logout-btn" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </button>
            </div>
        </header>
        
        <!-- Page Content -->
        <div class="page-content">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="page-header">
                <h2>Manage Supervisors</h2>
                <div class="btn-group">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSupervisorModal">
                        <i class="fas fa-user-plus"></i>
                        Add Supervisor
                    </button>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Department</th>
                                    <th>Institution</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($supervisors as $supervisor): ?>
                                <tr>
                                    <td><?= htmlspecialchars($supervisor['id']) ?></td>
                                    <td><?= htmlspecialchars($supervisor['username'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($supervisor['email']) ?></td>
                                    <td><?= htmlspecialchars($supervisor['department'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($supervisor['institution'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($supervisor['phone'] ?? 'N/A') ?></td>
                                    <td>
                                        <span class="badge bg-<?= ($supervisor['is_active'] ?? 1) ? 'success' : 'danger' ?>">
                                            <?= ($supervisor['is_active'] ?? 1) ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-primary" onclick="editSupervisor(<?= $supervisor['id'] ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" onclick="deleteSupervisor(<?= $supervisor['id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/admin-scripts.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        });
        
        // Edit supervisor
        function editSupervisor(id) {
            alert('Edit supervisor with ID: ' + id + ' (Functionality to be implemented)');
        }
        
        // Delete supervisor
        function deleteSupervisor(id) {
            if (confirm('Are you sure you want to delete this supervisor?')) {
                fetch('../backend/api/admin.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'delete_user',
                        user_id: id
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = 'supervisormanagement.php?success=' + encodeURIComponent('Supervisor deleted successfully');
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
            }
        }
    </script>
</body>
</html>


