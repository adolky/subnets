<?php
/**
 * SQLite to MySQL Migration Script
 * 
 * This script migrates data from the old SQLite database to the new MySQL database.
 * 
 * Usage:
 *   php migrate_sqlite_to_mysql.php
 * 
 * Requirements:
 *   - Old subnets.db file must exist in the same directory
 *   - MySQL database must be accessible with credentials in environment variables
 *   - Both PDO SQLite and PDO MySQL extensions must be enabled
 */

// Configuration
$sqliteDbPath = 'subnets.db';

// Get MySQL configuration from environment variables
$mysqlHost = getenv('DB_HOST') ?: 'localhost';
$mysqlDb = getenv('DB_NAME') ?: 'subnets';
$mysqlUser = getenv('DB_USER') ?: 'root';
$mysqlPass = getenv('DB_PASSWORD') ?: '';
$mysqlPort = getenv('DB_PORT') ?: 3306;

echo "=== SQLite to MySQL Migration Tool ===\n\n";

// Check if SQLite database exists
if (!file_exists($sqliteDbPath)) {
    die("Error: SQLite database file '$sqliteDbPath' not found.\n");
}

try {
    // Connect to SQLite
    echo "Connecting to SQLite database...\n";
    $sqliteDb = new PDO("sqlite:$sqliteDbPath");
    $sqliteDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ SQLite connection established\n\n";
    
    // Connect to MySQL
    echo "Connecting to MySQL database...\n";
    echo "Host: $mysqlHost:$mysqlPort\n";
    echo "Database: $mysqlDb\n";
    echo "User: $mysqlUser\n\n";
    
    $mysqlDsn = "mysql:host=$mysqlHost;port=$mysqlPort;dbname=$mysqlDb;charset=utf8mb4";
    $mysqlDb = new PDO($mysqlDsn, $mysqlUser, $mysqlPass);
    $mysqlDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ MySQL connection established\n\n";
    
    // Get count of records to migrate
    $stmt = $sqliteDb->query("SELECT COUNT(*) FROM subnet_configurations");
    $totalRecords = $stmt->fetchColumn();
    
    if ($totalRecords == 0) {
        echo "No records found in SQLite database. Nothing to migrate.\n";
        exit(0);
    }
    
    echo "Found $totalRecords record(s) to migrate.\n\n";
    
    // Fetch all records from SQLite
    echo "Reading data from SQLite...\n";
    $stmt = $sqliteDb->query("SELECT * FROM subnet_configurations ORDER BY id");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✓ Data read successfully\n\n";
    
    // Prepare insert statement for MySQL
    $insertSql = "INSERT INTO subnet_configurations 
                  (site_name, admin_number, network_address, mask_bits, division_data, vlan_ids, vlan_names, created_at, updated_at) 
                  VALUES 
                  (:site_name, :admin_number, :network_address, :mask_bits, :division_data, :vlan_ids, :vlan_names, :created_at, :updated_at)
                  ON DUPLICATE KEY UPDATE
                  division_data = VALUES(division_data),
                  vlan_ids = VALUES(vlan_ids),
                  vlan_names = VALUES(vlan_names),
                  updated_at = VALUES(updated_at)";
    
    $insertStmt = $mysqlDb->prepare($insertSql);
    
    // Migrate records
    echo "Migrating records to MySQL...\n";
    $migratedCount = 0;
    $skippedCount = 0;
    $errorCount = 0;
    
    foreach ($records as $record) {
        try {
            $insertStmt->execute([
                ':site_name' => $record['site_name'],
                ':admin_number' => $record['admin_number'],
                ':network_address' => $record['network_address'],
                ':mask_bits' => $record['mask_bits'],
                ':division_data' => $record['division_data'],
                ':vlan_ids' => $record['vlan_ids'] ?? '',
                ':vlan_names' => $record['vlan_names'] ?? '',
                ':created_at' => $record['created_at'],
                ':updated_at' => $record['updated_at']
            ]);
            
            $migratedCount++;
            echo "  ✓ Migrated: {$record['site_name']} - {$record['network_address']}\n";
            
        } catch (PDOException $e) {
            $errorCount++;
            echo "  ✗ Error migrating record ID {$record['id']}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== Migration Complete ===\n";
    echo "Total records: $totalRecords\n";
    echo "Successfully migrated: $migratedCount\n";
    echo "Errors: $errorCount\n\n";
    
    if ($migratedCount == $totalRecords) {
        echo "✓ All records migrated successfully!\n";
        echo "\nYou can now safely:\n";
        echo "1. Backup your SQLite database: mv subnets.db subnets.db.backup\n";
        echo "2. Update your application to use MySQL\n";
        echo "3. Remove the old SQLite database file when you're sure everything works\n";
    } else {
        echo "⚠ Some records failed to migrate. Please review the errors above.\n";
    }
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}

echo "\nMigration script completed.\n";
?>
