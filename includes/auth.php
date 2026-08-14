<?php
/**
 * Authentification et contrôle d'accès basé sur les rôles (RBAC)
 * Rôles : admin, gestionnaire, client
 */

function est_connecte() {
    return !empty($_SESSION['utilisateur_id']);
}

/** Bloque l'accès à la page si l'utilisateur n'est pas connecté */
function exiger_connexion() {
    if (!est_connecte()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

/**
 * Bloque l'accès si le rôle de l'utilisateur connecté n'est pas dans la liste autorisée.
 * Exemple : exiger_role(['admin','gestionnaire']);
 */
function exiger_role(array $rolesAutorises) {
    exiger_connexion();
    if (!in_array($_SESSION['role'], $rolesAutorises, true)) {
        http_response_code(403);
        require __DIR__ . '/../403.php';
        exit;
    }
}

function utilisateur_role() {
    return $_SESSION['role'] ?? null;
}

function utilisateur_nom() {
    return $_SESSION['nom'] ?? '';
}
