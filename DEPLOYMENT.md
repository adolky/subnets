# 🚀 Guide de Déploiement Production - Subnet Calculator

> **Guide complet pour déployer Subnet Calculator en environnement de production**

---

## 📋 Table des Matières

1. [Prérequis Production](#-prérequis-production)
2. [Architecture de Déploiement](#-architecture-de-déploiement)
3. [Déploiement sur Serveur Unique](#-déploiement-sur-serveur-unique)
4. [Déploiement Haute Disponibilité](#-déploiement-haute-disponibilité)
5. [Déploiement Cloud](#-déploiement-cloud)
6. [Sécurité Production](#-sécurité-production)
7. [Monitoring et Alertes](#-monitoring-et-alertes)
8. [Checklist Pre-Production](#-checklist-pre-production)

---

## ✅ Prérequis Production

### Infrastructure Minimale

| Ressource | Minimum | Recommandé | Haute Charge |
|-----------|---------|------------|--------------|
| **CPU** | 2 vCPU | 4 vCPU | 8 vCPU |
| **RAM** | 2 GB | 4 GB | 8 GB |
| **Disque** | 20 GB | 50 GB | 100 GB |
| **Bande passante** | 100 Mbps | 1 Gbps | 10 Gbps |

### Système d'Exploitation

**Recommandé :**
- Ubuntu 22.04 LTS
- Debian 12 (Bookworm)
- RHEL 9 / Rocky Linux 9

### Logiciels Requis

```bash
# Docker & Docker Compose
Docker Engine 24.0+
Docker Compose 2.20+

# Sécurité
UFW / firewalld
Fail2ban
Let's Encrypt / Certbot

# Monitoring (optionnel mais recommandé)
Prometheus
Grafana
Node Exporter
```

---

## 🏗️ Architecture de Déploiement

### Architecture Simple (Production Standard)

```
                      ┌─────────────────┐
                      │   Load Balancer │
                      │   (Nginx/HAProxy)│
                      │   + SSL/TLS     │
                      └────────┬────────┘
                               │
                ┌──────────────┴──────────────┐
                │                             │
         ┌──────▼───────┐            ┌───────▼──────┐
         │  Docker Host │            │   Backup     │
         │              │            │   Server     │
         │ ┌──────────┐ │            │              │
         │ │  App     │ │            │  • Database  │
         │ │Container │ │            │    Backups   │
         │ └────┬─────┘ │            │  • Volume    │
         │      │       │            │    Snapshots │
         │ ┌────▼─────┐ │            │              │
         │ │  MySQL   │ │            └──────────────┘
         │ │Container │ │
         │ └──────────┘ │
         └──────────────┘
```

---

### Architecture Haute Disponibilité

```
                    ┌────────────────┐
                    │  DNS / CloudFlare
                    │  + DDoS Protection
                    └────────┬───────┘
                             │
                    ┌────────▼────────┐
                    │  Load Balancer  │
                    │  (HAProxy)      │
                    │  + Keepalived   │
                    └─────┬──────┬────┘
                          │      │
              ┌───────────┘      └───────────┐
              │                              │
    ┌─────────▼─────────┐        ┌──────────▼─────────┐
    │   App Node 1      │        │   App Node 2       │
    │ ┌───────────────┐ │        │ ┌────────────────┐ │
    │ │ Subnet Calc   │ │        │ │ Subnet Calc    │ │
    │ └───────┬───────┘ │        │ └────────┬───────┘ │
    └─────────┼─────────┘        └──────────┼─────────┘
              │                              │
              └──────────┬───────────────────┘
                         │
              ┌──────────▼──────────┐
              │  MySQL Cluster      │
              │  (Master-Replica)   │
              │                     │
              │  Master ──► Replica │
              └─────────────────────┘
                         │
              ┌──────────▼──────────┐
              │  Shared Storage     │
              │  (NFS / GlusterFS)  │
              └─────────────────────┘
```

---

## 🖥️ Déploiement sur Serveur Unique

### Étape 1 : Préparation du Serveur

```bash
# Mise à jour système
sudo apt update && sudo apt upgrade -y

# Installation Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# Installation Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Vérification
docker --version
docker-compose --version

# Reboot pour appliquer les changements de groupe
sudo reboot
```

---

### Étape 2 : Configuration Firewall

```bash
# UFW (Ubuntu/Debian)
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow 22/tcp      # SSH
sudo ufw allow 80/tcp      # HTTP
sudo ufw allow 443/tcp     # HTTPS
sudo ufw enable

# Firewalld (RHEL/CentOS)
sudo firewall-cmd --permanent --add-service=ssh
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

---

### Étape 3 : Installation de l'Application

```bash
# Créer le répertoire de production
sudo mkdir -p /opt/subnet-calculator
sudo chown $USER:$USER /opt/subnet-calculator
cd /opt/subnet-calculator

# Cloner le repository
git clone https://github.com/adolky/subnets.git .

# Créer les dossiers nécessaires
mkdir -p backups logs data

# Configuration
cp .env.example .env
nano .env
```

**Configuration Production dans `.env` :**

```env
# Database Configuration
DB_HOST=mysql
DB_NAME=subnets
DB_USER=subnets_user
DB_PASSWORD=Pr0d!SecureP@ssw0rd#2025_CHANGEME
DB_PORT=3306

# MySQL Root Password
MYSQL_ROOT_PASSWORD=R00t!SecureP@ssw0rd#2025_CHANGEME

# Production Settings (ajouter ces lignes)
ENVIRONMENT=production
DEBUG_MODE=false
SESSION_LIFETIME=3600
```

---

### Étape 4 : Configuration Docker Production

**Créer `docker-compose.prod.yml` :**

```yaml
version: '3.8'

services:
  subnet-calculator:
    build: .
    container_name: subnet-calculator-prod
    restart: always
    ports:
      - "127.0.0.1:8080:80"  # Bind sur localhost uniquement
    environment:
      - DB_HOST=mysql
      - DB_NAME=${DB_NAME}
      - DB_USER=${DB_USER}
      - DB_PASSWORD=${DB_PASSWORD}
      - DB_PORT=${DB_PORT}
    volumes:
      - ./logs:/var/log/apache2
    depends_on:
      mysql:
        condition: service_healthy
    networks:
      - subnet-net
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost/"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s

  mysql:
    image: mysql:8.0
    container_name: mysql-prod
    restart: always
    environment:
      - MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD}
      - MYSQL_DATABASE=${DB_NAME}
      - MYSQL_USER=${DB_USER}
      - MYSQL_PASSWORD=${DB_PASSWORD}
    volumes:
      - mysql_data:/var/lib/mysql
      - ./backups:/backups
    networks:
      - subnet-net
    command: 
      - --default-authentication-plugin=mysql_native_password
      - --character-set-server=utf8mb4
      - --collation-server=utf8mb4_unicode_ci
      - --max_connections=200
      - --innodb_buffer_pool_size=512M
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost", "-u", "root", "-p${MYSQL_ROOT_PASSWORD}"]
      interval: 10s
      timeout: 5s
      retries: 5

volumes:
  mysql_data:
    driver: local

networks:
  subnet-net:
    driver: bridge
```

---

### Étape 5 : Nginx Reverse Proxy avec SSL

**Installation Nginx :**

```bash
sudo apt install nginx certbot python3-certbot-nginx -y
```

**Configuration Nginx `/etc/nginx/sites-available/subnet-calculator` :**

```nginx
# HTTP - Redirection vers HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name subnet-calculator.example.com;

    # Let's Encrypt Challenge
    location /.well-known/acme-challenge/ {
        root /var/www/html;
    }

    # Redirection HTTPS
    location / {
        return 301 https://$server_name$request_uri;
    }
}

# HTTPS
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name subnet-calculator.example.com;

    # SSL Certificates (généré par Certbot)
    ssl_certificate /etc/letsencrypt/live/subnet-calculator.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/subnet-calculator.example.com/privkey.pem;
    
    # SSL Configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;

    # Security Headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;

    # Logs
    access_log /var/log/nginx/subnet-calculator-access.log;
    error_log /var/log/nginx/subnet-calculator-error.log;

    # Reverse Proxy vers Docker
    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        # Timeouts
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
        
        # Buffer
        proxy_buffering on;
        proxy_buffer_size 4k;
        proxy_buffers 8 4k;
    }

    # Rate Limiting
    limit_req_zone $binary_remote_addr zone=subnet_limit:10m rate=10r/s;
    limit_req zone=subnet_limit burst=20 nodelay;
}
```

**Activer le site :**

```bash
# Créer le lien symbolique
sudo ln -s /etc/nginx/sites-available/subnet-calculator /etc/nginx/sites-enabled/

# Tester la configuration
sudo nginx -t

# Obtenir le certificat SSL
sudo certbot --nginx -d subnet-calculator.example.com

# Renouvellement automatique
sudo certbot renew --dry-run

# Redémarrer Nginx
sudo systemctl restart nginx
```

---

### Étape 6 : Démarrage Production

```bash
# Démarrer les services
cd /opt/subnet-calculator
docker-compose -f docker-compose.prod.yml up -d

# Vérifier les logs
docker-compose -f docker-compose.prod.yml logs -f

# Initialiser la base de données
docker-compose -f docker-compose.prod.yml exec subnet-calculator php db_init.php

# Créer l'utilisateur admin
docker-compose -f docker-compose.prod.yml exec subnet-calculator php add_admin_user.php

# Tester
curl -I https://subnet-calculator.example.com
```

---

## 🌐 Déploiement Haute Disponibilité

### HAProxy Configuration

**`/etc/haproxy/haproxy.cfg` :**

```haproxy
global
    log /dev/log local0
    chroot /var/lib/haproxy
    stats socket /run/haproxy/admin.sock mode 660 level admin
    stats timeout 30s
    user haproxy
    group haproxy
    daemon

defaults
    log     global
    mode    http
    option  httplog
    option  dontlognull
    timeout connect 5000
    timeout client  50000
    timeout server  50000

# Stats
listen stats
    bind *:8404
    stats enable
    stats uri /stats
    stats refresh 30s
    stats admin if TRUE

# Frontend HTTPS
frontend https_front
    bind *:443 ssl crt /etc/ssl/certs/subnet-calculator.pem
    mode http
    
    # Security Headers
    http-response set-header Strict-Transport-Security "max-age=31536000"
    http-response set-header X-Frame-Options "SAMEORIGIN"
    http-response set-header X-Content-Type-Options "nosniff"
    
    # ACLs
    acl is_subnet_calc hdr(host) -i subnet-calculator.example.com
    
    use_backend subnet_calc_backend if is_subnet_calc

# Backend - App Nodes
backend subnet_calc_backend
    mode http
    balance roundrobin
    option httpchk GET /
    
    # Session Stickiness
    cookie SERVERID insert indirect nocache
    
    # App Nodes
    server node1 10.0.1.10:8080 check cookie node1
    server node2 10.0.1.11:8080 check cookie node2
    server node3 10.0.1.12:8080 check cookie node3 backup
```

---

## ☁️ Déploiement Cloud

### Déploiement sur AWS

#### Infrastructure as Code (Terraform)

**`main.tf` :**

```hcl
provider "aws" {
  region = "eu-west-1"
}

# VPC
resource "aws_vpc" "subnet_calc_vpc" {
  cidr_block = "10.0.0.0/16"
  tags = {
    Name = "subnet-calculator-vpc"
  }
}

# Subnets
resource "aws_subnet" "public_subnet_1" {
  vpc_id            = aws_vpc.subnet_calc_vpc.id
  cidr_block        = "10.0.1.0/24"
  availability_zone = "eu-west-1a"
  tags = {
    Name = "subnet-calc-public-1"
  }
}

resource "aws_subnet" "public_subnet_2" {
  vpc_id            = aws_vpc.subnet_calc_vpc.id
  cidr_block        = "10.0.2.0/24"
  availability_zone = "eu-west-1b"
  tags = {
    Name = "subnet-calc-public-2"
  }
}

# EC2 Instance
resource "aws_instance" "app_server" {
  ami           = "ami-0c55b159cbfafe1f0"  # Ubuntu 22.04 LTS
  instance_type = "t3.medium"
  subnet_id     = aws_subnet.public_subnet_1.id
  
  vpc_security_group_ids = [aws_security_group.app_sg.id]
  
  user_data = file("install.sh")
  
  tags = {
    Name = "subnet-calculator-app"
  }
}

# RDS MySQL
resource "aws_db_instance" "mysql" {
  identifier           = "subnet-calc-db"
  engine              = "mysql"
  engine_version      = "8.0"
  instance_class      = "db.t3.micro"
  allocated_storage   = 20
  storage_type        = "gp2"
  
  db_name  = "subnets"
  username = "admin"
  password = var.db_password
  
  vpc_security_group_ids = [aws_security_group.db_sg.id]
  db_subnet_group_name   = aws_db_subnet_group.db_subnet_group.name
  
  backup_retention_period = 7
  backup_window          = "03:00-04:00"
  maintenance_window     = "sun:04:00-sun:05:00"
  
  skip_final_snapshot = false
  final_snapshot_identifier = "subnet-calc-final-snapshot"
  
  tags = {
    Name = "subnet-calculator-mysql"
  }
}

# Security Groups
resource "aws_security_group" "app_sg" {
  name        = "subnet-calc-app-sg"
  description = "Security group for Subnet Calculator app"
  vpc_id      = aws_vpc.subnet_calc_vpc.id
  
  ingress {
    from_port   = 80
    to_port     = 80
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }
  
  ingress {
    from_port   = 443
    to_port     = 443
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }
  
  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
}

# Load Balancer
resource "aws_lb" "app_lb" {
  name               = "subnet-calc-lb"
  internal           = false
  load_balancer_type = "application"
  security_groups    = [aws_security_group.app_sg.id]
  subnets            = [aws_subnet.public_subnet_1.id, aws_subnet.public_subnet_2.id]
  
  tags = {
    Name = "subnet-calculator-lb"
  }
}
```

**Déployer :**

```bash
terraform init
terraform plan
terraform apply
```

---

### Déploiement sur Azure

**Azure Container Instances :**

```bash
# Créer Resource Group
az group create --name subnet-calculator-rg --location westeurope

# Créer MySQL Database
az mysql server create \
  --resource-group subnet-calculator-rg \
  --name subnet-calc-mysql \
  --location westeurope \
  --admin-user adminuser \
  --admin-password SecureP@ssw0rd123! \
  --sku-name B_Gen5_1

# Créer Container Instance
az container create \
  --resource-group subnet-calculator-rg \
  --name subnet-calculator-app \
  --image yourdockerhub/subnet-calculator:latest \
  --dns-name-label subnet-calc \
  --ports 80 443 \
  --environment-variables \
    DB_HOST=subnet-calc-mysql.mysql.database.azure.com \
    DB_NAME=subnets \
    DB_USER=adminuser@subnet-calc-mysql \
    DB_PASSWORD=SecureP@ssw0rd123!
```

---

### Déploiement sur Google Cloud (GKE)

**Kubernetes Deployment :**

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: subnet-calculator
spec:
  replicas: 3
  selector:
    matchLabels:
      app: subnet-calculator
  template:
    metadata:
      labels:
        app: subnet-calculator
    spec:
      containers:
      - name: app
        image: gcr.io/YOUR_PROJECT/subnet-calculator:latest
        ports:
        - containerPort: 80
        env:
        - name: DB_HOST
          value: "mysql-service"
        - name: DB_NAME
          valueFrom:
            secretKeyRef:
              name: db-credentials
              key: database
        - name: DB_USER
          valueFrom:
            secretKeyRef:
              name: db-credentials
              key: username
        - name: DB_PASSWORD
          valueFrom:
            secretKeyRef:
              name: db-credentials
              key: password
---
apiVersion: v1
kind: Service
metadata:
  name: subnet-calculator-service
spec:
  type: LoadBalancer
  ports:
  - port: 80
    targetPort: 80
  selector:
    app: subnet-calculator
```

---

## 🔐 Sécurité Production

### Fail2ban Protection

```bash
# Installation
sudo apt install fail2ban -y

# Configuration
sudo nano /etc/fail2ban/jail.local
```

```ini
[DEFAULT]
bantime  = 3600
findtime  = 600
maxretry = 3

[sshd]
enabled = true

[nginx-http-auth]
enabled = true
port    = http,https
logpath = /var/log/nginx/subnet-calculator-error.log

[nginx-limit-req]
enabled = true
port    = http,https
logpath = /var/log/nginx/subnet-calculator-error.log
maxretry = 10
```

```bash
# Redémarrer
sudo systemctl restart fail2ban

# Vérifier
sudo fail2ban-client status
```

---

### WAF (Web Application Firewall)

**ModSecurity avec Nginx :**

```bash
# Installation
sudo apt install libnginx-mod-security -y

# Configuration
sudo cp /usr/share/modsecurity-crs/crs-setup.conf.example /etc/modsecurity/crs-setup.conf
sudo nano /etc/nginx/modsecurity/main.conf
```

---

## 📊 Monitoring et Alertes

### Prometheus + Grafana

**`docker-compose.monitoring.yml` :**

```yaml
version: '3.8'

services:
  prometheus:
    image: prom/prometheus:latest
    container_name: prometheus
    volumes:
      - ./monitoring/prometheus.yml:/etc/prometheus/prometheus.yml
      - prometheus_data:/prometheus
    command:
      - '--config.file=/etc/prometheus/prometheus.yml'
    ports:
      - "9090:9090"
    networks:
      - monitoring

  grafana:
    image: grafana/grafana:latest
    container_name: grafana
    environment:
      - GF_SECURITY_ADMIN_PASSWORD=SecureGrafanaPass123!
      - GF_INSTALL_PLUGINS=grafana-piechart-panel
    volumes:
      - grafana_data:/var/lib/grafana
      - ./monitoring/grafana-dashboards:/etc/grafana/provisioning/dashboards
    ports:
      - "3000:3000"
    networks:
      - monitoring

  node-exporter:
    image: prom/node-exporter:latest
    container_name: node-exporter
    ports:
      - "9100:9100"
    networks:
      - monitoring

  mysql-exporter:
    image: prom/mysqld-exporter:latest
    container_name: mysql-exporter
    environment:
      - DATA_SOURCE_NAME=subnets_user:${DB_PASSWORD}@(mysql:3306)/subnets
    ports:
      - "9104:9104"
    networks:
      - monitoring
      - subnet-net

volumes:
  prometheus_data:
  grafana_data:

networks:
  monitoring:
  subnet-net:
    external: true
```

---

## ✅ Checklist Pre-Production

### Sécurité
- [ ] SSL/TLS configuré (Let's Encrypt)
- [ ] Firewall activé (UFW/firewalld)
- [ ] Fail2ban installé et configuré
- [ ] Mots de passe forts (16+ caractères)
- [ ] SSH avec clés uniquement (désactiver mot de passe)
- [ ] Security headers configurés (Nginx)
- [ ] Rate limiting activé

### Performance
- [ ] MySQL optimisé (innodb_buffer_pool_size)
- [ ] Apache/Nginx configuré pour production
- [ ] Compression activée (gzip/brotli)
- [ ] Cache navigateur configuré
- [ ] CDN configuré (optionnel)

### Sauvegarde
- [ ] Backup automatique base de données (cron quotidien)
- [ ] Backup volumes Docker
- [ ] Backup configuration (.env, docker-compose)
- [ ] Test de restauration effectué
- [ ] Rétention 30 jours minimum

### Monitoring
- [ ] Prometheus + Grafana installés
- [ ] Alertes configurées (email/Slack)
- [ ] Dashboard Grafana configuré
- [ ] Logs centralisés (optionnel: ELK Stack)

### Documentation
- [ ] Runbook créé pour l'équipe
- [ ] Procédures d'escalade définies
- [ ] Contacts d'urgence à jour
- [ ] Documentation réseau à jour

### Tests
- [ ] Test de charge effectué (Apache Bench)
- [ ] Test de failover (si HA)
- [ ] Test de restauration backup
- [ ] Test des alertes

---

## 📞 Support Déploiement

**Besoin d'aide ?**
- 📖 Documentation : [docs/](docs/)
- 🐛 Issues : [GitHub](https://github.com/adolky/subnets/issues)
- 💬 Discussions : [GitHub Discussions](https://github.com/adolky/subnets/discussions)

---

**✅ Votre application est maintenant prête pour la production !**

**Bonne chance avec votre déploiement ! 🚀**
