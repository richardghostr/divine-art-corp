<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../includes/auth.php';

// Vérification de l'authentification pour les pages protégées
$protected_pages = ['index.php', 'devis.php', 'contacts.php', 'settings.php', 'clients.php', 'projets.php'];
$current_page = basename($_SERVER['PHP_SELF']);

if (in_array($current_page, $protected_pages)) {
    $auth = new Auth();
    $auth->requireAuth();
}

// Configuration de la page
$page_config = [
    'index.php' => ['title' => 'Tableau de bord', 'section' => 'dashboard'],
    'devis.php' => ['title' => 'Gestion des Devis', 'section' => 'devis'],
    'contacts.php' => ['title' => 'Gestion des Contacts', 'section' => 'contacts'],
    'clients.php' => ['title' => 'Gestion des Clients', 'section' => 'clients'],
    'projets.php' => ['title' => 'Gestion des Projets', 'section' => 'projets'],
    'settings.php' => ['title' => 'Paramètres', 'section' => 'settings'],
    'login.php' => ['title' => 'Connexion', 'section' => 'login']
];

$current_config = $page_config[$current_page] ?? ['title' => 'Administration', 'section' => 'admin'];
$page_title = $current_config['title'] . ' - Divine Art Corporation';

// Récupération des informations utilisateur
$auth = new Auth();
$current_user = $auth->getCurrentUser();
$user_name = $current_user ? $current_user['nom'] : 'Admin';

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
            <!-- Menu Toggle pour mobile -->
            <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
                <i class="fas fa-bars"></i>
            </button>
            
            <!-- Logo et Brand -->
            <div class="admin-brand">
                <div class="admin-logo">
                    <img src="../assets/images/Logo.svg" alt="Divine Art Corp">
                </div>
                <span class="admin-brand-text">Divine Art Corp</span>
            </div>
            
            <!-- Navigation Header (cachée sur mobile) -->
            <nav class="admin-header-nav ">
                <a href="../index.php" class="nav-link" target="_blank">
                    <i class="fas fa-globe"></i>
                    <span>Site Web</span>
                </a>
                <a href="#" class="nav-link">
                    <i class="fas fa-chart-line"></i>
                    <span>Analytics</span>
                </a>
                <a href="settings.php" class="nav-link">
                    <i class="fas fa-cog"></i>
                    <span>Paramètres</span>
                </a>
            </nav>
            
            <!-- Barre de recherche (cachée sur mobile) -->
            <div class="admin-search hidden-mobile">
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" placeholder="Rechercher..." class="search-input" id="globalSearch">
                </div>
            </div>
            
            <!-- Actions utilisateur -->
            <div class="admin-user-actions">
                <!-- Notifications -->
               
                <!-- Menu utilisateur -->
                <div class="user-menu" id="userMenu">
                    <div class="user-avatar" id="">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="user-info " id="">
                        <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                        <span class="user-role"><?php echo ucfirst($current_user['role'] ?? 'Administrateur'); ?></span>
                    </div>
                    <button class="user-dropdown-btn" id="userDropdownBtn" aria-label="User menu">
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

    <!-- Overlay pour sidebar mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Layout Principal -->
    <div class="admin-layout">
        
        <script>
        // Gestion des dropdowns et menu mobile
        document.addEventListener('DOMContentLoaded', function() {
            // Menu toggle
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.querySelector('.admin-sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            
            if (menuToggle && sidebar) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('open');
                    sidebarOverlay.classList.toggle('active');
                    document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
                });
            }
            
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', function() {
                    sidebar.classList.remove('open');
                    sidebarOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                });
            }
            
            // Dropdown utilisateur
            const userDropdownBtn = document.getElementById('userDropdownBtn');
            const userDropdown = document.getElementById('userDropdown');
            
            if (userDropdownBtn && userDropdown) {
                userDropdownBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userDropdown.style.display = userDropdown.style.display === 'none' ? 'block' : 'none';
                });
            }
            
            // Fermer les dropdowns en cliquant ailleurs
            document.addEventListener('click', function() {
                if (userDropdown) userDropdown.style.display = 'none';
            });
            
            // Recherche globale
            const globalSearch = document.getElementById('globalSearch');
            if (globalSearch) {
                globalSearch.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        const query = this.value.trim();
                        if (query) {
                            window.location.href = `search.php?q=${encodeURIComponent(query)}`;
                        }
                    }
                });
            }
            
            // Gestion responsive
            function handleResize() {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('open');
                    sidebarOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            }
            
            window.addEventListener('resize', handleResize);
            window.addEventListener('orientationchange', function() {
                setTimeout(handleResize, 100);
            });
        });
        </script>
<?php endif; ?>
