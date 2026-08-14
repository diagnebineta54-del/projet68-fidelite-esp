# Fidélité ESP — Programme de fidélisation (Projet 68)

Master CCA — École Supérieure Polytechnique de Dakar
Plateforme web de programme de fidélisation avec points, paliers et récompenses.

## Stack technique
PHP 8+ (orienté objet + procédural) · MySQL/PDO · Apache · HTML5/CSS3/JS vanilla · Chart.js (CDN)

## 1. Prérequis
- XAMPP installé et démarré (Apache + MySQL)

## 2. Installation (5 minutes)

**Étape 1 — Copier le projet dans XAMPP**
Copiez tout le dossier `fidelite-app` dans le dossier `htdocs` de XAMPP :
- Windows : `C:\xampp\htdocs\fidelite-app`
- Mac : `/Applications/XAMPP/htdocs/fidelite-app`
- Linux : `/opt/lampp/htdocs/fidelite-app`

**Étape 2 — Créer la base de données**
1. Ouvrez `http://localhost/phpmyadmin`
2. Cliquez sur l'onglet "Importer"
3. Choisissez le fichier `sql/database.sql`
4. Cliquez sur "Exécuter"

Cela crée la base `fidelite_esp` avec ses 7 tables et des données de démonstration (adhérents, récompenses, transactions...).

**Étape 3 — Créer les comptes de connexion**
Dans votre navigateur, allez sur :
```
http://localhost/fidelite-app/sql/seed_users.php
```
Ce script crée les comptes de démo avec un mot de passe correctement sécurisé (haché avec `password_hash()`). Vous ne pouvez pas le faire directement dans le fichier SQL car le hash doit être généré par PHP.

**Étape 4 — Se connecter**
```
http://localhost/fidelite-app/login.php
```

## 3. Comptes de démonstration

| Rôle | Email | Mot de passe |
|---|---|---|
| Administrateur | admin@fidelite-esp.sn | Password123! |
| Gestionnaire | gestionnaire@fidelite-esp.sn | Password123! |
| Client | moussa.diop@client.sn | Password123! |
| Client | aissatou.ndiaye@client.sn | Password123! |

**Important pour la vidéo/soutenance :** supprimez ou protégez `sql/seed_users.php` après la première exécution.

## 4. Structure du projet
```
fidelite-app/
├── config/          → connexion base de données, config générale, sessions sécurisées
├── includes/        → auth (rôles), fonctions communes, layout (header/sidebar/footer), SimplePDF
├── adherents/        → CRUD adhérents
├── transactions/     → attribution et historique des points
├── recompenses/      → catalogue de récompenses (CRUD)
├── echanges/          → demandes d'échange (client) + validation (staff)
├── paliers/           → gestion des paliers de statut (CRUD)
├── export/            → exports PDF et CSV
├── assets/            → CSS, JS
├── sql/                → script de création BDD + seed des comptes
├── dashboard.php       → tableau de bord (staff)
├── mon-compte.php      → espace personnel (client)
├── login.php / logout.php
└── audit.php            → journal d'audit (admin)
```

## 5. Rôles et permissions
- **admin** : accès complet, y compris suppression et journal d'audit
- **gestionnaire** : gestion quotidienne (adhérents, points, récompenses, échanges) sans suppression ni accès à l'audit
- **client** : consulte ses points/palier, demande des échanges

## 6. Fonctionnalités de sécurité implémentées
- Mots de passe hachés avec `password_hash()` / vérifiés avec `password_verify()`
- Requêtes préparées PDO partout (protection injections SQL)
- Échappement `htmlspecialchars()` systématique à l'affichage (protection XSS)
- Jeton CSRF sur tous les formulaires et liens de suppression
- Sessions avec expiration automatique après 30 min d'inactivité, `session_regenerate_id()` à la connexion
- Contrôle d'accès par rôle sur chaque page (`exiger_role()`)
- Journal d'audit horodaté de toutes les actions sensibles (créations, modifications, suppressions, connexions)

## 7. À propos de l'export PDF
Le cahier des charges recommande TCPDF, FPDF ou DOMPDF. Ces bibliothèques s'installent normalement via Composer. Pour que le projet fonctionne immédiatement, même sur un poste sans accès Internet, `includes/SimplePDF.php` est un petit générateur PDF natif en PHP pur (validé avec l'outil `qpdf --check`). Pour utiliser une vraie bibliothèque, installez-la via Composer (`composer require setasign/fpdf`) et remplacez simplement l'utilisation de `SimplePDF` dans le dossier `/export/` — la logique métier (requêtes SQL) ne change pas.

## 8. Points à modifier avant une mise en production réelle
- `config/db.php` : identifiants MySQL si différents de root/vide
- `config/app.php` : `BASE_URL` si le dossier n'est pas `/fidelite-app/`
- Configurer un vrai serveur SMTP pour les emails (actuellement `mail()` natif, souvent non fonctionnel en local — voir PHPMailer)
- Activer `session.cookie_secure` si déploiement en HTTPS

## 9. Règle métier : calcul des points et paliers
- Barème : 10 points par tranche de 1 000 FCFA d'achat (`config/app.php` → `POINTS_PAR_1000_FCFA`), multiplié par le coefficient du palier de l'adhérent
- Le palier est recalculé automatiquement à chaque transaction, sur la base des points cumulés sur les **12 derniers mois glissants** (`includes/functions.php` → `recalculer_palier()`)
- Le parrainage attribue automatiquement 300 points au parrain à l'inscription d'un filleul
