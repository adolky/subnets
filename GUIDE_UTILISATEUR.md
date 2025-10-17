# Guide Utilisateur - Système d'Authentification

## 🎯 Vue d'ensemble

Après connexion, vous verrez des boutons dans le coin supérieur droit de la page selon votre rôle.

## 🔐 Connexion

### Étape 1 : Ouvrir la page de connexion
1. Allez sur : http://10.105.126.7:8080/subnets.html
2. En haut à droite, vous verrez : **"Non connecté [Se connecter]"**
3. Cliquez sur **"Se connecter"**

### Étape 2 : Entrer vos identifiants
Une fenêtre s'ouvre avec :
- **Nom d'utilisateur** : Tapez votre nom d'utilisateur
- **Mot de passe** : Tapez votre mot de passe

**Compte par défaut :**
- Nom d'utilisateur : `admin`
- Mot de passe : `admin123`

### Étape 3 : Se connecter
- Cliquez sur le bouton **"Se connecter"**
- OU appuyez sur la touche **Entrée**

### Résultat
✅ Message : "Connexion réussie !"
✅ La fenêtre se ferme
✅ Le statut devient : **"Connecté: votre_nom"**
✅ De nouveaux boutons apparaissent

## 👤 Interface Utilisateur Normal

Après connexion, vous verrez :

```
┌────────────────────────────────────────────┐
│ Connecté: votre_nom                         │
│ [Déconnexion] [Changer mot de passe]       │
└────────────────────────────────────────────┘
```

### Boutons disponibles :

#### 🚪 **Déconnexion**
- Clic → Vous déconnecte immédiatement
- Retour à l'état "Non connecté"

#### 🔑 **Changer mot de passe**
- Ouvre une fenêtre pour changer votre mot de passe
- Vous devez entrer :
  1. Votre mot de passe actuel
  2. Votre nouveau mot de passe (minimum 6 caractères)
  3. Confirmer le nouveau mot de passe

## 👨‍💼 Interface Administrateur

Si vous êtes admin, vous verrez un bouton supplémentaire :

```
┌────────────────────────────────────────────────────────┐
│ Connecté: admin                                         │
│ [Déconnexion] [Changer mot de passe]                   │
│ [Gestion utilisateurs]                                  │
└────────────────────────────────────────────────────────┘
```

### 👥 **Gestion utilisateurs** (Admin uniquement)

Cliquez sur ce bouton pour :

#### Voir la liste des utilisateurs
Tableau affichant :
- Nom d'utilisateur
- Rôle (admin / user)
- Date de création
- Bouton Supprimer (sauf pour le compte admin)

#### Ajouter un nouvel utilisateur
Formulaire en bas :
1. **Nom d'utilisateur** : Choisissez un nom unique
2. **Mot de passe** : Minimum 6 caractères
3. **Rôle** : Sélectionnez "user" ou "admin"
4. Cliquez sur **"Ajouter"**

#### Supprimer un utilisateur
- Cliquez sur le bouton **"Supprimer"** à côté du nom
- ⚠️ Le compte "admin" ne peut pas être supprimé (protection)

## 🔑 Changer votre mot de passe

### Étape par étape :

1. **Connectez-vous** d'abord
2. Cliquez sur **"Changer mot de passe"**
3. Une fenêtre s'ouvre avec 3 champs :

   ```
   Ancien mot de passe : [______________]
   Nouveau mot de passe : [______________]
   Confirmer : [______________]
   ```

4. Remplissez les champs :
   - Entrez votre mot de passe actuel
   - Entrez votre nouveau mot de passe
   - Retapez le nouveau mot de passe pour confirmer

5. Cliquez sur **"Changer"**

### Validation :
- ✅ Nouveau mot de passe : minimum 6 caractères
- ✅ Les deux nouveaux mots de passe doivent correspondre
- ✅ L'ancien mot de passe doit être correct

## 💾 Fonctionnalités nécessitant une connexion

### SANS connexion, vous pouvez :
- ✅ Utiliser le calculateur de sous-réseaux
- ✅ Charger des configurations depuis la base de données
- ✅ Voir toutes les configurations sauvegardées
- ✅ Utiliser les liens de bookmark
- ✅ Utiliser toutes les fonctions de calcul

### AVEC connexion, vous pouvez aussi :
- ✅ Sauvegarder vos configurations
- ✅ Modifier vos configurations existantes
- ✅ Changer votre mot de passe

### ADMIN uniquement :
- ✅ Gérer les utilisateurs (ajouter/supprimer)

## 🆘 Résolution de problèmes

### Les boutons ne changent pas après connexion

**Solution 1 : Actualiser la page**
- Appuyez sur **Ctrl + Shift + R** (Windows/Linux)
- OU **Cmd + Shift + R** (Mac)

**Solution 2 : Effacer les cookies**
- Dans votre navigateur
- Paramètres → Confidentialité → Cookies
- Supprimez les cookies pour ce site

**Solution 3 : Vérifier la console**
- Appuyez sur **F12**
- Onglet "Console"
- Vous devriez voir : `Session check response: {success: true, ...}`

### Je ne vois pas "Gestion utilisateurs"

**Cause :** Vous n'êtes pas connecté en tant qu'admin

**Solution :** 
- Déconnectez-vous
- Reconnectez-vous avec le compte admin :
  - Nom d'utilisateur : `admin`
  - Mot de passe : `admin123`

### Le changement de mot de passe échoue

**Vérifiez :**
- ✅ L'ancien mot de passe est correct
- ✅ Le nouveau mot de passe a au moins 6 caractères
- ✅ Les deux nouveaux mots de passe sont identiques

### Message "Not authenticated" lors de la sauvegarde

**Solution :**
1. Vérifiez le statut en haut à droite
2. Si "Non connecté" → Cliquez sur "Se connecter"
3. Connectez-vous avec vos identifiants
4. Essayez de sauvegarder à nouveau

## 📝 Conseils de sécurité

### Mot de passe fort
- ✅ Au moins 6 caractères (minimum requis)
- ✅ Recommandé : 12+ caractères
- ✅ Mélangez lettres, chiffres et caractères spéciaux
- ✅ Ne réutilisez pas le même mot de passe ailleurs

### Bonnes pratiques
- ✅ Déconnectez-vous après utilisation
- ✅ Ne partagez pas vos identifiants
- ✅ Changez votre mot de passe régulièrement
- ✅ Le compte admin doit avoir un mot de passe fort

## 🎓 Cas d'usage typiques

### Utilisateur normal
```
1. Se connecter
2. Créer une configuration de sous-réseau
3. Sauvegarder la configuration
4. Travailler sur d'autres configurations
5. Charger une ancienne configuration
6. Se déconnecter
```

### Administrateur
```
1. Se connecter en tant qu'admin
2. Cliquer sur "Gestion utilisateurs"
3. Ajouter un nouvel utilisateur :
   - Nom : jean.dupont
   - Mot de passe : temp1234
   - Rôle : user
4. Dire à l'utilisateur de changer son mot de passe
5. Vérifier la liste des utilisateurs
6. Supprimer les comptes inactifs
7. Se déconnecter
```

## 🌟 Résumé rapide

| Action | Bouton | Qui peut le faire ? |
|--------|--------|---------------------|
| Se connecter | "Se connecter" | Tout le monde |
| Se déconnecter | "Déconnexion" | Utilisateurs connectés |
| Changer son mot de passe | "Changer mot de passe" | Utilisateurs connectés |
| Gérer les utilisateurs | "Gestion utilisateurs" | Admin uniquement |
| Utiliser le calculateur | Aucun bouton | Tout le monde |
| Sauvegarder config | Bouton "Save" | Utilisateurs connectés |
| Charger config | Bouton "Load" | Tout le monde |

## 📞 Support

### Identifiants par défaut
Si vous avez oublié vos identifiants ou besoin de réinitialiser :

**Compte Admin (par défaut) :**
```
Nom d'utilisateur : admin
Mot de passe : admin123
```

### Compte bloqué ?
Contactez votre administrateur système qui peut :
- Réinitialiser votre mot de passe via la base de données
- Créer un nouveau compte pour vous
- Débloquer votre accès

---

**Vous êtes prêt !** 🚀

Ouvrez votre navigateur et commencez à utiliser l'application :
👉 http://10.105.126.7:8080/subnets.html
