# Test de l'Installation Automatisée

Ce document décrit comment tester les scripts d'installation avant de les publier.

## Test Local (Recommandé)

### Linux / macOS / WSL

```bash
cd subnets
bash install.sh
```

### Windows PowerShell

```powershell
cd subnets
.\install.ps1
```

## Scénarios de Test

### 1. Installation Staging (Première fois)

- [ ] Supprimer le fichier `.env` s'il existe
- [ ] Exécuter le script
- [ ] Choisir option "1" (Staging)
- [ ] Vérifier la génération automatique des mots de passe
- [ ] Entrer nom d'utilisateur admin : `testadmin`
- [ ] Entrer mot de passe : `Test123!`
- [ ] Vérifier l'URL : http://localhost:8080
- [ ] Se connecter avec les credentials
- [ ] Tester la création d'un sous-réseau

### 2. Installation Production

- [ ] Arrêter Staging : `docker compose down`
- [ ] Exécuter le script
- [ ] Choisir option "2" (Production)
- [ ] Vérifier l'URL : http://localhost
- [ ] Se connecter avec les credentials

### 3. Réinstallation (Idempotence)

- [ ] Exécuter le script à nouveau
- [ ] Vérifier qu'il utilise le `.env` existant
- [ ] Changer le mot de passe admin
- [ ] Vérifier que l'utilisateur est mis à jour

### 4. Test d'Erreur - Docker non installé

```bash
# Temporairement renommer docker
sudo mv /usr/bin/docker /usr/bin/docker.bak

# Exécuter le script
bash install.sh

# Doit afficher un message d'erreur clair

# Restaurer docker
sudo mv /usr/bin/docker.bak /usr/bin/docker
```

### 5. Test d'Erreur - Port occupé

```bash
# Démarrer un serveur sur le port 8080
python3 -m http.server 8080 &
SERVER_PID=$!

# Exécuter le script (doit échouer)
bash install.sh

# Arrêter le serveur
kill $SERVER_PID
```

### 6. Test de Mot de Passe

- [ ] Entrer un mot de passe < 6 caractères → Doit refuser
- [ ] Entrer deux mots de passe différents → Doit redemander
- [ ] Entrer le même mot de passe 2 fois → Doit accepter

## Vérifications Post-Installation

### 1. Conteneurs

```bash
docker compose ps
# Doit afficher 2 conteneurs : subnet-calculator et mysql
```

### 2. Base de Données

```bash
docker compose exec mysql mysql -u root -p -e "SHOW DATABASES;"
# Doit afficher la base 'subnets'

docker compose exec mysql mysql -u root -p subnets -e "SHOW TABLES;"
# Doit afficher : saved_configs, subnets, users
```

### 3. Utilisateur Admin

```bash
docker compose exec mysql mysql -u root -p subnets -e "SELECT username, role FROM users;"
# Doit afficher l'utilisateur avec role='admin'
```

### 4. Application Web

```bash
# Staging
curl -I http://localhost:8080/subnets.html
# Doit retourner 200 OK

# Production
curl -I http://localhost/subnets.html
# Doit retourner 200 OK
```

### 5. Authentification

1. Ouvrir http://localhost:8080 (ou http://localhost)
2. Se connecter avec les credentials créés
3. Vérifier que le nom d'utilisateur s'affiche
4. Créer une configuration
5. Sauvegarder dans la base de données
6. Recharger la page et charger la configuration

## Checklist de Validation

### Script Bash (install.sh)

- [ ] Vérification Docker installé
- [ ] Vérification Docker Compose installé
- [ ] Menu de choix Staging/Production
- [ ] Création .env avec mots de passe aléatoires
- [ ] Démarrage conteneurs
- [ ] Attente MySQL (healthcheck)
- [ ] Initialisation base de données
- [ ] Création utilisateur admin (interactif)
- [ ] Affichage informations connexion
- [ ] Gestion erreur : Docker manquant
- [ ] Gestion erreur : Timeout MySQL
- [ ] Gestion erreur : Mot de passe < 6 caractères
- [ ] Gestion erreur : Mots de passe différents

### Script PowerShell (install.ps1)

- [ ] Même checklist que Bash
- [ ] Test sur Windows 10
- [ ] Test sur Windows 11
- [ ] Test avec PowerShell 5.1
- [ ] Test avec PowerShell 7.x

## Nettoyage Après Tests

```bash
# Arrêter tous les conteneurs
docker compose down
docker compose -f docker-compose.prod.yml down

# Supprimer les volumes (ATTENTION : supprime les données)
docker volume rm subnets_mysql_data

# Supprimer le fichier .env
rm .env

# Vérifier que tout est propre
docker compose ps
docker volume ls | grep subnets
```

## Tests Automatisés (Optionnel)

### Script de Test Bash

```bash
#!/bin/bash
# test_install.sh

echo "🧪 Tests automatisés de l'installation"

# Test 1: Vérification prérequis
echo "Test 1: Vérification Docker..."
if command -v docker &> /dev/null; then
    echo "✅ Docker installé"
else
    echo "❌ Docker non installé"
    exit 1
fi

# Test 2: Installation Staging
echo "Test 2: Installation Staging..."
echo -e "1\ntestadmin\nTest123!\nTest123!" | bash install.sh
if [ $? -eq 0 ]; then
    echo "✅ Installation réussie"
else
    echo "❌ Installation échouée"
    exit 1
fi

# Test 3: Vérification conteneurs
echo "Test 3: Vérification conteneurs..."
CONTAINERS=$(docker compose ps -q | wc -l)
if [ $CONTAINERS -eq 2 ]; then
    echo "✅ 2 conteneurs démarrés"
else
    echo "❌ Nombre de conteneurs incorrect: $CONTAINERS"
    exit 1
fi

# Test 4: Test HTTP
echo "Test 4: Test application web..."
sleep 5  # Attendre que l'app soit prête
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8080/subnets.html)
if [ $HTTP_CODE -eq 200 ]; then
    echo "✅ Application accessible"
else
    echo "❌ HTTP Code: $HTTP_CODE"
    exit 1
fi

echo "🎉 Tous les tests passés!"
```

## Résultat Attendu

```
🚀 Subnet Calculator - Installation Automatisée
================================================

✅ Docker et Docker Compose sont installés

📦 Choisissez l'environnement d'installation :
1) Staging (développement/test)
2) Production
Votre choix [1-2]: 1
🔧 Installation en mode STAGING

📝 Création du fichier .env...
✅ Fichier .env créé avec des mots de passe sécurisés

🛑 Arrêt des conteneurs existants (si présents)...

🐳 Démarrage des conteneurs Docker...
⏳ Attente du démarrage de MySQL...
✅ MySQL est prêt

🗄️  Initialisation de la base de données...

👤 Création de l'utilisateur administrateur
===========================================

Nom d'utilisateur admin [admin]: testadmin
Mot de passe admin: 
Confirmez le mot de passe: 
✅ Utilisateur admin 'testadmin' créé avec succès

✅ Installation terminée avec succès!

================================================
📊 Informations de connexion
================================================
🌐 URL: http://localhost:8080
👤 Utilisateur: testadmin
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

## Notes

- Les tests doivent être effectués sur un système propre (sans installation précédente)
- Vérifier que le port 8080 (Staging) ou 80 (Production) est libre
- Avoir au moins 1 Go d'espace disque disponible
- Connexion Internet requise pour télécharger les images Docker

## Problèmes Connus

### Port 8080 déjà utilisé

**Solution** : Modifier `docker-compose.yml` pour utiliser un autre port

### MySQL ne démarre pas

**Solution** : Vérifier les logs avec `docker compose logs mysql`

### Permission denied

**Solution Linux** : Ajouter l'utilisateur au groupe docker
```bash
sudo usermod -aG docker $USER
newgrp docker
```

**Solution Windows** : Exécuter PowerShell en tant qu'administrateur
