<?php
// Test the updated API directly
echo "🔍 Testing Updated API\n";
echo "====================\n\n";

$testIP = '192.168.3.125';
echo "Testing IP: $testIP\n\n";

// Simulate API call
$_POST['action'] = 'searchIP';
$_POST['ip'] = $testIP;
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Capture output
ob_start();
include 'api.php';
$output = ob_get_clean();

echo "API Response:\n";
echo $output . "\n";

$response = json_decode($output, true);
if ($response && isset($response['success'])) {
    if ($response['success']) {
        echo "\n✅ API call successful!\n";
        if (isset($response['data']) && !empty($response['data'])) {
            echo "📊 Found " . count($response['data']) . " match(es):\n";
            foreach ($response['data'] as $match) {
                echo "   - Site: {$match['siteName']}\n";
                echo "     Subnet: {$match['subnet']}\n";
                echo "     Network: {$match['networkAddress']}\n\n";
            }
        } else {
            echo "❌ No matches found\n";
        }
    } else {
        echo "❌ API call failed: " . $response['message'] . "\n";
    }
} else {
    echo "❌ Invalid API response\n";
}
?>