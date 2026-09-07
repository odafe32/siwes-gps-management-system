<?php
session_start();
require_once '../backend/config/db.php';
require_once '../backend/config/session.php';
require_once '../backend/models/Organization.php';

if (!isLoggedIn() || (!hasRole('admin') && !hasRole('coordinator'))) {
    header('Location: login.php?error=Access denied');
    exit();
}

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    Organization::delete($pdo, (int)$_GET['delete']);
    header('Location: organizations.php?msg=Organization+deleted');
    exit();
}

$organizations = Organization::all($pdo);
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizations - SIWES Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/admin-styles.css">
    <style>
        .org-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 4px solid var(--primary-color);
            height: 100%;
        }
        .org-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        .org-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        .org-address {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        .org-geofence {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 1rem;
            font-size: 0.85rem;
        }
        .org-geofence .label { color: #6c757d; }
        .org-geofence .value { font-weight: 600; color: #2c3e50; }
        .org-stats { display: flex; gap: 1.5rem; margin-bottom: 1rem; }
        .org-stat { text-align: center; }
        .org-stat .num { font-size: 1.5rem; font-weight: 700; color: var(--primary-color); }
        .org-stat .lbl { font-size: 0.75rem; color: #6c757d; text-transform: uppercase; }
        .org-actions { display: flex; gap: 0.5rem; }
        .org-actions a {
            flex: 1;
            text-align: center;
            padding: 0.5rem;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .btn-edit { background: rgba(26,77,46,0.1); color: var(--primary-color); }
        .btn-edit:hover { background: var(--primary-color); color: white; }
        .btn-delete { background: rgba(220,53,69,0.1); color: #dc3545; }
        .btn-delete:hover { background: #dc3545; color: white; }
        .add-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        .add-btn:hover { color: white; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(26,77,46,0.3); }
        .empty-state { text-align: center; padding: 3rem; color: #6c757d; }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.3; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php $pageTitle = 'Organizations'; include 'includes/header.php'; ?>

        <div class="page-content">
            <?php if ($msg): ?>
                <div class="alert alert-success" style="background:rgba(40,167,69,0.1);color:#28a745;border-left:4px solid #28a745;border-radius:8px;padding:1rem;margin-bottom:1.5rem;">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($msg); ?>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 style="color:var(--primary-color);font-weight:700;margin:0;">Host Organizations</h2>
                    <p style="color:#6c757d;margin:0.5rem 0 0 0;">Manage organizations and their geofence boundaries</p>
                </div>
                <a href="organization-add.php" class="add-btn">
                    <i class="fas fa-plus"></i> Add Organization
                </a>
            </div>

            <?php if (empty($organizations)): ?>
                <div class="empty-state">
                    <i class="fas fa-building"></i>
                    <h4>No organizations yet</h4>
                    <p>Add your first host organization to start setting up geofences.</p>
                    <a href="organization-add.php" class="add-btn mt-3">
                        <i class="fas fa-plus"></i> Add First Organization
                    </a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($organizations as $org): ?>
                        <?php
                        $students = Organization::getStudents($pdo, $org['id']);
                        $supervisors = Organization::getSupervisors($pdo, $org['id']);
                        ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="org-card">
                                <div class="org-name"><?php echo htmlspecialchars($org['org_name']); ?></div>
                                <div class="org-address">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?php echo htmlspecialchars($org['address'] ?: 'No address set'); ?>
                                </div>

                                <div class="org-geofence">
                                    <div class="row">
                                        <div class="col-6">
                                            <span class="label">Latitude:</span>
                                            <span class="value"><?php echo htmlspecialchars($org['geo_latitude']); ?></span>
                                        </div>
                                        <div class="col-6">
                                            <span class="label">Longitude:</span>
                                            <span class="value"><?php echo htmlspecialchars($org['geo_longitude']); ?></span>
                                        </div>
                                    </div>
                                    <div class="mt-1">
                                        <span class="label">Geofence Radius:</span>
                                        <span class="value"><?php echo htmlspecialchars($org['geofence_radius_m']); ?>m</span>
                                    </div>
                                </div>

                                <div class="org-stats">
                                    <div class="org-stat">
                                        <div class="num"><?php echo count($students); ?></div>
                                        <div class="lbl">Students</div>
                                    </div>
                                    <div class="org-stat">
                                        <div class="num"><?php echo count($supervisors); ?></div>
                                        <div class="lbl">Supervisors</div>
                                    </div>
                                </div>

                                <div class="org-actions">
                                    <a href="organization-edit.php?id=<?php echo $org['id']; ?>" class="btn-edit">
                                        <i class="fas fa-edit me-1"></i>Edit
                                    </a>
                                    <a href="#" class="btn-delete" data-bs-toggle="modal"
                                       data-bs-target="#deleteModal"
                                       data-org-id="<?php echo $org['id']; ?>"
                                       data-org-name="<?php echo htmlspecialchars($org['org_name']); ?>"
                                       data-org-students="<?php echo count($students); ?>">
                                        <i class="fas fa-trash me-1"></i>Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:12px;border:none;overflow:hidden;">
                <div class="modal-header" style="background:#dc3545;color:white;border:none;padding:1.25rem 1.5rem;">
                    <h5 class="modal-title" id="deleteModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Delete Organization
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding:1.5rem;">
                    <p style="font-size:1.1rem;margin-bottom:1rem;">
                        Are you sure you want to delete <strong id="deleteOrgName">this organization</strong>?
                    </p>
                    <div id="deleteWarning" style="background:#fff3cd;border-radius:8px;padding:0.75rem 1rem;font-size:0.9rem;color:#856404;margin-bottom:0;">
                        <i class="fas fa-info-circle me-1"></i>
                        <span id="deleteStudentCount">0</span> student(s) will be unlinked from this organization.
                    </div>
                </div>
                <div class="modal-footer" style="border:none;padding:0 1.5rem 1.5rem;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:8px;padding:0.5rem 1.5rem;">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <a href="#" id="deleteConfirmBtn" class="btn" style="background:#dc3545;color:white;border-radius:8px;padding:0.5rem 1.5rem;font-weight:600;">
                        <i class="fas fa-trash me-1"></i>Yes, Delete
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/admin-scripts.js"></script>
    <script>
        // Wire up the delete modal — populate org name and set the confirm URL
        const deleteModal = document.getElementById('deleteModal');
        deleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const orgId = button.getAttribute('data-org-id');
            const orgName = button.getAttribute('data-org-name');
            const studentCount = button.getAttribute('data-org-students');

            document.getElementById('deleteOrgName').textContent = orgName;
            document.getElementById('deleteStudentCount').textContent = studentCount;
            document.getElementById('deleteConfirmBtn').href = 'organizations.php?delete=' + orgId;
        });
    </script>
</body>
</html>
