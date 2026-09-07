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
    <link rel="stylesheet" href="assets/admin-styles.css">`n    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <!-- Additional GPS monitoring styles -->
    <style>
        .map-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .map-placeholder {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 3rem;
            text-align: center;
            color: #6c757d;
        }
        
        .location-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 4px solid var(--primary-color);
        }
        
        .location-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .location-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        
        .location-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: white;
            background: var(--primary-color);
        }
        
        .location-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-online {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }
        
        .status-offline {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }
        
        .location-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .location-details {
            color: #6c757d;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }
        
        .location-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.875rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .track-btn {
            background: var(--info-color);
            color: white;
        }
        
        .track-btn:hover {
            background: #138496;
            color: white;
        }
        
        .history-btn {
            background: var(--warning-color);
            color: white;
        }
        
        .history-btn:hover {
            background: #e0a800;
            color: white;
        }
        
        .locations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .locations-grid {
                grid-template-columns: 1fr;
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
                <h1 class="page-title">GPS Monitoring</h1>
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
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h2 class="mb-1">GPS Monitoring</h2>
                    <p class="text-muted mb-0">Track student locations and activities</p>
            </div>
        </div>

            <!-- Map Container -->
            <div class="map-container fade-in-up">
                <h3 class="section-title mb-3">
                    <i class="fas fa-map"></i>
                    Live Location Map
                </h3>
                <div class="map-placeholder">
                    <i class="fas fa-map-marked-alt fa-3x mb-3"></i>
                    <h4>Interactive Map</h4>
                    <p>GPS tracking map will be integrated here</p>
                    <button class="btn btn-primary" onclick="loadMap()">
                        <i class="fas fa-map"></i>
                        Load Map
                    </button>
                </div>
            </div>
            
            <!-- Location Cards -->
            <div class="locations-grid" id="studentLocations">
                <!-- Sample Location Cards -->
                <div class="location-card fade-in-up">
                    <div class="location-header">
                        <div class="location-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <span class="location-status status-online">Online</span>
        </div>
                    <div class="location-name">John Doe</div>
                    <div class="location-details">
                        <div><i class="fas fa-map-marker-alt"></i> Nsukka Campus, UNN</div>
                        <div><i class="fas fa-clock"></i> Last updated: 2 minutes ago</div>
                    </div>
                    <div class="location-actions">
                        <a href="#" class="action-btn track-btn" onclick="trackStudent(1)">
                            <i class="fas fa-location-arrow"></i>
                            Track
                        </a>
                        <a href="#" class="action-btn history-btn" onclick="viewHistory(1)">
                            <i class="fas fa-history"></i>
                            History
                        </a>
                    </div>
                </div>
                
                <div class="location-card fade-in-up">
                    <div class="location-header">
                        <div class="location-icon">
                            <i class="fas fa-user-graduate"></i>
            </div>
                        <span class="location-status status-online">Online</span>
                    </div>
                    <div class="location-name">Jane Smith</div>
                    <div class="location-details">
                        <div><i class="fas fa-map-marker-alt"></i> Industrial Area, Enugu</div>
                        <div><i class="fas fa-clock"></i> Last updated: 5 minutes ago</div>
                                        </div>
                    <div class="location-actions">
                        <a href="#" class="action-btn track-btn" onclick="trackStudent(2)">
                            <i class="fas fa-location-arrow"></i>
                            Track
                        </a>
                        <a href="#" class="action-btn history-btn" onclick="viewHistory(2)">
                            <i class="fas fa-history"></i>
                            History
                        </a>
                    </div>
                </div>
                
                <div class="location-card fade-in-up">
                    <div class="location-header">
                        <div class="location-icon">
                            <i class="fas fa-user-graduate"></i>
            </div>
                        <span class="location-status status-offline">Offline</span>
        </div>
                    <div class="location-name">Mike Johnson</div>
                    <div class="location-details">
                        <div><i class="fas fa-map-marker-alt"></i> Last known: Computer Village</div>
                        <div><i class="fas fa-clock"></i> Last updated: 1 hour ago</div>
                    </div>
                    <div class="location-actions">
                        <a href="#" class="action-btn track-btn" onclick="trackStudent(3)">
                            <i class="fas fa-location-arrow"></i>
                            Track
                        </a>
                        <a href="#" class="action-btn history-btn" onclick="viewHistory(3)">
                            <i class="fas fa-history"></i>
                            History
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/admin-scripts.js"></script>
    <script>
        // GPS monitoring functions
        function loadMap() {
            showToast('Map loading will be implemented in the next update', 'info');
        }
        
        function trackStudent(studentId) {
            showToast(`Tracking student ${studentId} will be implemented in the next update`, 'info');
        }
        
        function viewHistory(studentId) {
            showToast(`Viewing history for student ${studentId} will be implemented in the next update`, 'info');
        }
    </script>
</body>
</html> 




