<?php
session_start();
require_once '../backend/config/db.php';
require_once '../backend/config/session.php';
require_once '../backend/models/User.php';

if (!isLoggedIn() || (!hasRole('admin') && !hasRole('coordinator'))) {
    header('Location: login.php?error=Access denied');
    exit();
}

// Check if level column exists
$levelColumnExists = false;
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'level'");
    $levelColumnExists = $stmt->rowCount() > 0;
} catch (Exception $e) {
    $levelColumnExists = false;
}

// Handle search and filtering
$search = $_GET['search'] ?? '';
$department = $_GET['department'] ?? '';
$level = $_GET['level'] ?? '';
$status = $_GET['status'] ?? '';

// Build query with filters
$whereConditions = ["role = 'student'"];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(username LIKE ? OR email LIKE ? OR matric_number LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($department)) {
    $whereConditions[] = "department = ?";
    $params[] = $department;
}

if (!empty($level) && $levelColumnExists) {
    $whereConditions[] = "level = ?";
    $params[] = $level;
}

if (!empty($status)) {
    if ($status === 'active') {
        $whereConditions[] = "is_active = 1";
    } elseif ($status === 'inactive') {
        $whereConditions[] = "is_active = 0";
    }
}

$whereClause = implode(' AND ', $whereConditions);
$query = "SELECT * FROM users WHERE $whereClause ORDER BY id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Get unique departments for filters
$deptStmt = $pdo->query("SELECT DISTINCT department FROM users WHERE role = 'student' AND department IS NOT NULL AND department != ''");
$departments = $deptStmt->fetchAll(PDO::FETCH_COLUMN);

// Get unique levels for filters (only if column exists)
$levels = [];
if ($levelColumnExists) {
    $levelStmt = $pdo->query("SELECT DISTINCT level FROM users WHERE role = 'student' AND level IS NOT NULL AND level != ''");
    $levels = $levelStmt->fetchAll(PDO::FETCH_COLUMN);
}

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

// Set current page for sidebar
$current_page = 'studentmanagement';
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
    
    <style>
        .enhanced-student-management {
            background: #f5f7fa;
            min-height: 100vh;
        }
        
        .page-header {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        
        .btn-primary-custom {
            background: var(--primary-color);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary-custom:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(26, 77, 46, 0.3);
        }
        
        .search-filters {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        
        .filter-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: 1rem;
            align-items: end;
        }
        
        .search-input {
            position: relative;
        }
        
        .search-input i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        .search-input input {
            padding-left: 2.5rem;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .search-input input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(26, 77, 46, 0.25);
        }
        
        .filter-select {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .filter-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(26, 77, 46, 0.25);
        }
        
        .bulk-actions {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            display: none;
            align-items: center;
            justify-content: space-between;
        }
        
        .bulk-actions.show {
            display: flex;
        }
        
        .bulk-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .bulk-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .student-table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }
        
        .table-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .student-table {
            margin: 0;
        }
        
        .student-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: var(--primary-color);
            padding: 1rem;
            border: none;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .student-table td {
            padding: 1rem;
            vertical-align: middle;
            border-color: #f0f0f0;
        }
        
        .student-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-active {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        
        .status-inactive {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        .completion-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .completion-bar {
            width: 60px;
            height: 6px;
            background: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .completion-fill {
            height: 100%;
            background: var(--success-color);
            transition: width 0.3s ease;
        }
        
        .completion-text {
            font-size: 0.8rem;
            color: #6c757d;
            font-weight: 500;
        }
        
        .action-buttons-cell {
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
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .edit-btn {
            background-color: var(--info-color);
            color: white;
        }
        
        .edit-btn:hover {
            background-color: #0d6efd;
            transform: translateY(-1px);
        }
        
        .delete-btn {
            background-color: var(--danger-color);
            color: white;
        }
        
        .delete-btn:hover {
            background-color: #c82333;
            transform: translateY(-1px);
        }
        
        .view-btn {
            background-color: var(--success-color);
            color: white;
        }
        
        .view-btn:hover {
            background-color: #1e7e34;
            transform: translateY(-1px);
        }
        
        .table-footer {
            padding: 1rem 1.5rem;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .pagination-info {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .pagination {
            margin: 0;
        }
        
        .page-link {
            color: var(--primary-color);
            border-color: #e9ecef;
            padding: 0.5rem 0.75rem;
        }
        
        .page-link:hover {
            color: var(--secondary-color);
            background-color: #f8f9fa;
            border-color: #e9ecef;
        }
        
        .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }
        
        .empty-state h3 {
            margin-bottom: 0.5rem;
            color: #495057;
        }
        
        @media (max-width: 1200px) {
            .filter-row {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }
        
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .action-buttons {
                justify-content: center;
            }
            
            .bulk-actions {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
            
            .bulk-info {
                justify-content: center;
            }
            
            .bulk-buttons {
                justify-content: center;
            }
            
            .table-header {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
            
            .table-footer {
                flex-direction: column;
                gap: 1rem;
                align-items: center;
            }
        }
    </style>
</head>
<body class="enhanced-student-management">
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
            
            <!-- Page Header -->
            <div class="page-header">
                <h2 class="page-title">Manage Students</h2>
                <div class="action-buttons">
                    <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                        <i class="fas fa-user-plus"></i>
                        Add Student
                    </button>
                    <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#uploadStudentsModal">
                        <i class="fas fa-file-upload"></i>
                        Upload Students
                    </button>
                    <button class="btn-primary-custom" onclick="exportStudents()">
                        <i class="fas fa-download"></i>
                        Export Data
                    </button>
                </div>
            </div>
            
            <!-- Search and Filters -->
            <div class="search-filters">
                <form method="GET" class="filter-form">
                    <div class="filter-row">
                        <div class="search-input">
                            <i class="fas fa-search"></i>
                            <input type="text" class="form-control" name="search" placeholder="Search by name, email, or matric number..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <select class="form-select filter-select" name="department">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= htmlspecialchars($dept) ?>" <?= $department === $dept ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($levelColumnExists): ?>
                        <select class="form-select filter-select" name="level">
                            <option value="">All Levels</option>
                            <?php foreach ($levels as $lvl): ?>
                                <option value="<?= htmlspecialchars($lvl) ?>" <?= $level === $lvl ? 'selected' : '' ?>>
                                    Level <?= htmlspecialchars($lvl) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php else: ?>
                        <select class="form-select filter-select" name="level" disabled>
                            <option value="">Level (N/A)</option>
                        </select>
                        <?php endif; ?>
                        <select class="form-select filter-select" name="status">
                            <option value="">All Status</option>
                            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i>
                            Filter
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Bulk Actions -->
            <div class="bulk-actions" id="bulkActions">
                <div class="bulk-info">
                    <span id="selectedCount">0</span> students selected
                    <button class="btn btn-sm btn-outline-secondary" onclick="clearSelection()">Clear Selection</button>
                </div>
                <div class="bulk-buttons">
                    <button class="btn btn-sm btn-success" onclick="bulkAction('activate')">
                        <i class="fas fa-check"></i> Activate
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="bulkAction('deactivate')">
                        <i class="fas fa-times"></i> Deactivate
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="bulkAction('delete')">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                    <button class="btn btn-sm btn-info" onclick="bulkAction('export')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
            
            <!-- Student Table -->
            <div class="student-table-container">
                <div class="table-header">
                    <h3 class="table-title">Student Records</h3>
                    <div class="table-actions">
                        <button class="btn btn-sm btn-outline-primary" onclick="refreshTable()">
                            <i class="fas fa-sync-alt"></i>
                            Refresh
                        </button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table student-table">
                        <thead>
                            <tr>
                                <th width="50">
                                    <input type="checkbox" id="selectAll" class="form-check-input">
                                </th>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Matric Number</th>
                                <th>Department</th>
                                <?php if ($levelColumnExists): ?>
                                <th>Level</th>
                                <?php endif; ?>
                                <th>Status</th>
                                <th>Completion</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="<?= $levelColumnExists ? '10' : '9' ?>" class="empty-state">
                                        <i class="fas fa-users"></i>
                                        <h3>No students found</h3>
                                        <p>No students match your current filters. Try adjusting your search criteria.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $student): ?>
                                    <?php
                                    // Calculate completion percentage
                                    $completion = 0;
                                    $totalFields = 6;
                                    $filledFields = 0;
                                    
                                    if (!empty($student['full_name'])) $filledFields++;
                                    if (!empty($student['email'])) $filledFields++;
                                    if (!empty($student['matric_number'])) $filledFields++;
                                    if (!empty($student['department'])) $filledFields++;
                                    if (!empty($student['institution'])) $filledFields++;
                                    if (!empty($student['workplace_name'])) $filledFields++;
                                    
                                    $completion = round(($filledFields / $totalFields) * 100);
                                    ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="form-check-input student-checkbox" value="<?= $student['id'] ?>">
                                        </td>
                                        <td><?= htmlspecialchars($student['id']) ?></td>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($student['full_name'] ?? 'N/A') ?></div>
                                        </td>
                                        <td><?= htmlspecialchars($student['email']) ?></td>
                                        <td><?= htmlspecialchars($student['matric_number'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($student['department'] ?? 'N/A') ?></td>
                                        <?php if ($levelColumnExists): ?>
                                        <td><?= htmlspecialchars($student['level'] ?? 'N/A') ?></td>
                                        <?php endif; ?>
                                        <td>
                                            <span class="status-badge status-<?= ($student['is_active'] ?? 1) ? 'active' : 'inactive' ?>">
                                                <?= ($student['is_active'] ?? 1) ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="completion-indicator">
                                                <div class="completion-bar">
                                                    <div class="completion-fill" style="width: <?= $completion ?>%"></div>
                                                </div>
                                                <span class="completion-text"><?= $completion ?>%</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="action-buttons-cell">
                                                <button class="action-btn view-btn" onclick="viewStudent(<?= $student['id'] ?>)" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="action-btn edit-btn" onclick="editStudent(<?= $student['id'] ?>)" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="action-btn delete-btn" onclick="deleteStudent(<?= $student['id'] ?>)" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="table-footer">
                    <div class="pagination-info">
                        Showing <strong><?= count($students) ?></strong> of <strong><?= count($students) ?></strong> students
                    </div>
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <li class="page-item disabled">
                                <a class="page-link" href="#" tabindex="-1">Previous</a>
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
                                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="matric_number" class="form-label">Matric Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="matric_number" name="matric_number" required>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="department" class="form-label">Department <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="department" name="department" required>
                            </div>
                            <?php if ($levelColumnExists): ?>
                            <div class="col-md-6">
                                <label for="level" class="form-label">Level <span class="text-danger">*</span></label>
                                <select class="form-select" id="level" name="level" required>
                                    <option value="">Select Level</option>
                                    <option value="100">100 Level</option>
                                    <option value="200">200 Level</option>
                                    <option value="300">300 Level</option>
                                    <option value="400">400 Level</option>
                                    <option value="500">500 Level</option>
                                </select>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="institution" class="form-label">Institution <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="institution" name="institution" required>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitAddStudent()">Add Student</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Upload Students Modal -->
    <div class="modal fade" id="uploadStudentsModal" tabindex="-1" aria-labelledby="uploadStudentsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="uploadStudentsModalLabel">Upload Student List</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Instructions:</strong> Upload a CSV file with student data. 
                        <a href="#" id="downloadTemplate" class="alert-link">Download template</a>
                    </div>
                    <form id="uploadStudentsForm" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="csvFile" class="form-label">CSV File <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="csvFile" name="csvFile" accept=".csv" required>
                            <div class="form-text">Only CSV files are allowed. Maximum file size: 5MB</div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="skipDuplicates" name="skipDuplicates">
                                <label class="form-check-label" for="skipDuplicates">
                                    Skip duplicate entries (based on email)
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="submitUploadStudents()">Upload Students</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Selection management
        let selectedStudents = new Set();
        
        // Select all functionality
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
                if (this.checked) {
                    selectedStudents.add(checkbox.value);
                } else {
                    selectedStudents.delete(checkbox.value);
                }
            });
            updateBulkActions();
        });
        
        // Individual checkbox handling
        document.querySelectorAll('.student-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    selectedStudents.add(this.value);
                } else {
                    selectedStudents.delete(this.value);
                }
                updateBulkActions();
                updateSelectAll();
            });
        });
        
        function updateBulkActions() {
            const bulkActions = document.getElementById('bulkActions');
            const selectedCount = document.getElementById('selectedCount');
            
            selectedCount.textContent = selectedStudents.size;
            
            if (selectedStudents.size > 0) {
                bulkActions.classList.add('show');
            } else {
                bulkActions.classList.remove('show');
            }
        }
        
        function updateSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.student-checkbox');
            const checkedCount = document.querySelectorAll('.student-checkbox:checked').length;
            
            selectAll.checked = checkedCount === checkboxes.length;
            selectAll.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
        }
        
        function clearSelection() {
            selectedStudents.clear();
            document.querySelectorAll('.student-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });
            document.getElementById('selectAll').checked = false;
            updateBulkActions();
        }
        
        // Bulk actions
        function bulkAction(action) {
            if (selectedStudents.size === 0) {
                alert('Please select students first');
                return;
            }
            
            const actionText = {
                'activate': 'activate',
                'deactivate': 'deactivate',
                'delete': 'delete',
                'export': 'export'
            };
            
            if (confirm(`Are you sure you want to ${actionText[action]} ${selectedStudents.size} selected students?`)) {
                // Implement bulk action API call
                console.log(`Bulk ${action}:`, Array.from(selectedStudents));
                // Add actual API implementation here
            }
        }
        
        // Student actions
        function viewStudent(id) {
            // Implement view student details
            console.log('View student:', id);
        }
        
        function editStudent(id) {
            // Implement edit student
            console.log('Edit student:', id);
        }
        
        function deleteStudent(id) {
            if (confirm('Are you sure you want to delete this student?')) {
                // Implement delete student API call
                console.log('Delete student:', id);
            }
        }
        
        // Form submissions
        function submitAddStudent() {
            const form = document.getElementById('addStudentForm');
            const formData = new FormData(form);
            formData.append('action', 'add_student');
            
            // Show loading state
            const submitBtn = document.querySelector('#addStudentModal .btn-primary');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Adding...';
            submitBtn.disabled = true;
            
            fetch('../backend/api/admin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    alert('Student added successfully!');
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addStudentModal'));
                    modal.hide();
                    // Reset form
                    form.reset();
                    // Refresh page to show new student
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding the student');
            })
            .finally(() => {
                // Reset button state
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        }
        
        function submitUploadStudents() {
            const form = document.getElementById('uploadStudentsForm');
            const formData = new FormData(form);
            
            // Add API call here
            console.log('Upload students:', formData);
        }
        
        function exportStudents() {
            // Implement export functionality
            console.log('Export students');
        }
        
        function refreshTable() {
            location.reload();
        }
        
        // Download template
        document.getElementById('downloadTemplate').addEventListener('click', function(e) {
            e.preventDefault();
            const csvContent = "Name,Matric Number,Email,Phone,Department,Institution,Level\n" +
                              "John Doe,2021/123456,john@example.com,08012345678,Computer Science,University of Nigeria,400\n" +
                              "Mike Johnson,2021/345678,mike@example.com,08055555555,Mechanical Engineering,University of Nigeria,500";
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', 'student_template.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
        
        // Logout function
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }
    </script>
</body>
</html>
