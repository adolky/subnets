<?php
// Quick test of the new IP search API endpoint
echo "🧪 Database IP Search API Test\n";
echo "==============================\n\n";

// Test cases
$testIPs = [
    '192.168.1.50' => 'Valid IP in common range',
    '10.0.0.1' => 'Valid IP in another range',
    '999.999.999.999' => 'Invalid IP format',
    '' => 'Empty IP'
];

foreach ($testIPs as $ip => $description) {
    echo "Testing: $description\n";
    echo "IP: '$ip'\n";
    
    $url = "http://localhost:8000/api.php?action=searchIP&ip=" . urlencode($ip);
    echo "URL: $url\n";
    
    $response = @file_get_contents($url);
    
    if ($response === false) {
        echo "❌ ERROR: Could not connect to API\n";
    } else {
        $data = json_decode($response, true);
        if ($data) {
            echo "✅ Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "❌ Invalid JSON response: $response\n";
        }
    }
    
    echo str_repeat("-", 50) . "\n\n";
}
?>