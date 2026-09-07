<?php
require_once 'backend/config/db.php';

try {
    $stmt = $pdo->query('DESCRIBE users');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Users table structure:\n";
    foreach ($columns as $column) {
        echo $column['Field'] . ' - ' . $column['Type'] . "\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>

