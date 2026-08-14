<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

exiger_role(['client']);
$titrePage = 'Mon compte';

$stmt = $pdo->prepare("
    SELECT a.*, p.nom AS palier_nom, p.couleur, p.multiplicateur, p.avantages, p.seuil_points
    FROM adherents a JOIN paliers p ON p.id = a.palier_id
    WHERE a.utilisateur_id = ?
");
$stmt->execute([$_SESSION['utilisateur_id']]);
$adherent = $stmt->fetch();

if (!$adherent) {
    require __DIR__ . '/includes/header.php';
    echo '<div class="alerte alerte-info">Aucune fiche adhérent n\'est encore associée à votre compte. Contactez votre gestionnaire.</div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

// Palier suivant
$stmt = $pdo->prepare("SELECT * FROM paliers WHERE seuil_points > ? ORDER BY seuil_points ASC LIMIT 1");
$stmt->execute([$adherent['seuil_points']]);
$palierSuivant = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM transactions_points WHERE adherent_id = ? ORDER BY date_transaction DESC LIMIT 15");
$stmt->execute([$adherent['id']]);
$transactions = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT e.*, r.nom AS recompense_nom FROM echanges e
    JOIN recompenses r ON r.id = e.recompense_id
    WHERE e.adherent_id = ? ORDER BY e.date_echange DESC LIMIT 10
");
$stmt->execute([$adherent['id']]);
$echanges = $stmt->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="grille-kpi">
    <div class="kpi"><div class="valeur"><?= number_format($adherent['points_disponibles'],0,',',' ') ?></div><div class="libelle">Points disponibles</div></div>
    <div class="kpi"><div class="valeur"><?= number_format($adherent['points_total'],0,',',' ') ?></div><div class="libelle">Points cumulés (12 mois)</div></div>
    <div class="kpi"><div class="valeur"><span class="pastille pastille-<?= strtolower(h($adherent['palier_nom'])) ?>"><?= h($adherent['palier_nom']) ?></span></div><div class="libelle">Palier actuel</div></div>
    <div class="kpi"><div class="valeur"><?= $adherent['multiplicateur'] ?>×</div><div class="libelle">Multiplicateur de points</div></div>
</div>

<div class="carte">
    <h3>Mes avantages — palier <?= h($adherent['palier_nom']) ?></h3>
    <p><?= h($adherent['avantages']) ?></p>
    <?php if ($palierSuivant): ?>
        <p class="aide">Encore <strong><?= number_format($palierSuivant['seuil_points'] - $adherent['points_total'],0,',',' ') ?> points</strong> pour atteindre le palier <strong><?= h($palierSuivant['nom']) ?></strong>.</p>
    <?php else: ?>
        <p class="aide">Vous avez atteint le palier le plus élevé du programme. Bravo !</p>
    <?php endif; ?>
    <a href="<?= BASE_URL ?>recompenses/liste.php" class="btn btn-or">Voir le catalogue de récompenses</a>
</div>

<div class="carte">
    <h3>Mes 15 dernières transactions de points</h3>
    <div class="table-wrapper">
    <table class="data">
        <thead><tr><th>Date</th><th>Type</th><th>Description</th><th>Points</th></tr></thead>
        <tbody>
        <?php foreach ($transactions as $t): ?>
            <tr>
                <td><?= h(date('d/m/Y', strtotime($t['date_transaction']))) ?></td>
                <td><?= h($t['type']) ?></td>
                <td><?= h($t['description']) ?></td>
                <td style="color: <?= $t['points'] >= 0 ? '#3E7A55' : '#A6423A' ?>; font-weight:600;"><?= $t['points'] >= 0 ? '+' : '' ?><?= $t['points'] ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$transactions): ?><tr><td colspan="4">Aucune transaction pour le moment.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<div class="carte">
    <h3>Mes échanges de récompenses</h3>
    <div class="table-wrapper">
    <table class="data">
        <thead><tr><th>Date</th><th>Récompense</th><th>Points utilisés</th><th>Statut</th></tr></thead>
        <tbody>
        <?php foreach ($echanges as $e): ?>
            <tr>
                <td><?= h(date('d/m/Y', strtotime($e['date_echange']))) ?></td>
                <td><?= h($e['recompense_nom']) ?></td>
                <td><?= $e['points_utilises'] ?></td>
                <td><span class="pastille pastille-<?= $e['statut']==='refusee'?'refus':($e['statut']==='en_attente'?'attente':'succes') ?>"><?= h($e['statut']) ?></span></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$echanges): ?><tr><td colspan="4">Aucun échange pour le moment.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
