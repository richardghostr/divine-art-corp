<?php
/**
 * Système d'authentification
 * Divine Art Corporation
 */

require_once  '../config/database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = new DatabaseHelper();
    }
    
    /**
     * Authentifie un utilisateur
     */
    public function login($email, $password) {
        $user = $this->db->selectOne(
            "SELECT * FROM admin_users WHERE email = ? AND is_active = 1",
            [$email]
        );
        
        if ($user && password_verify($password, $user['password'])) {
            // Mise à jour de la dernière connexion
            $this->db->execute(
                "UPDATE admin_users SET last_login = NOW() WHERE id = ?",
                [$user['id']]
            );
            
            // Création de la session
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_email'] = $user['email'];
            $_SESSION['admin_role'] = $user['role'];
            $_SESSION['login_time'] = time();
            
            // Log de l'activité
            logActivity($user['id'], 'login');
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Déconnecte un utilisateur
     */
    public function logout() {
        if (isset($_SESSION['admin_id'])) {
            logActivity($_SESSION['admin_id'], 'logout');
        }
        
        session_destroy();
        
        // Suppression des cookies de mémorisation
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }
        
        return true;
    }
    
    /**
     * Vérifie si l'utilisateur est connecté
     */
    public function isLoggedIn() {
        return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }
    
    /**
     * Vérifie les permissions d'accès
     */
    public function hasPermission($required_role = 'admin') {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $user_role = $_SESSION['admin_role'] ?? '';
        
        $roles_hierarchy = [
            'editor' => 1,
            'manager' => 2,
            'admin' => 3
        ];
        
        $user_level = $roles_hierarchy[$user_role] ?? 0;
        $required_level = $roles_hierarchy[$required_role] ?? 3;
        
        return $user_level >= $required_level;
    }
    
    /**
     * Change le mot de passe d'un utilisateur
     */
    public function changePassword($user_id, $current_password, $new_password) {
        $user = $this->db->selectOne(
            "SELECT password FROM admin_users WHERE id = ?",
            [$user_id]
        );
        
        if (!$user || !password_verify($current_password, $user['password'])) {
            return false;
        }
        
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $result = $this->db->execute(
            "UPDATE admin_users SET password = ? WHERE id = ?",
            [$hashed_password, $user_id]
        );
        
        if ($result) {
            logActivity($user_id, 'password_change');
        }
        
        return $result;
    }
    
    /**
     * Génère un token de récupération de mot de passe
     */
    public function generateResetToken($email) {
        $user = $this->db->selectOne(
            "SELECT id FROM admin_users WHERE email = ? AND is_active = 1",
            [$email]
        );
        
        if (!$user) {
            return false;
        }
        
        $token = generateToken();
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Ici vous devriez stocker le token en base et envoyer l'email
        // Pour la démo, on retourne juste true
        
        logActivity($user['id'], 'password_reset_request');
        
        return true;
    }
}

/**
 * Middleware de protection des pages admin
 */
function requireAuth($required_role = 'admin') {
    session_start();
    
    $auth = new Auth();
    
    if (!$auth->isLoggedIn()) {
        $current_page = $_SERVER['REQUEST_URI'];
        header('Location: login.php?redirect=' . urlencode($current_page));
        exit;
    }
    
    if (!$auth->hasPermission($required_role)) {
        header('HTTP/1.1 403 Forbidden');
        die('Accès refusé. Permissions insuffisantes.');
    }
}

/**
 * Fonction pour obtenir les informations de l'utilisateur connecté
 */
function getCurrentUser() {
    if (!isset($_SESSION['admin_id'])) {
        return null;
    }
    
    $db = new DatabaseHelper();
    return $db->selectOne(
        "SELECT id, username, email, role, last_login FROM admin_users WHERE id = ?",
        [$_SESSION['admin_id']]
    );
}
?>
