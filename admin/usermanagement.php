<?php
session_start();
require_once '../backend/config/db.php';
require_once '../backend/config/session.php';
require_once '../backend/models/User.php';

if (!isLoggedIn() || (!hasRole('admin') && !hasRole('coordinator'))) {
    header('Location: login.php?error=Access denied');
    exit();
}

// Fetch all users except coordinators
$stmt = $pdo->prepare("SELECT * FROM users WHERE role NOT IN ('coordinator', 'admin') ORDER BY id DESC");
$stmt->execute();
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - SIWES Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/admin-styles.css">
    
    <!-- Additional user management styles -->
    <style>
        .user-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
        }
        
        .user-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .user-table {
            font-size: 0.95rem;
        }
        
        .user-table th {
            font-weight: 600;
            color: var(--primary-color);
            padding: 1rem;
        }
        
        .user-table td {
            padding: 1rem;
            vertical-align: middle;
        }
        
        .role-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.85rem;
            text-transform: capitalize;
        }
        
        .role-badge.admin {
            background-color: var(--primary-color);
            color: white;
        }
        
        
        .role-badge.supervisor {
            background-color: var(--accent-color);
            color: white;
        }
        
        .role-badge.student {
            background-color: var(--warning-color);
            color: var(--dark-color);
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .action-btn {
            padding: 0.4rem 0.75rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.85rem;
        }
        
        .edit-btn {
            background-color: var(--info-color);
            color: white;
        }
        
        .delete-btn {
            background-color: var(--danger-color);
            color: white;
        }
        
        .add-user-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .add-user-btn:hover {
            background: var(--secondary-color);
            transform: translateY(-3px);
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .avatar-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            background-color: var(--light-color);
            color: var(--primary-color);
        }
        
        @media (max-width: 992px) {
            .user-table {
                font-size: 0.85rem;
            }
            
            .user-table th, .user-table td {
                padding: 0.75rem 0.5rem;
            }
            
            .role-badge {
                padding: 0.35rem 0.75rem;
                font-size: 0.75rem;
            }
        }
        
        @media (max-width: 768px) {
            .table-responsive {
                overflow-x: auto;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
    </style>
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
                <h1 class="page-title">User Management</h1>
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
            <!-- Page Header with Add User Button -->
            <div class="page-header fade-in-up">
                <div>
                    <h2 class="mb-1">User Management</h2>
                    <p class="text-muted mb-0">Manage all system users</p>
                </div>
                <button class="add-user-btn" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-user-plus"></i>
                    Add New User
                </button>
            </div>
            
            <!-- Users Table Card -->
            <div class="user-card fade-in-up">
                <div class="card-header bg-white p-4 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-primary">
                            <i class="fas fa-users me-2"></i>
                            All Users
                        </h5>
                        <div class="d-flex gap-2">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search users..." id="userSearch">
                                <button class="btn btn-outline-secondary" type="button">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown">
                                    <i class="fas fa-filter me-1"></i> Filter
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="filterDropdown">
                                    <li><a class="dropdown-item" href="#">All Users</a></li>
                                    <li><a class="dropdown-item" href="#">Admins</a></li>
                                    <li><a class="dropdown-item" href="#">Supervisors</a></li>
                                    <li><a class="dropdown-item" href="#">Students</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 user-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Department</th>
                                <th>Institution</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['id']) ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle bg-light text-primary me-2">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div>
                                            <?php 
                                                if (!empty($user['name'])) {
                                                    echo htmlspecialchars($user['name']);
                                                } elseif (!empty($user['username'])) {
                                                    echo htmlspecialchars($user['username']);
                                                } else {
                                                    echo htmlspecialchars($user['email']);
                                                }
                                            ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td>
                                    <span class="role-badge <?= htmlspecialchars($user['role']) ?>">
                                        <?= htmlspecialchars($user['role']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($user['department'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($user['institution'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($user['created_at']) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn edit-btn" title="Edit User" data-user-id="<?= $user['id'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="action-btn delete-btn" title="Delete User" data-user-id="<?= $user['id'] ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white p-3 border-top d-flex justify-content-between align-items-center">
                    <div>Showing <span class="fw-bold"><?= count($users) ?></span> users</div>
                    <nav aria-label="Page navigation">
                        <ul class="pagination mb-0">
                            <li class="page-item disabled">
                                <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
                            </li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                            <li class="page-item"><a class="page-link" href="#">3</a></li>
                            <li class="page-item">
                                <a class="page-link" href="#">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addUserForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="" selected disabled>Select Role</option>
                                    <option value="admin">Admin</option>
                                    <option value="supervisor">Supervisor</option>
                                    <option value="student">Student</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="department" class="form-label">Department</label>
                                <input type="text" class="form-control" id="department" name="department">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="institution" class="form-label">Institution</label>
                                <input type="text" class="form-control" id="institution" name="institution">
                            </div>
                            <div class="col-md-6">
                                <label for="matric_number" class="form-label">Matric Number (For Students)</label>
                                <input type="text" class="form-control" id="matric_number" name="matric_number">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveUserBtn">Save User</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        });
        
        // Close sidebar when clicking overlay
        document.getElementById('sidebarOverlay')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('sidebarOverlay').classList.remove('active');
        });
        
        // Logout function is defined in admin-scripts.js
        
        // User search functionality
        document.getElementById('userSearch')?.addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('tbody tr');
            
            tableRows.forEach(row => {
                const name = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                const email = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                const role = row.querySelector('td:nth-child(4)').textContent.toLowerCase();
                const department = row.querySelector('td:nth-child(5)').textContent.toLowerCase();
                
                if (name.includes(searchValue) || email.includes(searchValue) || 
                    role.includes(searchValue) || department.includes(searchValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // Add user form submission
        document.getElementById('saveUserBtn')?.addEventListener('click', function() {
            // Form validation and submission logic would go here
            alert('User creation functionality will be implemented soon!');
        });
        
        // Edit and delete button event listeners
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                alert(`Edit user with ID: ${userId} - This functionality will be implemented soon!`);
            });
        });
        
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                    alert(`Delete user with ID: ${userId} - This functionality will be implemented soon!`);
                }
            });
        });
    </script>
</body>
</html>
