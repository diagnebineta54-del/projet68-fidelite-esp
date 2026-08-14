<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Accès refusé</title>
<link rel="stylesheet" href="/fidelite-app/assets/css/style.css"></head>
<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;background:#F7F4EE;">
  <div class="carte" style="max-width:420px;text-align:center;">
    <h1>403 — Accès refusé</h1>
    <p>Vous n'avez pas les droits nécessaires pour accéder à cette page.</p>
    <a class="btn btn-primaire" href="/fidelite-app/dashboard.php">Retour au tableau de bord</a>
  </div>
</body>
</html>
