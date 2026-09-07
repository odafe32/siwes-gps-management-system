<?php
session_start();
require_once '../backend/config/db.php';
require_once '../backend/config/session.php';

if (!isLoggedIn() || (!hasRole('admin') && !hasRole('coordinator'))) {
    header('Location: login.php?error=Access denied');
    exit();
}

// Date range filter
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$selectedOrg = $_GET['org_id'] ?? '';

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="system_attendance_report_' . $startDate . '_to_' . $endDate . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Student Name', 'Matric Number', 'Organization', 'Supervisor', 'Date', 'Check In', 'Check Out', 'Status']);

    if ($selectedOrg) {
        $stmt = $pdo->prepare("
            SELECT u.full_name, u.matric_number, o.org_name, sup.full_name as supervisor_name, a.date, a.check_in_time, a.check_out_time, a.status
            FROM attendance a
            JOIN users u ON u.id = a.intern_id
            LEFT JOIN organizations o ON o.id = u.organization_id
            LEFT JOIN users sup ON sup.id = u.supervisor_id
            WHERE a.date BETWEEN ? AND ? AND u.organization_id = ?
            ORDER BY o.org_name, u.full_name, a.date DESC
        ");
        $stmt->execute([$startDate, $endDate, $selectedOrg]);
    } else {
        $stmt = $pdo->prepare("
            SELECT u.full_name, u.matric_number, o.org_name, sup.full_name as supervisor_name, a.date, a.check_in_time, a.check_out_time, a.status
            FROM attendance a
            JOIN users u ON u.id = a.intern_id
            LEFT JOIN organizations o ON o.id = u.organization_id
            LEFT JOIN users sup ON sup.id = u.supervisor_id
            WHERE a.date BETWEEN ? AND ?
            ORDER BY o.org_name, u.full_name, a.date DESC
        ");
        $stmt->execute([$startDate, $endDate]);
    }
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}

// Get all organizations for filter
$orgs = $pdo->query("SELECT id, org_name FROM organizations ORDER BY org_name")->fetchAll(PDO::FETCH_ASSOC);

// Get attendance data
if ($selectedOrg) {
    $stmt = $pdo->prepare("
        SELECT u.full_name, u.matric_number, o.org_name, sup.full_name as supervisor_name, a.date, a.check_in_time, a.check_out_time, a.status
        FROM attendance a
        JOIN users u ON u.id = a.intern_id
        LEFT JOIN organizations o ON o.id = u.organization_id
        LEFT JOIN users sup ON sup.id = u.supervisor_id
        WHERE a.date BETWEEN ? AND ? AND u.organization_id = ?
        ORDER BY o.org_name, u.full_name, a.date DESC
    ");
    $stmt->execute([$startDate, $endDate, $selectedOrg]);
} else {
    $stmt = $pdo->prepare("
        SELECT u.full_name, u.matric_number, o.org_name, sup.full_name as supervisor_name, a.date, a.check_in_time, a.check_out_time, a.status
        FROM attendance a
        JOIN users u ON u.id = a.intern_id
        LEFT JOIN organizations o ON o.id = u.organization_id
        LEFT JOIN users sup ON sup.id = u.supervisor_id
        WHERE a.date BETWEEN ? AND ?
        ORDER BY o.org_name, u.full_name, a.date DESC
    ");
    $stmt->execute([$startDate, $endDate]);
}
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Summary stats
if ($selectedOrg) {
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present,
            SUM(CASE WHEN a.status = 'Outside Zone' THEN 1 ELSE 0 END) as outside,
            SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) as absent
        FROM attendance a
        JOIN users u ON u.id = a.intern_id
        WHERE a.date BETWEEN ? AND ? AND u.organization_id = ?
    ");
    $stmt->execute([$startDate, $endDate, $selectedOrg]);
} else {
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present,
            SUM(CASE WHEN a.status = 'Outside Zone' THEN 1 ELSE 0 END) as outside,
            SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) as absent
        FROM attendance a
        WHERE a.date BETWEEN ? AND ?
    ");
    $stmt->execute([$startDate, $endDate]);
}
$summary = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - SIWES Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/admin-styles.css">
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
        .btn-export { background: #28a745; color: white; border: none; padding: 0.5rem 1.25rem; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; }
        .btn-export:hover { color: white; background: #218838; text-decoration: none; }
        .form-control { border-radius: 8px; border: 2px solid #e9ecef; }
        .form-control:focus { border-color: var(--primary-color); }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php $pageTitle = 'Reports'; include 'includes/header.php'; ?>

        <div class="page-content">
            <h2 style="color:var(--primary-color);font-weight:700;margin-bottom:0.5rem;">System Attendance Report</h2>
            <p style="color:#6c757d;margin-bottom:1.5rem;">View and export attendance records across all organizations</p>

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
                        <label class="form-label fw-bold">Organization (optional)</label>
                        <select class="form-control" name="org_id">
                            <option value="">All Organizations</option>
                            <?php foreach ($orgs as $o): ?>
                                <option value="<?php echo $o['id']; ?>" <?php echo $selectedOrg == $o['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($o['org_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill"><i class="fas fa-search me-1"></i>Generate</button>
                        <a href="reports.php?export=csv&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>&org_id=<?php echo $selectedOrg; ?>" class="btn-export">
                            <i class="fas fa-download me-1"></i>CSV
                        </a>
                    </div>
                </form>
            </div>

            <!-- Summary -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3"><div class="stat-card stat-total"><div class="num"><?php echo $summary['total'] ?: 0; ?></div><div class="lbl">Total Records</div></div></div>
                <div class="col-6 col-md-3"><div class="stat-card stat-present"><div class="num"><?php echo $summary['present'] ?: 0; ?></div><div class="lbl">Present</div></div></div>
                <div class="col-6 col-md-3"><div class="stat-card stat-outside"><div class="num"><?php echo $summary['outside'] ?: 0; ?></div><div class="lbl">Outside Zone</div></div></div>
                <div class="col-6 col-md-3"><div class="stat-card stat-absent"><div class="num"><?php echo $summary['absent'] ?: 0; ?></div><div class="lbl">Absent</div></div></div>
            </div>

            <!-- Table -->
            <h5 style="color:var(--primary-color);margin-bottom:1rem;">
                <i class="fas fa-table me-2"></i>Attendance Records
                (<?php echo date('M j, Y', strtotime($startDate)); ?> — <?php echo date('M j, Y', strtotime($endDate)); ?>)
            </h5>

            <?php if (count($records) > 0): ?>
                <div class="table-responsive report-table">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Matric</th>
                                <th>Organization</th>
                                <th>Supervisor</th>
                                <th>Date</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $r): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($r['full_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($r['matric_number'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars($r['org_name'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars($r['supervisor_name'] ?? '—'); ?></td>
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/admin-scripts.js"></script>
</body>
</html>
