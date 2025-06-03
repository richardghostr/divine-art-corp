<?php
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['admin_logged_in'])) {
    $action = $_GET['action'] ?? 'login';
    
    if ($action === 'login') {
        include 'admin/login.php';
    } else {
        header('Location: ?page=admin&action=login');
        exit;
    }
} else {
    // L'utilisateur est connecté, afficher le dashboard
    $section = $_GET['section'] ?? 'dashboard';
    
    switch ($section) {
        case 'dashboard':
            include 'admin/dashboard.php';
            break;
        case 'devis':
            include 'admin/devis.php';
            break;
        case 'contacts':
            include 'admin/contacts.php';
            break;
        case 'settings':
            include 'admin/settings.php';
            break;
        default:
            include 'admin/dashboard.php';
    }
}
?>