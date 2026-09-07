<?php
session_start();
require_once '../backend/config/session.php';

// Check if user is logged in and has supervisor role
if (!isLoggedIn() || !hasRole('supervisor')) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Log Entry - SIWES Supervisor Portal</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/supervisor-styles.css" rel="stylesheet">
    <style>
        #map { height: 300px; width: 100%; border-radius: 8px; }
        .log-details { background-color: var(--bs-light-bg-subtle); border-radius: 8px; padding: 20px; }
        .status-badge { font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->  
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="menu-toggle">
                    <i class="fas fa-bars"></i>
                </div>
                <div class="page-title">
                    <h4>Review Log Entry</h4>
                </div>
                <div class="user-info">
                    <span class="user-name"><?php echo $_SESSION['name']; ?></span>
                    <button onclick="logout()" class="btn btn-sm btn-outline-light ms-2">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                </div>
            </header>

            <!-- Content Area -->
            <div class="content-area p-3">
                <div class="container-fluid">
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                        <li class="breadcrumb-item active">Review Log Entry</li>
                                    </ol>
                                </nav>
                                <a href="dashboard.php" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Log Entry Details</h5>
                                </div>
                                <div class="card-body">
                                    <div id="logDetails" class="log-details">
                                        <div class="text-center py-4">
                                            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                                            <p class="mt-2 text-muted">Loading log details...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card shadow-sm">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Review Decision</h5>
                                </div>
                                <div class="card-body">
                                    <form id="reviewForm">
                                        <div class="mb-3">
                                            <label for="supervisorComment" class="form-label">Supervisor Comment</label>
                                            <textarea class="form-control" id="supervisorComment" rows="4" 
                                                placeholder="Add your feedback or comments about this log entry..."></textarea>
                                        </div>
                                        
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-success btn-lg flex-fill" onclick="approveLog()">
                                                <i class="fas fa-check me-2"></i>Approve
                                            </button>
                                            <button type="button" class="btn btn-danger btn-lg flex-fill" onclick="rejectLog()">
                                                <i class="fas fa-times me-2"></i>Reject
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Student Information</h5>
                                </div>
                                <div class="card-body" id="studentInfo">
                                    <div class="text-center py-3">
                                        <i class="fas fa-spinner fa-spin text-primary"></i>
                                        <p class="mt-2 text-muted">Loading...</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card shadow-sm">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Location</h5>
                                </div>
                                <div class="card-body">
                                    <div id="mapContainer">
                                        <div id="map"></div>
                                        <p class="small text-muted mt-2">Location where this log entry was submitted</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/supervisor-scripts.js"></script>
    <script>
        let currentLogId = null;
        let currentLog = null;

        function loadLogDetails() {
            const urlParams = new URLSearchParams(window.location.search);
            currentLogId = urlParams.get('log_id');
            
            if (!currentLogId) {
                document.getElementById('logDetails').innerHTML = 
                    '<div class="alert alert-danger">No log ID provided</div>';
                return;
            }

            fetch("../backend/api/supervisor.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ action: "get_log_details", log_id: currentLogId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentLog = data.log;
                    displayLogDetails(data.log);
                    displayStudentInfo(data.log);
                    if (data.log.latitude && data.log.longitude) {
                        initMap(parseFloat(data.log.latitude), parseFloat(data.log.longitude));
                    } else {
                        document.getElementById('mapContainer').innerHTML = 
                            '<div class="alert alert-info">No location data available</div>';
                    }
                } else {
                    document.getElementById('logDetails').innerHTML = 
                        '<div class="alert alert-danger">Log not found</div>';
                }
            })
            .catch(error => {
                console.error('Error loading log details:', error);
                document.getElementById('logDetails').innerHTML = 
                    '<div class="alert alert-danger">Error loading log details</div>';
            });
        }

        function displayLogDetails(log) {
            const date = new Date(log.date).toLocaleDateString();
            const time = new Date(log.date).toLocaleTimeString();
            const statusClass = log.status === "pending" ? "warning" : 
                              log.status === "approved" ? "success" : "danger";
            
            document.getElementById("logDetails").innerHTML = `
                <div class="log-details">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Student:</strong> ${log.student_name || 'N/A'}
                        </div>
                        <div class="col-md-6">
                            <strong>Date:</strong> ${date} at ${time}
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Matric Number:</strong> ${log.matric_number || 'N/A'}
                        </div>
                        <div class="col-md-6">
                            <strong>Department:</strong> ${log.department || 'N/A'}
                        </div>
                    </div>
                    <div class="mb-3">
                        <strong>Status:</strong> 
                        <span class="badge bg-${statusClass} status-badge">${log.status ? log.status.toUpperCase() : 'PENDING'}</span>
                    </div>
                    <div class="mb-3">
                        <strong>Activity Description:</strong>
                        <p class="mt-2">${log.activity || 'No activity description provided'}</p>
                    </div>
                    ${log.supervisor_comment ? `
                        <div class="mb-3">
                            <strong>Previous Comment:</strong>
                            <p class="mt-2 text-muted">${log.supervisor_comment}</p>
                        </div>
                    ` : ''}
                </div>
            `;
        }

        function displayStudentInfo(log) {
            document.getElementById('studentInfo').innerHTML = `
                <div class="mb-3">
                    <strong>Name:</strong><br>
                    ${log.student_name || 'N/A'}
                </div>
                <div class="mb-3">
                    <strong>Matric Number:</strong><br>
                    ${log.matric_number || 'N/A'}
                </div>
                <div class="mb-3">
                    <strong>Department:</strong><br>
                    ${log.department || 'N/A'}
                </div>
                <div class="mb-3">
                    <strong>Institution:</strong><br>
                    ${log.institution || 'Not specified'}
                </div>
            `;
        }

        function initMap(lat, lng) {
            const mapDiv = document.getElementById("map");
            const map = new google.maps.Map(mapDiv, {
                center: { lat: lat, lng: lng },
                zoom: 15
            });
            new google.maps.Marker({
                position: { lat: lat, lng: lng },
                map: map,
                title: "Student Location"
            });
        }

        function approveLog() {
            if (!currentLogId) return;
            
            const comment = document.getElementById("supervisorComment").value;
            
            fetch("../backend/api/supervisor.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ action: "approve_log", log_id: currentLogId, comment: comment })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast("Log approved successfully!", "success");
                    setTimeout(() => {
                        window.location.href = "dashboard.php";
                    }, 1500);
                } else {
                    showToast("Error: " + (data.message || "Failed to approve log"), "error");
                }
            })
            .catch(error => { 
                console.error("Error approving log:", error);
                showToast("Network error. Please try again.", "error"); 
            });
        }

        function rejectLog() {
            if (!currentLogId) return;
            
            const comment = document.getElementById("supervisorComment").value;
            
            if (!comment.trim()) {
                showToast("Please provide a comment when rejecting a log entry.", "warning");
                return;
            }
            
            fetch("../backend/api/supervisor.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ action: "reject_log", log_id: currentLogId, comment: comment })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast("Log rejected successfully!", "success");
                    setTimeout(() => {
                        window.location.href = "dashboard.php";
                    }, 1500);
                } else {
                    showToast("Error: " + (data.message || "Failed to reject log"), "error");
                }
            })
            .catch(error => { 
                console.error("Error rejecting log:", error);
                showToast("Network error. Please try again.", "error"); 
            });
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadLogDetails();
        });
    </script>
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY&callback=initMap"></script>
</body>
</html>