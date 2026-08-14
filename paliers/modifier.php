<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

exiger_role(['admin', 'gestionnaire']);
$titrePage = 'Modifier un palier';
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM paliers WHERE id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) { http_response_code(404); die('Palier introuvable.'); }

$erreurs = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verifie($_POST['csrf_token'] ?? '')) $erreurs[] = "Jeton de sécurité invalide.";
    $seuil = $_POST['seuil_points'] ?? '';
    $multi = $_POST['multiplicateur'] ?? '';
    $avantages = trim($_POST['avantages'] ?? '');

    if (!ctype_digit((string)$seuil)) $erreurs[] = "Le seuil doit être un nombre entier positif.";
    if (!is_numeric($multi) || $multi <= 0) $erreurs[] = "Le multiplicateur doit être un nombre positif.";

    if (!$erreurs) {
        $pdo->prepare("UPDATE paliers SET seuil_points=?, multiplicateur=?, avantages=? WHERE id=?")
            ->execute([$seuil, $multi, $avantages, $id]);
        journaliser($pdo, 'MODIFICATION', 'paliers', $id, "Modification du palier {$p['nom']}");
        header('Location: liste.php?maj=1');
        exit;
    }
    $p = array_merge($p, ['seuil_points'=>$seuil,'multiplicateur'=>$multi,'avantages'=>$avantages]);
}
require __DIR__ . '/../includes/header.php';
?>
<div class="carte" style="max-width:640px;">
    <?php foreach ($erreurs as $e): ?><div class="alerte alerte-erreur"><?= h($e) ?></div><?php endforeach; ?>
    <form method="post" class="js-valider" novalidate>
        <?= csrf_champ() ?>
        <h3><?= h($p['nom']) ?></h3>
        <div class="form-grille">
            <div class="champ"><label for="seuil_points">Seuil de points (12 mois) *</label><input type="number" id="seuil_points" name="seuil_points" min="0" required value="<?= h($p['seuil_points']) ?>"></div>
            <div class="champ"><label for="multiplicateur">Multiplicateur *</label><input type="number" id="multiplicateur" step="0.01" min="0.01" name="multiplicateur" required value="<?= h($p['multiplicateur']) ?>"></div>
        </div>
        <div class="champ"><label for="avantages">Avantages</label><textarea id="avantages" name="avantages" rows="3"><?= h($p['avantages']) ?></textarea></div>
        <button type="submit" class="btn btn-primaire">Enregistrer</button>
        <a href="liste.php" class="btn btn-secondaire">Annuler</a>
    </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
