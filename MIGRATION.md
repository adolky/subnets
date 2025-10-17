# SQLite to MySQL Migration Summary

## Overview
This document summarizes the changes made to migrate the Subnet Calculator application from SQLite to MySQL.

## Changes Made

### 1. Dockerfile (`Dockerfile`)
- **Removed**: SQLite dependencies (`sqlite3`, `libsqlite3-dev`, `pdo_sqlite`)
- **Added**: MySQL client and extensions (`default-mysql-client`, `pdo_mysql`, `mysqli`)
- **Updated**: Entrypoint script to wait for MySQL availability before starting Apache
- **Removed**: SQLite database file initialization logic

### 2. Docker Compose Files
#### Development (`docker-compose.yml`)
- **Added**: MySQL 8.0 service container
- **Added**: MySQL environment variables (root password, database, user, password)
- **Added**: MySQL volume for data persistence
- **Added**: Health check for MySQL service
- **Updated**: Application service to depend on MySQL and use environment variables
- **Added**: Dedicated network for service communication

#### Production (`docker-compose.prod.yml`)
- **Added**: Production MySQL service with enhanced security
- **Added**: MySQL configuration and init scripts volume mount
- **Updated**: Environment variables for database connection
- **Added**: MySQL authentication plugin configuration
- **Enhanced**: Security and logging for both services

### 3. Database Initialization (`db_init.php`)
- **Changed**: Connection from SQLite to MySQL using PDO
- **Added**: Support for environment variables (DB_HOST, DB_NAME, DB_USER, DB_PASSWORD, DB_PORT)
- **Updated**: Table creation syntax for MySQL compatibility:
  - `INTEGER PRIMARY KEY AUTOINCREMENT` → `INT AUTO_INCREMENT PRIMARY KEY`
  - `TEXT` → `VARCHAR(255)` or `TEXT`
  - `DATETIME DEFAULT CURRENT_TIMESTAMP` → `TIMESTAMP DEFAULT CURRENT_TIMESTAMP`
  - Added `ON UPDATE CURRENT_TIMESTAMP` for updated_at field
  - Added `ENGINE=InnoDB` and `CHARSET=utf8mb4`
  - Changed `UNIQUE(...)` to `UNIQUE KEY unique_config (...)`
- **Enhanced**: Error handling and configuration flexibility

### 4. API Layer (`api.php`)
- **Updated**: Database connection to use new MySQL-compatible SubnetDatabase class
- **Changed**: Constructor to accept environment variables instead of file path
- **Maintained**: All existing SQL queries are compatible with MySQL (PDO prepared statements)

### 5. Documentation (`README.md`)
- **Updated**: All references from SQLite to MySQL
- **Added**: Environment variables documentation
- **Updated**: Installation instructions for both Docker and manual setup
- **Added**: MySQL configuration and setup steps
- **Updated**: Troubleshooting section for MySQL-specific issues
- **Added**: Migration section with detailed steps
- **Updated**: Technology stack, performance, and architecture sections

### 6. New Files Created

#### `.env.example`
Template file for environment variables containing:
- MySQL credentials (root password, database, user, password)
- Application configuration (server name)
- Database connection settings (host, port, name, user, password)

#### `migrate_sqlite_to_mysql.php`
Migration script that:
- Reads data from existing SQLite database
- Connects to MySQL database using environment variables
- Transfers all subnet configurations
- Handles duplicates with ON DUPLICATE KEY UPDATE
- Provides detailed progress reporting and error handling

#### `.gitignore`
Prevents committing sensitive files:
- Environment files (.env)
- Database files (*.db, *.sql)
- IDE configurations
- Logs and temporary files

## Database Schema Comparison

### SQLite (Old)
```sql
CREATE TABLE subnet_configurations (
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
```

### MySQL (New)
```sql
CREATE TABLE subnet_configurations (
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
```

## Benefits of MySQL Migration

1. **Scalability**: Better performance with large datasets and concurrent users
2. **Features**: Advanced features like replication, clustering, and better backup tools
3. **Enterprise Ready**: More suitable for production environments
4. **Concurrent Access**: Better handling of multiple simultaneous connections
5. **Data Integrity**: Enhanced transaction support and foreign key constraints
6. **Monitoring**: Better tools for monitoring and performance optimization

## Deployment Instructions

### For New Deployments
```bash
git clone https://github.com/adolky/subnets.git
cd subnets
cp .env.example .env
# Edit .env with secure passwords
nano .env
docker-compose up -d
```

### For Existing SQLite Users
```bash
# Backup existing data
cp subnets.db subnets.db.backup

# Configure MySQL
cp .env.example .env
nano .env

# Start MySQL
docker-compose up -d mysql

# Migrate data
export DB_HOST=localhost
export DB_NAME=subnets
export DB_USER=subnets_user
export DB_PASSWORD=your_password
php migrate_sqlite_to_mysql.php

# Start application
docker-compose up -d
```

## Testing Checklist

- [ ] MySQL container starts successfully
- [ ] Database tables are created automatically
- [ ] Application can connect to MySQL
- [ ] Subnet configurations can be saved
- [ ] Saved configurations can be loaded
- [ ] IP search functionality works
- [ ] VLAN IDs and descriptions are saved correctly
- [ ] Existing SQLite data migrates successfully
- [ ] No data loss during migration
- [ ] Performance is acceptable

## Rollback Plan

If issues occur, you can rollback to SQLite:
1. Stop containers: `docker-compose down`
2. Restore SQLite database: `cp subnets.db.backup subnets.db`
3. Checkout previous commit: `git checkout <previous-commit>`
4. Restart: `docker-compose up -d`

## Security Considerations

1. **Environment Variables**: Never commit `.env` file to version control
2. **Strong Passwords**: Use strong passwords for MySQL root and user accounts
3. **Network Isolation**: MySQL container only accessible from application container
4. **Least Privilege**: Application uses dedicated user with minimal permissions
5. **Regular Backups**: Implement automated backup strategy for MySQL data

## Maintenance

### Backup MySQL Database
```bash
# Using Docker
docker exec subnet-mysql mysqldump -u root -p subnets > backup.sql

# Restore from backup
docker exec -i subnet-mysql mysql -u root -p subnets < backup.sql
```

### View Logs
```bash
# MySQL logs
docker logs subnet-mysql

# Application logs
docker logs subnet-calculator
```

### Database Management
```bash
# Connect to MySQL
docker exec -it subnet-mysql mysql -u root -p

# View tables
USE subnets;
SHOW TABLES;
DESCRIBE subnet_configurations;
```

## Support

For issues or questions:
- Check the troubleshooting section in README.md
- Review Docker logs for error messages
- Ensure environment variables are correctly set
- Verify MySQL service health: `docker ps`

---

**Migration completed successfully!** The application now uses MySQL for better scalability and enterprise features.
