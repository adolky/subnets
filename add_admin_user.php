<?php
// Script pour ajouter un utilisateur admin
require_once 'db_init.php';

$username = 'admin'; // à personnaliser
$password = 'admin123'; // à personnaliser

$database = new SubnetDatabase(null, true);
$db = $database->getConnection();

// Vérifier si l'utilisateur existe déjà
$stmt = $db->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    echo "Utilisateur déjà existant : $username\n";
    exit(0);
}

// Hash du mot de passe
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Insertion
$stmt = $db->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
$stmt->execute([$username, $password_hash]);

if ($stmt->rowCount() > 0) {
    echo "Utilisateur admin ajouté avec succès : $username\n";
} else {
    echo "Erreur lors de l'ajout de l'utilisateur.\n";
}
