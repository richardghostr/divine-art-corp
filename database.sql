-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 08, 2025 at 02:21 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `divine_art_corp`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `calculer_stats_quotidiennes` (IN `date_stat` DATE)   BEGIN
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `nettoyer_logs` ()   BEGIN
    DELETE FROM logs_activite 
    WHERE date_creation < DATE_SUB(NOW(), INTERVAL 90 DAY);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `nettoyer_sessions` ()   BEGIN
    DELETE FROM sessions_admin 
    WHERE derniere_activite < DATE_SUB(NOW(), INTERVAL 30 DAY);
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mot_de_passe` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('admin','manager','editor') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin',
  `statut` enum('actif','inactif') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'actif',
  `derniere_connexion` datetime DEFAULT NULL,
  `tentatives_connexion` int DEFAULT '0',
  `token_reset` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token_reset_expire` datetime DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `nom`, `email`, `mot_de_passe`, `telephone`, `role`, `statut`, `derniere_connexion`, `tentatives_connexion`, `token_reset`, `token_reset_expire`, `date_creation`, `date_modification`) VALUES
(1, 'Administrateur Principal', 'admin@divineartcorp.cm', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+237 6XX XXX XXX', 'admin', 'actif', '2025-06-08 11:05:40', 0, NULL, NULL, '2025-06-03 15:38:23', '2025-06-08 11:05:40');

-- --------------------------------------------------------

--
-- Table structure for table `campagnes_marketing`
--

CREATE TABLE `campagnes_marketing` (
  `id` int NOT NULL,
  `nom_campagne` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `type_campagne` enum('social_media','google_ads','email_marketing','seo','content_marketing','influenceur','affichage','radio_tv') COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_id` int DEFAULT NULL,
  `client_nom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut` enum('planifiee','active','pause','terminee','annulee') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'planifiee',
  `objectif` enum('notoriete','trafic','leads','ventes','engagement','retention') COLLATE utf8mb4_unicode_ci NOT NULL,
  `budget_total` decimal(12,2) DEFAULT NULL,
  `budget_depense` decimal(12,2) DEFAULT '0.00',
  `date_debut` date DEFAULT NULL,
  `date_fin` date DEFAULT NULL,
  `plateforme` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `audience_cible` text COLLATE utf8mb4_unicode_ci,
  `kpi_objectif` text COLLATE utf8mb4_unicode_ci,
  `kpi_resultats` text COLLATE utf8mb4_unicode_ci,
  `impressions` int DEFAULT '0',
  `clics` int DEFAULT '0',
  `conversions` int DEFAULT '0',
  `ctr` decimal(5,2) DEFAULT '0.00',
  `cpc` decimal(8,2) DEFAULT '0.00',
  `cpm` decimal(8,2) DEFAULT '0.00',
  `roas` decimal(8,2) DEFAULT '0.00',
  `fichiers_creatifs` text COLLATE utf8mb4_unicode_ci,
  `rapport_performance` text COLLATE utf8mb4_unicode_ci,
  `notes_campagne` text COLLATE utf8mb4_unicode_ci,
  `admin_assigne` int DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entreprise` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `poste` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse` text COLLATE utf8mb4_unicode_ci,
  `ville` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pays` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Cameroun',
  `site_web` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `secteur_activite` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `taille_entreprise` enum('tpe','pme','eti','ge') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `budget_annuel` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_acquisition` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut` enum('prospect','client','client_vip','inactif') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'prospect',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `tags` text COLLATE utf8mb4_unicode_ci COMMENT 'Tags séparés par des virgules',
  `date_premier_contact` date DEFAULT NULL,
  `date_dernier_contact` date DEFAULT NULL,
  `nb_projets` int DEFAULT '0',
  `ca_total` decimal(10,2) DEFAULT '0.00',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `nom`, `email`, `telephone`, `entreprise`, `poste`, `adresse`, `ville`, `pays`, `site_web`, `secteur_activite`, `taille_entreprise`, `budget_annuel`, `source_acquisition`, `statut`, `notes`, `tags`, `date_premier_contact`, `date_dernier_contact`, `nb_projets`, `ca_total`, `date_creation`, `date_modification`) VALUES
(9, 'richard de reyes', 'richardtiomela4@gmail.com', '+237672507275', 'RIch-tech', 'Stagiaire', 'richardtiomela4@gmail.com', 'Bafoussam', 'Cameroun', NULL, 'Ingenieurie', NULL, NULL, NULL, 'prospect', 'GOOD', NULL, '2025-06-04', NULL, 0, 0.00, '2025-06-04 17:04:06', NULL),
(11, 'richard de reyes', 'richardtiomela5@gmail.com', '+237672507275', 'RIch-tech', 'Stagiaire', 'richardtiomela4@gmail.com', 'Bafoussam', 'Cameroun', NULL, 'Ingenieurie', NULL, NULL, NULL, 'prospect', 'BIRN', NULL, '2025-06-04', NULL, 0, 0.00, '2025-06-04 17:05:25', NULL),
(12, 'richard de reyes', 'carinefomekong152@gmail.com', '+237672507275', 'RIch-tech', 'Stagiaire', 'richardtiomela4@gmail.com', 'Bafoussam', 'Cameroun', NULL, 'Ingenieurie', NULL, NULL, NULL, 'prospect', 'bien', NULL, '2025-06-04', NULL, 0, 0.00, '2025-06-04 17:06:13', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `configurations`
--

CREATE TABLE `configurations` (
  `id` int NOT NULL,
  `cle` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valeur` text COLLATE utf8mb4_unicode_ci,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('string','integer','boolean','json','text') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string',
  `categorie` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'general',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `configurations`
--

INSERT INTO `configurations` (`id`, `cle`, `valeur`, `description`, `type`, `categorie`, `date_creation`, `date_modification`) VALUES
(1, 'site_name', 'Divine Art Corporation', 'Nom du site web', 'string', 'general', '2025-06-03 15:38:24', NULL),
(2, 'site_email', 'contact@divineartcorp.cm', 'Email principal du site', 'string', 'general', '2025-06-03 15:38:24', NULL),
(3, 'site_phone', '+237 6XX XXX XXX', 'Téléphone principal', 'string', 'general', '2025-06-03 15:38:24', NULL),
(4, 'site_address', 'Douala, Cameroun', 'Adresse de l\'entreprise', 'text', 'general', '2025-06-03 15:38:24', NULL),
(5, 'site_description', 'Agence créative spécialisée dans le marketing digital, design graphique et multimédia', 'Description du site', 'text', 'seo', '2025-06-03 15:38:24', NULL),
(6, 'notifications_email', '1', 'Activer les notifications par email', 'boolean', 'notifications', '2025-06-03 15:38:24', NULL),
(7, 'notifications_sms', '0', 'Activer les notifications par SMS', 'boolean', 'notifications', '2025-06-03 15:38:24', NULL),
(8, 'max_file_size', '5242880', 'Taille maximale des fichiers en octets (5MB)', 'integer', 'uploads', '2025-06-03 15:38:24', NULL),
(9, 'allowed_file_types', 'jpg,jpeg,png,gif,pdf,doc,docx,zip', 'Types de fichiers autorisés', 'string', 'uploads', '2025-06-03 15:38:24', NULL),
(10, 'smtp_host', '', 'Serveur SMTP', 'string', 'email', '2025-06-03 15:38:24', NULL),
(11, 'smtp_port', '587', 'Port SMTP', 'integer', 'email', '2025-06-03 15:38:24', NULL),
(12, 'smtp_username', '', 'Nom d\'utilisateur SMTP', 'string', 'email', '2025-06-03 15:38:24', NULL),
(13, 'smtp_password', '', 'Mot de passe SMTP', 'string', 'email', '2025-06-03 15:38:24', NULL),
(14, 'google_analytics_id', '', 'ID Google Analytics', 'string', 'tracking', '2025-06-03 15:38:24', NULL),
(15, 'facebook_pixel_id', '', 'ID Facebook Pixel', 'string', 'tracking', '2025-06-03 15:38:24', NULL),
(16, 'maintenance_mode', '0', 'Mode maintenance activé', 'boolean', 'system', '2025-06-03 15:38:24', NULL),
(17, 'cache_enabled', '1', 'Cache activé', 'boolean', 'performance', '2025-06-03 15:38:24', NULL),
(18, 'cache_duration', '3600', 'Durée du cache en secondes', 'integer', 'performance', '2025-06-03 15:38:24', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` int NOT NULL,
  `numero_contact` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entreprise` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sujet` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut` enum('nouveau','lu','repondu','archive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'nouveau',
  `priorite` enum('basse','normale','haute','urgente') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normale',
  `notes_admin` text COLLATE utf8mb4_unicode_ci,
  `admin_assigne` int DEFAULT NULL,
  `date_reponse` datetime DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `source` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'site_web',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `newsletter` text COLLATE utf8mb4_unicode_ci,
  `rgpd` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contacts`
--

INSERT INTO `contacts` (`id`, `numero_contact`, `nom`, `email`, `telephone`, `entreprise`, `sujet`, `message`, `statut`, `priorite`, `notes_admin`, `admin_assigne`, `date_reponse`, `ip_address`, `user_agent`, `source`, `date_creation`, `date_modification`, `newsletter`, `rgpd`) VALUES
(1, 'CNT2025060001', 'richard de reyes', 'richardtiomela4@gmail.com', '+237672507275', 'RIch-tech', 'devis', 'Aidez moi', 'lu', 'normale', NULL, NULL, NULL, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', 'site_web', '2025-06-05 13:04:19', '2025-06-05 13:54:46', '1', '1'),
(2, 'CNT2025060002', 'richard de reyes', 'richardtiomela6@gmail.com', '', 'RIch-tech', 'graphique', 'GENUIS', 'archive', 'normale', NULL, 1, NULL, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', 'site_web', '2025-06-05 13:18:46', '2025-06-05 15:27:03', '1', '1');

--
-- Triggers `contacts`
--
DELIMITER $$
CREATE TRIGGER `generate_contact_number` BEFORE INSERT ON `contacts` FOR EACH ROW BEGIN
    IF NEW.numero_contact IS NULL OR NEW.numero_contact = '' THEN
        SET @count = (SELECT COUNT(*) FROM contacts WHERE YEAR(date_creation) = YEAR(NOW()) AND MONTH(date_creation) = MONTH(NOW())) + 1;
        SET NEW.numero_contact = CONCAT('CNT', YEAR(NOW()), LPAD(MONTH(NOW()), 2, '0'), LPAD(@count, 4, '0'));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `devis`
--

CREATE TABLE `devis` (
  `id` int NOT NULL,
  `numero_devis` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entreprise` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `poste` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `service` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sous_service` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `budget` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delai` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fichiers_joints` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON des fichiers uploadés',
  `statut` enum('nouveau','en_cours','termine','annule','en_attente') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'nouveau',
  `priorite` enum('basse','normale','haute','urgente') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normale',
  `montant_estime` decimal(10,2) DEFAULT NULL,
  `montant_final` decimal(10,2) DEFAULT NULL,
  `notes_admin` text COLLATE utf8mb4_unicode_ci,
  `notes_client` text COLLATE utf8mb4_unicode_ci,
  `date_debut` date DEFAULT NULL,
  `date_fin_prevue` date DEFAULT NULL,
  `date_fin_reelle` date DEFAULT NULL,
  `admin_assigne` int DEFAULT NULL,
  `source` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'site_web',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `newsletter` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `devis`
--

INSERT INTO `devis` (`id`, `numero_devis`, `nom`, `email`, `telephone`, `entreprise`, `poste`, `service`, `sous_service`, `description`, `budget`, `delai`, `fichiers_joints`, `statut`, `priorite`, `montant_estime`, `montant_final`, `notes_admin`, `notes_client`, `date_debut`, `date_fin_prevue`, `date_fin_reelle`, `admin_assigne`, `source`, `ip_address`, `user_agent`, `date_creation`, `date_modification`, `newsletter`) VALUES
(1, 'DEV2025060001', '9', 'richardtiomela4@gmail.com', '+237672507275', 'RIch-tech', 'Stagiaire', 'Marketing Digital', 'Publicité en ligne', 'urgent', '500000 fcfa', '3 semaines', NULL, 'nouveau', 'urgente', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'site_web', NULL, NULL, '2025-06-05 12:05:52', NULL, NULL),
(3, 'DEV202506052533', 'richard de reyes', 'richardtiomela4@gmail.com', '+237672507275', 'RIch-tech', 'Stagiaire', 'marketing', 'social-media', 'DUR', '100k-300k', 'urgent', NULL, 'nouveau', 'normale', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'site_web', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-05 13:36:55', NULL, '1');

--
-- Triggers `devis`
--
DELIMITER $$
CREATE TRIGGER `generate_devis_number` BEFORE INSERT ON `devis` FOR EACH ROW BEGIN
    IF NEW.numero_devis IS NULL OR NEW.numero_devis = '' THEN
        SET @count = (SELECT COUNT(*) FROM devis WHERE YEAR(date_creation) = YEAR(NOW()) AND MONTH(date_creation) = MONTH(NOW())) + 1;
        SET NEW.numero_devis = CONCAT('DEV', YEAR(NOW()), LPAD(MONTH(NOW()), 2, '0'), LPAD(@count, 4, '0'));
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_client_stats` AFTER UPDATE ON `devis` FOR EACH ROW BEGIN
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
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `factures`
--

CREATE TABLE `factures` (
  `id` int NOT NULL,
  `numero_facture` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `devis_id` int DEFAULT NULL,
  `client_id` int NOT NULL,
  `montant_ht` decimal(10,2) NOT NULL,
  `taux_tva` decimal(5,2) DEFAULT '19.25',
  `montant_tva` decimal(10,2) NOT NULL,
  `montant_ttc` decimal(10,2) NOT NULL,
  `statut` enum('brouillon','envoyee','payee','en_retard','annulee') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'brouillon',
  `date_emission` date NOT NULL,
  `date_echeance` date NOT NULL,
  `date_paiement` date DEFAULT NULL,
  `mode_paiement` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_paiement` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `conditions_paiement` text COLLATE utf8mb4_unicode_ci,
  `fichier_pdf` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faq`
--

CREATE TABLE `faq` (
  `id` int NOT NULL,
  `question` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reponse` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `categorie` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ordre` int NOT NULL DEFAULT '0',
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `faq`
--

INSERT INTO `faq` (`id`, `question`, `reponse`, `categorie`, `ordre`, `actif`, `date_creation`, `date_modification`) VALUES
(1, 'Quels sont vos délais de réalisation ?', 'Les délais varient selon le type de projet. En général, comptez 3-5 jours pour un logo, 1-2 semaines pour une identité complète, et 2-4 semaines pour une stratégie marketing.', 'general', 1, 1, '2025-06-05 13:16:32', NULL),
(2, 'Proposez-vous des révisions ?', 'Oui, nous incluons 3 révisions gratuites dans tous nos projets. Des révisions supplémentaires peuvent être facturées selon la complexité.', 'general', 2, 1, '2025-06-05 13:16:32', NULL),
(3, 'Travaillez-vous avec des entreprises de toutes tailles ?', 'Absolument ! Nous accompagnons aussi bien les startups que les grandes entreprises, en adaptant nos services à vos besoins et budget.', 'general', 3, 1, '2025-06-05 13:16:32', NULL),
(4, 'Quels formats de fichiers livrez-vous ?', 'Nous livrons tous les formats nécessaires : AI, EPS, PDF, PNG, JPG en haute résolution, ainsi que les fichiers sources modifiables.', 'technique', 4, 1, '2025-06-05 13:16:32', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `fichiers`
--

CREATE TABLE `fichiers` (
  `id` int NOT NULL,
  `nom_original` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom_fichier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `chemin` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_mime` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `taille` int NOT NULL,
  `extension` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `table_liee` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_enregistrement` int DEFAULT NULL,
  `type_fichier` enum('devis','contact','projet','facture','autre') COLLATE utf8mb4_unicode_ci DEFAULT 'autre',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_upload` int DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `logs_activite`
--

CREATE TABLE `logs_activite` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `table_concernee` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_enregistrement` int DEFAULT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `logs_activite`
--

INSERT INTO `logs_activite` (`id`, `user_id`, `action`, `table_concernee`, `id_enregistrement`, `details`, `ip_address`, `user_agent`, `date_creation`) VALUES
(1, 1, 'connexion_reussie', 'admins', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-03 23:20:03'),
(2, 1, 'deconnexion', 'admins', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-03 23:21:07'),
(3, 1, 'connexion_reussie', 'admins', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-03 23:35:51'),
(4, 1, 'tentative_connexion_echouee', 'admins', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-03 23:51:40'),
(5, 1, 'connexion_reussie', 'admins', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-03 23:51:55'),
(6, 1, 'deconnexion', 'admins', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-04 00:27:39'),
(7, 1, 'connexion_reussie', 'admins', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-04 09:59:41'),
(8, 1, 'connexion_reussie', 'admins', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-04 10:44:53'),
(9, 1, 'database_backup', 'system', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-04 11:03:23'),
(10, 1, 'connexion_reussie', 'admins', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-04 12:00:15'),
(11, 1, 'connexion_reussie', 'admins', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-04 12:54:50'),
(12, 1, 'contacts_all_read', 'contacts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-04 16:27:13'),
(13, NULL, 'add_client', 'clients', 1, 'Nouveau client ajouté: richard de reyes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-04 16:28:52'),
(14, NULL, 'add_client', 'clients', 3, 'Nouveau client ajouté: richard de reyes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-04 16:44:53'),
(15, NULL, 'add_client', 'clients', 4, 'Nouveau client ajouté: richard de reyes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-04 16:47:32'),
(16, NULL, 'add_client', 'clients', 6, 'Nouveau client ajouté: richard de reyes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-04 16:51:10'),
(17, 1, 'update_client', 'clients', NULL, 'Client mis à jour: richard de reyes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-04 16:52:40'),
(18, 1, 'update_client', 'clients', 0, 'Client mis à jour: richard de reyes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-04 17:04:06'),
(19, NULL, 'add_client', 'clients', 11, 'Nouveau client ajouté: richard de reyes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-04 17:05:25'),
(20, NULL, 'add_client', 'clients', 12, 'Nouveau client ajouté: richard de reyes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-04 17:06:13'),
(21, 1, 'contacts_all_read', 'contacts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-04 17:07:18'),
(22, 1, 'deconnexion', 'admins', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-05 11:04:46'),
(23, 1, 'connexion_reussie', 'admins', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-05 11:05:11'),
(24, 1, 'connexion_reussie', 'admins', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-05 11:11:23'),
(25, 1, 'connexion_reussie', 'admins', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-05 11:40:05'),
(26, 1, 'devis_create', 'devis', 0, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-05 12:05:52'),
(27, 1, 'devis_create', 'devis', 0, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-05 12:06:24'),
(28, 1, 'cache_cleared', 'system', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-05 12:56:07'),
(29, 1, 'logs_cleared', 'logs_activite', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-05 12:56:15'),
(31, 1, 'devis_delete', 'devis', 2, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-05 13:13:46'),
(33, 1, 'contacts_all_read', 'contacts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-05 13:54:46'),
(34, 1, 'contact_archived', 'contacts', 2, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-05 14:39:04'),
(35, 1, 'contact_archived', 'contacts', 2, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-05 14:39:04'),
(36, 1, 'contact_assigned', 'contacts', 2, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-05 15:27:03'),
(37, 1, 'deconnexion', 'admins', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-06 08:54:52'),
(38, 1, 'connexion_reussie', 'admins', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-06 08:57:18'),
(39, 1, 'connexion_reussie', 'admins', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-06 09:09:37'),
(40, 1, 'connexion_reussie', 'admins', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-06 09:13:31'),
(41, 1, 'connexion_reussie', 'admins', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-06 09:27:25'),
(42, 1, 'deconnexion', 'admins', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-06 13:00:26'),
(43, 1, 'connexion_reussie', 'admins', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-08 11:05:40');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `admin_id` int NOT NULL,
  `titre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('info','success','warning','error') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `lu` tinyint(1) NOT NULL DEFAULT '0',
  `action_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `table_liee` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_enregistrement` int DEFAULT NULL,
  `date_lecture` datetime DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parametres`
--

CREATE TABLE `parametres` (
  `id` int NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cle` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valeur` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `parametres`
--

INSERT INTO `parametres` (`id`, `type`, `cle`, `valeur`, `description`, `date_creation`, `date_modification`) VALUES
(1, 'contact', 'adresse', 'Douala, Akwa Nord<br>Cameroun', 'Adresse de l\'entreprise', '2025-06-05 13:16:32', NULL),
(2, 'contact', 'telephone1', '+237 6XX XXX XXX', 'Numéro de téléphone principal', '2025-06-05 13:16:32', NULL),
(3, 'contact', 'telephone2', '+237 6XX XXX XXX', 'Numéro de téléphone secondaire', '2025-06-05 13:16:32', NULL),
(4, 'contact', 'email1', 'contact@divineartcorp.cm', 'Email principal', '2025-06-05 13:16:32', NULL),
(5, 'contact', 'email2', 'info@divineartcorp.cm', 'Email secondaire', '2025-06-05 13:16:32', NULL),
(6, 'contact', 'horaires', 'Lun - Ven: 8h00 - 18h00<br>Sam: 9h00 - 15h00', 'Horaires d\'ouverture', '2025-06-05 13:16:32', NULL),
(7, 'contact', 'map_url', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3979.808258706028!2d9.735686!3d4.01498!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNMKwMDAnNTMuOSJOIDnCsDQ0JzA4LjUiRQ!5e0!3m2!1sfr!2scm!4v1717500000000!5m2!1sfr!2scm', 'URL de la carte Google Maps', '2025-06-05 13:16:32', NULL),
(8, 'social', 'facebook', 'https://facebook.com/divineartcorp', 'Lien Facebook', '2025-06-05 13:16:32', NULL),
(9, 'social', 'instagram', 'https://instagram.com/divineartcorp', 'Lien Instagram', '2025-06-05 13:16:32', NULL),
(10, 'social', 'linkedin', 'https://linkedin.com/company/divineartcorp', 'Lien LinkedIn', '2025-06-05 13:16:32', NULL),
(11, 'social', 'twitter', 'https://twitter.com/divineartcorp', 'Lien Twitter', '2025-06-05 13:16:32', NULL),
(12, 'social', 'whatsapp', 'https://wa.me/237XXXXXXXXX', 'Lien WhatsApp', '2025-06-05 13:16:32', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `projets`
--

CREATE TABLE `projets` (
  `id` int NOT NULL,
  `devis_id` int NOT NULL,
  `nom` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `statut` enum('planifie','en_cours','en_pause','termine','annule') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'planifie',
  `progression` int DEFAULT '0' COMMENT 'Pourcentage de progression',
  `date_debut` date DEFAULT NULL,
  `date_fin_prevue` date DEFAULT NULL,
  `date_fin_reelle` date DEFAULT NULL,
  `budget_alloue` decimal(10,2) DEFAULT NULL,
  `cout_reel` decimal(10,2) DEFAULT NULL,
  `admin_responsable` int DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `fichiers_livres` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON des fichiers livrés',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `featured` int NOT NULL DEFAULT '0',
  `in_portfolio` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `projets_multimedia`
--

CREATE TABLE `projets_multimedia` (
  `id` int NOT NULL,
  `titre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `type_service` enum('video_promotionnelle','animation_2d','animation_3d','montage_video','spot_publicitaire','video_corporate','motion_design') COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_id` int DEFAULT NULL,
  `client_nom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut` enum('nouveau','pre_production','production','post_production','termine','annule') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'nouveau',
  `priorite` enum('basse','normale','haute','urgente') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normale',
  `duree_prevue` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `format_sortie` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `budget_estime` decimal(12,2) DEFAULT NULL,
  `budget_final` decimal(12,2) DEFAULT NULL,
  `date_debut` date DEFAULT NULL,
  `date_fin_prevue` date DEFAULT NULL,
  `date_fin_reelle` date DEFAULT NULL,
  `progression` int DEFAULT '0',
  `fichiers_sources` text COLLATE utf8mb4_unicode_ci,
  `fichier_final` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes_production` text COLLATE utf8mb4_unicode_ci,
  `feedback_client` text COLLATE utf8mb4_unicode_ci,
  `admin_assigne` int DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sauvegardes`
--

CREATE TABLE `sauvegardes` (
  `id` int NOT NULL,
  `nom_fichier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `chemin` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `taille` bigint NOT NULL,
  `type` enum('automatique','manuelle') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'automatique',
  `statut` enum('en_cours','termine','erreur') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_cours',
  `admin_id` int DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_fin` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `description_courte` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `couleur` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#e74c3c',
  `prix_base` decimal(10,2) DEFAULT NULL,
  `duree_estimee` int DEFAULT NULL COMMENT 'Durée en jours',
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `ordre` int DEFAULT '0',
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `nom`, `slug`, `description`, `description_courte`, `icone`, `couleur`, `prix_base`, `duree_estimee`, `actif`, `ordre`, `meta_title`, `meta_description`, `date_creation`, `date_modification`) VALUES
(1, 'Marketing Digital', 'marketing', 'Stratégies marketing complètes pour développer votre présence en ligne et atteindre vos objectifs commerciaux.', 'Développez votre présence digitale', 'fas fa-bullhorn', '#e74c3c', 1500.00, 30, 1, 1, NULL, NULL, '2025-06-03 15:38:24', NULL),
(2, 'Conception Graphique', 'graphique', 'Création d\'identités visuelles, logos, supports de communication pour renforcer votre image de marque.', 'Créez votre identité visuelle', 'fas fa-paint-brush', '#9b59b6', 800.00, 15, 1, 2, NULL, NULL, '2025-06-03 15:38:24', NULL),
(3, 'Conception Multimédia', 'multimedia', 'Production de contenus vidéo, animations, présentations interactives pour captiver votre audience.', 'Donnez vie à vos idées', 'fas fa-video', '#3498db', 2000.00, 45, 1, 3, NULL, NULL, '2025-06-03 15:38:24', NULL),
(4, 'Imprimerie', 'imprimerie', 'Services d\'impression professionnelle pour tous vos supports de communication physiques.', 'Imprimez avec qualité', 'fas fa-print', '#27ae60', 300.00, 7, 1, 4, NULL, NULL, '2025-06-03 15:38:24', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sessions_admin`
--

CREATE TABLE `sessions_admin` (
  `id` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `admin_id` int NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `donnees` text COLLATE utf8mb4_unicode_ci,
  `derniere_activite` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sous_services`
--

CREATE TABLE `sous_services` (
  `id` int NOT NULL,
  `service_id` int NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `prix_base` decimal(10,2) DEFAULT NULL,
  `duree_estimee` int DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `ordre` int DEFAULT '0',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sous_services`
--

INSERT INTO `sous_services` (`id`, `service_id`, `nom`, `slug`, `description`, `prix_base`, `duree_estimee`, `actif`, `ordre`, `date_creation`) VALUES
(1, 1, 'Stratégie Social Media', 'social-media', 'Gestion complète de vos réseaux sociaux', 800.00, 30, 1, 1, '2025-06-03 15:38:24'),
(2, 1, 'Publicité en ligne', 'publicite-ligne', 'Campagnes publicitaires Google Ads, Facebook Ads', 1200.00, 15, 1, 2, '2025-06-03 15:38:24'),
(3, 1, 'SEO/Référencement', 'seo-referencement', 'Optimisation pour les moteurs de recherche', 1000.00, 60, 1, 3, '2025-06-03 15:38:24'),
(4, 2, 'Création de logo', 'creation-logo', 'Design de logo professionnel et unique', 500.00, 7, 1, 1, '2025-06-03 15:38:24'),
(5, 2, 'Charte graphique', 'charte-graphique', 'Identité visuelle complète', 1200.00, 14, 1, 2, '2025-06-03 15:38:24'),
(6, 2, 'Supports print', 'supports-print', 'Flyers, brochures, cartes de visite', 300.00, 5, 1, 3, '2025-06-03 15:38:24'),
(7, 3, 'Vidéo promotionnelle', 'video-promotionnelle', 'Création de vidéos marketing', 1500.00, 21, 1, 1, '2025-06-03 15:38:24'),
(8, 3, 'Animation 2D/3D', 'animation-2d-3d', 'Animations graphiques professionnelles', 2500.00, 30, 1, 2, '2025-06-03 15:38:24'),
(9, 3, 'Montage vidéo', 'montage-video', 'Post-production et montage', 800.00, 10, 1, 3, '2025-06-03 15:38:24'),
(10, 4, 'Impression offset', 'impression-offset', 'Impression haute qualité grand volume', 200.00, 3, 1, 1, '2025-06-03 15:38:24'),
(11, 4, 'Impression numérique', 'impression-numerique', 'Impression rapide petit volume', 150.00, 1, 1, 2, '2025-06-03 15:38:24'),
(12, 4, 'Finition', 'finition', 'Reliure, pelliculage, découpe', 100.00, 2, 1, 3, '2025-06-03 15:38:24');

-- --------------------------------------------------------

--
-- Table structure for table `statistiques`
--

CREATE TABLE `statistiques` (
  `id` int NOT NULL,
  `date_stat` date NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valeur` decimal(15,2) NOT NULL DEFAULT '0.00',
  `donnees_json` json DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `statistiques`
--

INSERT INTO `statistiques` (`id`, `date_stat`, `type`, `valeur`, `donnees_json`, `date_creation`) VALUES
(1, '2025-06-03', 'devis_total', 0.00, '{\"en_cours\": null, \"nouveaux\": null, \"termines\": null, \"montant_total\": 0}', '2025-06-04 08:06:52'),
(2, '2025-06-03', 'contacts_total', 0.00, '{\"lus\": null, \"nouveaux\": null, \"repondus\": null}', '2025-06-04 08:06:52'),
(3, '2025-06-04', 'devis_total', 0.00, '{\"en_cours\": null, \"nouveaux\": null, \"termines\": null, \"montant_total\": 0}', '2025-06-05 00:00:00'),
(4, '2025-06-04', 'contacts_total', 0.00, '{\"lus\": null, \"nouveaux\": null, \"repondus\": null}', '2025-06-05 00:00:00'),
(5, '2025-06-05', 'devis_total', 2.00, '{\"en_cours\": 0, \"nouveaux\": 2, \"termines\": 0, \"montant_total\": 0}', '2025-06-06 13:36:06'),
(6, '2025-06-05', 'contacts_total', 2.00, '{\"lus\": 1, \"nouveaux\": 0, \"repondus\": 0}', '2025-06-06 13:36:06'),
(7, '2025-06-06', 'devis_total', 0.00, '{\"en_cours\": null, \"nouveaux\": null, \"termines\": null, \"montant_total\": 0}', '2025-06-07 00:00:00'),
(8, '2025-06-06', 'contacts_total', 0.00, '{\"lus\": null, \"nouveaux\": null, \"repondus\": null}', '2025-06-07 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `taches`
--

CREATE TABLE `taches` (
  `id` int NOT NULL,
  `projet_id` int NOT NULL,
  `nom` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `statut` enum('a_faire','en_cours','termine','annule') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'a_faire',
  `priorite` enum('basse','normale','haute','urgente') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normale',
  `date_debut` date DEFAULT NULL,
  `date_fin_prevue` date DEFAULT NULL,
  `date_fin_reelle` date DEFAULT NULL,
  `temps_estime` int DEFAULT NULL COMMENT 'Temps en heures',
  `temps_passe` int DEFAULT NULL COMMENT 'Temps en heures',
  `admin_assigne` int DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `ordre` int DEFAULT '0',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `vue_dashboard`
-- (See below for the actual view)
--
CREATE TABLE `vue_dashboard` (
`type` varchar(8)
,`total` bigint
,`en_attente` decimal(23,0)
,`en_cours` decimal(23,0)
,`termines` decimal(23,0)
,`aujourd_hui` decimal(23,0)
,`cette_semaine` decimal(23,0)
,`ce_mois` decimal(23,0)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vue_stats_contacts`
-- (See below for the actual view)
--
CREATE TABLE `vue_stats_contacts` (
`date_stat` date
,`total_contacts` bigint
,`contacts_nouveaux` decimal(23,0)
,`contacts_lus` decimal(23,0)
,`contacts_repondus` decimal(23,0)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vue_stats_devis`
-- (See below for the actual view)
--
CREATE TABLE `vue_stats_devis` (
`date_stat` date
,`total_devis` bigint
,`devis_nouveaux` decimal(23,0)
,`devis_en_cours` decimal(23,0)
,`devis_termines` decimal(23,0)
,`devis_annules` decimal(23,0)
,`montant_moyen` decimal(14,6)
,`montant_total` decimal(32,2)
);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_statut` (`statut`);

--
-- Indexes for table `campagnes_marketing`
--
ALTER TABLE `campagnes_marketing`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_statut` (`statut`),
  ADD KEY `idx_type_campagne` (`type_campagne`),
  ADD KEY `idx_client_email` (`client_email`),
  ADD KEY `idx_date_debut` (`date_debut`),
  ADD KEY `idx_date_fin` (`date_fin`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_statut` (`statut`),
  ADD KEY `idx_entreprise` (`entreprise`);

--
-- Indexes for table `configurations`
--
ALTER TABLE `configurations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cle` (`cle`),
  ADD KEY `idx_categorie` (`categorie`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_contact` (`numero_contact`),
  ADD KEY `idx_statut` (`statut`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_date_creation` (`date_creation`),
  ADD KEY `fk_contacts_admin` (`admin_assigne`),
  ADD KEY `idx_contacts_statut_date` (`statut`,`date_creation`);
ALTER TABLE `contacts` ADD FULLTEXT KEY `idx_contacts_search` (`nom`,`email`,`sujet`,`message`);

--
-- Indexes for table `devis`
--
ALTER TABLE `devis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_devis` (`numero_devis`),
  ADD KEY `idx_statut` (`statut`),
  ADD KEY `idx_service` (`service`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_date_creation` (`date_creation`),
  ADD KEY `idx_priorite` (`priorite`),
  ADD KEY `fk_devis_admin` (`admin_assigne`),
  ADD KEY `idx_devis_statut_date` (`statut`,`date_creation`),
  ADD KEY `idx_devis_service_statut` (`service`,`statut`);
ALTER TABLE `devis` ADD FULLTEXT KEY `idx_devis_search` (`nom`,`email`,`entreprise`,`description`);

--
-- Indexes for table `factures`
--
ALTER TABLE `factures`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_facture` (`numero_facture`),
  ADD KEY `fk_factures_devis` (`devis_id`),
  ADD KEY `fk_factures_client` (`client_id`),
  ADD KEY `idx_statut` (`statut`);

--
-- Indexes for table `faq`
--
ALTER TABLE `faq`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fichiers`
--
ALTER TABLE `fichiers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_fichiers_admin` (`admin_upload`),
  ADD KEY `idx_table_liee` (`table_liee`,`id_enregistrement`);

--
-- Indexes for table `logs_activite`
--
ALTER TABLE `logs_activite`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_logs_admin` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_date` (`date_creation`),
  ADD KEY `idx_logs_user_date` (`user_id`,`date_creation`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notifications_admin` (`admin_id`),
  ADD KEY `idx_lu` (`lu`),
  ADD KEY `idx_date_creation` (`date_creation`);

--
-- Indexes for table `parametres`
--
ALTER TABLE `parametres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_type_cle` (`type`,`cle`);

--
-- Indexes for table `projets`
--
ALTER TABLE `projets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_projets_devis` (`devis_id`),
  ADD KEY `fk_projets_admin` (`admin_responsable`),
  ADD KEY `idx_statut` (`statut`);

--
-- Indexes for table `projets_multimedia`
--
ALTER TABLE `projets_multimedia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_statut` (`statut`),
  ADD KEY `idx_type_service` (`type_service`),
  ADD KEY `idx_client_email` (`client_email`),
  ADD KEY `idx_date_creation` (`date_creation`);

--
-- Indexes for table `sauvegardes`
--
ALTER TABLE `sauvegardes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sauvegardes_admin` (`admin_id`),
  ADD KEY `idx_date_creation` (`date_creation`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_actif` (`actif`),
  ADD KEY `idx_ordre` (`ordre`);

--
-- Indexes for table `sessions_admin`
--
ALTER TABLE `sessions_admin`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sessions_admin` (`admin_id`),
  ADD KEY `idx_derniere_activite` (`derniere_activite`);

--
-- Indexes for table `sous_services`
--
ALTER TABLE `sous_services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sous_services_service` (`service_id`),
  ADD KEY `idx_actif` (`actif`);

--
-- Indexes for table `statistiques`
--
ALTER TABLE `statistiques`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_date_type` (`date_stat`,`type`),
  ADD KEY `idx_date_stat` (`date_stat`),
  ADD KEY `idx_type` (`type`);

--
-- Indexes for table `taches`
--
ALTER TABLE `taches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_taches_projet` (`projet_id`),
  ADD KEY `fk_taches_admin` (`admin_assigne`),
  ADD KEY `idx_statut` (`statut`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `campagnes_marketing`
--
ALTER TABLE `campagnes_marketing`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `configurations`
--
ALTER TABLE `configurations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `devis`
--
ALTER TABLE `devis`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `factures`
--
ALTER TABLE `factures`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faq`
--
ALTER TABLE `faq`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `fichiers`
--
ALTER TABLE `fichiers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `logs_activite`
--
ALTER TABLE `logs_activite`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `parametres`
--
ALTER TABLE `parametres`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `projets`
--
ALTER TABLE `projets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projets_multimedia`
--
ALTER TABLE `projets_multimedia`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sauvegardes`
--
ALTER TABLE `sauvegardes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sous_services`
--
ALTER TABLE `sous_services`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `statistiques`
--
ALTER TABLE `statistiques`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `taches`
--
ALTER TABLE `taches`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Structure for view `vue_dashboard`
--
DROP TABLE IF EXISTS `vue_dashboard`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vue_dashboard`  AS SELECT 'devis' AS `type`, count(0) AS `total`, sum((case when (`devis`.`statut` = 'nouveau') then 1 else 0 end)) AS `en_attente`, sum((case when (`devis`.`statut` = 'en_cours') then 1 else 0 end)) AS `en_cours`, sum((case when (`devis`.`statut` = 'termine') then 1 else 0 end)) AS `termines`, sum((case when (cast(`devis`.`date_creation` as date) = curdate()) then 1 else 0 end)) AS `aujourd_hui`, sum((case when ((week(`devis`.`date_creation`,0) = week(curdate(),0)) and (year(`devis`.`date_creation`) = year(curdate()))) then 1 else 0 end)) AS `cette_semaine`, sum((case when ((month(`devis`.`date_creation`) = month(curdate())) and (year(`devis`.`date_creation`) = year(curdate()))) then 1 else 0 end)) AS `ce_mois` FROM `devis`union all select 'contacts' AS `type`,count(0) AS `total`,sum((case when (`contacts`.`statut` = 'nouveau') then 1 else 0 end)) AS `en_attente`,sum((case when (`contacts`.`statut` = 'lu') then 1 else 0 end)) AS `en_cours`,sum((case when (`contacts`.`statut` = 'repondu') then 1 else 0 end)) AS `termines`,sum((case when (cast(`contacts`.`date_creation` as date) = curdate()) then 1 else 0 end)) AS `aujourd_hui`,sum((case when ((week(`contacts`.`date_creation`,0) = week(curdate(),0)) and (year(`contacts`.`date_creation`) = year(curdate()))) then 1 else 0 end)) AS `cette_semaine`,sum((case when ((month(`contacts`.`date_creation`) = month(curdate())) and (year(`contacts`.`date_creation`) = year(curdate()))) then 1 else 0 end)) AS `ce_mois` from `contacts`  ;

-- --------------------------------------------------------

--
-- Structure for view `vue_stats_contacts`
--
DROP TABLE IF EXISTS `vue_stats_contacts`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vue_stats_contacts`  AS SELECT cast(`contacts`.`date_creation` as date) AS `date_stat`, count(0) AS `total_contacts`, sum((case when (`contacts`.`statut` = 'nouveau') then 1 else 0 end)) AS `contacts_nouveaux`, sum((case when (`contacts`.`statut` = 'lu') then 1 else 0 end)) AS `contacts_lus`, sum((case when (`contacts`.`statut` = 'repondu') then 1 else 0 end)) AS `contacts_repondus` FROM `contacts` GROUP BY cast(`contacts`.`date_creation` as date) ORDER BY `date_stat` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `vue_stats_devis`
--
DROP TABLE IF EXISTS `vue_stats_devis`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vue_stats_devis`  AS SELECT cast(`devis`.`date_creation` as date) AS `date_stat`, count(0) AS `total_devis`, sum((case when (`devis`.`statut` = 'nouveau') then 1 else 0 end)) AS `devis_nouveaux`, sum((case when (`devis`.`statut` = 'en_cours') then 1 else 0 end)) AS `devis_en_cours`, sum((case when (`devis`.`statut` = 'termine') then 1 else 0 end)) AS `devis_termines`, sum((case when (`devis`.`statut` = 'annule') then 1 else 0 end)) AS `devis_annules`, avg(`devis`.`montant_final`) AS `montant_moyen`, sum(`devis`.`montant_final`) AS `montant_total` FROM `devis` GROUP BY cast(`devis`.`date_creation` as date) ORDER BY `date_stat` DESC ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `contacts`
--
ALTER TABLE `contacts`
  ADD CONSTRAINT `fk_contacts_admin` FOREIGN KEY (`admin_assigne`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `devis`
--
ALTER TABLE `devis`
  ADD CONSTRAINT `fk_devis_admin` FOREIGN KEY (`admin_assigne`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `factures`
--
ALTER TABLE `factures`
  ADD CONSTRAINT `fk_factures_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_factures_devis` FOREIGN KEY (`devis_id`) REFERENCES `devis` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `fichiers`
--
ALTER TABLE `fichiers`
  ADD CONSTRAINT `fk_fichiers_admin` FOREIGN KEY (`admin_upload`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `logs_activite`
--
ALTER TABLE `logs_activite`
  ADD CONSTRAINT `fk_logs_admin` FOREIGN KEY (`user_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `projets`
--
ALTER TABLE `projets`
  ADD CONSTRAINT `fk_projets_admin` FOREIGN KEY (`admin_responsable`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_projets_devis` FOREIGN KEY (`devis_id`) REFERENCES `devis` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sauvegardes`
--
ALTER TABLE `sauvegardes`
  ADD CONSTRAINT `fk_sauvegardes_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sessions_admin`
--
ALTER TABLE `sessions_admin`
  ADD CONSTRAINT `fk_sessions_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sous_services`
--
ALTER TABLE `sous_services`
  ADD CONSTRAINT `fk_sous_services_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `taches`
--
ALTER TABLE `taches`
  ADD CONSTRAINT `fk_taches_admin` FOREIGN KEY (`admin_assigne`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_taches_projet` FOREIGN KEY (`projet_id`) REFERENCES `projets` (`id`) ON DELETE CASCADE;

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `ev_nettoyer_sessions` ON SCHEDULE EVERY 1 DAY STARTS '2025-06-03 15:38:25' ON COMPLETION NOT PRESERVE ENABLE DO CALL nettoyer_sessions()$$

CREATE DEFINER=`root`@`localhost` EVENT `ev_nettoyer_logs` ON SCHEDULE EVERY 1 WEEK STARTS '2025-06-03 15:38:25' ON COMPLETION NOT PRESERVE ENABLE DO CALL nettoyer_logs()$$

CREATE DEFINER=`root`@`localhost` EVENT `ev_stats_quotidiennes` ON SCHEDULE EVERY 1 DAY STARTS '2025-06-04 00:00:00' ON COMPLETION NOT PRESERVE ENABLE DO CALL calculer_stats_quotidiennes(CURDATE() - INTERVAL 1 DAY)$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
