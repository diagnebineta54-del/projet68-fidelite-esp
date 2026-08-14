<?php
/**
 * Fonctions utilitaires partagées par toute l'application
 */

/** Echappe une chaîne pour affichage HTML (protection XSS) */
function h($chaine) {
    return htmlspecialchars($chaine ?? '', ENT_QUOTES, 'UTF-8');
}

/** Génère (ou récupère) un jeton CSRF pour la session en cours */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Vérifie le jeton CSRF envoyé par un formulaire */
function csrf_verifie($jetonRecu) {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$jetonRecu);
}

/** Champ caché CSRF prêt à l'emploi pour un <form> */
function csrf_champ() {
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

/**
 * Enregistre une action dans le journal d'audit (table audit_log)
 */
function journaliser(PDO $pdo, $action, $table, $enregistrementId = null, $details = '') {
    $utilisateurId = $_SESSION['utilisateur_id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'inconnu';
    $stmt = $pdo->prepare("INSERT INTO audit_log (utilisateur_id, action, table_concernee, enregistrement_id, details, adresse_ip)
                            VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$utilisateurId, $action, $table, $enregistrementId, $details, $ip]);
}

/**
 * Recalcule le palier d'un adhérent en fonction de ses points cumulés
 * sur les 12 derniers mois glissants (règle métier du programme).
 */
function recalculer_palier(PDO $pdo, $adherentId) {
    // Points gagnés sur les 12 derniers mois (hors expirations négatives)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(points), 0) AS total
        FROM transactions_points
        WHERE adherent_id = ?
          AND date_transaction >= (NOW() - INTERVAL 12 MONTH)
          AND points > 0
    ");
    $stmt->execute([$adherentId]);
    $pointsGlissants = (int)$stmt->fetch()['total'];

    // Choix du palier le plus élevé dont le seuil est atteint
    $stmt = $pdo->prepare("SELECT id FROM paliers WHERE seuil_points <= ? ORDER BY seuil_points DESC LIMIT 1");
    $stmt->execute([$pointsGlissants]);
    $palier = $stmt->fetch();
    $palierId = $palier ? (int)$palier['id'] : 1;

    $pdo->prepare("UPDATE adherents SET palier_id = ? WHERE id = ?")->execute([$palierId, $adherentId]);

    return $palierId;
}

/**
 * Recalcule les soldes points_total et points_disponibles d'un adhérent
 * à partir de l'historique des transactions et des échanges.
 */
function recalculer_soldes(PDO $pdo, $adherentId) {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(points),0) AS total FROM transactions_points WHERE adherent_id = ? AND points > 0");
    $stmt->execute([$adherentId]);
    $totalGagne = (int)$stmt->fetch()['total'];

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(points),0) AS total FROM transactions_points WHERE adherent_id = ?");
    $stmt->execute([$adherentId]);
    $soldeBrut = (int)$stmt->fetch()['total'];

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(points_utilises),0) AS total FROM echanges WHERE adherent_id = ? AND statut IN ('en_attente','validee','livree')");
    $stmt->execute([$adherentId]);
    $pointsEchanges = (int)$stmt->fetch()['total'];

    $disponible = max(0, $soldeBrut - $pointsEchanges);

    $pdo->prepare("UPDATE adherents SET points_total = ?, points_disponibles = ? WHERE id = ?")
        ->execute([$totalGagne, $disponible, $adherentId]);

    recalculer_palier($pdo, $adherentId);
}

/** Pagination simple : retourne [offset, page, totalPages] */
function paginer($totalLignes, $parPage = 20) {
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $totalPages = max(1, (int)ceil($totalLignes / $parPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $parPage;
    return [$offset, $page, $totalPages];
}

/** Construit une chaîne de requête GET en conservant les filtres existants */
function query_avec($params) {
    $actuel = $_GET;
    foreach ($params as $k => $v) {
        $actuel[$k] = $v;
    }
    return '?' . http_build_query($actuel);
}

/** Envoi d'un email simple (utilise mail() ; à remplacer par PHPMailer/SMTP en production) */
function envoyer_email($destinataire, $sujet, $messageHtml) {
    $entetes = "MIME-Version: 1.0\r\n";
    $entetes .= "Content-type:text/html;charset=UTF-8\r\n";
    $entetes .= "From: Fidélité ESP <no-reply@fidelite-esp.sn>\r\n";
    // NB : sur un XAMPP local sans serveur SMTP configuré, mail() renverra généralement
    // false. Voir README.md pour configurer sendmail/SMTP (ex : Mailtrap, PHPMailer).
    return @mail($destinataire, $sujet, $messageHtml, $entetes);
}
