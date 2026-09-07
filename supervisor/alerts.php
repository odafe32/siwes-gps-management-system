<?php
session_start();
require_once '../backend/config/db.php';
require_once '../backend/config/session.php';

if (!isLoggedIn() || !hasRole('supervisor')) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'] ?? 'Supervisor';

// Handle mark as read
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([(int)$_GET['mark_read'], $user_id]);
    header('Location: alerts.php?msg=Alert+marked+as+read');
    exit();
}

// Handle mark all as read
if (isset($_GET['mark_all_read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND type = 'geofence_breach'");
    $stmt->execute([$user_id]);
    header('Location: alerts.php?msg=All+alerts+marked+as+read');
    exit();
}

$msg = $_GET['msg'] ?? '';

// Get all breach alerts for this supervisor
$stmt = $pdo->prepare("
    SELECT * FROM notifications
    WHERE user_id = ? AND type = 'geofence_breach'
    ORDER BY created_at DESC
");
$stmt->execute([$user_id]);
$allAlerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
$unreadCount = count(array_filter($allAlerts, function($a) { return !$a['is_read']; }));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geofence Alerts - SIWES Supervisor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/supervisor-styles.css">
    <style>
        .alert-card {
            background: white; border-radius: 12px; margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06); overflow: hidden;
            border-left: 4px solid #dc3545;
        }
        .alert-card.read { border-left-color: #adb5bd; opacity: 0.7; }
        .alert-card-body { padding: 1.25rem; display: flex; justify-content: space-between; align-items: start; }
        .alert-title { font-weight: 700; color: #dc3545; font-size: 1rem; margin-bottom: 0.25rem; }
        .alert-card.read .alert-title { color: #6c757d; }
        .alert-message { color: #495057; font-size: 0.9rem; margin-bottom: 0.5rem; }
        .alert-time { color: #adb5bd; font-size: 0.8rem; }
        .alert-badge-new {
            background: #dc3545; color: white; font-size: 0.7rem; padding: 0.15rem 0.5rem;
            border-radius: 4px; margin-left: 0.5rem;
        }
        .summary-card {
            background: white; border-radius: 12px; padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06); margin-bottom: 1.5rem;
        }
        .summary-stat { text-align: center; }
        .summary-stat .num { font-size: 2rem; font-weight: 700; }
        .summary-stat .lbl { font-size: 0.8rem; color: #6c757d; text-transform: uppercase; }
        .num-unread { color: #dc3545; }
        .num-read { color: #28a745; }
        .num-total { color: var(--primary-color); }
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
                <h1 class="page-title">Geofence Alerts</h1>
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
            <?php if ($msg): ?>
                <div class="alert alert-success" style="background:rgba(40,167,69,0.1);color:#28a745;border-left:4px solid #28a745;border-radius:8px;padding:1rem;margin-bottom:1.5rem;">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($msg); ?>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 style="color:var(--primary-color);font-weight:700;margin:0;">Geofence Breach Alerts</h2>
                    <p style="color:#6c757d;margin:0.5rem 0 0 0;">Notifications when students check in outside their geofence</p>
                </div>
                <?php if ($unreadCount > 0): ?>
                    <a href="alerts.php?mark_all_read=1" class="btn btn-outline-secondary">
                        <i class="fas fa-check-double me-1"></i>Mark All as Read
                    </a>
                <?php endif; ?>
            </div>

            <!-- Summary -->
            <div class="row g-3 mb-4">
                <div class="col-4">
                    <div class="summary-card">
                        <div class="summary-stat">
                            <div class="num num-unread"><?php echo $unreadCount; ?></div>
                            <div class="lbl">Unread Alerts</div>
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="summary-card">
                        <div class="summary-stat">
                            <div class="num num-read"><?php echo count($allAlerts) - $unreadCount; ?></div>
                            <div class="lbl">Reviewed</div>
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="summary-card">
                        <div class="summary-stat">
                            <div class="num num-total"><?php echo count($allAlerts); ?></div>
                            <div class="lbl">Total Alerts</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alert List -->
            <?php if (count($allAlerts) > 0): ?>
                <?php foreach ($allAlerts as $alert): ?>
                    <div class="alert-card <?php echo $alert['is_read'] ? 'read' : ''; ?>">
                        <div class="alert-card-body">
                            <div style="flex:1;">
                                <div class="alert-title">
                                    <i class="fas fa-map-marker-times me-1"></i>
                                    <?php echo htmlspecialchars($alert['title']); ?>
                                    <?php if (!$alert['is_read']): ?>
                                        <span class="alert-badge-new">NEW</span>
                                    <?php endif; ?>
                                </div>
                                <div class="alert-message"><?php echo htmlspecialchars($alert['message']); ?></div>
                                <div class="alert-time">
                                    <i class="fas fa-clock me-1"></i><?php echo date('l, M j, Y \a\t g:i A', strtotime($alert['created_at'])); ?>
                                </div>
                            </div>
                            <?php if (!$alert['is_read']): ?>
                                <a href="alerts.php?mark_read=<?php echo $alert['id']; ?>" class="btn btn-sm btn-outline-secondary" style="white-space:nowrap;">
                                    <i class="fas fa-check me-1"></i>Mark as read
                                </a>
                            <?php else: ?>
                                <span class="badge bg-light text-secondary"><i class="fas fa-check me-1"></i>Reviewed</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="summary-card" style="text-align:center;padding:3rem;">
                    <i class="fas fa-bell-slash" style="font-size:3rem;color:#adb5bd;"></i>
                    <h4 style="color:#6c757d;margin-top:1rem;">No Geofence Alerts</h4>
                    <p style="color:#adb5bd;">You will be notified here when a student checks in outside their geofence boundary.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/supervisor-scripts.js"></script>
</body>
</html>
