# 🌐 Subnet Calculator & IPAM# 🌐 Subnet Calculator



> **Un calculateur de sous-réseaux professionnel avec gestion IPAM (IP Address Management), authentification multi-utilisateurs et export CSV détaillé.**A powerful, web-based subnet calculator with visual representation, intelligent IP search, and database management.



[![Docker](https://img.shields.io/badge/Docker-Ready-blue?logo=docker)](https://docker.com/)[![Docker](https://img.shields.io/badge/Docker-Ready-blue?logo=docker)](https://docker.com/)

[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php)](https://php.net/)[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php)](https://php.net/)

[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?logo=mysql&logoColor=white)](https://mysql.com/)

[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE.md)## ✨ Features



---- **Visual Subnet Division**: Interactive subnet splitting and joining with color-coded interface

- **VLAN Management**: Assign VLAN IDs (1-4094) and descriptions to subnets

## 📋 Table des Matières- **Database Storage**: Save and load configurations with MySQL

- **IP Search**: Find which subnet contains any IP address across all saved configurations

- [Aperçu](#-aperçu)- **User Authentication**: Multi-user support with role-based access control

- [Fonctionnalités](#-fonctionnalités)

- [Démarrage Rapide](#-démarrage-rapide)## 🚀 Quick Start

- [Documentation](#-documentation)

- [Architecture](#-architecture)### Using Docker (Recommended)

- [Support](#-support)

- [Licence](#-licence)```bash

# Clone repository

---git clone https://github.com/adolky/subnets.git

cd subnets

## 🎯 Aperçu

# Configure environment

**Subnet Calculator** est une application web complète pour la gestion des adresses IP et des sous-réseaux. Elle combine la puissance d'un calculateur de sous-réseaux traditionnel avec des fonctionnalités IPAM modernes.cp .env.example .env

nano .env  # Set your passwords

### ✨ Pourquoi utiliser Subnet Calculator ?

# Start application

- 🎨 **Interface visuelle intuitive** - Division et fusion de sous-réseaux par simple clicdocker compose up -d

- 💾 **Persistance en base de données** - Sauvegarde automatique de toutes vos configurations

- 🔍 **Recherche IP intelligente** - Trouvez instantanément à quel sous-réseau appartient une IP# Access at http://localhost:8080

- 📊 **Export CSV détaillé** - Exportez tous vos sous-réseaux avec calculs IP précis```

- 👥 **Multi-utilisateurs** - Gestion des rôles (Admin/Viewer) avec authentification sécurisée

- 🏷️ **Gestion VLAN** - Assignation d'IDs et noms de VLANs (1-4094)### Manual Installation

- 🐳 **Déploiement Docker** - Installation en 2 minutes

**Requirements:** PHP 8.2+, MySQL 8.0+, Apache/Nginx

---

```bash

## 🚀 Fonctionnalités# Create database

mysql -u root -p

### 🎯 Calculateur de Sous-RéseauxCREATE DATABASE subnets;

CREATE USER 'subnets_user'@'localhost' IDENTIFIED BY 'your_password';

| Fonctionnalité | Description |GRANT ALL PRIVILEGES ON subnets.* TO 'subnets_user'@'localhost';

|----------------|-------------|FLUSH PRIVILEGES;

| **Division visuelle** | Divisez un réseau en deux sous-réseaux égaux en un clic |

| **Fusion intelligente** | Regroupez deux sous-réseaux adjacents en un seul |# Configure environment

| **Calculs automatiques** | Netmask, broadcast, plage utilisable, nombre d'hôtes |export DB_HOST=localhost

| **Support CIDR** | De /8 à /30 (ou /32 pour hosts uniques) |export DB_NAME=subnets

| **Affichage coloré** | Code couleur selon la taille du sous-réseau |export DB_USER=subnets_user

export DB_PASSWORD=your_password

### 💾 Gestion de Base de Données

# Initialize database

- 📝 **Sauvegarde de configurations** - Nom du site, numéro d'admin, notesphp db_init.php

- 📂 **Liste des configurations** - Visualisation et chargement rapide

- 🗑️ **Suppression sécurisée** - Avec confirmation utilisateur# Add admin user

- 🔄 **Mise à jour en temps réel** - Modifications enregistrées instantanémentphp add_admin_user.php

- 📊 **Historique** - Dates de création et modification```



### 🔍 Recherche IP Avancée## 🎮 Usage



- 🎯 **Recherche globale** - Parcourt toutes les configurations1. **Enter network** (e.g., `192.168.0.0/16`)

- 📍 **Localisation précise** - Identifie le sous-réseau exact2. **Click Update** to validate

- 📋 **Détails complets** - Site, admin, VLAN, plage utilisable3. **Divide/Join** subnets visually

- ⚡ **Résultats instantanés** - Recherche optimisée4. **Add VLAN IDs** and descriptions

5. **Save to Database** for persistence

### 📥 Export CSV Professionnel6. **Search IPs** to find their subnet



**Export de TOUTES les subdivisions** - Plus seulement les réseaux parents !## 🔧 Configuration



15 colonnes détaillées :Edit `.env` file:

- Site Name, Admin Number, Parent Network

- Subnet, Netmask, First IP, Last IP```env

- Usable First, Usable Last, Usable CountDB_HOST=mysql

- Total Hosts, VLAN ID, VLAN NameDB_NAME=subnets

- Created At, Updated AtDB_USER=subnets_user

DB_PASSWORD=your_secure_password

📈 **Amélioration : +840% de données exportées**DB_PORT=3306

MYSQL_ROOT_PASSWORD=your_root_password

### 👥 Authentification & Sécurité```



- 🔐 **Login sécurisé** - Authentification par nom d'utilisateur et mot de passe## 🐛 Troubleshooting

- 👤 **Rôles utilisateurs** - Admin (lecture/écriture) ou Viewer (lecture seule)

- 🔑 **Changement de mot de passe** - Fonctionnalité intégrée```bash

- 🛡️ **Protection CSRF** - Tokens de sécurité pour toutes les actions# Check container status

- 📝 **Gestion utilisateurs** - Interface admin pour créer/modifier/supprimerdocker compose ps

- 🔒 **Sessions sécurisées** - Cookies HttpOnly avec expiration

# View logs

---docker compose logs subnet-calculator



## 🚀 Démarrage Rapide

### ⚡ Installation Automatique en Une Ligne (Recommandée)

**Linux / macOS / WSL :**

```bash
curl -sSL https://raw.githubusercontent.com/adolky/subnets/master/install.sh | bash
```

**Windows PowerShell :**

```powershell
iwr -useb https://raw.githubusercontent.com/adolky/subnets/master/install.ps1 | iex
```

**Le script vous demandera :**
- Environnement : Staging ou Production
- Nom d'utilisateur admin
- Mot de passe admin

**✅ Installation complète en moins de 2 minutes !**

📖 **Plus de détails :** [QUICK_INSTALL.md](QUICK_INSTALL.md)

---

### Installation Docker Manuelle

```bash
# 1. Cloner le repository
git clone https://github.com/adolky/subnets.git
cd subnets

# 2. Configurer l'environnement
cp .env.example .env
nano .env  # Définir vos mots de passe

# 3. Démarrer l'application
docker compose up -d

# 4. Créer un utilisateur admin
docker compose exec subnet-calculator php add_admin_user.php

# 5. Accéder à l'application
# http://localhost:8080
```

---

**C'est tout ! 🎉 Votre IPAM est prêt.**

### Configuration Minimale

Éditez `.env` :

```env
DB_PASSWORD=VotreMotDePasseSecurise123!
MYSQL_ROOT_PASSWORD=VotreMotDePasseRootSecurise123!
```

---

## 📚 Documentation

| Document | Description |
|----------|-------------|
| [**INSTALLATION.md**](INSTALLATION.md) | Guide d'installation détaillé (Docker + Manuel) |
| [**TUTORIAL.md**](TUTORIAL.md) | Tutoriel complet avec exemples pratiques |
| [**MAINTENANCE.md**](MAINTENANCE.md) | Guide de maintenance et administration |
| [**DEPLOYMENT.md**](DEPLOYMENT.md) | Déploiement en production |
| [**docs/**](docs/) | Documentation technique complète |

### Guides Rapides

- 📖 [Guide Utilisateur](docs/GUIDE_UTILISATEUR.md) - Interface et fonctionnalités
- 🔐 [Authentification](docs/AUTHENTICATION_FEATURES.md) - Système de sécurité
- 📥 [Export CSV](docs/EXPORT_DETAILED_IMPROVEMENT.md) - Export détaillé (840% amélioration)
- 🧪 [Tests](tests/) - Suite de tests automatisés

---

## 🏗️ Architecture

### Structure du Projet

```
subnets/
├── 📄 subnets.html          # Application principale (Frontend)
├── 📄 api.php               # API REST (Configurations)
├── 📄 session_api.php       # API Authentification
├── 📄 index.php             # Point d'entrée avec redirection auth
├── 📄 db_init.php           # Initialisation de la base de données
├── 📄 add_admin_user.php    # Script de création utilisateur admin
│
├── 📁 img/                  # Images des masques de sous-réseau
├── 📁 docs/                 # Documentation technique
├── 📁 tests/                # Tests automatisés (Playwright)
├── 📁 screenshots/          # Captures d'écran des tests
│
├── 🐳 Dockerfile            # Image Docker PHP-Apache
├── 🐳 docker-compose.yml    # Stack complète (App + MySQL)
├── 🐳 docker-compose.prod.yml # Configuration production
│
├── 📋 .env.example          # Template de configuration
└── 📖 Documentation...      # README, INSTALLATION, TUTORIAL, etc.
```

### Stack Technique

| Composant | Technologie | Version |
|-----------|-------------|---------|
| **Frontend** | HTML5, CSS3, JavaScript (Vanilla) | - |
| **Backend** | PHP | 8.2+ |
| **Base de données** | MySQL | 8.0+ |
| **Serveur Web** | Apache | 2.4+ |
| **Containerisation** | Docker | 20.10+ |
| **Tests** | Playwright (Python) | 1.40+ |

---

## 🧪 Tests

Suite de tests automatisés avec Playwright :

```bash
cd tests
pip install playwright pytest
playwright install

# Exécuter tous les tests
./test_all_features.sh

# Tests individuels
python test_export_detailed.py       # Export CSV (47 subnets)
python test_authentication.py        # Authentification
python test_export_menu_position.py  # Position menu (0.0px)
```

**Résultats validés** :
- ✅ 47 sous-réseaux exportés (vs 5 configs)
- ✅ Menu positioning : 0.0px de différence
- ✅ 100% de données complètes dans CSV
- ✅ Tous les tests d'authentification passés

---

## 🛠️ Support

- 📖 **Documentation** : Consultez [docs/](docs/)
- 🐛 **Bug Reports** : [GitHub Issues](https://github.com/adolky/subnets/issues)
- 💬 **Questions** : [GitHub Discussions](https://github.com/adolky/subnets/discussions)

---

## 🎯 Roadmap

### Version 2.0 (À venir)

- [ ] 🌐 Support IPv6
- [ ] 📊 Dashboard analytique avec graphiques
- [ ] 📱 Application mobile (PWA)
- [ ] 🔌 API REST complète avec documentation OpenAPI
- [ ] 📈 Monitoring de l'utilisation IP
- [ ] 🌍 Internationalisation (i18n)
- [ ] 🤖 Import/Export depuis NetBox, phpIPAM

---

## 📄 Licence

Ce projet est sous licence **MIT** - voir le fichier [LICENSE.md](LICENSE.md) pour plus de détails.

---

## 📊 Statistiques du Projet

- ⭐ **Lignes de code** : ~3,000+ (Frontend + Backend)
- 🧪 **Tests automatisés** : 8 suites de tests
- 📖 **Pages de documentation** : 15+ documents
- 🐳 **Images Docker** : 2 (app + mysql)
- 🚀 **Temps de déploiement** : < 2 minutes
- 📊 **Export CSV** : 840% plus de données qu'avant

---

<div align="center">

**🌟 Si ce projet vous aide, n'hésitez pas à lui donner une ⭐ sur GitHub !**

**Made with ❤️ for Network Administrators**

[🏠 Homepage](https://github.com/adolky/subnets) • 
[📖 Documentation](docs/) • 
[🐛 Report Bug](https://github.com/adolky/subnets/issues)

</div>
