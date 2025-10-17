# Instructions pour intégrer l'authentification dans le Frontend

## Vue d'ensemble

Le backend exige maintenant une authentification (username/password) pour toute sauvegarde ou mise à jour de configuration. Le frontend doit être modifié pour demander ces credentials à l'utilisateur.

---

## Modifications nécessaires dans `subnets.html`

### 1. Ajouter un modal d'authentification

Ajouter ce HTML dans le `<body>` de subnets.html :

```html
<!-- Authentication Modal -->
<div id="authModal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5);">
    <div style="background-color:white; margin:15% auto; padding:20px; border:1px solid #888; width:400px; border-radius:8px;">
        <h2 style="margin-top:0;">Authentication Required</h2>
        <p>Please enter your credentials to save or update the configuration.</p>
        
        <form id="authForm" onsubmit="return false;">
            <div style="margin-bottom:15px;">
                <label for="authUsername" style="display:block; margin-bottom:5px;">Username:</label>
                <input type="text" id="authUsername" required 
                       style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
            </div>
            
            <div style="margin-bottom:15px;">
                <label for="authPassword" style="display:block; margin-bottom:5px;">Password:</label>
                <input type="password" id="authPassword" required 
                       style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
            </div>
            
            <div id="authError" style="color:red; margin-bottom:15px; display:none;"></div>
            
            <div style="text-align:right;">
                <button type="button" onclick="closeAuthModal()" 
                        style="padding:8px 16px; margin-right:10px; cursor:pointer;">
                    Cancel
                </button>
                <button type="button" onclick="submitWithAuth()" 
                        style="padding:8px 16px; background-color:#4CAF50; color:white; border:none; border-radius:4px; cursor:pointer;">
                    Login & Save
                </button>
            </div>
        </form>
    </div>
</div>
```

### 2. Ajouter les fonctions JavaScript

Ajouter ces fonctions dans la section `<script>` :

```javascript
// Variable globale pour stocker les données à sauvegarder
let pendingSaveData = null;

// Ouvrir le modal d'authentification
function openAuthModal(dataToSave) {
    pendingSaveData = dataToSave;
    document.getElementById('authModal').style.display = 'block';
    document.getElementById('authUsername').value = '';
    document.getElementById('authPassword').value = '';
    document.getElementById('authError').style.display = 'none';
}

// Fermer le modal d'authentification
function closeAuthModal() {
    document.getElementById('authModal').style.display = 'none';
    pendingSaveData = null;
}

// Soumettre avec authentification
function submitWithAuth() {
    const username = document.getElementById('authUsername').value;
    const password = document.getElementById('authPassword').value;
    
    if (!username || !password) {
        showAuthError('Please enter both username and password');
        return;
    }
    
    // Ajouter les credentials aux données
    pendingSaveData.username = username;
    pendingSaveData.password = password;
    
    // Envoyer la requête
    saveToDatabase(pendingSaveData);
}

// Afficher une erreur d'authentification
function showAuthError(message) {
    const errorDiv = document.getElementById('authError');
    errorDiv.textContent = message;
    errorDiv.style.display = 'block';
}
```

### 3. Modifier la fonction de sauvegarde existante

Trouver la fonction qui sauvegarde les configurations (probablement nommée quelque chose comme `saveConfiguration()` ou similaire) et la modifier pour appeler d'abord le modal d'authentification.

**AVANT** :
```javascript
function saveConfiguration() {
    const data = {
        siteName: document.getElementById('siteName').value,
        adminNumber: document.getElementById('adminNumber').value,
        networkAddress: currentNetwork,
        maskBits: currentMask,
        divisionData: getDivisionData(),
        vlanIds: getVlanIds(),
        vlanNames: getVlanNames()
    };
    
    saveToDatabase(data);
}
```

**APRÈS** :
```javascript
function saveConfiguration() {
    const data = {
        siteName: document.getElementById('siteName').value,
        adminNumber: document.getElementById('adminNumber').value,
        networkAddress: currentNetwork,
        maskBits: currentMask,
        divisionData: getDivisionData(),
        vlanIds: getVlanIds(),
        vlanNames: getVlanNames()
    };
    
    // Demander l'authentification avant de sauvegarder
    openAuthModal(data);
}
```

### 4. Modifier la fonction saveToDatabase

Modifier la fonction qui envoie les données à l'API pour gérer les erreurs d'authentification :

```javascript
function saveToDatabase(data) {
    fetch('/api.php?action=save', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Fermer le modal d'authentification
            closeAuthModal();
            
            // Afficher le message de succès
            alert(result.message);
            
            // Réinitialiser ou recharger si nécessaire
        } else {
            // Vérifier si c'est une erreur d'authentification
            if (result.message.includes('Authentication failed')) {
                showAuthError(result.message);
            } else {
                // Fermer le modal et afficher l'erreur
                closeAuthModal();
                alert('Error: ' + result.message);
            }
        }
    })
    .catch(error => {
        closeAuthModal();
        alert('Network error: ' + error.message);
    });
}
```

### 5. Gérer les mises à jour de configuration

Si vous avez une fonction séparée pour mettre à jour une configuration existante, appliquer le même principe :

```javascript
function updateConfiguration(configId) {
    const data = {
        configId: configId,
        siteName: document.getElementById('siteName').value,
        adminNumber: document.getElementById('adminNumber').value,
        networkAddress: currentNetwork,
        maskBits: currentMask,
        divisionData: getDivisionData(),
        vlanIds: getVlanIds(),
        vlanNames: getVlanNames()
    };
    
    // Demander l'authentification avant de mettre à jour
    openAuthModal(data);
}
```

---

## Améliorations optionnelles

### 1. Session persistante (éviter de redemander le mot de passe)

```javascript
// Stocker les credentials en mémoire pour la session
let sessionCredentials = null;

function saveConfiguration() {
    const data = prepareDataToSave();
    
    if (sessionCredentials) {
        // Utiliser les credentials de session
        data.username = sessionCredentials.username;
        data.password = sessionCredentials.password;
        saveToDatabase(data);
    } else {
        // Demander l'authentification
        openAuthModal(data);
    }
}

function submitWithAuth() {
    const username = document.getElementById('authUsername').value;
    const password = document.getElementById('authPassword').value;
    
    // Sauvegarder pour la session
    sessionCredentials = { username, password };
    
    pendingSaveData.username = username;
    pendingSaveData.password = password;
    
    saveToDatabase(pendingSaveData);
}

// Bouton de déconnexion
function logout() {
    sessionCredentials = null;
    alert('Logged out successfully');
}
```

### 2. Indicateur de statut de connexion

```html
<div id="loginStatus" style="position:fixed; top:10px; right:10px; padding:8px; background:#f0f0f0; border-radius:4px;">
    <span id="loginStatusText">Not logged in</span>
    <button id="logoutBtn" onclick="logout()" style="display:none; margin-left:10px;">Logout</button>
</div>
```

```javascript
function updateLoginStatus(isLoggedIn) {
    const statusText = document.getElementById('loginStatusText');
    const logoutBtn = document.getElementById('logoutBtn');
    
    if (isLoggedIn) {
        statusText.textContent = 'Logged in as ' + sessionCredentials.username;
        logoutBtn.style.display = 'inline';
    } else {
        statusText.textContent = 'Not logged in';
        logoutBtn.style.display = 'none';
    }
}
```

---

## Test du frontend

### 1. Tester sans credentials
- Cliquer sur "Save to Database"
- Le modal devrait apparaître
- Essayer de soumettre sans remplir → message d'erreur

### 2. Tester avec mauvais credentials
- Entrer username: `admin`, password: `wrong`
- Le message d'erreur devrait s'afficher dans le modal

### 3. Tester avec bons credentials
- Entrer username: `admin`, password: `admin123`
- La configuration devrait être sauvegardée
- Le modal devrait se fermer
- Message de succès

### 4. Tester la mise à jour
- Charger une configuration existante
- Modifier quelque chose
- Cliquer "Update Configuration"
- Le modal devrait apparaître
- Authentification requise

---

## Credentials par défaut

**Username**: `admin`  
**Password**: `admin123`

⚠️ **Important** : Changer le mot de passe en production !

---

## Support

Si vous rencontrez des problèmes :
1. Vérifier la console JavaScript (F12)
2. Vérifier les requêtes réseau (onglet Network)
3. Vérifier que l'API retourne bien les erreurs d'authentification

---

## Résumé des modifications

| Fichier | Modification |
|---------|--------------|
| `subnets.html` | Ajouter modal d'authentification |
| `subnets.html` | Ajouter fonctions JS (openAuthModal, closeAuthModal, submitWithAuth) |
| `subnets.html` | Modifier saveConfiguration() pour appeler openAuthModal() |
| `subnets.html` | Modifier saveToDatabase() pour gérer erreurs auth |

**Temps estimé** : 30-45 minutes
