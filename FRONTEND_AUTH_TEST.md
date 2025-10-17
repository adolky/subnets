# Test du Flux d'Authentification Frontend

## Objectif
Valider que le modal d'authentification s'affiche correctement et que les credentials sont envoyés à l'API.

## Modifications Apportées

### 1. Modal d'Authentification (HTML)
- Ajout d'un nouveau modal `authModal` avec les champs :
  - **Username** : champ texte pour le nom d'utilisateur
  - **Password** : champ mot de passe
  - **Message d'erreur** : zone pour afficher les erreurs d'authentification
  - **Boutons** : Cancel et "Login & Save"

### 2. Fonctions JavaScript Ajoutées

#### `showAuthModal()`
- Cache le modal de sauvegarde
- Réinitialise les champs d'authentification
- Affiche le modal d'authentification
- Place le focus sur le champ username

#### `closeAuthModal()`
- Cache le modal d'authentification
- Réaffiche le modal de sauvegarde
- Annule les données en attente

#### `submitWithAuth()`
- Récupère les credentials (username, password)
- Valide que les champs ne sont pas vides
- Ajoute les credentials aux données de sauvegarde
- Envoie la requête à l'API
- Gère les erreurs d'authentification spécifiquement
- Réaffiche le modal d'authentification en cas d'erreur

### 3. Flux d'Authentification Modifié

**Ancien flux :**
1. Utilisateur clique sur "Save to Database"
2. Remplir le formulaire (Site Name, Admin Number)
3. Cliquer sur "Save Configuration"
4. ❌ Erreur : "Authentication failed"

**Nouveau flux :**
1. Utilisateur clique sur "Save to Database"
2. Remplir le formulaire (Site Name, Admin Number)
3. Cliquer sur "Save Configuration"
4. ✅ Modal d'authentification s'affiche
5. Entrer les credentials (username: admin, password: admin123)
6. Cliquer sur "Login & Save"
7. ✅ Configuration sauvegardée avec succès

## Plan de Test

### Test 1 : Affichage du Modal d'Authentification
**Étapes :**
1. Ouvrir http://localhost:8080/subnets.html
2. Configurer un réseau (ex: 192.168.1.0/24)
3. Cliquer sur "Save to Database"
4. Remplir les champs requis
5. Cliquer sur "Save Configuration"

**Résultat attendu :**
- Le modal de sauvegarde disparaît
- Le modal d'authentification apparaît
- Les champs username et password sont vides
- Le focus est sur le champ username

### Test 2 : Validation des Champs
**Étapes :**
1. Suivre Test 1 jusqu'au modal d'authentification
2. Cliquer sur "Login & Save" sans remplir les champs

**Résultat attendu :**
- Message d'erreur : "Please enter both username and password"
- Le modal reste affiché

### Test 3 : Authentification Avec Mauvais Credentials
**Étapes :**
1. Suivre Test 1 jusqu'au modal d'authentification
2. Entrer username: "wrong", password: "wrong"
3. Cliquer sur "Login & Save"

**Résultat attendu :**
- Requête envoyée à l'API
- Modal de sauvegarde s'affiche brièvement avec "Saving configuration..."
- Modal d'authentification réapparaît
- Message d'erreur : "Authentication failed: invalid username or password"

### Test 4 : Authentification Réussie (Nouvelle Configuration)
**Étapes :**
1. Suivre Test 1 jusqu'au modal d'authentification
2. Entrer username: "admin", password: "admin123"
3. Cliquer sur "Login & Save"

**Résultat attendu :**
- Requête envoyée à l'API avec username et password
- Modal de sauvegarde affiche "Saving configuration..."
- Message de succès : "Configuration saved successfully"
- Modal se ferme automatiquement après 2 secondes
- Le bouton "Save to Database" devient "Update Configuration"

### Test 5 : Authentification pour Mise à Jour
**Étapes :**
1. Charger une configuration existante
2. Modifier le réseau (ajouter des divisions)
3. Cliquer sur "Update Configuration"
4. Le modal d'authentification s'affiche directement (pas de formulaire de sauvegarde)
5. Entrer username: "admin", password: "admin123"
6. Cliquer sur "Login & Save"

**Résultat attendu :**
- Configuration mise à jour avec succès
- Message : "Configuration updated successfully"
- Modal se ferme automatiquement

### Test 6 : Annulation de l'Authentification
**Étapes :**
1. Suivre Test 1 jusqu'au modal d'authentification
2. Cliquer sur "Cancel"

**Résultat attendu :**
- Modal d'authentification se ferme
- Modal de sauvegarde réapparaît
- Les données du formulaire sont conservées
- Aucune requête n'est envoyée

### Test 7 : Annulation Complète
**Étapes :**
1. Suivre Test 6
2. Dans le modal de sauvegarde, cliquer sur "Cancel"

**Résultat attendu :**
- Modal de sauvegarde se ferme
- Modal d'authentification se ferme (s'il était ouvert)
- Toutes les données en attente sont effacées

## Données de Test

### Utilisateur Admin
- **Username :** admin
- **Password :** admin123

### Configuration Réseau de Test
- **Network Address :** 192.168.1.0/24
- **Site Name :** Test Site
- **Admin Number :** ADM-001

## Vérification Backend

Pour vérifier que les credentials sont bien envoyés à l'API :

```bash
# Vérifier les logs du conteneur
docker logs subnet-calculator --tail 50 -f

# Tester l'API directement
curl -X POST http://localhost:8080/api.php?action=save \
  -H "Content-Type: application/json" \
  -d '{
    "siteName": "Test Site",
    "adminNumber": "ADM-001",
    "networkAddress": "192.168.1.0/24",
    "maskBits": 24,
    "divisionData": "",
    "vlanIds": "",
    "vlanNames": "",
    "username": "admin",
    "password": "admin123"
  }'
```

## Structure du Code

### Variables Globales Ajoutées
```javascript
let pendingSaveData = null;  // Stocke les données en attente d'authentification
```

### Payload API
```json
{
  "siteName": "Test Site",
  "adminNumber": "ADM-001",
  "networkAddress": "192.168.1.0/24",
  "maskBits": 24,
  "divisionData": "...",
  "vlanIds": "...",
  "vlanNames": "...",
  "username": "admin",      // ← Nouveau
  "password": "admin123"     // ← Nouveau
}
```

## Résolution des Problèmes

### Le modal d'authentification ne s'affiche pas
- Vérifier que `showAuthModal()` est bien appelée dans `saveToDatabase()`
- Vérifier l'ID du modal : `authModal`
- Vérifier le CSS : `display: flex;`

### Les credentials ne sont pas envoyés
- Vérifier que `submitWithAuth()` est appelée au clic du bouton
- Vérifier que `pendingSaveData` contient les données
- Vérifier le spread operator : `{ ...pendingSaveData, username, password }`

### L'erreur d'authentification ne s'affiche pas
- Vérifier que le message d'erreur contient "authentication" (insensible à la casse)
- Vérifier l'élément `authError`
- Vérifier `authError.style.display = 'block'`

## Statut
- ✅ Modal HTML créé
- ✅ Fonctions JavaScript ajoutées
- ✅ Flux d'authentification modifié
- ✅ Gestion des erreurs implémentée
- 🔄 Tests à effectuer par l'utilisateur

## Prochaines Étapes
1. Rafraîchir la page dans le navigateur (Ctrl+F5 pour vider le cache)
2. Exécuter les tests ci-dessus
3. Valider que tout fonctionne correctement
4. Créer d'autres utilisateurs si nécessaire avec `add_admin_user.php`
