<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

exiger_connexion();

if (utilisateur_role() === 'client') {
    header('Location: ' . BASE_URL . 'mon-compte.php');
    exit;
}

$titrePage = 'Tableau de bord';

// --- KPI ---
$nbAdherents = $pdo->query("SELECT COUNT(*) FROM adherents WHERE actif = 1")->fetchColumn();
$pointsDistribues = $pdo->query("SELECT COALESCE(SUM(points),0) FROM transactions_points WHERE points > 0")->fetchColumn();
$pointsEnCirculation = $pdo->query("SELECT COALESCE(SUM(points_disponibles),0) FROM adherents")->fetchColumn();
$echangesEnAttente = $pdo->query("SELECT COUNT(*) FROM echanges WHERE statut = 'en_attente'")->fetchColumn();

// --- Répartition par palier ---
$repartitionPaliers = $pdo->query("
    SELECT p.nom, p.couleur, COUNT(a.id) AS nb
    FROM paliers p
    LEFT JOIN adherents a ON a.palier_id = p.id AND a.actif = 1
    GROUP BY p.id, p.nom, p.couleur
    ORDER BY p.ordre
")->fetchAll();

// --- Evolution mensuelle des points distribués (12 derniers mois) ---
$evolution = $pdo->query("
    SELECT DATE_FORMAT(date_transaction, '%Y-%m') AS mois, SUM(points) AS total
    FROM transactions_points
    WHERE points > 0 AND date_transaction >= (NOW() - INTERVAL 12 MONTH)
    GROUP BY mois
    ORDER BY mois
")->fetchAll();

// --- Top 5 adhérents ---
$topAdherents = $pdo->query("
    SELECT nom, prenom, points_total FROM adherents WHERE actif = 1
    ORDER BY points_total DESC LIMIT 5
")->fetchAll();

// --- Récompenses les plus échangées ---
$topRecompenses = $pdo->query("
    SELECT r.nom, COUNT(e.id) AS nb
    FROM echanges e JOIN recompenses r ON r.id = e.recompense_id
    GROUP BY r.id, r.nom ORDER BY nb DESC LIMIT 5
")->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="grille-kpi">
    <div class="kpi"><div class="valeur"><?= number_format($nbAdherents, 0, ',', ' ') ?></div><div class="libelle">Adhérents actifs</div></div>
    <div class="kpi"><div class="valeur"><?= number_format($pointsDistribues, 0, ',', ' ') ?></div><div class="libelle">Points distribués (total)</div></div>
    <div class="kpi"><div class="valeur"><?= number_format($pointsEnCirculation, 0, ',', ' ') ?></div><div class="libelle">Points en circulation</div></div>
    <div class="kpi"><div class="valeur"><?= number_format($echangesEnAttente, 0, ',', ' ') ?></div><div class="libelle">Échanges en attente</div></div>
</div>

<div class="grille-graphiques">
    <div class="carte">
        <h3>Répartition des adhérents par palier</h3>
        <canvas id="graphPaliers" height="220"></canvas>
    </div>
    <div class="carte">
        <h3>Évolution mensuelle des points distribués</h3>
        <canvas id="graphEvolution" height="220"></canvas>
    </div>
    <div class="carte">
        <h3>Top 5 adhérents (points cumulés)</h3>
        <canvas id="graphTopAdherents" height="220"></canvas>
    </div>
    <div class="carte">
        <h3>Récompenses les plus demandées</h3>
        <canvas id="graphTopRecompenses" height="220"></canvas>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
const couleursMarque = ['#2E1A47', '#C9A15A', '#4A2F6E', '#8C5A3B', '#9E9E9E'];

new Chart(document.getElementById('graphPaliers'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($repartitionPaliers, 'nom')) ?>,
        datasets: [{
            data: <?= json_encode(array_map('intval', array_column($repartitionPaliers, 'nb'))) ?>,
            backgroundColor: <?= json_encode(array_column($repartitionPaliers, 'couleur')) ?>
        }]
    },
    options: { plugins: { legend: { position: 'bottom' } } }
});

new Chart(document.getElementById('graphEvolution'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($evolution, 'mois')) ?>,
        datasets: [{
            label: 'Points distribués',
            data: <?= json_encode(array_map('intval', array_column($evolution, 'total'))) ?>,
            borderColor: '#2E1A47',
            backgroundColor: 'rgba(201,161,90,.25)',
            tension: .3, fill: true
        }]
    },
    options: { plugins: { legend: { display: false } } }
});

new Chart(document.getElementById('graphTopAdherents'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_map(fn($a) => $a['prenom'] . ' ' . $a['nom'], $topAdherents)) ?>,
        datasets: [{ label: 'Points cumulés', data: <?= json_encode(array_map('intval', array_column($topAdherents, 'points_total'))) ?>, backgroundColor: '#C9A15A' }]
    },
    options: { indexAxis: 'y', plugins: { legend: { display: false } } }
});

new Chart(document.getElementById('graphTopRecompenses'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($topRecompenses, 'nom')) ?>,
        datasets: [{ label: "Nombre d'échanges", data: <?= json_encode(array_map('intval', array_column($topRecompenses, 'nb'))) ?>, backgroundColor: '#4A2F6E' }]
    },
    options: { plugins: { legend: { display: false } } }
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
