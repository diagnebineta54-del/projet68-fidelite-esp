<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

exiger_role(['admin', 'gestionnaire']);
$titrePage = 'Modifier un adhérent';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM adherents WHERE id = ?");
$stmt->execute([$id]);
$adherent = $stmt->fetch();
if (!$adherent) { http_response_code(404); die('Adhérent introuvable.'); }

$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verifie($_POST['csrf_token'] ?? '')) {
        $erreurs[] = "Jeton de sécurité invalide.";
    }
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $dateNaissance = $_POST['date_naissance'] ?? '';
    $actif = isset($_POST['actif']) ? 1 : 0;
    $optIn = isset($_POST['opt_in_rgpd']) ? 1 : 0;

    if ($nom === '') $erreurs[] = "Le nom est obligatoire.";
    if ($prenom === '') $erreurs[] = "Le prénom est obligatoire.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erreurs[] = "Email invalide.";

    if (!$erreurs) {
        $stmt = $pdo->prepare("SELECT id FROM adherents WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetch()) $erreurs[] = "Cet email est déjà utilisé par un autre adhérent.";
    }

    if (!$erreurs) {
        $pdo->prepare("
            UPDATE adherents SET nom=?, prenom=?, email=?, telephone=?, date_naissance=?, actif=?, opt_in_rgpd=?
            WHERE id = ?
        ")->execute([$nom, $prenom, $email, $telephone ?: null, $dateNaissance ?: null, $actif, $optIn, $id]);

        journaliser($pdo, 'MODIFICATION', 'adherents', $id, "Modification de la fiche adhérent");
        header('Location: voir.php?id=' . $id . '&maj=1');
        exit;
    }
    $adherent = array_merge($adherent, compact('nom','prenom','email','telephone','actif'));
}

require __DIR__ . '/../includes/header.php';
?>
<div class="carte" style="max-width:720px;">
    <?php foreach ($erreurs as $e): ?><div class="alerte alerte-erreur"><?= h($e) ?></div><?php endforeach; ?>
    <form method="post" class="js-valider" novalidate>
        <?= csrf_champ() ?>
        <div class="form-grille">
            <div class="champ"><label for="nom">Nom *</label><input type="text" id="nom" name="nom" required value="<?= h($adherent['nom']) ?>"></div>
            <div class="champ"><label for="prenom">Prénom *</label><input type="text" id="prenom" name="prenom" required value="<?= h($adherent['prenom']) ?>"></div>
            <div class="champ"><label for="email">Email *</label><input type="email" id="email" name="email" required value="<?= h($adherent['email']) ?>"></div>
            <div class="champ"><label for="telephone">Téléphone</label><input type="text" id="telephone" name="telephone" value="<?= h($adherent['telephone']) ?>"></div>
            <div class="champ"><label for="date_naissance">Date de naissance</label><input type="date" id="date_naissance" name="date_naissance" value="<?= h($adherent['date_naissance']) ?>"></div>
        </div>
        <div class="champ" style="margin-top:.5rem;">
            <label style="display:flex; align-items:center; gap:.5rem; font-weight:400;">
                <input type="checkbox" name="actif" style="width:auto;" <?= $adherent['actif'] ? 'checked' : '' ?>> Adhérent actif
            </label>
        </div>
        <div class="champ">
            <label style="display:flex; align-items:center; gap:.5rem; font-weight:400;">
                <input type="checkbox" name="opt_in_rgpd" style="width:auto;" <?= $adherent['opt_in_rgpd'] ? 'checked' : '' ?>> Consentement RGPD / CDP
            </label>
        </div>
        <button type="submit" class="btn btn-primaire">Enregistrer les modifications</button>
        <a href="voir.php?id=<?= $id ?>" class="btn btn-secondaire">Annuler</a>
    </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
