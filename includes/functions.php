<?php


function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function sendEmail($to, $subject, $message, $from = 'contact@divineartcorp.cm') {
    $headers = "From: $from\r\n";
    $headers .= "Reply-To: $from\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}

function formatDate($date) {
    return date('d/m/Y à H:i', strtotime($date));
}

function truncateText($text, $length = 150) {
    if (strlen($text) > $length) {
        return substr($text, 0, $length) . '...';
    }
    return $text;
}

function generateDevisNumber() {
    return 'DAC-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function getServiceIcon($service) {
    $icons = [
        'marketing' => 'fas fa-chart-line',
        'graphique' => 'fas fa-palette',
        'multimedia' => 'fas fa-video',
        'imprimerie' => 'fas fa-print'
    ];
    
    return isset($icons[$service]) ? $icons[$service] : 'fas fa-cog';
}

function getServiceColor($service) {
    $colors = [
        'marketing' => '#e74c3c',
        'graphique' => '#3498db',
        'multimedia' => '#9b59b6',
        'imprimerie' => '#27ae60'
    ];
    
    return isset($colors[$service]) ? $colors[$service] : '#34495e';
}

function isAdmin() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ?page=admin&action=login');
        exit();
    }
}

function logActivity($action, $details = '') {
    // Log des activités pour l'administration
    $log = date('Y-m-d H:i:s') . " - $action";
    if ($details) {
        $log .= " - $details";
    }
    $log .= "\n";
    
    file_put_contents('logs/activity.log', $log, FILE_APPEND | LOCK_EX);
}


?><?php

function getPageTitle($page) {
    $titles = [
        'welcome' => 'Bienvenue',
        'home' => 'Accueil',
        'marketing' => 'Marketing Digital',
        'graphique' => 'Conception Graphique', 
        'multimedia' => 'Conception Multimédia',
        'imprimerie' => 'Imprimerie',
        'contact' => 'Contact',
        'devis' => 'Demande de Devis',
        'admin' => 'Administration'
    ];
    
    return $titles[$page] ?? 'Page';
}

function getPageDescription($page) {
    $descriptions = [
        'welcome' => 'Bienvenue chez Divine Art Corporation - Votre partenaire créatif au Cameroun',
        'home' => 'Divine Art Corporation - Services de marketing, design et impression au Cameroun',
        'marketing' => 'Services de marketing digital professionnel au Cameroun - SEO, réseaux sociaux, publicité',
        'graphique' => 'Conception graphique et identité visuelle au Cameroun - Logo, branding, supports print',
        'multimedia' => 'Création multimédia au Cameroun - Vidéo, photo, motion design, contenu digital',
        'imprimerie' => 'Services d\'imprimerie haute qualité au Cameroun - Offset, numérique, grand format',
        'contact' => 'Contactez Divine Art Corporation pour vos projets créatifs au Cameroun',
        'devis' => 'Demandez un devis gratuit pour vos projets de marketing et design',
        'admin' => 'Interface d\'administration Divine Art Corporation'
    ];
    
    return $descriptions[$page] ?? 'Divine Art Corporation - Votre partenaire créatif au Cameroun';
}