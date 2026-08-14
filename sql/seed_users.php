<?php
/**
 * A exécuter UNE SEULE FOIS dans le navigateur, juste après avoir importé
 * database.sql dans PhpMyAdmin :
 *      http://localhost/fidelite-app/sql/seed_users.php
 *
 * Ce script crée les 4 comptes de démonstration avec un mot de passe
 * correctement haché via password_hash() (impossible à faire de façon
 * fiable directement en SQL).
 *
 * Mot de passe de démo pour TOUS les comptes : Password123!
 */
require_once __DIR__ . '/../config/db.php';

$motDePasseDemo = 'Password123!';
$hash = password_hash($motDePasseDemo, PASSWORD_DEFAULT);

$comptes = [
    ['Administrateur ESP', 'admin@fidelite-esp.sn', 'admin'],
    ['Fatou Gestionnaire', 'gestionnaire@fidelite-esp.sn', 'gestionnaire'],
    ['Moussa Diop', 'moussa.diop@client.sn', 'client'],
    ['Aissatou Ndiaye', 'aissatou.ndiaye@client.sn', 'client'],
];

$stmtCheck = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
$stmtInsert = $pdo->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe, role, actif) VALUES (?, ?, ?, ?, 1)");

echo "<h2>Création des comptes de démonstration</h2><ul>";
foreach ($comptes as [$nom, $email, $role]) {
    $stmtCheck->execute([$email]);
    if ($stmtCheck->fetch()) {
        echo "<li>$email — déjà existant, ignoré.</li>";
        continue;
    }
    $stmtInsert->execute([$nom, $email, $hash, $role]);
    echo "<li>$email — créé avec succès (rôle : $role).</li>";
}
echo "</ul>";

// Lie les comptes clients existants aux adhérents correspondants (par email)
$pdo->exec("
    UPDATE adherents a
    JOIN utilisateurs u ON u.email = a.email
    SET a.utilisateur_id = u.id
    WHERE a.utilisateur_id IS NULL
");

echo "<p><strong>Mot de passe pour tous les comptes : $motDePasseDemo</strong></p>";
echo "<p>Vous pouvez maintenant vous connecter sur <a href='../login.php'>login.php</a>, puis supprimer ce fichier (sql/seed_users.php) par sécurité.</p>";
