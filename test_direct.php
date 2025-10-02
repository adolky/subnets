<?php
// Direct test of API functionality
ob_start();

// Simulate API request
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'list';

try {
    include 'api.php';
    $output = ob_get_contents();
} catch (Exception $e) {
    $output = "Error: " . $e->getMessage();
}

ob_end_clean();

echo "API Output:\n";
echo $output;
echo "\n\nTesting JSON:\n";

$json = json_decode($output, true);
if ($json === null) {
    echo "JSON Error: " . json_last_error_msg() . "\n";
    echo "Raw output length: " . strlen($output) . "\n";
    echo "First 500 characters: " . substr($output, 0, 500) . "\n";
} else {
    echo "JSON Success!\n";
    print_r($json);
}
?>