<?php
session_start();
require_once '../backend/config/db.php';
require_once '../backend/config/session.php';
require_once '../backend/models/Organization.php';

if (!isLoggedIn() || (!hasRole('admin') && !hasRole('coordinator'))) {
    header('Location: login.php?error=Access denied');
    exit();
}

$id = (int)($_GET['id'] ?? 0);
$org = Organization::find($pdo, $id);

if (!$org) {
    header('Location: organizations.php?msg=Organization+not+found');
    exit();
}

$error = '';
$success = '';

// Handle form submission (update org)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_org'])) {
    $org_name = trim($_POST['org_name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $geo_latitude = $_POST['geo_latitude'] ?? '';
    $geo_longitude = $_POST['geo_longitude'] ?? '';
    $geofence_radius_m = (int)($_POST['geofence_radius_m'] ?? 100);

    if (empty($org_name) || empty($geo_latitude) || empty($geo_longitude)) {
        $error = 'Organization name and geofence coordinates are required.';
    } else {
        try {
            Organization::update($pdo, $id, [
                'org_name' => $org_name,
                'address' => $address,
                'geo_latitude' => $geo_latitude,
                'geo_longitude' => $geo_longitude,
                'geofence_radius_m' => $geofence_radius_m
            ]);
            $success = 'Organization updated successfully.';
            $org = Organization::find($pdo, $id); // reload
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle assign student to org
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_student'])) {
    $student_id = (int)$_POST['student_id'];
    $pdo->prepare("UPDATE users SET organization_id = ? WHERE id = ? AND role = 'student'")->execute([$id, $student_id]);
    $success = 'Student assigned to organization.';
}

// Handle remove student from org
if (isset($_GET['remove_student']) && is_numeric($_GET['remove_student'])) {
    $pdo->prepare("UPDATE users SET organization_id = NULL WHERE id = ? AND organization_id = ?")->execute([(int)$_GET['remove_student'], $id]);
    header("Location: organization-edit.php?id=$id&msg=Student+removed");
    exit();
}

// Handle assign supervisor to org
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_supervisor'])) {
    $supervisor_id = (int)$_POST['supervisor_id'];
    $supervisor_type = $_POST['supervisor_type'] ?? 'school';
    $pdo->prepare("UPDATE users SET organization_id = ?, supervisor_type = ? WHERE id = ? AND role = 'supervisor'")->execute([$id, $supervisor_type, $supervisor_id]);
    $success = 'Supervisor assigned to organization.';
}

// Handle remove supervisor from org
if (isset($_GET['remove_supervisor']) && is_numeric($_GET['remove_supervisor'])) {
    $pdo->prepare("UPDATE users SET organization_id = NULL WHERE id = ? AND organization_id = ?")->execute([(int)$_GET['remove_supervisor'], $id]);
    header("Location: organization-edit.php?id=$id&msg=Supervisor+removed");
    exit();
}

$msg = $_GET['msg'] ?? '';
$students = Organization::getStudents($pdo, $id);
$supervisors = Organization::getSupervisors($pdo, $id);

// Get unassigned students and supervisors for dropdowns
$unassignedStudents = $pdo->query("SELECT id, full_name, matric_number FROM users WHERE role='student' AND (organization_id IS NULL OR organization_id != $id) ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
$unassignedSupervisors = $pdo->query("SELECT id, full_name, email FROM users WHERE role='supervisor' AND (organization_id IS NULL OR organization_id != $id) ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Organization - SIWES Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="assets/admin-styles.css">
    <style>
        #map { height: 350px; border-radius: 12px; border: 2px solid #e9ecef; margin-bottom: 1rem; }
        .form-card { background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 4px 15px rgba(0,0,0,0.08); margin-bottom: 1.5rem; }
        .form-label { font-weight: 600; color: #2c3e50; margin-bottom: 0.5rem; }
        .form-control { border-radius: 8px; border: 2px solid #e9ecef; padding: 0.6rem 0.9rem; }
        .form-control:focus { border-color: var(--primary-color); box-shadow: 0 0 0 0.2rem rgba(26,77,46,0.15); }
        .btn-save { background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; border: none; padding: 0.75rem 2rem; border-radius: 8px; font-weight: 600; }
        .btn-save:hover { color: white; transform: translateY(-1px); }
        .btn-cancel { background: #f8f9fa; color: #6c757d; border: 2px solid #e9ecef; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600; text-decoration: none; }
        .btn-cancel:hover { background: #e9ecef; color: #2c3e50; text-decoration: none; }
        .map-hint { background: #fff3cd; border-radius: 8px; padding: 0.75rem 1rem; font-size: 0.85rem; color: #856404; margin-bottom: 1rem; }
        .coord-display { background: #f8f9fa; border-radius: 8px; padding: 0.75rem; font-family: monospace; font-size: 0.9rem; margin-top: 0.5rem; }
        .section-title { font-size: 1.1rem; font-weight: 700; color: var(--primary-color); margin-bottom: 1rem; }
        .person-row { display: flex; justify-content: space-between; align-items: center; padding: 0.6rem 0; border-bottom: 1px solid #f0f0f0; }
        .person-row:last-child { border-bottom: none; }
        .person-name { font-weight: 600; color: #2c3e50; }
        .person-detail { font-size: 0.85rem; color: #6c757d; }
        .badge-type { font-size: 0.7rem; padding: 0.2rem 0.5rem; border-radius: 4px; }
        .badge-industry { background: rgba(23,162,184,0.15); color: #17a2b8; }
        .badge-school { background: rgba(255,193,7,0.15); color: #856404; }
        .btn-remove { font-size: 0.8rem; color: #dc3545; text-decoration: none; }
        .btn-remove:hover { color: #c82333; text-decoration: underline; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php $pageTitle = 'Edit Organization'; include 'includes/header.php'; ?>

        <div class="page-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 style="color:var(--primary-color);font-weight:700;margin:0;"><?php echo htmlspecialchars($org['org_name']); ?></h2>
                    <p style="color:#6c757d;margin:0.5rem 0 0 0;">Edit geofence boundary and manage assigned people</p>
                </div>
                <a href="organizations.php" class="btn-cancel"><i class="fas fa-arrow-left me-1"></i>Back</a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger" style="border-radius:8px;padding:1rem;margin-bottom:1.5rem;">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <?php if ($success || $msg): ?>
                <div class="alert alert-success" style="background:rgba(40,167,69,0.1);color:#28a745;border-left:4px solid #28a745;border-radius:8px;padding:1rem;margin-bottom:1.5rem;">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success ?: $msg); ?>
                </div>
            <?php endif; ?>

            <!-- Geofence Settings -->
            <div class="form-card">
                <h3 class="section-title"><i class="fas fa-map-marked-alt me-2"></i>Geofence Settings</h3>
                <form method="POST" id="orgForm">
                    <input type="hidden" name="update_org" value="1">
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label">Organization Name <span style="color:#dc3545;">*</span></label>
                            <input type="text" class="form-control" name="org_name" required value="<?php echo htmlspecialchars($org['org_name']); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Geofence Radius (meters)</label>
                            <input type="number" class="form-control" name="geofence_radius_m" min="10" max="5000" value="<?php echo htmlspecialchars($org['geofence_radius_m']); ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="address" id="addressInput" value="<?php echo htmlspecialchars($org['address']); ?>">
                            <button type="button" class="btn btn-outline-secondary" id="searchBtn" onclick="searchAddress()">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                        <small class="text-muted">Type an address and click Search to find it on the map.</small>
                    </div>

                    <div class="map-hint">
                        <i class="fas fa-info-circle me-1"></i>
                        Click on the map to move the geofence center. The green circle shows the boundary.
                    </div>

                    <div id="map"></div>

                    <div class="coord-display">
                        <strong>Geofence Center:</strong>
                        <span id="coordText">Lat: <?php echo $org['geo_latitude']; ?>, Lng: <?php echo $org['geo_longitude']; ?></span>
                    </div>

                    <input type="hidden" name="geo_latitude" id="latInput" value="<?php echo $org['geo_latitude']; ?>">
                    <input type="hidden" name="geo_longitude" id="lngInput" value="<?php echo $org['geo_longitude']; ?>">

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-save"><i class="fas fa-save me-1"></i> Save Changes</button>
                        <a href="organizations.php" class="btn-cancel">Cancel</a>
                    </div>
                </form>
            </div>

            <!-- Assigned Students -->
            <div class="form-card">
                <h3 class="section-title"><i class="fas fa-user-graduate me-2"></i>Assigned Students (<?php echo count($students); ?>)</h3>

                <?php if (count($students) > 0): ?>
                    <?php foreach ($students as $s): ?>
                        <div class="person-row">
                            <div>
                                <div class="person-name"><?php echo htmlspecialchars($s['full_name']); ?></div>
                                <div class="person-detail"><?php echo htmlspecialchars($s['matric_number'] ?? 'No matric'); ?> — <?php echo htmlspecialchars($s['department'] ?? ''); ?></div>
                            </div>
                            <a href="organization-edit.php?id=<?php echo $id; ?>&remove_student=<?php echo $s['id']; ?>" class="btn-remove"
                               onclick="return confirm('Remove this student from the organization?')">
                                <i class="fas fa-times"></i> Remove
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#6c757d;">No students assigned yet.</p>
                <?php endif; ?>

                <?php if (count($unassignedStudents) > 0): ?>
                    <form method="POST" class="mt-3">
                        <input type="hidden" name="assign_student" value="1">
                        <div class="row g-2 align-items-end">
                            <div class="col">
                                <select name="student_id" class="form-control" required>
                                    <option value="">-- Select a student to assign --</option>
                                    <?php foreach ($unassignedStudents as $s): ?>
                                        <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['full_name'] . ' (' . ($s['matric_number'] ?? 'no matric') . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-save"><i class="fas fa-plus"></i> Assign</button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Assigned Supervisors -->
            <div class="form-card">
                <h3 class="section-title"><i class="fas fa-user-tie me-2"></i>Assigned Supervisors (<?php echo count($supervisors); ?>)</h3>

                <?php if (count($supervisors) > 0): ?>
                    <?php foreach ($supervisors as $s): ?>
                        <div class="person-row">
                            <div>
                                <div class="person-name">
                                    <?php echo htmlspecialchars($s['full_name']); ?>
                                    <?php if (!empty($s['supervisor_type'])): ?>
                                        <span class="badge badge-type badge-<?php echo $s['supervisor_type']; ?>"><?php echo ucfirst($s['supervisor_type']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="person-detail"><?php echo htmlspecialchars($s['email']); ?></div>
                            </div>
                            <a href="organization-edit.php?id=<?php echo $id; ?>&remove_supervisor=<?php echo $s['id']; ?>" class="btn-remove"
                               onclick="return confirm('Remove this supervisor from the organization?')">
                                <i class="fas fa-times"></i> Remove
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#6c757d;">No supervisors assigned yet.</p>
                <?php endif; ?>

                <?php if (count($unassignedSupervisors) > 0): ?>
                    <form method="POST" class="mt-3">
                        <input type="hidden" name="assign_supervisor" value="1">
                        <div class="row g-2 align-items-end">
                            <div class="col">
                                <select name="supervisor_id" class="form-control" required>
                                    <option value="">-- Select a supervisor to assign --</option>
                                    <?php foreach ($unassignedSupervisors as $s): ?>
                                        <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['full_name'] . ' (' . $s['email'] . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-auto">
                                <select name="supervisor_type" class="form-control" style="width:auto;">
                                    <option value="school">School-based</option>
                                    <option value="industry">Industry-based</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-save"><i class="fas fa-plus"></i> Assign</button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="assets/admin-scripts.js"></script>
    <script>
        const initLat = <?php echo $org['geo_latitude']; ?>;
        const initLng = <?php echo $org['geo_longitude']; ?>;
        const initRadius = <?php echo (int)$org['geofence_radius_m']; ?>;

        const map = L.map('map').setView([initLat, initLng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap', maxZoom: 19
        }).addTo(map);

        let marker = L.marker([initLat, initLng]).addTo(map);
        let circle = L.circle([initLat, initLng], {
            radius: initRadius, color: '#1a4d2e', fillColor: '#4a7c59', fillOpacity: 0.2
        }).addTo(map);

        function setGeofence(lat, lng, radius) {
            map.removeLayer(marker);
            map.removeLayer(circle);
            marker = L.marker([lat, lng]).addTo(map);
            circle = L.circle([lat, lng], { radius: radius, color: '#1a4d2e', fillColor: '#4a7c59', fillOpacity: 0.2 }).addTo(map);
            document.getElementById('latInput').value = lat.toFixed(7);
            document.getElementById('lngInput').value = lng.toFixed(7);
            document.getElementById('coordText').textContent = `Lat: ${lat.toFixed(7)}, Lng: ${lng.toFixed(7)}`;
        }

        map.on('click', function(e) {
            const radius = parseInt(document.querySelector('[name="geofence_radius_m"]').value) || 100;
            setGeofence(e.latlng.lat, e.latlng.lng, radius);
        });

        document.querySelector('[name="geofence_radius_m"]').addEventListener('input', function() {
            const lat = parseFloat(document.getElementById('latInput').value);
            const lng = parseFloat(document.getElementById('lngInput').value);
            if (lat && lng) setGeofence(lat, lng, parseInt(this.value) || 100);
        });

        // Search address using OpenStreetMap Nominatim (free, no API key)
        function searchAddress() {
            const address = document.getElementById('addressInput').value.trim();
            if (!address) { alert('Please type an address first.'); return; }

            const btn = document.getElementById('searchBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Searching...';

            fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(address) + '&limit=1')
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-search"></i> Search';

                    if (data && data.length > 0) {
                        const lat = parseFloat(data[0].lat);
                        const lng = parseFloat(data[0].lon);
                        const radius = parseInt(document.querySelector('[name="geofence_radius_m"]').value) || 100;
                        setGeofence(lat, lng, radius);
                        map.setView([lat, lng], 16);
                    } else {
                        alert('Address not found. Try a more specific address.');
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-search"></i> Search';
                    alert('Search failed. Check your internet connection.');
                });
        }

        document.getElementById('addressInput').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchAddress();
            }
        });
    </script>
</body>
</html>
