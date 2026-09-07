<?php
session_start();
require_once '../backend/config/db.php';
require_once '../backend/config/session.php';
require_once '../backend/models/LocationLog.php';
require_once '../backend/models/Attendance.php';

if (!isLoggedIn() || !hasRole('supervisor')) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'] ?? 'Supervisor';

// Get all assigned students with their latest location and today's attendance
$stmt = $pdo->prepare("
    SELECT u.id, u.full_name, u.matric_number, u.organization_id,
           o.org_name, o.geo_latitude, o.geo_longitude, o.geofence_radius_m,
           ll.latitude, ll.longitude, ll.captured_at, ll.in_geofence,
           a.check_in_time, a.check_out_time, a.status as att_status
    FROM users u
    LEFT JOIN organizations o ON o.id = u.organization_id
    LEFT JOIN location_logs ll ON ll.id = (
        SELECT id FROM location_logs WHERE intern_id = u.id ORDER BY captured_at DESC LIMIT 1
    )
    LEFT JOIN attendance a ON a.intern_id = u.id AND a.date = CURDATE()
    WHERE u.supervisor_id = ? AND u.role = 'student'
    ORDER BY u.full_name
");
$stmt->execute([$user_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get attendance summary for today
$attSummary = Attendance::getSummary($pdo, $user_id);

// Count students with locations
$studentsWithLocation = array_filter($students, function($s) { return $s['latitude'] !== null; });
$studentsWithoutLocation = array_filter($students, function($s) { return $s['latitude'] === null; });
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Map - SIWES Supervisor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="assets/supervisor-styles.css">
    <style>
        #map { height: calc(100vh - 280px); min-height: 400px; border-radius: 12px; border: 2px solid #e9ecef; }
        .map-card { background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); }
        .stat-pill {
            display: inline-flex; align-items: center; gap: 0.4rem;
            padding: 0.4rem 0.8rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600;
        }
        .pill-present { background: rgba(40,167,69,0.15); color: #28a745; }
        .pill-outside { background: rgba(255,193,7,0.15); color: #856404; }
        .pill-absent { background: rgba(220,53,69,0.15); color: #dc3545; }
        .pill-total { background: rgba(26,77,46,0.15); color: var(--primary-color); }

        .student-list-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 0.6rem 0.75rem; border-radius: 8px; cursor: pointer; transition: background 0.2s;
        }
        .student-list-item:hover { background: #f0f4f8; }
        .student-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
        .dot-green { background: #28a745; }
        .dot-red { background: #dc3545; }
        .dot-gray { background: #adb5bd; }
        .student-name { font-weight: 600; font-size: 0.9rem; }
        .student-status { font-size: 0.8rem; color: #6c757d; }

        .legend {
            background: white; padding: 0.75rem 1rem; border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15); font-size: 0.85rem; line-height: 1.8;
        }
        .legend-item { display: flex; align-items: center; gap: 0.5rem; }
        .legend-dot { width: 12px; height: 12px; border-radius: 50; }

        .refresh-indicator {
            position: absolute; top: 10px; right: 10px; z-index: 1000;
            background: white; padding: 0.4rem 0.75rem; border-radius: 20px;
            font-size: 0.8rem; box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .refresh-indicator i { color: var(--primary-color); }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">Live Map</h1>
            </div>
            <div class="user-menu">
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
                    <div class="user-role">Supervisor</div>
                </div>
                <button class="logout-btn" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </button>
            </div>
        </header>

        <div class="page-content">
            <!-- Summary pills -->
            <div class="d-flex flex-wrap gap-2 mb-3">
                <span class="stat-pill pill-total">
                    <i class="fas fa-users"></i> <?php echo count($students); ?> Students
                </span>
                <span class="stat-pill pill-present">
                    <i class="fas fa-check-circle"></i> <?php echo $attSummary['present']; ?> Present
                </span>
                <span class="stat-pill pill-outside">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $attSummary['outside_zone']; ?> Outside Zone
                </span>
                <span class="stat-pill pill-absent">
                    <i class="fas fa-times-circle"></i> <?php echo $attSummary['absent']; ?> Not Checked In
                </span>
                <span class="stat-pill" style="background:rgba(23,162,184,0.15);color:#17a2b8;">
                    <i class="fas fa-satellite"></i> <?php echo count($studentsWithLocation); ?> GPS Active
                </span>
            </div>

            <div class="row g-3">
                <!-- Map -->
                <div class="col-lg-9">
                    <div class="map-card p-2" style="position:relative;">
                        <div id="map"></div>
                        <div class="refresh-indicator" id="refreshIndicator">
                            <i class="fas fa-sync-alt"></i> Auto-refresh: 30s
                        </div>
                    </div>
                </div>

                <!-- Student List -->
                <div class="col-lg-3">
                    <div class="map-card p-3" style="max-height:calc(100vh - 280px); overflow-y:auto;">
                        <h6 style="color:var(--primary-color);font-weight:700;margin-bottom:0.75rem;">
                            <i class="fas fa-list me-1"></i>Students
                        </h6>
                        <?php if (count($students) === 0): ?>
                            <p style="color:#6c757d;font-size:0.85rem;">No students assigned to you.</p>
                        <?php else: ?>
                            <?php foreach ($students as $s): ?>
                                <?php
                                $dotClass = 'dot-gray';
                                $statusText = 'No GPS data';
                                if ($s['latitude'] !== null) {
                                    $dotClass = $s['in_geofence'] ? 'dot-green' : 'dot-red';
                                    $statusText = $s['in_geofence'] ? 'Inside geofence' : 'Outside geofence';
                                }
                                if ($s['att_status']) {
                                    $statusText .= ' — ' . $s['att_status'];
                                }
                                ?>
                                <div class="student-list-item" onclick="focusStudent(<?php echo $s['id']; ?>)">
                                    <div style="display:flex;align-items:center;gap:0.5rem;">
                                        <div class="student-dot <?php echo $dotClass; ?>"></div>
                                        <div>
                                            <div class="student-name"><?php echo htmlspecialchars($s['full_name']); ?></div>
                                            <div class="student-status"><?php echo htmlspecialchars($statusText); ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="assets/supervisor-scripts.js"></script>
    <script>
        // Student data from PHP
        const students = <?php echo json_encode($students); ?>;
        const studentMarkers = {};
        const geofenceCircles = {};
        const orgsShown = new Set();

        // Initialize map centered on first student or first org
        let initLat = 9.0820, initLng = 8.6753, initZoom = 6;
        if (students.length > 0) {
            // Find first student with location
            for (const s of students) {
                if (s.latitude !== null) {
                    initLat = parseFloat(s.latitude);
                    initLng = parseFloat(s.longitude);
                    initZoom = 14;
                    break;
                }
            }
            // If no student has location, use first org
            if (initZoom === 6) {
                for (const s of students) {
                    if (s.geo_latitude !== null) {
                        initLat = parseFloat(s.geo_latitude);
                        initLng = parseFloat(s.geo_longitude);
                        initZoom = 14;
                        break;
                    }
                }
            }
        }

        const map = L.map('map').setView([initLat, initLng], initZoom);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap', maxZoom: 19
        }).addTo(map);

        // Legend
        const legend = L.control({ position: 'bottomleft' });
        legend.onAdd = function() {
            const div = L.DomUtil.create('div', 'legend');
            div.innerHTML = `
                <strong>Legend</strong><br>
                <div class="legend-item"><span class="legend-dot" style="background:#28a745;"></span> Inside geofence (Present)</div>
                <div class="legend-item"><span class="legend-dot" style="background:#dc3545;"></span> Outside geofence (Breach)</div>
                <div class="legend-item"><span class="legend-dot" style="background:#adb5bd;"></span> No GPS data</div>
                <div class="legend-item"><span style="display:inline-block;width:12px;height:12px;border:2px solid #1a4d2e;border-radius:50%;background:rgba(74,124,89,0.2);"></span> Geofence boundary</div>
            `;
            return div;
        };
        legend.addTo(map);

        // Render students and geofences
        function renderStudents() {
            students.forEach(function(s) {
                // Draw geofence circle for the org (only once per org)
                if (s.geo_latitude !== null && s.organization_id && !orgsShown.has(s.organization_id)) {
                    orgsShown.add(s.organization_id);
                    L.circle([parseFloat(s.geo_latitude), parseFloat(s.geo_longitude)], {
                        radius: parseInt(s.geofence_radius_m),
                        color: '#1a4d2e', fillColor: '#4a7c59', fillOpacity: 0.15
                    }).addTo(map).bindPopup('<strong>' + s.org_name + '</strong><br>Geofence: ' + s.geofence_radius_m + 'm');
                }

                // Draw student marker if they have a location
                if (s.latitude !== null) {
                    const lat = parseFloat(s.latitude);
                    const lng = parseFloat(s.longitude);
                    const isInside = s.in_geofence == 1 || s.in_geofence === true;
                    const color = isInside ? '#28a745' : '#dc3545';

                    // Remove old marker if exists
                    if (studentMarkers[s.id]) {
                        map.removeLayer(studentMarkers[s.id]);
                    }

                    // Custom marker
                    const icon = L.divIcon({
                        className: 'student-marker',
                        html: `<div style="background:${color};width:18px;height:18px;border-radius:50%;border:3px solid white;box-shadow:0 0 8px rgba(0,0,0,0.4);"></div>`,
                        iconSize: [18, 18], iconAnchor: [9, 9]
                    });

                    const marker = L.marker([lat, lng], { icon: icon }).addTo(map);

                    // Popup content
                    let popupContent = `
                        <div style="min-width:200px;">
                            <div style="font-weight:700;font-size:1rem;color:${color};margin-bottom:0.25rem;">
                                ${s.full_name}
                            </div>
                            <div style="font-size:0.85rem;color:#6c757d;margin-bottom:0.5rem;">
                                Matric: ${s.matric_number || 'N/A'}<br>
                                Organization: ${s.org_name || 'N/A'}
                            </div>
                            <div style="font-size:0.85rem;">
                                <strong>Status:</strong>
                                <span style="color:${color};font-weight:600;">${isInside ? 'Inside geofence' : 'OUTSIDE geofence'}</span><br>
                                <strong>Attendance:</strong> ${s.att_status || 'Not checked in'}<br>
                                <strong>Check-in:</strong> ${s.check_in_time || '—'}<br>
                                <strong>Check-out:</strong> ${s.check_out_time || '—'}<br>
                                <strong>Last GPS:</strong> ${s.captured_at ? new Date(s.captured_at).toLocaleString() : 'N/A'}
                            </div>
                        </div>
                    `;
                    marker.bindPopup(popupContent);
                    studentMarkers[s.id] = marker;
                }
            });
        }

        // Focus on a student when clicked in the list
        function focusStudent(studentId) {
            if (studentMarkers[studentId]) {
                const marker = studentMarkers[studentId];
                map.setView(marker.getLatLng(), 16);
                marker.openPopup();
            } else {
                // Find the student's org and fly there
                const s = students.find(st => st.id == studentId);
                if (s && s.geo_latitude) {
                    map.setView([parseFloat(s.geo_latitude), parseFloat(s.geo_longitude)], 15);
                } else {
                    alert('No location data for this student yet.');
                }
            }
        }

        // Initial render
        renderStudents();

        // Auto-refresh every 30 seconds
        let refreshCountdown = 30;
        const refreshDiv = document.getElementById('refreshIndicator');
        setInterval(function() {
            refreshCountdown--;
            if (refreshCountdown <= 0) {
                refreshDiv.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> Refreshing...';
                // Reload the page to get fresh data
                location.reload();
            } else {
                refreshDiv.innerHTML = '<i class="fas fa-sync-alt"></i> Auto-refresh: ' + refreshCountdown + 's';
            }
        }, 1000);
    </script>
</body>
</html>
