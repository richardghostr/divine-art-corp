<?php
/**
 * Script pour créer les tables nécessaires au fonctionnement du site
 * Divine Art Corporation
 */

// Inclure la configuration de la base de données
require_once '../config/database.php';

// Fonction pour exécuter une requête SQL
function executeQuery($conn, $query, $message) {
    echo "<p>$message... ";
    if (mysqli_query($conn, $query)) {
        echo "<span style='color: green;'>Succès</span></p>";
        return true;
    } else {
        echo "<span style='color: red;'>Erreur: " . mysqli_error($conn) . "</span></p>";
        return false;
    }
}

// Vérifier si la connexion est établie
if (!$conn) {
    die("Connexion échouée: " . mysqli_connect_error());
}

echo "<h1>Création des tables pour Divine Art Corporation</h1>";

// Création de la table parametres
$sql_parametres = "CREATE TABLE IF NOT EXISTS parametres (
    id INT(11) NOT NULL AUTO_INCREMENT,
    type VARCHAR(50) NOT NULL,
    cle VARCHAR(100) NOT NULL,
    valeur TEXT NOT NULL,
    description TEXT NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_type_cle (type, cle)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

executeQuery($conn, $sql_parametres, "Création de la table parametres");

// Création de la table contacts
$sql_contacts = "CREATE TABLE IF NOT EXISTS contacts (
    id INT(11) NOT NULL AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telephone VARCHAR(20) NULL,
    entreprise VARCHAR(100) NULL,
    sujet VARCHAR(50) NULL,
    message TEXT NOT NULL,
    newsletter TINYINT(1) NOT NULL DEFAULT 0,
    rgpd TINYINT(1) NOT NULL DEFAULT 0,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    statut VARCHAR(20) NOT NULL DEFAULT 'nouveau',
    traite_par INT(11) NULL,
    date_traitement DATETIME NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

executeQuery($conn, $sql_contacts, "Création de la table contacts");

// Création de la table faq
$sql_faq = "CREATE TABLE IF NOT EXISTS faq (
    id INT(11) NOT NULL AUTO_INCREMENT,
    question VARCHAR(255) NOT NULL,
    reponse TEXT NOT NULL,
    categorie VARCHAR(50) NULL,
    ordre INT(11) NOT NULL DEFAULT 0,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

executeQuery($conn, $sql_faq, "Création de la table faq");

// Insertion des données par défaut dans la table parametres
$sql_insert_parametres = "INSERT IGNORE INTO parametres (type, cle, valeur, description) VALUES
    ('contact', 'adresse', 'Douala, Akwa Nord<br>Cameroun', 'Adresse de l\'entreprise'),
    ('contact', 'telephone1', '+237 6XX XXX XXX', 'Numéro de téléphone principal'),
    ('contact', 'telephone2', '+237 6XX XXX XXX', 'Numéro de téléphone secondaire'),
    ('contact', 'email1', 'contact@divineartcorp.cm', 'Email principal'),
    ('contact', 'email2', 'info@divineartcorp.cm', 'Email secondaire'),
    ('contact', 'horaires', 'Lun - Ven: 8h00 - 18h00<br>Sam: 9h00 - 15h00', 'Horaires d\'ouverture'),
    ('contact', 'map_url', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3979.808258706028!2d9.735686!3d4.01498!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNMKwMDAnNTMuOSJOIDnCsDQ0JzA4LjUiRQ!5e0!3m2!1sfr!2scm!4v1717500000000!5m2!1sfr!2scm', 'URL de la carte Google Maps'),
    ('social', 'facebook', 'https://facebook.com/divineartcorp', 'Lien Facebook'),
    ('social', 'instagram', 'https://instagram.com/divineartcorp', 'Lien Instagram'),
    ('social', 'linkedin', 'https://linkedin.com/company/divineartcorp', 'Lien LinkedIn'),
    ('social', 'twitter', 'https://twitter.com/divineartcorp', 'Lien Twitter'),
    ('social', 'whatsapp', 'https://wa.me/237XXXXXXXXX', 'Lien WhatsApp');";

executeQuery($conn, $sql_insert_parametres, "Insertion des données par défaut dans la table parametres");

// Insertion des FAQ par défaut
$sql_insert_faq = "INSERT IGNORE INTO faq (question, reponse, categorie, ordre, actif) VALUES
    ('Quels sont vos délais de réalisation ?', 'Les délais varient selon le type de projet. En général, comptez 3-5 jours pour un logo, 1-2 semaines pour une identité complète, et 2-4 semaines pour une stratégie marketing.', 'general', 1, 1),
    ('Proposez-vous des révisions ?', 'Oui, nous incluons 3 révisions gratuites dans tous nos projets. Des révisions supplémentaires peuvent être facturées selon la complexité.', 'general', 2, 1),
    ('Travaillez-vous avec des entreprises de toutes tailles ?', 'Absolument ! Nous accompagnons aussi bien les startups que les grandes entreprises, en adaptant nos services à vos besoins et budget.', 'general', 3, 1),
    ('Quels formats de fichiers livrez-vous ?', 'Nous livrons tous les formats nécessaires : AI, EPS, PDF, PNG, JPG en haute résolution, ainsi que les fichiers sources modifiables.', 'technique', 4, 1);";

executeQuery($conn, $sql_insert_faq, "Insertion des FAQ par défaut");

echo "<h2>Installation terminée</h2>";
echo "<p>Les tables nécessaires ont été créées et les données par défaut ont été insérées.</p>";
echo "<p><a href='../index.php'>Retour au site</a></p>";

// Fermer la connexion
mysqli_close($conn);
?>
