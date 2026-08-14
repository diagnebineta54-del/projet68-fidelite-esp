<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

exiger_role(['admin']);
$id = (int)($_GET['id'] ?? 0);
if (!csrf_verifie($_GET['csrf'] ?? '')) die('Jeton de sécurité invalide.');

$stmt = $pdo->prepare("SELECT COUNT(*) FROM adherents WHERE palier_id = ?");
$stmt->execute([$id]);
if ($stmt->fetchColumn() > 0) {
    header('Location: liste.php?erreur=utilise');
    exit;
}

$stmt = $pdo->prepare("SELECT nom FROM paliers WHERE id = ?");
$stmt->execute([$id]);
$nom = $stmt->fetchColumn();
if ($nom) {
    $pdo->prepare("DELETE FROM paliers WHERE id = ?")->execute([$id]);
    journaliser($pdo, 'SUPPRESSION', 'paliers', $id, "Suppression du palier $nom");
}
header('Location: liste.php?suppr=1');
exit;
