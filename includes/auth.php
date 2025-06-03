<?php
/**
 * Système d'authentification avec MySQLi
 * Divine Art Corporation
 */

require_once __DIR__ . '/../config/database.php';

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
        
        // Détruire toutes les variables de session
        $_SESSION = array();
        
        // Détruire le cookie de session si il existe
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Détruire la session
        session_destroy();
        
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
    
    /**
     * Crée un nouvel utilisateur admin
     */
    public function createUser($username, $email, $password, $role = 'editor') {
        // Vérifier si l'utilisateur existe déjà
        $existing = $this->db->selectOne(
            "SELECT id FROM admin_users WHERE username = ? OR email = ?",
            [$username, $email]
        );
        
        if ($existing) {
            return false;
        }
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $result = $this->db->execute(
            "INSERT INTO admin_users (username, email, password, role) VALUES (?, ?, ?, ?)",
            [$username, $email, $hashed_password, $role]
        );
        
        if ($result) {
            $user_id = $this->db->lastInsertId();
            logActivity($_SESSION['admin_id'] ?? null, 'user_create', 'admin_users', $user_id);
            return $user_id;
        }
        
        return false;
    }
    
    /**
     * Met à jour les informations d'un utilisateur
     */
    public function updateUser($user_id, $data) {
        $allowed_fields = ['username', 'email', 'role', 'is_active'];
        $set_clauses = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowed_fields)) {
                $set_clauses[] = "$field = ?";
                $params[] = $value;
            }
        }
        
        if (empty($set_clauses)) {
            return false;
        }
        
        $params[] = $user_id;
        $query = "UPDATE admin_users SET " . implode(', ', $set_clauses) . " WHERE id = ?";
        
        $result = $this->db->execute($query, $params);
        
        if ($result) {
            logActivity($_SESSION['admin_id'] ?? null, 'user_update', 'admin_users', $user_id);
        }
        
        return $result;
    }
    
    /**
     * Supprime un utilisateur
     */
    public function deleteUser($user_id) {
        // Ne pas permettre la suppression de son propre compte
        if ($user_id == ($_SESSION['admin_id'] ?? 0)) {
            return false;
        }
        
        $result = $this->db->execute(
            "DELETE FROM admin_users WHERE id = ?",
            [$user_id]
        );
        
        if ($result) {
            logActivity($_SESSION['admin_id'] ?? null, 'user_delete', 'admin_users', $user_id);
        }
        
        return $result;
    }
}

/**
 * Middleware de protection des pages admin
 */
function requireAuth($required_role = 'admin') {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
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

/**
 * Fonction pour obtenir tous les utilisateurs
 */
function getAllUsers() {
    $db = new DatabaseHelper();
    return $db->select(
        "SELECT id, username, email, role, is_active, last_login, date_creation FROM admin_users ORDER BY username"
    );
}

/**
 * Fonction pour vérifier la force d'un mot de passe
 */
function checkPasswordStrength($password) {
    $score = 0;
    $feedback = [];
    
    // Longueur minimum
    if (strlen($password) >= 8) {
        $score += 1;
    } else {
        $feedback[] = "Au moins 8 caractères";
    }
    
    // Majuscules
    if (preg_match('/[A-Z]/', $password)) {
        $score += 1;
    } else {
        $feedback[] = "Au moins une majuscule";
    }
    
    // Minuscules
    if (preg_match('/[a-z]/', $password)) {
        $score += 1;
    } else {
        $feedback[] = "Au moins une minuscule";
    }
    
    // Chiffres
    if (preg_match('/[0-9]/', $password)) {
        $score += 1;
    } else {
        $feedback[] = "Au moins un chiffre";
    }
    
    // Caractères spéciaux
    if (preg_match('/[^A-Za-z0-9]/', $password)) {
        $score += 1;
    } else {
        $feedback[] = "Au moins un caractère spécial";
    }
    
    return [
        'score' => $score,
        'strength' => $score < 3 ? 'faible' : ($score < 4 ? 'moyen' : 'fort'),
        'feedback' => $feedback
    ];
}
?>
