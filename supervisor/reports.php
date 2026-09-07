<?php
session_start();
require_once '../backend/config/db.php';
require_once '../backend/config/session.php';
require_once '../backend/models/LocationLog.php';

if (!isLoggedIn() || !hasRole('supervisor')) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'] ?? 'Supervisor';

// Date range filter
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // first day of current month
$endDate = $_GET['end_date'] ?? date('Y-m-d'); // today
$selectedStudent = $_GET['student_id'] ?? '';

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance_report_' . $startDate . '_to_' . $endDate . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Student Name', 'Matric Number', 'Organization', 'Date', 'Check In', 'Check Out', 'Status']);

    $stmt = $pdo->prepare("
        SELECT u.full_name, u.matric_number, o.org_name, a.date, a.check_in_time, a.check_out_time, a.status
        FROM attendance a
        JOIN users u ON u.id = a.intern_id
        LEFT JOIN organizations o ON o.id = u.organization_id
        WHERE u.supervisor_id = ? AND a.date BETWEEN ? AND ?
        ORDER BY u.full_name, a.date DESC
    ");
    $stmt->execute([$user_id, $startDate, $endDate]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}

// Get all assigned students
$stmt = $pdo->prepare("
    SELECT u.id, u.full_name, u.matric_number, o.org_name
    FROM users u
    LEFT JOIN organizations o ON o.id = u.organization_id
    WHERE u.supervisor_id = ? AND u.role = 'student'
    ORDER BY u.full_name
");
$stmt->execute([$user_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get attendance data for the date range
if ($selectedStudent) {
    $stmt = $pdo->prepare("
        SELECT u.full_name, u.matric_number, o.org_name, a.date, a.check_in_time, a.check_out_time, a.status
        FROM attendance a
        JOIN users u ON u.id = a.intern_id
        LEFT JOIN organizations o ON o.id = u.organization_id
        WHERE u.supervisor_id = ? AND a.date BETWEEN ? AND ? AND u.id = ?
        ORDER BY a.date DESC
    ");
    $stmt->execute([$user_id, $startDate, $endDate, $selectedStudent]);
} else {
    $stmt = $pdo->prepare("
        SELECT u.full_name, u.matric_number, o.org_name, a.date, a.check_in_time, a.check_out_time, a.status
        FROM attendance a
        JOIN users u ON u.id = a.intern_id
        LEFT JOIN organizations o ON o.id = u.organization_id
        WHERE u.supervisor_id = ? AND a.date BETWEEN ? AND ?
        ORDER BY u.full_name, a.date DESC
    ");
    $stmt->execute([$user_id, $startDate, $endDate]);
}
$attendanceRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get summary stats
$stmt = $pdo->prepare("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN a.status = 'Outside Zone' THEN 1 ELSE 0 END) as outside,
        SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) as absent
    FROM attendance a
    JOIN users u ON u.id = a.intern_id
    WHERE u.supervisor_id = ? AND a.date BETWEEN ? AND ?
");
$stmt->execute([$user_id, $startDate, $endDate]);
$summary = $stmt->fetch(PDO::FETCH_ASSOC);

// Get movement data for selected student
$movementData = [];
if ($selectedStudent) {
    $movementData = LocationLog::getByDateRange($pdo, $selectedStudent, $startDate, $endDate);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - SIWES Supervisor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="assets/supervisor-styles.css">
    <style>
        .report-card { background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.06); margin-bottom: 1.5rem; }
        .stat-card { background: white; border-radius: 10px; padding: 1rem; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.06); }
        .stat-card .num { font-size: 1.75rem; font-weight: 700; }
        .stat-card .lbl { font-size: 0.8rem; color: #6c757d; text-transform: uppercase; }
        .stat-present .num { color: #28a745; }
        .stat-outside .num { color: #ffc107; }
        .stat-absent .num { color: #dc3545; }
        .stat-total .num { color: var(--primary-color); }
        .report-table { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.06); }
        .report-table th { background: var(--primary-color); color: white; font-size: 0.85rem; padding: 0.75rem; }
        .report-table td { padding: 0.65rem; border-bottom: 1px solid #f0f0f0; font-size: 0.9rem; }
        .report-table tr:last-child td { border-bottom: none; }
        .status-badge { font-size: 0.75rem; padding: 0.2rem 0.6rem; border-radius: 4px; font-weight: 600; }
        .badge-present { background: rgba(40,167,69,0.15); color: #28a745; }
        .badge-outside { background: rgba(255,193,7,0.15); color: #856404; }
        .badge-absent { background: rgba(220,53,69,0.15); color: #dc3545; }
        .btn-export { background: #28a745; color: white; border: none; padding: 0.5rem 1.25rem; border-radius: 8px; font-weight: 600; text-decoration: none; }
        .btn-export:hover { color: white; background: #218838; }
        #movementMap { height: 350px; border-radius: 12px; border: 2px solid #e9ecef; margin-top: 1rem; }
        .form-control { border-radius: 8px; border: 2px solid #e9ecef; }
        .form-control:focus { border-color: var(--primary-color); }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
                <h1 class="page-title">Reports</h1>
            </div>
            <div class="user-menu">
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
                    <div class="user-role">Supervisor</div>
                </div>
                <button class="logout-btn" onclick="logout()"><i class="fas fa-sign-out-alt"></i> Logout</button>
            </div>
        </header>

        <div class="page-content">
            <h2 style="color:var(--primary-color);font-weight:700;margin-bottom:0.5rem;">Attendance & Movement Reports</h2>
            <p style="color:#6c757d;margin-bottom:1.5rem;">Generate reports for your assigned students over a date range</p>

            <!-- Filters -->
            <div class="report-card">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Start Date</label>
                        <input type="date" class="form-control" name="start_date" value="<?php echo $startDate; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">End Date</label>
                        <input type="date" class="form-control" name="end_date" value="<?php echo $endDate; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Student (optional)</label>
                        <select class="form-control" name="student_id">
                            <option value="">All Students</option>
                            <?php foreach ($students as $s): ?>
                                <option value="<?php echo $s['id']; ?>" <?php echo $selectedStudent == $s['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($s['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill"><i class="fas fa-search me-1"></i>Generate</button>
                        <a href="reports.php?export=csv&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn-export d-flex align-items-center">
                            <i class="fas fa-download me-1"></i>CSV
                        </a>
                    </div>
                </form>
            </div>

            <!-- Summary Stats -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="stat-card stat-total"><div class="num"><?php echo $summary['total'] ?: 0; ?></div><div class="lbl">Total Records</div></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card stat-present"><div class="num"><?php echo $summary['present'] ?: 0; ?></div><div class="lbl">Present</div></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card stat-outside"><div class="num"><?php echo $summary['outside'] ?: 0; ?></div><div class="lbl">Outside Zone</div></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card stat-absent"><div class="num"><?php echo $summary['absent'] ?: 0; ?></div><div class="lbl">Absent</div></div>
                </div>
            </div>

            <!-- Attendance Table -->
            <h5 style="color:var(--primary-color);margin-bottom:1rem;"><i class="fas fa-table me-2"></i>Attendance Records (<?php echo date('M j, Y', strtotime($startDate)); ?> — <?php echo date('M j, Y', strtotime($endDate)); ?>)</h5>

            <?php if (count($attendanceRecords) > 0): ?>
                <div class="table-responsive report-table mb-4">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Matric</th>
                                <th>Organization</th>
                                <th>Date</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendanceRecords as $r): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($r['full_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($r['matric_number'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars($r['org_name'] ?? '—'); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($r['date'])); ?></td>
                                    <td><?php echo $r['check_in_time'] ?: '—'; ?></td>
                                    <td><?php echo $r['check_out_time'] ?: '—'; ?></td>
                                    <td>
                                        <?php
                                        $cls = 'badge-absent';
                                        if ($r['status'] === 'Present') $cls = 'badge-present';
                                        elseif ($r['status'] === 'Outside Zone') $cls = 'badge-outside';
                                        ?>
                                        <span class="status-badge <?php echo $cls; ?>"><?php echo htmlspecialchars($r['status']); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="report-card text-center" style="color:#6c757d;padding:2rem;">
                    <i class="fas fa-folder-open" style="font-size:2.5rem;opacity:0.3;"></i>
                    <p style="margin-top:0.5rem;">No attendance records found for this date range.</p>
                </div>
            <?php endif; ?>

            <!-- Movement Report (only when a specific student is selected) -->
            <?php if ($selectedStudent && count($movementData) > 0): ?>
                <h5 style="color:var(--primary-color);margin-bottom:1rem;"><i class="fas fa-route me-2"></i>Movement History — <?php echo htmlspecialchars($students[array_search($selectedStudent, array_column($students, 'id'))]['full_name'] ?? 'Student'); ?></h5>
                <div class="report-card">
                    <p style="color:#6c757d;font-size:0.9rem;">GPS location trail from <?php echo date('M j, Y', strtotime($startDate)); ?> to <?php echo date('M j, Y', strtotime($endDate)); ?>. Green dots = inside geofence, red dots = outside.</p>
                    <div id="movementMap"></div>

                    <div class="table-responsive mt-3">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>Latitude</th>
                                    <th>Longitude</th>
                                    <th>In Geofence</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice(array_reverse($movementData), 0, 50) as $log): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y g:i A', strtotime($log['captured_at'])); ?></td>
                                        <td style="font-family:monospace;font-size:0.85rem;"><?php echo $log['latitude']; ?></td>
                                        <td style="font-family:monospace;font-size:0.85rem;"><?php echo $log['longitude']; ?></td>
                                        <td>
                                            <?php if ($log['in_geofence']): ?>
                                                <span class="status-badge badge-present">Inside</span>
                                            <?php else: ?>
                                                <span class="status-badge badge-outside">Outside</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php elseif ($selectedStudent && count($movementData) === 0): ?>
                <div class="report-card text-center" style="color:#6c757d;padding:2rem;">
                    <i class="fas fa-map-marker-alt" style="font-size:2.5rem;opacity:0.3;"></i>
                    <p style="margin-top:0.5rem;">No GPS location data for this student in the selected date range.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="assets/supervisor-scripts.js"></script>
    <?php if ($selectedStudent && count($movementData) > 0): ?>
    <script>
        const movementData = <?php echo json_encode($movementData); ?>;
        const movement = movementData.map(m => ({
            lat: parseFloat(m.latitude),
            lng: parseFloat(m.longitude),
            in_geofence: m.in_geofence == 1 || m.in_geofence === true,
            captured_at: m.captured_at
        }));

        // Calculate center
        const centerLat = movement.reduce((s, m) => s + m.lat, 0) / movement.length;
        const centerLng = movement.reduce((s, m) => s + m.lng, 0) / movement.length;

        const mmap = L.map('movementMap').setView([centerLat, centerLng], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap', maxZoom: 19
        }).addTo(mmap);

        // Draw the trail
        const trailCoords = movement.map(m => [m.lat, m.lng]);
        L.polyline(trailCoords, { color: '#1a4d2e', weight: 3, opacity: 0.6 }).addTo(mmap);

        // Draw markers
        movement.forEach(function(m, i) {
            const color = m.in_geofence ? '#28a745' : '#dc3545';
            const icon = L.divIcon({
                className: 'trail-marker',
                html: `<div style="background:${color};width:12px;height:12px;border-radius:50%;border:2px solid white;box-shadow:0 0 4px rgba(0,0,0,0.3);"></div>`,
                iconSize: [12, 12], iconAnchor: [6, 6]
            });
            L.marker([m.lat, m.lng], { icon: icon }).addTo(mmap)
                .bindPopup(`<strong>Point ${i+1}</strong><br>${new Date(m.captured_at).toLocaleString()}<br>Status: ${m.in_geofence ? 'Inside geofence' : 'OUTSIDE geofence'}`);
        });

        // Fit map to show all markers
        const group = L.featureGroup(mmap._layers ? Object.values(mmap._layers).filter(l => l.getLatLng) : []);
        if (group.getLayers().length > 0) mmap.fitBounds(group.getBounds(), { padding: [30, 30] });
    </script>
    <?php endif; ?>
</body>
</html>
