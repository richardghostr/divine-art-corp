<?php
/**
 * Fichier de fonctions utilitaires pour Divine Art Corporation
 * Contient toutes les fonctions communes utilisées dans l'application
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

/**
 * ================================
 * FONCTIONS DE SÉCURITÉ
 * ================================
 */

/**
 * Nettoie et sécurise une chaîne de caractères
 */
function sanitize_string($string) {
    return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
}

/**
 * Nettoie un email
 */
function sanitize_email($email) {
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

/**
 * Valide un email
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Valide un numéro de téléphone camerounais
 */
function validate_phone($phone) {
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    return preg_match('/^(\+237|237)?[6-9][0-9]{8}$/', $phone);
}

/**
 * Génère un token sécurisé
 */
function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Génère un hash sécurisé pour les mots de passe
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Vérifie un mot de passe
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Génère un CSRF token
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generate_token();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie un CSRF token
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * ================================
 * FONCTIONS DE FORMATAGE
 * ================================
 */

/**
 * Formate une date en français
 */
function format_date($date, $format = 'd/m/Y') {
    if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return '-';
    }
    
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date($format, $timestamp);
}

/**
 * Formate une date relative (il y a X temps)
 */
function time_ago($date) {
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'À l\'instant';
    if ($diff < 3600) return floor($diff / 60) . ' min';
    if ($diff < 86400) return floor($diff / 3600) . ' h';
    if ($diff < 2592000) return floor($diff / 86400) . ' j';
    if ($diff < 31536000) return floor($diff / 2592000) . ' mois';
    
    return floor($diff / 31536000) . ' an' . (floor($diff / 31536000) > 1 ? 's' : '');
}

/**
 * Formate un prix en FCFA
 */
function format_price($price) {
    return number_format($price, 0, ',', ' ') . ' FCFA';
}

/**
 * Formate un numéro de téléphone
 */
function format_phone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) === 9 && substr($phone, 0, 1) === '6') {
        return '+237 ' . substr($phone, 0, 1) . ' ' . substr($phone, 1, 2) . ' ' . 
               substr($phone, 3, 2) . ' ' . substr($phone, 5, 2) . ' ' . substr($phone, 7, 2);
    }
    return $phone;
}

/**
 * Tronque un texte
 */
function truncate_text($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

/**
 * Génère un slug à partir d'un texte
 */
function generate_slug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[àáâãäå]/', 'a', $text);
    $text = preg_replace('/[èéêë]/', 'e', $text);
    $text = preg_replace('/[ìíîï]/', 'i', $text);
    $text = preg_replace('/[òóôõö]/', 'o', $text);
    $text = preg_replace('/[ùúûü]/', 'u', $text);
    $text = preg_replace('/[ç]/', 'c', $text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

/**
 * ================================
 * FONCTIONS DE VALIDATION
 * ================================
 */

/**
 * Valide les données d'un devis
 */
function validate_devis_data($data) {
    $errors = [];
    
    if (empty($data['nom'])) {
        $errors[] = 'Le nom est requis';
    }
    
    if (empty($data['email']) || !validate_email($data['email'])) {
        $errors[] = 'Email valide requis';
    }
    
    if (empty($data['telephone']) || !validate_phone($data['telephone'])) {
        $errors[] = 'Numéro de téléphone valide requis';
    }
    
    if (empty($data['service'])) {
        $errors[] = 'Le service est requis';
    }
    
    if (empty($data['description'])) {
        $errors[] = 'La description est requise';
    }
    
    return $errors;
}

/**
 * Valide les données de contact
 */
function validate_contact_data($data) {
    $errors = [];
    
    if (empty($data['nom'])) {
        $errors[] = 'Le nom est requis';
    }
    
    if (empty($data['email']) || !validate_email($data['email'])) {
        $errors[] = 'Email valide requis';
    }
    
    if (empty($data['sujet'])) {
        $errors[] = 'Le sujet est requis';
    }
    
    if (empty($data['message'])) {
        $errors[] = 'Le message est requis';
    }
    
    return $errors;
}

/**
 * ================================
 * FONCTIONS D'UPLOAD
 * ================================
 */

/**
 * Upload un fichier de manière sécurisée
 */
function upload_file($file, $upload_dir = 'uploads/', $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'pdf']) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'Erreur de paramètres'];
    }
    
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return ['success' => false, 'message' => 'Aucun fichier envoyé'];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['success' => false, 'message' => 'Fichier trop volumineux'];
        default:
            return ['success' => false, 'message' => 'Erreur inconnue'];
    }
    
    // Vérifier la taille (5MB max)
    if ($file['size'] > 5000000) {
        return ['success' => false, 'message' => 'Fichier trop volumineux (5MB max)'];
    }
    
    // Vérifier l'extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed_types)) {
        return ['success' => false, 'message' => 'Type de fichier non autorisé'];
    }
    
    // Générer un nom unique
    $filename = uniqid() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    // Créer le dossier si nécessaire
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Déplacer le fichier
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'filepath' => $filepath];
    } else {
        return ['success' => false, 'message' => 'Erreur lors de l\'upload'];
    }
}

/**
 * ================================
 * FONCTIONS EMAIL
 * ================================
 */

/**
 * Envoie un email simple
 */
function send_email($to, $subject, $message, $from = null) {
    if (!$from) {
        $from = 'noreply@divineartcorp.cm';
    }
    
    $headers = [
        'From: ' . $from,
        'Reply-To: ' . $from,
        'X-Mailer: PHP/' . phpversion(),
        'Content-Type: text/html; charset=UTF-8'
    ];
    
    return mail($to, $subject, $message, implode("\r\n", $headers));
}

/**
 * Envoie un email de notification de devis
 */
function send_devis_notification($devis_data) {
    $subject = 'Nouvelle demande de devis - Divine Art Corporation';
    
    $message = '
    <html>
    <head>
        <title>Nouvelle demande de devis</title>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #e74c3c; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .field { margin-bottom: 15px; }
            .label { font-weight: bold; color: #333; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>Nouvelle demande de devis</h2>
            </div>
            <div class="content">
                <div class="field">
                    <span class="label">Nom:</span> ' . sanitize_string($devis_data['nom']) . '
                </div>
                <div class="field">
                    <span class="label">Email:</span> ' . sanitize_string($devis_data['email']) . '
                </div>
                <div class="field">
                    <span class="label">Téléphone:</span> ' . sanitize_string($devis_data['telephone']) . '
                </div>
                <div class="field">
                    <span class="label">Service:</span> ' . sanitize_string($devis_data['service']) . '
                </div>
                <div class="field">
                    <span class="label">Description:</span><br>' . nl2br(sanitize_string($devis_data['description'])) . '
                </div>
                <div class="field">
                    <span class="label">Date:</span> ' . date('d/m/Y H:i') . '
                </div>
            </div>
        </div>
    </body>
    </html>';
    
    return send_email('admin@divineartcorp.cm', $subject, $message);
}

/**
 * ================================
 * FONCTIONS DE CACHE
 * ================================
 */

/**
 * Met en cache des données
 */
function cache_set($key, $data, $expiration = 3600) {
    $cache_dir = 'cache/';
    if (!is_dir($cache_dir)) {
        mkdir($cache_dir, 0755, true);
    }
    
    $cache_file = $cache_dir . md5($key) . '.cache';
    $cache_data = [
        'expiration' => time() + $expiration,
        'data' => $data
    ];
    
    return file_put_contents($cache_file, serialize($cache_data));
}

/**
 * Récupère des données du cache
 */
function cache_get($key) {
    $cache_dir = 'cache/';
    $cache_file = $cache_dir . md5($key) . '.cache';
    
    if (!file_exists($cache_file)) {
        return false;
    }
    
    $cache_data = unserialize(file_get_contents($cache_file));
    
    if ($cache_data['expiration'] < time()) {
        unlink($cache_file);
        return false;
    }
    
    return $cache_data['data'];
}

/**
 * Supprime une entrée du cache
 */
function cache_delete($key) {
    $cache_dir = 'cache/';
    $cache_file = $cache_dir . md5($key) . '.cache';
    
    if (file_exists($cache_file)) {
        return unlink($cache_file);
    }
    
    return true;
}

/**
 * Vide tout le cache
 */
function cache_clear() {
    $cache_dir = 'cache/';
    if (!is_dir($cache_dir)) {
        return true;
    }
    
    $files = glob($cache_dir . '*.cache');
    foreach ($files as $file) {
        unlink($file);
    }
    
    return true;
}

/**
 * ================================
 * FONCTIONS DE LOGGING
 * ================================
 */

/**
 * Écrit dans le log
 */
function write_log($message, $level = 'INFO') {
    $log_dir = 'logs/';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_file = $log_dir . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] [$level] $message" . PHP_EOL;
    
    return file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * Log d'activité admin
 */
function log_activity($user_id, $action, $table = null, $record_id = null, $details = null) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $conn->prepare("INSERT INTO logs_activite (user_id, action, table_concernee, id_enregistrement, details, ip_address, user_agent, date_creation) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("ississs", $user_id, $action, $table, $record_id, $details, $ip, $user_agent);
    
    return $stmt->execute();
}

/**
 * ================================
 * FONCTIONS UTILITAIRES
 * ================================
 */

/**
 * Redirige vers une URL
 */
function redirect($url, $permanent = false) {
    if ($permanent) {
        header('HTTP/1.1 301 Moved Permanently');
    }
    header('Location: ' . $url);
    exit();
}

/**
 * Retourne l'URL de base du site
 */
function get_base_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'];
    $path = dirname($script);
    
    return $protocol . '://' . $host . $path;
}

/**
 * Vérifie si l'utilisateur est connecté
 */
function is_logged_in() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Vérifie si l'utilisateur est admin
 */
function is_admin() {
    return is_logged_in() && isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'admin';
}

/**
 * Génère une pagination
 */
function generate_pagination($current_page, $total_pages, $base_url) {
    if ($total_pages <= 1) {
        return '';
    }
    
    $pagination = '<nav aria-label="Pagination"><ul class="pagination">';
    
    // Bouton précédent
    if ($current_page > 1) {
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $base_url . '&page=' . ($current_page - 1) . '">Précédent</a></li>';
    }
    
    // Numéros de pages
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $current_page ? ' active' : '';
        $pagination .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $base_url . '&page=' . $i . '">' . $i . '</a></li>';
    }
    
    // Bouton suivant
    if ($current_page < $total_pages) {
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $base_url . '&page=' . ($current_page + 1) . '">Suivant</a></li>';
    }
    
    $pagination .= '</ul></nav>';
    
    return $pagination;
}

/**
 * Génère un breadcrumb
 */
function generate_breadcrumb($items) {
    $breadcrumb = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';
    
    $count = count($items);
    foreach ($items as $index => $item) {
        $active = $index === $count - 1 ? ' active' : '';
        
        if ($active) {
            $breadcrumb .= '<li class="breadcrumb-item' . $active . '">' . $item['title'] . '</li>';
        } else {
            $breadcrumb .= '<li class="breadcrumb-item"><a href="' . $item['url'] . '">' . $item['title'] . '</a></li>';
        }
    }
    
    $breadcrumb .= '</ol></nav>';
    
    return $breadcrumb;
}

/**
 * Convertit une taille en octets en format lisible
 */
function format_bytes($size, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}

/**
 * Génère un mot de passe aléatoire
 */
function generate_password($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    return substr(str_shuffle($chars), 0, $length);
}

/**
 * Vérifie la force d'un mot de passe
 */
function check_password_strength($password) {
    $score = 0;
    $feedback = [];
    
    if (strlen($password) >= 8) {
        $score += 1;
    } else {
        $feedback[] = 'Au moins 8 caractères';
    }
    
    if (preg_match('/[a-z]/', $password)) {
        $score += 1;
    } else {
        $feedback[] = 'Au moins une minuscule';
    }
    
    if (preg_match('/[A-Z]/', $password)) {
        $score += 1;
    } else {
        $feedback[] = 'Au moins une majuscule';
    }
    
    if (preg_match('/[0-9]/', $password)) {
        $score += 1;
    } else {
        $feedback[] = 'Au moins un chiffre';
    }
    
    if (preg_match('/[^a-zA-Z0-9]/', $password)) {
        $score += 1;
    } else {
        $feedback[] = 'Au moins un caractère spécial';
    }
    
    $strength = ['Très faible', 'Faible', 'Moyen', 'Fort', 'Très fort'];
    
    return [
        'score' => $score,
        'strength' => $strength[$score],
        'feedback' => $feedback
    ];
}

/**
 * ================================
 * FONCTIONS DE CONFIGURATION
 * ================================
 */

/**
 * Récupère une configuration
 */
function get_config($key, $default = null) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT valeur FROM configurations WHERE cle = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['valeur'];
    }
    
    return $default;
}

/**
 * Définit une configuration
 */
function set_config($key, $value) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("INSERT INTO configurations (cle, valeur) VALUES (?, ?) ON DUPLICATE KEY UPDATE valeur = ?");
    $stmt->bind_param("sss", $key, $value, $value);
    
    return $stmt->execute();
}

/**
 * ================================
 * FONCTIONS DE STATISTIQUES
 * ================================
 */

/**
 * Calcule les statistiques des devis
 */
function get_devis_stats() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stats = [];
    
    // Total des devis
    $result = $conn->query("SELECT COUNT(*) as total FROM devis");
    $stats['total'] = $result->fetch_assoc()['total'];
    
    // Devis en attente
    $result = $conn->query("SELECT COUNT(*) as pending FROM devis WHERE statut = 'nouveau'");
    $stats['pending'] = $result->fetch_assoc()['pending'];
    
    // Devis acceptés
    $result = $conn->query("SELECT COUNT(*) as accepted FROM devis WHERE statut = 'en_cours'");
    $stats['accepted'] = $result->fetch_assoc()['accepted'];
    
    // Devis ce mois
    $result = $conn->query("SELECT COUNT(*) as this_month FROM devis WHERE MONTH(date_creation) = MONTH(CURRENT_DATE()) AND YEAR(date_creation) = YEAR(CURRENT_DATE())");
    $stats['this_month'] = $result->fetch_assoc()['this_month'];
    
    return $stats;
}

/**
 * Calcule les statistiques des contacts
 */
function get_contact_stats() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stats = [];
    
    // Total des messages
    $result = $conn->query("SELECT COUNT(*) as total FROM contacts");
    $stats['total'] = $result->fetch_assoc()['total'];
    
    // Messages non lus
    $result = $conn->query("SELECT COUNT(*) as unread FROM contacts WHERE statut = 'nouveau'");
    $stats['unread'] = $result->fetch_assoc()['unread'];
    
    // Messages ce mois
    $result = $conn->query("SELECT COUNT(*) as this_month FROM contacts WHERE MONTH(date_creation) = MONTH(CURRENT_DATE()) AND YEAR(date_creation) = YEAR(CURRENT_DATE())");
    $stats['this_month'] = $result->fetch_assoc()['this_month'];
    
    return $stats;
}

/**
 * ================================
 * FONCTIONS DE GÉNÉRATION
 * ================================
 */

/**
 * Génère un numéro de devis unique
 */
function generate_devis_number() {
    $prefix = 'DEV';
    $year = date('Y');
    $month = date('m');
    
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Compter les devis du mois
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM devis WHERE YEAR(date_creation) = ? AND MONTH(date_creation) = ?");
    $stmt->bind_param("ii", $year, $month);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'] + 1;
    
    return $prefix . $year . $month . str_pad($count, 4, '0', STR_PAD_LEFT);
}

/**
 * Génère un numéro de contact unique
 */
function generate_contact_number() {
    $prefix = 'CNT';
    $year = date('Y');
    $month = date('m');
    
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Compter les contacts du mois
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM contacts WHERE YEAR(date_creation) = ? AND MONTH(date_creation) = ?");
    $stmt->bind_param("ii", $year, $month);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'] + 1;
    
    return $prefix . $year . $month . str_pad($count, 4, '0', STR_PAD_LEFT);
}

// Initialisation des constantes si pas déjà définies
if (!defined('SITE_NAME')) {
    define('SITE_NAME', get_config('site_name', 'Divine Art Corporation'));
}

if (!defined('SITE_EMAIL')) {
    define('SITE_EMAIL', get_config('site_email', 'contact@divineartcorp.cm'));
}

if (!defined('SITE_PHONE')) {
    define('SITE_PHONE', get_config('site_phone', '+237 6XX XXX XXX'));
}

if (!defined('SITE_ADDRESS')) {
    define('SITE_ADDRESS', get_config('site_address', 'Douala, Cameroun'));
}




// Vérification de l'authentification
if (!function_exists('checkAuth')) {
    function checkAuth() {
        if (!isset($_SESSION)) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            header('Location: /admin/login.php');
            exit();
        }
    }
}

?>

<?php
// Add your custom functions here

function getPageTitle($page) {
    $titles = [
        'welcome'     => 'Bienvenue',
        'home'        => 'Accueil',
        'marketing'   => 'Marketing',
        'graphique'   => 'Graphisme',
        'multimedia'  => 'Multimédia',
        'imprimerie'  => 'Imprimerie',
        'contact'     => 'Contact',
        'devis'       => 'Demande de devis',
        'admin'       => 'Administration'
    ];
    return $titles[$page] ?? 'Page';
}

function getPageDescription($page) {
    $descriptions = [
        'welcome'     => 'Bienvenue sur Divine Art Corporation.',
        'home'        => 'Page d\'accueil de Divine Art Corporation.',
        'marketing'   => 'Nos services de marketing.',
        'graphique'   => 'Nos services graphiques.',
        'multimedia'  => 'Nos services multimédia.',
        'imprimerie'  => 'Nos services d\'imprimerie.',
        'contact'     => 'Contactez-nous.',
        'devis'       => 'Demandez un devis personnalisé.',
        'admin'       => 'Espace d\'administration.'
    ];
    return $descriptions[$page] ?? 'Divine Art Corporation';
}