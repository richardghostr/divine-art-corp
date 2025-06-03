<?php
$current_page = basename($_SERVER['PHP_SELF']);

// Navigation items
$nav_items = [
    'dashboard' => [
        'title' => 'Principal',
        'items' => [
            ['file' => 'index.php', 'icon' => 'fas fa-home', 'text' => 'Dashboard', 'badge' => null],
            ['file' => 'projects.php', 'icon' => 'fas fa-project-diagram', 'text' => 'Projets', 'badge' => '12'],
            ['file' => 'clients.php', 'icon' => 'fas fa-users', 'text' => 'Clients', 'badge' => null],
            ['file' => 'devis.php', 'icon' => 'fas fa-file-invoice', 'text' => 'Devis', 'badge' => '5']
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
            ['file' => 'contacts.php', 'icon' => 'fas fa-address-book', 'text' => 'Contacts', 'badge' => null],
            ['file' => 'settings.php', 'icon' => 'fas fa-cogs', 'text' => 'Paramètres', 'badge' => null]
        ]
    ]
];

// Calcul de l'utilisation du stockage (simulation)
$storage_used = 65; // Pourcentage
$storage_used_gb = 6.5;
$storage_total_gb = 10;
?>

<!-- Sidebar -->
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
    
    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="storage-info">
            <div class="storage-header">
                <i class="fas fa-hdd"></i>
                <span>Stockage</span>
            </div>
            <div class="storage-bar">
                <div class="storage-progress" style="width: <?php echo $storage_used; ?>%"></div>
            </div>
            <div class="storage-text">
                <?php echo $storage_used; ?>% utilisé (<?php echo $storage_used_gb; ?> GB / <?php echo $storage_total_gb; ?> GB)
            </div>
        </div>
        
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Déconnexion</span>
        </a>
    </div>
</aside>
