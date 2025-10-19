# 📦 Guide d'Installation - Subnet Calculator

> **Guide complet pour installer Subnet Calculator sur votre infrastructure**

---

## ⚡ Installation Rapide en Une Ligne

**La méthode la plus rapide pour installer Subnet Calculator :**

### Linux / macOS / WSL

```bash
curl -sSL https://raw.githubusercontent.com/adolky/subnets/master/install.sh | bash
```

### Windows PowerShell

```powershell
iwr -useb https://raw.githubusercontent.com/adolky/subnets/master/install.ps1 | iex
```

**✅ Installation complète en moins de 2 minutes !**

📖 **Pour plus de détails, voir la section [Installation Docker Automatisée](#-installation-automatisée-en-une-ligne-recommandée)**

---

## 📋 Table des Matières

1. [Prérequis](#-prérequis)
2. [Installation Docker (Recommandée)](#-installation-docker-recommandée)
3. [Installation Manuelle](#-installation-manuelle)
4. [Configuration](#-configuration)
5. [Vérification](#-vérification)
6. [Dépannage](#-dépannage)

---

## ✅ Prérequis

### Installation Docker

| Composant | Version Minimale | Recommandée |
|-----------|------------------|-------------|
| **Docker** | 20.10+ | Dernière version |
| **Docker Compose** | 2.0+ | Dernière version |
| **Système** | Linux, macOS, Windows WSL2 | Linux |
| **RAM** | 512 MB | 1 GB |
| **Disque** | 500 MB | 1 GB |

### Installation Manuelle

| Composant | Version Minimale | Recommandée |
|-----------|------------------|-------------|
| **PHP** | 8.2 | 8.3 |
| **MySQL** | 8.0 | 8.0.35+ |
| **Apache** | 2.4 | 2.4.57+ |
| **Extensions PHP** | mysqli, pdo_mysql | + opcache |

---

## 🐳 Installation Docker (Recommandée)

### ⚡ Installation Automatisée en Une Ligne (Recommandée)

**Cette méthode automatise entièrement l'installation : création de la base de données, configuration, déploiement et création de l'utilisateur admin.**

#### Linux / macOS / WSL :

```bash
curl -sSL https://raw.githubusercontent.com/adolky/subnets/master/install.sh | bash
```

**Ou si vous avez déjà cloné le repository :**

```bash
cd subnets && bash install.sh
```

#### Windows PowerShell :

```powershell
iwr -useb https://raw.githubusercontent.com/adolky/subnets/master/install.ps1 | iex
```

**Ou si vous avez déjà cloné le repository :**

```powershell
cd subnets; .\install.ps1
```

**Ce que fait le script automatiquement :**
- ✅ Vérifie les prérequis Docker
- ✅ Demande le choix entre Staging ou Production
- ✅ Génère des mots de passe sécurisés pour la base de données
- ✅ Démarre les conteneurs Docker
- ✅ Initialise la base de données
- ✅ Crée l'utilisateur administrateur (avec prompt interactif)
- ✅ Affiche les informations de connexion

**Avantages :**
- 🚀 Installation en moins de 2 minutes
- 🔒 Mots de passe générés automatiquement et sécurisés
- 🎯 Aucune configuration manuelle requise
- ✨ Interface interactive et guidée

---

### Méthode Manuelle : Installation Standard

Si vous préférez contrôler chaque étape manuellement :

```bash
# 1. Cloner le repository
git clone https://github.com/adolky/subnets.git
cd subnets

# 2. Copier le fichier de configuration
cp .env.example .env

# 3. Éditer la configuration
nano .env
```

**Modifier ces lignes dans `.env` :**

```env
# IMPORTANT : Changez ces mots de passe !
DB_PASSWORD=VotreMotDePasseSecurise123!
MYSQL_ROOT_PASSWORD=VotreMotDePasseRootSecurise123!
```

```bash
# 4. Démarrer les conteneurs (Staging)
docker compose up -d

# OU pour Production
docker compose -f docker-compose.prod.yml up -d

# 5. Vérifier les logs
docker compose logs -f

# 6. Créer un utilisateur administrateur
docker compose exec subnet-calculator php add_admin_user.php
```

Suivez les instructions à l'écran :

- Username: `admin`
- Password: `VotreMotDePasse!`
- Role: `admin`

```bash
# 7. Accéder à l'application
# Staging: http://localhost:8080
# Production: http://localhost
```

**✅ Installation terminée !**

---

### Méthode Alternative : Installation avec Port Personnalisé

Si le port 8080 est déjà utilisé :

```bash
# Éditer docker-compose.yml
nano docker-compose.yml
```

Modifier la ligne :
```yaml
ports:
  - "8080:80"  # Changer 8080 en 9090 par exemple
```

Puis :
```bash
docker compose up -d
# Accéder à http://localhost:9090
```

---

## 🔧 Installation Manuelle

### Étape 1 : Installation des Prérequis

#### Sur Ubuntu/Debian :

```bash
# Mettre à jour le système
sudo apt update && sudo apt upgrade -y

# Installer Apache
sudo apt install apache2 -y

# Installer PHP et extensions
sudo apt install php8.2 php8.2-cli php8.2-common php8.2-mysql \
                 php8.2-mbstring php8.2-curl php8.2-xml -y

# Installer MySQL
sudo apt install mysql-server -y

# Activer les modules Apache
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### Sur CentOS/RHEL :

```bash
# Installer les repositories
sudo dnf install epel-release -y
sudo dnf module reset php
sudo dnf module enable php:8.2 -y

# Installer les paquets
sudo dnf install httpd php php-mysqlnd php-mbstring php-xml -y
sudo dnf install mysql-server -y

# Démarrer les services
sudo systemctl start httpd
sudo systemctl start mysqld
sudo systemctl enable httpd
sudo systemctl enable mysqld
```

---

### Étape 2 : Configuration de la Base de Données

```bash
# Se connecter à MySQL
sudo mysql -u root -p
```

```sql
-- Créer la base de données
CREATE DATABASE subnets CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Créer l'utilisateur
CREATE USER 'subnets_user'@'localhost' IDENTIFIED BY 'VotreMotDePasseSecurise123!';

-- Donner les permissions
GRANT ALL PRIVILEGES ON subnets.* TO 'subnets_user'@'localhost';

-- Appliquer les changements
FLUSH PRIVILEGES;

-- Quitter
EXIT;
```

---

### Étape 3 : Installation de l'Application

```bash
# Aller dans le répertoire web
cd /var/www/html

# Cloner le repository
sudo git clone https://github.com/adolky/subnets.git subnet-calculator
cd subnet-calculator

# Définir les permissions
sudo chown -R www-data:www-data /var/www/html/subnet-calculator
sudo chmod -R 755 /var/www/html/subnet-calculator
```

---

### Étape 4 : Configuration de l'Application

```bash
# Créer le fichier de configuration
sudo nano /var/www/html/subnet-calculator/.env
```

**Contenu du fichier `.env` :**

```env
# Database Configuration
DB_HOST=localhost
DB_NAME=subnets
DB_USER=subnets_user
DB_PASSWORD=VotreMotDePasseSecurise123!
DB_PORT=3306
```

**Charger les variables d'environnement :**

```bash
# Exporter les variables pour le script d'initialisation
export $(cat /var/www/html/subnet-calculator/.env | xargs)

# Initialiser la base de données
cd /var/www/html/subnet-calculator
php db_init.php
```

---

### Étape 5 : Configuration Apache

```bash
# Créer le fichier VirtualHost
sudo nano /etc/apache2/sites-available/subnet-calculator.conf
```

**Contenu (Ubuntu/Debian) :**

```apache
<VirtualHost *:80>
    ServerName subnet-calculator.example.com
    DocumentRoot /var/www/html/subnet-calculator

    <Directory /var/www/html/subnet-calculator>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Logs
    ErrorLog ${APACHE_LOG_DIR}/subnet-calculator-error.log
    CustomLog ${APACHE_LOG_DIR}/subnet-calculator-access.log combined

    # Security Headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
</VirtualHost>
```

**Pour CentOS/RHEL, créer `/etc/httpd/conf.d/subnet-calculator.conf` :**

```apache
<VirtualHost *:80>
    ServerName subnet-calculator.example.com
    DocumentRoot /var/www/html/subnet-calculator

    <Directory /var/www/html/subnet-calculator>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog /var/log/httpd/subnet-calculator-error.log
    CustomLog /var/log/httpd/subnet-calculator-access.log combined
</VirtualHost>
```

**Activer le site et redémarrer Apache :**

```bash
# Ubuntu/Debian
sudo a2ensite subnet-calculator
sudo systemctl restart apache2

# CentOS/RHEL
sudo systemctl restart httpd
```

---

### Étape 6 : Créer un Utilisateur Admin

```bash
cd /var/www/html/subnet-calculator
php add_admin_user.php
```

Entrer les informations :
- Username: `admin`
- Password: `VotreMotDePasse!`
- Role: `admin`

---

### Étape 7 : Configuration du Firewall

```bash
# Ubuntu/Debian (UFW)
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw reload

# CentOS/RHEL (firewalld)
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

---

## ⚙️ Configuration

### Variables d'Environnement

| Variable | Description | Défaut | Exemple |
|----------|-------------|--------|---------|
| `DB_HOST` | Hôte MySQL | `mysql` | `localhost` |
| `DB_NAME` | Nom de la base | `subnets` | `subnets` |
| `DB_USER` | Utilisateur MySQL | `subnets_user` | `subnets_user` |
| `DB_PASSWORD` | Mot de passe MySQL | - | `SecurePass123!` |
| `DB_PORT` | Port MySQL | `3306` | `3306` |
| `MYSQL_ROOT_PASSWORD` | Mot de passe root (Docker) | - | `RootPass123!` |

### Fichier .env

**Développement :**
```env
DB_HOST=mysql
DB_NAME=subnets
DB_USER=subnets_user
DB_PASSWORD=dev_password_123
DB_PORT=3306
MYSQL_ROOT_PASSWORD=root_password_123
```

**Production :**
```env
DB_HOST=mysql
DB_NAME=subnets
DB_USER=subnets_user
DB_PASSWORD=Pr0d!SecureP@ssw0rd#2025
DB_PORT=3306
MYSQL_ROOT_PASSWORD=R00t!SecureP@ssw0rd#2025
```

⚠️ **Sécurité :** Utilisez des mots de passe forts (16+ caractères, majuscules, minuscules, chiffres, symboles)

---

## ✅ Vérification

### Vérification Docker

```bash
# Vérifier que les conteneurs sont en cours d'exécution
docker compose ps

# Devrait afficher :
# NAME                  STATUS
# subnet-calculator     Up
# mysql                 Up

# Vérifier les logs
docker compose logs subnet-calculator
docker compose logs mysql

# Tester la connexion à la base de données
docker compose exec mysql mysql -u subnets_user -p subnets -e "SHOW TABLES;"
```

### Vérification Manuelle

```bash
# Vérifier Apache
sudo systemctl status apache2   # Ubuntu/Debian
sudo systemctl status httpd     # CentOS/RHEL

# Vérifier MySQL
sudo systemctl status mysql     # Ubuntu/Debian
sudo systemctl status mysqld    # CentOS/RHEL

# Tester PHP
php -v

# Tester la connexion MySQL
mysql -u subnets_user -p subnets -e "SHOW TABLES;"
```

### Test de l'Application

1. **Ouvrir le navigateur** : `http://localhost:8080` (ou votre IP/domaine)
2. **Se connecter** avec les identifiants créés
3. **Créer une configuration** :
   - Network: `192.168.0.0/24`
   - Site Name: `Test Site`
   - Admin Number: `ADM-001`
   - Cliquer "Update"
   - Cliquer "Save to Database"
4. **Tester l'export CSV** :
   - Cliquer "📥 Export"
   - Choisir "🌍 Tous les sous-réseaux"
   - Vérifier le téléchargement du fichier

**✅ Si toutes les étapes fonctionnent, l'installation est réussie !**

---

## 🔍 Dépannage

### Problème : Conteneur n'arrive pas à démarrer

```bash
# Vérifier les logs détaillés
docker compose logs -f subnet-calculator

# Redémarrer les conteneurs
docker compose restart

# Reconstruire les images
docker compose down
docker compose build --no-cache
docker compose up -d
```

### Problème : Erreur de connexion à la base de données

**Vérifier la connexion :**
```bash
docker compose exec mysql mysql -u root -p

# Dans MySQL :
SHOW DATABASES;
SELECT User, Host FROM mysql.user;
```

**Recréer l'utilisateur :**
```sql
DROP USER 'subnets_user'@'%';
CREATE USER 'subnets_user'@'%' IDENTIFIED BY 'VotreMotDePasse';
GRANT ALL PRIVILEGES ON subnets.* TO 'subnets_user'@'%';
FLUSH PRIVILEGES;
```

### Problème : Port 8080 déjà utilisé

```bash
# Trouver le processus qui utilise le port
sudo lsof -i :8080

# Tuer le processus (remplacer PID)
sudo kill -9 PID

# Ou changer le port dans docker-compose.yml
nano docker-compose.yml
# Changer "8080:80" en "9090:80"
docker compose up -d
```

### Problème : Permissions insuffisantes (Installation Manuelle)

```bash
# Réparer les permissions
sudo chown -R www-data:www-data /var/www/html/subnet-calculator
sudo chmod -R 755 /var/www/html/subnet-calculator

# Vérifier les permissions du dossier
ls -la /var/www/html/subnet-calculator
```

### Problème : PHP ne charge pas les variables d'environnement

**Solution 1 : Utiliser un fichier .env et le charger dans PHP**

Modifier vos fichiers PHP pour charger `.env` :
```php
<?php
// Au début de api.php, session_api.php, db_init.php
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
        putenv(trim($key) . '=' . trim($value));
    }
}
?>
```

**Solution 2 : Définir les variables dans Apache**

```bash
sudo nano /etc/apache2/envvars
```

Ajouter :
```bash
export DB_HOST=localhost
export DB_NAME=subnets
export DB_USER=subnets_user
export DB_PASSWORD=VotreMotDePasse
export DB_PORT=3306
```

```bash
sudo systemctl restart apache2
```

### Problème : Page blanche / Erreur 500

```bash
# Activer l'affichage des erreurs PHP temporairement
sudo nano /etc/php/8.2/apache2/php.ini

# Modifier ces lignes :
display_errors = On
error_reporting = E_ALL

# Redémarrer Apache
sudo systemctl restart apache2

# Vérifier les logs
sudo tail -f /var/log/apache2/subnet-calculator-error.log
```

---

## 📞 Besoin d'Aide ?

- 📖 **Documentation complète** : [docs/](docs/)
- 🔧 **Guide de maintenance** : [MAINTENANCE.md](MAINTENANCE.md)
- 🐛 **Signaler un bug** : [GitHub Issues](https://github.com/adolky/subnets/issues)

---

**Installation terminée ! Passez au [TUTORIAL.md](TUTORIAL.md) pour apprendre à utiliser l'application. 🎉**
