<?php
// Test the search API endpoint directly via HTTP
echo "🌐 HTTP API Test\n";
echo "===============\n\n";

$testIP = '192.168.3.125';
$apiUrl = "http://localhost:8080/api.php?action=searchIP&ip=" . urlencode($testIP);

echo "Testing IP: $testIP\n";
echo "API URL: $apiUrl\n\n";

// Use cURL to make HTTP request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
if ($error) {
    echo "cURL Error: $error\n";
    exit;
}

echo "Response:\n";
echo $response . "\n\n";

$data = json_decode($response, true);
if ($data && isset($data['success'])) {
    if ($data['success']) {
        echo "✅ Search successful!\n";
        if (isset($data['data']) && !empty($data['data'])) {
            echo "📊 Found " . count($data['data']) . " match(es):\n";
            foreach ($data['data'] as $match) {
                echo "   - Site: {$match['siteName']}\n";
                echo "     Subnet: {$match['subnet']}\n";
                echo "     Network: {$match['networkAddress']}\n\n";
            }
        } else {
            echo "❌ No matches found\n";
        }
    } else {
        echo "❌ Search failed: " . $data['message'] . "\n";
    }
} else {
    echo "❌ Invalid response format\n";
}
?>