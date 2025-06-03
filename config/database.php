<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'divine_art_corp');

class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    private $dbh;
    private $error;

    public function __construct() {
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname . ';charset=utf8';
        $options = array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        );

        try {
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function query($query) {
        $this->stmt = $this->dbh->prepare($query);
    }

    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    public function execute() {
        return $this->stmt->execute();
    }

    public function resultset() {
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function single() {
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function rowCount() {
        return $this->stmt->rowCount();
    }

    public function lastInsertId() {
        return $this->dbh->lastInsertId();
    }
}

// Initialisation de la base de données
function initDatabase() {
    try {
        $pdo = new PDO('mysql:host=' . DB_HOST, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Créer la base de données si elle n'existe pas
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8 COLLATE utf8_general_ci");
        $pdo->exec("USE " . DB_NAME);
        
        // Créer les tables
        $tables = [
            "CREATE TABLE IF NOT EXISTS contacts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nom VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                telephone VARCHAR(20),
                entreprise VARCHAR(100),
                message TEXT,
                date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS devis (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nom VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                telephone VARCHAR(20),
                entreprise VARCHAR(100),
                service VARCHAR(50) NOT NULL,
                sous_service VARCHAR(100),
                description TEXT,
                budget VARCHAR(50),
                delai VARCHAR(50),
                statut ENUM('nouveau', 'en_cours', 'termine') DEFAULT 'nouveau',
                date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS services (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nom VARCHAR(100) NOT NULL,
                description TEXT,
                icone VARCHAR(50),
                couleur VARCHAR(20),
                actif BOOLEAN DEFAULT TRUE
            )",
            
            "CREATE TABLE IF NOT EXISTS sous_services (
                id INT AUTO_INCREMENT PRIMARY KEY,
                service_id INT,
                nom VARCHAR(100) NOT NULL,
                description TEXT,
                prix_min DECIMAL(10,2),
                prix_max DECIMAL(10,2),
                FOREIGN KEY (service_id) REFERENCES services(id)
            )",
            
            "CREATE TABLE IF NOT EXISTS admin_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(100),
                role ENUM('admin', 'manager') DEFAULT 'admin',
                date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )"
        ];
        
        foreach ($tables as $table) {
            $pdo->exec($table);
        }
        
        // Insérer les données par défaut
        insertDefaultData($pdo);
        
    } catch(PDOException $e) {
        die("Erreur de base de données: " . $e->getMessage());
    }
}

function insertDefaultData($pdo) {
    // Vérifier si les services existent déjà
    $stmt = $pdo->query("SELECT COUNT(*) FROM services");
    if ($stmt->fetchColumn() == 0) {
        $services = [
            ['Marketing Digital', 'Stratégies digitales performantes pour développer votre présence en ligne', 'fas fa-chart-line', '#e74c3c'],
            ['Conception Graphique', 'Identité visuelle professionnelle pour votre marque', 'fas fa-palette', '#3498db'],
            ['Conception Multimédia', 'Contenus visuels impactants pour vos communications', 'fas fa-video', '#9b59b6'],
            ['Imprimerie', 'Impression haute qualité pour tous vos supports', 'fas fa-print', '#27ae60']
        ];
        
        foreach ($services as $service) {
            $stmt = $pdo->prepare("INSERT INTO services (nom, description, icone, couleur) VALUES (?, ?, ?, ?)");
            $stmt->execute($service);
        }
    }
    
    // Créer un utilisateur admin par défaut
    $stmt = $pdo->query("SELECT COUNT(*) FROM admin_users");
    if ($stmt->fetchColumn() == 0) {
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admin_users (username, password, email) VALUES (?, ?, ?)");
        $stmt->execute(['admin', $password, 'admin@divineartcorp.cm']);
    }
}

// Initialiser la base de données
initDatabase();
?>