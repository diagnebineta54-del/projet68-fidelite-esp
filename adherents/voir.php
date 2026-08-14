<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

exiger_role(['admin', 'gestionnaire']);

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT a.*, p.nom AS palier_nom, p.multiplicateur, p.avantages FROM adherents a JOIN paliers p ON p.id = a.palier_id WHERE a.id = ?");
$stmt->execute([$id]);
$adherent = $stmt->fetch();
if (!$adherent) { http_response_code(404); die('Adhérent introuvable.'); }

$titrePage = 'Fiche adhérent — ' . $adherent['prenom'] . ' ' . $adherent['nom'];

$stmt = $pdo->prepare("SELECT * FROM transactions_points WHERE adherent_id = ? ORDER BY date_transaction DESC LIMIT 20");
$stmt->execute([$id]);
$transactions = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT e.*, r.nom AS recompense_nom FROM echanges e JOIN recompenses r ON r.id = e.recompense_id WHERE e.adherent_id = ? ORDER BY e.date_echange DESC LIMIT 20");
$stmt->execute([$id]);
$echanges = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<?php if (isset($_GET['maj'])): ?><div class="alerte alerte-succes">Fiche mise à jour avec succès.</div><?php endif; ?>

<div class="barre-actions">
    <div>
        <span class="pastille pastille-<?= strtolower(h($adherent['palier_nom'])) ?>"><?= h($adherent['palier_nom']) ?></span>
        <?= $adherent['actif'] ? '<span class="pastille pastille-succes">Actif</span>' : '<span class="pastille pastille-refus">Inactif</span>' ?>
    </div>
    <div style="display:flex; gap:.5rem;">
        <a class="btn btn-secondaire" href="../export/fiche_adherent_pdf.php?id=<?= $id ?>">Exporter la fiche en PDF</a>
        <a class="btn btn-primaire" href="modifier.php?id=<?= $id ?>">Modifier</a>
        <a class="btn btn-secondaire" href="liste.php">← Retour à la liste</a>
    </div>
</div>

<div class="grille-kpi">
    <div class="kpi"><div class="valeur"><?= number_format($adherent['points_disponibles'],0,',',' ') ?></div><div class="libelle">Points disponibles</div></div>
    <div class="kpi"><div class="valeur"><?= number_format($adherent['points_total'],0,',',' ') ?></div><div class="libelle">Points cumulés (12 mois)</div></div>
    <div class="kpi"><div class="valeur"><?= $adherent['multiplicateur'] ?>×</div><div class="libelle">Multiplicateur</div></div>
    <div class="kpi"><div class="valeur"><?= h(date('d/m/Y', strtotime($adherent['date_adhesion']))) ?></div><div class="libelle">Date d'adhésion</div></div>
</div>

<div class="carte">
    <h3>Informations personnelles</h3>
    <p><strong>Nom complet :</strong> <?= h($adherent['prenom'] . ' ' . $adherent['nom']) ?></p>
    <p><strong>Email :</strong> <?= h($adherent['email']) ?></p>
    <p><strong>Téléphone :</strong> <?= h($adherent['telephone'] ?: '—') ?></p>
    <p><strong>Date de naissance :</strong> <?= $adherent['date_naissance'] ? h(date('d/m/Y', strtotime($adherent['date_naissance']))) : '—' ?></p>
    <p><strong>Consentement RGPD/CDP :</strong> <?= $adherent['opt_in_rgpd'] ? 'Oui' : 'Non' ?></p>
</div>

<div class="carte">
    <div class="barre-actions"><h3 style="margin:0;">Historique des points</h3><a href="../transactions/ajouter.php?adherent_id=<?= $id ?>" class="btn btn-petit btn-or">+ Attribuer des points</a></div>
    <div class="table-wrapper">
    <table class="data">
        <thead><tr><th>Date</th><th>Type</th><th>Description</th><th>Points</th></tr></thead>
        <tbody>
        <?php foreach ($transactions as $t): ?>
            <tr>
                <td><?= h(date('d/m/Y H:i', strtotime($t['date_transaction']))) ?></td>
                <td><?= h($t['type']) ?></td>
                <td><?= h($t['description']) ?></td>
                <td style="color: <?= $t['points'] >= 0 ? '#3E7A55' : '#A6423A' ?>; font-weight:600;"><?= $t['points'] >= 0 ? '+' : '' ?><?= $t['points'] ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$transactions): ?><tr><td colspan="4">Aucune transaction.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<div class="carte">
    <h3>Historique des échanges</h3>
    <div class="table-wrapper">
    <table class="data">
        <thead><tr><th>Date</th><th>Récompense</th><th>Points</th><th>Statut</th></tr></thead>
        <tbody>
        <?php foreach ($echanges as $e): ?>
            <tr>
                <td><?= h(date('d/m/Y', strtotime($e['date_echange']))) ?></td>
                <td><?= h($e['recompense_nom']) ?></td>
                <td><?= $e['points_utilises'] ?></td>
                <td><span class="pastille pastille-<?= $e['statut']==='refusee'?'refus':($e['statut']==='en_attente'?'attente':'succes') ?>"><?= h($e['statut']) ?></span></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$echanges): ?><tr><td colspan="4">Aucun échange.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
