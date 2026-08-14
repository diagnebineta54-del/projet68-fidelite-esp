<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

exiger_role(['admin', 'gestionnaire']);
$id = (int)($_GET['id'] ?? 0);
if (!csrf_verifie($_GET['csrf'] ?? '')) die('Jeton de sécurité invalide.');

$stmt = $pdo->prepare("SELECT nom FROM recompenses WHERE id = ?");
$stmt->execute([$id]);
$nom = $stmt->fetchColumn();

if ($nom) {
    $pdo->prepare("DELETE FROM recompenses WHERE id = ?")->execute([$id]);
    journaliser($pdo, 'SUPPRESSION', 'recompenses', $id, "Suppression de la récompense $nom");
}
header('Location: liste.php?suppr=1');
exit;
