<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once 'header.php';
require_once 'sidebar.php';

// Vérification de l'authentification
$auth = new Auth();
$auth->requireAuth();

// Vérifier les permissions (seuls les admins peuvent accéder aux paramètres)
if ($_SESSION['admin']['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$databaseHelper = new DatabaseHelper();
$success_message = '';
$error_message = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        try {
            switch ($action) {
                case 'update_general':
                    $settings = [
                        'site_name' => sanitizeInput($_POST['site_name']),
                        'site_email' => sanitizeInput($_POST['site_email']),
                        'site_phone' => sanitizeInput($_POST['site_phone']),
                        'site_address' => sanitizeInput($_POST['site_address']),
                        'site_description' => sanitizeInput($_POST['site_description'])
                    ];
                    
                    foreach ($settings as $key => $value) {
                        $databaseHelper->execute(
                            "UPDATE configurations SET valeur = ?, date_modification = NOW() WHERE cle = ?",
                            [$value, $key]
                        );
                    }
                    
                    $auth->logActivity($_SESSION['admin']['id'], 'settings_general_update', 'configurations');
                    $success_message = "Paramètres généraux mis à jour avec succès.";
                    break;
                    
                case 'update_email':
                    $email_settings = [
                        'smtp_host' => sanitizeInput($_POST['smtp_host']),
                        'smtp_port' => (int)$_POST['smtp_port'],
                        'smtp_username' => sanitizeInput($_POST['smtp_username']),
                        'smtp_password' => sanitizeInput($_POST['smtp_password'])
                    ];
                    
                    foreach ($email_settings as $key => $value) {
                        $databaseHelper->execute(
                            "UPDATE configurations SET valeur = ?, date_modification = NOW() WHERE cle = ?",
                            [$value, $key]
                        );
                    }
                    
                    $auth->logActivity($_SESSION['admin']['id'], 'settings_email_update', 'configurations');
                    $success_message = "Paramètres email mis à jour avec succès.";
                    break;
                    
                case 'update_notifications':
                    $notification_settings = [
                        'notifications_email' => isset($_POST['notifications_email']) ? '1' : '0',
                        'notifications_sms' => isset($_POST['notifications_sms']) ? '1' : '0'
                    ];
                    
                    foreach ($notification_settings as $key => $value) {
                        $databaseHelper->execute(
                            "UPDATE configurations SET valeur = ?, date_modification = NOW() WHERE cle = ?",
                            [$value, $key]
                        );
                    }
                    
                    $auth->logActivity($_SESSION['admin']['id'], 'settings_notifications_update', 'configurations');
                    $success_message = "Paramètres de notifications mis à jour avec succès.";
                    break;
                    
                case 'update_uploads':
                    $upload_settings = [
                        'max_file_size' => (int)$_POST['max_file_size'],
                        'allowed_file_types' => sanitizeInput($_POST['allowed_file_types'])
                    ];
                    
                    foreach ($upload_settings as $key => $value) {
                        $databaseHelper->execute(
                            "UPDATE configurations SET valeur = ?, date_modification = NOW() WHERE cle = ?",
                            [$value, $key]
                        );
                    }
                    
                    $auth->logActivity($_SESSION['admin']['id'], 'settings_uploads_update', 'configurations');
                    $success_message = "Paramètres d'upload mis à jour avec succès.";
                    break;
                    
                case 'update_tracking':
                    $tracking_settings = [
                        'google_analytics_id' => sanitizeInput($_POST['google_analytics_id']),
                        'facebook_pixel_id' => sanitizeInput($_POST['facebook_pixel_id'])
                    ];
                    
                    foreach ($tracking_settings as $key => $value) {
                        $databaseHelper->execute(
                            "UPDATE configurations SET valeur = ?, date_modification = NOW() WHERE cle = ?",
                            [$value, $key]
                        );
                    }
                    
                    $auth->logActivity($_SESSION['admin']['id'], 'settings_tracking_update', 'configurations');
                    $success_message = "Paramètres de tracking mis à jour avec succès.";
                    break;
                    
                case 'update_system':
                    $system_settings = [
                        'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
                        'cache_enabled' => isset($_POST['cache_enabled']) ? '1' : '0',
                        'cache_duration' => (int)$_POST['cache_duration']
                    ];
                    
                    foreach ($system_settings as $key => $value) {
                        $databaseHelper->execute(
                            "UPDATE configurations SET valeur = ?, date_modification = NOW() WHERE cle = ?",
                            [$value, $key]
                        );
                    }
                    
                    $auth->logActivity($_SESSION['admin']['id'], 'settings_system_update', 'configurations');
                    $success_message = "Paramètres système mis à jour avec succès.";
                    break;
                    
                case 'add_admin':
                    $nom = sanitizeInput($_POST['nom']);
                    $email = sanitizeInput($_POST['email']);
                    $telephone = sanitizeInput($_POST['telephone']);
                    $role = sanitizeInput($_POST['role']);
                    $mot_de_passe = password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT);
                    
                    // Vérifier si l'email existe déjà
                    $existing = $databaseHelper->selectOne(
                        "SELECT id FROM admins WHERE email = ?",
                        [$email]
                    );
                    
                    if ($existing) {
                        $error_message = "Un administrateur avec cet email existe déjà.";
                        break;
                    }
                    
                    $result = $databaseHelper->execute(
                        "INSERT INTO admins (nom, email, telephone, role, mot_de_passe) VALUES (?, ?, ?, ?, ?)",
                        [$nom, $email, $telephone, $role, $mot_de_passe]
                    );
                    
                    if ($result) {
                        $lastInsertId = $conn->insert_id;
                        $auth->logActivity($_SESSION['admin']['id'], 'admin_created', 'admins', $lastInsertId);
                        $success_message = "Administrateur ajouté avec succès.";
                    }
                    break;
                    
                case 'update_admin':
                    $admin_id = (int)$_POST['admin_id'];
                    $nom = sanitizeInput($_POST['nom']);
                    $email = sanitizeInput($_POST['email']);
                    $telephone = sanitizeInput($_POST['telephone']);
                    $role = sanitizeInput($_POST['role']);
                    $statut = sanitizeInput($_POST['statut']);
                    
                    // Vérifier si l'email existe déjà pour un autre admin
                    $existing = $databaseHelper->selectOne(
                        "SELECT id FROM admins WHERE email = ? AND id != ?",
                        [$email, $admin_id]
                    );
                    
                    if ($existing) {
                        $error_message = "Un autre administrateur avec cet email existe déjà.";
                        break;
                    }
                    
                    $query = "UPDATE admins SET nom = ?, email = ?, telephone = ?, role = ?, statut = ?, date_modification = NOW() WHERE id = ?";
                    $params = [$nom, $email, $telephone, $role, $statut, $admin_id];
                    
                    // Si un nouveau mot de passe est fourni
                    if (!empty($_POST['mot_de_passe'])) {
                        $mot_de_passe = password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT);
                        $query = "UPDATE admins SET nom = ?, email = ?, telephone = ?, role = ?, statut = ?, mot_de_passe = ?, date_modification = NOW() WHERE id = ?";
                        $params = [$nom, $email, $telephone, $role, $statut, $mot_de_passe, $admin_id];
                    }
                    
                    $result = $databaseHelper->execute($query, $params);
                    
                    if ($result) {
                        $auth->logActivity($_SESSION['admin']['id'], 'admin_updated', 'admins', $admin_id);
                        $success_message = "Administrateur mis à jour avec succès.";
                    }
                    break;
                    
                case 'delete_admin':
                    $admin_id = (int)$_POST['admin_id'];
                    
                    // Empêcher la suppression de son propre compte
                    if ($admin_id === $_SESSION['admin']['id']) {
                        $error_message = "Vous ne pouvez pas supprimer votre propre compte.";
                        break;
                    }
                    
                    // Vérifier s'il y a des données associées
                    $has_data = $databaseHelper->selectOne(
                        "SELECT COUNT(*) as count FROM devis WHERE admin_assigne = ?",
                        [$admin_id]
                    );
                    
                    if ($has_data && $has_data['count'] > 0) {
                        $error_message = "Impossible de supprimer cet administrateur car il a des données associées.";
                        break;
                    }
                    
                    $result = $databaseHelper->execute(
                        "DELETE FROM admins WHERE id = ?",
                        [$admin_id]
                    );
                    
                    if ($result) {
                        $auth->logActivity($_SESSION['admin']['id'], 'admin_deleted', 'admins', $admin_id);
                        $success_message = "Administrateur supprimé avec succès.";
                    }
                    break;
                    
                case 'backup_database':
                    // Créer une sauvegarde de la base de données
                    $backup_name = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
                    $backup_path = '../backups/' . $backup_name;
                    
                    // Créer le dossier de sauvegarde s'il n'existe pas
                    if (!is_dir('../backups')) {
                        mkdir('../backups', 0755, true);
                    }
                    
                    // Commande mysqldump (à adapter selon votre configuration)
                    $command = "mysqldump --user='" . DB_USER . "' --password='" . DB_PASS . "' --host='" . DB_HOST . "' " . DB_NAME . " > " . $backup_path;
                    
                    exec($command, $output, $return_var);
                    
                    if ($return_var === 0) {
                        $file_size = filesize($backup_path);
                        
                        // Enregistrer la sauvegarde dans la base
                        $databaseHelper->execute(
                            "INSERT INTO sauvegardes (nom_fichier, chemin, taille, type, statut, admin_id, date_fin) VALUES (?, ?, ?, 'manuelle', 'termine', ?, NOW())",
                            [$backup_name, $backup_path, $file_size, $_SESSION['admin']['id']]
                        );
                        
                        $auth->logActivity($_SESSION['admin']['id'], 'database_backup', 'sauvegardes');
                        $success_message = "Sauvegarde créée avec succès.";
                    } else {
                        $error_message = "Erreur lors de la création de la sauvegarde.";
                    }
                    break;
                    
                case 'clear_cache':
                    // Nettoyer le cache (à adapter selon votre système de cache)
                    $cache_dir = '../cache/';
                    if (is_dir($cache_dir)) {
                        $files = glob($cache_dir . '*');
                        foreach ($files as $file) {
                            if (is_file($file)) {
                                unlink($file);
                            }
                        }
                    }
                    
                    $auth->logActivity($_SESSION['admin']['id'], 'cache_cleared', 'system');
                    $success_message = "Cache vidé avec succès.";
                    break;
                    
                case 'clear_logs':
                    // Nettoyer les anciens logs
                    $result = $databaseHelper->execute(
                        "DELETE FROM logs_activite WHERE date_creation < DATE_SUB(NOW(), INTERVAL 90 DAY)"
                    );
                    
                    if ($result) {
                        $auth->logActivity($_SESSION['admin']['id'], 'logs_cleared', 'logs_activite');
                        $success_message = "Anciens logs supprimés avec succès.";
                    }
                    break;
            }
        } catch (Exception $e) {
            $error_message = "Erreur lors de l'opération: " . $e->getMessage();
            error_log("Erreur settings: " . $e->getMessage());
        }
    }
}

// Récupération des configurations
$configurations = [];
$config_query = "SELECT cle, valeur, description, type, categorie FROM configurations ORDER BY categorie, cle";
$config_result = $conn->query($config_query);

while ($row = $config_result->fetch_assoc()) {
    $configurations[$row['categorie']][$row['cle']] = $row;
}

// Récupération des administrateurs
$admins_query = "SELECT * FROM admins ORDER BY nom";
$admins_result = $conn->query($admins_query);
$admins_list = [];
while ($row = $admins_result->fetch_assoc()) {
    $admins_list[] = $row;
}

// Récupération des sauvegardes récentes
$backups_query = "SELECT * FROM sauvegardes ORDER BY date_creation DESC LIMIT 10";
$backups_result = $conn->query($backups_query);
$backups_list = [];
while ($row = $backups_result->fetch_assoc()) {
    $backups_list[] = $row;
}

// Statistiques système
$stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM devis) as total_devis,
        (SELECT COUNT(*) FROM contacts) as total_contacts,
        (SELECT COUNT(*) FROM projets) as total_projets,
        (SELECT COUNT(*) FROM admins WHERE statut = 'actif') as total_admins,
        (SELECT COUNT(*) FROM logs_activite WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) as logs_24h,
        (SELECT COUNT(*) FROM sessions_admin WHERE derniere_activite >= DATE_SUB(NOW(), INTERVAL 1 HOUR)) as sessions_actives
";

$stats_result = $conn->query($stats_query);
$system_stats = $stats_result->fetch_assoc();
?>

<!-- Contenu Principal -->
<main class="admin-main">
    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <div class="section-header">
        <div class="section-title">
            <h2>Paramètres du Système</h2>
            <p>Configuration et administration du site</p>
        </div>
    </div>

    <!-- Statistiques système -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-file-invoice"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $system_stats['total_devis']; ?></div>
                <div class="stat-label">Devis total</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-envelope"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $system_stats['total_contacts']; ?></div>
                <div class="stat-label">Messages total</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-project-diagram"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $system_stats['total_projets']; ?></div>
                <div class="stat-label">Projets total</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $system_stats['total_admins']; ?></div>
                <div class="stat-label">Administrateurs</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $system_stats['logs_24h']; ?></div>
                <div class="stat-label">Activités 24h</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-wifi"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $system_stats['sessions_actives']; ?></div>
                <div class="stat-label">Sessions actives</div>
            </div>
        </div>
    </div>

    <!-- Onglets de configuration -->
    <div class="settings-tabs">
        <div class="tab-nav">
            <button class="tab-button active" data-tab="general">
                <i class="fas fa-cog"></i>
                Général
            </button>
            <button class="tab-button" data-tab="email">
                <i class="fas fa-envelope"></i>
                Email
            </button>
            <button class="tab-button" data-tab="notifications">
                <i class="fas fa-bell"></i>
                Notifications
            </button>
            <button class="tab-button" data-tab="uploads">
                <i class="fas fa-upload"></i>
                Uploads
            </button>
            <button class="tab-button" data-tab="tracking">
                <i class="fas fa-chart-bar"></i>
                Tracking
            </button>
            <button class="tab-button" data-tab="system">
                <i class="fas fa-server"></i>
                Système
            </button>
            <button class="tab-button" data-tab="admins">
                <i class="fas fa-users-cog"></i>
                Administrateurs
            </button>
            <button class="tab-button" data-tab="maintenance">
                <i class="fas fa-tools"></i>
                Maintenance
            </button>
        </div>

        <!-- Onglet Général -->
        <div class="tab-content active" id="general">
            <div class="settings-section" >
                <h3>Paramètres généraux</h3>
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="update_general">
                    
                    <div class="form-group">
                        <label for="site_name">Nom du site</label>
                        <input type="text" name="site_name" id="site_name" class="form-control" 
                               value="<?php echo htmlspecialchars($configurations['general']['site_name']['valeur'] ?? ''); ?>" required>
                        <small class="form-help">Le nom de votre site web</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="site_email">Email principal</label>
                        <input type="email" name="site_email" id="site_email" class="form-control" 
                               value="<?php echo htmlspecialchars($configurations['general']['site_email']['valeur'] ?? ''); ?>" required>
                        <small class="form-help">Adresse email principale du site</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="site_phone">Téléphone</label>
                        <input type="text" name="site_phone" id="site_phone" class="form-control" 
                               value="<?php echo htmlspecialchars($configurations['general']['site_phone']['valeur'] ?? ''); ?>">
                        <small class="form-help">Numéro de téléphone principal</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="site_address">Adresse</label>
                        <textarea name="site_address" id="site_address" class="form-control" rows="3"><?php echo htmlspecialchars($configurations['general']['site_address']['valeur'] ?? ''); ?></textarea>
                        <small class="form-help">Adresse physique de l'entreprise</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="site_description">Description</label>
                        <textarea name="site_description" id="site_description" class="form-control" rows="3"><?php echo htmlspecialchars($configurations['seo']['site_description']['valeur'] ?? ''); ?></textarea>
                        <small class="form-help">Description du site pour le SEO</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Onglet Email -->
        <div class="tab-content" id="email">
            <div class="settings-section">
                <h3>Configuration SMTP</h3>
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="update_email">
                    
                    <div class="form-group">
                        <label for="smtp_host">Serveur SMTP</label>
                        <input type="text" name="smtp_host" id="smtp_host" class="form-control" 
                               value="<?php echo htmlspecialchars($configurations['email']['smtp_host']['valeur'] ?? ''); ?>">
                        <small class="form-help">Ex: smtp.gmail.com</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="smtp_port">Port SMTP</label>
                        <input type="number" name="smtp_port" id="smtp_port" class="form-control" 
                               value="<?php echo htmlspecialchars($configurations['email']['smtp_port']['valeur'] ?? '587'); ?>">
                        <small class="form-help">Port du serveur SMTP (587 pour TLS, 465 pour SSL)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="smtp_username">Nom d'utilisateur</label>
                        <input type="text" name="smtp_username" id="smtp_username" class="form-control" 
                               value="<?php echo htmlspecialchars($configurations['email']['smtp_username']['valeur'] ?? ''); ?>">
                        <small class="form-help">Nom d'utilisateur pour l'authentification SMTP</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="smtp_password">Mot de passe</label>
                        <input type="password" name="smtp_password" id="smtp_password" class="form-control" 
                               value="<?php echo htmlspecialchars($configurations['email']['smtp_password']['valeur'] ?? ''); ?>">
                        <small class="form-help">Mot de passe pour l'authentification SMTP</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-outline" onclick="testEmailConfig()">
                            <i class="fas fa-paper-plane"></i>
                            Tester la configuration
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Onglet Notifications -->
        <div class="tab-content" id="notifications">
            <div class="settings-section">
                <h3>Paramètres de notifications</h3>
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="update_notifications">
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="notifications_email" 
                                       <?php echo ($configurations['notifications']['notifications_email']['valeur'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                <span class="checkbox-custom"></span>
                                Notifications par email
                            </label>
                            <small class="form-help">Recevoir des notifications par email pour les nouveaux devis et contacts</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="notifications_sms" 
                                       <?php echo ($configurations['notifications']['notifications_sms']['valeur'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                <span class="checkbox-custom"></span>
                                Notifications par SMS
                            </label>
                            <small class="form-help">Recevoir des notifications par SMS (nécessite une configuration supplémentaire)</small>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Onglet Uploads -->
        <div class="tab-content" id="uploads">
            <div class="settings-section">
                <h3>Paramètres d'upload</h3>
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="update_uploads">
                    
                    <div class="form-group">
                        <label for="max_file_size">Taille maximale des fichiers (octets)</label>
                        <input type="number" name="max_file_size" id="max_file_size" class="form-control" 
                               value="<?php echo htmlspecialchars($configurations['uploads']['max_file_size']['valeur'] ?? '5242880'); ?>">
                        <small class="form-help">Taille maximale en octets (5242880 = 5MB)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="allowed_file_types">Types de fichiers autorisés</label>
                        <input type="text" name="allowed_file_types" id="allowed_file_types" class="form-control" 
                               value="<?php echo htmlspecialchars($configurations['uploads']['allowed_file_types']['valeur'] ?? 'jpg,jpeg,png,gif,pdf,doc,docx,zip'); ?>">
                        <small class="form-help">Extensions séparées par des virgules</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Onglet Tracking -->
        <div class="tab-content" id="tracking">
            <div class="settings-section">
                <h3>Outils de tracking</h3>
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="update_tracking">
                    
                    <div class="form-group">
                        <label for="google_analytics_id">ID Google Analytics</label>
                        <input type="text" name="google_analytics_id" id="google_analytics_id" class="form-control" 
                               value="<?php echo htmlspecialchars($configurations['tracking']['google_analytics_id']['valeur'] ?? ''); ?>"
                               placeholder="G-XXXXXXXXXX">
                        <small class="form-help">ID de suivi Google Analytics</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="facebook_pixel_id">ID Facebook Pixel</label>
                        <input type="text" name="facebook_pixel_id" id="facebook_pixel_id" class="form-control" 
                               value="<?php echo htmlspecialchars($configurations['tracking']['facebook_pixel_id']['valeur'] ?? ''); ?>"
                               placeholder="123456789012345">
                        <small class="form-help">ID du pixel Facebook</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Onglet Système -->
        <div class="tab-content" id="system">
            <div class="settings-section">
                <h3>Paramètres système</h3>
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="update_system">
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="maintenance_mode" 
                                       <?php echo ($configurations['system']['maintenance_mode']['valeur'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                <span class="checkbox-custom"></span>
                                Mode maintenance
                            </label>
                            <small class="form-help">Activer le mode maintenance pour le site public</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="cache_enabled" 
                                       <?php echo ($configurations['performance']['cache_enabled']['valeur'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                <span class="checkbox-custom"></span>
                                Cache activé
                            </label>
                            <small class="form-help">Activer le système de cache pour améliorer les performances</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="cache_duration">Durée du cache (secondes)</label>
                        <input type="number" name="cache_duration" id="cache_duration" class="form-control" 
                               value="<?php echo htmlspecialchars($configurations['performance']['cache_duration']['valeur'] ?? '3600'); ?>">
                        <small class="form-help">Durée de conservation du cache en secondes</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Onglet Administrateurs -->
        <div class="tab-content" id="admins">
            <div class="settings-section">
                <div class="section-header">
                    <h3>Gestion des administrateurs</h3>
                    <button class="btn btn-primary" onclick="showAddAdminModal()">
                        <i class="fas fa-plus"></i>
                        Ajouter un administrateur
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th>Statut</th>
                                <th>Dernière connexion</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins_list as $admin): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($admin['nom']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                    <td>
                                        <span class="role-badge <?php echo $admin['role']; ?>">
                                            <?php echo ucfirst($admin['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $admin['statut']; ?>">
                                            <?php echo ucfirst($admin['statut']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($admin['derniere_connexion']): ?>
                                            <?php echo date('d/m/Y H:i', strtotime($admin['derniere_connexion'])); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Jamais</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-outline" onclick="editAdmin(<?php echo $admin['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($admin['id'] !== $_SESSION['admin']['id']): ?>
                                                <button class="btn btn-sm btn-danger" onclick="deleteAdmin(<?php echo $admin['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Onglet Maintenance -->
        <div class="tab-content" id="maintenance">
            <div class="settings-section">
                <h3>Outils de maintenance</h3>
                
                <div class="maintenance-grid">
                    <div class="maintenance-card">
                        <div class="maintenance-icon">
                            <i class="fas fa-database"></i>
                        </div>
                        <div class="maintenance-content">
                            <h4>Sauvegarde de la base de données</h4>
                            <p>Créer une sauvegarde complète de la base de données</p>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="backup_database">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-download"></i>
                                    Créer une sauvegarde
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="maintenance-card">
                        <div class="maintenance-icon">
                            <i class="fas fa-broom"></i>
                        </div>
                        <div class="maintenance-content">
                            <h4>Vider le cache</h4>
                            <p>Supprimer tous les fichiers de cache temporaires</p>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="clear_cache">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-trash-alt"></i>
                                    Vider le cache
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="maintenance-card">
                        <div class="maintenance-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="maintenance-content">
                            <h4>Nettoyer les logs</h4>
                            <p>Supprimer les anciens logs d'activité (+ de 90 jours)</p>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="clear_logs">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-eraser"></i>
                                    Nettoyer les logs
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Sauvegardes récentes -->
                <div class="backups-section">
                    <h4>Sauvegardes récentes</h4>
                    <?php if (empty($backups_list)): ?>
                        <p class="text-muted">Aucune sauvegarde trouvée.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Nom du fichier</th>
                                        <th>Taille</th>
                                        <th>Type</th>
                                        <th>Date</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($backups_list as $backup): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($backup['nom_fichier']); ?></td>
                                            <td><?php echo formatFileSize($backup['taille']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $backup['type'] === 'automatique' ? 'info' : 'primary'; ?>">
                                                    <?php echo ucfirst($backup['type']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($backup['date_creation'])); ?></td>
                                            <td>
                                                <span class="status-badge <?php echo $backup['statut']; ?>">
                                                    <?php echo ucfirst($backup['statut']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($backup['statut'] === 'termine' && file_exists($backup['chemin'])): ?>
                                                    <a href="<?php echo $backup['chemin']; ?>" class="btn btn-sm btn-outline" download>
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal Ajouter Administrateur -->
<div id="addAdminModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Ajouter un administrateur</h3>
            <button class="modal-close" onclick="closeModal('addAdminModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="addAdminForm" method="POST">
                <input type="hidden" name="action" value="add_admin">
                
                <div class="form-group">
                    <label for="add_nom">Nom complet</label>
                    <input type="text" name="nom" id="add_nom" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="add_email">Email</label>
                    <input type="email" name="email" id="add_email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="add_telephone">Téléphone</label>
                    <input type="text" name="telephone" id="add_telephone" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="add_role">Rôle</label>
                    <select name="role" id="add_role" class="form-control" required>
                        <option value="editor">Éditeur</option>
                        <option value="manager">Manager</option>
                        <option value="admin">Administrateur</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="add_mot_de_passe">Mot de passe</label>
                    <input type="password" name="mot_de_passe" id="add_mot_de_passe" class="form-control" required>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal('addAdminModal')">Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Modifier Administrateur -->
<div id="editAdminModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Modifier l'administrateur</h3>
            <button class="modal-close" onclick="closeModal('editAdminModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="editAdminForm" method="POST">
                <input type="hidden" name="action" value="update_admin">
                <input type="hidden" name="admin_id" id="edit_admin_id">
                
                <div class="form-group">
                    <label for="edit_nom">Nom complet</label>
                    <input type="text" name="nom" id="edit_nom" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_email">Email</label>
                    <input type="email" name="email" id="edit_email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_telephone">Téléphone</label>
                    <input type="text" name="telephone" id="edit_telephone" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="edit_role">Rôle</label>
                    <select name="role" id="edit_role" class="form-control" required>
                        <option value="editor">Éditeur</option>
                        <option value="manager">Manager</option>
                        <option value="admin">Administrateur</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_statut">Statut</label>
                    <select name="statut" id="edit_statut" class="form-control" required>
                        <option value="actif">Actif</option>
                        <option value="inactif">Inactif</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_mot_de_passe">Nouveau mot de passe (optionnel)</label>
                    <input type="password" name="mot_de_passe" id="edit_mot_de_passe" class="form-control">
                    <small class="form-help">Laissez vide pour conserver le mot de passe actuel</small>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal('editAdminModal')">Annuler</button>
                    <button type="submit" class="btn btn-primary">Modifier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Gestion des onglets
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            // Retirer la classe active de tous les boutons et contenus
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Ajouter la classe active au bouton et contenu sélectionnés
            this.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });
});

function showAddAdminModal() {
    document.getElementById('addAdminModal').style.display = 'flex';
}

function editAdmin(id) {
    // Récupérer les détails via AJAX
    fetch(`ajax/get_admin_details.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('edit_admin_id').value = id;
                document.getElementById('edit_nom').value = data.admin.nom;
                document.getElementById('edit_email').value = data.admin.email;
                document.getElementById('edit_telephone').value = data.admin.telephone || '';
                document.getElementById('edit_role').value = data.admin.role;
                document.getElementById('edit_statut').value = data.admin.statut;
                
                document.getElementById('editAdminModal').style.display = 'flex';
            }
        })
        .catch(error => console.error('Erreur:', error));
}

function deleteAdmin(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet administrateur ? Cette action est irréversible.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_admin">
            <input type="hidden" name="admin_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function testEmailConfig() {
    // Tester la configuration email
    fetch('ajax/test_email_config.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            smtp_host: document.getElementById('smtp_host').value,
            smtp_port: document.getElementById('smtp_port').value,
            smtp_username: document.getElementById('smtp_username').value,
            smtp_password: document.getElementById('smtp_password').value
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Configuration email testée avec succès !');
        } else {
            alert('Erreur lors du test : ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors du test de la configuration email.');
    });
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Fonction pour formater la taille des fichiers
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

<?php
// Fonction PHP pour formater la taille des fichiers
function formatFileSize($bytes) {
    if ($bytes == 0) return '0 Bytes';
    
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}
?>
</script>



<style>
    /* ========================================
   PARAMETRES STYLES
   ======================================== */

/* Alertes */
.alert {
  padding: var(--admin-space-lg);
  border-radius: var(--admin-radius-md);
  margin-bottom: var(--admin-space-xl);
  display: flex;
  align-items: center;
  gap: var(--admin-space-md);
  font-size: 0.875rem;
  font-weight: 500;
}

.alert-success {
  background: rgba(39, 174, 96, 0.1);
  color: var(--admin-success);
  border-left: 4px solid var(--admin-success);
}

.alert-error {
  background: rgba(231, 76, 60, 0.1);
  color: var(--admin-danger);
  border-left: 4px solid var(--admin-danger);
}

/* Onglets */
.settings-tabs {
  background: var(--admin-card-bg);
  border-radius: var(--admin-radius-xl);
  box-shadow: var(--admin-shadow-sm);
  overflow: hidden;
  margin-top: var(--admin-space-xl);
}

.tab-nav {
  display: flex;
  overflow-x: auto;
  padding: var(--admin-space-md) var(--admin-space-lg);
  border-bottom: 1px solid var(--admin-border-light);
  gap: var(--admin-space-xs);
}

.tab-button {
  display: flex;
  align-items: center;
  gap: var(--admin-space-sm);
  padding: var(--admin-space-md) var(--admin-space-lg);
  background: none;
  border: none;
  border-radius: var(--admin-radius-md);
  cursor: pointer;
  transition: var(--admin-transition);
  font-size: 0.875rem;
  color: var(--admin-text-secondary);
  white-space: nowrap;
}

.tab-button.active, 
.tab-button:hover {
  background: rgba(231, 76, 60, 0.1);
  color: var(--admin-accent);
}

.tab-button i {
  font-size: 1rem;
}

.tab-content {
  display: none;
  padding: var(--admin-space-xl);
}

.tab-content.active {
  display: block;
  animation: fadeIn 0.3s ease-out;
}

/* Formulaires */


.form-help {
  display: block;
  margin-top: var(--admin-space-xs);
  font-size: 0.75rem;
  color: var(--admin-text-muted);
}

.checkbox-group {
  display: flex;
  align-items: center;
  gap: var(--admin-space-sm);
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: var(--admin-space-sm);
  cursor: pointer;
  font-size: 0.875rem;
  color: var(--admin-text-primary);
}

.checkbox-custom {
  width: 18px;
  height: 18px;
  border: 1px solid var(--admin-border);
  border-radius: var(--admin-radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: var(--admin-transition);
}

.checkbox-custom::after {
  content: "✓";
  font-size: 0.75rem;
  color: white;
  display: none;
}

.checkbox-label input:checked + .checkbox-custom {
  background: var(--admin-accent);
  border-color: var(--admin-accent);
}

.checkbox-label input:checked + .checkbox-custom::after {
  display: block;
}

/* Tableaux */
.table-responsive {
  overflow-x: auto;
  margin-top: var(--admin-space-xl);
}

.table {
  width: 100%;
  border-collapse: collapse;
}

.table th,
.table td {
  padding: var(--admin-space-md);
  text-align: left;
  border-bottom: 1px solid var(--admin-border-light);
}

.table th {
  background: var(--admin-bg);
  font-weight: 600;
  color: var(--admin-text-primary);
  font-size: 0.875rem;
}

.table tr:hover {
  background: var(--admin-border-light);
}

.role-badge, .status-badge {
  font-size: 0.75rem;
  padding: 0.25rem 0.75rem;
  border-radius: var(--admin-radius-sm);
  font-weight: 500;
}

.role-badge.editor {
  background: rgba(52, 152, 219, 0.1);
  color: var(--admin-info);
}

.role-badge.manager {
  background: rgba(155, 89, 182, 0.1);
  color: #9b59b6;
}

.role-badge.admin {
  background: rgba(231, 76, 60, 0.1);
  color: var(--admin-accent);
}

.status-badge.actif {
  background: rgba(39, 174, 96, 0.1);
  color: var(--admin-success);
}

.status-badge.inactif {
  background: rgba(108, 117, 125, 0.1);
  color: var(--admin-text-muted);
}

.action-buttons {
  display: flex;
  gap: var(--admin-space-xs);
}

/* Grille de maintenance */
.maintenance-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: var(--admin-space-xl);
  margin-top: var(--admin-space-xl);
}

.maintenance-card {
  background: var(--admin-card-bg);
  border-radius: var(--admin-radius-lg);
  padding: var(--admin-space-xl);
  display: flex;
  flex-direction: column;
  gap: var(--admin-space-md);
  box-shadow: var(--admin-shadow-sm);
  transition: var(--admin-transition);
}

.maintenance-card:hover {
  transform: translateY(-3px);
  box-shadow: var(--admin-shadow-md);
}

.maintenance-icon {
  width: 50px;
  height: 50px;
  border-radius: var(--admin-radius-lg);
  background: rgba(52, 152, 219, 0.1);
  color: var(--admin-info);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

.maintenance-content h4 {
  font-size: 1.125rem;
  font-weight: 600;
  margin-bottom: var(--admin-space-xs);
}

.maintenance-content p {
  color: var(--admin-text-secondary);
  font-size: 0.875rem;
  margin-bottom: var(--admin-space-md);
}

/* Modales */
.modal {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  z-index: 2000;
  align-items: center;
  justify-content: center;
}

.modal-content {
  background: var(--admin-card-bg);
  width: 90%;
  max-width: 600px;
  border-radius: var(--admin-radius-xl);
  box-shadow: var(--admin-shadow-lg);
  overflow: hidden;
  animation: modalFadeIn 0.3s ease-out;
}

@keyframes modalFadeIn {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--admin-space-lg);
  border-bottom: 1px solid var(--admin-border-light);
}

.modal-header h3 {
  font-size: 1.25rem;
  color: var(--admin-text-primary);
}

.modal-close {
  background: none;
  border: none;
  color: var(--admin-text-muted);
  cursor: pointer;
  font-size: 1.5rem;
  transition: var(--admin-transition);
}

.modal-close:hover {
  color: var(--admin-accent);
}

.modal-body {
  padding: var(--admin-space-xl);
}

/* Badges */
.badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: var(--admin-radius-sm);
  font-size: 0.75rem;
  font-weight: 500;
}

.badge-info {
  background: rgba(52, 152, 219, 0.1);
  color: var(--admin-info);
}

.badge-primary {
  background: rgba(231, 76, 60, 0.1);
  color: var(--admin-accent);
}

/* Responsive */
@media (max-width: 768px) {
  .tab-nav {
    flex-wrap: wrap;
  }
  
  .modal-body {
    padding: var(--admin-space-lg);
  }
  
  .maintenance-grid {
    grid-template-columns: 1fr;
  }
}
</style>