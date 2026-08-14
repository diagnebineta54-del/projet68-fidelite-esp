<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

exiger_role(['admin']);
$titrePage = 'Nouveau palier';
$erreurs = [];
$v = ['nom'=>'','seuil_points'=>'','multiplicateur'=>'1.00','avantages'=>'','couleur'=>'#999999','ordre'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verifie($_POST['csrf_token'] ?? '')) $erreurs[] = "Jeton de sécurité invalide.";
    foreach ($v as $k => $_) $v[$k] = trim($_POST[$k] ?? '');

    if ($v['nom'] === '') $erreurs[] = "Le nom est obligatoire.";
    if (!ctype_digit($v['seuil_points'])) $erreurs[] = "Le seuil doit être un entier positif.";
    if (!is_numeric($v['multiplicateur']) || $v['multiplicateur'] <= 0) $erreurs[] = "Multiplicateur invalide.";
    if (!ctype_digit($v['ordre'])) $erreurs[] = "L'ordre doit être un entier.";

    if (!$erreurs) {
        $pdo->prepare("INSERT INTO paliers (nom, seuil_points, multiplicateur, avantages, couleur, ordre) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute([$v['nom'], $v['seuil_points'], $v['multiplicateur'], $v['avantages'], $v['couleur'], $v['ordre']]);
        journaliser($pdo, 'CREATION', 'paliers', $pdo->lastInsertId(), "Création du palier {$v['nom']}");
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
        <div class="form-grille">
            <div class="champ"><label for="nom">Nom *</label><input type="text" id="nom" name="nom" required value="<?= h($v['nom']) ?>"></div>
            <div class="champ"><label for="ordre">Ordre d'affichage *</label><input type="number" id="ordre" name="ordre" min="1" required value="<?= h($v['ordre']) ?>"></div>
            <div class="champ"><label for="seuil_points">Seuil de points *</label><input type="number" id="seuil_points" name="seuil_points" min="0" required value="<?= h($v['seuil_points']) ?>"></div>
            <div class="champ"><label for="multiplicateur">Multiplicateur *</label><input type="number" id="multiplicateur" step="0.01" min="0.01" name="multiplicateur" required value="<?= h($v['multiplicateur']) ?>"></div>
            <div class="champ"><label for="couleur">Couleur (badge)</label><input type="color" id="couleur" name="couleur" value="<?= h($v['couleur']) ?>"></div>
        </div>
        <div class="champ"><label for="avantages">Avantages</label><textarea id="avantages" name="avantages" rows="3"><?= h($v['avantages']) ?></textarea></div>
        <button type="submit" class="btn btn-primaire">Enregistrer</button>
        <a href="liste.php" class="btn btn-secondaire">Annuler</a>
    </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
