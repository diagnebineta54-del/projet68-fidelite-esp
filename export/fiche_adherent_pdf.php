<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/SimplePDF.php';

exiger_role(['admin', 'gestionnaire']);

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT a.*, p.nom AS palier_nom, p.multiplicateur, p.avantages FROM adherents a JOIN paliers p ON p.id=a.palier_id WHERE a.id=?");
$stmt->execute([$id]);
$a = $stmt->fetch();
if (!$a) { http_response_code(404); die('Adhérent introuvable.'); }

$stmt = $pdo->prepare("SELECT * FROM transactions_points WHERE adherent_id=? ORDER BY date_transaction DESC LIMIT 25");
$stmt->execute([$id]);
$transactions = $stmt->fetchAll();

journaliser($pdo, 'EXPORT', 'adherents', $id, "Export PDF de la fiche adhérent {$a['prenom']} {$a['nom']}");

$pdf = new SimplePDF();
$pdf->titre('Fidélité ESP — Fiche adhérent');
$pdf->texte('Document généré le ' . date('d/m/Y à H:i'), 9);
$pdf->espace(8);

$pdf->sousTitre($a['prenom'] . ' ' . $a['nom']);
$pdf->texte('Email : ' . $a['email']);
$pdf->texte('Téléphone : ' . ($a['telephone'] ?: '—'));
$pdf->texte('Date d\'adhésion : ' . date('d/m/Y', strtotime($a['date_adhesion'])));
$pdf->texte('Palier actuel : ' . $a['palier_nom'] . ' (multiplicateur x' . $a['multiplicateur'] . ')');
$pdf->texte('Points disponibles : ' . number_format($a['points_disponibles'], 0, ',', ' '));
$pdf->texte('Points cumulés (12 mois) : ' . number_format($a['points_total'], 0, ',', ' '));
$pdf->espace(8);
$pdf->ligneHorizontale();

$pdf->sousTitre('Historique des points (25 dernières transactions)');
$pdf->ligneColonnes([0 => 'Date', 90 => 'Type', 200 => 'Description', 420 => 'Points'], 9, true);
$pdf->ligneHorizontale();
foreach ($transactions as $t) {
    $pdf->ligneColonnes([
        0   => date('d/m/Y', strtotime($t['date_transaction'])),
        90  => $t['type'],
        200 => mb_substr($t['description'] ?? '', 0, 34),
        420 => ($t['points'] >= 0 ? '+' : '') . $t['points'],
    ]);
}

$pdf->sortie('fiche_adherent_' . $a['nom'] . '_' . $id . '.pdf', 'D');
