<?php
$cookieFile = sys_get_temp_dir() . '/siwes_access.txt';
@unlink($cookieFile);

// Login as admin
$ch = curl_init('http://localhost:8080/backend/api/auth.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['action'=>'login','role'=>'admin','email'=>'admin@test.com','password'=>'12345678']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$resp = curl_exec($ch);
curl_close($ch);
echo "Login: $resp\n\n";

// Test each admin page
$pages = ['dashboard.php', 'studentmanagement.php', 'supervisormanagement.php', 'logbook-management.php', 'usermanagement.php', 'manage.php', 'notifications.php', 'reports.php', 'gps-monitoring.php', 'communications.php', 'settings.php', 'backup-restore.php'];

foreach ($pages as $page) {
    $ch = curl_init("http://localhost:8080/admin/$page");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $status = $httpCode == 200 ? "OK" : ">>> BLOCKED ($httpCode)";
    echo str_pad($page, 35) . " $status\n";
}

@unlink($cookieFile);
