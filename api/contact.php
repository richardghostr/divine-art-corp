<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    // Validation et nettoyage des données
    $nom = sanitizeInput($_POST['nom'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $telephone = sanitizeInput($_POST['telephone'] ?? '');
    $entreprise = sanitizeInput($_POST['entreprise'] ?? '');
    $sujet = sanitizeInput($_POST['sujet'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');
    $newsletter = isset($_POST['newsletter']) ? 1 : 0;
    $rgpd = isset($_POST['rgpd']) ? 1 : 0;

    // Validation des champs requis
    $errors = [];
    
    if (empty($nom)) {
        $errors[] = 'Le nom est requis';
    }
    
    if (empty($email)) {
        $errors[] = 'L\'email est requis';
    } elseif (!validateEmail($email)) {
        $errors[] = 'L\'email n\'est pas valide';
    }
    
    if (empty($message)) {
        $errors[] = 'Le message est requis';
    }
    
    if (!$rgpd) {
        $errors[] = 'Vous devez accepter l\'utilisation de vos données';
    }
    
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit;
    }
    
    // Insertion en base de données
    $db = new Database();
    $db->query("INSERT INTO contacts (nom, email, telephone, entreprise, sujet, message, newsletter) 
                VALUES (:nom, :email, :telephone, :entreprise, :sujet, :message, :newsletter)");
    
    $db->bind(':nom', $nom);
    $db->bind(':email', $email);
    $db->bind(':telephone', $telephone);
    $db->bind(':entreprise', $entreprise);
    $db->bind(':sujet', $sujet);
    $db->bind(':message', $message);
    $db->bind(':newsletter', $newsletter);
    
    if ($db->execute()) {
        // Envoi d'email de confirmation
        $emailSubject = "Nouvelle demande de contact - Divine Art Corporation";
        $emailMessage = "
        <html>
        <head>
            <title>Nouvelle demande de contact</title>
        </head>
        <body>
            <h2>Nouvelle demande de contact</h2>
            <p><strong>Nom:</strong> $nom</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Téléphone:</strong> $telephone</p>
            <p><strong>Entreprise:</strong> $entreprise</p>
            <p><strong>Sujet:</strong> $sujet</p>
            <p><strong>Message:</strong></p>
            <p>$message</p>
            <p><strong>Newsletter:</strong> " . ($newsletter ? 'Oui' : 'Non') . "</p>
            <hr>
            <p>Message reçu le " . date('d/m/Y à H:i') . "</p>
        </body>
        </html>";
        
        // Envoyer à l'équipe
        sendEmail('contact@divineartcorp.cm', $emailSubject, $emailMessage, $email);
        
        // Email de confirmation au client
        $clientEmailSubject = "Confirmation de réception - Divine Art Corporation";
        $clientEmailMessage = "
        <html>
        <head>
            <title>Confirmation de réception</title>
        </head>
        <body>
            <h2>Bonjour $nom,</h2>
            <p>Nous avons bien reçu votre message et vous remercions de votre intérêt pour Divine Art Corporation.</p>
            <p>Notre équipe va étudier votre demande et vous recontacter dans les plus brefs délais.</p>
            <p>En attendant, n'hésitez pas à consulter notre portfolio sur notre site web.</p>
            <br>
            <p>Cordialement,</p>
            <p>L'équipe Divine Art Corporation</p>
            <hr>
            <p>Divine Art Corporation - Votre partenaire créatif au Cameroun</p>
        </body>
        </html>";
        
        sendEmail($email, $clientEmailSubject, $clientEmailMessage);
        
        // Log de l'activité
        logActivity("Nouveau contact reçu", "De: $nom ($email)");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Votre message a été envoyé avec succès. Nous vous recontacterons rapidement.'
        ]);
        
    } else {
        throw new Exception('Erreur lors de l\'enregistrement');
    }
    
} catch (Exception $e) {
    error_log("Erreur contact: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Une erreur est survenue. Veuillez réessayer plus tard.'
    ]);
}
?>