<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

exiger_role(['admin', 'gestionnaire']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verifie($_POST['csrf_token'] ?? '')) {
    header('Location: liste.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

$stmt = $pdo->prepare("SELECT e.*, a.email, a.prenom, r.nom AS recompense_nom, r.id AS recompense_id FROM echanges e JOIN adherents a ON a.id=e.adherent_id JOIN recompenses r ON r.id=e.recompense_id WHERE e.id = ?");
$stmt->execute([$id]);
$echange = $stmt->fetch();

if (!$echange) { header('Location: liste.php'); exit; }

if ($action === 'valider' && $echange['statut'] === 'en_attente') {
    $pdo->prepare("UPDATE echanges SET statut='validee', date_traitement=NOW(), traite_par=? WHERE id=?")
        ->execute([$_SESSION['utilisateur_id'], $id]);
    $pdo->prepare("UPDATE recompenses SET stock = GREATEST(0, stock - 1) WHERE id = ?")->execute([$echange['recompense_id']]);
    envoyer_email($echange['email'], 'Échange validé', "<p>Bonjour {$echange['prenom']},</p><p>Votre échange pour « {$echange['recompense_nom']} » a été validé.</p>");
    journaliser($pdo, 'VALIDATION', 'echanges', $id, "Échange validé");
} elseif ($action === 'refuser' && $echange['statut'] === 'en_attente') {
    $pdo->prepare("UPDATE echanges SET statut='refusee', date_traitement=NOW(), traite_par=? WHERE id=?")
        ->execute([$_SESSION['utilisateur_id'], $id]);
    recalculer_soldes($pdo, $echange['adherent_id']); // les points redeviennent disponibles
    envoyer_email($echange['email'], 'Échange refusé', "<p>Bonjour {$echange['prenom']},</p><p>Votre demande d'échange pour « {$echange['recompense_nom']} » a été refusée. Vos points restent disponibles.</p>");
    journaliser($pdo, 'REFUS', 'echanges', $id, "Échange refusé");
} elseif ($action === 'livrer' && $echange['statut'] === 'validee') {
    $pdo->prepare("UPDATE echanges SET statut='livree', date_traitement=NOW(), traite_par=? WHERE id=?")
        ->execute([$_SESSION['utilisateur_id'], $id]);
    journaliser($pdo, 'LIVRAISON', 'echanges', $id, "Récompense livrée");
}

header('Location: liste.php?traite=1');
exit;
