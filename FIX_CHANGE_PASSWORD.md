# Correction du Changement de Mot de Passe

**Date**: 17 octobre 2025  
**Statut**: ✅ RÉSOLU

---

## 🐛 Problème Identifié

Le bouton "Changer" dans le modal "Changer mon mot de passe" ne fonctionnait pas.

### Symptômes
- Clic sur le bouton "Changer" sans effet
- Aucune requête envoyée au serveur
- Formulaire ne se soumettant pas

---

## 🔍 Causes Racines

### 1. Incohérence des IDs des Champs

**HTML** (lignes 2148-2158) :
```html
<input type="password" id="oldPwd" required>
<input type="password" id="newPwd1" required>
<input type="password" id="newPwd2" required>
```

**JavaScript** (fonction `submitChangePwd`) :
```javascript
const currentPassword = document.getElementById('currentPassword').value; // ❌
const newPassword = document.getElementById('newPassword').value;         // ❌
const confirmPassword = document.getElementById('confirmPassword').value; // ❌
```

➡️ **Résultat** : Les valeurs étaient `undefined`, le formulaire ne pouvait pas être soumis.

### 2. Configuration du Formulaire Incorrecte

**Bouton HTML** :
```html
<button type="button" class="btn-primary" onclick="submitChangePwd()">Changer</button>
```

**Problèmes** :
- `type="button"` au lieu de `type="submit"` → pas de soumission de formulaire
- `onclick="submitChangePwd()"` sans passage de l'événement
- La fonction attend `event` : `function submitChangePwd(event)`
- Formulaire avec `onsubmit="return false;"` sans appel de fonction

### 3. Pas de Credentials dans le Fetch

```javascript
fetch('session_api.php', {
  method: 'POST',
  body: formData  // ❌ Pas de credentials
})
```

➡️ **Résultat** : Les cookies de session n'étaient pas envoyés.

---

## ✅ Solutions Appliquées

### 1. IDs des Champs Corrigés

**AVANT** :
```html
<input type="password" id="oldPwd" required>
<input type="password" id="newPwd1" required>
<input type="password" id="newPwd2" required>
```

**APRÈS** :
```html
<input type="password" id="currentPassword" required>
<input type="password" id="newPassword" required>
<input type="password" id="confirmPassword" required>
```

### 2. Formulaire et Bouton Corrigés

**AVANT** :
```html
<form id="changePwdForm" onsubmit="return false;">
  ...
  <button type="button" class="btn-primary" onclick="submitChangePwd()">Changer</button>
</form>
```

**APRÈS** :
```html
<form id="changePwdForm" onsubmit="submitChangePwd(event); return false;">
  ...
  <button type="submit" class="btn-primary">Changer</button>
</form>
```

**Changements** :
- ✅ Formulaire appelle `submitChangePwd(event)` au submit
- ✅ Bouton de type `submit` pour déclencher le submit
- ✅ L'événement est passé à la fonction
- ✅ Suppression du `onclick` inutile

### 3. Credentials Ajoutés au Fetch

**AVANT** :
```javascript
fetch('session_api.php', {
  method: 'POST',
  body: formData
})
```

**APRÈS** :
```javascript
fetch('session_api.php', {
  method: 'POST',
  credentials: 'same-origin',
  body: formData
})
```

---

## 🧪 Validation par Tests Automatisés

**Script de test** : `test_change_password.py`

### Résultats des Tests

| Test | Résultat | Détails |
|------|----------|---------|
| Connexion admin | ✅ | Statut: "Connecté: admin" |
| Bouton visible | ✅ | "Changer mot de passe" visible |
| Modal s'ouvre | ✅ | Titre: "Changer mon mot de passe" |
| Champs présents | ✅ | Tous les 3 champs correctement identifiés |
| Test négatif (mauvais pwd) | ✅ | Erreur: "Old password incorrect" |
| Changement de mot de passe | ✅ | "Password changed successfully!" |
| Modal se ferme | ✅ | Fermeture automatique après succès |
| Reconnexion nouveau pwd | ✅ | Connexion réussie avec NewPass123 |
| Restauration ancien pwd | ✅ | Mot de passe restauré à admin123 |

### Scénarios Testés

#### 1. **Test Négatif** - Mauvais ancien mot de passe
```
Entrée: WrongPassword / NewPass123 / NewPass123
Résultat: ✅ Erreur "Old password incorrect" (code 400)
```

#### 2. **Test Positif** - Changement réussi
```
Entrée: admin123 / NewPass123 / NewPass123
Résultat: ✅ "Password changed successfully!"
Modal fermé: ✅
```

#### 3. **Validation du Nouveau Mot de Passe**
```
Déconnexion: ✅
Reconnexion avec NewPass123: ✅ "Connecté: admin"
```

#### 4. **Restauration**
```
Changement: NewPass123 → admin123
Résultat: ✅ "Password changed successfully!"
```

### Captures d'Écran

```
playwright_screenshots/
├── changepwd_01_initial.png           - Page initiale
├── changepwd_02_login_modal.png       - Modal de connexion
├── changepwd_03_after_login.png       - Après connexion
├── changepwd_05_modal_opened.png      - Modal changement pwd ouvert
├── changepwd_06_wrong_current_pwd.png - Test avec mauvais pwd
├── changepwd_07_after_wrong_pwd.png   - Après erreur
├── changepwd_08_correct_filled.png    - Formulaire correct rempli
├── changepwd_09_after_change.png      - Après changement réussi
├── changepwd_10_logged_out.png        - Après déconnexion
├── changepwd_11_relogin_new_pwd.png   - Reconnexion nouveau pwd
├── changepwd_12_relogin_success.png   - Reconnexion réussie
└── changepwd_13_restored.png          - Mot de passe restauré
```

---

## 📊 Résumé des Modifications

### Fichiers Modifiés

**subnets.html** :
- Ligne ~2147 : Formulaire `onsubmit="submitChangePwd(event); return false;"`
- Ligne ~2149 : ID `currentPassword` (était `oldPwd`)
- Ligne ~2152 : ID `newPassword` (était `newPwd1`)
- Ligne ~2155 : ID `confirmPassword` (était `newPwd2`)
- Ligne ~2159 : Bouton `type="submit"` (était `type="button"`)
- Ligne ~2159 : Suppression du `onclick="submitChangePwd()"`
- Ligne ~825 : Ajout de `credentials: 'same-origin'` dans le fetch

### Tests Créés

- `test_change_password.py` : Test complet du changement de mot de passe avec 9 étapes

---

## ✅ Fonctionnalités Validées

1. ✅ **Bouton "Changer mot de passe"** : Visible après connexion
2. ✅ **Modal s'ouvre** : Sans erreur JavaScript
3. ✅ **Champs du formulaire** : Tous présents avec bons IDs
4. ✅ **Validation ancien mot de passe** : Erreur si incorrect
5. ✅ **Changement de mot de passe** : Succès avec bon ancien pwd
6. ✅ **Modal se ferme** : Automatiquement après succès
7. ✅ **Nouveau mot de passe** : Fonctionne pour reconnexion
8. ✅ **Session persistante** : Cookies correctement envoyés
9. ✅ **Restauration** : Possible de remettre l'ancien mot de passe

---

## 🎯 Avant / Après

### AVANT ❌
- IDs incohérents entre HTML et JavaScript
- Bouton `type="button"` sans soumission
- Pas de credentials dans les requêtes
- Formulaire ne se soumettait pas
- Changement de mot de passe impossible

### APRÈS ✅
- IDs cohérents (`currentPassword`, `newPassword`, `confirmPassword`)
- Bouton `type="submit"` avec soumission de formulaire
- Credentials inclus dans toutes les requêtes
- Formulaire fonctionne correctement
- Changement de mot de passe validé par test complet

---

## 🚀 Comment Tester

### Test Manuel

1. Ouvrir : http://10.105.126.7:8080/subnets.html
2. Se connecter : admin / admin123
3. Cliquer sur **"Changer mot de passe"**
4. Remplir :
   - Ancien mot de passe : `admin123`
   - Nouveau mot de passe : `TestPass123`
   - Confirmer : `TestPass123`
5. Cliquer sur **"Changer"**
6. Vérifier le message : "Password changed successfully!"
7. Se déconnecter
8. Se reconnecter avec : admin / TestPass123
9. Changer à nouveau le mot de passe pour revenir à admin123

### Test Automatisé

```bash
cd /home/aku/subnets
python3 test_change_password.py
```

Les captures d'écran seront dans `playwright_screenshots/changepwd_*.png`

---

## ✨ Conclusion

Le changement de mot de passe fonctionne maintenant parfaitement :
- ✅ Formulaire se soumet correctement
- ✅ Validation de l'ancien mot de passe
- ✅ Nouveau mot de passe pris en compte immédiatement
- ✅ Validation complète avec reconnexion
- ✅ Validé par tests automatisés avec 13 captures d'écran

**Problème RÉSOLU** - Les utilisateurs peuvent maintenant changer leur mot de passe en toute sécurité !
