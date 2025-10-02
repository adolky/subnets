<?php
// Test the API response directly
$url = 'http://localhost:9000/api.php?action=list';
$response = file_get_contents($url);
echo "Response: \n";
var_dump($response);
echo "\n\nJSON decode test:\n";
$decoded = json_decode($response, true);
if ($decoded === null) {
    echo "JSON decode failed. Error: " . json_last_error_msg() . "\n";
    echo "Raw response (first 200 chars): " . substr($response, 0, 200) . "\n";
} else {
    echo "JSON decoded successfully!\n";
    print_r($decoded);
}
?>