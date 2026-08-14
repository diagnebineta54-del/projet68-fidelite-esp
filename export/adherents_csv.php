<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

exiger_role(['admin', 'gestionnaire']);

$recherche = trim($_GET['q'] ?? '');
$palierFiltre = $_GET['palier'] ?? '';
$statutFiltre = $_GET['statut'] ?? '';

$conditions = [];
$params = [];
if ($recherche !== '') {
    $conditions[] = "(a.nom LIKE ? OR a.prenom LIKE ? OR a.email LIKE ? OR a.telephone LIKE ?)";
    $like = "%$recherche%";
    array_push($params, $like, $like, $like, $like);
}
if ($palierFiltre !== '') { $conditions[] = "a.palier_id = ?"; $params[] = $palierFiltre; }
if ($statutFiltre !== '') { $conditions[] = "a.actif = ?"; $params[] = $statutFiltre === 'actif' ? 1 : 0; }
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$stmt = $pdo->prepare("
    SELECT a.nom, a.prenom, a.email, a.telephone, p.nom AS palier, a.points_total, a.points_disponibles,
           a.date_adhesion, a.actif
    FROM adherents a JOIN paliers p ON p.id = a.palier_id
    $where ORDER BY a.nom
");
$stmt->execute($params);
$adherents = $stmt->fetchAll();

journaliser($pdo, 'EXPORT', 'adherents', null, 'Export CSV de la liste des adhérents (' . count($adherents) . ' lignes)');

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="adherents_' . date('Y-m-d') . '.csv"');

$sortie = fopen('php://output', 'w');
fwrite($sortie, "\xEF\xBB\xBF"); // BOM UTF-8 pour compatibilité Excel
fputcsv($sortie, ['Nom', 'Prénom', 'Email', 'Téléphone', 'Palier', 'Points cumulés', 'Points disponibles', 'Date adhésion', 'Statut'], ';');

foreach ($adherents as $a) {
    fputcsv($sortie, [
        $a['nom'], $a['prenom'], $a['email'], $a['telephone'], $a['palier'],
        $a['points_total'], $a['points_disponibles'], date('d/m/Y', strtotime($a['date_adhesion'])),
        $a['actif'] ? 'Actif' : 'Inactif'
    ], ';');
}
fclose($sortie);
exit;
