#!/bin/bash
# Subnet Calculator - Installation Automatisée avec Docker
# Usage: bash install.sh

set -e

echo "🚀 Subnet Calculator - Installation Automatisée"
echo "================================================"
echo ""

# Vérifier que Docker est installé
if ! command -v docker &> /dev/null; then
    echo "❌ Docker n'est pas installé. Veuillez installer Docker d'abord."
    echo "   https://docs.docker.com/get-docker/"
    exit 1
fi

if ! command -v docker compose &> /dev/null; then
    echo "❌ Docker Compose n'est pas installé. Veuillez installer Docker Compose d'abord."
    exit 1
fi

echo "✅ Docker et Docker Compose sont installés"
echo ""

# Demander l'environnement
echo "📦 Choisissez l'environnement d'installation :"
echo "1) Staging (développement/test)"
echo "2) Production"
read -p "Votre choix [1-2]: " ENV_CHOICE

if [ "$ENV_CHOICE" = "2" ]; then
    COMPOSE_FILE="docker-compose.prod.yml"
    ENV_TYPE="PRODUCTION"
    echo "🏭 Installation en mode PRODUCTION"
else
    COMPOSE_FILE="docker-compose.yml"
    ENV_TYPE="STAGING"
    echo "🔧 Installation en mode STAGING"
fi
echo ""

# Créer le fichier .env s'il n'existe pas
if [ ! -f .env ]; then
    echo "📝 Création du fichier .env..."
    
    # Générer des mots de passe aléatoires sécurisés
    DB_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
    ROOT_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
    
    cat > .env << EOF
# Database Configuration - $ENV_TYPE
MYSQL_ROOT_PASSWORD=$ROOT_PASSWORD
MYSQL_DATABASE=subnets
MYSQL_USER=subnets_user
MYSQL_PASSWORD=$DB_PASSWORD

# Optional: Server configuration
SERVER_NAME=subnet-calculator.local
EOF
    
    echo "✅ Fichier .env créé avec des mots de passe sécurisés"
else
    echo "ℹ️  Fichier .env existant détecté, utilisation de la configuration existante"
fi
echo ""

# Arrêter les conteneurs existants
echo "🛑 Arrêt des conteneurs existants (si présents)..."
docker compose -f "$COMPOSE_FILE" down 2>/dev/null || true
echo ""

# Démarrer les conteneurs
echo "🐳 Démarrage des conteneurs Docker..."
docker compose -f "$COMPOSE_FILE" up -d --build

# Attendre que MySQL soit prêt
echo ""
echo "⏳ Attente du démarrage de MySQL..."
MAX_TRIES=30
COUNT=0
until docker compose -f "$COMPOSE_FILE" exec -T mysql mysqladmin ping -h localhost --silent 2>/dev/null || [ $COUNT -eq $MAX_TRIES ]; do
    echo -n "."
    sleep 2
    COUNT=$((COUNT + 1))
done
echo ""

if [ $COUNT -eq $MAX_TRIES ]; then
    echo "❌ Timeout: MySQL n'a pas démarré dans le temps imparti"
    exit 1
fi

echo "✅ MySQL est prêt"
echo ""

# Initialiser la base de données
echo "🗄️  Initialisation de la base de données..."
docker compose -f "$COMPOSE_FILE" exec -T subnet-calculator php db_init.php
echo ""

# Créer l'utilisateur admin
echo "👤 Création de l'utilisateur administrateur"
echo "==========================================="
echo ""

read -p "Nom d'utilisateur admin [admin]: " ADMIN_USER
ADMIN_USER=${ADMIN_USER:-admin}

# Demander le mot de passe avec confirmation
while true; do
    read -sp "Mot de passe admin: " ADMIN_PASS
    echo ""
    read -sp "Confirmez le mot de passe: " ADMIN_PASS_CONFIRM
    echo ""
    
    if [ "$ADMIN_PASS" = "$ADMIN_PASS_CONFIRM" ]; then
        if [ ${#ADMIN_PASS} -lt 6 ]; then
            echo "❌ Le mot de passe doit contenir au moins 6 caractères"
            continue
        fi
        break
    else
        echo "❌ Les mots de passe ne correspondent pas. Réessayez."
    fi
done

# Créer un script PHP temporaire pour créer l'utilisateur
cat > /tmp/create_admin.php << 'EOPHP'
<?php
require_once '/var/www/html/db_init.php';

$username = getenv('ADMIN_USER');
$password = getenv('ADMIN_PASS');

if (empty($username) || empty($password)) {
    echo "❌ Erreur: Nom d'utilisateur ou mot de passe vide\n";
    exit(1);
}

try {
    $database = new SubnetDatabase(null, true);
    $db = $database->getConnection();
    
    // Vérifier si l'utilisateur existe déjà
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        echo "⚠️  L'utilisateur '$username' existe déjà\n";
        
        // Mettre à jour le mot de passe et le rôle
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password_hash = ?, role = 'admin' WHERE username = ?");
        $stmt->execute([$password_hash, $username]);
        echo "✅ Mot de passe mis à jour et rôle défini sur 'admin'\n";
    } else {
        // Créer le nouvel utilisateur
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, 'admin')");
        $stmt->execute([$username, $password_hash]);
        echo "✅ Utilisateur admin '$username' créé avec succès\n";
    }
    exit(0);
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
EOPHP

# Copier le script dans le conteneur et l'exécuter
docker cp /tmp/create_admin.php subnet-calculator:/tmp/create_admin.php
docker compose -f "$COMPOSE_FILE" exec -T -e ADMIN_USER="$ADMIN_USER" -e ADMIN_PASS="$ADMIN_PASS" subnet-calculator php /tmp/create_admin.php
docker compose -f "$COMPOSE_FILE" exec -T subnet-calculator rm /tmp/create_admin.php
rm /tmp/create_admin.php

echo ""
echo "✅ Installation terminée avec succès!"
echo ""
echo "================================================"
echo "📊 Informations de connexion"
echo "================================================"

if [ "$ENV_TYPE" = "PRODUCTION" ]; then
    echo "🌐 URL: http://localhost"
else
    echo "🌐 URL: http://localhost:8080"
fi

echo "👤 Utilisateur: $ADMIN_USER"
echo "🔑 Mot de passe: (celui que vous avez défini)"
echo ""
echo "================================================"
echo "📝 Commandes utiles"
echo "================================================"
echo "Voir les logs:        docker compose -f $COMPOSE_FILE logs -f"
echo "Arrêter:              docker compose -f $COMPOSE_FILE down"
echo "Redémarrer:           docker compose -f $COMPOSE_FILE restart"
echo "Statut:               docker compose -f $COMPOSE_FILE ps"
echo ""
echo "🎉 Bonne utilisation de Subnet Calculator!"
