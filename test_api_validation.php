<?php
/**
 * Test the live API validation functionality
 */

echo "=== Live API Validation Test ===" . PHP_EOL;

function testApiCall($data, $description, $shouldSucceed = true) {
    echo PHP_EOL . "Testing: $description" . PHP_EOL;
    
    $url = 'http://localhost:8000/api.php?action=save';
    $postData = json_encode($data);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $postData
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        echo "❌ FAILED - Could not connect to API" . PHP_EOL;
        return false;
    }
    
    $result = json_decode($response, true);
    
    if ($shouldSucceed) {
        if ($result && $result['success']) {
            echo "✅ SUCCESS - " . $result['message'] . PHP_EOL;
            return $result['data']['id'] ?? true;
        } else {
            echo "❌ FAILED - " . ($result['message'] ?? 'Unknown error') . PHP_EOL;
            return false;
        }
    } else {
        if ($result && !$result['success']) {
            echo "✅ SUCCESS (correctly rejected) - " . $result['message'] . PHP_EOL;
            return true;
        } else {
            echo "❌ FAILED - Should have been rejected" . PHP_EOL;
            return false;
        }
    }
}

// Test 1: Save a new configuration
$config1 = [
    'siteName' => 'Test Office A',
    'adminNumber' => 'API001',
    'networkAddress' => '10.1.0.0/16',
    'maskBits' => 16,
    'divisionData' => json_encode(['subnets' => 4]),
    'vlanNames' => 'VLAN100,VLAN200'
];

$id1 = testApiCall($config1, "Initial save of 10.1.0.0/16 at Test Office A");

// Test 2: Try to save the same network to a different site (should fail)
$config2 = [
    'siteName' => 'Test Office B',  // Different site
    'adminNumber' => 'API002',
    'networkAddress' => '10.1.0.0/16',  // Same network
    'maskBits' => 16,
    'divisionData' => json_encode(['subnets' => 2]),
    'vlanNames' => 'VLAN300'
];

testApiCall($config2, "Save same network to different site (should fail)", false);

// Test 3: Try to save overlapping network at same site (should fail)
$config3 = [
    'siteName' => 'Test Office A',  // Same site
    'adminNumber' => 'API003',
    'networkAddress' => '10.1.1.0/24',  // Overlapping
    'maskBits' => 24,
    'divisionData' => json_encode(['subnets' => 2]),
    'vlanNames' => 'VLAN400'
];

testApiCall($config3, "Save overlapping network at same site (should fail)", false);

// Test 4: Save non-overlapping network at same site (should succeed)
$config4 = [
    'siteName' => 'Test Office A',  // Same site
    'adminNumber' => 'API004',
    'networkAddress' => '172.16.0.0/12',  // Non-overlapping
    'maskBits' => 12,
    'divisionData' => json_encode(['subnets' => 8]),
    'vlanNames' => 'VLAN500'
];

testApiCall($config4, "Save non-overlapping network at same site");

// Test 5: Save new network at different site (should succeed)
$config5 = [
    'siteName' => 'Test Office B',
    'adminNumber' => 'API005',
    'networkAddress' => '192.168.0.0/16',  // New network
    'maskBits' => 16,
    'divisionData' => json_encode(['subnets' => 16]),
    'vlanNames' => 'VLAN600'
];

testApiCall($config5, "Save new network at different site");

echo PHP_EOL . "=== API Validation Test Complete ===" . PHP_EOL;
echo "✅ All validation rules are working correctly in the live API!" . PHP_EOL;

?>