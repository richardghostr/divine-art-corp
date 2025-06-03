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
    $poste = sanitizeInput($_POST['poste'] ?? '');
    $service = sanitizeInput($_POST['service'] ?? '');
    $sous_service = sanitizeInput($_POST['sous_service'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $budget = sanitizeInput($_POST['budget'] ?? '');
    $delai = sanitizeInput($_POST['delai'] ?? '');
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
    
    if (empty($telephone)) {
        $errors[] = 'Le téléphone est requis';
    }
    
    if (empty($service)) {
        $errors[] = 'Le service est requis';
    }
    
    if (empty($description)) {
        $errors[] = 'La description du projet est requise';
    }
    
    if (!$rgpd) {
        $errors[] = 'Vous devez accepter l\'utilisation de vos données';
    }
    
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit;
    }
    
    // Génération du numéro de devis
    $numero_devis = generateDevisNumber();
    
    // Insertion en base de données
    $db = new Database();
    $db->query("INSERT INTO devis (numero_devis, nom, email, telephone, entreprise, poste, service, sous_service, description, budget, delai, newsletter) 
                VALUES (:numero_devis, :nom, :email, :telephone, :entreprise, :poste, :service, :sous_service, :description, :budget, :delai, :newsletter)");
    
    $db->bind(':numero_devis', $numero_devis);
    $db->bind(':nom', $nom);
    $db->bind(':email', $email);
    $db->bind(':telephone', $telephone);
    $db->bind(':entreprise', $entreprise);
    $db->bind(':poste', $poste);
    $db->bind(':service', $service);
    $db->bind(':sous_service', $sous_service);
    $db->bind(':description', $description);
    $db->bind(':budget', $budget);
    $db->bind(':delai', $delai);
    $db->bind(':newsletter', $newsletter);
    
    if ($db->execute()) {
        $devis_id = $db->lastInsertId();
        
        // Envoi d'email de notification à l'équipe
        $emailSubject = "Nouvelle demande de devis #$numero_devis - Divine Art Corporation";
        $emailMessage = "
        <html>
        <head>
            <title>Nouvelle demande de devis</title>
            <style>
                body { font-family: Arial, sans-serif; }
                .header { background: #e74c3c; color: white; padding: 20px; }
                .content { padding: 20px; }
                .info-table { width: 100%; border-collapse: collapse; }
                .info-table td { padding: 10px; border-bottom: 1px solid #eee; }
                .label { font-weight: bold; width: 150px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>Nouvelle demande de devis #$numero_devis</h2>
            </div>
            <div class='content'>
                <table class='info-table'>
                    <tr><td class='label'>Nom:</td><td>$nom</td></tr>
                    <tr><td class='label'>Email:</td><td>$email</td></tr>
                    <tr><td class='label'>Téléphone:</td><td>$telephone</td></tr>
                    <tr><td class='label'>Entreprise:</td><td>$entreprise</td></tr>
                    <tr><td class='label'>Poste:</td><td>$poste</td></tr>
                    <tr><td class='label'>Service:</td><td>$service</td></tr>
                    <tr><td class='label'>Sous-service:</td><td>$sous_service</td></tr>
                    <tr><td class='label'>Budget:</td><td>$budget</td></tr>
                    <tr><td class='label'>Délai:</td><td>$delai</td></tr>
                </table>
                <h3>Description du projet:</h3>
                <p>$description</p>
                <hr>
                <p>Demande reçue le " . date('d/m/Y à H:i') . "</p>
                <p><a href='http://localhost/divine-art-corp/admin/devis.php?id=$devis_id'>Voir dans l'administration</a></p>
            </div>
        </body>
        </html>";
        
        sendEmail('contact@divineartcorp.cm', $emailSubject, $emailMessage, $email);
        
        // Email de confirmation au client
        $clientEmailSubject = "Confirmation de votre demande de devis #$numero_devis";
        $clientEmailMessage = "
        <html>
        <head>
            <title>Confirmation de demande de devis</title>
            <style>
                body { font-family: Arial, sans-serif; }
                .header { background: #e74c3c; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .highlight { background: #f8f9fa; padding
                .content { padding: 20px; }
                .highlight { background: #f8f9fa; padding: 15px; border-left: 4px solid #e74c3c; margin: 20px 0; }
                .next-steps { background: #e8f5e8; padding: 15px; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>Merci pour votre demande de devis !</h2>
                <p>Numéro de référence: #$numero_devis</p>
            </div>
            <div class='content'>
                <p>Bonjour $nom,</p>
                <p>Nous avons bien reçu votre demande de devis pour le service <strong>$service</strong> et vous remercions de votre confiance.</p>
                
                <div class='highlight'>
                    <h3>Récapitulatif de votre demande:</h3>
                    <p><strong>Service:</strong> $service</p>
                    <p><strong>Sous-service:</strong> $sous_service</p>
                    <p><strong>Budget indicatif:</strong> $budget</p>
                    <p><strong>Délai souhaité:</strong> $delai</p>
                </div>
                
                <div class='next-steps'>
                    <h3>Prochaines étapes:</h3>
                    <ul>
                        <li>📋 Analyse de votre demande sous 24h</li>
                        <li>📞 Appel de notre expert pour affiner le projet</li>
                        <li>📄 Envoi du devis détaillé sous 48h</li>
                        <li>🚀 Démarrage du projet après validation</li>
                    </ul>
                </div>
                
                <p>Si vous avez des questions urgentes, n'hésitez pas à nous contacter au +237 6XX XXX XXX.</p>
                
                <p>Cordialement,<br>
                L'équipe Divine Art Corporation</p>
                
                <hr>
                <p style='font-size: 12px; color: #666;'>Divine Art Corporation - Votre partenaire créatif au Cameroun</p>
            </div>
        </body>
        </html>";
        
        sendEmail($email, $clientEmailSubject, $clientEmailMessage);
        
        // Log de l'activité
        logActivity("Nouvelle demande de devis", "Devis #$numero_devis - $nom ($email) - Service: $service");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Votre demande de devis a été envoyée avec succès.',
            'devis_number' => $numero_devis
        ]);
        
    } else {
        throw new Exception('Erreur lors de l\'enregistrement du devis');
    }
    
} catch (Exception $e) {
    error_log("Erreur devis: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Une erreur est survenue lors de l\'envoi de votre demande. Veuillez réessayer plus tard.'
    ]);
}
?>