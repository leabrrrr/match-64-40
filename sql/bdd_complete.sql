-- ============================================================
-- Base complète : MATCH 64/40
-- À coller dans phpMyAdmin > onglet SQL > Exécuter
-- ATTENTION : supprime l'ancienne base et toutes ses données
-- ============================================================

SET NAMES utf8mb4;

DROP DATABASE IF EXISTS projetsitemvcmatchs;
CREATE DATABASE projetsitemvcmatchs CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE projetsitemvcmatchs;

-- ------------------------------------------------------------
-- Tables
-- ------------------------------------------------------------

CREATE TABLE clubs (
    id_club INT AUTO_INCREMENT,
    nom VARCHAR(100),                  -- même nom que sur la FFBB
    email VARCHAR(50),
    mdp VARCHAR(80),
    description VARCHAR(200),
    adresse VARCHAR(200),
    telephone VARCHAR(50),
    logo VARCHAR(50),
    role VARCHAR(10) DEFAULT 'club',   -- 'club' ou 'admin'
    valide BOOLEAN DEFAULT 0,          -- 0 = en attente, 1 = validé par l'admin
    CONSTRAINT pk_clubs PRIMARY KEY (id_club)
) ENGINE=InnoDB;

CREATE TABLE matchs (
    id_match INT AUTO_INCREMENT,
    equipe_domicile VARCHAR(100),      -- les noms FFBB dépassent souvent 50 caractères
    equipe_exterieur VARCHAR(100),
    club_domicile VARCHAR(100),        -- nom du club FFBB ("LONS BASKET" pour l'équipe "LONS BASKET - 2")
    club_exterieur VARCHAR(100),
    date_match DATE,
    heure_match TIME,
    lieu VARCHAR(100),                 -- "GYMNASE DU HEDAS, Pau"
    departement VARCHAR(3),            -- département de la salle : "64", "40", ou autre si match à l'extérieur
    competition VARCHAR(100),          -- "Régionale masculine seniors - Division 3"
    niveau VARCHAR(20),                -- "Départemental", "Régional" ou "National"
    id_poule VARCHAR(20),              -- identifiant de la poule à la FFBB (pour le classement)
    nom_poule VARCHAR(50),             -- "Poule G"
    logo_domicile VARCHAR(36),         -- identifiants des logos à la FFBB
    logo_exterieur VARCHAR(36),        -- (ex : f40a09e2-32dd-488c-9138-283f6b52a498)
    logo_competition VARCHAR(36),
    ffbb_id VARCHAR(50),               -- identifiant de la rencontre à la FFBB
    id_club INT,                       -- NULL si le club organisateur n'est pas inscrit
    CONSTRAINT pk_match PRIMARY KEY (id_match),
    CONSTRAINT fk_match FOREIGN KEY (id_club) REFERENCES clubs(id_club)
) ENGINE=InnoDB;

CREATE TABLE match_details (
    id_match_detail INT AUTO_INCREMENT,
    prix_entree DECIMAL(5,2),
    horaire_ouvertures_portes TIME,
    description_organisation VARCHAR(200),
    coordonnees_gps VARCHAR(50),
    contact_telephone VARCHAR(50),
    contact_mail VARCHAR(50),
    buvette BOOL,
    parking BOOL,
    id_match INT,
    CONSTRAINT pk_match_detail PRIMARY KEY (id_match_detail),
    CONSTRAINT fk_match_detail FOREIGN KEY (id_match) REFERENCES matchs(id_match)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Comptes de test (mots de passe hashés avec password_hash)
-- Les noms des clubs sont ceux de la FFBB : la synchro les reconnaît
-- et leur rattache automatiquement leurs matchs à domicile.
--   Admin                        : admin@gestionmatchs.fr   / admin64
--   ELAN BEARNAIS PAU NORD-EST   : contact@elanbearnais.fr  / club40
--   LONS BASKET                  : contact@lonsbasket.fr    / club40  (en attente de validation)
-- ------------------------------------------------------------
INSERT INTO clubs (nom, email, mdp, description, adresse, telephone, logo, role, valide) VALUES
('Administrateur', 'admin@gestionmatchs.fr',
 '$2y$12$MQKNLRdxDe7EjXwY5MogUuwzimHsRveUoet.Q7WPlndd4n3pLWr6a',
 '', '', '', '', 'admin', 1),
('ELAN BEARNAIS PAU NORD-EST', 'contact@elanbearnais.fr',
 '$2y$12$ZH2f.9VUVgClahdimdxnteVnov38nu3UywG2qS.bnRoukhKbAXGXa',
 'Club de basket de Pau', '64000 Pau',
 '05 59 00 00 00', '', 'club', 1),
('LONS BASKET', 'contact@lonsbasket.fr',
 '$2y$12$ZH2f.9VUVgClahdimdxnteVnov38nu3UywG2qS.bnRoukhKbAXGXa',
 'Club de basket de Lons', '64140 Lons',
 '05 59 00 00 01', '', 'club', 0);

-- Les matchs ne sont pas insérés ici : connecte-toi en admin
-- et clique sur "Synchroniser la FFBB" pour les récupérer.
