<div class="admin-sidebar">
    <div class="sidebar-header">
        <img src="assets/images/logo.png" alt="DAC" class="sidebar-logo">
        <h3>DAC Admin</h3>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <li class="nav-item">
                <a href="?page=admin&section=dashboard" class="nav-link <?php echo ($_GET['section'] ?? 'dashboard') === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Tableau de Bord</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="?page=admin&section=devis" class="nav-link <?php echo ($_GET['section'] ?? '') === 'devis' ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i>
                    <span>Gestion des Devis</span>
                    <?php if ($devisEnAttente > 0): ?>
                        <span class="badge"><?php echo $devisEnAttente; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="?page=admin&section=contacts" class="nav-link <?php echo ($_GET['section'] ?? '') === 'contacts' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="?page=admin&section=portfolio" class="nav-link <?php echo ($_GET['section'] ?? '') === 'portfolio' ? 'active' : ''; ?>">
                    <i class="fas fa-images"></i>
                    <span>Portfolio</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="?page=admin&section=services" class="nav-link <?php echo ($_GET['section'] ?? '') === 'services' ? 'active' : ''; ?>">
                    <i class="fas fa-cogs"></i>
                    <span>Services</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="?page=admin&section=analytics" class="nav-link <?php echo ($_GET['section'] ?? '') === 'analytics' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Statistiques</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="?page=admin&section=settings" class="nav-link <?php echo ($_GET['section'] ?? '') === 'settings' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Paramètres</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-details">
                <span class="user-name"><?php echo $_SESSION['admin_user']['username']; ?></span>
                <span class="user-role">Administrateur</span>
            </div>
        </div>
        
        <a href="api/admin.php?action=logout" class="logout-btn" title="Déconnexion">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
</div>