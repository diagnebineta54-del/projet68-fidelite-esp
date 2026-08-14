<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

exiger_role(['admin', 'gestionnaire']);
$titrePage = 'Liste des adhérents';

// --- Filtres ---
$recherche = trim($_GET['q'] ?? '');
$palierFiltre = $_GET['palier'] ?? '';
$statutFiltre = $_GET['statut'] ?? '';

$conditions = [];
$params = [];

if ($recherche !== '') {
    $conditions[] = "(a.nom LIKE ? OR a.prenom LIKE ? OR a.email LIKE ? OR a.telephone LIKE ?)";
    $like = "%$recherche%";
    array_push($params, $like, $like, $like, $like);
}
if ($palierFiltre !== '') {
    $conditions[] = "a.palier_id = ?";
    $params[] = $palierFiltre;
}
if ($statutFiltre !== '') {
    $conditions[] = "a.actif = ?";
    $params[] = $statutFiltre === 'actif' ? 1 : 0;
}

$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

// --- Total pour pagination ---
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM adherents a $where");
$stmtTotal->execute($params);
$total = (int)$stmtTotal->fetchColumn();

[$offset, $page, $totalPages] = paginer($total, 20);

$sql = "
    SELECT a.*, p.nom AS palier_nom, p.couleur
    FROM adherents a JOIN paliers p ON p.id = a.palier_id
    $where
    ORDER BY a.date_creation DESC
    LIMIT $offset, 20
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$adherents = $stmt->fetchAll();

$paliers = $pdo->query("SELECT * FROM paliers ORDER BY ordre")->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<div class="barre-actions">
    <div><?= $total ?> adhérent(s) trouvé(s)</div>
    <div style="display:flex; gap:.5rem;">
        <a class="btn btn-secondaire" href="../export/adherents_csv.php?<?= http_build_query($_GET) ?>">Exporter CSV</a>
        <a class="btn btn-secondaire" href="../export/adherents_pdf.php?<?= http_build_query($_GET) ?>">Exporter PDF</a>
        <a class="btn btn-primaire" href="ajouter.php">+ Nouvel adhérent</a>
    </div>
</div>

<form method="get" class="barre-filtres carte">
    <div class="champ">
        <label for="q">Recherche</label>
        <input type="text" id="q" name="q" placeholder="Nom, prénom, email, téléphone…" value="<?= h($recherche) ?>">
    </div>
    <div class="champ">
        <label for="palier">Palier</label>
        <select id="palier" name="palier">
            <option value="">Tous les paliers</option>
            <?php foreach ($paliers as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $palierFiltre == $p['id'] ? 'selected' : '' ?>><?= h($p['nom']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="champ">
        <label for="statut">Statut</label>
        <select id="statut" name="statut">
            <option value="">Tous</option>
            <option value="actif" <?= $statutFiltre === 'actif' ? 'selected' : '' ?>>Actif</option>
            <option value="inactif" <?= $statutFiltre === 'inactif' ? 'selected' : '' ?>>Inactif</option>
        </select>
    </div>
    <button type="submit" class="btn btn-or">Filtrer</button>
    <a href="liste.php" class="btn btn-secondaire">Réinitialiser</a>
</form>

<div class="carte table-wrapper">
    <table class="data">
        <thead>
            <tr>
                <th>Nom complet</th><th>Email</th><th>Téléphone</th><th>Palier</th>
                <th>Points disponibles</th><th>Adhésion</th><th>Statut</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($adherents as $a): ?>
            <tr>
                <td><?= h($a['prenom'] . ' ' . $a['nom']) ?></td>
                <td><?= h($a['email']) ?></td>
                <td><?= h($a['telephone']) ?></td>
                <td><span class="pastille pastille-<?= strtolower(h($a['palier_nom'])) ?>"><?= h($a['palier_nom']) ?></span></td>
                <td><?= number_format($a['points_disponibles'],0,',',' ') ?></td>
                <td><?= h(date('d/m/Y', strtotime($a['date_adhesion']))) ?></td>
                <td><?= $a['actif'] ? '<span class="pastille pastille-succes">Actif</span>' : '<span class="pastille pastille-refus">Inactif</span>' ?></td>
                <td style="white-space:nowrap;">
                    <a class="btn btn-petit btn-secondaire" href="voir.php?id=<?= $a['id'] ?>">Voir</a>
                    <a class="btn btn-petit btn-secondaire" href="modifier.php?id=<?= $a['id'] ?>">Modifier</a>
                    <?php if (utilisateur_role() === 'admin'): ?>
                    <a class="btn btn-petit btn-danger js-confirmer-suppression" href="supprimer.php?id=<?= $a['id'] ?>&csrf=<?= h(csrf_token()) ?>">Suppr.</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$adherents): ?><tr><td colspan="8">Aucun adhérent ne correspond à ces critères.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<div class="pagination">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="<?= query_avec(['page' => $i]) ?>" class="<?= $i === $page ? 'actif' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
