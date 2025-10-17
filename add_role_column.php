#!/usr/bin/env php
<?php
/**
 * Script pour ajouter la colonne role à la table users si elle n'existe pas
 */

$dsn = "mysql:host=mysql;dbname=subnets;charset=utf8mb4";
$pdo = new PDO($dsn, "subnets_user", "change_this_password");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Vérifier si la colonne role existe
$stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
$exists = $stmt->rowCount() > 0;

if (!$exists) {
    echo "Ajout de la colonne role...\n";
    $pdo->exec("ALTER TABLE users ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'user' AFTER password_hash");
    echo "✅ Colonne role ajoutée avec succès!\n";
    
    // Mettre à jour l'utilisateur admin pour avoir le rôle admin
    $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE username = 'admin'");
    $stmt->execute();
    echo "✅ Rôle 'admin' assigné à l'utilisateur admin\n";
} else {
    echo "✅ La colonne role existe déjà\n";
}

// Afficher la structure
echo "\n📋 Structure de la table users:\n";
$stmt = $pdo->query("DESCRIBE users");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
    $default = $col["Default"] ?: "NULL";
    echo "  - {$col["Field"]}: {$col["Type"]} (NULL={$col["Null"]}, défaut: {$default})\n";
}

// Afficher les utilisateurs
echo "\n👥 Utilisateurs:\n";
$stmt = $pdo->query("SELECT username, role, created_at FROM users");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $user) {
    echo "  - {$user['username']} ({$user['role']}) - créé le {$user['created_at']}\n";
}

echo "\n✅ Terminé!\n";
?>
