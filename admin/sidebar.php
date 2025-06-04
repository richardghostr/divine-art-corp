<?php
require_once '../config/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();
$dbHelper = new DatabaseHelper();

$current_page = basename($_SERVER['PHP_SELF']);

// Récupération des statistiques pour les badges
$stats = [];

// Nouveaux devis
$result = $dbHelper->selectOne("SELECT COUNT(*) as count FROM devis WHERE statut = 'nouveau'");
$stats['nouveaux_devis'] = $result ? $result['count'] : 0;

// Nouveaux contacts
$result = $dbHelper->selectOne("SELECT COUNT(*) as count FROM contacts WHERE statut = 'nouveau'");
$stats['nouveaux_contacts'] = $result ? $result['count'] : 0;

// Projets en cours
$result = $dbHelper->selectOne("SELECT COUNT(*) as count FROM projets WHERE statut = 'en_cours'");
$stats['projets_en_cours'] = $result ? $result['count'] : 0;

// Navigation items avec badges dynamiques
$nav_items = [
    'dashboard' => [
        'title' => 'Principal',
        'items' => [
            ['file' => 'index.php', 'icon' => 'fas fa-home', 'text' => 'Dashboard', 'badge' => null],
            ['file' => 'projets.php', 'icon' => 'fas fa-project-diagram', 'text' => 'Projets', 'badge' => $stats['projets_en_cours'] > 0 ? $stats['projets_en_cours'] : null],
            ['file' => 'clients.php', 'icon' => 'fas fa-users', 'text' => 'Clients', 'badge' => null],
            ['file' => 'devis.php', 'icon' => 'fas fa-file-invoice', 'text' => 'Devis', 'badge' => $stats['nouveaux_devis'] > 0 ? $stats['nouveaux_devis'] : null]
        ]
    ],
    'services' => [
        'title' => 'Services',
        'items' => [
            ['file' => 'marketing.php', 'icon' => 'fas fa-bullhorn', 'text' => 'Marketing', 'badge' => null],
            ['file' => 'graphique.php', 'icon' => 'fas fa-paint-brush', 'text' => 'Design Graphique', 'badge' => null],
            ['file' => 'multimedia.php', 'icon' => 'fas fa-video', 'text' => 'Multimédia', 'badge' => null],
            ['file' => 'imprimerie.php', 'icon' => 'fas fa-print', 'text' => 'Imprimerie', 'badge' => null]
        ]
    ],
    'management' => [
        'title' => 'Gestion',
        'items' => [
            ['file' => 'portfolio.php', 'icon' => 'fas fa-images', 'text' => 'Portfolio', 'badge' => null],
            ['file' => 'finances.php', 'icon' => 'fas fa-chart-pie', 'text' => 'Finances', 'badge' => null],
            ['file' => 'contacts.php', 'icon' => 'fas fa-address-book', 'text' => 'Contacts', 'badge' => $stats['nouveaux_contacts'] > 0 ? $stats['nouveaux_contacts'] : null],
            ['file' => 'settings.php', 'icon' => 'fas fa-cogs', 'text' => 'Paramètres', 'badge' => null]
        ]
    ]
];

// Calcul de l'utilisation du stockage (simulation)
$storage_query = "SELECT SUM(taille) as total_size FROM fichiers";
$storage_result = $dbHelper->selectOne($storage_query);
$storage_used_bytes = $storage_result ? $storage_result['total_size'] : 0;

// Conversion en GB et calcul du pourcentage
$storage_total_gb = 10; // Limite de stockage en GB
$storage_used_gb = round($storage_used_bytes / (1024 * 1024 * 1024), 2);
$storage_used_percent = min(100, round(($storage_used_gb / $storage_total_gb) * 100));

// Si pas de données, utiliser des valeurs par défaut pour la démo
if ($storage_used_bytes == 0) {
    $storage_used_gb = 6.5;
    $storage_used_percent = 65;
}
?>

<aside class="admin-sidebar">
  
    <nav class="sidebar-nav">
        <?php foreach ($nav_items as $section_key => $section): ?>
            <div class="nav-section">
                <div class="nav-section-title"><?php echo $section['title']; ?></div>
                <ul class="nav-list">
                    <?php foreach ($section['items'] as $item): ?>
                        <li class="nav-item">
                            <a href="<?php echo $item['file']; ?>" 
                               class="nav-link <?php echo ($current_page === $item['file']) ? 'active' : ''; ?>">
                                <i class="<?php echo $item['icon']; ?> nav-icon"></i>
                                <span class="nav-text"><?php echo $item['text']; ?></span>
                                <?php if ($item['badge']): ?>
                                    <span class="nav-badge"><?php echo $item['badge']; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </nav>
    
    <div class="sidebar-footer">
        <a href="logout.php" class="logout-btn" onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?')">
            <i class="fas fa-sign-out-alt"></i>
            <span>Déconnexion</span>
        </a>
    </div>
</aside>

<script>
// Auto-refresh des badges toutes les 60 secondes
setInterval(function() {
    fetch('ajax/get_sidebar_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour les badges
                updateBadge('devis.php', data.stats.nouveaux_devis);
                updateBadge('contacts.php', data.stats.nouveaux_contacts);
                updateBadge('projets.php', data.stats.projets_en_cours);
            }
        })
        .catch(error => console.error('Erreur mise à jour sidebar:', error));
}, 60000);

function updateBadge(page, count) {
    const link = document.querySelector(`a[href="${page}"]`);
    if (link) {
        let badge = link.querySelector('.nav-badge');
        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'nav-badge';
                link.appendChild(badge);
            }
            badge.textContent = count;
        } else if (badge) {
            badge.remove();
        }
    }
}
</script>
