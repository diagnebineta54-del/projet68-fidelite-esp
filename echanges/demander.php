<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

exiger_role(['client']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verifie($_POST['csrf_token'] ?? '')) {
    header('Location: ../recompenses/liste.php?erreur=jeton');
    exit;
}

$recompenseId = (int)($_POST['recompense_id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM adherents WHERE utilisateur_id = ?");
$stmt->execute([$_SESSION['utilisateur_id']]);
$adherent = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM recompenses WHERE id = ? AND actif = 1");
$stmt->execute([$recompenseId]);
$recompense = $stmt->fetch();

if (!$adherent || !$recompense) {
    header('Location: ../recompenses/liste.php?erreur=introuvable');
    exit;
}

if ($adherent['points_disponibles'] < $recompense['cout_points']) {
    header('Location: ../recompenses/liste.php?erreur=solde');
    exit;
}
if ($recompense['stock'] < 1) {
    header('Location: ../recompenses/liste.php?erreur=stock');
    exit;
}

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("INSERT INTO echanges (adherent_id, recompense_id, points_utilises, statut) VALUES (?, ?, ?, 'en_attente')");
    $stmt->execute([$adherent['id'], $recompenseId, $recompense['cout_points']]);
    $echangeId = $pdo->lastInsertId();

    recalculer_soldes($pdo, $adherent['id']);
    journaliser($pdo, 'CREATION', 'echanges', $echangeId, "Demande d'échange : {$recompense['nom']} ({$recompense['cout_points']} points)");

    $pdo->commit();
    header('Location: ../echanges/mes-echanges.php?demande=1');
} catch (Exception $e) {
    $pdo->rollBack();
    header('Location: ../recompenses/liste.php?erreur=technique');
}
exit;
