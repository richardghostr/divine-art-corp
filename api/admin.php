<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'logout':
        handleLogout();
        break;
    case 'update_devis_status':
        handleUpdateDevisStatus();
        break;
    case 'delete_contact':
        handleDeleteContact();
        break;
    case 'get_stats':
        handleGetStats();
        break;
    case 'export_data':
        handleExportData();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
}

function handleLogout() {
    if (isset($_SESSION['admin_user'])) {
        logActivity("Déconnexion admin", "Utilisateur: " . $_SESSION['admin_user']['username']);
    }
    
    session_destroy();
    header('Location: ../index.php?page=admin&action=login');
    exit;
}

function handleUpdateDevisStatus() {
    requireAdmin();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
        return;
    }
    
    $devis_id = (int)($_POST['devis_id'] ?? 0);
    $new_status = sanitizeInput($_POST['status'] ?? '');
    $notes = sanitizeInput($_POST['notes'] ?? '');
    
    if (!$devis_id || !$new_status) {
        echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
        return;
    }
    
    $allowed_statuses = ['nouveau', 'en_cours', 'termine', 'annule'];
    if (!in_array($new_status, $allowed_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Statut invalide']);
        return;
    }
    
    try {
        $db = new Database();
        
        // Récupérer les infos du devis
        $db->query("SELECT * FROM devis WHERE id = :id");
        $db->bind(':id', $devis_id);
        $devis = $db->single();
        
        if (!$devis) {
            echo json_encode(['success' => false, 'message' => 'Devis non trouvé']);
            return;
        }
        
        // Mettre à jour le statut
        $db->query("UPDATE devis SET statut = :status, notes_admin = :notes WHERE id = :id");
        $db->bind(':status', $new_status);
        $db->bind(':notes', $notes);
        $db->bind(':id', $devis_id);
        
        if ($db->execute()) {
            // Envoyer un email de notification au client si nécessaire
            if ($new_status === 'en_cours') {
                sendDevisStatusEmail($devis, $new_status);
            }
            
            logActivity("Mise à jour statut devis", "Devis #{$devis['numero_devis']} -> $new_status");
            
            echo json_encode([
                'success' => true, 
                'message' => 'Statut mis à jour avec succès'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
        }
        
    } catch (Exception $e) {
        error_log("Erreur update devis status: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
    }
}

function handleDeleteContact() {
    requireAdmin();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
        return;
    }
    
    $contact_id = (int)($_POST['contact_id'] ?? 0);
    
    if (!$contact_id) {
        echo json_encode(['success' => false, 'message' => 'ID contact manquant']);
        return;
    }
    
    try {
        $db = new Database();
        
        // Vérifier que le contact existe
        $db->query("SELECT nom, email FROM contacts WHERE id = :id");
        $db->bind(':id', $contact_id);
        $contact = $db->single();
        
        if (!$contact) {
            echo json_encode(['success' => false, 'message' => 'Contact non trouvé']);
            return;
        }
        
        // Supprimer le contact
        $db->query("DELETE FROM contacts WHERE id = :id");
        $db->bind(':id', $contact_id);
        
        if ($db->execute()) {
            logActivity("Suppression contact", "Contact: {$contact['nom']} ({$contact['email']})");
            
            echo json_encode([
                'success' => true, 
                'message' => 'Contact supprimé avec succès'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
        }
        
    } catch (Exception $e) {
        error_log("Erreur delete contact: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
    }
}

function handleGetStats() {
    requireAdmin();
    
    try {
        $db = new Database();
        
        // Statistiques générales
        $db->query("SELECT * FROM vue_statistiques");
        $stats = $db->single();
        
        // Évolution des demandes par mois (6 derniers mois)
        $db->query("
            SELECT 
                DATE_FORMAT(date_creation, '%Y-%m') as mois,
                COUNT(*) as total_devis
            FROM devis 
            WHERE date_creation >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(date_creation, '%Y-%m')
            ORDER BY mois
        ");
        $evolution_devis = $db->resultset();
        
        $db->query("
            SELECT 
                DATE_FORMAT(date_creation, '%Y-%m') as mois,
                COUNT(*) as total_contacts
            FROM contacts 
            WHERE date_creation >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(date_creation, '%Y-%m')
            ORDER BY mois
        ");
        $evolution_contacts = $db->resultset();
        
        // Répartition par service
        $db->query("
            SELECT 
                service,
                COUNT(*) as total
            FROM devis 
            GROUP BY service
            ORDER BY total DESC
        ");
        $repartition_services = $db->resultset();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'stats_generales' => $stats,
                'evolution_devis' => $evolution_devis,
                'evolution_contacts' => $evolution_contacts,
                'repartition_services' => $repartition_services
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Erreur get stats: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des statistiques']);
    }
}

function handleExportData() {
    requireAdmin();
    
    $type = $_GET['type'] ?? '';
    $format = $_GET['format'] ?? 'csv';
    
    if (!in_array($type, ['devis', 'contacts', 'newsletter'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Type d\'export invalide']);
        return;
    }
    
    try {
        $db = new Database();
        
        switch ($type) {
            case 'devis':
                $db->query("SELECT * FROM devis ORDER BY date_creation DESC");
                $filename = 'devis_' . date('Y-m-d');
                break;
            case 'contacts':
                $db->query("SELECT * FROM contacts ORDER BY date_creation DESC");
                $filename = 'contacts_' . date('Y-m-d');
                break;
            case 'newsletter':
                $db->query("SELECT * FROM newsletter WHERE statut = 'actif' ORDER BY date_inscription DESC");
                $filename = 'newsletter_' . date('Y-m-d');
                break;
        }
        
        $data = $db->resultset();
        
        if ($format === 'csv') {
            exportToCSV($data, $filename);
        } else {
            exportToJSON($data, $filename);
        }
        
        logActivity("Export de données", "Type: $type, Format: $format");
        
    } catch (Exception $e) {
        error_log("Erreur export: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'export']);
    }
}

function sendDevisStatusEmail($devis, $status) {
    $status_messages = [
        'en_cours' => 'Votre devis est en cours de traitement',
        'termine' => 'Votre devis est terminé',
        'annule' => 'Votre devis a été annulé'
    ];
    
    $subject = "Mise à jour de votre devis #{$devis['numero_devis']} - Divine Art Corporation";
    $message = "
    <html>
    <head>
        <title>Mise à jour de votre devis</title>
    </head>
    <body>
        <h2>Bonjour {$devis['nom']},</h2>
        <p>{$status_messages[$status]}.</p>
        <p><strong>Numéro de devis :</strong> {$devis['numero_devis']}</p>
        <p><strong>Service :</strong> " . ucfirst($devis['service']) . "</p>
        <p>Notre équipe vous contactera prochainement pour la suite.</p>
        <p>Cordialement,<br>L'équipe Divine Art Corporation</p>
    </body>
    </html>";
    
    sendEmail($devis['email'], $subject, $message);
}

function exportToCSV($data, $filename) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    if (!empty($data)) {
        // En-têtes
        fputcsv($output, array_keys($data[0]));
        
        // Données
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
}

function exportToJSON($data, $filename) {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.json"');
    
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>