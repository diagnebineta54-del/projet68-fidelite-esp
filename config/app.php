<?php
/**
 * Configuration générale de l'application + démarrage de session sécurisée
 */

// --- Sessions sécurisées ---
ini_set('session.cookie_httponly', 1);   // cookie inaccessible en JS
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');
// Décommentez la ligne suivante si vous déployez le site en HTTPS :
// ini_set('session.cookie_secure', 1);

session_name('FIDELITE_ESP_SESSION');

$DUREE_SESSION_SECONDES = 30 * 60; // expiration automatique après 30 minutes d'inactivité

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Expiration automatique par inactivité
if (isset($_SESSION['derniere_activite']) && (time() - $_SESSION['derniere_activite'] > $DUREE_SESSION_SECONDES)) {
    session_unset();
    session_destroy();
    header('Location: ' . BASE_URL . 'login.php?expire=1');
    exit;
}
$_SESSION['derniere_activite'] = time();

// --- Constantes ---
define('APP_NOM', 'Fidélité ESP');
define('BASE_URL', '/fidelite-app/'); // à adapter selon le dossier XAMPP (htdocs)
define('DEVISE', 'FCFA');
define('POINTS_PAR_1000_FCFA', 10); // 1000 FCFA d'achat = 10 points de base (avant multiplicateur du palier)

error_reporting(E_ALL);
ini_set('display_errors', 0); // ne jamais afficher les erreurs PHP brutes à l'écran en prod
ini_set('log_errors', 1);
