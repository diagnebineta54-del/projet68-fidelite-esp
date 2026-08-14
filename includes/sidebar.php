<?php $role = utilisateur_role(); $page = basename($_SERVER['PHP_SELF']); ?>
<nav class="app-sidebar" id="appSidebar">
    <div class="sidebar-logo">
        <span class="logo-mark">◆</span>
        <span class="logo-texte">Fidélité<strong>ESP</strong></span>
    </div>
    <ul class="sidebar-nav">
        <li><a href="<?= BASE_URL ?>dashboard.php" class="<?= $page === 'dashboard.php' ? 'actif' : '' ?>">Tableau de bord</a></li>

        <?php if (in_array($role, ['admin','gestionnaire'])): ?>
        <li class="sidebar-groupe">Adhérents</li>
        <li><a href="<?= BASE_URL ?>adherents/liste.php" class="<?= strpos($_SERVER['REQUEST_URI'],'adherents')!==false ? 'actif' : '' ?>">Liste des adhérents</a></li>
        <li><a href="<?= BASE_URL ?>adherents/ajouter.php">Nouvel adhérent</a></li>

        <li class="sidebar-groupe">Points</li>
        <li><a href="<?= BASE_URL ?>transactions/liste.php" class="<?= strpos($_SERVER['REQUEST_URI'],'transactions')!==false ? 'actif' : '' ?>">Transactions de points</a></li>
        <li><a href="<?= BASE_URL ?>transactions/ajouter.php">Attribuer des points</a></li>

        <li class="sidebar-groupe">Récompenses</li>
        <li><a href="<?= BASE_URL ?>recompenses/liste.php" class="<?= strpos($_SERVER['REQUEST_URI'],'recompenses')!==false ? 'actif' : '' ?>">Catalogue</a></li>
        <li><a href="<?= BASE_URL ?>echanges/liste.php" class="<?= strpos($_SERVER['REQUEST_URI'],'echanges')!==false ? 'actif' : '' ?>">Échanges / demandes</a></li>

        <li class="sidebar-groupe">Paramétrage</li>
        <li><a href="<?= BASE_URL ?>paliers/liste.php" class="<?= strpos($_SERVER['REQUEST_URI'],'paliers')!==false ? 'actif' : '' ?>">Paliers de statut</a></li>
        <?php endif; ?>

        <?php if ($role === 'admin'): ?>
        <li class="sidebar-groupe">Administration</li>
        <li><a href="<?= BASE_URL ?>audit.php" class="<?= $page === 'audit.php' ? 'actif' : '' ?>">Journal d'audit</a></li>
        <?php endif; ?>

        <?php if ($role === 'client'): ?>
        <li class="sidebar-groupe">Mon compte</li>
        <li><a href="<?= BASE_URL ?>mon-compte.php" class="<?= $page === 'mon-compte.php' ? 'actif' : '' ?>">Mes points &amp; mon palier</a></li>
        <li><a href="<?= BASE_URL ?>recompenses/liste.php" class="<?= strpos($_SERVER['REQUEST_URI'],'recompenses')!==false ? 'actif' : '' ?>">Catalogue de récompenses</a></li>
        <li><a href="<?= BASE_URL ?>echanges/mes-echanges.php" class="<?= strpos($_SERVER['REQUEST_URI'],'mes-echanges')!==false ? 'actif' : '' ?>">Mes échanges</a></li>
        <?php endif; ?>
    </ul>
</nav>
