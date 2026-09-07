<?php
session_start();
require_once '../backend/config/db.php';
require_once '../backend/config/session.php';
require_once '../backend/models/Geofence.php';
require_once '../backend/models/LocationLog.php';
require_once '../backend/models/Attendance.php';
require_once '../backend/models/Organization.php';

if (!isLoggedIn() || !hasRole('student')) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Get student's organization
$stmt = $pdo->prepare("
    SELECT o.* FROM organizations o
    JOIN users u ON u.organization_id = o.id
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$org = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle check-in
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$org) {
        $error = 'You are not assigned to any organization. Contact your supervisor.';
    } else {
        $lat = $_POST['latitude'] ?? null;
        $lng = $_POST['longitude'] ?? null;

        if ($lat === null || $lng === null) {
            $error = 'Could not capture your GPS location. Please enable location services.';
        } else {
            // Run geofence check
            $result = Geofence::checkStudentGeofence($pdo, $userId, (float)$lat, (float)$lng);
            $within = $result['within'];

            // Log the location ping
            LocationLog::log($pdo, $userId, (float)$lat, (float)$lng, $within);

            if ($_POST['action'] === 'checkin') {
                $att = Attendance::checkIn($pdo, $userId, $within);
                if ($within) {
                    $success = "Checked in successfully! You are within the geofence ({$result['distance']}m from center). Status: Present.";
                } else {
                    $success = "Checked in, but you are OUTSIDE the geofence ({$result['distance']}m from center). Status: Outside Zone. Your supervisor has been notified.";
                    // Create breach notification for supervisor
                    $supervisorId = $pdo->query("SELECT supervisor_id FROM users WHERE id = $userId")->fetchColumn();
                    if ($supervisorId) {
                        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'geofence_breach')");
                        $stmt->execute([
                            $supervisorId,
                            'Geofence Breach Alert',
                            $_SESSION['full_name'] . " checked in OUTSIDE the geofence for {$org['org_name']} ({$result['distance']}m away)."
                        ]);
                    }
                }
            } elseif ($_POST['action'] === 'checkout') {
                $att = Attendance::checkOut($pdo, $userId, $within);
                if ($within) {
                    $success = "Checked out successfully. Status: Present.";
                } else {
                    $success = "Checked out. You were outside the geofence ({$result['distance']}m). Status: Outside Zone.";
                }
            }
        }
    }
}

// Get today's attendance
$todayAtt = Attendance::getToday($pdo, $userId);
$isCheckedIn = $todayAtt && $todayAtt['check_in_time'] && !$todayAtt['check_out_time'];

// Get attendance history (last 30 records)
$history = Attendance::getHistory($pdo, $userId, 30);

// Get stats
$stmt = $pdo->prepare("
    SELECT
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN status = 'Outside Zone' THEN 1 ELSE 0 END) as outside,
        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent,
        COUNT(*) as total
    FROM attendance WHERE intern_id = ?
");
$stmt->execute([$userId]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - SIWES Student</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="assets/student-styles.css">
    <style>
        .status-card {
            background: white; border-radius: 12px; padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08); margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary-color);
        }
        .status-badge {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.5rem 1rem; border-radius: 20px; font-weight: 600; font-size: 0.9rem;
        }
        .status-present { background: rgba(40,167,69,0.15); color: #28a745; }
        .status-outside { background: rgba(255,193,7,0.15); color: #856404; }
        .status-absent { background: rgba(220,53,69,0.15); color: #dc3545; }
        .status-none { background: rgba(108,117,125,0.15); color: #6c757d; }

        .checkin-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white; border: none; padding: 1rem 2rem; border-radius: 10px;
            font-size: 1.1rem; font-weight: 700; width: 100%;
            transition: all 0.3s ease; cursor: pointer;
        }
        .checkin-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(40,167,69,0.4); color: white; }
        .checkin-btn:disabled { background: #6c757d; cursor: not-allowed; transform: none; box-shadow: none; }

        .checkout-btn {
            background: linear-gradient(135deg, #dc3545, #e83e8c);
            color: white; border: none; padding: 1rem 2rem; border-radius: 10px;
            font-size: 1.1rem; font-weight: 700; width: 100%;
            transition: all 0.3s ease; cursor: pointer;
        }
        .checkout-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(220,53,69,0.4); color: white; }
        .checkout-btn:disabled { background: #6c757d; cursor: not-allowed; transform: none; box-shadow: none; }

        #map { height: 300px; border-radius: 12px; border: 2px solid #e9ecef; margin-top: 1rem; }
        .stat-card { background: white; border-radius: 10px; padding: 1rem; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.06); }
        .stat-card .num { font-size: 1.75rem; font-weight: 700; }
        .stat-card .lbl { font-size: 0.8rem; color: #6c757d; text-transform: uppercase; }
        .stat-present .num { color: #28a745; }
        .stat-outside .num { color: #ffc107; }
        .stat-absent .num { color: #dc3545; }
        .stat-total .num { color: var(--primary-color); }

        .history-table { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.06); }
        .history-table th { background: var(--primary-color); color: white; font-size: 0.85rem; text-transform: uppercase; padding: 0.75rem; }
        .history-table td { padding: 0.75rem; border-bottom: 1px solid #f0f0f0; }
        .history-table tr:last-child td { border-bottom: none; }

        .gps-info { background: #f8f9fa; border-radius: 8px; padding: 0.75rem; font-size: 0.85rem; margin-top: 0.5rem; }
        .gps-info .label { color: #6c757d; }
        .gps-info .value { font-weight: 600; font-family: monospace; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <!-- Header -->
        <header class="header">
            <div class="header-left">
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">Attendance</h1>
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

        <div class="page-content">
            <?php if ($error): ?>
                <div class="alert alert-danger" style="border-radius:8px;padding:1rem;margin-bottom:1.5rem;">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success" style="background:rgba(40,167,69,0.1);color:#28a745;border-left:4px solid #28a745;border-radius:8px;padding:1rem;margin-bottom:1.5rem;">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (!$org): ?>
                <div class="status-card" style="border-left-color:#dc3545;">
                    <h4 style="color:#dc3545;"><i class="fas fa-exclamation-triangle me-2"></i>No Organization Assigned</h4>
                    <p style="color:#6c757d;margin-top:0.5rem;">You are not assigned to any host organization. Please contact your supervisor or coordinator to be assigned to an organization before you can check in.</p>
                </div>
            <?php else: ?>
                <!-- Current Status -->
                <div class="status-card">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h4 style="color:var(--primary-color);margin:0;">Today's Attendance</h4>
                            <p style="color:#6c757d;margin:0.25rem 0 0 0;"><?php echo date('l, F j, Y'); ?></p>
                        </div>
                        <div>
                            <?php
                            $todayStatus = $todayAtt ? $todayAtt['status'] : null;
                            $statusClass = 'status-none';
                            $statusText = 'Not Checked In';
                            $statusIcon = 'fa-clock';
                            if ($todayStatus === 'Present') { $statusClass = 'status-present'; $statusText = 'Present'; $statusIcon = 'fa-check-circle'; }
                            elseif ($todayStatus === 'Outside Zone') { $statusClass = 'status-outside'; $statusText = 'Outside Zone'; $statusIcon = 'fa-exclamation-triangle'; }
                            elseif ($todayStatus === 'Absent') { $statusClass = 'status-absent'; $statusText = 'Absent'; $statusIcon = 'fa-times-circle'; }
                            ?>
                            <span class="status-badge <?php echo $statusClass; ?>">
                                <i class="fas <?php echo $statusIcon; ?>"></i> <?php echo $statusText; ?>
                            </span>
                        </div>
                    </div>

                    <?php if ($todayAtt): ?>
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <div class="gps-info">
                                    <span class="label">Check-in:</span>
                                    <span class="value"><?php echo $todayAtt['check_in_time'] ?: '—'; ?></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="gps-info">
                                    <span class="label">Check-out:</span>
                                    <span class="value"><?php echo $todayAtt['check_out_time'] ?: '—'; ?></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="gps-info">
                                    <span class="label">Organization:</span>
                                    <span class="value"><?php echo htmlspecialchars($org['org_name']); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Check-in / Check-out -->
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="status-card">
                            <h5 style="color:var(--primary-color);margin-bottom:1rem;">
                                <i class="fas fa-map-marker-alt me-2"></i>GPS Check-in / Check-out
                            </h5>
                            <p style="color:#6c757d;font-size:0.9rem;margin-bottom:1rem;">
                                Your organization's geofence is at <strong><?php echo htmlspecialchars($org['org_name']); ?></strong>
                                with a <?php echo $org['geofence_radius_m']; ?>m radius.
                                Click the button below to capture your location and check in/out.
                            </p>

                            <form method="POST" id="attendanceForm">
                                <input type="hidden" name="latitude" id="latInput">
                                <input type="hidden" name="longitude" id="lngInput">
                                <input type="hidden" name="action" id="actionInput">

                                <?php if ($isCheckedIn): ?>
                                    <button type="button" class="checkout-btn" onclick="captureAndSubmit('checkout')">
                                        <i class="fas fa-sign-out-alt me-2"></i>Check Out
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="checkin-btn" onclick="captureAndSubmit('checkin')">
                                        <i class="fas fa-sign-in-alt me-2"></i>Check In
                                    </button>
                                <?php endif; ?>
                            </form>

                            <div id="gpsStatus" style="margin-top:1rem;font-size:0.85rem;"></div>

                            <!-- Manual location entry (for testing / when GPS is unavailable) -->
                            <div style="margin-top:1rem;border-top:1px solid #e9ecef;padding-top:1rem;">
                                <a href="#" style="font-size:0.85rem;color:var(--primary-color);text-decoration:none;" onclick="document.getElementById('manualBox').style.display='block';this.style.display='none';return false;">
                                    <i class="fas fa-keyboard me-1"></i>Enter location manually
                                </a>
                                <div id="manualBox" style="display:none;margin-top:0.75rem;">
                                    <p style="font-size:0.8rem;color:#6c757d;margin-bottom:0.5rem;">Enter coordinates manually (useful for testing or when GPS is blocked):</p>
                                    <div class="row g-2 mb-2">
                                        <div class="col">
                                            <input type="number" class="form-control form-control-sm" id="manualLat" placeholder="Latitude" step="0.0000001">
                                        </div>
                                        <div class="col">
                                            <input type="number" class="form-control form-control-sm" id="manualLng" placeholder="Longitude" step="0.0000001">
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="manualSubmit('checkin')">
                                        <i class="fas fa-sign-in-alt"></i> Manual Check In
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="manualSubmit('checkout')">
                                        <i class="fas fa-sign-out-alt"></i> Manual Check Out
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="useOrgLocation()">
                                        <i class="fas fa-crosshairs"></i> Use Org Location
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="status-card">
                            <h5 style="color:var(--primary-color);margin-bottom:0.5rem;">
                                <i class="fas fa-map me-2"></i>Your Geofence
                            </h5>
                            <p style="color:#6c757d;font-size:0.85rem;">The green circle shows your organization's geofence boundary. You must be inside it to be marked Present.</p>
                            <div id="map"></div>
                        </div>
                    </div>
                </div>

                <!-- Stats -->
                <div class="row g-3 mt-3">
                    <div class="col-6 col-md-3">
                        <div class="stat-card stat-present">
                            <div class="num"><?php echo $stats['present'] ?: 0; ?></div>
                            <div class="lbl">Present Days</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-card stat-outside">
                            <div class="num"><?php echo $stats['outside'] ?: 0; ?></div>
                            <div class="lbl">Outside Zone</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-card stat-absent">
                            <div class="num"><?php echo $stats['absent'] ?: 0; ?></div>
                            <div class="lbl">Absent Days</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-card stat-total">
                            <div class="num"><?php echo $stats['total'] ?: 0; ?></div>
                            <div class="lbl">Total Records</div>
                        </div>
                    </div>
                </div>

                <!-- History -->
                <div class="mt-4">
                    <h5 style="color:var(--primary-color);margin-bottom:1rem;">
                        <i class="fas fa-history me-2"></i>Attendance History
                    </h5>
                    <?php if (count($history) > 0): ?>
                        <div class="table-responsive history-table">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Check In</th>
                                        <th>Check Out</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($history as $h): ?>
                                        <tr>
                                            <td><?php echo date('M j, Y', strtotime($h['date'])); ?></td>
                                            <td><?php echo $h['check_in_time'] ?: '—'; ?></td>
                                            <td><?php echo $h['check_out_time'] ?: '—'; ?></td>
                                            <td>
                                                <?php
                                                $cls = 'status-none';
                                                if ($h['status'] === 'Present') $cls = 'status-present';
                                                elseif ($h['status'] === 'Outside Zone') $cls = 'status-outside';
                                                elseif ($h['status'] === 'Absent') $cls = 'status-absent';
                                                ?>
                                                <span class="status-badge <?php echo $cls; ?>" style="font-size:0.8rem;padding:0.25rem 0.6rem;">
                                                    <?php echo htmlspecialchars($h['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="status-card" style="text-align:center;color:#6c757d;">
                            <i class="fas fa-calendar-times" style="font-size:2rem;opacity:0.3;"></i>
                            <p style="margin-top:0.5rem;">No attendance records yet. Check in to get started!</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="assets/student-scripts.js"></script>
    <?php if ($org): ?>
    <script>
        // Initialize map showing the geofence
        const orgLat = <?php echo $org['geo_latitude']; ?>;
        const orgLng = <?php echo $org['geo_longitude']; ?>;
        const orgRadius = <?php echo (int)$org['geofence_radius_m']; ?>;

        const map = L.map('map').setView([orgLat, orgLng], 16);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap', maxZoom: 19
        }).addTo(map);

        // Geofence circle
        L.circle([orgLat, orgLng], {
            radius: orgRadius, color: '#1a4d2e', fillColor: '#4a7c59', fillOpacity: 0.2
        }).addTo(map);

        // Organization marker
        L.marker([orgLat, orgLng]).addTo(map)
            .bindPopup('<?php echo htmlspecialchars($org['org_name']); ?><br>Geofence: <?php echo $org["geofence_radius_m"]; ?>m');

        // Capture GPS and submit form
        // Manual location entry (for testing or when GPS is blocked)
        function manualSubmit(action) {
            const lat = document.getElementById('manualLat').value;
            const lng = document.getElementById('manualLng').value;
            if (!lat || !lng) { alert('Please enter both latitude and longitude.'); return; }
            document.getElementById('latInput').value = lat;
            document.getElementById('lngInput').value = lng;
            document.getElementById('actionInput').value = action;
            document.getElementById('gpsStatus').innerHTML = '<i class="fas fa-check me-1" style="color:#28a745;"></i>Manual location: ' + parseFloat(lat).toFixed(6) + ', ' + parseFloat(lng).toFixed(6) + '. Submitting...';
            document.getElementById('attendanceForm').submit();
        }

        // Fill the manual fields with the org's geofence center (simulates being at the org)
        function useOrgLocation() {
            document.getElementById('manualLat').value = orgLat;
            document.getElementById('manualLng').value = orgLng;
        }

        function captureAndSubmit(action) {
            const statusDiv = document.getElementById('gpsStatus');
            const btn = event.target.closest('button');
            btn.disabled = true;
            statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Capturing your GPS location...';

            if (!navigator.geolocation) {
                statusDiv.innerHTML = '<span style="color:#dc3545;"><i class="fas fa-times me-1"></i>GPS not supported on this device.</span>';
                btn.disabled = false;
                return;
            }

            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    document.getElementById('latInput').value = lat;
                    document.getElementById('lngInput').value = lng;
                    document.getElementById('actionInput').value = action;
                    statusDiv.innerHTML = '<i class="fas fa-check me-1" style="color:#28a745;"></i>Location captured: ' + lat.toFixed(6) + ', ' + lng.toFixed(6) + ' (accuracy: ±' + Math.round(pos.coords.accuracy) + 'm). Submitting...';

                    // Show user's position on map
                    L.marker([lat, lng], {
                        icon: L.divIcon({ className: 'user-marker', html: '<div style="background:#007bff;width:16px;height:16px;border-radius:50%;border:3px solid white;box-shadow:0 0 5px rgba(0,0,0,0.5);"></div>', iconSize: [16,16], iconAnchor: [8,8] })
                    }).addTo(map).bindPopup('Your location');

                    document.getElementById('attendanceForm').submit();
                },
                function(err) {
                    btn.disabled = false;
                    let msg = 'Could not capture your location.';
                    if (err.code === 1) msg = 'Location permission denied. Please allow location access.';
                    if (err.code === 2) msg = 'Location unavailable. Try again outside.';
                    if (err.code === 3) msg = 'Location request timed out. Try again.';
                    statusDiv.innerHTML = '<span style="color:#dc3545;"><i class="fas fa-times me-1"></i>' + msg + '</span>';
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        }
    </script>
    <?php endif; ?>
</body>
</html>
