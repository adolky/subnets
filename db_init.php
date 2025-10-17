<?php
/**
 * MySQL Database Initialization Script for Subnet Calculator
 * Creates the database and tables for storing subnet configurations
 */

class SubnetDatabase {
    private $db;
    private $dbHost;
    private $dbName;
    private $dbUser;
    private $dbPassword;
    private $dbPort;
    private $silent;
    
    public function __construct($config = null, $silent = false) {
        // Get database configuration from environment variables or parameters
        if ($config && is_array($config)) {
            $this->dbHost = $config['host'] ?? 'localhost';
            $this->dbName = $config['name'] ?? 'subnets';
            $this->dbUser = $config['user'] ?? 'root';
            $this->dbPassword = $config['password'] ?? '';
            $this->dbPort = $config['port'] ?? 3306;
        } else {
            $this->dbHost = getenv('DB_HOST') ?: 'localhost';
            $this->dbName = getenv('DB_NAME') ?: 'subnets';
            $this->dbUser = getenv('DB_USER') ?: 'root';
            $this->dbPassword = getenv('DB_PASSWORD') ?: '';
            $this->dbPort = getenv('DB_PORT') ?: 3306;
        }
        
        $this->silent = $silent || (php_sapi_name() !== 'cli');
        $this->initializeDatabase();
    }
    
    private function initializeDatabase() {
        try {
            // Create MySQL database connection
            $dsn = "mysql:host={$this->dbHost};port={$this->dbPort};dbname={$this->dbName};charset=utf8mb4";
            $this->db = new PDO($dsn, $this->dbUser, $this->dbPassword);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
            // Create the subnet_configurations table
            $this->createTables();
            
            // Only echo when run directly from CLI and not silent
            if (!$this->silent) {
                echo "Database initialized successfully!\n";
            }
        } catch (PDOException $e) {
            if (!$this->silent) {
                die("Database connection failed: " . $e->getMessage());
            } else {
                throw $e; // Re-throw for API to handle
            }
        }
    }
    
    private function createTables() {
        $sql = "
        CREATE TABLE IF NOT EXISTS subnet_configurations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            site_name VARCHAR(255) NOT NULL,
            admin_number VARCHAR(100) NOT NULL,
            network_address VARCHAR(50) NOT NULL,
            mask_bits INT NOT NULL,
            division_data TEXT,
            vlan_ids TEXT,
            vlan_names TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_config (site_name, admin_number, network_address)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(64) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        // Execute each statement separately for MySQL
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $this->db->exec($statement);
            }
        }
        
        // Create indexes if they don't exist (MySQL doesn't support IF NOT EXISTS for indexes)
        try {
            $this->db->exec("CREATE INDEX idx_site_admin ON subnet_configurations(site_name, admin_number)");
        } catch (PDOException $e) {
            // Index already exists, ignore
        }
        try {
            $this->db->exec("CREATE INDEX idx_network ON subnet_configurations(network_address)");
        } catch (PDOException $e) {
            // Index already exists, ignore
        }
        try {
            $this->db->exec("CREATE INDEX idx_created ON subnet_configurations(created_at DESC)");
        } catch (PDOException $e) {
            // Index already exists, ignore
        }
    }
    
    public function getConnection() {
        return $this->db;
    }
}

// Initialize database if this script is run directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME']) || (php_sapi_name() === 'cli' && !isset($included_from_api))) {
    echo "Initializing Subnet Calculator database...\n";
    $database = new SubnetDatabase();
    echo "Subnet Calculator database initialized successfully!\n";
}
?>