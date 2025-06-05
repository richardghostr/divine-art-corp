<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Vérification de l'authentification
$auth = new Auth();
$auth->requireAuth();

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID invalide']);
    exit;
}

$devis_id = (int)$_GET['id'];

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Récupérer les détails du devis
    $query = "
        SELECT d.*, 
               COALESCE(a.nom, 'Non assigné') as admin_nom,
               COALESCE(p.id, 0) as projet_id,
               COALESCE(p.nom, '') as projet_nom,
               COALESCE(p.statut, '') as projet_statut
        FROM devis d
        LEFT JOIN admins a ON d.admin_assigne = a.id
        LEFT JOIN projets p ON d.id = p.devis_id
        WHERE d.id = ?
    ";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $devis_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($devis = mysqli_fetch_assoc($result)) {
        // Nettoyer les données pour l'affichage
        foreach ($devis as $key => $value) {
            if ($value === null) {
                $devis[$key] = '';
            }
        }
        
        echo json_encode([
            'success' => true,
            'devis' => $devis
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Devis non trouvé'
        ]);
    }
    
    mysqli_stmt_close($stmt);
    
} catch (Exception $e) {
    error_log("Erreur get_devis_details: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des données'
    ]);
}
?>
