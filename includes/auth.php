<?php
/**
 * Système d'authentification sécurisé pour Divine Art Corporation
 * Compatible avec la structure de base de données fournie
 */

require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;
    private $max_login_attempts = 5;
    private $lockout_time = 15 * 60; // 15 minutes en secondes
    
    public function __construct() {
        $this->db = new DatabaseHelper();
    }
    
    /**
     * Authentifie un administrateur
     */
    public function login($email, $password) {
        // Vérifier si l'email est valide
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Email invalide'];
        }
        
        // Récupérer l'utilisateur
        $user = $this->db->selectOne(
            "SELECT * FROM admins WHERE email = ?",
            [$email]
        );
        
        // Vérifications de sécurité
        if (!$user) {
            return ['success' => false, 'error' => 'Identifiants incorrects'];
        }
        
        if ($user['statut'] !== 'actif') {
            return ['success' => false, 'error' => 'Votre compte est désactivé'];
        }
        
        if ($user['tentatives_connexion'] >= $this->max_login_attempts) {
            $last_attempt = strtotime($user['derniere_connexion'] ?? 'now');
            $remaining_time = $this->lockout_time - (time() - $last_attempt);
            
            if ($remaining_time > 0) {
                return [
                    'success' => false, 
                    'error' => 'Trop de tentatives. Veuillez réessayer dans ' . ceil($remaining_time / 60) . ' minutes.'
                ];
            } else {
                // Réinitialiser le compteur après la période de verrouillage
                $this->db->execute(
                    "UPDATE admins SET tentatives_connexion = 0 WHERE id = ?",
                    [$user['id']]
                );
            }
        }
        
        // Vérifier le mot de passe
        if ($user && password_verify($password, $user['mot_de_passe'])) {
            // Vérifier si le mot de passe doit être mis à jour (algorithme obsolète)
            if (password_needs_rehash($user['mot_de_passe'], PASSWORD_DEFAULT)) {
                $this->updatePassword($user['id'], $password);
            }
            
            // Mise à jour de la dernière connexion
            $this->db->execute(
                "UPDATE admins SET 
                    derniere_connexion = NOW(), 
                    tentatives_connexion = 0,
                    token_reset = NULL,
                    token_reset_expire = NULL
                WHERE id = ?",
                [$user['id']]
            );
            
            // Création de la session sécurisée
            $this->createSecureSession($user);
            
            // Journalisation
            $this->logActivity($user['id'], 'connexion_reussie', 'admins', $user['id']);
            
            return ['success' => true, 'user' => $user];
        } else {
            // Incrémenter le compteur de tentatives échouées
            $this->db->execute(
                "UPDATE admins SET 
                    tentatives_connexion = tentatives_connexion + 1,
                    derniere_connexion = NOW()
                WHERE id = ?",
                [$user['id']]
            );
            
            $remaining_attempts = $this->max_login_attempts - ($user['tentatives_connexion'] + 1);
            
            // Journalisation
            $this->logActivity($user['id'], 'tentative_connexion_echouee', 'admins', $user['id']);
            
            return [
                'success' => false, 
                'error' => 'Identifiants incorrects',
                'remaining_attempts' => $remaining_attempts > 0 ? $remaining_attempts : 0
            ];
        }
    }
    
    /**
     * Crée une session sécurisée
     */
    private function createSecureSession($user) {
        // Configurer les paramètres de session sécurisés
        $session_name = 'DIVINEART_SESSID';
        $secure = true; // HTTPS seulement
        $httponly = true; // Empêcher l'accès via JavaScript
        
        // Forcer l'utilisation de cookies seulement
        ini_set('session.use_only_cookies', 1);
        
        // Paramètres du cookie
        $cookieParams = session_get_cookie_params();
        session_set_cookie_params(
            $cookieParams["lifetime"],
            $cookieParams["path"],
            $cookieParams["domain"],
            $secure,
            $httponly
        );
        
        // Nom de la session
        session_name($session_name);
        session_start();
        session_regenerate_id(true);
        
        // Stocker les données utilisateur
        $_SESSION['admin'] = [
            'id' => $user['id'],
            'nom' => $user['nom'],
            'email' => $user['email'],
            'role' => $user['role'],
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'login_time' => time()
        ];
        
        // Token CSRF
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }
    
    /**
     * Déconnecte l'utilisateur
     */
    public function logout() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Journalisation
        if (isset($_SESSION['admin']['id'])) {
            $this->logActivity($_SESSION['admin']['id'], 'deconnexion', 'admins', $_SESSION['admin']['id']);
        }
        
        // Supprimer toutes les variables de session
        $_SESSION = array();
        
        // Supprimer le cookie de session
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        // Détruire la session
        session_destroy();
        
        return true;
    }
    
    /**
     * Vérifie si l'utilisateur est connecté et valide la session
     */
    public function isLoggedIn() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['admin'])) {
            return false;
        }
        
        // Vérification de sécurité supplémentaire
        $session = $_SESSION['admin'];
        
        // Vérifier l'IP et le User Agent
        if ($session['ip'] !== $_SERVER['REMOTE_ADDR'] || 
            $session['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            $this->logout();
            return false;
        }
        
        // Vérifier si la session a expiré (30 minutes d'inactivité)
        if (isset($session['login_time']) && (time() - $session['login_time'] > 1800)) {
            $this->logout();
            return false;
        }
        
        // Mettre à jour le temps d'activité
        $_SESSION['admin']['login_time'] = time();
        
        return true;
    }
    
    /**
     * Vérifie les permissions
     */
    public function hasPermission($required_role) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $user_role = $_SESSION['admin']['role'] ?? '';
        
        $roles_hierarchy = [
            'editor' => 1,
            'manager' => 2,
            'admin' => 3
        ];
        
        $user_level = $roles_hierarchy[$user_role] ?? 0;
        $required_level = $roles_hierarchy[$required_role] ?? 0;
        
        return $user_level >= $required_level;
    }
    
    /**
     * Change le mot de passe
     */
    public function changePassword($admin_id, $current_password, $new_password) {
        // Vérifier la force du mot de passe
        $strength = $this->checkPasswordStrength($new_password);
        if ($strength['score'] < 4) {
            return [
                'success' => false,
                'error' => 'Le mot de passe est trop faible',
                'feedback' => $strength['feedback']
            ];
        }
        
        // Récupérer l'utilisateur
        $admin = $this->db->selectOne(
            "SELECT mot_de_passe FROM admins WHERE id = ?",
            [$admin_id]
        );
        
        if (!$admin || !password_verify($current_password, $admin['mot_de_passe'])) {
            return ['success' => false, 'error' => 'Mot de passe actuel incorrect'];
        }
        
        // Mettre à jour le mot de passe
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $result = $this->db->execute(
            "UPDATE admins SET 
                mot_de_passe = ?,
                token_reset = NULL,
                token_reset_expire = NULL,
                date_modification = NOW()
            WHERE id = ?",
            [$hashed_password, $admin_id]
        );
        
        if ($result) {
            $this->logActivity($admin_id, 'changement_mot_de_passe', 'admins', $admin_id);
            return ['success' => true];
        }
        
        return ['success' => false, 'error' => 'Erreur lors de la mise à jour'];
    }
    
    /**
     * Génère un token de réinitialisation
     */
    public function generateResetToken($email) {
        $admin = $this->db->selectOne(
            "SELECT id FROM admins WHERE email = ? AND statut = 'actif'",
            [$email]
        );
        
        if (!$admin) {
            // Ne pas révéler si l'email existe ou non
            return ['success' => true];
        }
        
        // Générer un token sécurisé
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 heure
        
        $result = $this->db->execute(
            "UPDATE admins SET 
                token_reset = ?,
                token_reset_expire = ?,
                date_modification = NOW()
            WHERE id = ?",
            [$token, $expires, $admin['id']]
        );
        
        if ($result) {
            $this->logActivity($admin['id'], 'demande_reinitialisation_mdp', 'admins', $admin['id']);
            return ['success' => true, 'token' => $token];
        }
        
        return ['success' => false, 'error' => 'Erreur lors de la génération du token'];
    }
    
    /**
     * Réinitialise le mot de passe avec un token valide
     */
    public function resetPasswordWithToken($token, $new_password) {
        // Vérifier la force du mot de passe
        $strength = $this->checkPasswordStrength($new_password);
        if ($strength['score'] < 4) {
            return [
                'success' => false,
                'error' => 'Le mot de passe est trop faible',
                'feedback' => $strength['feedback']
            ];
        }
        
        $admin = $this->db->selectOne(
            "SELECT id FROM admins WHERE token_reset = ? AND token_reset_expire > NOW()",
            [$token]
        );
        
        if (!$admin) {
            return ['success' => false, 'error' => 'Token invalide ou expiré'];
        }
        
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $result = $this->db->execute(
            "UPDATE admins SET 
                mot_de_passe = ?,
                token_reset = NULL,
                token_reset_expire = NULL,
                tentatives_connexion = 0,
                date_modification = NOW()
            WHERE id = ?",
            [$hashed_password, $admin['id']]
        );
        
        if ($result) {
            $this->logActivity($admin['id'], 'reinitialisation_mdp', 'admins', $admin['id']);
            return ['success' => true];
        }
        
        return ['success' => false, 'error' => 'Erreur lors de la réinitialisation'];
    }
    
    /**
     * Vérifie la force d'un mot de passe
     */
    public function checkPasswordStrength($password) {
        $score = 0;
        $feedback = [];
        
        // Longueur minimum
        if (strlen($password) >= 12) {
            $score += 2;
        } elseif (strlen($password) >= 8) {
            $score += 1;
            $feedback[] = "Utilisez au moins 12 caractères pour plus de sécurité";
        } else {
            $feedback[] = "Le mot de passe doit contenir au moins 8 caractères";
        }
        
        // Majuscules
        if (preg_match('/[A-Z]/', $password)) {
            $score += 1;
        } else {
            $feedback[] = "Ajoutez au moins une majuscule";
        }
        
        // Minuscules
        if (preg_match('/[a-z]/', $password)) {
            $score += 1;
        } else {
            $feedback[] = "Ajoutez au moins une minuscule";
        }
        
        // Chiffres
        if (preg_match('/[0-9]/', $password)) {
            $score += 1;
        } else {
            $feedback[] = "Ajoutez au moins un chiffre";
        }
        
        // Caractères spéciaux
        if (preg_match('/[^A-Za-z0-9]/', $password)) {
            $score += 1;
        } else {
            $feedback[] = "Ajoutez au moins un caractère spécial";
        }
        
        // Mots de passe courants à éviter
        $common_passwords = ['password', '123456', 'qwerty', 'divineart', 'admin'];
        if (in_array(strtolower($password), $common_passwords)) {
            $score = 0;
            $feedback[] = "Ce mot de passe est trop courant et facile à deviner";
        }
        
        return [
            'score' => $score,
            'strength' => $score < 3 ? 'faible' : ($score < 5 ? 'moyen' : 'fort'),
            'feedback' => $feedback
        ];
    }
    
    /**
     * Met à jour le mot de passe de l'utilisateur avec un nouveau hash
     */
    private function updatePassword($admin_id, $new_password) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $this->db->execute(
            "UPDATE admins SET 
                mot_de_passe = ?,
                token_reset = NULL,
                token_reset_expire = NULL,
                date_modification = NOW()
            WHERE id = ?",
            [$hashed_password, $admin_id]
        );
        $this->logActivity($admin_id, 'mise_a_jour_hash_mot_de_passe', 'admins', $admin_id);
    }

    /**
     * Journalisation des activités
     */
    public function logActivity($user_id, $action, $table = null, $record_id = null) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'inconnu';
        
        $this->db->execute(
            "INSERT INTO logs_activite 
                (user_id, action, table_concernee, id_enregistrement, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?)",
            [$user_id, $action, $table, $record_id, $ip, $user_agent]
        );
    }
    
    /**
     * Récupère l'utilisateur actuel
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return $this->db->selectOne(
            "SELECT id, nom, email, role, telephone, statut, derniere_connexion, date_creation 
             FROM admins 
             WHERE id = ?",
            [$_SESSION['admin']['id']]
        );
    }
    
    /**
     * Middleware de protection des routes
     */
    public function requireAuth($required_role = 'admin', $redirect = 'login.php') {
        if (!$this->isLoggedIn()) {
            if ($redirect) {
                header('Location: ' . $redirect . '?redirect=' . urlencode($_SERVER['REQUEST_URI']));
                exit;
            } else {
                http_response_code(401);
                exit('Accès non autorisé');
            }
        }
        
        if (!$this->hasPermission($required_role)) {
            http_response_code(403);
            exit('Permissions insuffisantes');
        }
        
        return true;
    }
    
    /**
     * Génère un token CSRF
     */
    public function getCsrfToken() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Vérifie un token CSRF
     */
    public function verifyCsrfToken($token) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            return false;
        }
        
        return true;
    }
}

// Fonction helper pour le middleware
function requireAuth($required_role = 'admin', $redirect = 'login.php') {
    $auth = new Auth();
    $auth->requireAuth($required_role, $redirect);
}