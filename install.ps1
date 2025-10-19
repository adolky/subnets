# Subnet Calculator - Installation Automatisée avec Docker (PowerShell)
# Usage: .\install.ps1

$ErrorActionPreference = "Stop"

Write-Host "🚀 Subnet Calculator - Installation Automatisée" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

# Vérifier que Docker est installé
try {
    $null = docker --version
    Write-Host "✅ Docker est installé" -ForegroundColor Green
} catch {
    Write-Host "❌ Docker n'est pas installé. Veuillez installer Docker Desktop d'abord." -ForegroundColor Red
    Write-Host "   https://docs.docker.com/desktop/install/windows-install/" -ForegroundColor Yellow
    exit 1
}

try {
    $null = docker compose version
    Write-Host "✅ Docker Compose est installé" -ForegroundColor Green
} catch {
    Write-Host "❌ Docker Compose n'est pas disponible." -ForegroundColor Red
    exit 1
}

Write-Host ""

# Demander l'environnement
Write-Host "📦 Choisissez l'environnement d'installation :" -ForegroundColor Cyan
Write-Host "1) Staging (développement/test)"
Write-Host "2) Production"
$EnvChoice = Read-Host "Votre choix [1-2]"

if ($EnvChoice -eq "2") {
    $ComposeFile = "docker-compose.prod.yml"
    $EnvType = "PRODUCTION"
    Write-Host "🏭 Installation en mode PRODUCTION" -ForegroundColor Yellow
} else {
    $ComposeFile = "docker-compose.yml"
    $EnvType = "STAGING"
    Write-Host "🔧 Installation en mode STAGING" -ForegroundColor Yellow
}
Write-Host ""

# Créer le fichier .env s'il n'existe pas
if (-not (Test-Path .env)) {
    Write-Host "📝 Création du fichier .env..." -ForegroundColor Cyan
    
    # Générer des mots de passe aléatoires sécurisés
    $DbPassword = -join ((65..90) + (97..122) + (48..57) | Get-Random -Count 25 | ForEach-Object {[char]$_})
    $RootPassword = -join ((65..90) + (97..122) + (48..57) | Get-Random -Count 25 | ForEach-Object {[char]$_})
    
    $EnvContent = @"
# Database Configuration - $EnvType
MYSQL_ROOT_PASSWORD=$RootPassword
MYSQL_DATABASE=subnets
MYSQL_USER=subnets_user
MYSQL_PASSWORD=$DbPassword

# Optional: Server configuration
SERVER_NAME=subnet-calculator.local
"@
    
    $EnvContent | Out-File -FilePath .env -Encoding utf8
    Write-Host "✅ Fichier .env créé avec des mots de passe sécurisés" -ForegroundColor Green
} else {
    Write-Host "ℹ️  Fichier .env existant détecté, utilisation de la configuration existante" -ForegroundColor Yellow
}
Write-Host ""

# Arrêter les conteneurs existants
Write-Host "🛑 Arrêt des conteneurs existants (si présents)..." -ForegroundColor Cyan
try {
    docker compose -f $ComposeFile down 2>$null
} catch {
    # Ignorer les erreurs si aucun conteneur n'existe
}
Write-Host ""

# Démarrer les conteneurs
Write-Host "🐳 Démarrage des conteneurs Docker..." -ForegroundColor Cyan
docker compose -f $ComposeFile up -d --build

# Attendre que MySQL soit prêt
Write-Host ""
Write-Host "⏳ Attente du démarrage de MySQL..." -ForegroundColor Cyan
$MaxTries = 30
$Count = 0
$MysqlReady = $false

while (-not $MysqlReady -and $Count -lt $MaxTries) {
    try {
        $null = docker compose -f $ComposeFile exec -T mysql mysqladmin ping -h localhost --silent 2>$null
        $MysqlReady = $true
    } catch {
        Write-Host -NoNewline "."
        Start-Sleep -Seconds 2
        $Count++
    }
}
Write-Host ""

if (-not $MysqlReady) {
    Write-Host "❌ Timeout: MySQL n'a pas démarré dans le temps imparti" -ForegroundColor Red
    exit 1
}

Write-Host "✅ MySQL est prêt" -ForegroundColor Green
Write-Host ""

# Initialiser la base de données
Write-Host "🗄️  Initialisation de la base de données..." -ForegroundColor Cyan
docker compose -f $ComposeFile exec -T subnet-calculator php db_init.php
Write-Host ""

# Créer l'utilisateur admin
Write-Host "👤 Création de l'utilisateur administrateur" -ForegroundColor Cyan
Write-Host "===========================================" -ForegroundColor Cyan
Write-Host ""

$AdminUser = Read-Host "Nom d'utilisateur admin [admin]"
if ([string]::IsNullOrWhiteSpace($AdminUser)) {
    $AdminUser = "admin"
}

# Demander le mot de passe avec confirmation
$PasswordMatch = $false
while (-not $PasswordMatch) {
    $AdminPass = Read-Host "Mot de passe admin" -AsSecureString
    $AdminPassConfirm = Read-Host "Confirmez le mot de passe" -AsSecureString
    
    $AdminPassPlain = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($AdminPass))
    $AdminPassConfirmPlain = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($AdminPassConfirm))
    
    if ($AdminPassPlain -eq $AdminPassConfirmPlain) {
        if ($AdminPassPlain.Length -lt 6) {
            Write-Host "❌ Le mot de passe doit contenir au moins 6 caractères" -ForegroundColor Red
        } else {
            $PasswordMatch = $true
        }
    } else {
        Write-Host "❌ Les mots de passe ne correspondent pas. Réessayez." -ForegroundColor Red
    }
}

# Créer un script PHP temporaire pour créer l'utilisateur
$CreateAdminPhp = @'
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
'@

$TempFile = [System.IO.Path]::GetTempFileName()
$CreateAdminPhp | Out-File -FilePath $TempFile -Encoding utf8 -NoNewline

# Copier le script dans le conteneur et l'exécuter
docker cp $TempFile subnet-calculator:/tmp/create_admin.php
$env:ADMIN_USER = $AdminUser
$env:ADMIN_PASS = $AdminPassPlain
docker compose -f $ComposeFile exec -T -e ADMIN_USER="$AdminUser" -e ADMIN_PASS="$AdminPassPlain" subnet-calculator php /tmp/create_admin.php
docker compose -f $ComposeFile exec -T subnet-calculator rm /tmp/create_admin.php
Remove-Item $TempFile

Write-Host ""
Write-Host "✅ Installation terminée avec succès!" -ForegroundColor Green
Write-Host ""
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "📊 Informations de connexion" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan

if ($EnvType -eq "PRODUCTION") {
    Write-Host "🌐 URL: http://localhost" -ForegroundColor Yellow
} else {
    Write-Host "🌐 URL: http://localhost:8080" -ForegroundColor Yellow
}

Write-Host "👤 Utilisateur: $AdminUser" -ForegroundColor Yellow
Write-Host "🔑 Mot de passe: (celui que vous avez défini)" -ForegroundColor Yellow
Write-Host ""
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "📝 Commandes utiles" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "Voir les logs:        docker compose -f $ComposeFile logs -f"
Write-Host "Arrêter:              docker compose -f $ComposeFile down"
Write-Host "Redémarrer:           docker compose -f $ComposeFile restart"
Write-Host "Statut:               docker compose -f $ComposeFile ps"
Write-Host ""
Write-Host "🎉 Bonne utilisation de Subnet Calculator!" -ForegroundColor Green
