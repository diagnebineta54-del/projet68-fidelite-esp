<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($titrePage) ? h($titrePage) . ' — ' : '' ?><?= APP_NOM ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php if (est_connecte()): ?>
<div class="app-shell">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="app-main">
        <header class="app-topbar">
            <button class="burger" id="burgerBtn" aria-label="Ouvrir le menu">☰</button>
            <h1 class="topbar-titre"><?= h($titrePage ?? '') ?></h1>
            <div class="topbar-user">
                <span class="badge-role badge-<?= h(utilisateur_role()) ?>"><?= h(utilisateur_role()) ?></span>
                <span><?= h(utilisateur_nom()) ?></span>
                <a href="<?= BASE_URL ?>logout.php" class="lien-deconnexion">Déconnexion</a>
            </div>
        </header>
        <main class="app-content">
<?php endif; ?>
