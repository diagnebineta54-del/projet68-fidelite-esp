<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

exiger_role(['admin', 'gestionnaire']);
$titrePage = 'Nouvel adhérent';

$erreurs = [];
$valeurs = ['nom'=>'','prenom'=>'','email'=>'','telephone'=>'','date_naissance'=>'','date_adhesion'=>date('Y-m-d'),'opt_in_rgpd'=>0,'parraine_par'=>''];

$parrains = $pdo->query("SELECT id, nom, prenom FROM adherents WHERE actif = 1 ORDER BY nom")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verifie($_POST['csrf_token'] ?? '')) {
        $erreurs[] = "Jeton de sécurité invalide, veuillez réessayer.";
    }
    $valeurs['nom'] = trim($_POST['nom'] ?? '');
    $valeurs['prenom'] = trim($_POST['prenom'] ?? '');
    $valeurs['email'] = trim($_POST['email'] ?? '');
    $valeurs['telephone'] = trim($_POST['telephone'] ?? '');
    $valeurs['date_naissance'] = $_POST['date_naissance'] ?? '';
    $valeurs['date_adhesion'] = $_POST['date_adhesion'] ?? date('Y-m-d');
    $valeurs['opt_in_rgpd'] = isset($_POST['opt_in_rgpd']) ? 1 : 0;
    $valeurs['parraine_par'] = $_POST['parraine_par'] ?: null;

    // --- Validation côté serveur (indispensable, ne jamais faire confiance au JS) ---
    if ($valeurs['nom'] === '' || mb_strlen($valeurs['nom']) > 100) $erreurs[] = "Le nom est obligatoire (100 caractères max).";
    if ($valeurs['prenom'] === '' || mb_strlen($valeurs['prenom']) > 100) $erreurs[] = "Le prénom est obligatoire (100 caractères max).";
    if (!filter_var($valeurs['email'], FILTER_VALIDATE_EMAIL)) $erreurs[] = "L'adresse email est invalide.";
    if ($valeurs['telephone'] !== '' && !preg_match('/^[0-9+ ]{7,20}$/', $valeurs['telephone'])) $erreurs[] = "Le téléphone est invalide.";
    if (!empty($erreurs) === false) {
        $stmt = $pdo->prepare("SELECT id FROM adherents WHERE email = ?");
        $stmt->execute([$valeurs['email']]);
        if ($stmt->fetch()) $erreurs[] = "Un adhérent existe déjà avec cet email.";
    }

    if (!$erreurs) {
        $stmt = $pdo->prepare("
            INSERT INTO adherents (nom, prenom, email, telephone, date_naissance, date_adhesion, palier_id, points_total, points_disponibles, parraine_par, opt_in_rgpd, actif)
            VALUES (?, ?, ?, ?, ?, ?, 1, 0, 0, ?, ?, 1)
        ");
        $stmt->execute([
            $valeurs['nom'], $valeurs['prenom'], $valeurs['email'], $valeurs['telephone'] ?: null,
            $valeurs['date_naissance'] ?: null, $valeurs['date_adhesion'], $valeurs['parraine_par'], $valeurs['opt_in_rgpd']
        ]);
        $nouvelId = $pdo->lastInsertId();

        // Bonus de parrainage automatique (300 points) si applicable
        if ($valeurs['parraine_par']) {
            $pdo->prepare("INSERT INTO transactions_points (adherent_id, type, points, description, cree_par) VALUES (?, 'parrainage', 300, ?, ?)")
                ->execute([$valeurs['parraine_par'], 'Parrainage de ' . $valeurs['prenom'] . ' ' . $valeurs['nom'], $_SESSION['utilisateur_id']]);
            recalculer_soldes($pdo, $valeurs['parraine_par']);
        }

        journaliser($pdo, 'CREATION', 'adherents', $nouvelId, "Création de l'adhérent {$valeurs['prenom']} {$valeurs['nom']}");

        header('Location: liste.php?ajout=1');
        exit;
    }
}

require __DIR__ . '/../includes/header.php';
?>

<div class="carte" style="max-width:720px;">
    <?php foreach ($erreurs as $e): ?><div class="alerte alerte-erreur"><?= h($e) ?></div><?php endforeach; ?>

    <form method="post" class="js-valider" novalidate>
        <?= csrf_champ() ?>
        <div class="form-grille">
            <div class="champ"><label for="nom">Nom *</label><input type="text" id="nom" name="nom" required maxlength="100" value="<?= h($valeurs['nom']) ?>"></div>
            <div class="champ"><label for="prenom">Prénom *</label><input type="text" id="prenom" name="prenom" required maxlength="100" value="<?= h($valeurs['prenom']) ?>"></div>
            <div class="champ"><label for="email">Email *</label><input type="email" id="email" name="email" required value="<?= h($valeurs['email']) ?>"></div>
            <div class="champ"><label for="telephone">Téléphone</label><input type="text" id="telephone" name="telephone" placeholder="77 123 45 67" value="<?= h($valeurs['telephone']) ?>"></div>
            <div class="champ"><label for="date_naissance">Date de naissance</label><input type="date" id="date_naissance" name="date_naissance" value="<?= h($valeurs['date_naissance']) ?>"></div>
            <div class="champ"><label for="date_adhesion">Date d'adhésion *</label><input type="date" id="date_adhesion" name="date_adhesion" required value="<?= h($valeurs['date_adhesion']) ?>"></div>
            <div class="champ">
                <label for="parraine_par">Parrainé par</label>
                <select id="parraine_par" name="parraine_par">
                    <option value="">Aucun parrain</option>
                    <?php foreach ($parrains as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= h($p['prenom'] . ' ' . $p['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="aide">Attribue automatiquement 300 points de bonus au parrain.</div>
            </div>
        </div>
        <div class="champ" style="margin-top:.5rem;">
            <label style="display:flex; align-items:center; gap:.5rem; font-weight:400;">
                <input type="checkbox" name="opt_in_rgpd" style="width:auto;" <?= $valeurs['opt_in_rgpd'] ? 'checked' : '' ?>>
                L'adhérent consent à la collecte de ses données personnelles (conformité Loi 2008-12 / CDP)
            </label>
        </div>
        <button type="submit" class="btn btn-primaire">Enregistrer l'adhérent</button>
        <a href="liste.php" class="btn btn-secondaire">Annuler</a>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
