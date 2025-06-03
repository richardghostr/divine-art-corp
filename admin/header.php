<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../includes/auth.php';

// Vérification de l'authentification pour les pages protégées
$protected_pages = ['index.php', 'devis.php', 'contacts.php', 'settings.php'];
$current_page = basename($_SERVER['PHP_SELF']);

if (in_array($current_page, $protected_pages)) {
    requireAuth();
}

// Configuration de la page
$page_config = [
    'index.php' => ['title' => 'Dashboard', 'section' => 'dashboard'],
    'devis.php' => ['title' => 'Gestion des Devis', 'section' => 'devis'],
    'contacts.php' => ['title' => 'Gestion des Contacts', 'section' => 'contacts'],
    'settings.php' => ['title' => 'Paramètres', 'section' => 'settings'],
    'login.php' => ['title' => 'Connexion', 'section' => 'login']
];

$current_config = $page_config[$current_page] ?? ['title' => 'Administration', 'section' => 'admin'];
$page_title = $current_config['title'] . ' - Divine Art Corporation';

// Récupération des informations utilisateur
$current_user = getCurrentUser();
$user_name = $current_user ? $current_user['username'] : 'Admin DAC';

// Récupération des statistiques pour les notifications
$db = new DatabaseHelper();
$notifications = [];

try {
    // Nouveaux devis
    $new_devis = $db->selectOne("SELECT COUNT(*) as count FROM devis WHERE statut = 'nouveau'");
    if ($new_devis && $new_devis['count'] > 0) {
        $notifications[] = [
            'type' => 'devis',
            'count' => $new_devis['count'],
            'message' => $new_devis['count'] . ' nouveau(x) devis'
        ];
    }
    
    // Nouveaux contacts
    $new_contacts = $db->selectOne("SELECT COUNT(*) as count FROM contacts WHERE statut = 'nouveau'");
    if ($new_contacts && $new_contacts['count'] > 0) {
        $notifications[] = [
            'type' => 'contacts',
            'count' => $new_contacts['count'],
            'message' => $new_contacts['count'] . ' nouveau(x) contact(s)'
        ];
    }
    
    // Devis urgents
    $urgent_devis = $db->selectOne("SELECT COUNT(*) as count FROM devis WHERE priorite = 'urgente' AND statut != 'termine'");
    if ($urgent_devis && $urgent_devis['count'] > 0) {
        $notifications[] = [
            'type' => 'urgent',
            'count' => $urgent_devis['count'],
            'message' => $urgent_devis['count'] . ' devis urgent(s)'
        ];
    }
} catch (Exception $e) {
    error_log("Erreur récupération notifications: " . $e->getMessage());
}

$total_notifications = array_sum(array_column($notifications, 'count'));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <!-- Meta tags -->
    <meta name="description" content="Interface d'administration Divine Art Corporation">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
</head>
<body class="admin-body">

<?php if ($current_page !== 'login.php'): ?>
    <!-- Header Admin -->
    <header class="admin-header">
        <div class="admin-header-content">
            <!-- Logo et Brand -->
            <div class="admin-brand">
                <div class="admin-logo">
                    <i class="fas fa-palette"></i>
                </div>
                <span class="admin-brand-text">Divine Art Corp</span>
            </div>
            
            <!-- Navigation Header -->
            <nav class="admin-header-nav">
                <a href="../index.php" class="nav-link" target="_blank">
                    <i class="fas fa-globe"></i>
                    Site Web
                </a>
                <a href="#" class="nav-link">
                    <i class="fas fa-chart-line"></i>
                    Analytics
                </a>
                <a href="settings.php" class="nav-link">
                    <i class="fas fa-cog"></i>
                    Paramètres
                </a>
            </nav>
            
            <!-- Barre de recherche -->
            <div class="admin-search">
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" placeholder="Rechercher..." class="search-input" id="globalSearch">
                </div>
            </div>
            
            <!-- Actions utilisateur -->
            <div class="admin-user-actions">
                <button class="notification-btn" id="notificationBtn">
                    <i class="fas fa-bell"></i>
                    <?php if ($total_notifications > 0): ?>
                        <span class="notification-badge"><?php echo $total_notifications; ?></span>
                    <?php endif; ?>
                </button>
                
                <!-- Dropdown notifications -->
                <div class="notification-dropdown" id="notificationDropdown" style="display: none;">
                    <div class="notification-header">
                        <h4>Notifications</h4>
                        <span class="notification-count"><?php echo $total_notifications; ?> nouvelle(s)</span>
                    </div>
                    <div class="notification-list">
                        <?php if (empty($notifications)): ?>
                            <div class="notification-item">
                                <div class="notification-content">
                                    <p>Aucune nouvelle notification</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($notifications as $notification): ?>
                                <div class="notification-item">
                                    <div class="notification-icon <?php echo $notification['type']; ?>">
                                        <i class="fas fa-<?php echo $notification['type'] === 'devis' ? 'file-invoice' : ($notification['type'] === 'contacts' ? 'envelope' : 'exclamation-triangle'); ?>"></i>
                                    </div>
                                    <div class="notification-content">
                                        <p><?php echo htmlspecialchars($notification['message']); ?></p>
                                        <span class="notification-time">Maintenant</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="notification-footer">
                        <a href="#" class="notification-link">Voir toutes les notifications</a>
                    </div>
                </div>
                
                <div class="user-menu" id="userMenu">
                    <div class="user-avatar">
                        <img src="/placeholder.svg?height=40&width=40" alt="Admin">
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                        <span class="user-role"><?php echo ucfirst($_SESSION['admin_role'] ?? 'Administrateur'); ?></span>
                    </div>
                    <button class="user-dropdown-btn" id="userDropdownBtn">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                
                <!-- Dropdown Menu -->
                <div class="user-dropdown" id="userDropdown" style="display: none;">
                    <a href="settings.php" class="dropdown-item">
                        <i class="fas fa-user"></i>
                        Profil
                    </a>
                    <a href="settings.php" class="dropdown-item">
                        <i class="fas fa-cog"></i>
                        Paramètres
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="dropdown-item">
                        <i class="fas fa-sign-out-alt"></i>
                        Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Layout Principal -->
    <div class="admin-layout">
        
        <script>
        // Gestion des dropdowns
        document.addEventListener('DOMContentLoaded', function() {
            // Dropdown utilisateur
            const userDropdownBtn = document.getElementById('userDropdownBtn');
            const userDropdown = document.getElementById('userDropdown');
            
            if (userDropdownBtn && userDropdown) {
                userDropdownBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userDropdown.style.display = userDropdown.style.display === 'none' ? 'block' : 'none';
                    // Fermer les notifications si ouvertes
                    const notificationDropdown = document.getElementById('notificationDropdown');
                    if (notificationDropdown) {
                        notificationDropdown.style.display = 'none';
                    }
                });
            }
            
            // Dropdown notifications
            const notificationBtn = document.getElementById('notificationBtn');
            const notificationDropdown = document.getElementById('notificationDropdown');
            
            if (notificationBtn && notificationDropdown) {
                notificationBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    notificationDropdown.style.display = notificationDropdown.style.display === 'none' ? 'block' : 'none';
                    // Fermer le menu utilisateur si ouvert
                    if (userDropdown) {
                        userDropdown.style.display = 'none';
                    }
                });
            }
            
            // Fermer les dropdowns en cliquant ailleurs
            document.addEventListener('click', function() {
                if (userDropdown) userDropdown.style.display = 'none';
                if (notificationDropdown) notificationDropdown.style.display = 'none';
            });
            
            // Recherche globale
            const globalSearch = document.getElementById('globalSearch');
            if (globalSearch) {
                globalSearch.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        const query = this.value.trim();
                        if (query) {
                            // Redirection vers la page de recherche ou filtrage
                            console.log('Recherche:', query);
                        }
                    }
                });
            }
        });
        </script>
<?php endif; ?>
