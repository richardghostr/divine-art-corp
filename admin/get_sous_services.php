<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireAuth();

$db = Database::getInstance();
$conn = $db->getConnection();

$service_id = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;

try {
    $stmt = $conn->prepare("SELECT id, nom FROM sous_services WHERE service_id = ? AND actif = 1 ORDER BY ordre");
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sous_services = [];
    while ($row = $result->fetch_assoc()) {
        $sous_services[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'sous_services' => $sous_services
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}