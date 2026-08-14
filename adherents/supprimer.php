<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

exiger_role(['admin']); // suppression réservée à l'administrateur

$id = (int)($_GET['id'] ?? 0);

if (!csrf_verifie($_GET['csrf'] ?? '')) {
    die('Jeton de sécurité invalide.');
}

$stmt = $pdo->prepare("SELECT * FROM adherents WHERE id = ?");
$stmt->execute([$id]);
$adherent = $stmt->fetch();

if ($adherent) {
    $pdo->prepare("DELETE FROM adherents WHERE id = ?")->execute([$id]);
    journaliser($pdo, 'SUPPRESSION', 'adherents', $id, "Suppression de l'adhérent {$adherent['prenom']} {$adherent['nom']}");
}

header('Location: liste.php?suppr=1');
exit;
