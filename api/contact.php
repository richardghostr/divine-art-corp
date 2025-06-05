<?php
/**
 * Traitement du formulaire de contact
 * Divine Art Corporation
 */

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure la configuration de la base de données
require_once '../config/database.php';

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Vérifier le token CSRF
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['contact_message'] = "Erreur de sécurité. Veuillez réessayer.";
        $_SESSION['contact_message_type'] = "error";
        $_SESSION['form_data'] = $_POST;
        header('Location: ../index.php?page=contact');
        exit;
    }
    
    // Récupérer et nettoyer les données du formulaire
    $nom = isset($_POST['nom']) ? mysqli_real_escape_string($conn, trim($_POST['nom'])) : '';
    $email = isset($_POST['email']) ? mysqli_real_escape_string($conn, trim($_POST['email'])) : '';
    $telephone = isset($_POST['telephone']) ? mysqli_real_escape_string($conn, trim($_POST['telephone'])) : '';
    $entreprise = isset($_POST['entreprise']) ? mysqli_real_escape_string($conn, trim($_POST['entreprise'])) : '';
    $sujet = isset($_POST['sujet']) ? mysqli_real_escape_string($conn, trim($_POST['sujet'])) : '';
    $message = isset($_POST['message']) ? mysqli_real_escape_string($conn, trim($_POST['message'])) : '';
    $newsletter = isset($_POST['newsletter']) ? 1 : 0;
    $rgpd = isset($_POST['rgpd']) ? 1 : 0;
    
    // Valider les données
    $errors = [];
    
    if (empty($nom)) {
        $errors[] = "Le nom est obligatoire.";
    }
    
    if (empty($email)) {
        $errors[] = "L'email est obligatoire.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email n'est pas valide.";
    }
    
    if (empty($message)) {
        $errors[] = "Le message est obligatoire.";
    }
    
    if (!$rgpd) {
        $errors[] = "Vous devez accepter la politique de confidentialité.";
    }
    
    // S'il y a des erreurs, rediriger vers le formulaire avec les messages d'erreur
    if (!empty($errors)) {
        $_SESSION['contact_message'] = implode("<br>", $errors);
        $_SESSION['contact_message_type'] = "error";
        $_SESSION['form_data'] = $_POST;
        header('Location: ../index.php?page=contact');
        exit;
    }
    
    // Vérifier si la table contacts existe
    $check_table_query = "SHOW TABLES LIKE 'contacts'";
    $table_result = mysqli_query($conn, $check_table_query);
    $table_exists = ($table_result && mysqli_num_rows($table_result) > 0);
    
    if (!$table_exists) {
        // Créer la table contacts si elle n'existe pas
        $create_table_query = "CREATE TABLE IF NOT EXISTS contacts (
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
        
        if (!mysqli_query($conn, $create_table_query)) {
            error_log("Erreur lors de la création de la table contacts: " . mysqli_error($conn));
            $_SESSION['contact_message'] = "Une erreur est survenue. Veuillez réessayer plus tard.";
            $_SESSION['contact_message_type'] = "error";
            $_SESSION['form_data'] = $_POST;
            header('Location: ../index.php?page=contact');
            exit;
        }
    }
    
    // Enregistrer le message dans la base de données
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $query = "INSERT INTO contacts (nom, email, telephone, entreprise, sujet, message, newsletter, rgpd, ip_address, user_agent) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssssssiiis", $nom, $email, $telephone, $entreprise, $sujet, $message, $newsletter, $rgpd, $ip_address, $user_agent);
        
        if (mysqli_stmt_execute($stmt)) {
            // Message enregistré avec succès
            $_SESSION['contact_message'] = "Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.";
            $_SESSION['contact_message_type'] = "success";
            
            // Enregistrer l'activité
            if (function_exists('logActivity')) {
                logActivity(0, 'Nouveau message de contact', 'contacts', mysqli_insert_id($conn), "Message de $nom ($email)");
            }
            
            // Envoyer une notification par email (à implémenter)
            // sendContactNotification($nom, $email, $sujet, $message);
            
            // Rediriger vers la page de contact
            header('Location: ../index.php?page=contact');
            exit;
        } else {
            // Erreur lors de l'enregistrement
            error_log("Erreur lors de l'enregistrement du message: " . mysqli_stmt_error($stmt));
            $_SESSION['contact_message'] = "Une erreur est survenue lors de l'envoi du message. Veuillez réessayer.";
            $_SESSION['contact_message_type'] = "error";
            $_SESSION['form_data'] = $_POST;
            header('Location: ../index.php?page=contact');
            exit;
        }
        
        mysqli_stmt_close($stmt);
    } else {
        // Erreur de préparation de la requête
        error_log("Erreur de préparation de la requête: " . mysqli_error($conn));
        $_SESSION['contact_message'] = "Une erreur est survenue. Veuillez réessayer plus tard.";
        $_SESSION['contact_message_type'] = "error";
        $_SESSION['form_data'] = $_POST;
        header('Location: ../index.php?page=contact');
        exit;
    }
} else {
    // Accès direct au script sans soumission de formulaire
    header('Location: ../index.php');
    exit;
}
?>
