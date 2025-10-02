<?php
// Debug script to check database content
echo "🔍 Debugging Database IP Search Issue\n";
echo "=====================================\n\n";

try {
    $db = new PDO('sqlite:subnets.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if database exists and has data
    $stmt = $db->query('SELECT COUNT(*) as count FROM subnet_configurations');
    $count = $stmt->fetch()['count'];
    echo "📊 Total configurations in database: $count\n\n";
    
    if ($count == 0) {
        echo "⚠️ No configurations found in database!\n";
        echo "The current table you see is likely not saved to the database yet.\n";
        echo "Try saving it first using 'Save to Database' option.\n\n";
    } else {
        echo "✅ Found configurations. Let's examine them:\n\n";
        
        $stmt = $db->query('SELECT id, site_name, network_address, division_data, created_at FROM subnet_configurations ORDER BY created_at DESC LIMIT 3');
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "📋 Configuration ID: {$row['id']}\n";
            echo "   Site: {$row['site_name']}\n";
            echo "   Network: {$row['network_address']}\n";
            echo "   Created: {$row['created_at']}\n";
            echo "   Division Data: " . substr($row['division_data'], 0, 200) . "...\n";
            
            // Try to parse division data
            $divisionData = json_decode($row['division_data'], true);
            if ($divisionData) {
                echo "   ✅ Division data is valid JSON\n";
            } else {
                echo "   ❌ Division data is invalid JSON\n";
            }
            
            echo "   " . str_repeat("-", 50) . "\n\n";
        }
    }
    
    // Test the search API directly
    echo "🧪 Testing Search API for IP: 192.168.3.125\n";
    echo "===========================================\n";
    
    // Simulate the API call
    $_GET['action'] = 'searchIP';
    $_GET['ip'] = '192.168.3.125';
    
    ob_start();
    include 'api.php';
    $apiResponse = ob_get_clean();
    
    echo "API Response: $apiResponse\n\n";
    
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
}
?>