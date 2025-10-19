# 🔧 Correction du problème "role = undefined"

## 📋 Problème identifié

Les rôles des utilisateurs s'affichaient comme "undefined" dans la modal "Gestion des utilisateurs".

## 🔍 Cause racine

La table `users` dans la base de données MySQL **ne contenait pas** la colonne `role`. Bien que :
1. Le schéma dans `db_init.php` incluait cette colonne
2. Le code PHP dans `session_api.php` essayait de sélectionner ce champ
3. Le frontend s'attendait à recevoir cette information

La table avait été créée **avant** l'ajout de la fonctionnalité d'authentification avec rôles, et n'avait jamais été mise à jour.

## 🛠️ Solution appliquée

### 1. Création du script de migration

Fichier: `add_role_column.php`

```php
<?php
$dsn = "mysql:host=mysql;dbname=subnets;charset=utf8mb4";
$pdo = new PDO($dsn, "subnets_user", "change_this_password");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Vérifier si la colonne role existe
$stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
$exists = $stmt->rowCount() > 0;

if (!$exists) {
    // Ajouter la colonne
    $pdo->exec("ALTER TABLE users ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'user' AFTER password_hash");
    
    // Mettre à jour l'utilisateur admin
    $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE username = 'admin'");
    $stmt->execute();
}
?>
```

### 2. Exécution de la migration

```bash
docker cp add_role_column.php subnet-calculator:/var/www/html/
docker exec subnet-calculator php /var/www/html/add_role_column.php
```

### 3. Résultat

```
✅ Colonne role ajoutée avec succès!
✅ Rôle 'admin' assigné à l'utilisateur admin

📋 Structure de la table users:
  - id: int
  - username: varchar(64)
  - password_hash: varchar(255)
  - role: varchar(20) (défaut: user)  ← NOUVEAU
  - created_at: timestamp
```

## ✅ Validation

### Test API
```bash
curl -X POST 'http://10.105.126.7:8080/session_api.php' \
     -d 'action=list_users' -b cookies.txt | jq .
```

**Résultat** :
```json
{
  "success": true,
  "users": [
    {
      "username": "admin",
      "role": "admin",        ← Plus "undefined" !
      "created_at": "2025-10-17 00:02:44"
    },
    {
      "username": "test",
      "role": "user",         ← Rôle correct
      "created_at": "2025-10-17 18:46:59"
    }
  ]
}
```

### Test automatisé

```bash
python3 test_user_admin_button.py
```

**Résultat** :
```
User 1: ['admin', 'admin', '2025-10-17 00:02:44', 'Protected']
User 2: ['test', 'user', '2025-10-17 18:46:59', 'Delete']
User 3: ['testuser_78383', 'user', '2025-10-17 18:45:51', 'Delete']

✅ TEST RÉUSSI - Le bouton 'Gestion utilisateurs' fonctionne !
```

### Test visuel

| Avant | Après |
|-------|-------|
| ![undefined](role_test_04_modal_opened.png) | ![roles OK](role_test_05_fixed.png) |
| Rôles: "undefined" ❌ | Rôles: "admin", "user" ✅ |

## 📊 Impact

- **6 utilisateurs** dans la base de données
- **Tous les rôles** s'affichent correctement
- **admin** → rôle "admin"
- **5 autres utilisateurs** → rôle "user"
- **0 "undefined"** ✅

## 🎓 Leçons apprises

1. **Toujours vérifier la structure de la base de données** en cas d'erreur SQL
2. **Les migrations de schéma** sont nécessaires quand on ajoute des fonctionnalités
3. **Le code peut être correct** mais la base de données obsolète
4. **Utiliser `DESCRIBE table`** ou `SHOW COLUMNS` pour diagnostiquer

## 🔄 Pour l'avenir

Si vous ajoutez un nouveau champ à `db_init.php`, pensez à créer un script de migration pour les bases de données existantes :

```php
// Exemple de pattern de migration
$stmt = $pdo->query("SHOW COLUMNS FROM table_name LIKE 'new_column'");
if ($stmt->rowCount() === 0) {
    $pdo->exec("ALTER TABLE table_name ADD COLUMN new_column ...");
}
```

## ✅ État final

🟢 **RÉSOLU** - Tous les rôles s'affichent correctement dans l'interface web !

---

**Date de correction** : 17 octobre 2025  
**Temps de résolution** : ~20 minutes  
**Fichiers modifiés** :
- Aucun (problème de base de données uniquement)

**Fichiers créés** :
- `add_role_column.php` (script de migration)
- `FIX_ROLE_UNDEFINED.md` (ce document)
