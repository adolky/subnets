<?php
// Direct test of the search API functionality
echo "🔍 Direct API Search Test\n";
echo "========================\n\n";

// Test IP that should match
$testIP = '192.168.3.125';
echo "Testing IP: $testIP\n\n";

try {
    $db = new PDO('sqlite:subnets.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all configurations
    $stmt = $db->prepare("SELECT id, site_name, admin_number, network_address, mask_bits, division_data, vlan_names, created_at FROM subnet_configurations ORDER BY created_at DESC");
    $stmt->execute();
    $configurations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📊 Found " . count($configurations) . " configurations\n\n";
    
    $matches = [];
    
    foreach ($configurations as $config) {
        echo "🔍 Checking configuration: {$config['site_name']} ({$config['network_address']})\n";
        
        // Parse division data
        $divisionData = json_decode($config['division_data'], true);
        if (!$divisionData) {
            echo "   ❌ Invalid division data\n";
            continue;
        }
        
        echo "   📋 Division data: " . json_encode($divisionData) . "\n";
        
        // Extract subnets from this configuration
        $subnets = extractSubnetsFromConfig($config);
        
        echo "   📊 Found " . count($subnets) . " subnets:\n";
        foreach ($subnets as $subnet) {
            echo "      - {$subnet['network']}/{$subnet['mask']}\n";
            
            // Check if IP is in this subnet
            if (isIPInSubnet($testIP, $subnet['network'], $subnet['mask'])) {
                echo "      ✅ MATCH! IP $testIP is in subnet {$subnet['network']}/{$subnet['mask']}\n";
                $matches[] = [
                    'configId' => $config['id'],
                    'siteName' => $config['site_name'],
                    'adminNumber' => $config['admin_number'],
                    'networkAddress' => $config['network_address'],
                    'subnet' => $subnet['network'] . '/' . $subnet['mask'],
                    'vlanName' => $subnet['vlanName'] ?? '',
                    'createdAt' => $config['created_at']
                ];
            }
        }
        echo "\n";
    }
    
    echo "🎯 Final Results:\n";
    echo "================\n";
    if (empty($matches)) {
        echo "❌ No matches found for IP $testIP\n";
    } else {
        echo "✅ Found " . count($matches) . " match(es):\n";
        foreach ($matches as $match) {
            echo "   - Site: {$match['siteName']}\n";
            echo "     Subnet: {$match['subnet']}\n";
            echo "     Network: {$match['networkAddress']}\n\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// Helper functions (copied from API)
function extractSubnetsFromConfig($config) {
    $subnets = [];
    $divisionData = json_decode($config['division_data'], true);
    $vlanNames = json_decode($config['vlan_names'], true) ?? [];
    
    if (!$divisionData) {
        return $subnets;
    }
    
    // Parse the network address to get base network and mask
    $networkParts = explode('/', $config['network_address']);
    if (count($networkParts) !== 2) {
        return $subnets;
    }
    
    $baseNetwork = $networkParts[0];
    $baseMask = intval($networkParts[1]);
    
    // Recursive function to extract all subnets from division data
    $extractFromNode = function($node, $currentNetwork, $currentMask) use (&$extractFromNode, &$subnets, $vlanNames) {
        if (!is_array($node)) {
            return;
        }
        
        // Add current subnet
        $subnetKey = $currentNetwork . '/' . $currentMask;
        $subnets[] = [
            'network' => $currentNetwork,
            'mask' => $currentMask,
            'vlanName' => $vlanNames[$subnetKey] ?? ''
        ];
        
        // Process child subnets
        if (isset($node[2]) && is_array($node[2])) {
            foreach ($node[2] as $childIndex => $childNode) {
                if (is_array($childNode)) {
                    // Calculate child network address
                    $childMask = $currentMask + 1;
                    $childNetwork = calculateSubnetAddress($currentNetwork, $currentMask, $childIndex);
                    $extractFromNode($childNode, $childNetwork, $childMask);
                }
            }
        }
    };
    
    $extractFromNode($divisionData, $baseNetwork, $baseMask);
    
    return $subnets;
}

function calculateSubnetAddress($networkAddress, $mask, $subnetIndex) {
    // Convert IP to long
    $networkLong = ip2long($networkAddress);
    
    // Calculate subnet size
    $subnetSize = 1 << (32 - ($mask + 1));
    
    // Calculate new subnet address
    $newNetworkLong = $networkLong + ($subnetIndex * $subnetSize);
    
    return long2ip($newNetworkLong);
}

function isIPInSubnet($ip, $networkIP, $maskBits) {
    $ipLong = ip2long($ip);
    $networkLong = ip2long($networkIP);
    $subnetMask = (-1 << (32 - $maskBits)) & 0xFFFFFFFF;
    
    $networkAddress = $networkLong & $subnetMask;
    $broadcastAddress = $networkAddress | (~$subnetMask & 0xFFFFFFFF);
    
    $result = $ipLong >= $networkAddress && $ipLong <= $broadcastAddress;
    
    echo "      🧮 Math check: IP $ip (" . ip2long($ip) . ") in range $networkAddress to $broadcastAddress = " . ($result ? 'YES' : 'NO') . "\n";
    
    return $result;
}
?>