<?php
session_start();
require_once '../backend/config/db.php';
require_once '../backend/config/session.php';
require_once '../backend/models/Organization.php';

if (!isLoggedIn() || (!hasRole('admin') && !hasRole('coordinator'))) {
    header('Location: login.php?error=Access denied');
    exit();
}

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $org_name = trim($_POST['org_name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $geo_latitude = $_POST['geo_latitude'] ?? '';
    $geo_longitude = $_POST['geo_longitude'] ?? '';
    $geofence_radius_m = (int)($_POST['geofence_radius_m'] ?? 100);
    $student_ids = $_POST['student_ids'] ?? [];
    $supervisor_ids = $_POST['supervisor_ids'] ?? [];
    $supervisor_types = $_POST['supervisor_types'] ?? [];

    if (empty($org_name) || empty($geo_latitude) || empty($geo_longitude)) {
        $error = 'Organization name and geofence coordinates are required.';
    } else {
        try {
            $id = Organization::create($pdo, [
                'org_name' => $org_name,
                'address' => $address,
                'geo_latitude' => $geo_latitude,
                'geo_longitude' => $geo_longitude,
                'geofence_radius_m' => $geofence_radius_m
            ]);
            if ($id) {
                // Assign selected students
                if (!empty($student_ids)) {
                    $stmt = $pdo->prepare("UPDATE users SET organization_id = ? WHERE id = ? AND role = 'student'");
                    foreach ($student_ids as $sid) {
                        $stmt->execute([$id, (int)$sid]);
                    }
                }
                // Assign selected supervisors with their type
                if (!empty($supervisor_ids)) {
                    $stmt = $pdo->prepare("UPDATE users SET organization_id = ?, supervisor_type = ? WHERE id = ? AND role = 'supervisor'");
                    foreach ($supervisor_ids as $idx => $sid) {
                        $type = $supervisor_types[$idx] ?? 'school';
                        $stmt->execute([$id, $type, (int)$sid]);
                    }
                }
                header('Location: organization-edit.php?id=' . $id . '&msg=Organization+created+successfully');
                exit();
            } else {
                $error = 'Failed to create organization.';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get all students and supervisors for selection
$allStudents = $pdo->query("SELECT id, full_name, matric_number, department FROM users WHERE role='student' ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
$allSupervisors = $pdo->query("SELECT id, full_name, email FROM users WHERE role='supervisor' ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Organization - SIWES Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="assets/admin-styles.css">
    <style>
        #map {
            height: 400px;
            border-radius: 12px;
            border: 2px solid #e9ecef;
            margin-bottom: 1rem;
        }
        .form-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        .form-label { font-weight: 600; color: #2c3e50; margin-bottom: 0.5rem; }
        .form-control { border-radius: 8px; border: 2px solid #e9ecef; padding: 0.6rem 0.9rem; }
        .form-control:focus { border-color: var(--primary-color); box-shadow: 0 0 0 0.2rem rgba(26,77,46,0.15); }
        .btn-save {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white; border: none; padding: 0.75rem 2rem; border-radius: 8px; font-weight: 600;
        }
        .btn-save:hover { color: white; transform: translateY(-1px); }
        .btn-cancel {
            background: #f8f9fa; color: #6c757d; border: 2px solid #e9ecef; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600; text-decoration: none;
        }
        .btn-cancel:hover { background: #e9ecef; color: #2c3e50; text-decoration: none; }
        .map-hint {
            background: #fff3cd; border-radius: 8px; padding: 0.75rem 1rem; font-size: 0.85rem; color: #856404; margin-bottom: 1rem;
        }
        .coord-display {
            background: #f8f9fa; border-radius: 8px; padding: 0.75rem; font-family: monospace; font-size: 0.9rem; margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php $pageTitle = 'Add Organization'; include 'includes/header.php'; ?>

        <div class="page-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 style="color:var(--primary-color);font-weight:700;margin:0;">Add Host Organization</h2>
                    <p style="color:#6c757d;margin:0.5rem 0 0 0;">Set up a new organization and draw its geofence boundary</p>
                </div>
                <a href="organizations.php" class="btn-cancel"><i class="fas fa-arrow-left me-1"></i>Back</a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger" style="border-radius:8px;padding:1rem;margin-bottom:1.5rem;">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="form-card">
                <form method="POST" id="orgForm">
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label">Organization Name <span style="color:#dc3545;">*</span></label>
                            <input type="text" class="form-control" name="org_name" required
                                   placeholder="e.g. Tech Solutions Ltd" value="<?php echo htmlspecialchars($_POST['org_name'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Geofence Radius (meters)</label>
                            <input type="number" class="form-control" name="geofence_radius_m" min="10" max="5000"
                                   value="<?php echo htmlspecialchars($_POST['geofence_radius_m'] ?? '100'); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="address" id="addressInput"
                                   placeholder="e.g. 123 Business District, Lagos, Nigeria" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
                            <button type="button" class="btn btn-outline-secondary" id="searchBtn" onclick="searchAddress()">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                        <small class="text-muted">Type an address and click Search to find it on the map.</small>
                    </div>

                    <div class="map-hint">
                        <i class="fas fa-info-circle me-1"></i>
                        Click on the map to set the geofence center. The circle shows the geofence boundary. Adjust the radius above.
                    </div>

                    <div id="map"></div>

                    <div class="coord-display">
                        <strong>Geofence Center:</strong>
                        <span id="coordText">Not set yet — click on the map</span>
                    </div>

                    <input type="hidden" name="geo_latitude" id="latInput" value="">
                    <input type="hidden" name="geo_longitude" id="lngInput" value="">

                    <!-- Assign Students -->
                    <div class="mt-4 mb-3">
                        <label class="form-label">
                            <i class="fas fa-user-graduate me-1"></i>Assign Students
                            <small style="font-weight:400;color:#6c757d;">(optional — you can do this later)</small>
                        </label>
                        <?php if (count($allStudents) > 0): ?>
                            <div style="max-height:200px;overflow-y:auto;border:1px solid #e9ecef;border-radius:8px;padding:0.5rem;">
                                <?php foreach ($allStudents as $s): ?>
                                    <div class="form-check" style="padding:0.4rem 0.4rem 0.4rem 2rem;border-radius:6px;">
                                        <input class="form-check-input" type="checkbox" name="student_ids[]"
                                               value="<?php echo $s['id']; ?>" id="student_<?php echo $s['id']; ?>">
                                        <label class="form-check-label" for="student_<?php echo $s['id']; ?>" style="font-size:0.9rem;">
                                            <strong><?php echo htmlspecialchars($s['full_name']); ?></strong>
                                            <span style="color:#6c757d;">— <?php echo htmlspecialchars($s['matric_number'] ?? 'no matric'); ?>
                                            <?php if ($s['department']): ?>, <?php echo htmlspecialchars($s['department']); ?><?php endif; ?></span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="color:#6c757d;font-size:0.9rem;">No students registered yet.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Assign Supervisors -->
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-user-tie me-1"></i>Assign Supervisors
                            <small style="font-weight:400;color:#6c757d;">(optional — you can do this later)</small>
                        </label>
                        <?php if (count($allSupervisors) > 0): ?>
                            <div style="max-height:200px;overflow-y:auto;border:1px solid #e9ecef;border-radius:8px;padding:0.5rem;">
                                <?php foreach ($allSupervisors as $idx => $s): ?>
                                    <div class="row g-2 align-items-center" style="padding:0.4rem;border-radius:6px;">
                                        <div class="col-auto">
                                            <input class="form-check-input" type="checkbox" name="supervisor_ids[]"
                                                   value="<?php echo $s['id']; ?>" id="sup_<?php echo $s['id']; ?>"
                                                   onchange="toggleSupType(<?php echo $idx; ?>)">
                                        </div>
                                        <div class="col">
                                            <label for="sup_<?php echo $s['id']; ?>" style="font-size:0.9rem;cursor:pointer;">
                                                <strong><?php echo htmlspecialchars($s['full_name']); ?></strong>
                                                <span style="color:#6c757d;">— <?php echo htmlspecialchars($s['email']); ?></span>
                                            </label>
                                        </div>
                                        <div class="col-auto" id="suptype_<?php echo $idx; ?>" style="display:none;">
                                            <select name="supervisor_types[]" class="form-control form-control-sm" style="width:auto;">
                                                <option value="school">School-based</option>
                                                <option value="industry">Industry-based</option>
                                            </select>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="color:#6c757d;font-size:0.9rem;">No supervisors registered yet.</p>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-save">
                            <i class="fas fa-save me-1"></i> Create Organization
                        </button>
                        <a href="organizations.php" class="btn-cancel">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="assets/admin-scripts.js"></script>
    <script>
        // Initialize Leaflet map centered on Nigeria
        const map = L.map('map').setView([9.0820, 8.6753], 6);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap',
            maxZoom: 19
        }).addTo(map);

        let marker = null;
        let circle = null;

        function setGeofence(lat, lng, radius) {
            if (marker) map.removeLayer(marker);
            if (circle) map.removeLayer(circle);

            marker = L.marker([lat, lng]).addTo(map);
            circle = L.circle([lat, lng], {
                radius: radius,
                color: '#1a4d2e',
                fillColor: '#4a7c59',
                fillOpacity: 0.2
            }).addTo(map);

            document.getElementById('latInput').value = lat.toFixed(7);
            document.getElementById('lngInput').value = lng.toFixed(7);
            document.getElementById('coordText').textContent =
                `Lat: ${lat.toFixed(7)}, Lng: ${lng.toFixed(7)}`;
        }

        // Click to set geofence center
        map.on('click', function(e) {
            const radius = parseInt(document.querySelector('[name="geofence_radius_m"]').value) || 100;
            setGeofence(e.latlng.lat, e.latlng.lng, radius);
        });

        // Update circle when radius changes
        document.querySelector('[name="geofence_radius_m"]').addEventListener('input', function() {
            if (marker) {
                const lat = parseFloat(document.getElementById('latInput').value);
                const lng = parseFloat(document.getElementById('lngInput').value);
                setGeofence(lat, lng, parseInt(this.value) || 100);
            }
        });

        // Try to use browser geolocation as starting point
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(pos) {
                map.setView([pos.coords.latitude, pos.coords.longitude], 15);
            }, function() { /* ignore — keep default Nigeria view */ });
        }

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

        // Also search when user presses Enter in the address field
        document.getElementById('addressInput').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchAddress();
            }
        });

        // Show/hide supervisor type dropdown when checkbox is toggled
        function toggleSupType(idx) {
            const typeDiv = document.getElementById('suptype_' + idx);
            const checkbox = document.querySelectorAll('input[name="supervisor_ids[]"]')[idx];
            typeDiv.style.display = checkbox.checked ? 'block' : 'none';
        }
    </script>
</body>
</html>
