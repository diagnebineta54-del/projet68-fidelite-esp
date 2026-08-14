<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

exiger_connexion();
$titrePage = 'Catalogue de récompenses';
$role = utilisateur_role();

$recherche = trim($_GET['q'] ?? '');
$categorieFiltre = $_GET['categorie'] ?? '';

$conditions = ['actif = 1'];
$params = [];
if ($role !== 'client') { array_shift($conditions); $conditions = []; } // staff voit aussi les récompenses désactivées
if ($recherche !== '') { $conditions[] = "nom LIKE ?"; $params[] = "%$recherche%"; }
if ($categorieFiltre !== '') { $conditions[] = "categorie = ?"; $params[] = $categorieFiltre; }
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM recompenses $where");
$stmtTotal->execute($params);
$total = (int)$stmtTotal->fetchColumn();
[$offset, $page, $totalPages] = paginer($total, 20);

$stmt = $pdo->prepare("SELECT * FROM recompenses $where ORDER BY cout_points ASC LIMIT $offset, 20");
$stmt->execute($params);
$recompenses = $stmt->fetchAll();

$categories = $pdo->query("SELECT DISTINCT categorie FROM recompenses ORDER BY categorie")->fetchAll(PDO::FETCH_COLUMN);

$soldeClient = 0;
if ($role === 'client') {
    $stmt = $pdo->prepare("SELECT points_disponibles FROM adherents WHERE utilisateur_id = ?");
    $stmt->execute([$_SESSION['utilisateur_id']]);
    $soldeClient = (int)($stmt->fetchColumn() ?: 0);
}

require __DIR__ . '/../includes/header.php';
?>

<?php if ($role === 'client'): ?>
<div class="alerte alerte-info">Votre solde de points disponibles : <strong><?= number_format($soldeClient,0,',',' ') ?> points</strong></div>
<?php endif; ?>

<div class="barre-actions">
    <div><?= $total ?> récompense(s)</div>
    <?php if (in_array($role, ['admin','gestionnaire'])): ?>
        <a class="btn btn-primaire" href="ajouter.php">+ Nouvelle récompense</a>
    <?php endif; ?>
</div>

<form method="get" class="barre-filtres carte">
    <div class="champ"><label for="q">Recherche</label><input type="text" id="q" name="q" value="<?= h($recherche) ?>"></div>
    <div class="champ">
        <label for="categorie">Catégorie</label>
        <select id="categorie" name="categorie">
            <option value="">Toutes</option>
            <?php foreach ($categories as $c): ?><option value="<?= h($c) ?>" <?= $categorieFiltre===$c?'selected':'' ?>><?= h($c) ?></option><?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn btn-or">Filtrer</button>
</form>

<div class="grille-graphiques">
<?php foreach ($recompenses as $r): ?>
    <div class="carte">
        <span class="pastille pastille-attente"><?= h($r['categorie']) ?></span>
        <h3 style="margin-top:.5rem;"><?= h($r['nom']) ?></h3>
        <p><?= h($r['description']) ?></p>
        <p><strong><?= number_format($r['cout_points'],0,',',' ') ?> points</strong> — Stock : <?= (int)$r['stock'] ?>
            <?php if (!$r['actif']): ?> <span class="pastille pastille-refus">Inactive</span><?php endif; ?>
        </p>
        <?php if ($role === 'client'): ?>
            <form method="post" action="../echanges/demander.php">
                <?= csrf_champ() ?>
                <input type="hidden" name="recompense_id" value="<?= $r['id'] ?>">
                <button type="submit" class="btn btn-or" <?= ($soldeClient < $r['cout_points'] || $r['stock'] < 1) ? 'disabled' : '' ?>>
                    <?= $soldeClient < $r['cout_points'] ? 'Points insuffisants' : ($r['stock'] < 1 ? 'Rupture de stock' : 'Échanger mes points') ?>
                </button>
            </form>
        <?php else: ?>
            <a class="btn btn-petit btn-secondaire" href="modifier.php?id=<?= $r['id'] ?>">Modifier</a>
            <a class="btn btn-petit btn-danger js-confirmer-suppression" href="supprimer.php?id=<?= $r['id'] ?>&csrf=<?= h(csrf_token()) ?>">Supprimer</a>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
<?php if (!$recompenses): ?><p>Aucune récompense disponible.</p><?php endif; ?>
</div>

<div class="pagination">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="<?= query_avec(['page' => $i]) ?>" class="<?= $i === $page ? 'actif' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
