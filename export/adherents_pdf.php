<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/SimplePDF.php';

exiger_role(['admin', 'gestionnaire']);

$recherche = trim($_GET['q'] ?? '');
$palierFiltre = $_GET['palier'] ?? '';
$statutFiltre = $_GET['statut'] ?? '';

$conditions = [];
$params = [];
if ($recherche !== '') {
    $conditions[] = "(a.nom LIKE ? OR a.prenom LIKE ? OR a.email LIKE ?)";
    $like = "%$recherche%"; array_push($params, $like, $like, $like);
}
if ($palierFiltre !== '') { $conditions[] = "a.palier_id = ?"; $params[] = $palierFiltre; }
if ($statutFiltre !== '') { $conditions[] = "a.actif = ?"; $params[] = $statutFiltre === 'actif' ? 1 : 0; }
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$stmt = $pdo->prepare("
    SELECT a.nom, a.prenom, a.email, p.nom AS palier, a.points_disponibles, a.date_adhesion, a.actif
    FROM adherents a JOIN paliers p ON p.id = a.palier_id
    $where ORDER BY a.nom
");
$stmt->execute($params);
$adherents = $stmt->fetchAll();

journaliser($pdo, 'EXPORT', 'adherents', null, 'Export PDF de la liste des adhérents (' . count($adherents) . ' lignes)');

$pdf = new SimplePDF();
$pdf->titre('Fidélité ESP — Liste des adhérents');
$pdf->texte('Document généré le ' . date('d/m/Y à H:i'), 9);
$pdf->espace(6);
$pdf->ligneHorizontale();

$pdf->ligneColonnes([0 => 'Nom', 90 => 'Prénom', 170 => 'Email', 330 => 'Palier', 420 => 'Points', 470 => 'Adhésion'], 9, true);
$pdf->ligneHorizontale();

foreach ($adherents as $a) {
    $pdf->ligneColonnes([
        0   => mb_substr($a['nom'], 0, 15),
        90  => mb_substr($a['prenom'], 0, 12),
        170 => mb_substr($a['email'], 0, 26),
        330 => $a['palier'],
        420 => number_format($a['points_disponibles'], 0, ',', ' '),
        470 => date('d/m/Y', strtotime($a['date_adhesion'])),
    ]);
}

$pdf->espace(10);
$pdf->texte('Total : ' . count($adherents) . ' adhérent(s).', 9);

$pdf->sortie('liste_adherents_' . date('Y-m-d') . '.pdf', 'D');
