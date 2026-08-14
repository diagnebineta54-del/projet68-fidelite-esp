<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

exiger_role(['admin', 'gestionnaire']);
$titrePage = 'Attribuer des points';

$adherents = $pdo->query("SELECT a.id, a.nom, a.prenom, p.multiplicateur FROM adherents a JOIN paliers p ON p.id = a.palier_id WHERE a.actif = 1 ORDER BY a.nom")->fetchAll();
$adherentIdPreselectionne = (int)($_GET['adherent_id'] ?? 0);

$erreurs = [];
$valeurs = ['adherent_id' => $adherentIdPreselectionne, 'type' => 'achat', 'montant_achat' => '', 'points_manuels' => '', 'description' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verifie($_POST['csrf_token'] ?? '')) $erreurs[] = "Jeton de sécurité invalide.";

    $valeurs['adherent_id'] = (int)($_POST['adherent_id'] ?? 0);
    $valeurs['type'] = $_POST['type'] ?? 'achat';
    $valeurs['montant_achat'] = $_POST['montant_achat'] ?? '';
    $valeurs['points_manuels'] = $_POST['points_manuels'] ?? '';
    $valeurs['description'] = trim($_POST['description'] ?? '');

    if (!$valeurs['adherent_id']) $erreurs[] = "Veuillez sélectionner un adhérent.";
    if (!in_array($valeurs['type'], ['achat','bonus_anniversaire','parrainage','ajustement','expiration'])) $erreurs[] = "Type de transaction invalide.";

    $stmtA = $pdo->prepare("SELECT a.*, p.multiplicateur FROM adherents a JOIN paliers p ON p.id = a.palier_id WHERE a.id = ?");
    $stmtA->execute([$valeurs['adherent_id']]);
    $adherent = $stmtA->fetch();
    if (!$adherent) $erreurs[] = "Adhérent introuvable.";

    $points = 0;
    if (!$erreurs) {
        if ($valeurs['type'] === 'achat') {
            $montant = (float)str_replace(',', '.', $valeurs['montant_achat']);
            if ($montant <= 0) $erreurs[] = "Le montant de l'achat doit être positif.";
            else $points = (int)round(($montant / 1000) * POINTS_PAR_1000_FCFA * $adherent['multiplicateur']);
        } elseif ($valeurs['type'] === 'ajustement') {
            $points = (int)($valeurs['points_manuels'] ?: 0);
            if ($points === 0) $erreurs[] = "Veuillez indiquer un nombre de points (positif ou négatif).";
        } else {
            $points = (int)($valeurs['points_manuels'] ?: 0);
            if ($points <= 0) $erreurs[] = "Veuillez indiquer un nombre de points positif.";
        }
    }

    if (!$erreurs) {
        $stmt = $pdo->prepare("
            INSERT INTO transactions_points (adherent_id, type, points, montant_achat, description, cree_par)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $valeurs['adherent_id'], $valeurs['type'], $points,
            $valeurs['type'] === 'achat' ? (float)str_replace(',', '.', $valeurs['montant_achat']) : null,
            $valeurs['description'] ?: ucfirst($valeurs['type']),
            $_SESSION['utilisateur_id']
        ]);
        $transId = $pdo->lastInsertId();

        recalculer_soldes($pdo, $valeurs['adherent_id']);
        journaliser($pdo, 'CREATION', 'transactions_points', $transId, "$points points ({$valeurs['type']}) pour l'adhérent #{$valeurs['adherent_id']}");

        // Notification email à l'adhérent (si email valide)
        envoyer_email($adherent['email'], 'Points de fidélité crédités',
            "<p>Bonjour {$adherent['prenom']},</p><p>Vous venez de recevoir <strong>$points points</strong> sur votre compte fidélité.</p>");

        header('Location: liste.php?ajout=1');
        exit;
    }
}

require __DIR__ . '/../includes/header.php';
?>
<div class="carte" style="max-width:640px;">
    <?php foreach ($erreurs as $e): ?><div class="alerte alerte-erreur"><?= h($e) ?></div><?php endforeach; ?>

    <form method="post" class="js-valider" novalidate id="formPoints">
        <?= csrf_champ() ?>
        <div class="champ">
            <label for="adherent_id">Adhérent *</label>
            <select id="adherent_id" name="adherent_id" required>
                <option value="">— Choisir —</option>
                <?php foreach ($adherents as $a): ?>
                    <option value="<?= $a['id'] ?>" <?= $valeurs['adherent_id'] == $a['id'] ? 'selected' : '' ?>><?= h($a['prenom'] . ' ' . $a['nom']) ?> (×<?= $a['multiplicateur'] ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="champ">
            <label for="type">Type de transaction *</label>
            <select id="type" name="type" onchange="basculerChamps()">
                <option value="achat" <?= $valeurs['type']==='achat'?'selected':'' ?>>Achat (calcul automatique)</option>
                <option value="bonus_anniversaire" <?= $valeurs['type']==='bonus_anniversaire'?'selected':'' ?>>Bonus anniversaire</option>
                <option value="parrainage" <?= $valeurs['type']==='parrainage'?'selected':'' ?>>Parrainage</option>
                <option value="ajustement" <?= $valeurs['type']==='ajustement'?'selected':'' ?>>Ajustement manuel (+/-)</option>
            </select>
        </div>
        <div class="champ" id="champMontant">
            <label for="montant_achat">Montant de l'achat (FCFA) *</label>
            <input type="number" id="montant_achat" name="montant_achat" min="1" step="1" value="<?= h($valeurs['montant_achat']) ?>">
            <div class="aide">Barème : <?= POINTS_PAR_1000_FCFA ?> points / 1 000 FCFA, multiplié par le coefficient du palier de l'adhérent.</div>
        </div>
        <div class="champ" id="champPointsManuels" style="display:none;">
            <label for="points_manuels">Nombre de points *</label>
            <input type="number" id="points_manuels" name="points_manuels" step="1" value="<?= h($valeurs['points_manuels']) ?>">
        </div>
        <div class="champ">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="2"><?= h($valeurs['description']) ?></textarea>
        </div>
        <button type="submit" class="btn btn-primaire">Enregistrer la transaction</button>
        <a href="liste.php" class="btn btn-secondaire">Annuler</a>
    </form>
</div>
<script>
function basculerChamps() {
    var type = document.getElementById('type').value;
    document.getElementById('champMontant').style.display = (type === 'achat') ? 'block' : 'none';
    document.getElementById('champPointsManuels').style.display = (type === 'achat') ? 'none' : 'block';
}
basculerChamps();
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
