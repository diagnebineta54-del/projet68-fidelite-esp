<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

exiger_role(['admin', 'gestionnaire']);
$titrePage = 'Paliers de statut';

$paliers = $pdo->query("
    SELECT p.*, (SELECT COUNT(*) FROM adherents a WHERE a.palier_id = p.id AND a.actif = 1) AS nb_adherents
    FROM paliers p ORDER BY p.ordre
")->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<?php if (isset($_GET['maj'])): ?><div class="alerte alerte-succes">Palier mis à jour.</div><?php endif; ?>
<?php if (isset($_GET['ajout'])): ?><div class="alerte alerte-succes">Palier créé.</div><?php endif; ?>
<?php if (isset($_GET['erreur']) && $_GET['erreur']==='utilise'): ?><div class="alerte alerte-erreur">Impossible de supprimer : des adhérents sont rattachés à ce palier.</div><?php endif; ?>

<?php if (utilisateur_role() === 'admin'): ?>
<div class="barre-actions"><div></div><a class="btn btn-primaire" href="ajouter.php">+ Nouveau palier</a></div>
<?php endif; ?>

<div class="grille-graphiques">
<?php foreach ($paliers as $p): ?>
    <div class="carte" style="border-top: 4px solid <?= h($p['couleur']) ?>;">
        <h3><?= h($p['nom']) ?></h3>
        <p><strong>Seuil :</strong> <?= number_format($p['seuil_points'],0,',',' ') ?> points cumulés (12 mois)</p>
        <p><strong>Multiplicateur :</strong> ×<?= $p['multiplicateur'] ?></p>
        <p><strong>Avantages :</strong> <?= h($p['avantages']) ?></p>
        <p><strong>Adhérents dans ce palier :</strong> <?= $p['nb_adherents'] ?></p>
        <a class="btn btn-petit btn-secondaire" href="modifier.php?id=<?= $p['id'] ?>">Modifier</a>
        <?php if (utilisateur_role() === 'admin'): ?>
        <a class="btn btn-petit btn-danger js-confirmer-suppression" href="supprimer.php?id=<?= $p['id'] ?>&csrf=<?= h(csrf_token()) ?>">Supprimer</a>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
