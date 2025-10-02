<?php
// Simple test for the API
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing API...\n";

try {
    require_once 'db_init.php';
    echo "Database class loaded successfully.\n";
    
    $database = new SubnetDatabase();
    echo "Database initialized successfully.\n";
    
    $db = $database->getConnection();
    echo "Database connection obtained.\n";
    
    // Test a simple query
    $stmt = $db->query("SELECT COUNT(*) FROM subnet_configurations");
    $count = $stmt->fetchColumn();
    echo "Current configurations count: $count\n";
    
    echo "API test completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>