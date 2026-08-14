<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

exiger_role(['client']);
$titrePage = 'Mes échanges';

$stmt = $pdo->prepare("SELECT id, points_disponibles FROM adherents WHERE utilisateur_id = ?");
$stmt->execute([$_SESSION['utilisateur_id']]);
$adherent = $stmt->fetch();

$echanges = [];
if ($adherent) {
    $stmt = $pdo->prepare("SELECT e.*, r.nom AS recompense_nom FROM echanges e JOIN recompenses r ON r.id = e.recompense_id WHERE e.adherent_id = ? ORDER BY e.date_echange DESC");
    $stmt->execute([$adherent['id']]);
    $echanges = $stmt->fetchAll();
}

require __DIR__ . '/../includes/header.php';
?>
<?php if (isset($_GET['demande'])): ?><div class="alerte alerte-succes">Votre demande d'échange a été enregistrée et est en attente de validation.</div><?php endif; ?>

<div class="carte table-wrapper">
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
        <?php if (!$echanges): ?><tr><td colspan="4">Vous n'avez pas encore demandé d'échange.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
