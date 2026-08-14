<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

exiger_role(['admin', 'gestionnaire']);
$titrePage = 'Modifier une récompense';
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM recompenses WHERE id = ?");
$stmt->execute([$id]);
$r = $stmt->fetch();
if (!$r) { http_response_code(404); die('Récompense introuvable.'); }

$erreurs = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verifie($_POST['csrf_token'] ?? '')) $erreurs[] = "Jeton de sécurité invalide.";
    $nom = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $categorie = trim($_POST['categorie'] ?? '');
    $cout = $_POST['cout_points'] ?? '';
    $stock = $_POST['stock'] ?? '';
    $actif = isset($_POST['actif']) ? 1 : 0;

    if ($nom === '') $erreurs[] = "Le nom est obligatoire.";
    if (!ctype_digit((string)$cout) || (int)$cout <= 0) $erreurs[] = "Coût en points invalide.";
    if (!ctype_digit((string)$stock) || (int)$stock < 0) $erreurs[] = "Stock invalide.";

    if (!$erreurs) {
        $pdo->prepare("UPDATE recompenses SET nom=?, description=?, categorie=?, cout_points=?, stock=?, actif=? WHERE id=?")
            ->execute([$nom, $description, $categorie, $cout, $stock, $actif, $id]);
        journaliser($pdo, 'MODIFICATION', 'recompenses', $id, "Modification de la récompense $nom");
        header('Location: liste.php?maj=1');
        exit;
    }
    $r = array_merge($r, compact('nom','description','categorie','actif')) + ['cout_points'=>$cout,'stock'=>$stock];
}
require __DIR__ . '/../includes/header.php';
?>
<div class="carte" style="max-width:640px;">
    <?php foreach ($erreurs as $e): ?><div class="alerte alerte-erreur"><?= h($e) ?></div><?php endforeach; ?>
    <form method="post" class="js-valider" novalidate>
        <?= csrf_champ() ?>
        <div class="champ"><label for="nom">Nom *</label><input type="text" id="nom" name="nom" required value="<?= h($r['nom']) ?>"></div>
        <div class="champ"><label for="description">Description</label><textarea id="description" name="description" rows="3"><?= h($r['description']) ?></textarea></div>
        <div class="form-grille">
            <div class="champ"><label for="categorie">Catégorie</label><input type="text" id="categorie" name="categorie" value="<?= h($r['categorie']) ?>"></div>
            <div class="champ"><label for="cout_points">Coût en points *</label><input type="number" id="cout_points" name="cout_points" min="1" required value="<?= h($r['cout_points']) ?>"></div>
            <div class="champ"><label for="stock">Stock *</label><input type="number" id="stock" name="stock" min="0" required value="<?= h($r['stock']) ?>"></div>
        </div>
        <div class="champ"><label style="display:flex;align-items:center;gap:.5rem;font-weight:400;"><input type="checkbox" name="actif" style="width:auto;" <?= $r['actif']?'checked':'' ?>> Récompense active dans le catalogue</label></div>
        <button type="submit" class="btn btn-primaire">Enregistrer</button>
        <a href="liste.php" class="btn btn-secondaire">Annuler</a>
    </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
