<?php
/**
 * Comprehensive Test Suite for Subnet Calculator
 * Tests all database and API functionality
 */

echo "🧪 Starting Comprehensive Subnet Calculator Test Suite\n";
echo "======================================================\n\n";

// Test configuration
$baseUrl = 'http://localhost:8080';
$testResults = [];

function testAPI($endpoint, $method = 'GET', $data = null) {
    global $baseUrl;
    
    $url = $baseUrl . '/api.php' . $endpoint;
    
    if ($method === 'GET') {
        $response = @file_get_contents($url);
    } else {
        $options = [
            'http' => [
                'method' => $method,
                'header' => 'Content-Type: application/json',
                'content' => $data ? json_encode($data) : ''
            ]
        ];
        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);
    }
    
    if ($response === false) {
        return ['success' => false, 'error' => 'Request failed'];
    }
    
    $decoded = json_decode($response, true);
    if ($decoded === null) {
        return ['success' => false, 'error' => 'Invalid JSON response', 'raw' => substr($response, 0, 200)];
    }
    
    return $decoded;
}

function runTest($testName, $testFunction) {
    global $testResults;
    echo "🔍 Testing: $testName\n";
    
    try {
        $result = $testFunction();
        if ($result['success']) {
            echo "✅ PASS: {$result['message']}\n";
            $testResults[] = ['test' => $testName, 'status' => 'PASS', 'message' => $result['message']];
        } else {
            echo "❌ FAIL: {$result['message']}\n";
            $testResults[] = ['test' => $testName, 'status' => 'FAIL', 'message' => $result['message']];
        }
    } catch (Exception $e) {
        echo "💥 ERROR: " . $e->getMessage() . "\n";
        $testResults[] = ['test' => $testName, 'status' => 'ERROR', 'message' => $e->getMessage()];
    }
    
    echo "\n";
}

// Test 1: Database Connection
runTest("Database Connection", function() {
    try {
        require_once 'db_init.php';
        $db = new SubnetDatabase('subnets.db', true);
        $conn = $db->getConnection();
        $stmt = $conn->query("SELECT COUNT(*) FROM subnet_configurations");
        $count = $stmt->fetchColumn();
        return ['success' => true, 'message' => "Database connected. Found $count existing configurations."];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()];
    }
});

// Test 2: API List Configurations
runTest("API List Configurations", function() {
    $result = testAPI('?action=list');
    if (!$result['success']) {
        return ['success' => false, 'message' => $result['error'] ?? 'API request failed'];
    }
    
    $count = count($result['data'] ?? []);
    return ['success' => true, 'message' => "API returned $count configurations successfully"];
});

// Test 3: Save New Configuration
runTest("Save New Configuration", function() {
    $testConfig = [
        'siteName' => 'Test Site ' . date('His'),
        'adminNumber' => 'TEST' . rand(100, 999),
        'networkAddress' => '10.0.0.0/8',
        'maskBits' => 8,
        'divisionData' => '1.0',
        'vlanNames' => '10.0.0.0/8:TestVLAN;'
    ];
    
    $result = testAPI('?action=save', 'POST', $testConfig);
    if (!$result['success']) {
        return ['success' => false, 'message' => $result['message'] ?? 'Save failed'];
    }
    
    // Store the ID for later tests
    global $testConfigId;
    $testConfigId = $result['data']['id'] ?? null;
    
    return ['success' => true, 'message' => "Configuration saved successfully with ID: $testConfigId"];
});

// Test 4: Load Saved Configuration
runTest("Load Saved Configuration", function() {
    global $testConfigId;
    if (!$testConfigId) {
        return ['success' => false, 'message' => 'No test configuration ID available'];
    }
    
    $result = testAPI("?action=load&id=$testConfigId");
    if (!$result['success']) {
        return ['success' => false, 'message' => $result['message'] ?? 'Load failed'];
    }
    
    $config = $result['data'];
    if (!$config['site_name'] || !$config['admin_number']) {
        return ['success' => false, 'message' => 'Loaded configuration missing required fields'];
    }
    
    return ['success' => true, 'message' => "Configuration loaded: {$config['site_name']} / {$config['admin_number']}"];
});

// Test 5: Update Configuration
runTest("Update Configuration", function() {
    global $testConfigId;
    if (!$testConfigId) {
        return ['success' => false, 'message' => 'No test configuration ID available'];
    }
    
    $updateData = [
        'configId' => $testConfigId,
        'siteName' => 'Updated Site',
        'adminNumber' => 'UPD123',
        'networkAddress' => '10.0.0.0/8',
        'maskBits' => 8,
        'divisionData' => '2.10',
        'vlanNames' => '10.0.0.0/8:UpdatedVLAN;'
    ];
    
    $result = testAPI('?action=save', 'POST', $updateData);
    if (!$result['success']) {
        return ['success' => false, 'message' => $result['message'] ?? 'Update failed'];
    }
    
    return ['success' => true, 'message' => 'Configuration updated successfully'];
});

// Test 6: Verify Update
runTest("Verify Configuration Update", function() {
    global $testConfigId;
    if (!$testConfigId) {
        return ['success' => false, 'message' => 'No test configuration ID available'];
    }
    
    $result = testAPI("?action=load&id=$testConfigId");
    if (!$result['success']) {
        return ['success' => false, 'message' => 'Failed to reload configuration'];
    }
    
    $config = $result['data'];
    if ($config['division_data'] !== '2.10') {
        return ['success' => false, 'message' => 'Update verification failed - division data not updated'];
    }
    
    if (strpos($config['vlan_names'], 'UpdatedVLAN') === false) {
        return ['success' => false, 'message' => 'Update verification failed - VLAN names not updated'];
    }
    
    return ['success' => true, 'message' => 'Configuration update verified successfully'];
});

// Test 7: Delete Test Configuration
runTest("Delete Test Configuration", function() {
    global $testConfigId;
    if (!$testConfigId) {
        return ['success' => false, 'message' => 'No test configuration ID available'];
    }
    
    $result = testAPI('?action=delete', 'DELETE', ['id' => $testConfigId]);
    if (!$result['success']) {
        return ['success' => false, 'message' => $result['message'] ?? 'Delete failed'];
    }
    
    return ['success' => true, 'message' => 'Test configuration deleted successfully'];
});

// Test 8: Validation Tests
runTest("Input Validation", function() {
    // Test missing required fields
    $invalidConfig = [
        'siteName' => '',
        'adminNumber' => 'TEST123',
        'networkAddress' => '192.168.1.0/24'
    ];
    
    $result = testAPI('?action=save', 'POST', $invalidConfig);
    if ($result['success']) {
        return ['success' => false, 'message' => 'Validation failed - empty site name was accepted'];
    }
    
    // Test invalid network format
    $invalidNetwork = [
        'siteName' => 'Test',
        'adminNumber' => 'TEST123',
        'networkAddress' => 'invalid.network',
        'maskBits' => 24
    ];
    
    $result = testAPI('?action=save', 'POST', $invalidNetwork);
    if ($result['success']) {
        return ['success' => false, 'message' => 'Validation failed - invalid network format was accepted'];
    }
    
    return ['success' => true, 'message' => 'Input validation working correctly'];
});

// Test Results Summary
echo "\n🏁 TEST RESULTS SUMMARY\n";
echo "======================\n";

$totalTests = count($testResults);
$passedTests = count(array_filter($testResults, function($r) { return $r['status'] === 'PASS'; }));
$failedTests = count(array_filter($testResults, function($r) { return $r['status'] === 'FAIL'; }));
$errorTests = count(array_filter($testResults, function($r) { return $r['status'] === 'ERROR'; }));

foreach ($testResults as $result) {
    $icon = $result['status'] === 'PASS' ? '✅' : ($result['status'] === 'FAIL' ? '❌' : '💥');
    echo "$icon {$result['status']}: {$result['test']}\n";
}

echo "\n📊 OVERALL RESULTS:\n";
echo "Total Tests: $totalTests\n";
echo "Passed: $passedTests\n";
echo "Failed: $failedTests\n";
echo "Errors: $errorTests\n";

if ($failedTests === 0 && $errorTests === 0) {
    echo "\n🎉 ALL TESTS PASSED! The subnet calculator is working perfectly!\n";
} else {
    echo "\n⚠️  Some tests failed. Please check the issues above.\n";
}

echo "\nTest completed at: " . date('Y-m-d H:i:s') . "\n";
?>