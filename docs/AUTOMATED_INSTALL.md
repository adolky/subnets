# 🎯 Commande d'Installation Automatisée - Documentation Technique

## Vue d'ensemble

Les scripts `install.sh` (Linux/macOS/WSL) et `install.ps1` (Windows PowerShell) permettent d'installer automatiquement Subnet Calculator avec Docker en une seule commande.

## Utilisation

### Linux / macOS / WSL

```bash
# Installation directe depuis GitHub
curl -sSL https://raw.githubusercontent.com/adolky/subnets/master/install.sh | bash

# Ou depuis le repository cloné
cd subnets && bash install.sh
```

### Windows PowerShell

```powershell
# Installation directe depuis GitHub
iwr -useb https://raw.githubusercontent.com/adolky/subnets/master/install.ps1 | iex

# Ou depuis le repository cloné
cd subnets; .\install.ps1
```

## Processus Automatisé

### 1. Vérification des Prérequis

Le script vérifie automatiquement :
- ✅ Docker est installé (version 20.10+)
- ✅ Docker Compose est installé (version 2.0+)

Si Docker n'est pas installé, le script affiche un lien vers la documentation d'installation.

### 2. Choix de l'Environnement

Le script demande interactivement :

```
📦 Choisissez l'environnement d'installation :
1) Staging (développement/test)
2) Production
Votre choix [1-2]:
```

**Différences entre les environnements :**

| Aspect | Staging | Production |
|--------|---------|------------|
| Fichier Docker | `docker-compose.yml` | `docker-compose.prod.yml` |
| Port HTTP | 8080 | 80 |
| Port HTTPS | Non configuré | 443 |
| Restart Policy | `unless-stopped` | `always` |
| Logs | Standard | Rotatifs (10MB max, 3 fichiers) |
| Security | Standard | Renforcé (no-new-privileges, tmpfs) |

### 3. Configuration Automatique

Le script :
- Génère un fichier `.env` avec des mots de passe aléatoires sécurisés (25 caractères)
- Utilise `openssl` (Linux/macOS) ou `Get-Random` (Windows) pour la génération
- Configure automatiquement les variables d'environnement

**Exemple de `.env` généré :**

```env
MYSQL_ROOT_PASSWORD=aBc123XyZ789MnOp456QrSt
MYSQL_DATABASE=subnets
MYSQL_USER=subnets_user
MYSQL_PASSWORD=dEf456UvW012GhI789JkL345
SERVER_NAME=subnet-calculator.local
```

### 4. Déploiement des Conteneurs

Le script :
1. Arrête les conteneurs existants (si présents)
2. Démarre les nouveaux conteneurs avec `docker compose up -d --build`
3. Attend que MySQL soit prêt (max 30 tentatives × 2 secondes = 60 secondes)

**Vérification de santé MySQL :**

```bash
docker compose exec -T mysql mysqladmin ping -h localhost --silent
```

### 5. Initialisation de la Base de Données

Exécution automatique du script `db_init.php` :

```bash
docker compose exec -T subnet-calculator php db_init.php
```

Ce script crée :
- La base de données `subnets`
- Les tables `users`, `saved_configs`, `subnets`
- Les index et contraintes nécessaires

### 6. Création de l'Utilisateur Admin

Le script demande interactivement :
1. **Nom d'utilisateur** (par défaut : `admin`)
2. **Mot de passe** avec confirmation
   - Validation : minimum 6 caractères
   - Vérification : les deux saisies doivent correspondre

**Processus technique :**
1. Création d'un script PHP temporaire (`/tmp/create_admin.php`)
2. Copie du script dans le conteneur
3. Exécution avec variables d'environnement `ADMIN_USER` et `ADMIN_PASS`
4. Nettoyage automatique du script temporaire

Le script PHP :
- Vérifie si l'utilisateur existe déjà
- Crée un nouvel utilisateur OU met à jour le mot de passe existant
- Hash le mot de passe avec `PASSWORD_DEFAULT` (bcrypt)
- Définit le rôle sur `admin`

## Fonctionnalités Avancées

### Gestion des Erreurs

Le script utilise `set -e` (bash) / `$ErrorActionPreference = "Stop"` (PowerShell) pour arrêter l'exécution en cas d'erreur.

**Gestion des timeouts :**
- Si MySQL ne démarre pas en 60 secondes, le script s'arrête avec un message d'erreur
- L'utilisateur peut alors vérifier les logs avec `docker compose logs`

### Idempotence

Le script est idempotent :
- Peut être exécuté plusieurs fois sans danger
- Met à jour la configuration existante si `.env` existe déjà
- Met à jour l'utilisateur admin au lieu de créer un doublon

### Sécurité

**Mots de passe :**
- Générés avec 25 caractères aléatoires (alphanumériques)
- Jamais affichés dans les logs ou la console
- Stockés uniquement dans `.env` (non versionné)

**Transmission sécurisée :**
- Utilise des variables d'environnement pour passer les credentials
- Nettoie les fichiers temporaires après utilisation

### Compatibilité Multi-Plateforme

| Plateforme | Script | Shell Requis | Notes |
|------------|--------|--------------|-------|
| Linux | `install.sh` | bash | Utilise `openssl` pour générer les mots de passe |
| macOS | `install.sh` | bash | Utilise `openssl` pour générer les mots de passe |
| WSL | `install.sh` | bash | Windows Subsystem for Linux |
| Windows | `install.ps1` | PowerShell 5.1+ | Utilise `Get-Random` pour générer les mots de passe |

## Informations Post-Installation

Après l'installation, le script affiche :

```
✅ Installation terminée avec succès!

================================================
📊 Informations de connexion
================================================
🌐 URL: http://localhost:8080  (ou :80 en prod)
👤 Utilisateur: admin
🔑 Mot de passe: (celui que vous avez défini)

================================================
📝 Commandes utiles
================================================
Voir les logs:        docker compose logs -f
Arrêter:              docker compose down
Redémarrer:           docker compose restart
Statut:               docker compose ps

🎉 Bonne utilisation de Subnet Calculator!
```

## Dépannage

### Problème : Docker n'est pas installé

**Message d'erreur :**
```
❌ Docker n'est pas installé. Veuillez installer Docker d'abord.
```

**Solution :**
- Linux : https://docs.docker.com/engine/install/
- macOS : https://docs.docker.com/desktop/install/mac-install/
- Windows : https://docs.docker.com/desktop/install/windows-install/

### Problème : Timeout MySQL

**Message d'erreur :**
```
❌ Timeout: MySQL n'a pas démarré dans le temps imparti
```

**Solution :**
```bash
# Vérifier les logs MySQL
docker compose logs mysql

# Redémarrer les conteneurs
docker compose restart

# Réexécuter le script
bash install.sh
```

### Problème : Port déjà utilisé

**Message d'erreur :**
```
Error: bind: address already in use
```

**Solution pour Staging (port 8080) :**
```bash
# Trouver le processus utilisant le port
sudo lsof -i :8080

# Modifier docker-compose.yml pour utiliser un autre port
nano docker-compose.yml
# Changer "8080:80" en "9090:80"

# Réexécuter
bash install.sh
```

### Problème : Les mots de passe ne correspondent pas

Le script redemandera automatiquement jusqu'à ce que les mots de passe correspondent.

### Problème : Permissions insuffisantes

**Sur Linux/macOS :**
```bash
chmod +x install.sh
bash install.sh
```

**Sur Windows :**
```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
.\install.ps1
```

## Architecture du Script

### Bash (install.sh)

```
1. Vérifications préliminaires
   ├── Docker installé ?
   └── Docker Compose installé ?

2. Choix environnement
   ├── Option 1: Staging → docker-compose.yml
   └── Option 2: Production → docker-compose.prod.yml

3. Configuration
   ├── .env existe ?
   │   ├── Oui → Utiliser config existante
   │   └── Non → Créer avec mots de passe aléatoires
   └── Générer mots de passe (openssl)

4. Déploiement
   ├── Arrêter conteneurs existants
   ├── Démarrer nouveaux conteneurs
   └── Attendre MySQL (healthcheck)

5. Initialisation
   ├── Exécuter db_init.php
   └── Créer utilisateur admin
       ├── Demander username
       ├── Demander password (avec confirmation)
       └── Exécuter create_admin.php

6. Affichage informations
   └── URL, credentials, commandes utiles
```

### PowerShell (install.ps1)

Structure identique, avec adaptations Windows :
- Utilise `Get-Random` au lieu de `openssl`
- Utilise `SecureString` pour les mots de passe
- Gestion d'erreurs PowerShell (`try/catch`)

## Maintenance

### Réexécuter le script

Le script peut être réexécuté à tout moment :
- Pour mettre à jour l'installation
- Pour changer l'environnement (Staging ↔ Production)
- Pour réinitialiser l'utilisateur admin

### Mise à jour des conteneurs

```bash
# Mettre à jour les images
docker compose pull

# Redémarrer avec les nouvelles images
docker compose up -d --build

# Réexécuter le script pour réinitialiser
bash install.sh
```

## Licence

Ce script fait partie du projet Subnet Calculator sous licence MIT.
