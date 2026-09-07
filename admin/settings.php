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
    <title>Settings - SIWES Logbook</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/admin-styles.css">
    
    <!-- Additional settings styles -->
    <style>
        .settings-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 4px solid var(--primary-color);
            margin-bottom: 1.5rem;
        }
        
        .settings-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .settings-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .settings-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            background: var(--primary-color);
        }
        
        .settings-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }
        
        .settings-description {
            color: #6c757d;
            font-size: 0.875rem;
            margin: 0;
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
        
        .btn-danger {
            background: var(--danger-color);
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: var(--primary-color);
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .settings-grid {
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
        <?php 
        $pageTitle = 'Settings';
        include 'includes/header.php'; 
        ?>
        
        <!-- Page Content -->
        <div class="page-content">
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h2 class="mb-1">System Settings</h2>
                    <p class="text-muted mb-0">Configure system preferences and security settings</p>
                </div>
            </div>
            
            <!-- Settings Grid -->
            <div class="settings-grid">
                <!-- General Settings -->
                <div class="settings-card fade-in-up">
                    <div class="settings-header">
                        <div class="settings-icon">
                            <i class="fas fa-cog"></i>
                        </div>
                        <div>
                            <h3 class="settings-title">General Settings</h3>
                            <p class="settings-description">Configure basic system preferences</p>
                        </div>
                    </div>
                    
                    <form id="generalSettingsForm">
                        <div class="mb-3">
                            <label for="siteName" class="form-label">Site Name</label>
                            <input type="text" class="form-control" id="siteName" value="SIWES Logbook System">
                        </div>
                        
                        <div class="mb-3">
                            <label for="adminEmail" class="form-label">Admin Email</label>
                            <input type="email" class="form-control" id="adminEmail" value="admin@siwes.com">
                        </div>
                        
                        <div class="mb-3">
                            <label for="timezone" class="form-label">Timezone</label>
                            <select class="form-control" id="timezone">
                                <option value="Africa/Lagos" selected>Africa/Lagos (GMT+1)</option>
                                <option value="UTC">UTC</option>
                                <option value="America/New_York">America/New_York</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Save Changes
                        </button>
                    </form>
                </div>
                
                <!-- Security Settings -->
                <div class="settings-card fade-in-up">
                    <div class="settings-header">
                        <div class="settings-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div>
                            <h3 class="settings-title">Security Settings</h3>
                            <p class="settings-description">Configure security preferences</p>
                        </div>
                    </div>
                    
                    <form id="securitySettingsForm">
                        <div class="mb-3">
                            <label class="form-label d-flex justify-content-between">
                                <span>Two-Factor Authentication</span>
                                <label class="switch">
                                    <input type="checkbox" id="twoFactorAuth">
                                    <span class="slider"></span>
                                </label>
                            </label>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label d-flex justify-content-between">
                                <span>Session Timeout (minutes)</span>
                                <input type="number" class="form-control" id="sessionTimeout" value="30" style="width: 80px;">
                            </label>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label d-flex justify-content-between">
                                <span>Password Policy</span>
                                <select class="form-control" id="passwordPolicy" style="width: 150px;">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Save Changes
                        </button>
                    </form>
                </div>
                
                <!-- Notification Settings -->
                <div class="settings-card fade-in-up">
                    <div class="settings-header">
                        <div class="settings-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <div>
                            <h3 class="settings-title">Notification Settings</h3>
                            <p class="settings-description">Configure notification preferences</p>
                        </div>
                    </div>
                    
                    <form id="notificationSettingsForm">
                        <div class="mb-3">
                            <label class="form-label d-flex justify-content-between">
                                <span>Email Notifications</span>
                                <label class="switch">
                                    <input type="checkbox" id="emailNotifications" checked>
                                    <span class="slider"></span>
                                </label>
                            </label>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label d-flex justify-content-between">
                                <span>SMS Notifications</span>
                                <label class="switch">
                                    <input type="checkbox" id="smsNotifications">
                                    <span class="slider"></span>
                                </label>
                            </label>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label d-flex justify-content-between">
                                <span>Push Notifications</span>
                                <label class="switch">
                                    <input type="checkbox" id="pushNotifications" checked>
                                    <span class="slider"></span>
                                </label>
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Save Changes
                        </button>
                    </form>
                </div>
                
                <!-- System Maintenance -->
                <div class="settings-card fade-in-up">
                    <div class="settings-header">
                        <div class="settings-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <div>
                            <h3 class="settings-title">System Maintenance</h3>
                            <p class="settings-description">System maintenance and backup options</p>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <button class="btn btn-primary me-2" onclick="backupDatabase()">
                            <i class="fas fa-download"></i>
                            Backup Database
                        </button>
                        <button class="btn btn-warning me-2" onclick="clearCache()">
                            <i class="fas fa-broom"></i>
                            Clear Cache
                        </button>
                    </div>
                    
                    <div class="mb-3">
                        <button class="btn btn-info me-2" onclick="systemHealth()">
                            <i class="fas fa-heartbeat"></i>
                            System Health
                        </button>
                        <button class="btn btn-secondary" onclick="viewLogs()">
                            <i class="fas fa-file-alt"></i>
                            View Logs
                        </button>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <h6 class="text-danger">Danger Zone</h6>
                        <button class="btn btn-danger" onclick="resetSystem()">
                            <i class="fas fa-exclamation-triangle"></i>
                            Reset System
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/admin-scripts.js"></script>
    <script>
        // Settings functions
        function backupDatabase() {
            showToast('Database backup will be implemented in the next update', 'info');
        }
        
        function clearCache() {
            showToast('Cache cleared successfully', 'success');
        }
        
        function systemHealth() {
            showToast('System health check will be implemented in the next update', 'info');
        }
        
        function viewLogs() {
            showToast('System logs will be implemented in the next update', 'info');
        }
        
        function resetSystem() {
            if (confirm('Are you sure you want to reset the system? This action cannot be undone.')) {
                showToast('System reset will be implemented in the next update', 'warning');
            }
        }
        
        // Form submissions
        document.getElementById('generalSettingsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            showToast('General settings saved successfully', 'success');
        });
        
        document.getElementById('securitySettingsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            showToast('Security settings saved successfully', 'success');
        });
        
        document.getElementById('notificationSettingsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            showToast('Notification settings saved successfully', 'success');
        });
    </script>
</body>
</html> 