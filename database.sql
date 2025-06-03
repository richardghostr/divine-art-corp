-- Script de création de la base de données Divine Art Corporation
-- Version: 1.0
-- Date: 2024

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Création de la base de données
CREATE DATABASE IF NOT EXISTS `divine_art_corp` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `divine_art_corp`;

-- --------------------------------------------------------
-- Structure de la table `admin_users`
-- --------------------------------------------------------

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','manager','editor') DEFAULT 'admin',
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `contacts`
-- --------------------------------------------------------

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `entreprise` varchar(100) DEFAULT NULL,
  `sujet` varchar(100) DEFAULT NULL,
  `message` text NOT NULL,
  `newsletter` tinyint(1) DEFAULT 0,
  `statut` enum('nouveau','lu','traite','archive') DEFAULT 'nouveau',
  `repondu` tinyint(1) DEFAULT 0,
  `date_reponse` timestamp NULL DEFAULT NULL,
  `notes_admin` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_statut` (`statut`),
  KEY `idx_date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `devis`
-- --------------------------------------------------------

CREATE TABLE `devis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero_devis` varchar(20) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `entreprise` varchar(100) DEFAULT NULL,
  `poste` varchar(100) DEFAULT NULL,
  `service` varchar(50) NOT NULL,
  `sous_service` varchar(100) DEFAULT NULL,
  `description` text NOT NULL,
  `budget` varchar(50) DEFAULT NULL,
  `delai` varchar(50) DEFAULT NULL,
  `newsletter` tinyint(1) DEFAULT 0,
  `statut` enum('nouveau','en_cours','termine','annule') DEFAULT 'nouveau',
  `priorite` enum('basse','normale','haute','urgente') DEFAULT 'normale',
  `montant_estime` decimal(12,2) DEFAULT NULL,
  `montant_final` decimal(12,2) DEFAULT NULL,
  `date_debut` date DEFAULT NULL,
  `date_fin_prevue` date DEFAULT NULL,
  `date_fin_reelle` date DEFAULT NULL,
  `notes_admin` text DEFAULT NULL,
  `fichiers_joints` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_devis` (`numero_devis`),
  KEY `idx_email` (`email`),
  KEY `idx_service` (`service`),
  KEY `idx_statut` (`statut`),
  KEY `idx_date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `services`
-- --------------------------------------------------------

CREATE TABLE `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `description_courte` varchar(255) DEFAULT NULL,
  `icone` varchar(50) DEFAULT NULL,
  `couleur` varchar(20) DEFAULT NULL,
  `prix_min` decimal(10,2) DEFAULT NULL,
  `prix_max` decimal(10,2) DEFAULT NULL,
  `duree_min` int(11) DEFAULT NULL COMMENT 'Durée en jours',
  `duree_max` int(11) DEFAULT NULL COMMENT 'Durée en jours',
  `ordre_affichage` int(11) DEFAULT 0,
  `actif` tinyint(1) DEFAULT 1,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(255) DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_actif` (`actif`),
  KEY `idx_ordre` (`ordre_affichage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `sous_services`
-- --------------------------------------------------------

CREATE TABLE `sous_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `prix_min` decimal(10,2) DEFAULT NULL,
  `prix_max` decimal(10,2) DEFAULT NULL,
  `duree_estimee` int(11) DEFAULT NULL COMMENT 'Durée en jours',
  `ordre_affichage` int(11) DEFAULT 0,
  `actif` tinyint(1) DEFAULT 1,
  `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `service_slug` (`service_id`, `slug`),
  KEY `idx_service_id` (`service_id`),
  KEY `idx_actif` (`actif`),
  CONSTRAINT `fk_sous_services_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `portfolio`
-- --------------------------------------------------------

CREATE TABLE `portfolio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `client` varchar(100) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `sous_service_id` int(11) DEFAULT NULL,
  `image_principale` varchar(255) DEFAULT NULL,
  `images_galerie` text DEFAULT NULL COMMENT 'JSON array of image paths',
  `url_projet` varchar(255) DEFAULT NULL,
  `technologies` varchar(255) DEFAULT NULL,
  `date_realisation` date DEFAULT NULL,
  `duree_projet` int(11) DEFAULT NULL COMMENT 'Durée en jours',
  `featured` tinyint(1) DEFAULT 0,
  `ordre_affichage` int(11) DEFAULT 0,
  `actif` tinyint(1) DEFAULT 1,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(255) DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_service_id` (`service_id`),
  KEY `idx_featured` (`featured`),
  KEY `idx_actif` (`actif`),
  CONSTRAINT `fk_portfolio_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `temoignages`
-- --------------------------------------------------------

CREATE TABLE `temoignages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom_client` varchar(100) NOT NULL,
  `entreprise` varchar(100) DEFAULT NULL,
  `poste` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `temoignage` text NOT NULL,
  `note` int(1) DEFAULT NULL CHECK (`note` >= 1 AND `note` <= 5),
  `photo_client` varchar(255) DEFAULT NULL,
  `service_concerne` varchar(100) DEFAULT NULL,
  `approuve` tinyint(1) DEFAULT 0,
  `featured` tinyint(1) DEFAULT 0,
  `ordre_affichage` int(11) DEFAULT 0,
  `actif` tinyint(1) DEFAULT 1,
  `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_approuve` (`approuve`),
  KEY `idx_featured` (`featured`),
  KEY `idx_actif` (`actif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `newsletter`
-- --------------------------------------------------------

CREATE TABLE `newsletter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `nom` varchar(100) DEFAULT NULL,
  `statut` enum('actif','inactif','desabonne') DEFAULT 'actif',
  `token_desabonnement` varchar(255) DEFAULT NULL,
  `source` varchar(50) DEFAULT NULL COMMENT 'contact, devis, inscription',
  `ip_address` varchar(45) DEFAULT NULL,
  `date_inscription` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_desabonnement` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_statut` (`statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `parametres`
-- --------------------------------------------------------

CREATE TABLE `parametres` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cle` varchar(100) NOT NULL,
  `valeur` text DEFAULT NULL,
  `type` enum('text','number','boolean','json','file') DEFAULT 'text',
  `description` varchar(255) DEFAULT NULL,
  `groupe` varchar(50) DEFAULT 'general',
  `ordre_affichage` int(11) DEFAULT 0,
  `date_modification` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cle` (`cle`),
  KEY `idx_groupe` (`groupe`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `logs_activite`
-- --------------------------------------------------------

CREATE TABLE `logs_activite` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_concernee` varchar(50) DEFAULT NULL,
  `id_enregistrement` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_date_creation` (`date_creation`),
  CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Insertion des données par défaut
-- --------------------------------------------------------

-- Insertion des utilisateurs admin
INSERT INTO `admin_users` (`username`, `password`, `email`, `role`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@divineartcorp.cm', 'admin'),
('manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager@divineartcorp.cm', 'manager');

-- Insertion des services
INSERT INTO `services` (`nom`, `slug`, `description`, `description_courte`, `icone`, `couleur`, `prix_min`, `prix_max`, `duree_min`, `duree_max`, `ordre_affichage`, `actif`) VALUES
('Marketing Digital', 'marketing-digital', 'Stratégies digitales performantes pour développer votre présence en ligne et générer plus de leads qualifiés.', 'Stratégies digitales performantes pour booster votre présence en ligne', 'fas fa-chart-line', '#e74c3c', 75000.00, 500000.00, 7, 60, 1, 1),
('Conception Graphique', 'conception-graphique', 'Identité visuelle professionnelle pour marquer les esprits et créer une image de marque forte.', 'Identité visuelle professionnelle pour marquer les esprits', 'fas fa-palette', '#3498db', 25000.00, 300000.00, 3, 21, 2, 1),
('Conception Multimédia', 'conception-multimedia', 'Contenus visuels impactants pour captiver votre audience sur tous les canaux de communication.', 'Contenus visuels impactants pour captiver votre audience', 'fas fa-video', '#9b59b6', 100000.00, 800000.00, 5, 45, 3, 1),
('Imprimerie', 'imprimerie', 'Impression haute qualité pour tous vos supports marketing, du petit format aux grands panneaux.', 'Impression haute qualité pour tous vos supports marketing', 'fas fa-print', '#27ae60', 5000.00, 200000.00, 1, 14, 4, 1);

-- Insertion des sous-services
INSERT INTO `sous_services` (`service_id`, `nom`, `slug`, `description`, `prix_min`, `prix_max`, `duree_estimee`, `ordre_affichage`, `actif`) VALUES
-- Marketing Digital
(1, 'SEO & SEM', 'seo-sem', 'Optimisation pour les moteurs de recherche et campagnes publicitaires Google Ads', 150000.00, 300000.00, 30, 1, 1),
(1, 'Réseaux Sociaux', 'reseaux-sociaux', 'Gestion complète de vos réseaux sociaux et community management', 100000.00, 200000.00, 30, 2, 1),
(1, 'Email Marketing', 'email-marketing', 'Campagnes d\'email marketing personnalisées et automatisées', 75000.00, 150000.00, 14, 3, 1),
(1, 'Campagnes Publicitaires', 'campagnes-publicitaires', 'Création et gestion de campagnes publicitaires multi-canaux', 200000.00, 500000.00, 21, 4, 1),
(1, 'Études de Marché', 'etudes-marche', 'Analyses approfondies de votre marché et de la concurrence', 300000.00, 400000.00, 21, 5, 1),
(1, 'Relations Publiques', 'relations-publiques', 'Gestion de votre image de marque et communication événementielle', 250000.00, 350000.00, 30, 6, 1),

-- Conception Graphique
(2, 'Identité Visuelle', 'identite-visuelle', 'Création complète de votre identité de marque avec logo et charte graphique', 200000.00, 300000.00, 14, 1, 1),
(2, 'Supports de Communication', 'supports-communication', 'Design de flyers, brochures, cartes de visite et affiches', 25000.00, 100000.00, 5, 2, 1),
(2, 'Packaging & Étiquetage', 'packaging-etiquetage', 'Conception d\'emballages attractifs et d\'étiquettes produits', 150000.00, 250000.00, 10, 3, 1),
(2, 'Illustrations & Infographies', 'illustrations-infographies', 'Créations visuelles sur mesure pour illustrer vos concepts', 75000.00, 150000.00, 7, 4, 1),
(2, 'UI/UX Design', 'ui-ux-design', 'Maquettes web et mobile optimisées pour l\'expérience utilisateur', 300000.00, 500000.00, 21, 5, 1),
(2, 'Design Textile', 'design-textile', 'Création de designs pour vêtements et objets promotionnels', 50000.00, 120000.00, 7, 6, 1),

-- Conception Multimédia
(3, 'Production Vidéo', 'production-video', 'Création de vidéos promotionnelles, institutionnelles et publicitaires', 200000.00, 800000.00, 21, 1, 1),
(3, 'Photographie Professionnelle', 'photographie-professionnelle', 'Shootings photo pour produits, événements et portraits corporate', 100000.00, 300000.00, 3, 2, 1),
(3, 'Motion Design', 'motion-design', 'Animations graphiques et effets visuels pour dynamiser vos communications', 150000.00, 400000.00, 14, 3, 1),
(3, 'Contenu Réseaux Sociaux', 'contenu-reseaux-sociaux', 'Création de contenus visuels optimisés pour vos réseaux sociaux', 75000.00, 200000.00, 7, 4, 1),
(3, 'Présentations Interactives', 'presentations-interactives', 'Présentations PowerPoint et supports visuels percutants', 100000.00, 200000.00, 7, 5, 1),
(3, 'Podcasts & Production Audio', 'podcasts-production-audio', 'Enregistrement, montage et production de contenus audio', 80000.00, 250000.00, 10, 6, 1),

-- Imprimerie
(4, 'Impression Numérique', 'impression-numerique', 'Impression rapide et économique pour petites et moyennes séries', 5000.00, 50000.00, 2, 1, 1),
(4, 'Impression Offset', 'impression-offset', 'Qualité supérieure pour gros tirages et supports premium', 10000.00, 100000.00, 5, 2, 1),
(4, 'Grands Formats', 'grands-formats', 'Impression grand format pour communications extérieures', 25000.00, 200000.00, 3, 3, 1),
(4, 'Supports Marketing', 'supports-marketing', 'Tous vos supports de communication imprimés', 15000.00, 80000.00, 3, 4, 1),
(4, 'Objets Publicitaires', 'objets-publicitaires', 'Personnalisation d\'objets promotionnels pour votre marque', 20000.00, 150000.00, 7, 5, 1),
(4, 'Reliure & Finitions', 'reliure-finitions', 'Finitions professionnelles pour vos documents importants', 8000.00, 40000.00, 2, 6, 1);

-- Insertion des témoignages
INSERT INTO `temoignages` (`nom_client`, `entreprise`, `poste`, `temoignage`, `note`, `service_concerne`, `approuve`, `featured`, `ordre_affichage`, `actif`) VALUES
('Marie Dubois', 'TechCorp Sarl', 'Directrice Marketing', 'Grâce à Divine Art Corporation, notre visibilité en ligne a augmenté de 300% en 6 mois. Leur expertise en SEO est remarquable et leur équipe est très professionnelle.', 5, 'Marketing Digital', 1, 1, 1, 1),
('Jean Kamga', 'Fashion Plus', 'CEO', 'L\'équipe de DAC a transformé notre stratégie social media. Nos ventes ont doublé grâce à leur approche créative et leur suivi rigoureux. Je recommande vivement !', 5, 'Marketing Digital', 1, 1, 2, 1),
('Sophie Nkomo', 'Restaurant Le Gourmet', 'Propriétaire', 'Le nouveau logo et l\'identité visuelle créés par Divine Art Corporation ont complètement relooké notre restaurant. Nos clients adorent le nouveau design !', 5, 'Conception Graphique', 1, 1, 3, 1),
('Paul Mbarga', 'Clinique Santé Plus', 'Directeur', 'La vidéo promotionnelle réalisée par DAC a dépassé nos attentes. Professionnalisme, créativité et respect des délais : tout y était !', 5, 'Conception Multimédia', 1, 0, 4, 1),
('Fatima Ousmane', 'Boutique Élégance', 'Gérante', 'Excellent travail d\'impression pour nos cartes de visite et flyers. La qualité est au rendez-vous et les prix sont très compétitifs.', 4, 'Imprimerie', 1, 0, 5, 1);

-- Insertion des paramètres
INSERT INTO `parametres` (`cle`, `valeur`, `type`, `description`, `groupe`, `ordre_affichage`) VALUES
('site_name', 'Divine Art Corporation', 'text', 'Nom du site web', 'general', 1),
('site_description', 'Votre partenaire créatif au Cameroun pour tous vos besoins en marketing, design et impression.', 'text', 'Description du site', 'general', 2),
('contact_email', 'contact@divineartcorp.cm', 'text', 'Email de contact principal', 'contact', 1),
('contact_phone', '+237 6XX XXX XXX', 'text', 'Téléphone de contact', 'contact', 2),
('contact_address', 'Douala, Akwa Nord, Cameroun', 'text', 'Adresse physique', 'contact', 3),
('social_facebook', 'https://facebook.com/divineartcorp', 'text', 'Page Facebook', 'social', 1),
('social_instagram', 'https://instagram.com/divineartcorp', 'text', 'Compte Instagram', 'social', 2),
('social_linkedin', 'https://linkedin.com/company/divineartcorp', 'text', 'Page LinkedIn', 'social', 3),
('social_twitter', 'https://twitter.com/divineartcorp', 'text', 'Compte Twitter', 'social', 4),
('email_smtp_host', 'smtp.gmail.com', 'text', 'Serveur SMTP', 'email', 1),
('email_smtp_port', '587', 'number', 'Port SMTP', 'email', 2),
('email_smtp_username', 'contact@divineartcorp.cm', 'text', 'Nom d\'utilisateur SMTP', 'email', 3),
('email_smtp_password', '', 'text', 'Mot de passe SMTP', 'email', 4),
('analytics_google', '', 'text', 'Code Google Analytics', 'analytics', 1),
('seo_meta_title', 'Divine Art Corporation - Votre partenaire créatif au Cameroun', 'text', 'Titre SEO par défaut', 'seo', 1),
('seo_meta_description', 'Services de marketing digital, conception graphique, multimédia et imprimerie au Cameroun. Expertise professionnelle pour développer votre entreprise.', 'text', 'Description SEO par défaut', 'seo', 2);

-- Insertion d'exemples de portfolio
INSERT INTO `portfolio` (`titre`, `description`, `client`, `service_id`, `image_principale`, `date_realisation`, `duree_projet`, `featured`, `ordre_affichage`, `actif`) VALUES
('Identité Visuelle Restaurant Le Gourmet', 'Création complète de l\'identité visuelle pour ce restaurant haut de gamme de Douala, incluant logo, charte graphique et supports de communication.', 'Restaurant Le Gourmet', 2, 'portfolio/restaurant-gourmet-logo.jpg', '2024-01-15', 14, 1, 1, 1),
('Campagne Digital TechCorp', 'Stratégie marketing digital complète avec SEO, campagnes Google Ads et gestion des réseaux sociaux pour cette entreprise technologique.', 'TechCorp Sarl', 1, 'portfolio/techcorp-campaign.jpg', '2024-02-20', 45, 1, 2, 1),
('Vidéo Promotionnelle Banque Atlantique', 'Production d\'une vidéo institutionnelle de 3 minutes présentant les services de la banque avec motion design et voix-off professionnelle.', 'Banque Atlantique', 3, 'portfolio/banque-atlantique-video.jpg', '2024-03-10', 21, 1, 3, 1),
('Catalogue Produits Fashion Plus', 'Impression offset haute qualité d\'un catalogue de 48 pages pour cette marque de mode camerounaise.', 'Fashion Plus', 4, 'portfolio/fashion-plus-catalogue.jpg', '2024-01-30', 7, 0, 4, 1);

-- Insertion d'exemples de contacts
INSERT INTO `contacts` (`nom`, `email`, `telephone`, `entreprise`, `sujet`, `message`, `newsletter`, `statut`) VALUES
('Alain Nguema', 'alain.nguema@email.cm', '+237 677123456', 'Startup Innov', 'marketing', 'Bonjour, je souhaiterais avoir des informations sur vos services de marketing digital pour ma startup.', 1, 'nouveau'),
('Clarisse Mballa', 'clarisse@boutique-style.cm', '+237 698765432', 'Boutique Style', 'graphique', 'Nous avons besoin d\'une nouvelle identité visuelle pour notre boutique. Pouvez-vous nous aider ?', 0, 'lu'),
('Robert Essomba', 'r.essomba@gmail.com', '+237 655987654', '', 'autre', 'Je voudrais connaître vos tarifs pour l\'impression de cartes de visite.', 1, 'traite');

-- Insertion d'exemples de devis
INSERT INTO `devis` (`numero_devis`, `nom`, `email`, `telephone`, `entreprise`, `service`, `sous_service`, `description`, `budget`, `delai`, `newsletter`, `statut`, `montant_estime`) VALUES
('DAC-2024-0001', 'Marie Fotso', 'marie.fotso@entreprise.cm', '+237 677888999', 'Entreprise Fotso & Fils', 'marketing', 'seo-sem', 'Nous souhaitons améliorer notre référencement naturel et lancer des campagnes Google Ads pour notre entreprise de BTP.', '300k-500k', '1-2-mois', 1, 'en_cours', 350000.00),
('DAC-2024-0002', 'Pierre Ndjock', 'pierre@restaurant-saveurs.cm', '+237 698111222', 'Restaurant Les Saveurs', 'graphique', 'identite-visuelle', 'Création d\'un logo et d\'une charte graphique complète pour notre nouveau restaurant.', '100k-300k', '3-4-semaines', 0, 'nouveau', 250000.00),
('DAC-2024-0003', 'Sylvie Manga', 'sylvie.manga@ong-espoir.org', '+237 655333444', 'ONG Espoir', 'multimedia', 'production-video', 'Réalisation d\'une vidéo de sensibilisation de 5 minutes sur l\'éducation des enfants.', '300k-500k', '1-2-mois', 1, 'termine', 400000.00);

-- Insertion dans la newsletter
INSERT INTO `newsletter` (`email`, `nom`, `statut`, `source`) VALUES
('marie.fotso@entreprise.cm', 'Marie Fotso', 'actif', 'devis'),
('alain.nguema@email.cm', 'Alain Nguema', 'actif', 'contact'),
('sylvie.manga@ong-espoir.org', 'Sylvie Manga', 'actif', 'devis'),
('info@techcorp.cm', 'TechCorp', 'actif', 'inscription');

COMMIT;

-- --------------------------------------------------------
-- Index et optimisations supplémentaires
-- --------------------------------------------------------

-- Index pour améliorer les performances
CREATE INDEX idx_devis_service_statut ON devis(service, statut);
CREATE INDEX idx_contacts_date_statut ON contacts(date_creation, statut);
CREATE INDEX idx_portfolio_service_featured ON portfolio(service_id, featured);
CREATE INDEX idx_sous_services_service_actif ON sous_services(service_id, actif);

-- Vue pour les statistiques rapides
CREATE VIEW vue_statistiques AS
SELECT 
    (SELECT COUNT(*) FROM devis) as total_devis,
    (SELECT COUNT(*) FROM devis WHERE statut = 'nouveau') as devis_nouveaux,
    (SELECT COUNT(*) FROM devis WHERE statut = 'en_cours') as devis_en_cours,
    (SELECT COUNT(*) FROM devis WHERE statut = 'termine') as devis_termines,
    (SELECT COUNT(*) FROM contacts) as total_contacts,
    (SELECT COUNT(*) FROM contacts WHERE statut = 'nouveau') as contacts_nouveaux,
    (SELECT COUNT(*) FROM newsletter WHERE statut = 'actif') as abonnes_newsletter,
    (SELECT COUNT(*) FROM portfolio WHERE actif = 1) as projets_portfolio;

-- Procédure stockée pour nettoyer les anciens logs
DELIMITER //
CREATE PROCEDURE CleanOldLogs()
BEGIN
    DELETE FROM logs_activite WHERE date_creation < DATE_SUB(NOW(), INTERVAL 6 MONTH);
END //
DELIMITER ;

-- Trigger pour mettre à jour automatiquement la date de modification
DELIMITER //
CREATE TRIGGER update_devis_modification 
    BEFORE UPDATE ON devis 
    FOR EACH ROW 
BEGIN
    SET NEW.date_modification = CURRENT_TIMESTAMP;
END //
DELIMITER ;

-- Fonction pour générer un numéro de devis unique
DELIMITER //
CREATE FUNCTION GenerateDevisNumber() 
RETURNS VARCHAR(20)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE next_number INT;
    DECLARE year_part VARCHAR(4);
    DECLARE number_part VARCHAR(4);
    
    SET year_part = YEAR(CURDATE());
    
    SELECT COALESCE(MAX(CAST(SUBSTRING(numero_devis, -4) AS UNSIGNED)), 0) + 1 
    INTO next_number 
    FROM devis 
    WHERE numero_devis LIKE CONCAT('DAC-', year_part, '-%');
    
    SET number_part = LPAD(next_number, 4, '0');
    
    RETURN CONCAT('DAC-', year_part, '-', number_part);
END //
DELIMITER ;

-- --------------------------------------------------------
-- Données de test supplémentaires pour le développement
-- --------------------------------------------------------

-- Plus de témoignages pour les tests
INSERT INTO `temoignages` (`nom_client`, `entreprise`, `poste`, `temoignage`, `note`, `service_concerne`, `approuve`, `featured`, `actif`) VALUES
('Dr. Amina Hassan', 'Clinique Moderne', 'Directrice Médicale', 'La brochure médicale conçue par DAC est parfaite. Design professionnel et informations claires pour nos patients.', 5, 'Conception Graphique', 1, 0, 1),
('Michel Owona', 'Garage Auto Plus', 'Propriétaire', 'Excellent travail sur notre signalétique extérieure. Bâches de qualité et installation impeccable.', 4, 'Imprimerie', 1, 0, 1),
('Sandrine Biya', 'École Internationale', 'Directrice Communication', 'La vidéo de présentation de notre école a été un succès total. Parents et élèves ont adoré !', 5, 'Conception Multimédia', 1, 0, 1);

-- Paramètres additionnels
INSERT INTO `parametres` (`cle`, `valeur`, `type`, `description`, `groupe`) VALUES
('maintenance_mode', '0', 'boolean', 'Mode maintenance du site', 'system'),
('max_file_upload', '10', 'number', 'Taille max des fichiers (MB)', 'system'),
('backup_frequency', 'weekly', 'text', 'Fréquence des sauvegardes', 'system'),
('currency', 'FCFA', 'text', 'Devise par défaut', 'general'),
('timezone', 'Africa/Douala', 'text', 'Fuseau horaire', 'general'),
('working_hours', '{"lundi":"8h-18h","mardi":"8h-18h","mercredi":"8h-18h","jeudi":"8h-18h","vendredi":"8h-18h","samedi":"9h-15h","dimanche":"Fermé"}', 'json', 'Horaires de travail', 'contact');

-- --------------------------------------------------------
-- Fin du script
-- --------------------------------------------------------

-- Affichage des statistiques finales
SELECT 'Base de données Divine Art Corporation créée avec succès!' as Message;
SELECT 
    (SELECT COUNT(*) FROM services) as 'Services créés',
    (SELECT COUNT(*) FROM sous_services) as 'Sous-services créés',
    (SELECT COUNT(*) FROM admin_users) as 'Utilisateurs admin',
    (SELECT COUNT(*) FROM temoignages) as 'Témoignages',
    (SELECT COUNT(*) FROM parametres) as 'Paramètres configurés';