<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

exiger_role(['admin', 'gestionnaire']);
$titrePage = 'Échanges / demandes';

$statutFiltre = $_GET['statut'] ?? '';
$conditions = [];
$params = [];
if ($statutFiltre !== '') { $conditions[] = "e.statut = ?"; $params[] = $statutFiltre; }
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM echanges e $where");
$stmtTotal->execute($params);
$total = (int)$stmtTotal->fetchColumn();
[$offset, $page, $totalPages] = paginer($total, 20);

$stmt = $pdo->prepare("
    SELECT e.*, a.nom, a.prenom, r.nom AS recompense_nom, r.stock
    FROM echanges e
    JOIN adherents a ON a.id = e.adherent_id
    JOIN recompenses r ON r.id = e.recompense_id
    $where
    ORDER BY e.date_echange DESC
    LIMIT $offset, 20
");
$stmt->execute($params);
$echanges = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<?php if (isset($_GET['traite'])): ?><div class="alerte alerte-succes">Demande traitée avec succès.</div><?php endif; ?>

<form method="get" class="barre-filtres carte">
    <div class="champ">
        <label for="statut">Statut</label>
        <select id="statut" name="statut" onchange="this.form.submit()">
            <option value="">Tous</option>
            <?php foreach (['en_attente'=>'En attente','validee'=>'Validée','refusee'=>'Refusée','livree'=>'Livrée'] as $k=>$l): ?>
                <option value="<?= $k ?>" <?= $statutFiltre===$k?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<div class="carte table-wrapper">
    <table class="data">
        <thead><tr><th>Date</th><th>Adhérent</th><th>Récompense</th><th>Points</th><th>Statut</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($echanges as $e): ?>
            <tr>
                <td><?= h(date('d/m/Y H:i', strtotime($e['date_echange']))) ?></td>
                <td><?= h($e['prenom'] . ' ' . $e['nom']) ?></td>
                <td><?= h($e['recompense_nom']) ?></td>
                <td><?= $e['points_utilises'] ?></td>
                <td><span class="pastille pastille-<?= $e['statut']==='refusee'?'refus':($e['statut']==='en_attente'?'attente':'succes') ?>"><?= h($e['statut']) ?></span></td>
                <td style="white-space:nowrap;">
                    <?php if ($e['statut'] === 'en_attente'): ?>
                        <form method="post" action="traiter.php" style="display:inline;">
                            <?= csrf_champ() ?>
                            <input type="hidden" name="id" value="<?= $e['id'] ?>">
                            <button type="submit" name="action" value="valider" class="btn btn-petit btn-primaire">Valider</button>
                            <button type="submit" name="action" value="refuser" class="btn btn-petit btn-danger">Refuser</button>
                        </form>
                    <?php elseif ($e['statut'] === 'validee'): ?>
                        <form method="post" action="traiter.php" style="display:inline;">
                            <?= csrf_champ() ?>
                            <input type="hidden" name="id" value="<?= $e['id'] ?>">
                            <button type="submit" name="action" value="livrer" class="btn btn-petit btn-or">Marquer livrée</button>
                        </form>
                    <?php else: ?>—<?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$echanges): ?><tr><td colspan="6">Aucune demande.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<div class="pagination">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="<?= query_avec(['page' => $i]) ?>" class="<?= $i === $page ? 'actif' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
