<?php
/**
 * Configuration de la base de données avec MySQLi
 * Divine Art Corporation
 */
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'divine_art_corp');


// Dans config/database.php
class Database {
    private $host = 'localhost';
    private $db_name = 'divine_art_corp';
    private $username = 'root'; // Changez selon votre configuration
    private $password = '';     // Changez selon votre configuration
    private $conn;
    private static $instance = null;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    public function getConnection() {
        $this->conn = null;
        
        try {
            // Création de la connexion MySQLi
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);
            
            // Vérification de la connexion
            if ($this->conn->connect_error) {
                throw new Exception("Erreur de connexion: " . $this->conn->connect_error);
            }
            
            // Configuration du charset
            $this->conn->set_charset("utf8mb4");
            
        } catch(Exception $exception) {
            error_log("Erreur de connexion: " . $exception->getMessage());
            throw new Exception("Erreur de connexion à la base de données");
        }
        
        return $this->conn;
    }
    
    public function closeConnection() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

/**
 * Classe utilitaire pour les opérations de base de données avec MySQLi
 */
class DatabaseHelper {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }
    
    /**
     * Exécute une requête SELECT et retourne les résultats
     */
    public function select($query, $params = []) {
        try {
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                throw new Exception("Erreur de préparation: " . $this->conn->error);
            }
            
            // Liaison des paramètres si ils existent
            if (!empty($params)) {
                $types = $this->getParamTypes($params);
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            if (!$result) {
                throw new Exception("Erreur d'exécution: " . $this->conn->error);
            }
            
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            
            $stmt->close();
            return $data;
            
        } catch(Exception $e) {
            error_log("Erreur SELECT: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Exécute une requête SELECT et retourne un seul résultat
     */
    public function selectOne($query, $params = []) {
        try {
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                throw new Exception("Erreur de préparation: " . $this->conn->error);
            }
            
            // Liaison des paramètres si ils existent
            if (!empty($params)) {
                $types = $this->getParamTypes($params);
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            if (!$result) {
                throw new Exception("Erreur d'exécution: " . $this->conn->error);
            }
            
            $data = $result->fetch_assoc();
            $stmt->close();
            
            return $data ?: false;
            
        } catch(Exception $e) {
            error_log("Erreur SELECT ONE: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Exécute une requête INSERT, UPDATE ou DELETE
     */
    public function execute($query, $params = []) {
        try {
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                throw new Exception("Erreur de préparation: " . $this->conn->error);
            }
            
            // Liaison des paramètres si ils existent
            if (!empty($params)) {
                $types = $this->getParamTypes($params);
                $stmt->bind_param($types, ...$params);
            }
            
            $result = $stmt->execute();
            
            if (!$result) {
                throw new Exception("Erreur d'exécution: " . $this->conn->error);
            }
            
            $stmt->close();
            return true;
            
        } catch(Exception $e) {
            error_log("Erreur EXECUTE: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Retourne l'ID du dernier enregistrement inséré
     */
    public function lastInsertId() {
        return $this->conn->insert_id;
    }
    
    /**
     * Démarre une transaction
     */
    public function beginTransaction() {
        return $this->conn->autocommit(false);
    }
    
    /**
     * Valide une transaction
     */
    public function commit() {
        $result = $this->conn->commit();
        $this->conn->autocommit(true);
        return $result;
    }
    
    /**
     * Annule une transaction
     */
    public function rollback() {
        $result = $this->conn->rollback();
        $this->conn->autocommit(true);
        return $result;
    }
    
    /**
     * Détermine les types de paramètres pour bind_param
     */
    private function getParamTypes($params) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        return $types;
    }
    
    /**
     * Échappe une chaîne pour éviter les injections SQL
     */
    public function escape($string) {
        return $this->conn->real_escape_string($string);
    }
    
    /**
     * Retourne le nombre de lignes affectées
     */
    public function affectedRows() {
        return $this->conn->affected_rows;
    }
    
    /**
     * Ferme la connexion
     */
    public function close() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
    
    /**
     * Destructeur pour fermer automatiquement la connexion
     */
    public function __destruct() {
        $this->close();
    }
}

/**
 * Fonctions utilitaires globales
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function logActivity($user_id, $action, $table = null, $record_id = null, $details = null) {
    try {
        $db = new DatabaseHelper();
        $query = "INSERT INTO logs_activite (user_id, action, table_concernee, id_enregistrement, details, ip_address, user_agent) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $user_id,
            $action,
            $table,
            $record_id,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ];
        
        return $db->execute($query, $params);
    } catch(Exception $e) {
        error_log("Erreur log activité: " . $e->getMessage());
        return false;
    }
}

/**
 * Fonction pour formater les montants
 */
function formatMontant($montant) {
    return number_format($montant, 0, ',', ' ') . ' FCFA';
}

/**
 * Fonction pour valider un email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Fonction pour valider un numéro de téléphone camerounais
 */
function validatePhone($phone) {
    // Pattern pour les numéros camerounais
    $pattern = '/^(\+237|237)?[6-9][0-9]{8}$/';
    return preg_match($pattern, $phone);
}

/**
 * Fonction pour générer un mot de passe sécurisé
 */
function generatePassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    return substr(str_shuffle($chars), 0, $length);
}


// Connexion à la base de données
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);












?>

