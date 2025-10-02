<?php
/**
 * Subnet Configuration API
 * Handles saving and loading subnet configurations from SQLite database
 */

// Start output buffering to catch any unwanted output
ob_start();

// Disable error display and enable error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once 'db_init.php';

// Clear any output that might have been generated
ob_clean();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

class SubnetAPI {
    private $db;
    
    public function __construct() {
        $database = new SubnetDatabase('subnets.db', true); // Silent mode
        $this->db = $database->getConnection();
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';
        
        try {
            switch ($method) {
                case 'POST':
                    if ($action === 'save') {
                        return $this->saveConfiguration();
                    }
                    break;
                case 'GET':
                    if ($action === 'list') {
                        return $this->listConfigurations();
                    } elseif ($action === 'load') {
                        return $this->loadConfiguration();
                    } elseif ($action === 'searchIP') {
                        return $this->searchIPInDatabase();
                    }
                    break;
                case 'DELETE':
                    if ($action === 'delete') {
                        return $this->deleteConfiguration();
                    }
                    break;
            }
            
            return $this->sendResponse(false, 'Invalid action or method');
        } catch (Exception $e) {
            return $this->sendResponse(false, 'Server error: ' . $e->getMessage());
        }
    }
    
    private function saveConfiguration() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $requiredFields = ['siteName', 'adminNumber', 'networkAddress', 'maskBits', 'divisionData'];
        foreach ($requiredFields as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                return $this->sendResponse(false, "Missing required field: $field");
            }
        }
        
        // Validate network address format
        if (!$this->validateNetworkAddress($input['networkAddress'], $input['maskBits'])) {
            return $this->sendResponse(false, 'Invalid network address format. Expected format: x.x.x.x/x');
        }
        
        $siteName = trim($input['siteName']);
        $adminNumber = trim($input['adminNumber']);
        $networkAddress = trim($input['networkAddress']);
        $maskBits = intval($input['maskBits']);
        $divisionData = $input['divisionData'];
        $vlanNames = $input['vlanNames'] ?? '';
        
        try {
            // Perform validation checks before saving
            $validationResult = $this->validateSubnetConfiguration($networkAddress, $siteName, $adminNumber, $input['configId'] ?? null);
            if (!$validationResult['valid']) {
                return $this->sendResponse(false, $validationResult['message']);
            }
            
            $configId = $input['configId'] ?? null;
            
            if ($configId) {
                // Update existing configuration by ID
                // Additional check: ensure we're not changing the network to conflict with another site
                $stmt = $this->db->prepare(
                    "SELECT site_name, network_address FROM subnet_configurations WHERE id = ?"
                );
                $stmt->execute([$configId]);
                $currentConfig = $stmt->fetch();
                
                if (!$currentConfig) {
                    return $this->sendResponse(false, 'Configuration not found');
                }
                
                // If network address changed, validate the new address
                if ($currentConfig['network_address'] !== $networkAddress) {
                    $validationResult = $this->validateSubnetConfiguration($networkAddress, $siteName, $adminNumber, $configId);
                    if (!$validationResult['valid']) {
                        return $this->sendResponse(false, $validationResult['message']);
                    }
                }
                
                $stmt = $this->db->prepare(
                    "UPDATE subnet_configurations 
                     SET division_data = ?, vlan_names = ?, updated_at = CURRENT_TIMESTAMP 
                     WHERE id = ?"
                );
                $stmt->execute([$divisionData, $vlanNames, $configId]);
                
                if ($stmt->rowCount() > 0) {
                    $message = 'Configuration updated successfully';
                    return $this->sendResponse(true, $message, ['id' => $configId]);
                } else {
                    return $this->sendResponse(false, 'Configuration not found or no changes made');
                }
            } else {
                // Check if configuration already exists (for new saves)
                $stmt = $this->db->prepare(
                    "SELECT id FROM subnet_configurations 
                     WHERE site_name = ? AND admin_number = ? AND network_address = ?"
                );
                $stmt->execute([$siteName, $adminNumber, $networkAddress]);
                $existingConfig = $stmt->fetch();
                
                if ($existingConfig) {
                    // Update existing configuration
                    $stmt = $this->db->prepare(
                        "UPDATE subnet_configurations 
                         SET division_data = ?, vlan_names = ?, updated_at = CURRENT_TIMESTAMP 
                         WHERE id = ?"
                    );
                    $stmt->execute([$divisionData, $vlanNames, $existingConfig['id']]);
                    $message = 'Configuration updated successfully';
                    return $this->sendResponse(true, $message, ['id' => $existingConfig['id']]);
                } else {
                    // Insert new configuration
                    $stmt = $this->db->prepare(
                        "INSERT INTO subnet_configurations 
                         (site_name, admin_number, network_address, mask_bits, division_data, vlan_names) 
                         VALUES (?, ?, ?, ?, ?, ?)"
                    );
                    $stmt->execute([$siteName, $adminNumber, $networkAddress, $maskBits, $divisionData, $vlanNames]);
                    $newId = $this->db->lastInsertId();
                    $message = 'Configuration saved successfully';
                    return $this->sendResponse(true, $message, ['id' => $newId]);
                }
            }
            
        } catch (PDOException $e) {
            return $this->sendResponse(false, 'Database error: ' . $e->getMessage());
        }
    }
    
    private function listConfigurations() {
        $search = $_GET['search'] ?? '';
        
        $sql = "SELECT id, site_name, admin_number, network_address, mask_bits, 
                       created_at, updated_at 
                FROM subnet_configurations";
        $params = [];
        
        if (!empty($search)) {
            $sql .= " WHERE site_name LIKE ? OR admin_number LIKE ? OR network_address LIKE ?";
            $searchParam = "%$search%";
            $params = [$searchParam, $searchParam, $searchParam];
        }
        
        $sql .= " ORDER BY updated_at DESC LIMIT 50";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $configurations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $this->sendResponse(true, 'Configurations retrieved successfully', $configurations);
    }
    
    private function loadConfiguration() {
        $id = $_GET['id'] ?? '';
        
        if (empty($id)) {
            return $this->sendResponse(false, 'Configuration ID is required');
        }
        
        $stmt = $this->db->prepare(
            "SELECT * FROM subnet_configurations WHERE id = ?"
        );
        $stmt->execute([$id]);
        
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$config) {
            return $this->sendResponse(false, 'Configuration not found');
        }
        
        return $this->sendResponse(true, 'Configuration loaded successfully', $config);
    }
    
    private function deleteConfiguration() {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? '';
        
        if (empty($id)) {
            return $this->sendResponse(false, 'Configuration ID is required');
        }
        
        $stmt = $this->db->prepare("DELETE FROM subnet_configurations WHERE id = ?");
        $result = $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            return $this->sendResponse(true, 'Configuration deleted successfully');
        } else {
            return $this->sendResponse(false, 'Configuration not found or already deleted');
        }
    }
    
    private function validateSubnetConfiguration($networkAddress, $siteName, $adminNumber, $excludeConfigId = null) {
        // Check if this network address is already assigned to a different site
        $query = "SELECT id, site_name, admin_number FROM subnet_configurations WHERE network_address = ?";
        $params = [$networkAddress];
        
        if ($excludeConfigId) {
            $query .= " AND id != ?";
            $params[] = $excludeConfigId;
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $existingConfig = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingConfig) {
            // Check if it's assigned to a different site
            if ($existingConfig['site_name'] !== $siteName) {
                return [
                    'valid' => false,
                    'message' => "Network {$networkAddress} is already assigned to site '{$existingConfig['site_name']}' (Admin: {$existingConfig['admin_number']}). A subnet cannot be assigned to multiple sites."
                ];
            }
            
            // Check if it's a duplicate with different admin number at same site
            if ($existingConfig['admin_number'] !== $adminNumber) {
                return [
                    'valid' => false,
                    'message' => "Network {$networkAddress} already exists at site '{$siteName}' with admin number '{$existingConfig['admin_number']}'. Duplicate configurations are not allowed."
                ];
            }
        }
        
        // Check for overlapping subnet ranges within the same site
        $networkParts = explode('/', $networkAddress);
        $networkIp = $networkParts[0];
        $maskBits = intval($networkParts[1]);
        
        // Get all existing networks for this site (excluding current config if updating)
        $query = "SELECT network_address FROM subnet_configurations WHERE site_name = ?";
        $params = [$siteName];
        
        if ($excludeConfigId) {
            $query .= " AND id != ?";
            $params[] = $excludeConfigId;
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $existingNetworks = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($existingNetworks as $existingNetwork) {
            // Skip exact matches (these are handled by the duplicate check above)
            if ($existingNetwork === $networkAddress) {
                continue;
            }
            
            if ($this->networksOverlap($networkAddress, $existingNetwork)) {
                return [
                    'valid' => false,
                    'message' => "Network {$networkAddress} overlaps with existing network {$existingNetwork} at the same site. Overlapping subnets are not allowed."
                ];
            }
        }
        
        return ['valid' => true];
    }
    
    private function networksOverlap($network1, $network2) {
        // Parse both networks
        list($ip1, $mask1) = explode('/', $network1);
        list($ip2, $mask2) = explode('/', $network2);
        
        // Convert IPs to long integers
        $ip1Long = ip2long($ip1);
        $ip2Long = ip2long($ip2);
        
        // Calculate network addresses
        $network1Long = $ip1Long & (-1 << (32 - intval($mask1)));
        $network2Long = $ip2Long & (-1 << (32 - intval($mask2)));
        
        // Calculate broadcast addresses
        $broadcast1Long = $network1Long | ((1 << (32 - intval($mask1))) - 1);
        $broadcast2Long = $network2Long | ((1 << (32 - intval($mask2))) - 1);
        
        // Check for overlap
        return !($broadcast1Long < $network2Long || $broadcast2Long < $network1Long);
    }
    
    private function validateNetworkAddress($networkAddress, $maskBits) {
        // Check format x.x.x.x/x
        $pattern = '/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})\/(\d{1,2})$/';
        if (!preg_match($pattern, $networkAddress, $matches)) {
            return false;
        }
        
        // Validate IP octets (0-255)
        for ($i = 1; $i <= 4; $i++) {
            if ($matches[$i] > 255) {
                return false;
            }
        }
        
        // Validate mask bits (0-32)
        $mask = intval($matches[5]);
        if ($mask < 0 || $mask > 32 || $mask != $maskBits) {
            return false;
        }
        
        return true;
    }
    
    private function searchIPInDatabase() {
        $ip = $_GET['ip'] ?? '';
        
        if (empty($ip)) {
            return $this->sendResponse(false, 'IP address parameter is required');
        }
        
        // Validate IP address format
        if (!$this->validateIP($ip)) {
            return $this->sendResponse(false, 'Invalid IP address format');
        }
        
        try {
            // Get all subnet configurations from database
            $stmt = $this->db->prepare(
                "SELECT id, site_name, admin_number, network_address, mask_bits, division_data, vlan_names, created_at 
                 FROM subnet_configurations 
                 ORDER BY created_at DESC"
            );
            $stmt->execute();
            $configurations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $matches = [];
            
            foreach ($configurations as $config) {
                $subnets = $this->extractSubnetsFromConfig($config);
                
                foreach ($subnets as $subnet) {
                    if ($this->isIPInSubnet($ip, $subnet['network'], $subnet['mask'])) {
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
            }
            
            if (empty($matches)) {
                return $this->sendResponse(true, 'IP address not found in any saved subnet configurations', []);
            }
            
            $count = count($matches);
            return $this->sendResponse(true, "Found $count matching subnet(s) for IP $ip", $matches);
            
        } catch (Exception $e) {
            return $this->sendResponse(false, 'Error searching IP in database: ' . $e->getMessage());
        }
    }
    
    private function validateIP($ip) {
        $pattern = '/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/';
        if (!preg_match($pattern, $ip, $matches)) {
            return false;
        }
        
        // Validate each octet (0-255)
        for ($i = 1; $i <= 4; $i++) {
            $octet = intval($matches[$i]);
            if ($octet < 0 || $octet > 255) {
                return false;
            }
        }
        
        return true;
    }
    
    private function extractSubnetsFromConfig($config) {
        $subnets = [];
        $divisionData = $config['division_data'];
        $vlanNames = json_decode($config['vlan_names'], true) ?? [];
        
        // Parse the network address to get base network and mask
        $networkParts = explode('/', $config['network_address']);
        if (count($networkParts) !== 2) {
            return $subnets;
        }
        
        $baseNetwork = $networkParts[0];
        $baseMask = intval($networkParts[1]);
        
        // If division data is just "1.0", it means no subdivisions
        if ($divisionData === '1.0' || !$divisionData) {
            // Just return the base network
            $subnets[] = [
                'network' => $baseNetwork,
                'mask' => $baseMask,
                'vlanName' => $vlanNames[$baseNetwork . '/' . $baseMask] ?? ''
            ];
            return $subnets;
        }
        
        // Decode the division data from the encoded format
        $binaryString = $this->asciiToBin($divisionData);
        if (!$binaryString) {
            // If can't decode, return base network
            $subnets[] = [
                'network' => $baseNetwork,
                'mask' => $baseMask,
                'vlanName' => $vlanNames[$baseNetwork . '/' . $baseMask] ?? ''
            ];
            return $subnets;
        }
        
        // Parse binary string into subnet tree structure
        $rootNode = [];
        $position = 0;
        $this->parseNodeFromBinary($rootNode, $binaryString, $position);
        
        // Extract all subnets from the tree
        $this->extractSubnetsFromNode($rootNode, $baseNetwork, $baseMask, $subnets, $vlanNames);
        
        return $subnets;
    }
    
    private function asciiToBin($str) {
        if (preg_match('/([0-9]+)\.([0-9a-f]+)/', $str, $matches)) {
            $len = intval($matches[1]);
            $encoded = $matches[2];
            $out = '';
            
            for ($i = 0; $i < $len; $i++) {
                $hexIndex = intval($i / 4);
                if ($hexIndex < strlen($encoded)) {
                    $ch = hexdec($encoded[$hexIndex]);
                    $pos = $i % 4;
                    $out .= (($ch & (1 << $pos)) ? '1' : '0');
                }
            }
            
            return $out;
        }
        
        return false;
    }
    
    private function parseNodeFromBinary(&$node, $binaryString, &$position) {
        if ($position >= strlen($binaryString)) {
            return;
        }
        
        $bit = $binaryString[$position];
        $position++;
        
        if ($bit === '1') {
            // Node has children
            $node[2] = [[], []];
            $this->parseNodeFromBinary($node[2][0], $binaryString, $position);
            $this->parseNodeFromBinary($node[2][1], $binaryString, $position);
        }
        // If bit is '0', it's a leaf node (no children)
    }
    
    private function extractSubnetsFromNode($node, $network, $mask, &$subnets, $vlanNames) {
        // Add current subnet
        $subnetKey = $network . '/' . $mask;
        $subnets[] = [
            'network' => $network,
            'mask' => $mask,
            'vlanName' => $vlanNames[$subnetKey] ?? ''
        ];
        
        // If node has children, process them
        if (isset($node[2]) && is_array($node[2]) && count($node[2]) >= 2) {
            $childMask = $mask + 1;
            
            // Left child (first half)
            $leftNetwork = $network;
            $this->extractSubnetsFromNode($node[2][0], $leftNetwork, $childMask, $subnets, $vlanNames);
            
            // Right child (second half)
            $rightNetwork = $this->calculateSubnetAddress($network, $mask, 1);
            $this->extractSubnetsFromNode($node[2][1], $rightNetwork, $childMask, $subnets, $vlanNames);
        }
    }
    
    private function calculateSubnetAddress($networkAddress, $mask, $subnetIndex) {
        // Convert IP to long
        $networkLong = ip2long($networkAddress);
        
        // Calculate subnet size
        $subnetSize = 1 << (32 - ($mask + 1));
        
        // Calculate new subnet address
        $newNetworkLong = $networkLong + ($subnetIndex * $subnetSize);
        
        return long2ip($newNetworkLong);
    }
    
    private function isIPInSubnet($ip, $networkIP, $maskBits) {
        $ipLong = ip2long($ip);
        $networkLong = ip2long($networkIP);
        $subnetMask = (-1 << (32 - $maskBits)) & 0xFFFFFFFF;
        
        $networkAddress = $networkLong & $subnetMask;
        $broadcastAddress = $networkAddress | (~$subnetMask & 0xFFFFFFFF);
        
        return $ipLong >= $networkAddress && $ipLong <= $broadcastAddress;
    }
    
    private function sendResponse($success, $message, $data = null) {
        $response = [
            'success' => $success,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response);
        return $response;
    }
}

// Handle the request
try {
    $api = new SubnetAPI();
    $api->handleRequest();
} catch (Exception $e) {
    // Ensure we send a proper JSON error response
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>