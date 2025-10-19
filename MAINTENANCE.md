# 🔧 Guide de Maintenance - Subnet Calculator

> **Guide complet pour administrer et maintenir Subnet Calculator en production**

---

## 📋 Table des Matières

1. [Administration Quotidienne](#-administration-quotidienne)
2. [Surveillance et Monitoring](#-surveillance-et-monitoring)
3. [Sauvegardes](#-sauvegardes)
4. [Mise à Jour](#-mise-à-jour)
5. [Dépannage](#-dépannage)
6. [Performance](#-performance)
7. [Sécurité](#-sécurité)

---

## 📅 Administration Quotidienne

### Vérification Quotidienne (5 minutes)

```bash
#!/bin/bash
# daily_check.sh - Script de vérification quotidienne

echo "🔍 Vérification quotidienne - $(date)"
echo "==========================================="

# 1. Statut des conteneurs
echo -e "\n📦 Statut Docker:"
docker compose ps

# 2. Logs des dernières 24h
echo -e "\n📋 Erreurs récentes:"
docker compose logs --since 24h | grep -i error | tail -20

# 3. Espace disque
echo -e "\n💾 Espace disque:"
df -h | grep -E '^/dev'

# 4. Taille base de données
echo -e "\n🗄️  Taille base de données:"
docker compose exec mysql mysql -u root -p${MYSQL_ROOT_PASSWORD} -e \
  "SELECT table_schema AS 'Database', 
   ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)' 
   FROM information_schema.tables 
   WHERE table_schema = 'subnets' 
   GROUP BY table_schema;"

# 5. Nombre d'utilisateurs
echo -e "\n👥 Utilisateurs actifs:"
docker compose exec mysql mysql -u subnets_user -p${DB_PASSWORD} subnets -e \
  "SELECT role, COUNT(*) as count FROM users GROUP BY role;"

# 6. Nombre de configurations
echo -e "\n📊 Configurations sauvegardées:"
docker compose exec mysql mysql -u subnets_user -p${DB_PASSWORD} subnets -e \
  "SELECT COUNT(*) as total FROM configs;"

echo -e "\n✅ Vérification terminée\n"
```

**Automatiser avec cron :**

```bash
# Éditer crontab
crontab -e

# Ajouter cette ligne (exécution quotidienne à 8h)
0 8 * * * /opt/subnet-calculator/daily_check.sh >> /var/log/subnet-calculator-check.log 2>&1
```

---

## 📊 Surveillance et Monitoring

### Logs Docker

```bash
# Voir tous les logs
docker compose logs -f

# Logs d'un service spécifique
docker compose logs -f subnet-calculator
docker compose logs -f mysql

# Dernières 100 lignes
docker compose logs --tail=100

# Logs depuis une heure
docker compose logs --since 1h

# Filtrer les erreurs
docker compose logs | grep -i error

# Exporter les logs
docker compose logs --since 24h > logs_$(date +%Y%m%d).txt
```

### Logs Apache (dans le conteneur)

```bash
# Accéder au conteneur
docker compose exec subnet-calculator bash

# Voir les logs d'accès
tail -f /var/log/apache2/access.log

# Voir les logs d'erreur
tail -f /var/log/apache2/error.log

# Analyser les IPs qui se connectent
awk '{print $1}' /var/log/apache2/access.log | sort | uniq -c | sort -rn | head -10
```

### Logs MySQL

```bash
# Accéder au conteneur MySQL
docker compose exec mysql bash

# Logs d'erreur MySQL
tail -f /var/log/mysql/error.log

# Requêtes lentes
tail -f /var/log/mysql/slow-query.log
```

---

### Monitoring avec Prometheus + Grafana (Optionnel)

**docker-compose.monitoring.yml :**

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
      - '--storage.tsdb.path=/prometheus'
    ports:
      - "9090:9090"
    networks:
      - subnet-net

  grafana:
    image: grafana/grafana:latest
    container_name: grafana
    ports:
      - "3000:3000"
    environment:
      - GF_SECURITY_ADMIN_PASSWORD=admin
    volumes:
      - grafana_data:/var/lib/grafana
    networks:
      - subnet-net

  cadvisor:
    image: gcr.io/cadvisor/cadvisor:latest
    container_name: cadvisor
    volumes:
      - /:/rootfs:ro
      - /var/run:/var/run:ro
      - /sys:/sys:ro
      - /var/lib/docker/:/var/lib/docker:ro
    ports:
      - "8081:8080"
    networks:
      - subnet-net

volumes:
  prometheus_data:
  grafana_data:

networks:
  subnet-net:
    external: true
```

**Démarrer monitoring :**

```bash
docker compose -f docker-compose.monitoring.yml up -d

# Accéder à Grafana : http://localhost:3000
# Login: admin / admin
```

---

## 💾 Sauvegardes

### Sauvegarde Automatique de la Base de Données

**Script de backup quotidien :**

```bash
#!/bin/bash
# backup_db.sh - Sauvegarde automatique de la base de données

# Configuration
BACKUP_DIR="/opt/subnet-calculator/backups"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

# Créer le dossier de backup
mkdir -p $BACKUP_DIR

# Backup MySQL
echo "🔄 Démarrage backup: $DATE"
docker compose exec -T mysql mysqldump \
  -u root \
  -p${MYSQL_ROOT_PASSWORD} \
  --databases subnets \
  --single-transaction \
  --quick \
  --lock-tables=false \
  > "$BACKUP_DIR/subnets_$DATE.sql"

# Compresser
gzip "$BACKUP_DIR/subnets_$DATE.sql"

# Vérifier
if [ -f "$BACKUP_DIR/subnets_$DATE.sql.gz" ]; then
  SIZE=$(du -h "$BACKUP_DIR/subnets_$DATE.sql.gz" | cut -f1)
  echo "✅ Backup réussi: subnets_$DATE.sql.gz ($SIZE)"
else
  echo "❌ Erreur backup!"
  exit 1
fi

# Nettoyer les anciens backups
find $BACKUP_DIR -name "subnets_*.sql.gz" -mtime +$RETENTION_DAYS -delete
echo "🧹 Anciens backups supprimés (>$RETENTION_DAYS jours)"

# Backup CSV export (optionnel)
echo "📥 Export CSV..."
# TODO: Appeler l'API pour déclencher export CSV automatique

echo "✅ Sauvegarde terminée: $(date)"
```

**Automatiser avec cron :**

```bash
# Backup quotidien à 2h du matin
0 2 * * * /opt/subnet-calculator/backup_db.sh >> /var/log/subnet-calculator-backup.log 2>&1

# Backup hebdomadaire complet le dimanche à 3h
0 3 * * 0 /opt/subnet-calculator/backup_full.sh >> /var/log/subnet-calculator-backup.log 2>&1
```

---

### Restauration d'un Backup

```bash
# 1. Lister les backups disponibles
ls -lh /opt/subnet-calculator/backups/

# 2. Décompresser le backup
gunzip /opt/subnet-calculator/backups/subnets_20251019_020000.sql.gz

# 3. Restaurer
docker compose exec -T mysql mysql \
  -u root \
  -p${MYSQL_ROOT_PASSWORD} \
  < /opt/subnet-calculator/backups/subnets_20251019_020000.sql

# 4. Vérifier
docker compose exec mysql mysql -u root -p${MYSQL_ROOT_PASSWORD} subnets -e "SELECT COUNT(*) FROM configs;"

echo "✅ Restauration terminée"
```

---

### Backup Complet (Base + Application)

```bash
#!/bin/bash
# backup_full.sh - Backup complet

BACKUP_DIR="/opt/subnet-calculator/backups"
DATE=$(date +%Y%m%d_%H%M%S)
FULL_BACKUP="$BACKUP_DIR/full_backup_$DATE"

mkdir -p $FULL_BACKUP

# 1. Backup base de données
docker compose exec -T mysql mysqldump \
  -u root -p${MYSQL_ROOT_PASSWORD} \
  --all-databases \
  --single-transaction \
  > "$FULL_BACKUP/database.sql"

# 2. Backup configurations
cp -r /opt/subnet-calculator/.env $FULL_BACKUP/
cp -r /opt/subnet-calculator/docker-compose.yml $FULL_BACKUP/

# 3. Backup volumes Docker
docker run --rm \
  -v subnet-calculator_mysql_data:/data \
  -v $FULL_BACKUP:/backup \
  alpine tar czf /backup/mysql_volume.tar.gz -C /data .

# 4. Compresser tout
cd $BACKUP_DIR
tar czf "full_backup_$DATE.tar.gz" "full_backup_$DATE/"
rm -rf "full_backup_$DATE"

echo "✅ Backup complet créé: full_backup_$DATE.tar.gz"
```

---

## 🔄 Mise à Jour

### Mise à Jour de l'Application

```bash
# 1. Backup avant mise à jour
./backup_full.sh

# 2. Télécharger la nouvelle version
cd /opt/subnet-calculator
git fetch --all
git tag  # Voir les versions disponibles

# 3. Mettre à jour vers une version spécifique
git checkout v1.5.0  # Remplacer par la version souhaitée

# 4. Reconstruire les images Docker
docker compose build --no-cache

# 5. Redémarrer les services
docker compose down
docker compose up -d

# 6. Vérifier les logs
docker compose logs -f

# 7. Tester l'application
curl -I http://localhost:8080
```

---

### Mise à Jour de la Base de Données

Si une mise à jour nécessite des changements de schéma :

```bash
# 1. Backup
./backup_db.sh

# 2. Appliquer les migrations
docker compose exec mysql mysql -u root -p${MYSQL_ROOT_PASSWORD} subnets < migrations/v1.5.0.sql

# 3. Vérifier
docker compose exec mysql mysql -u root -p${MYSQL_ROOT_PASSWORD} subnets -e "SHOW TABLES;"
```

---

### Rollback en Cas de Problème

```bash
# 1. Arrêter les services
docker compose down

# 2. Revenir à la version précédente
git checkout v1.4.0

# 3. Restaurer le backup
gunzip /opt/subnet-calculator/backups/subnets_YYYYMMDD_HHMMSS.sql.gz
docker compose up -d
docker compose exec -T mysql mysql -u root -p${MYSQL_ROOT_PASSWORD} < /opt/subnet-calculator/backups/subnets_YYYYMMDD_HHMMSS.sql

# 4. Redémarrer
docker compose restart

# 5. Vérifier
curl -I http://localhost:8080
```

---

## 🔍 Dépannage

### Problème : Application Lente

**Diagnostic :**

```bash
# 1. Vérifier CPU/RAM
docker stats

# 2. Vérifier les processus MySQL
docker compose exec mysql mysql -u root -p${MYSQL_ROOT_PASSWORD} -e "SHOW PROCESSLIST;"

# 3. Analyser les requêtes lentes
docker compose exec mysql mysql -u root -p${MYSQL_ROOT_PASSWORD} -e "SELECT * FROM mysql.slow_log ORDER BY query_time DESC LIMIT 10;"

# 4. Vérifier l'espace disque
df -h
```

**Solutions :**
- Augmenter les ressources Docker
- Optimiser les index MySQL
- Nettoyer les anciennes données

---

### Problème : Erreur de Connexion Base de Données

```bash
# 1. Vérifier que MySQL est en cours d'exécution
docker compose ps mysql

# 2. Vérifier les credentials
docker compose exec mysql mysql -u subnets_user -p${DB_PASSWORD} subnets -e "SELECT 1;"

# 3. Recréer l'utilisateur
docker compose exec mysql mysql -u root -p${MYSQL_ROOT_PASSWORD} << EOF
DROP USER 'subnets_user'@'%';
CREATE USER 'subnets_user'@'%' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON subnets.* TO 'subnets_user'@'%';
FLUSH PRIVILEGES;
EOF

# 4. Redémarrer
docker compose restart
```

---

### Problème : Conteneur ne Démarre Pas

```bash
# 1. Voir les logs détaillés
docker compose logs subnet-calculator

# 2. Vérifier la configuration
docker compose config

# 3. Reconstruire l'image
docker compose build --no-cache subnet-calculator

# 4. Nettoyer Docker
docker system prune -a
docker volume prune

# 5. Redémarrer
docker compose up -d
```

---

## ⚡ Performance

### Optimisation MySQL

**Fichier `mysql.cnf` :**

```ini
[mysqld]
# InnoDB Settings
innodb_buffer_pool_size = 512M
innodb_log_file_size = 128M
innodb_flush_method = O_DIRECT

# Query Cache (deprecated in MySQL 8.0+)
# query_cache_type = 1
# query_cache_size = 64M

# Connections
max_connections = 200
thread_cache_size = 50

# Slow Query Log
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow-query.log
long_query_time = 2
```

**Ajouter dans docker-compose.yml :**

```yaml
services:
  mysql:
    volumes:
      - ./mysql.cnf:/etc/mysql/conf.d/custom.cnf
```

---

### Optimisation Apache

**Fichier `apache.conf` :**

```apache
# Performance
KeepAlive On
MaxKeepAliveRequests 100
KeepAliveTimeout 5

# MPM Prefork
<IfModule mpm_prefork_module>
    StartServers          5
    MinSpareServers       5
    MaxSpareServers      10
    MaxRequestWorkers   150
    MaxConnectionsPerChild 1000
</IfModule>

# Compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>

# Cache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType text/css "access plus 1 week"
    ExpiresByType application/javascript "access plus 1 week"
</IfModule>
```

---

### Nettoyage Régulier

```bash
#!/bin/bash
# cleanup.sh - Nettoyage mensuel

echo "🧹 Nettoyage mensuel..."

# 1. Supprimer anciennes sessions
docker compose exec mysql mysql -u root -p${MYSQL_ROOT_PASSWORD} subnets << EOF
DELETE FROM sessions WHERE updated_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
EOF

# 2. Optimiser les tables
docker compose exec mysql mysql -u root -p${MYSQL_ROOT_PASSWORD} subnets -e "OPTIMIZE TABLE configs, users, sessions;"

# 3. Analyser les tables
docker compose exec mysql mysql -u root -p${MYSQL_ROOT_PASSWORD} subnets -e "ANALYZE TABLE configs, users, sessions;"

# 4. Nettoyer Docker
docker system prune -f
docker volume prune -f

# 5. Nettoyer anciens logs
find /var/log/subnet-calculator* -name "*.log" -mtime +60 -delete

echo "✅ Nettoyage terminé"
```

---

## 🔐 Sécurité

### Audit de Sécurité Mensuel

```bash
#!/bin/bash
# security_audit.sh

echo "🔐 Audit de sécurité - $(date)"

# 1. Vérifier les utilisateurs sans mot de passe fort
docker compose exec mysql mysql -u root -p${MYSQL_ROOT_PASSWORD} subnets -e \
  "SELECT username FROM users WHERE LENGTH(password) < 60;"

# 2. Lister les utilisateurs admin
docker compose exec mysql mysql -u root -p${MYSQL_ROOT_PASSWORD} subnets -e \
  "SELECT username, role, created_at FROM users WHERE role = 'admin';"

# 3. Vérifier les mises à jour disponibles
cd /opt/subnet-calculator
git fetch --all
git log --oneline HEAD..origin/master

# 4. Scanner les vulnérabilités Docker
docker scan subnet-calculator:latest

# 5. Vérifier les permissions fichiers
find /opt/subnet-calculator -type f -perm /o+w -ls

echo "✅ Audit terminé"
```

---

### Renforcement de la Sécurité

**1. Configurer HTTPS (avec Let's Encrypt) :**

```bash
# Installer Certbot
sudo apt install certbot python3-certbot-apache -y

# Obtenir certificat
sudo certbot --apache -d subnet-calculator.example.com

# Renouvellement automatique
sudo certbot renew --dry-run
```

**2. Fail2ban pour protection brute-force :**

```bash
# Installer Fail2ban
sudo apt install fail2ban -y

# Configuration
sudo nano /etc/fail2ban/jail.local
```

```ini
[apache-auth]
enabled = true
port    = http,https
logpath = /var/log/apache2/*error.log
maxretry = 3
bantime  = 3600
```

**3. Firewall UFW :**

```bash
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS
sudo ufw enable
```

---

## 📈 Statistiques d'Utilisation

```bash
#!/bin/bash
# stats.sh - Statistiques mensuelles

echo "📊 Statistiques d'utilisation - $(date)"

# Connexions par utilisateur
docker compose exec mysql mysql -u root -p${MYSQL_ROOT_PASSWORD} subnets -e \
  "SELECT users.username, COUNT(sessions.id) as connections 
   FROM sessions 
   JOIN users ON sessions.user_id = users.id 
   WHERE sessions.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY) 
   GROUP BY users.username 
   ORDER BY connections DESC;"

# Configurations créées par mois
docker compose exec mysql mysql -u root -p${MYSQL_ROOT_PASSWORD} subnets -e \
  "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as configs_created 
   FROM configs 
   GROUP BY month 
   ORDER BY month DESC 
   LIMIT 12;"

# Taille moyenne des réseaux
docker compose exec mysql mysql -u root -p${MYSQL_ROOT_PASSWORD} subnets -e \
  "SELECT network, site_name, LENGTH(division_data) as complexity 
   FROM configs 
   ORDER BY complexity DESC 
   LIMIT 10;"
```

---

## 📞 Support et Escalade

### Niveaux de Support

**Niveau 1 - Utilisateur :**
- Consulter [TUTORIAL.md](TUTORIAL.md)
- Vérifier la documentation utilisateur

**Niveau 2 - Admin Système :**
- Vérifier logs
- Redémarrer services
- Restaurer backups

**Niveau 3 - Développeur :**
- Analyser code
- Debug avancé
- Créer issue GitHub

---

**✅ Votre installation est maintenant sécurisée et optimisée pour la production !**

**Prochaine étape :** [DEPLOYMENT.md](DEPLOYMENT.md) pour les scénarios de déploiement avancés
