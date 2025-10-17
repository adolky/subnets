# Quick Start Guide - MySQL Version

## Prerequisites
- Docker and Docker Compose installed
- Git installed

## Installation Steps

### 1. Clone and Setup
```bash
# Clone repository
git clone https://github.com/adolky/subnets.git
cd subnets

# Create environment configuration
cp .env.example .env
```

### 2. Configure Environment Variables
Edit the `.env` file and set secure passwords:
```bash
nano .env
```

**Important**: Change these default passwords:
- `MYSQL_ROOT_PASSWORD`
- `MYSQL_PASSWORD` (and `DB_PASSWORD` - keep them the same)

### 3. Start the Application
```bash
# Start all services
docker-compose up -d

# Check status
docker-compose ps

# View logs
docker-compose logs -f
```

### 4. Access the Application
Open your browser and navigate to:
```
http://localhost:8080
```

## Verification

### Check MySQL Connection
```bash
# Connect to MySQL container
docker exec -it subnet-mysql mysql -u root -p

# Inside MySQL
USE subnets;
SHOW TABLES;
EXIT;
```

### Test API
```bash
# List configurations
curl http://localhost:8080/api.php?action=list

# Check health
docker-compose ps
```

## Common Commands

### Start/Stop Services
```bash
# Start services
docker-compose up -d

# Stop services
docker-compose down

# Restart a service
docker-compose restart subnet-calculator
```

### View Logs
```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f subnet-calculator
docker-compose logs -f mysql
```

### Database Backup
```bash
# Create backup
docker exec subnet-mysql mysqldump -u root -p subnets > backup_$(date +%Y%m%d).sql

# Restore from backup
docker exec -i subnet-mysql mysql -u root -p subnets < backup.sql
```

## Migrating from SQLite

If you have existing data in SQLite:

### 1. Backup Current Data
```bash
cp subnets.db subnets.db.backup
```

### 2. Start MySQL Service
```bash
docker-compose up -d mysql

# Wait for MySQL to be ready
docker logs -f subnet-mysql
# Press Ctrl+C when you see "ready for connections"
```

### 3. Run Migration
```bash
# Set environment variables (use values from your .env file)
export DB_HOST=localhost
export DB_NAME=subnets
export DB_USER=subnets_user
export DB_PASSWORD=your_password_from_env
export DB_PORT=3306

# If MySQL is in Docker, you may need to expose port 3306
# Edit docker-compose.yml and ensure MySQL has: ports: - "3306:3306"

# Run migration
php migrate_sqlite_to_mysql.php
```

### 4. Start Full Application
```bash
docker-compose up -d
```

## Troubleshooting

### MySQL Won't Start
```bash
# Check logs
docker logs subnet-mysql

# Remove volumes and restart (WARNING: destroys data)
docker-compose down -v
docker-compose up -d
```

### Cannot Connect to Database
```bash
# Check environment variables
docker exec subnet-calculator env | grep DB_

# Verify MySQL is healthy
docker ps

# Test connection manually
docker exec -it subnet-calculator php -r "try { \$db = new PDO('mysql:host=mysql;dbname=subnets', 'subnets_user', 'your_password'); echo 'OK\n'; } catch(Exception \$e) { echo \$e->getMessage(); }"
```

### Application Shows Errors
```bash
# Check PHP logs
docker exec subnet-calculator tail -f /var/log/apache2/error.log

# Check database tables exist
docker exec -it subnet-mysql mysql -u root -p -e "USE subnets; SHOW TABLES;"
```

### Port Already in Use
If port 8080 is already in use, edit `docker-compose.yml`:
```yaml
services:
  subnet-calculator:
    ports:
      - "8081:80"  # Change 8080 to 8081 or another free port
```

## Production Deployment

For production, use the production compose file:

```bash
# Configure production environment
cp .env.example .env
nano .env  # Set STRONG passwords

# Start production services
docker-compose -f docker-compose.prod.yml up -d

# Monitor
docker-compose -f docker-compose.prod.yml logs -f
```

## Security Checklist

- [ ] Changed all default passwords in `.env`
- [ ] `.env` file is NOT committed to git (check `.gitignore`)
- [ ] MySQL root password is strong (16+ characters)
- [ ] MySQL user password is strong (16+ characters)
- [ ] Network access to MySQL is restricted (not exposed publicly)
- [ ] Regular backups are scheduled
- [ ] HTTPS is configured (for production)

## Additional Resources

- **Full Documentation**: See `README.md`
- **Migration Guide**: See `MIGRATION.md`
- **Issues**: https://github.com/adolky/subnets/issues

## Support

For help or questions:
1. Check logs: `docker-compose logs`
2. Verify configuration: `cat .env`
3. Review `MIGRATION.md` for detailed information
4. Open an issue on GitHub

---

**Ready to go!** Your subnet calculator is now running with MySQL backend.
