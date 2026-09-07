<?php
require_once '../backend/config/session.php';
requireRole('supervisor');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assigned Students - SIWES Supervisor Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="assets/supervisor-styles.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <!-- Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Assigned Students</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="input-group">
                            <input type="text" id="searchInput" class="form-control" placeholder="Search students...">
                            <button class="btn btn-outline-secondary" type="button">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card shadow-sm bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Students</h5>
                                <h2 class="card-text" id="totalStudents">0</h2>
                                <p class="card-text"><small>Students assigned to you</small></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Active Students</h5>
                                <h2 class="card-text" id="activeStudents">0</h2>
                                <p class="card-text"><small>Students with log entries</small></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Inactive Students</h5>
                                <h2 class="card-text" id="inactiveStudents">0</h2>
                                <p class="card-text"><small>Students with no log entries</small></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Students Table -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Student List</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Matric Number</th>
                                        <th>Department</th>
                                        <th>Institution</th>
                                        <th>Pending Logs</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="studentsTableBody">
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            <div class="d-flex justify-content-center">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Student Details Modal -->
    <div class="modal fade" id="studentDetailsModal" tabindex="-1" aria-labelledby="studentDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="studentDetailsModalLabel">Student Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card shadow-sm mb-3">
                                <div class="card-header bg-white">
                                    <h6 class="card-title mb-0">Personal Information</h6>
                                </div>
                                <div class="card-body" id="studentPersonalInfo">
                                    <div class="d-flex justify-content-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card shadow-sm mb-3">
                                <div class="card-header bg-white">
                                    <h6 class="card-title mb-0">SIWES Information</h6>
                                </div>
                                <div class="card-body" id="studentSiwesInfo">
                                    <div class="d-flex justify-content-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">Log Entries Summary</h6>
                        </div>
                        <div class="card-body" id="studentLogSummary">
                            <div class="d-flex justify-content-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="#" id="viewLogsBtn" class="btn btn-primary">View All Logs</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Toast Container for Notifications -->
    <div id="toast-container" class="position-fixed top-0 end-0 p-3" style="z-index: 1050;"></div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/supervisor-scripts.js"></script>
    <script>
        let students = [];
        let currentStudentId = null;
        
        // Load students data
        function loadStudents() {
            fetch('../backend/api/supervisor.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_assigned_students' })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    students = data.students;
                    displayStudents(students);
                    updateStatistics(data);
                } else {
                    showToast("Error loading students: " + (data.message || "Unknown error"), "error");
                }
            })
            .catch(error => {
                console.error('Error loading students:', error);
                showToast("Network error. Please try again later.", "error");
                document.getElementById('studentsTableBody').innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center">
                            <div class="alert alert-danger mb-0">
                                Failed to load students. Please try refreshing the page.
                            </div>
                        </td>
                    </tr>
                `;
            });
        }
        
        // Display students in table
        function displayStudents(studentsData) {
            const tableBody = document.getElementById('studentsTableBody');
            
            if (studentsData.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center">
                            <div class="alert alert-info mb-0">
                                No students assigned to you yet.
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }
            
            let html = '';
            studentsData.forEach(student => {
                html += `
                    <tr>
                        <td>${student.full_name || 'N/A'}</td>
                        <td>${student.matric_number || 'N/A'}</td>
                        <td>${student.department || 'N/A'}</td>
                        <td>${student.institution || 'N/A'}</td>
                        <td>
                            ${student.pending_logs > 0 ? 
                                `<span class="badge bg-warning">${student.pending_logs}</span>` : 
                                `<span class="badge bg-secondary">0</span>`
                            }
                        </td>
                        <td>
                            ${student.active ? 
                                `<span class="badge bg-success">Active</span>` : 
                                `<span class="badge bg-danger">Inactive</span>`
                            }
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="viewStudentDetails(${student.id})">
                                <i class="fas fa-eye"></i> View
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            tableBody.innerHTML = html;
        }
        
        // Update statistics cards
        function updateStatistics(data) {
            document.getElementById('totalStudents').textContent = data.total || 0;
            document.getElementById('activeStudents').textContent = data.active || 0;
            document.getElementById('inactiveStudents').textContent = data.inactive || 0;
        }
        
        // View student details
        function viewStudentDetails(studentId) {
            currentStudentId = studentId;
            const student = students.find(s => s.id === studentId);
            
            if (!student) {
                showToast("Student not found", "error");
                return;
            }
            
            // Set modal title
            document.getElementById('studentDetailsModalLabel').textContent = `Student Details: ${student.full_name || 'N/A'}`;
            
            // Display personal info
            document.getElementById('studentPersonalInfo').innerHTML = `
                <div class="mb-3">
                    <strong>Name:</strong><br>
                    ${student.full_name || 'N/A'}
                </div>
                <div class="mb-3">
                    <strong>Matric Number:</strong><br>
                    ${student.matric_number || 'N/A'}
                </div>
                <div class="mb-3">
                    <strong>Department:</strong><br>
                    ${student.department || 'N/A'}
                </div>
                <div class="mb-3">
                    <strong>Institution:</strong><br>
                    ${student.institution || 'N/A'}
                </div>
            `;
            
            // Display SIWES info
            document.getElementById('studentSiwesInfo').innerHTML = `
                <div class="mb-3">
                    <strong>Student ID:</strong><br>
                    ${student.student_id || 'N/A'}
                </div>
                <div class="mb-3">
                    <strong>Workplace:</strong><br>
                    ${student.workplace_name || 'Not specified'}
                </div>
                <div class="mb-3">
                    <strong>SIWES Start Date:</strong><br>
                    ${student.siwes_start_date ? new Date(student.siwes_start_date).toLocaleDateString() : 'Not specified'}
                </div>
                <div class="mb-3">
                    <strong>SIWES End Date:</strong><br>
                    ${student.siwes_end_date ? new Date(student.siwes_end_date).toLocaleDateString() : 'Not specified'}
                </div>
            `;
            
            // Display log summary (placeholder - would need additional API endpoint for actual data)
            document.getElementById('studentLogSummary').innerHTML = `
                <div class="row">
                    <div class="col-md-4 text-center">
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h3>${student.pending_logs || 0}</h3>
                                <p class="mb-0">Pending Logs</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h3>-</h3>
                                <p class="mb-0">Approved Logs</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h3>-</h3>
                                <p class="mb-0">Rejected Logs</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Click "View All Logs" to see detailed log entries for this student.
                </div>
            `;
            
            // Set view logs button link
            document.getElementById('viewLogsBtn').href = `dashboard.php?student_id=${studentId}`;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('studentDetailsModal'));
            modal.show();
        }
        
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const filteredStudents = students.filter(student => {
                return (
                    (student.full_name && student.full_name.toLowerCase().includes(searchTerm)) ||
                    (student.matric_number && student.matric_number.toLowerCase().includes(searchTerm)) ||
                    (student.department && student.department.toLowerCase().includes(searchTerm)) ||
                    (student.institution && student.institution.toLowerCase().includes(searchTerm))
                );
            });
            displayStudents(filteredStudents);
        });
        
        // Toast notification function
        function showToast(message, type = 'info') {
            // Create toast container if it doesn't exist
            let toastContainer = document.getElementById('toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.id = 'toast-container';
                toastContainer.className = 'position-fixed top-0 end-0 p-3';
                toastContainer.style.zIndex = '1050';
                document.body.appendChild(toastContainer);
            }
            
            // Create toast element
            const toastId = 'toast-' + Date.now();
            const toastEl = document.createElement('div');
            toastEl.id = toastId;
            toastEl.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type} border-0`;
            toastEl.setAttribute('role', 'alert');
            toastEl.setAttribute('aria-live', 'assertive');
            toastEl.setAttribute('aria-atomic', 'true');
            
            // Toast content
            toastEl.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;
            
            // Add toast to container
            toastContainer.appendChild(toastEl);
            
            // Initialize and show toast
            const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
            toast.show();
            
            // Remove toast after it's hidden
            toastEl.addEventListener('hidden.bs.toast', function () {
                toastEl.remove();
            });
        }
        
        // Load students when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadStudents();
        });
    </script>
</body>
</html>