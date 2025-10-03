<?php
/**
 * SQLite Database Initialization Script for Subnet Calculator
 * Creates the database and tables for storing subnet configurations
 */

class SubnetDatabase {
    private $db;
    private $dbPath;
    private $silent;
    
    public function __construct($dbPath = 'subnets.db', $silent = false) {
        $this->dbPath = $dbPath;
        $this->silent = $silent || (php_sapi_name() !== 'cli');
        $this->initializeDatabase();
    }
    
    private function initializeDatabase() {
        try {
            // Create SQLite database connection
            $this->db = new PDO("sqlite:" . $this->dbPath);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
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
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            site_name TEXT NOT NULL,
            admin_number TEXT NOT NULL,
            network_address TEXT NOT NULL,
            mask_bits INTEGER NOT NULL,
            division_data TEXT,
            vlan_ids TEXT,
            vlan_names TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(site_name, admin_number, network_address)
        );
        
        CREATE INDEX IF NOT EXISTS idx_site_admin ON subnet_configurations(site_name, admin_number);
        CREATE INDEX IF NOT EXISTS idx_network ON subnet_configurations(network_address);
        CREATE INDEX IF NOT EXISTS idx_created ON subnet_configurations(created_at DESC);
        ";
        
        $this->db->exec($sql);
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