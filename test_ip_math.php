<?php
// Quick IP search validation test
echo "🧪 IP Search Mathematical Validation\n";
echo "===================================\n\n";

function ipToLong($ip) {
    $parts = explode('.', $ip);
    return ($parts[0] << 24) + ($parts[1] << 16) + ($parts[2] << 8) + $parts[3];
}

function isIPInSubnet($targetIP, $networkIP, $maskBits) {
    $targetLong = ipToLong($targetIP);
    $networkLong = ipToLong($networkIP);
    $subnetMask = (-1 << (32 - $maskBits)) & 0xFFFFFFFF;
    
    $networkAddress = $networkLong & $subnetMask;
    $broadcastAddress = $networkAddress | (~$subnetMask & 0xFFFFFFFF);
    
    echo "Target IP: $targetIP → $targetLong\n";
    echo "Network: $networkIP/$maskBits → $networkLong\n";
    echo "Subnet Mask: " . sprintf("0x%08X", $subnetMask) . "\n";
    echo "Network Address: $networkAddress\n";
    echo "Broadcast Address: $broadcastAddress\n";
    echo "In Range: " . ($targetLong >= $networkAddress && $targetLong <= $broadcastAddress ? "✅ YES" : "❌ NO") . "\n\n";
    
    return $targetLong >= $networkAddress && $targetLong <= $broadcastAddress;
}

// Test the problematic case
echo "Testing: 192.168.3.4 in subnet 192.168.3.0/25\n";
echo "===========================================\n";
$result1 = isIPInSubnet('192.168.3.4', '192.168.3.0', 25);

echo "Testing: 192.168.3.150 in subnet 192.168.3.128/26\n";
echo "=============================================\n";
$result2 = isIPInSubnet('192.168.3.150', '192.168.3.128', 26);

echo "Testing: 10.0.0.1 in subnet 192.168.3.0/25 (should fail)\n";
echo "=======================================================\n";
$result3 = isIPInSubnet('10.0.0.1', '192.168.3.0', 25);

echo "Summary:\n";
echo "========\n";
echo "192.168.3.4 in 192.168.3.0/25: " . ($result1 ? "✅ PASS" : "❌ FAIL") . "\n";
echo "192.168.3.150 in 192.168.3.128/26: " . ($result2 ? "✅ PASS" : "❌ FAIL") . "\n";
echo "10.0.0.1 in 192.168.3.0/25: " . (!$result3 ? "✅ PASS (correctly rejected)" : "❌ FAIL (should be rejected)") . "\n";
?>