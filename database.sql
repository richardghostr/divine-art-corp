-- Base de données Divine Art Corporation
-- Version mise à jour avec MySQLi
-- Date: 2024

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------

--
-- Structure de la base de données `divine_art_corp`
--

CREATE DATABASE IF NOT EXISTS `divine_art_corp` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `divine_art_corp`;

-- --------------------------------------------------------

--
-- Structure de la table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `role` enum('admin','manager','editor') NOT NULL DEFAULT 'admin',
  `statut` enum('actif','inactif') NOT NULL DEFAULT 'actif',
  `derniere_connexion` datetime DEFAULT NULL,
  `tentatives_connexion` int(11) DEFAULT 0,
  `token_reset` varchar(255) DEFAULT NULL,
  `token_reset_expire` datetime DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_statut` (`statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Données de la table `admins`
--

INSERT INTO `admins` (`nom`, `email`, `mot_de_passe`, `telephone`, `role`, `statut`) VALUES
('Administrateur Principal', 'admin@divineartcorp.cm', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+237 6XX XXX XXX', 'admin', 'actif');

-- --------------------------------------------------------

--
-- Structure de la table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text,
  `description_courte` varchar(255) DEFAULT NULL,
  `icone` varchar(50) DEFAULT NULL,
  `couleur` varchar(7) DEFAULT '#e74c3c',
  `prix_base` decimal(10,2) DEFAULT NULL,
  `duree_estimee` int(11) DEFAULT NULL COMMENT 'Durée en jours',
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `ordre` int(11) DEFAULT 0,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(255) DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_actif` (`actif`),
  KEY `idx_ordre` (`ordre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Données de la table `services`
--

INSERT INTO `services` (`nom`, `slug`, `description`, `description_courte`, `icone`, `couleur`, `prix_base`, `duree_estimee`, `actif`, `ordre`) VALUES
('Marketing Digital', 'marketing', 'Stratégies marketing complètes pour développer votre présence en ligne et atteindre vos objectifs commerciaux.', 'Développez votre présence digitale', 'fas fa-bullhorn', '#e74c3c', 1500.00, 30, 1, 1),
('Conception Graphique', 'graphique', 'Création d\'identités visuelles, logos, supports de communication pour renforcer votre image de marque.', 'Créez votre identité visuelle', 'fas fa-paint-brush', '#9b59b6', 800.00, 15, 1, 2),
('Conception Multimédia', 'multimedia', 'Production de contenus vidéo, animations, présentations interactives pour captiver votre audience.', 'Donnez vie à vos idées', 'fas fa-video', '#3498db', 2000.00, 45, 1, 3),
('Imprimerie', 'imprimerie', 'Services d\'impression professionnelle pour tous vos supports de communication physiques.', 'Imprimez avec qualité', 'fas fa-print', '#27ae60', 300.00, 7, 1, 4);

-- --------------------------------------------------------

--
-- Structure de la table `sous_services`
--

CREATE TABLE `sous_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text,
  `prix_base` decimal(10,2) DEFAULT NULL,
  `duree_estimee` int(11) DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `ordre` int(11) DEFAULT 0,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_sous_services_service` (`service_id`),
  KEY `idx_actif` (`actif`),
  CONSTRAINT `fk_sous_services_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Données de la table `sous_services`
--

INSERT INTO `sous_services` (`service_id`, `nom`, `slug`, `description`, `prix_base`, `duree_estimee`, `actif`, `ordre`) VALUES
(1, 'Stratégie Social Media', 'social-media', 'Gestion complète de vos réseaux sociaux', 800.00, 30, 1, 1),
(1, 'Publicité en ligne', 'publicite-ligne', 'Campagnes publicitaires Google Ads, Facebook Ads', 1200.00, 15, 1, 2),
(1, 'SEO/Référencement', 'seo-referencement', 'Optimisation pour les moteurs de recherche', 1000.00, 60, 1, 3),
(2, 'Création de logo', 'creation-logo', 'Design de logo professionnel et unique', 500.00, 7, 1, 1),
(2, 'Charte graphique', 'charte-graphique', 'Identité visuelle complète', 1200.00, 14, 1, 2),
(2, 'Supports print', 'supports-print', 'Flyers, brochures, cartes de visite', 300.00, 5, 1, 3),
(3, 'Vidéo promotionnelle', 'video-promotionnelle', 'Création de vidéos marketing', 1500.00, 21, 1, 1),
(3, 'Animation 2D/3D', 'animation-2d-3d', 'Animations graphiques professionnelles', 2500.00, 30, 1, 2),
(3, 'Montage vidéo', 'montage-video', 'Post-production et montage', 800.00, 10, 1, 3),
(4, 'Impression offset', 'impression-offset', 'Impression haute qualité grand volume', 200.00, 3, 1, 1),
(4, 'Impression numérique', 'impression-numerique', 'Impression rapide petit volume', 150.00, 1, 1, 2),
(4, 'Finition', 'finition', 'Reliure, pelliculage, découpe', 100.00, 2, 1, 3);

-- --------------------------------------------------------

--
-- Structure de la table `devis`
--

CREATE TABLE `devis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero_devis` varchar(50) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `entreprise` varchar(150) DEFAULT NULL,
  `poste` varchar(100) DEFAULT NULL,
  `service` varchar(50) NOT NULL,
  `sous_service` varchar(100) DEFAULT NULL,
  `description` text NOT NULL,
  `budget` varchar(50) DEFAULT NULL,
  `delai` varchar(50) DEFAULT NULL,
  `fichiers_joints` text DEFAULT NULL COMMENT 'JSON des fichiers uploadés',
  `statut` enum('nouveau','en_cours','termine','annule','en_attente') NOT NULL DEFAULT 'nouveau',
  `priorite` enum('basse','normale','haute','urgente') NOT NULL DEFAULT 'normale',
  `montant_estime` decimal(10,2) DEFAULT NULL,
  `montant_final` decimal(10,2) DEFAULT NULL,
  `notes_admin` text DEFAULT NULL,
  `notes_client` text DEFAULT NULL,
  `date_debut` date DEFAULT NULL,
  `date_fin_prevue` date DEFAULT NULL,
  `date_fin_reelle` date DEFAULT NULL,
  `admin_assigne` int(11) DEFAULT NULL,
  `source` varchar(50) DEFAULT 'site_web',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_devis` (`numero_devis`),
  KEY `idx_statut` (`statut`),
  KEY `idx_service` (`service`),
  KEY `idx_email` (`email`),
  KEY `idx_date_creation` (`date_creation`),
  KEY `idx_priorite` (`priorite`),
  KEY `fk_devis_admin` (`admin_assigne`),
  CONSTRAINT `fk_devis_admin` FOREIGN KEY (`admin_assigne`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `contacts`
--

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero_contact` varchar(50) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `entreprise` varchar(150) DEFAULT NULL,
  `sujet` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `statut` enum('nouveau','lu','repondu','archive') NOT NULL DEFAULT 'nouveau',
  `priorite` enum('basse','normale','haute','urgente') NOT NULL DEFAULT 'normale',
  `notes_admin` text DEFAULT NULL,
  `admin_assigne` int(11) DEFAULT NULL,
  `date_reponse` datetime DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `source` varchar(50) DEFAULT 'site_web',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_contact` (`numero_contact`),
  KEY `idx_statut` (`statut`),
  KEY `idx_email` (`email`),
  KEY `idx_date_creation` (`date_creation`),
  KEY `fk_contacts_admin` (`admin_assigne`),
  CONSTRAINT `fk_contacts_admin` FOREIGN KEY (`admin_assigne`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `projets`
--

CREATE TABLE `projets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `devis_id` int(11) NOT NULL,
  `nom` varchar(200) NOT NULL,
  `description` text,
  `statut` enum('planifie','en_cours','en_pause','termine','annule') NOT NULL DEFAULT 'planifie',
  `progression` int(11) DEFAULT 0 COMMENT 'Pourcentage de progression',
  `date_debut` date DEFAULT NULL,
  `date_fin_prevue` date DEFAULT NULL,
  `date_fin_reelle` date DEFAULT NULL,
  `budget_alloue` decimal(10,2) DEFAULT NULL,
  `cout_reel` decimal(10,2) DEFAULT NULL,
  `admin_responsable` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `fichiers_livres` text DEFAULT NULL COMMENT 'JSON des fichiers livrés',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_projets_devis` (`devis_id`),
  KEY `fk_projets_admin` (`admin_responsable`),
  KEY `idx_statut` (`statut`),
  CONSTRAINT `fk_projets_devis` FOREIGN KEY (`devis_id`) REFERENCES `devis` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_projets_admin` FOREIGN KEY (`admin_responsable`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `taches`
--

CREATE TABLE `taches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `projet_id` int(11) NOT NULL,
  `nom` varchar(200) NOT NULL,
  `description` text,
  `statut` enum('a_faire','en_cours','termine','annule') NOT NULL DEFAULT 'a_faire',
  `priorite` enum('basse','normale','haute','urgente') NOT NULL DEFAULT 'normale',
  `date_debut` date DEFAULT NULL,
  `date_fin_prevue` date DEFAULT NULL,
  `date_fin_reelle` date DEFAULT NULL,
  `temps_estime` int(11) DEFAULT NULL COMMENT 'Temps en heures',
  `temps_passe` int(11) DEFAULT NULL COMMENT 'Temps en heures',
  `admin_assigne` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `ordre` int(11) DEFAULT 0,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_taches_projet` (`projet_id`),
  KEY `fk_taches_admin` (`admin_assigne`),
  KEY `idx_statut` (`statut`),
  CONSTRAINT `fk_taches_projet` FOREIGN KEY (`projet_id`) REFERENCES `projets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_taches_admin` FOREIGN KEY (`admin_assigne`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `entreprise` varchar(150) DEFAULT NULL,
  `poste` varchar(100) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `pays` varchar(100) DEFAULT 'Cameroun',
  `site_web` varchar(255) DEFAULT NULL,
  `secteur_activite` varchar(100) DEFAULT NULL,
  `taille_entreprise` enum('tpe','pme','eti','ge') DEFAULT NULL,
  `budget_annuel` varchar(50) DEFAULT NULL,
  `source_acquisition` varchar(100) DEFAULT NULL,
  `statut` enum('prospect','client','client_vip','inactif') NOT NULL DEFAULT 'prospect',
  `notes` text DEFAULT NULL,
  `tags` text DEFAULT NULL COMMENT 'Tags séparés par des virgules',
  `date_premier_contact` date DEFAULT NULL,
  `date_dernier_contact` date DEFAULT NULL,
  `nb_projets` int(11) DEFAULT 0,
  `ca_total` decimal(10,2) DEFAULT 0.00,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_statut` (`statut`),
  KEY `idx_entreprise` (`entreprise`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `factures`
--

CREATE TABLE `factures` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero_facture` varchar(50) NOT NULL,
  `devis_id` int(11) DEFAULT NULL,
  `client_id` int(11) NOT NULL,
  `montant_ht` decimal(10,2) NOT NULL,
  `taux_tva` decimal(5,2) DEFAULT 19.25,
  `montant_tva` decimal(10,2) NOT NULL,
  `montant_ttc` decimal(10,2) NOT NULL,
  `statut` enum('brouillon','envoyee','payee','en_retard','annulee') NOT NULL DEFAULT 'brouillon',
  `date_emission` date NOT NULL,
  `date_echeance` date NOT NULL,
  `date_paiement` date DEFAULT NULL,
  `mode_paiement` varchar(50) DEFAULT NULL,
  `reference_paiement` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `conditions_paiement` text DEFAULT NULL,
  `fichier_pdf` varchar(255) DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_facture` (`numero_facture`),
  KEY `fk_factures_devis` (`devis_id`),
  KEY `fk_factures_client` (`client_id`),
  KEY `idx_statut` (`statut`),
  CONSTRAINT `fk_factures_devis` FOREIGN KEY (`devis_id`) REFERENCES `devis` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_factures_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `logs_activite`
--

CREATE TABLE `logs_activite` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_concernee` varchar(50) DEFAULT NULL,
  `id_enregistrement` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_logs_admin` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_date` (`date_creation`),
  CONSTRAINT `fk_logs_admin` FOREIGN KEY (`user_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `configurations`
--

CREATE TABLE `configurations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cle` varchar(100) NOT NULL,
  `valeur` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `type` enum('string','integer','boolean','json','text') NOT NULL DEFAULT 'string',
  `categorie` varchar(50) DEFAULT 'general',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cle` (`cle`),
  KEY `idx_categorie` (`categorie`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Données de la table `configurations`
--

INSERT INTO `configurations` (`cle`, `valeur`, `description`, `type`, `categorie`) VALUES
('site_name', 'Divine Art Corporation', 'Nom du site web', 'string', 'general'),
('site_email', 'contact@divineartcorp.cm', 'Email principal du site', 'string', 'general'),
('site_phone', '+237 6XX XXX XXX', 'Téléphone principal', 'string', 'general'),
('site_address', 'Douala, Cameroun', 'Adresse de l\'entreprise', 'text', 'general'),
('site_description', 'Agence créative spécialisée dans le marketing digital, design graphique et multimédia', 'Description du site', 'text', 'seo'),
('notifications_email', '1', 'Activer les notifications par email', 'boolean', 'notifications'),
('notifications_sms', '0', 'Activer les notifications par SMS', 'boolean', 'notifications'),
('max_file_size', '5242880', 'Taille maximale des fichiers en octets (5MB)', 'integer', 'uploads'),
('allowed_file_types', 'jpg,jpeg,png,gif,pdf,doc,docx,zip', 'Types de fichiers autorisés', 'string', 'uploads'),
('smtp_host', '', 'Serveur SMTP', 'string', 'email'),
('smtp_port', '587', 'Port SMTP', 'integer', 'email'),
('smtp_username', '', 'Nom d\'utilisateur SMTP', 'string', 'email'),
('smtp_password', '', 'Mot de passe SMTP', 'string', 'email'),
('google_analytics_id', '', 'ID Google Analytics', 'string', 'tracking'),
('facebook_pixel_id', '', 'ID Facebook Pixel', 'string', 'tracking'),
('maintenance_mode', '0', 'Mode maintenance activé', 'boolean', 'system'),
('cache_enabled', '1', 'Cache activé', 'boolean', 'performance'),
('cache_duration', '3600', 'Durée du cache en secondes', 'integer', 'performance');

-- --------------------------------------------------------

--
-- Structure de la table `fichiers`
--

CREATE TABLE `fichiers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom_original` varchar(255) NOT NULL,
  `nom_fichier` varchar(255) NOT NULL,
  `chemin` varchar(500) NOT NULL,
  `type_mime` varchar(100) NOT NULL,
  `taille` int(11) NOT NULL,
  `extension` varchar(10) NOT NULL,
  `table_liee` varchar(50) DEFAULT NULL,
  `id_enregistrement` int(11) DEFAULT NULL,
  `type_fichier` enum('devis','contact','projet','facture','autre') DEFAULT 'autre',
  `description` varchar(255) DEFAULT NULL,
  `admin_upload` int(11) DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_fichiers_admin` (`admin_upload`),
  KEY `idx_table_liee` (`table_liee`, `id_enregistrement`),
  CONSTRAINT `fk_fichiers_admin` FOREIGN KEY (`admin_upload`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') NOT NULL DEFAULT 'info',
  `lu` tinyint(1) NOT NULL DEFAULT 0,
  `action_url` varchar(500) DEFAULT NULL,
  `table_liee` varchar(50) DEFAULT NULL,
  `id_enregistrement` int(11) DEFAULT NULL,
  `date_lecture` datetime DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_notifications_admin` (`admin_id`),
  KEY `idx_lu` (`lu`),
  KEY `idx_date_creation` (`date_creation`),
  CONSTRAINT `fk_notifications_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `sessions_admin`
--

CREATE TABLE `sessions_admin` (
  `id` varchar(128) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text NOT NULL,
  `donnees` text DEFAULT NULL,
  `derniere_activite` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_sessions_admin` (`admin_id`),
  KEY `idx_derniere_activite` (`derniere_activite`),
  CONSTRAINT `fk_sessions_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `statistiques`
--

CREATE TABLE `statistiques` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date_stat` date NOT NULL,
  `type` varchar(50) NOT NULL,
  `valeur` decimal(15,2) NOT NULL DEFAULT 0.00,
  `donnees_json` json DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_date_type` (`date_stat`, `type`),
  KEY `idx_date_stat` (`date_stat`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `sauvegardes`
--

CREATE TABLE `sauvegardes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom_fichier` varchar(255) NOT NULL,
  `chemin` varchar(500) NOT NULL,
  `taille` bigint(20) NOT NULL,
  `type` enum('automatique','manuelle') NOT NULL DEFAULT 'automatique',
  `statut` enum('en_cours','termine','erreur') NOT NULL DEFAULT 'en_cours',
  `admin_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_fin` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_sauvegardes_admin` (`admin_id`),
  KEY `idx_date_creation` (`date_creation`),
  CONSTRAINT `fk_sauvegardes_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Vues pour faciliter les requêtes
--

--
-- Vue pour les statistiques des devis
--
CREATE VIEW `vue_stats_devis` AS
SELECT 
    DATE(date_creation) as date_stat,
    COUNT(*) as total_devis,
    SUM(CASE WHEN statut = 'nouveau' THEN 1 ELSE 0 END) as devis_nouveaux,
    SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) as devis_en_cours,
    SUM(CASE WHEN statut = 'termine' THEN 1 ELSE 0 END) as devis_termines,
    SUM(CASE WHEN statut = 'annule' THEN 1 ELSE 0 END) as devis_annules,
    AVG(montant_final) as montant_moyen,
    SUM(montant_final) as montant_total
FROM devis 
GROUP BY DATE(date_creation)
ORDER BY date_stat DESC;

--
-- Vue pour les statistiques des contacts
--
CREATE VIEW `vue_stats_contacts` AS
SELECT 
    DATE(date_creation) as date_stat,
    COUNT(*) as total_contacts,
    SUM(CASE WHEN statut = 'nouveau' THEN 1 ELSE 0 END) as contacts_nouveaux,
    SUM(CASE WHEN statut = 'lu' THEN 1 ELSE 0 END) as contacts_lus,
    SUM(CASE WHEN statut = 'repondu' THEN 1 ELSE 0 END) as contacts_repondus
FROM contacts 
GROUP BY DATE(date_creation)
ORDER BY date_stat DESC;

--
-- Vue pour le tableau de bord
--
CREATE VIEW `vue_dashboard` AS
SELECT 
    'devis' as type,
    COUNT(*) as total,
    SUM(CASE WHEN statut = 'nouveau' THEN 1 ELSE 0 END) as en_attente,
    SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) as en_cours,
    SUM(CASE WHEN statut = 'termine' THEN 1 ELSE 0 END) as termines,
    SUM(CASE WHEN DATE(date_creation) = CURDATE() THEN 1 ELSE 0 END) as aujourd_hui,
    SUM(CASE WHEN WEEK(date_creation) = WEEK(CURDATE()) AND YEAR(date_creation) = YEAR(CURDATE()) THEN 1 ELSE 0 END) as cette_semaine,
    SUM(CASE WHEN MONTH(date_creation) = MONTH(CURDATE()) AND YEAR(date_creation) = YEAR(CURDATE()) THEN 1 ELSE 0 END) as ce_mois
FROM devis

UNION ALL

SELECT 
    'contacts' as type,
    COUNT(*) as total,
    SUM(CASE WHEN statut = 'nouveau' THEN 1 ELSE 0 END) as en_attente,
    SUM(CASE WHEN statut = 'lu' THEN 1 ELSE 0 END) as en_cours,
    SUM(CASE WHEN statut = 'repondu' THEN 1 ELSE 0 END) as termines,
    SUM(CASE WHEN DATE(date_creation) = CURDATE() THEN 1 ELSE 0 END) as aujourd_hui,
    SUM(CASE WHEN WEEK(date_creation) = WEEK(CURDATE()) AND YEAR(date_creation) = YEAR(CURDATE()) THEN 1 ELSE 0 END) as cette_semaine,
    SUM(CASE WHEN MONTH(date_creation) = MONTH(CURDATE()) AND YEAR(date_creation) = YEAR(CURDATE()) THEN 1 ELSE 0 END) as ce_mois
FROM contacts;

-- --------------------------------------------------------

--
-- Procédures stockées
--

DELIMITER $$

--
-- Procédure pour nettoyer les anciennes sessions
--
CREATE PROCEDURE `nettoyer_sessions`()
BEGIN
    DELETE FROM sessions_admin 
    WHERE derniere_activite < DATE_SUB(NOW(), INTERVAL 30 DAY);
END$$

--
-- Procédure pour nettoyer les anciens logs
--
CREATE PROCEDURE `nettoyer_logs`()
BEGIN
    DELETE FROM logs_activite 
    WHERE date_creation < DATE_SUB(NOW(), INTERVAL 90 DAY);
END$$

--
-- Procédure pour calculer les statistiques quotidiennes
--
CREATE PROCEDURE `calculer_stats_quotidiennes`(IN date_stat DATE)
BEGIN
    -- Statistiques des devis
    INSERT INTO statistiques (date_stat, type, valeur, donnees_json) 
    SELECT 
        date_stat,
        'devis_total',
        COUNT(*),
        JSON_OBJECT(
            'nouveaux', SUM(CASE WHEN statut = 'nouveau' THEN 1 ELSE 0 END),
            'en_cours', SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END),
            'termines', SUM(CASE WHEN statut = 'termine' THEN 1 ELSE 0 END),
            'montant_total', COALESCE(SUM(montant_final), 0)
        )
    FROM devis 
    WHERE DATE(date_creation) = date_stat
    ON DUPLICATE KEY UPDATE 
        valeur = VALUES(valeur),
        donnees_json = VALUES(donnees_json);
    
    -- Statistiques des contacts
    INSERT INTO statistiques (date_stat, type, valeur, donnees_json) 
    SELECT 
        date_stat,
        'contacts_total',
        COUNT(*),
        JSON_OBJECT(
            'nouveaux', SUM(CASE WHEN statut = 'nouveau' THEN 1 ELSE 0 END),
            'lus', SUM(CASE WHEN statut = 'lu' THEN 1 ELSE 0 END),
            'repondus', SUM(CASE WHEN statut = 'repondu' THEN 1 ELSE 0 END)
        )
    FROM contacts 
    WHERE DATE(date_creation) = date_stat
    ON DUPLICATE KEY UPDATE 
        valeur = VALUES(valeur),
        donnees_json = VALUES(donnees_json);
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Triggers
--

--
-- Trigger pour mettre à jour les statistiques clients
--
DELIMITER $$
CREATE TRIGGER `update_client_stats` AFTER UPDATE ON `devis`
FOR EACH ROW
BEGIN
    IF NEW.statut = 'termine' AND OLD.statut != 'termine' THEN
        -- Mettre à jour ou créer le client
        INSERT INTO clients (nom, email, telephone, entreprise, date_premier_contact, nb_projets, ca_total)
        VALUES (NEW.nom, NEW.email, NEW.telephone, NEW.entreprise, NEW.date_creation, 1, COALESCE(NEW.montant_final, 0))
        ON DUPLICATE KEY UPDATE
            nb_projets = nb_projets + 1,
            ca_total = ca_total + COALESCE(NEW.montant_final, 0),
            date_dernier_contact = NOW(),
            statut = 'client';
    END IF;
END$$
DELIMITER ;

--
-- Trigger pour générer automatiquement les numéros
--
DELIMITER $$
CREATE TRIGGER `generate_devis_number` BEFORE INSERT ON `devis`
FOR EACH ROW
BEGIN
    IF NEW.numero_devis IS NULL OR NEW.numero_devis = '' THEN
        SET @count = (SELECT COUNT(*) FROM devis WHERE YEAR(date_creation) = YEAR(NOW()) AND MONTH(date_creation) = MONTH(NOW())) + 1;
        SET NEW.numero_devis = CONCAT('DEV', YEAR(NOW()), LPAD(MONTH(NOW()), 2, '0'), LPAD(@count, 4, '0'));
    END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER `generate_contact_number` BEFORE INSERT ON `contacts`
FOR EACH ROW
BEGIN
    IF NEW.numero_contact IS NULL OR NEW.numero_contact = '' THEN
        SET @count = (SELECT COUNT(*) FROM contacts WHERE YEAR(date_creation) = YEAR(NOW()) AND MONTH(date_creation) = MONTH(NOW())) + 1;
        SET NEW.numero_contact = CONCAT('CNT', YEAR(NOW()), LPAD(MONTH(NOW()), 2, '0'), LPAD(@count, 4, '0'));
    END IF;
END$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Index pour optimiser les performances
--

-- Index composites pour les requêtes fréquentes
CREATE INDEX `idx_devis_statut_date` ON `devis` (`statut`, `date_creation`);
CREATE INDEX `idx_devis_service_statut` ON `devis` (`service`, `statut`);
CREATE INDEX `idx_contacts_statut_date` ON `contacts` (`statut`, `date_creation`);
CREATE INDEX `idx_logs_user_date` ON `logs_activite` (`user_id`, `date_creation`);

-- Index pour les recherches textuelles
CREATE FULLTEXT INDEX `idx_devis_search` ON `devis` (`nom`, `email`, `entreprise`, `description`);
CREATE FULLTEXT INDEX `idx_contacts_search` ON `contacts` (`nom`, `email`, `sujet`, `message`);

-- --------------------------------------------------------

--
-- Événements programmés pour la maintenance
--

SET GLOBAL event_scheduler = ON;

-- Nettoyage automatique des sessions expirées (quotidien)
CREATE EVENT IF NOT EXISTS `ev_nettoyer_sessions`
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
  CALL nettoyer_sessions();

-- Nettoyage automatique des anciens logs (hebdomadaire)
CREATE EVENT IF NOT EXISTS `ev_nettoyer_logs`
ON SCHEDULE EVERY 1 WEEK
STARTS CURRENT_TIMESTAMP
DO
  CALL nettoyer_logs();

-- Calcul des statistiques quotidiennes (quotidien à minuit)
CREATE EVENT IF NOT EXISTS `ev_stats_quotidiennes`
ON SCHEDULE EVERY 1 DAY
STARTS (CURRENT_DATE + INTERVAL 1 DAY)
DO
  CALL calculer_stats_quotidiennes(CURDATE() - INTERVAL 1 DAY);

-- --------------------------------------------------------

COMMIT;

-- Fin du script de création de la base de données
-- Divine Art Corporation - Version MySQLi
