-- ============================================================
-- Projet 68 — Plateforme de programme de fidélisation
-- Master CCA — ESP Dakar
-- Script de création de la base de données + données de démo
-- ============================================================

DROP DATABASE IF EXISTS fidelite_esp;
CREATE DATABASE fidelite_esp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fidelite_esp;

-- ------------------------------------------------------------
-- 1. UTILISATEURS (comptes de connexion : admin, gestionnaire, client)
-- ------------------------------------------------------------
CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('admin','gestionnaire','client') NOT NULL DEFAULT 'client',
    actif TINYINT(1) NOT NULL DEFAULT 1,
    derniere_connexion DATETIME NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 2. PALIERS (bronze, argent, or, platine)
-- ------------------------------------------------------------
CREATE TABLE paliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    seuil_points INT NOT NULL DEFAULT 0,        -- points cumulés sur 12 mois glissants requis
    multiplicateur DECIMAL(4,2) NOT NULL DEFAULT 1.00,
    avantages TEXT,
    ordre INT NOT NULL DEFAULT 0,
    couleur VARCHAR(20) DEFAULT '#999999'
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 3. ADHERENTS (profil fidélité, lié à un compte utilisateur "client")
-- ------------------------------------------------------------
CREATE TABLE adherents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    telephone VARCHAR(30),
    date_naissance DATE NULL,
    date_adhesion DATE NOT NULL,
    palier_id INT NOT NULL DEFAULT 1,
    points_total INT NOT NULL DEFAULT 0,        -- cumul historique (statut)
    points_disponibles INT NOT NULL DEFAULT 0,  -- solde utilisable
    parraine_par INT NULL,                      -- adherent_id du parrain
    opt_in_rgpd TINYINT(1) NOT NULL DEFAULT 0,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_adherent_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL,
    CONSTRAINT fk_adherent_palier FOREIGN KEY (palier_id) REFERENCES paliers(id),
    CONSTRAINT fk_adherent_parrain FOREIGN KEY (parraine_par) REFERENCES adherents(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 4. RECOMPENSES (catalogue)
-- ------------------------------------------------------------
CREATE TABLE recompenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    description TEXT,
    categorie VARCHAR(80),
    cout_points INT NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    image VARCHAR(255) NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 5. TRANSACTIONS_POINTS (gains, bonus, ajustements, expiration)
-- ------------------------------------------------------------
CREATE TABLE transactions_points (
    id INT AUTO_INCREMENT PRIMARY KEY,
    adherent_id INT NOT NULL,
    type ENUM('achat','bonus_anniversaire','parrainage','ajustement','expiration') NOT NULL,
    points INT NOT NULL,                 -- positif = crédit, négatif = débit/expiration
    montant_achat DECIMAL(12,2) NULL,
    description VARCHAR(255),
    date_transaction DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_expiration DATE NULL,
    cree_par INT NULL,
    CONSTRAINT fk_tp_adherent FOREIGN KEY (adherent_id) REFERENCES adherents(id) ON DELETE CASCADE,
    CONSTRAINT fk_tp_utilisateur FOREIGN KEY (cree_par) REFERENCES utilisateurs(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 6. ECHANGES (rédemption de points contre récompenses)
-- ------------------------------------------------------------
CREATE TABLE echanges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    adherent_id INT NOT NULL,
    recompense_id INT NOT NULL,
    points_utilises INT NOT NULL,
    statut ENUM('en_attente','validee','refusee','livree') NOT NULL DEFAULT 'en_attente',
    date_echange DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_traitement DATETIME NULL,
    traite_par INT NULL,
    commentaire VARCHAR(255) NULL,
    CONSTRAINT fk_ech_adherent FOREIGN KEY (adherent_id) REFERENCES adherents(id) ON DELETE CASCADE,
    CONSTRAINT fk_ech_recompense FOREIGN KEY (recompense_id) REFERENCES recompenses(id),
    CONSTRAINT fk_ech_utilisateur FOREIGN KEY (traite_par) REFERENCES utilisateurs(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 7. AUDIT_LOG (journal horodaté des actions sensibles)
-- ------------------------------------------------------------
CREATE TABLE audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NULL,
    action VARCHAR(100) NOT NULL,
    table_concernee VARCHAR(60) NOT NULL,
    enregistrement_id INT NULL,
    details TEXT NULL,
    adresse_ip VARCHAR(45) NULL,
    date_action DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- DONNEES DE DEMONSTRATION
-- ============================================================

-- Paliers
INSERT INTO paliers (nom, seuil_points, multiplicateur, avantages, ordre, couleur) VALUES
('Bronze',    0,    1.00, 'Accumulation standard des points, accès au catalogue de base', 1, '#8C5A3B'),
('Argent',  2000,    1.20, 'Bonus de 20% sur les points, offres exclusives trimestrielles', 2, '#9E9E9E'),
('Or',      5000,    1.50, 'Bonus de 50% sur les points, conseiller dédié, invitations VIP', 3, '#D4AF37'),
('Platine', 10000,   2.00, 'Doublement des points, cadeaux premium, service prioritaire', 4, '#4B4B77');

-- Les comptes utilisateurs (admin, gestionnaire, clients) NE SONT PAS créés ici.
-- Les mots de passe doivent être hachés avec la fonction PHP password_hash(),
-- donc ils sont créés automatiquement par le script sql/seed_users.php
-- (à exécuter UNE SEULE FOIS dans le navigateur après l'import de ce script SQL).
-- Voir le README.md, section "Installation", étape 3.

-- Adherents
INSERT INTO adherents (utilisateur_id, nom, prenom, email, telephone, date_naissance, date_adhesion, palier_id, points_total, points_disponibles, opt_in_rgpd, actif) VALUES
(NULL, 'Diop', 'Moussa', 'moussa.diop@client.sn', '771234501', '1990-04-12', '2023-01-15', 3, 5400, 3200, 1, 1),
(NULL, 'Ndiaye', 'Aissatou', 'aissatou.ndiaye@client.sn', '771234502', '1995-09-03', '2023-06-20', 2, 2600, 1800, 1, 1),
(NULL, 'Fall', 'Cheikh', 'cheikh.fall@mail.sn', '771234503', '1988-01-22', '2024-02-10', 1, 450, 450, 1, 1),
(NULL, 'Sow', 'Mariama', 'mariama.sow@mail.sn', '771234504', '1992-11-30', '2022-08-05', 4, 12500, 8000, 1, 1),
(NULL, 'Ba', 'Ibrahima', 'ibrahima.ba@mail.sn', '771234505', '1985-07-18', '2024-11-01', 1, 150, 150, 0, 1);

-- Recompenses
INSERT INTO recompenses (nom, description, categorie, cout_points, stock, actif) VALUES
('Bon d\'achat 5 000 FCFA', 'Bon d\'achat valable dans le réseau de magasins partenaires', 'Bon d\'achat', 1000, 100, 1),
('Bon d\'achat 20 000 FCFA', 'Bon d\'achat valable dans le réseau de magasins partenaires', 'Bon d\'achat', 3500, 50, 1),
('Casquette de marque', 'Casquette brodée édition limitée', 'Goodies', 800, 30, 1),
('Panier gourmand', 'Panier de produits locaux sénégalais', 'Cadeau', 2500, 20, 1),
('Week-end en résidence (Saly)', 'Séjour de 2 nuits en résidence partenaire', 'Voyage', 9000, 5, 1),
('Livraison prioritaire (1 an)', 'Livraison prioritaire gratuite pendant 12 mois', 'Service', 4000, 999, 1);

-- Transactions de points (cree_par = NULL : les comptes staff n'existent pas encore à ce stade,
-- ils seront créés ensuite par sql/seed_users.php)
INSERT INTO transactions_points (adherent_id, type, points, montant_achat, description, date_transaction, cree_par) VALUES
(1, 'achat', 1200, 60000.00, 'Achat en boutique - Dakar Plateau', '2025-09-10 10:15:00', NULL),
(1, 'achat', 900, 45000.00, 'Achat en ligne', '2025-11-05 16:42:00', NULL),
(1, 'bonus_anniversaire', 200, NULL, 'Bonus anniversaire', '2025-04-12 09:00:00', NULL),
(2, 'achat', 600, 30000.00, 'Achat en boutique - Almadies', '2025-10-02 11:00:00', NULL),
(2, 'parrainage', 300, NULL, 'Parrainage de Cheikh Fall', '2024-02-10 12:00:00', NULL),
(3, 'achat', 450, 22500.00, 'Achat en boutique - Sacré-Cœur', '2025-12-01 14:20:00', NULL),
(4, 'achat', 2000, 100000.00, 'Achat entreprise - commande groupée', '2025-06-15 09:30:00', NULL),
(5, 'achat', 150, 7500.00, 'Premier achat', '2025-11-20 17:10:00', NULL);

-- Echanges (traite_par = NULL pour la même raison)
INSERT INTO echanges (adherent_id, recompense_id, points_utilises, statut, date_echange, traite_par, commentaire) VALUES
(1, 2, 3500, 'validee', '2025-12-10 08:00:00', NULL, 'Retrait en boutique Plateau'),
(4, 5, 9000, 'livree', '2025-07-01 10:00:00', NULL, 'Séjour confirmé pour le 15/07'),
(2, 3, 800, 'en_attente', '2026-08-01 09:00:00', NULL, NULL);

-- Journal d'audit (utilisateur_id = NULL : entrées historiques créées avant les comptes)
INSERT INTO audit_log (utilisateur_id, action, table_concernee, enregistrement_id, details, adresse_ip) VALUES
(NULL, 'CONNEXION', 'utilisateurs', NULL, 'Connexion réussie (donnée de démonstration)', '127.0.0.1'),
(NULL, 'CREATION', 'adherents', 5, 'Création de l\'adhérent Ibrahima Ba', '127.0.0.1'),
(NULL, 'CREATION', 'transactions_points', 8, 'Ajout de 150 points pour l\'adhérent #5', '127.0.0.1');
