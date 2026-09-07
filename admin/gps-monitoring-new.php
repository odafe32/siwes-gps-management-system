<?php
session_start();
require_once '../backend/config/db.php';
require_once '../backend/config/session.php';

// Check if user is logged in and has admin/coordinator role
if (!isLoggedIn() || (!hasRole('admin') && !hasRole('coordinator'))) {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPS Monitoring - SIWES Logbook</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/admin-styles.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        .map-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .locations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .location-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .location-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .location-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .location-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1a4d2e 0%, #2d5a3d 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .location-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-online {
            background: #d4edda;
            color: #155724;
        }
        
        .status-offline {
            background: #f8d7da;
            color: #721c24;
        }
        
        .location-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .location-details {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .location-details div {
            margin-bottom: 0.25rem;
        }
        
        .location-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .action-btn {
            flex: 1;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            text-align: center;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .track-btn {
            background: #007bff;
            color: white;
        }
        
        .track-btn:hover {
            background: #0056b3;
            color: white;
        }
        
        .history-btn {
            background: #6c757d;
            color: white;
        }
        
        .history-btn:hover {
            background: #545b62;
            color: white;
        }
        
        #map {
            height: 500px;
            width: 100%;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .map-controls {
            margin-top: 1rem;
        }
        
        .student-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
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
                <h1 class="page-title">GPS Monitoring</h1>
            </div>
            
            <div class="user-menu">
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars(['name'] ?? 'Admin'); ?></div>
                    <div class="user-role"><?php echo ucfirst(['role'] ?? 'admin'); ?></div>
                </div>
                <button class="logout-btn" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </button>
            </div>
        </header>
        
        <!-- Page Content -->
        <div class="page-content">
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h2 class="mb-1">GPS Monitoring</h2>
                    <p class="text-muted mb-0">Track student locations and activities in real-time</p>
                </div>
            </div>

            <!-- Map Container -->
            <div class="map-container fade-in-up">
                <h3 class="section-title mb-3">
                    <i class="fas fa-map"></i>
                    Live Location Map
                </h3>
                <div id="map"></div>
                <div class="map-controls">
                    <button class="btn btn-primary me-2" onclick="loadMap()">
                        <i class="fas fa-sync-alt"></i>
                        Refresh Map
                    </button>
                    <button class="btn btn-outline-primary me-2" onclick="centerMap()">
                        <i class="fas fa-crosshairs"></i>
                        Center Map
                    </button>
                    <button class="btn btn-outline-success" onclick="toggleRealTime()">
                        <i class="fas fa-play"></i>
                        <span id="realtimeToggle">Start Real-time</span>
                    </button>
                </div>
            </div>
            
            <!-- Student Locations -->
            <div class="locations-grid" id="studentLocations">
                <!-- Student location cards will be loaded here -->
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/admin-scripts.js"></script>
    <script>
        let map;
        let markers = [];
        let realTimeInterval;
        let isRealTimeActive = false;
        
        // Initialize map
        function initMap() {
            map = L.map('map').setView([6.5244, 3.3792], 10); // Default to Lagos, Nigeria
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: ' OpenStreetMap contributors'
            }).addTo(map);
            
            loadStudentLocations();
        }
        
        // Load student locations from API
        async function loadStudentLocations() {
            try {
                const response = await fetch('../backend/api/admin.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'get_active_students'
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    displayStudentLocations(data.students);
                    updateMapMarkers(data.students);
                } else {
                    showToast('Failed to load student locations', 'error');
                }
            } catch (error) {
                console.error('Error loading student locations:', error);
                showToast('Error loading student locations', 'error');
            }
        }
        
        // Display student location cards
        function displayStudentLocations(students) {
            const container = document.getElementById('studentLocations');
            container.innerHTML = '';
            
            students.forEach(student => {
                const isOnline = isStudentOnline(student.last_activity_date, student.last_activity_time);
                const statusClass = isOnline ? 'status-online' : 'status-offline';
                const statusText = isOnline ? 'Online' : 'Offline';
                
                const card = document.createElement('div');
                card.className = 'location-card fade-in-up';
                card.innerHTML = 
                    <div class="location-header">
                        <div class="location-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <span class="location-status "></span>
                    </div>
                    <div class="location-name"></div>
                    <div class="location-details">
                        <div><i class="fas fa-id-card"></i> </div>
                        <div><i class="fas fa-building"></i> </div>
                        <div><i class="fas fa-map-marker-alt"></i> </div>
                        <div><i class="fas fa-clock"></i> Last updated: </div>
                    </div>
                    <div class="location-actions">
                        <a href="#" class="action-btn track-btn" onclick="trackStudent()">
                            <i class="fas fa-location-arrow"></i>
                            Track
                        </a>
                        <a href="#" class="action-btn history-btn" onclick="viewHistory()">
                            <i class="fas fa-history"></i>
                            History
                        </a>
                    </div>
                ;
                container.appendChild(card);
            });
        }
        
        // Update map markers
        function updateMapMarkers(students) {
            // Clear existing markers
            markers.forEach(marker => map.removeLayer(marker));
            markers = [];
            
            students.forEach(student => {
                if (student.last_latitude && student.last_longitude) {
                    const isOnline = isStudentOnline(student.last_activity_date, student.last_activity_time);
                    const markerColor = isOnline ? 'green' : 'red';
                    
                    const marker = L.circleMarker([student.last_latitude, student.last_longitude], {
                        color: markerColor,
                        fillColor: markerColor,
                        fillOpacity: 0.7,
                        radius: 8
                    }).addTo(map);
                    
                    marker.bindPopup(
                        <div class="student-info">
                            <h6></h6>
                            <p><strong>Matric:</strong> </p>
                            <p><strong>Department:</strong> </p>
                            <p><strong>Workplace:</strong> </p>
                            <p><strong>Last Activity:</strong> </p>
                            <p><strong>Status:</strong> </p>
                        </div>
                    );
                    
                    markers.push(marker);
                }
            });
        }
        
        // Check if student is online (active within last 30 minutes)
        function isStudentOnline(date, time) {
            if (!date || !time) return false;
            
            const lastActivity = new Date(date + ' ' + time);
            const now = new Date();
            const diffMinutes = (now - lastActivity) / (1000 * 60);
            
            return diffMinutes <= 30;
        }
        
        // Format last activity time
        function formatLastActivity(date, time) {
            if (!date || !time) return 'Never';
            
            const lastActivity = new Date(date + ' ' + time);
            const now = new Date();
            const diffMinutes = Math.floor((now - lastActivity) / (1000 * 60));
            
            if (diffMinutes < 1) return 'Just now';
            if (diffMinutes < 60) return ${diffMinutes} minutes ago;
            
            const diffHours = Math.floor(diffMinutes / 60);
            if (diffHours < 24) return ${diffHours} hours ago;
            
            const diffDays = Math.floor(diffHours / 24);
            return ${diffDays} days ago;
        }
        
        // Load map function
        function loadMap() {
            if (!map) {
                initMap();
            } else {
                loadStudentLocations();
            }
        }
        
        // Center map function
        function centerMap() {
            if (map && markers.length > 0) {
                const group = new L.featureGroup(markers);
                map.fitBounds(group.getBounds().pad(0.1));
            }
        }
        
        // Toggle real-time updates
        function toggleRealTime() {
            const toggle = document.getElementById('realtimeToggle');
            
            if (isRealTimeActive) {
                clearInterval(realTimeInterval);
                isRealTimeActive = false;
                toggle.textContent = 'Start Real-time';
                showToast('Real-time updates stopped', 'info');
            } else {
                realTimeInterval = setInterval(loadStudentLocations, 30000); // Update every 30 seconds
                isRealTimeActive = true;
                toggle.textContent = 'Stop Real-time';
                showToast('Real-time updates started', 'success');
            }
        }
        
        // Track specific student
        function trackStudent(studentId) {
            // Center map on student's location
            const student = markers.find(marker => marker.studentId === studentId);
            if (student) {
                map.setView([student.getLatLng().lat, student.getLatLng().lng], 15);
                student.openPopup();
            }
            showToast(Tracking student , 'info');
        }
        
        // View student history
        function viewHistory(studentId) {
            showToast(Viewing history for student , 'info');
            // TODO: Implement history modal or redirect to history page
        }
        
        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initMap();
        });
    </script>
</body>
</html>
