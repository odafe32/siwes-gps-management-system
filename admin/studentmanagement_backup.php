<?php
session_start();
require_once '../backend/config/db.php';
require_once '../backend/config/session.php';
require_once '../backend/models/User.php';

if (!isLoggedIn() || (!hasRole('admin') && !hasRole('coordinator'))) {
    header('Location: login.php?error=Access denied');
    exit();
}

// Fetch all students
$stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'student' ORDER BY id DESC");
$stmt->execute();
$students = $stmt->fetchAll();

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
    <title>Student Management - SIWES Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/admin-styles.css">
    
    <!-- Additional student management styles -->
    <style>
        .student-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
        }
        
        .student-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .student-table {
            font-size: 0.95rem;
        }
        
        .student-table th {
            font-weight: 600;
            color: var(--primary-color);
            padding: 1rem;
        }
        
        .student-table td {
            padding: 1rem;
            vertical-align: middle;
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
        
        .add-student-btn {
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
        
        .add-student-btn:hover {
            background: var(--secondary-color);
            transform: translateY(-3px);
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .upload-btn {
            background: var(--accent-color);
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
        
        .upload-btn:hover {
            background: var(--secondary-color);
            transform: translateY(-3px);
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
        }
        
        @media (max-width: 992px) {
            .student-table {
                font-size: 0.85rem;
            }
            
            .student-table th, .student-table td {
                padding: 0.75rem 0.5rem;
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
            
            .btn-group {
                width: 100%;
            }
            
            .add-student-btn, .upload-btn {
                width: 100%;
                justify-content: center;
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
                <h1 class="page-title">Student Management</h1>
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
                <h2>Manage Students</h2>
                <div class="btn-group">
                    <button class="add-student-btn" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                        <i class="fas fa-user-plus"></i>
                        Add Student
                    </button>
                    <button class="upload-btn" data-bs-toggle="modal" data-bs-target="#uploadStudentsModal">
                        <i class="fas fa-file-upload"></i>
                        Upload Student List
                    </button>
                </div>
            </div>
            
            <div class="card student-card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table student-table">
                            <thead>
                                <tr>
                                    <th class="id-column">ID</th>
                                    <th class="name-column">Name</th>
                                    <th class="email-column">Email</th>
                                    <th class="matric-column">Matric Number</th>
                                    <th class="department-column">Department</th>
                                    <th class="institution-column">Institution</th>
                                    <th class="level-column">Level</th>
                                    <th class="phone-column">Phone</th>
                                    <th class="actions-column">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?= htmlspecialchars($student['id']) ?></td>
                                    <td><?= htmlspecialchars($student['username'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($student['email']) ?></td>
                                    <td><?= htmlspecialchars($student['matric_number'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($student['department'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($student['institution'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($student['level'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($student['phone'] ?? 'N/A') ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="action-btn edit-btn" data-id="<?= $student['id'] ?>" onclick="editStudent(<?= $student['id'] ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="action-btn delete-btn" data-id="<?= $student['id'] ?>" onclick="deleteStudent(<?= $student['id'] ?>)">
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
                <div class="card-footer bg-white p-3 border-top d-flex justify-content-between align-items-center">
                    <div>Showing <span class="fw-bold"><?= count($students) ?></span> students</div>
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
    
    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addStudentModalLabel">Add New Student</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addStudentForm">
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
                                <label for="matric_number" class="form-label">Matric Number</label>
                                <input type="text" class="form-control" id="matric_number" name="matric_number" required>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="text" class="form-control" id="phone" name="phone" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="department" class="form-label">Department</label>
                                <input type="text" class="form-control" id="department" name="department" required>
                            </div>
                            <div class="col-md-6">
                                <label for="institution" class="form-label">Institution</label>
                                <input type="text" class="form-control" id="institution" name="institution" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="level" class="form-label">Level</label>
                                <select class="form-select" id="level" name="level" required>
                                    <option value="" selected disabled>Select Level</option>
                                    <option value="100">100 Level</option>
                                    <option value="200">200 Level</option>
                                    <option value="300">300 Level</option>
                                    <option value="400">400 Level</option>
                                    <option value="500">500 Level</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveStudentBtn">Save Student</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Upload Students Modal -->
    <div class="modal fade" id="uploadStudentsModal" tabindex="-1" aria-labelledby="uploadStudentsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="uploadStudentsModalLabel">Upload Student List</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="uploadStudentsForm" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="csvFile" class="form-label">CSV File</label>
                            <input type="file" class="form-control" id="csvFile" name="csvFile" accept=".csv" required>
                            <div class="form-text">Upload a CSV file with the following columns: Name, Matric Number, Email, Phone, Department, Institution, Level</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sample Format</label>
                            <pre class="bg-light p-2 rounded">Name,Matric Number,Email,Phone,Department,Institution,Level
John Doe,2021/123456,john@example.com,08012345678,Computer Science,University of Nigeria,400</pre>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="generatePasswords" name="generatePasswords" checked>
                                <label class="form-check-label" for="generatePasswords">
                                    Generate random passwords for students
                                </label>
                            </div>
                            <div class="form-text">If checked, system will generate random passwords for each student. Otherwise, matric number will be used as password.</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="uploadStudentsBtn">Upload</button>
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
        
                // Bulk operations functionality
        let selectedStudents = [];
        
        // Select all functionality
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateSelectedCount();
        });
        
        document.getElementById('selectAllHeader').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            document.getElementById('selectAll').checked = this.checked;
            updateSelectedCount();
        });
        
        // Individual checkbox change
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('student-checkbox')) {
                updateSelectedCount();
            }
        });
        
        function updateSelectedCount() {
            const checkboxes = document.querySelectorAll('.student-checkbox:checked');
            const count = checkboxes.length;
            document.getElementById('selectedCount').textContent = count;
            
            const bulkBtn = document.getElementById('bulkActionsBtn');
            if (count > 0) {
                bulkBtn.disabled = false;
                bulkBtn.classList.remove('btn-outline-danger');
                bulkBtn.classList.add('btn-danger');
            } else {
                bulkBtn.disabled = true;
                bulkBtn.classList.remove('btn-danger');
                bulkBtn.classList.add('btn-outline-danger');
            }
        }
        
        function bulkAction(action) {
            const checkboxes = document.querySelectorAll('.student-checkbox:checked');
            const selectedIds = Array.from(checkboxes).map(cb => cb.value);
            
            if (selectedIds.length === 0) {
                alert('Please select at least one student');
                return;
            }
            
            let confirmMessage = '';
            let actionText = '';
            
            switch(action) {
                case 'activate':
                    confirmMessage = `Are you sure you want to activate ${selectedIds.length} selected students?`;
                    actionText = 'activate';
                    break;
                case 'deactivate':
                    confirmMessage = `Are you sure you want to deactivate ${selectedIds.length} selected students?`;
                    actionText = 'deactivate';
                    break;
                case 'delete':
                    confirmMessage = `Are you sure you want to delete ${selectedIds.length} selected students? This action cannot be undone.`;
                    actionText = 'delete';
                    break;
                case 'export':
                    exportSelectedStudents(selectedIds);
                    return;
            }
            
            if (confirm(confirmMessage)) {
                fetch('../backend/api/admin.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'bulk_' + actionText,
                        student_ids: selectedIds
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = 'studentmanagement.php?success=' + encodeURIComponent(data.message);
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
        
        function exportSelectedStudents(selectedIds) {
            // Get selected student data
            const selectedRows = [];
            selectedIds.forEach(id => {
                const row = document.querySelector(`input[value="${id}"]`).closest('tr');
                const cells = row.querySelectorAll('td');
                selectedRows.push({
                    id: cells[1].textContent,
                    name: cells[2].textContent,
                    email: cells[3].textContent,
                    matric: cells[4].textContent,
                    department: cells[5].textContent,
                    institution: cells[6].textContent,
                    level: cells[7].textContent,
                    status: cells[8].textContent.trim()
                });
            });
            
            // Create CSV content
            let csvContent = 'ID,Name,Email,Matric Number,Department,Institution,Level,Status\n';
            selectedRows.forEach(row => {
                csvContent += `${row.id},"${row.name}","${row.email}","${row.matric}","${row.department}","${row.institution}","${row.level}","${row.status}"\n`;
            });
            
            // Download CSV
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', `students_export_${new Date().toISOString().split('T')[0]}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        // Logout function is defined in admin-scripts.js
        
        // Add student
        document.getElementById('saveStudentBtn').addEventListener('click', function() {
            const form = document.getElementById('addStudentForm');
            const formData = new FormData(form);
            formData.append('action', 'add_student');
            formData.append('role', 'student');
            
            fetch('../backend/api/admin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'studentmanagement.php?success=' + encodeURIComponent('Student added successfully');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
        
        // Upload students
        document.getElementById('uploadStudentsBtn').addEventListener('click', function() {
            const form = document.getElementById('uploadStudentsForm');
            const formData = new FormData(form);
            formData.append('action', 'upload_students');
            
            fetch('../backend/api/admin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'studentmanagement.php?success=' + encodeURIComponent(data.message);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
        
        // Edit student
        function editStudent(id) {
            // Redirect to edit page or show edit modal
            alert('Edit student with ID: ' + id + ' (Functionality to be implemented)');
        }
        
        // Delete student
        function deleteStudent(id) {
            if (confirm('Are you sure you want to delete this student?')) {
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
                        window.location.href = 'studentmanagement.php?success=' + encodeURIComponent('Student deleted successfully');
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











