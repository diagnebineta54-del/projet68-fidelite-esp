<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (est_connecte()) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$erreur = '';
$expire = isset($_GET['expire']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verifie($_POST['csrf_token'] ?? '')) {
        $erreur = "Session expirée, veuillez réessayer.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $motDePasse = $_POST['mot_de_passe'] ?? '';

        // Anti-bruteforce très simple basé sur la session
        $_SESSION['tentatives'] = $_SESSION['tentatives'] ?? 0;

        if ($_SESSION['tentatives'] >= 6) {
            $erreur = "Trop de tentatives échouées. Réessayez dans quelques minutes.";
        } elseif ($email === '' || $motDePasse === '') {
            $erreur = "Veuillez renseigner votre email et votre mot de passe.";
        } else {
            $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $utilisateur = $stmt->fetch();

            if ($utilisateur && $utilisateur['actif'] && password_verify($motDePasse, $utilisateur['mot_de_passe'])) {
                session_regenerate_id(true); // empêche la fixation de session
                $_SESSION['utilisateur_id'] = $utilisateur['id'];
                $_SESSION['nom'] = $utilisateur['nom'];
                $_SESSION['role'] = $utilisateur['role'];
                $_SESSION['tentatives'] = 0;

                $pdo->prepare("UPDATE utilisateurs SET derniere_connexion = NOW() WHERE id = ?")->execute([$utilisateur['id']]);
                journaliser($pdo, 'CONNEXION', 'utilisateurs', $utilisateur['id'], 'Connexion réussie');

                header('Location: ' . BASE_URL . 'dashboard.php');
                exit;
            } else {
                $_SESSION['tentatives']++;
                $erreur = "Email ou mot de passe incorrect.";
                if ($utilisateur) {
                    journaliser($pdo, 'CONNEXION_ECHEC', 'utilisateurs', $utilisateur['id'], 'Mot de passe incorrect');
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Connexion — <?= APP_NOM ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,700&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="page-connexion">
  <div class="carte-connexion">
    <div class="logo-mark">◆</div>
    <h1>Fidélité ESP</h1>
    <p class="sous-titre">Programme de fidélisation — Connexion à la plateforme</p>

    <?php if ($expire): ?><div class="alerte alerte-info">Votre session a expiré, merci de vous reconnecter.</div><?php endif; ?>
    <?php if ($erreur): ?><div class="alerte alerte-erreur"><?= h($erreur) ?></div><?php endif; ?>

    <form method="post" class="js-valider" novalidate>
        <?= csrf_champ() ?>
        <div class="champ">
            <label for="email">Adresse email</label>
            <input type="email" id="email" name="email" required autofocus value="<?= h($_POST['email'] ?? '') ?>">
        </div>
        <div class="champ">
            <label for="mot_de_passe">Mot de passe</label>
            <input type="password" id="mot_de_passe" name="mot_de_passe" required>
        </div>
        <button type="submit" class="btn btn-primaire" style="width:100%;">Se connecter</button>
    </form>

    <div class="comptes-demo">
        <strong>Comptes de démonstration</strong> (mot de passe : <code>Password123!</code>)<br>
        Admin : admin@fidelite-esp.sn<br>
        Gestionnaire : gestionnaire@fidelite-esp.sn<br>
        Client : moussa.diop@client.sn
    </div>
  </div>
</div>
</body>
</html>
