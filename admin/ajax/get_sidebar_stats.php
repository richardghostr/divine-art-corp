<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Vérification de l'authentification
$auth = new Auth();
$auth->requireAuth();

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stats = [
        'nouveaux_devis' => 0,
        'nouveaux_contacts' => 0,
        'projets_en_cours' => 0
    ];
    
    // Nouveaux devis
    $query = "SELECT COUNT(*) as count FROM devis WHERE statut = 'nouveau'";
    $result = mysqli_query($conn, $query);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $stats['nouveaux_devis'] = (int)$row['count'];
        mysqli_free_result($result);
    }
    
    // Nouveaux contacts
    $query = "SELECT COUNT(*) as count FROM contacts WHERE statut = 'nouveau'";
    $result = mysqli_query($conn, $query);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $stats['nouveaux_contacts'] = (int)$row['count'];
        mysqli_free_result($result);
    }
    
    // Projets en cours
    $query = "SELECT COUNT(*) as count FROM projets WHERE statut = 'en_cours'";
    $result = mysqli_query($conn, $query);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $stats['projets_en_cours'] = (int)$row['count'];
        mysqli_free_result($result);
    }
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    error_log("Erreur get_sidebar_stats: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération des statistiques'
    ]);
}
?>
