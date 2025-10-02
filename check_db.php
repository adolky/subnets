<?php
// Check both database files
$databases = ['subnet_configurations.db', 'subnets.db'];

foreach ($databases as $dbFile) {
    echo "=== Checking $dbFile ===" . PHP_EOL;
    try {
        $pdo = new PDO("sqlite:$dbFile");
        
        // Get all tables
        $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
        echo "Tables: ";
        while ($table = $tables->fetch(PDO::FETCH_ASSOC)) {
            echo $table['name'] . " ";
        }
        echo PHP_EOL;
        
        // Try to query subnet_configurations table
        $result = $pdo->query('SELECT COUNT(*) as count FROM subnet_configurations');
        $count = $result->fetchColumn();
        echo "Total configurations: $count" . PHP_EOL;
        
        if ($count > 0) {
            echo "Sample configurations:" . PHP_EOL;
            $stmt = $pdo->query('SELECT id, site_name, admin_number, network_address FROM subnet_configurations LIMIT 3');
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "ID: {$row['id']}, Site: {$row['site_name']}, Admin: {$row['admin_number']}, Network: {$row['network_address']}" . PHP_EOL;
            }
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . PHP_EOL;
    }
    echo PHP_EOL;
}
?>