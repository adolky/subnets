# 🌐 Subnet Calculator

A powerful, web-based subnet calculator with visual representation, intelligent IP search, and database management.

[![Docker](https://img.shields.io/badge/Docker-Ready-blue?logo=docker)](https://docker.com/)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php)](https://php.net/)

## ✨ Features

- **Visual Subnet Division**: Interactive subnet splitting and joining with color-coded interface
- **VLAN Management**: Assign VLAN IDs (1-4094) and descriptions to subnets
- **Database Storage**: Save and load configurations with MySQL
- **IP Search**: Find which subnet contains any IP address across all saved configurations
- **User Authentication**: Multi-user support with role-based access control

## 🚀 Quick Start

### Using Docker (Recommended)

```bash
# Clone repository
git clone https://github.com/adolky/subnets.git
cd subnets

# Configure environment
cp .env.example .env
nano .env  # Set your passwords

# Start application
docker compose up -d

# Access at http://localhost:8080
```

### Manual Installation

**Requirements:** PHP 8.2+, MySQL 8.0+, Apache/Nginx

```bash
# Create database
mysql -u root -p
CREATE DATABASE subnets;
CREATE USER 'subnets_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON subnets.* TO 'subnets_user'@'localhost';
FLUSH PRIVILEGES;

# Configure environment
export DB_HOST=localhost
export DB_NAME=subnets
export DB_USER=subnets_user
export DB_PASSWORD=your_password

# Initialize database
php db_init.php

# Add admin user
php add_admin_user.php
```

## 🎮 Usage

1. **Enter network** (e.g., `192.168.0.0/16`)
2. **Click Update** to validate
3. **Divide/Join** subnets visually
4. **Add VLAN IDs** and descriptions
5. **Save to Database** for persistence
6. **Search IPs** to find their subnet

## 🔧 Configuration

Edit `.env` file:

```env
DB_HOST=mysql
DB_NAME=subnets
DB_USER=subnets_user
DB_PASSWORD=your_secure_password
DB_PORT=3306
MYSQL_ROOT_PASSWORD=your_root_password
```

## 🐛 Troubleshooting

```bash
# Check container status
docker compose ps

# View logs
docker compose logs subnet-calculator

# Restart services
docker compose restart
```

## 📁 Project Structure

```
subnets/
├── subnets.html       # Main application
├── api.php            # REST API
├── session_api.php    # Authentication API
├── db_init.php        # Database setup
├── add_admin_user.php # Admin user creation
├── index.php          # Entry point
├── img/               # Subnet mask images
└── docker-compose.yml # Docker configuration
```

## 📄 License

MIT License - see [LICENSE.md](LICENSE.md)

---

**Made with ❤️ for network administrators**
