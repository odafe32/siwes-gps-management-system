<?php
require_once 'backend/config/db.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully!\n";
    
    // Get all table names
    $tables = [];
    $result = $pdo->query("SHOW TABLES");
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    
    echo "Found tables: " . implode(', ', $tables) . "\n";
    
    // Drop all tables
    foreach ($tables as $table) {
        try {
            $pdo->exec("DROP TABLE IF EXISTS `$table`");
            echo "Dropped table: $table\n";
        } catch (Exception $e) {
            echo "Error dropping table $table: " . $e->getMessage() . "\n";
        }
    }
    
    echo "All tables dropped successfully!\n";
    
} catch(PDOException $e) {
    echo "Database reset failed: " . $e->getMessage() . "\n";
}
?> 