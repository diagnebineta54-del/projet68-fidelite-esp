<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

exiger_role(['admin', 'gestionnaire']);
$titrePage = 'Transactions de points';

$recherche = trim($_GET['q'] ?? '');
$typeFiltre = $_GET['type'] ?? '';

$conditions = [];
$params = [];
if ($recherche !== '') {
    $conditions[] = "(a.nom LIKE ? OR a.prenom LIKE ?)";
    $params[] = "%$recherche%"; $params[] = "%$recherche%";
}
if ($typeFiltre !== '') {
    $conditions[] = "t.type = ?";
    $params[] = $typeFiltre;
}
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM transactions_points t JOIN adherents a ON a.id = t.adherent_id $where");
$stmtTotal->execute($params);
$total = (int)$stmtTotal->fetchColumn();
[$offset, $page, $totalPages] = paginer($total, 20);

$stmt = $pdo->prepare("
    SELECT t.*, a.nom, a.prenom
    FROM transactions_points t JOIN adherents a ON a.id = t.adherent_id
    $where
    ORDER BY t.date_transaction DESC
    LIMIT $offset, 20
");
$stmt->execute($params);
$transactions = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="barre-actions">
    <div><?= $total ?> transaction(s)</div>
    <a class="btn btn-primaire" href="ajouter.php">+ Attribuer des points</a>
</div>

<form method="get" class="barre-filtres carte">
    <div class="champ"><label for="q">Adhérent</label><input type="text" id="q" name="q" value="<?= h($recherche) ?>" placeholder="Nom ou prénom"></div>
    <div class="champ">
        <label for="type">Type</label>
        <select id="type" name="type">
            <option value="">Tous types</option>
            <?php foreach (['achat','bonus_anniversaire','parrainage','ajustement','expiration'] as $t): ?>
                <option value="<?= $t ?>" <?= $typeFiltre === $t ? 'selected' : '' ?>><?= h($t) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn btn-or">Filtrer</button>
    <a href="liste.php" class="btn btn-secondaire">Réinitialiser</a>
</form>

<div class="carte table-wrapper">
    <table class="data">
        <thead><tr><th>Date</th><th>Adhérent</th><th>Type</th><th>Description</th><th>Montant achat</th><th>Points</th></tr></thead>
        <tbody>
        <?php foreach ($transactions as $t): ?>
            <tr>
                <td><?= h(date('d/m/Y H:i', strtotime($t['date_transaction']))) ?></td>
                <td><?= h($t['prenom'] . ' ' . $t['nom']) ?></td>
                <td><?= h($t['type']) ?></td>
                <td><?= h($t['description']) ?></td>
                <td><?= $t['montant_achat'] ? number_format($t['montant_achat'],0,',',' ') . ' ' . DEVISE : '—' ?></td>
                <td style="color: <?= $t['points'] >= 0 ? '#3E7A55' : '#A6423A' ?>; font-weight:600;"><?= $t['points'] >= 0 ? '+' : '' ?><?= $t['points'] ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$transactions): ?><tr><td colspan="6">Aucune transaction trouvée.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<div class="pagination">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="<?= query_avec(['page' => $i]) ?>" class="<?= $i === $page ? 'actif' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
