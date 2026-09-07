<?php
session_start();
require_once '../backend/config/db.php';
require_once '../backend/config/session.php';
require_once '../backend/models/LogEntry.php';

// Check if user is logged in and has student role
if (!isLoggedIn() || !hasRole('student')) {
    header('Location: login.php');
    exit();
}

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $activity = trim($_POST['activity']);
        $student_id = $_SESSION['user_id'];
        $latitude = !empty($_POST['latitude']) ? $_POST['latitude'] : null;
        $longitude = !empty($_POST['longitude']) ? $_POST['longitude'] : null;
        $location_address = !empty($_POST['location_address']) ? $_POST['location_address'] : null;
        $accuracy = !empty($_POST['accuracy']) ? $_POST['accuracy'] : null;
        $altitude = !empty($_POST['altitude']) ? $_POST['altitude'] : null;
        $heading = !empty($_POST['heading']) ? $_POST['heading'] : null;
        $speed = !empty($_POST['speed']) ? $_POST['speed'] : null;
        $date = $_POST['date'] ?? date('Y-m-d');
        
        if (empty($activity)) {
            throw new Exception('Activity description is required');
        }
        
        // Insert into database with proper error handling
        $stmt = $pdo->prepare("INSERT INTO log_entries (student_id, activity, date, latitude, longitude, location_address, status, accuracy, altitude, heading, speed, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, NOW())");
        
        if (!$stmt->execute([$student_id, $activity, $date, $latitude, $longitude, $location_address, $accuracy, $altitude, $heading, $speed])) {
            throw new Exception('Database error: ' . implode(', ', $stmt->errorInfo()));
        }
        
        $message = "Log entry submitted successfully!";
        $messageType = "success";
        
        // Clear form data after successful submission
        $_POST = array();
        
    } catch (Exception $e) {
        $message = "Error submitting log entry: " . $e->getMessage();
        $messageType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Log Entry - SIWES Logbook</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/student-styles.css">
    
    <style>
        .form-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .form-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .form-control {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(26, 77, 46, 0.25);
        }
        
        .btn-primary {
            background: var(--primary-color);
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            background: var(--secondary-color);
        }
        
        .location-section {
            background: rgba(26, 77, 46, 0.05);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .location-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .location-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .location-status.success {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }
        
        .location-status.warning {
            background: rgba(255, 193, 7, 0.1);
            color: var(--warning-color);
        }
        
        .location-status.error {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }
        
        .accuracy-indicator {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }
        
        .accuracy-excellent {
            background-color: #d4edda;
            color: #155724;
        }
        
        .accuracy-high {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .accuracy-good {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .accuracy-fair {
            background-color: #ffeaa7;
            color: #6c5ce7;
        }
        
        .accuracy-low {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .location-coordinates {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .tips-section {
            background: rgba(26, 77, 46, 0.05);
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 1rem;
        }
        
        .tips-title {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .tip-item {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .tip-item i {
            color: var(--primary-color);
            margin-top: 0.25rem;
        }
        
        .btn-location {
            background: var(--info-color);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .btn-location:hover {
            background: #138496;
        }
        
        .btn-location:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        
        .accuracy-info {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 0.5rem;
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
                <h1 class="page-title">New Log Entry</h1>
            </div>
            
            <div class="user-menu">
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Student'); ?></div>
                    <div class="user-role">Student</div>
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
            
            <!-- Log Entry Form -->
            <div class="form-card fade-in-up">
                <h3 class="form-title">
                    <i class="fas fa-plus-circle"></i>
                    Create New Log Entry
                </h3>
                
                <form method="POST" id="logEntryForm">
                    <!-- Location Section -->
                    <div class="location-section">
                        <div class="location-header">
                                            <i class="fas fa-map-marker-alt"></i>
                            Location Capture
                </div>
                
                        <div id="locationStatus" class="location-status warning">
                            <i class="fas fa-clock"></i>
                            <span>Click "Get High Accuracy Location" to capture your current location</span>
                        </div>
                        
                        <div class="location-coordinates">
                                <div>
                                <label for="latitude" class="form-label">Latitude</label>
                                <input type="text" class="form-control" id="latitude" name="latitude" readonly>
                                </div>
                                <div>
                                <label for="longitude" class="form-label">Longitude</label>
                                <input type="text" class="form-control" id="longitude" name="longitude" readonly>
                                </div>
                            </div>
                            
                            <!-- Hidden fields for additional GPS data -->
                            <input type="hidden" id="accuracy" name="accuracy">
                            <input type="hidden" id="altitude" name="altitude">
                            <input type="hidden" id="heading" name="heading">
                            <input type="hidden" id="speed" name="speed">
                            
                        <div class="mb-3">
                            <label for="location_address" class="form-label">Location Address</label>
                            <input type="text" class="form-control" id="location_address" name="location_address" placeholder="Address will be automatically filled">
                            </div>
                            
                        <button type="button" class="btn-location" id="getLocationBtn">
                            <i class="fas fa-location-arrow"></i>
                            Get High Accuracy Location
                        </button>
                        
                        <div class="accuracy-info">
                            <i class="fas fa-info-circle"></i>
                            Location accuracy depends on your device's GPS and network connection
                        </div>
                    </div>
                    
                    <!-- Date and Time Section -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="date" class="form-label">Date *</label>
                            <input type="date" class="form-control" id="date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="time" class="form-label">Time *</label>
                            <input type="time" class="form-control" id="time" name="time" value="<?php echo date('H:i'); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="activity" class="form-label">Activity Description *</label>
                        <textarea 
                            class="form-control" 
                            id="activity" 
                            name="activity" 
                            rows="8" 
                            placeholder="Describe your SIWES activity for today..."
                            required
                        ><?php echo htmlspecialchars($_POST['activity'] ?? ''); ?></textarea>
                        <div class="form-text">
                            Provide a detailed description of your activities, tasks completed, skills learned, and any challenges encountered.
                        </div>
                                </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Current Date & Time</label>
                        <input type="text" class="form-control" value="<?php echo date('F j, Y g:i A'); ?>" readonly>
                                </div>
                    
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-paper-plane"></i>
                        Submit Log Entry
                    </button>
                </form>
                
                <!-- Tips Section -->
                <div class="tips-section">
                    <h5 class="tips-title">
                        <i class="fas fa-lightbulb"></i>
                        Writing Tips
                    </h5>
                    <div class="tip-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Be specific about the tasks you performed</span>
                            </div>
                    <div class="tip-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Mention any new skills or knowledge gained</span>
                                </div>
                    <div class="tip-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Include challenges faced and how you overcame them</span>
                                </div>
                    <div class="tip-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Describe your interactions with colleagues or supervisors</span>
                            </div>
                    <div class="tip-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Reflect on how this experience contributes to your career goals</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/student-scripts.js"></script>
    <script>
        // Geolocation functionality with improved accuracy
        let currentPosition = null;
        let locationAccuracy = null;
        let locationAttempts = 0;
        let maxAttempts = 3;
        let bestPosition = null;
        let bestAccuracy = Infinity;
        
        document.getElementById('getLocationBtn').addEventListener('click', function() {
            const btn = this;
            const statusDiv = document.getElementById('locationStatus');
            
            if (!navigator.geolocation) {
                statusDiv.className = 'location-status error';
                statusDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i><span>Geolocation is not supported by this browser</span>';
                return;
            }
            
            // Reset variables for new attempt
            locationAttempts = 0;
            bestPosition = null;
            bestAccuracy = Infinity;
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Getting High Accuracy Location...';
            statusDiv.className = 'location-status warning';
            statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Getting high accuracy location (attempt 1 of 3)...</span>';
            
            // Start multiple attempts for better accuracy
            attemptLocationCapture();
        });
        
        function attemptLocationCapture() {
            const btn = document.getElementById('getLocationBtn');
            const statusDiv = document.getElementById('locationStatus');
            
            locationAttempts++;
            
            const options = {
                enableHighAccuracy: true,
                timeout: 20000,
                maximumAge: 0,
                altitude: true,
                altitudeAccuracy: true,
                heading: true,
                speed: true
            };
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    currentPosition = position;
                    const accuracy = position.coords.accuracy;
                    
                    // Track best position
                    if (accuracy < bestAccuracy) {
                        bestPosition = position;
                        bestAccuracy = accuracy;
                    }
                    
                    // Check if we should try again for better accuracy
                    if (locationAttempts < maxAttempts && accuracy > 20) {
                        statusDiv.innerHTML = `<i class="fas fa-spinner fa-spin"></i><span>Attempt ${locationAttempts + 1} of 3 - Current accuracy: ${Math.round(accuracy)}m (seeking better accuracy)...</span>`;
                        setTimeout(() => attemptLocationCapture(), 2000);
                        return;
                    }
                    
                    // Use best position found
                    const finalPosition = bestPosition || position;
                    const lat = finalPosition.coords.latitude;
                    const lng = finalPosition.coords.longitude;
                    locationAccuracy = bestAccuracy;
                    
                    document.getElementById('latitude').value = lat.toFixed(8);
                    document.getElementById('longitude').value = lng.toFixed(8);
                    
                    // Store additional GPS data
                    document.getElementById('accuracy').value = finalPosition.coords.accuracy || '';
                    document.getElementById('altitude').value = finalPosition.coords.altitude || '';
                    document.getElementById('heading').value = finalPosition.coords.heading || '';
                    document.getElementById('speed').value = finalPosition.coords.speed || '';
                    
                    // Get address from coordinates
                    getAddressFromCoordinates(lat, lng);
                    
                    // Validate location against workplace
                    validateWorkplaceLocation(lat, lng).then(validation => {
                        let accuracyText = '';
                        let accuracyClass = '';
                        
                        if (locationAccuracy <= 5) {
                            accuracyText = 'Excellent accuracy';
                            accuracyClass = 'excellent';
                        } else if (locationAccuracy <= 10) {
                            accuracyText = 'High accuracy';
                            accuracyClass = 'high';
                        } else if (locationAccuracy <= 20) {
                            accuracyText = 'Good accuracy';
                            accuracyClass = 'good';
                        } else if (locationAccuracy <= 50) {
                            accuracyText = 'Fair accuracy';
                            accuracyClass = 'fair';
                        } else {
                            accuracyText = 'Low accuracy';
                            accuracyClass = 'low';
                        }
                        
                        if (validation.isValid) {
                            if (validation.warning) {
                                statusDiv.className = 'location-status warning';
                                statusDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i><span>Location captured (${accuracyText}) - ${validation.warning}</span>`;
                            } else {
                                let workplaceUpdateMessage = '';
                                if (locationAccuracy && locationAccuracy <= 20) {
                                    workplaceUpdateMessage = ' - Workplace location updated!';
                                } else if (locationAccuracy && locationAccuracy > 20) {
                                    workplaceUpdateMessage = ' - Accuracy too low for workplace update';
                                }
                                
                                statusDiv.className = 'location-status success';
                                statusDiv.innerHTML = `<i class="fas fa-check-circle"></i><span>Location validated! You are ${validation.distance}m from ${validation.workplaceName} (${accuracyText}: ±${Math.round(locationAccuracy)}m)${workplaceUpdateMessage}</span>`;
                            }
                        } else {
                            statusDiv.className = 'location-status error';
                            statusDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i><span>Location validation failed! You are ${validation.distance}m from ${validation.workplaceName} (maximum allowed: ${validation.maxDistance}m)</span>`;
                            
                            // Clear coordinates if validation fails
                            document.getElementById('latitude').value = '';
                            document.getElementById('longitude').value = '';
                            document.getElementById('location_address').value = '';
                        }
                    });
                    
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-location-arrow"></i> Get High Accuracy Location';
                },
                function(error) {
                    let errorMessage = 'Unknown error occurred';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage = 'Location permission denied. Please allow location access in your browser settings.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage = 'Location information unavailable. Please check your GPS settings and try again.';
                            break;
                        case error.TIMEOUT:
                            if (locationAttempts < maxAttempts) {
                                statusDiv.innerHTML = `<i class="fas fa-spinner fa-spin"></i><span>Attempt ${locationAttempts + 1} of 3 - Timeout, retrying...</span>`;
                                setTimeout(() => attemptLocationCapture(), 1000);
                                return;
                            }
                            errorMessage = 'Location request timed out after multiple attempts. Please check your GPS signal and try again.';
                            break;
                    }
                    
                    statusDiv.className = 'location-status error';
                    statusDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i><span>' + errorMessage + '</span>';
                    
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-location-arrow"></i> Get High Accuracy Location';
                },
                options
            );
        }
        
        function getAddressFromCoordinates(lat, lng) {
            // Using OpenStreetMap Nominatim API for reverse geocoding
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`)
                .then(response => response.json())
                .then(data => {
                    if (data.display_name) {
                        document.getElementById('location_address').value = data.display_name;
                        
                        // Also update workplace location address if this is a high accuracy capture
                        updateWorkplaceLocationAddress(lat, lng, data.display_name);
                    }
                })
                .catch(error => {
                    console.log('Could not get address from coordinates:', error);
                    const fallbackAddress = `Lat: ${lat}, Lng: ${lng}`;
                    document.getElementById('location_address').value = fallbackAddress;
                    
                    // Update workplace location with fallback address
                    updateWorkplaceLocationAddress(lat, lng, fallbackAddress);
                });
        }
        
        function updateWorkplaceLocationAddress(lat, lng, address) {
            // Only update workplace location if accuracy is good enough (≤20m)
            if (locationAccuracy && locationAccuracy > 20) {
                console.log('Accuracy too low for workplace location update:', locationAccuracy);
                return;
            }
            
            // Update workplace location coordinates and address
            fetch('../backend/api/student.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'update_workplace_location',
                    latitude: lat,
                    longitude: lng,
                    address: address
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Workplace location updated successfully with high accuracy');
                } else {
                    console.log('Failed to update workplace location:', data.message);
                }
            })
            .catch(error => {
                console.log('Error updating workplace location:', error);
            });
        }
        
        // Function to calculate distance between two coordinates using Haversine formula
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371e3; // Earth's radius in meters
            const φ1 = lat1 * Math.PI/180;
            const φ2 = lat2 * Math.PI/180;
            const Δφ = (lat2-lat1) * Math.PI/180;
            const Δλ = (lon2-lon1) * Math.PI/180;

            const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
                      Math.cos(φ1) * Math.cos(φ2) *
                      Math.sin(Δλ/2) * Math.sin(Δλ/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

            const d = R * c; // Distance in meters
            return d;
        }
        
        // Function to validate location against workplace coordinates
        function validateWorkplaceLocation(currentLat, currentLng) {
            // Get workplace coordinates from server
            return fetch('../backend/api/student.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'get_workplace_location'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.workplace_latitude && data.workplace_longitude) {
                    const workplaceLat = parseFloat(data.workplace_latitude);
                    const workplaceLng = parseFloat(data.workplace_longitude);
                    const distance = calculateDistance(currentLat, currentLng, workplaceLat, workplaceLng);
                    
                    // 100 meter tolerance
                    const maxDistance = 100;
                    
                    return {
                        isValid: distance <= maxDistance,
                        distance: Math.round(distance),
                        maxDistance: maxDistance,
                        workplaceName: data.workplace_name || 'your workplace'
                    };
                } else {
                    // No workplace location set, allow submission with warning
                    return {
                        isValid: true,
                        warning: 'No workplace location set. Please update your profile to set your workplace location for validation.'
                    };
                }
            })
            .catch(error => {
                console.error('Error validating location:', error);
                // On error, allow submission but with warning
                return {
                    isValid: true,
                    warning: 'Could not validate location. Please check your internet connection.'
                };
            });
        }
        
        // Form validation and submission
        document.getElementById('logEntryForm').addEventListener('submit', function(e) {
            console.log('Form submission started');
            
            const activity = document.getElementById('activity').value.trim();
            const submitBtn = document.getElementById('submitBtn');
            
            console.log('Activity length:', activity.length);
            
            if (!activity) {
                e.preventDefault();
                console.log('No activity provided');
                showToast('Please provide a detailed activity description', 'error');
                return;
            }
            
            if (activity.length < 50) {
                e.preventDefault();
                console.log('Activity too short:', activity.length);
                showToast('Please provide a more detailed description (at least 50 characters)', 'error');
                return;
            }
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
            
            // Check if location was captured
            const latitude = document.getElementById('latitude').value;
            console.log('Location captured:', !!latitude);
            
            if (!latitude) {
                if (!confirm('No location was captured. Do you want to submit without location data?')) {
                    e.preventDefault();
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Log Entry';
                    console.log('User cancelled submission due to no location');
                    return;
                }
            }
            
            console.log('Form will submit normally');
            // Allow form to submit normally - don't prevent default
            // The form will submit to the server and reload the page
        });
        
        // Auto-hide success messages
        const successAlert = document.querySelector('.alert-success');
        if (successAlert) {
            setTimeout(() => {
                successAlert.style.opacity = '0';
                setTimeout(() => {
                    successAlert.remove();
                }, 300);
            }, 5000);
        }
    </script>
</body>
</html> 


