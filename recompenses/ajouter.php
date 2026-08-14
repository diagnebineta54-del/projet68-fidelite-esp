<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

exiger_role(['admin', 'gestionnaire']);
$titrePage = 'Nouvelle récompense';
$erreurs = [];
$v = ['nom'=>'','description'=>'','categorie'=>'','cout_points'=>'','stock'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verifie($_POST['csrf_token'] ?? '')) $erreurs[] = "Jeton de sécurité invalide.";
    foreach ($v as $k => $_) $v[$k] = trim($_POST[$k] ?? '');

    if ($v['nom'] === '') $erreurs[] = "Le nom est obligatoire.";
    if (!ctype_digit($v['cout_points']) || (int)$v['cout_points'] <= 0) $erreurs[] = "Le coût en points doit être un entier positif.";
    if (!ctype_digit($v['stock']) || (int)$v['stock'] < 0) $erreurs[] = "Le stock doit être un entier positif ou nul.";

    if (!$erreurs) {
        $stmt = $pdo->prepare("INSERT INTO recompenses (nom, description, categorie, cout_points, stock, actif) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->execute([$v['nom'], $v['description'], $v['categorie'], $v['cout_points'], $v['stock']]);
        journaliser($pdo, 'CREATION', 'recompenses', $pdo->lastInsertId(), "Création de la récompense {$v['nom']}");
        header('Location: liste.php?ajout=1');
        exit;
    }
}
require __DIR__ . '/../includes/header.php';
?>
<div class="carte" style="max-width:640px;">
    <?php foreach ($erreurs as $e): ?><div class="alerte alerte-erreur"><?= h($e) ?></div><?php endforeach; ?>
    <form method="post" class="js-valider" novalidate>
        <?= csrf_champ() ?>
        <div class="champ"><label for="nom">Nom *</label><input type="text" id="nom" name="nom" required value="<?= h($v['nom']) ?>"></div>
        <div class="champ"><label for="description">Description</label><textarea id="description" name="description" rows="3"><?= h($v['description']) ?></textarea></div>
        <div class="form-grille">
            <div class="champ"><label for="categorie">Catégorie</label><input type="text" id="categorie" name="categorie" value="<?= h($v['categorie']) ?>" placeholder="Bon d'achat, Goodies, Voyage…"></div>
            <div class="champ"><label for="cout_points">Coût en points *</label><input type="number" id="cout_points" name="cout_points" min="1" required value="<?= h($v['cout_points']) ?>"></div>
            <div class="champ"><label for="stock">Stock *</label><input type="number" id="stock" name="stock" min="0" required value="<?= h($v['stock']) ?>"></div>
        </div>
        <button type="submit" class="btn btn-primaire">Enregistrer</button>
        <a href="liste.php" class="btn btn-secondaire">Annuler</a>
    </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
