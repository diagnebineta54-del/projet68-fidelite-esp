<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

exiger_role(['admin']);
$titrePage = "Journal d'audit";

$actionFiltre = $_GET['action_type'] ?? '';
$conditions = [];
$params = [];
if ($actionFiltre !== '') { $conditions[] = "l.action = ?"; $params[] = $actionFiltre; }
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM audit_log l $where");
$stmtTotal->execute($params);
$total = (int)$stmtTotal->fetchColumn();
[$offset, $page, $totalPages] = paginer($total, 30);

$stmt = $pdo->prepare("
    SELECT l.*, u.nom AS utilisateur_nom
    FROM audit_log l LEFT JOIN utilisateurs u ON u.id = l.utilisateur_id
    $where ORDER BY l.date_action DESC LIMIT $offset, 30
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

$actionsDistinctes = $pdo->query("SELECT DISTINCT action FROM audit_log ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);

require __DIR__ . '/includes/header.php';
?>
<form method="get" class="barre-filtres carte">
    <div class="champ">
        <label for="action_type">Type d'action</label>
        <select id="action_type" name="action_type" onchange="this.form.submit()">
            <option value="">Toutes</option>
            <?php foreach ($actionsDistinctes as $a): ?><option value="<?= h($a) ?>" <?= $actionFiltre===$a?'selected':'' ?>><?= h($a) ?></option><?php endforeach; ?>
        </select>
    </div>
</form>

<div class="carte table-wrapper">
    <table class="data">
        <thead><tr><th>Date</th><th>Utilisateur</th><th>Action</th><th>Table</th><th>Enreg. #</th><th>Détails</th><th>IP</th></tr></thead>
        <tbody>
        <?php foreach ($logs as $l): ?>
            <tr>
                <td><?= h(date('d/m/Y H:i:s', strtotime($l['date_action']))) ?></td>
                <td><?= h($l['utilisateur_nom'] ?? 'Système') ?></td>
                <td><?= h($l['action']) ?></td>
                <td><?= h($l['table_concernee']) ?></td>
                <td><?= h($l['enregistrement_id']) ?></td>
                <td><?= h($l['details']) ?></td>
                <td><?= h($l['adresse_ip']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$logs): ?><tr><td colspan="7">Aucune entrée.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<div class="pagination">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="<?= query_avec(['page' => $i]) ?>" class="<?= $i === $page ? 'actif' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
