<?php
// Gestion du portfolio - Divine Art Corporation
// Version: 2.0
// Date: 2024

// Initialisation de la session et vérification de l'authentification
session_start();
require_once '../includes/auth.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Vérification de l'authentification
$auth = new Auth();
$auth->requireAuth();

// Connexion à la base de données
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Erreur de connexion: " . $conn->connect_error);
}

// Récupération des statistiques du portfolio
$stats = [];
$query = "SELECT 
          COUNT(*) as total_projets,
          SUM(CASE WHEN p.featured = 1 THEN 1 ELSE 0 END) as projets_featured,
          COUNT(DISTINCT d.service) as services_distincts,
          SUM(CASE WHEN p.date_creation >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as nouveaux_projets
          FROM projets p 
          JOIN devis d ON p.devis_id = d.id 
          WHERE p.statut = 'termine' AND p.in_portfolio = 1";
$result = $conn->query($query);
if ($result) {
    $stats = $result->fetch_assoc();
}

// Récupération des services pour le filtrage
$services = [];
$query = "SELECT slug, nom FROM services ORDER BY nom ASC";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
}

// Pagination et filtrage
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$service_filter = isset($_GET['service']) ? $conn->real_escape_string($_GET['service']) : '';
$featured_filter = isset($_GET['featured']) ? (int)$_GET['featured'] : -1;

// Construction de la clause WHERE
$where_clause = "p.statut = 'termine' AND p.in_portfolio = 1";
if (!empty($search)) {
    $where_clause .= " AND (p.nom LIKE '%$search%' OR d.nom LIKE '%$search%' OR d.entreprise LIKE '%$search%')";
}
if (!empty($service_filter)) {
    $where_clause .= " AND d.service = '$service_filter'";
}
if ($featured_filter !== -1) {
    $where_clause .= " AND p.featured = $featured_filter";
}

// Récupération des projets pour le portfolio
$query = "SELECT p.*, d.nom as client_nom, d.entreprise, d.service, d.montant_final,
          s.nom as service_nom,
          (SELECT chemin FROM fichiers WHERE table_liee = 'projets' AND id_enregistrement = p.id AND type_fichier = 'image' ORDER BY id DESC LIMIT 1) as image_principale
          FROM projets p 
          JOIN devis d ON p.devis_id = d.id 
          LEFT JOIN services s ON d.service = s.slug
          WHERE $where_clause
          ORDER BY p.featured DESC, p.date_fin_reelle DESC
          LIMIT $offset, $limit";
$result = $conn->query($query);

$projets = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Image par défaut si pas d'image
        if (empty($row['image_principale'])) {
            $row['image_principale'] = '../assets/img/portfolio/default-' . $row['service'] . '.jpg';
        }
        $projets[] = $row;
    }
}

// Compter le nombre total de projets pour la pagination
$query = "SELECT COUNT(*) as total FROM projets p JOIN devis d ON p.devis_id = d.id WHERE $where_clause";
$result = $conn->query($query);
$total_projets = $result->fetch_assoc()['total'];
$total_pages = ceil($total_projets / $limit);

// Traitement des actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $projet_id = isset($_POST['projet_id']) ? intval($_POST['projet_id']) : 0;
        
        // Action: Mettre en avant un projet
        if ($_POST['action'] === 'toggle_featured' && $projet_id > 0) {
            $featured = isset($_POST['featured']) ? (int)$_POST['featured'] : 0;
            
            $query = "UPDATE projets SET featured = ?, date_modification = NOW() WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $featured, $projet_id);
            
            if ($stmt->execute()) {
                $message = $featured ? "Le projet a été mis en avant." : "Le projet n'est plus mis en avant.";
                $message_type = "success";
                logActivity($_SESSION['admin_id'], 'toggle_featured', 'projets', $projet_id, "Featured: " . ($featured ? 'Oui' : 'Non'));
            } else {
                $message = "Erreur lors de la mise à jour: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }
        
        // Action: Retirer du portfolio
        if ($_POST['action'] === 'remove_from_portfolio' && $projet_id > 0) {
            $query = "UPDATE projets SET in_portfolio = 0, date_modification = NOW() WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $projet_id);
            
            if ($stmt->execute()) {
                $message = "Le projet a été retiré du portfolio.";
                $message_type = "success";
                logActivity($_SESSION['admin_id'], 'remove_from_portfolio', 'projets', $projet_id, "Retiré du portfolio");
            } else {
                $message = "Erreur lors de la suppression: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }
    }
}

// Récupérer les projets terminés non dans le portfolio
$projets_disponibles = [];
$query = "SELECT p.id, p.nom, d.nom as client_nom, d.entreprise, d.service, s.nom as service_nom
          FROM projets p 
          JOIN devis d ON p.devis_id = d.id 
          LEFT JOIN services s ON d.service = s.slug
          WHERE p.statut = 'termine' AND p.in_portfolio = 0
          ORDER BY p.date_fin_reelle DESC
          LIMIT 20";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $projets_disponibles[] = $row;
    }
}

$page_title = "Gestion du Portfolio";
require_once 'header.php';
require_once 'sidebar.php';
?>

<main class="admin-main">
    <div class="content-header">
        <div class="header-left">
            <h1><i class="fas fa-images"></i> Gestion du Portfolio</h1>
            <p>Gérez les projets affichés dans votre portfolio public</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-outline" onclick="exportPortfolio()">
                <i class="fas fa-file-export"></i> Exporter
            </button>
            <button class="btn btn-primary" onclick="openModal('addToPortfolioModal')">
                <i class="fas fa-plus"></i> Ajouter au Portfolio
            </button>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <!-- Statistiques -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon bg-blue">
                <i class="fas fa-images"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['total_projets'] ?? 0; ?></h3>
                <p>Projets au Portfolio</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-orange">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['projets_featured'] ?? 0; ?></h3>
                <p>Projets Mis en Avant</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-green">
                <i class="fas fa-tags"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['services_distincts'] ?? 0; ?></h3>
                <p>Services Représentés</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-purple">
                <i class="fas fa-plus-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['nouveaux_projets'] ?? 0; ?></h3>
                <p>Nouveaux (30j)</p>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="filters-bar">
        <div class="filter-tabs">
            <a href="?featured=-1" class="filter-tab <?php echo $featured_filter === -1 ? 'active' : ''; ?>">
                Tous
            </a>
            <a href="?featured=1" class="filter-tab <?php echo $featured_filter === 1 ? 'active' : ''; ?>">
                Mis en avant
            </a>
            <a href="?featured=0" class="filter-tab <?php echo $featured_filter === 0 ? 'active' : ''; ?>">
                Standard
            </a>
        </div>
        <div class="filter-actions">
            <form method="GET" class="filter-form">
                <select name="service" class="filter-select" onchange="this.form.submit()">
                    <option value="">Tous les services</option>
                    <?php foreach ($services as $service): ?>
                        <option value="<?php echo $service['slug']; ?>" <?php echo $service_filter === $service['slug'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($service['nom']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="search" class="filter-search" placeholder="Rechercher..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- Portfolio Grid -->
    <div class="projects-grid">
        <?php if (empty($projets)): ?>
            <div class="empty-state">
                <i class="fas fa-images"></i>
                <h3>Portfolio vide</h3>
                <p>Aucun projet trouvé dans le portfolio avec les critères sélectionnés.</p>
                <button class="btn btn-primary" onclick="openModal('addToPortfolioModal')">
                    <i class="fas fa-plus"></i> Ajouter des projets
                </button>
            </div>
        <?php else: ?>
            <?php foreach ($projets as $projet): ?>
                <div class="project-card portfolio-item">
                    <?php if ($projet['featured']): ?>
                        <div class="featured-badge">
                            <i class="fas fa-star"></i> Mis en avant
                        </div>
                    <?php endif; ?>
                    
                    <div class="project-image">
                        <img src="<?php echo htmlspecialchars($projet['image_principale']); ?>" 
                             alt="<?php echo htmlspecialchars($projet['nom']); ?>"
                             onerror="this.src='../assets/img/portfolio/default.jpg'">
                        <div class="image-overlay">
                            <button class="btn btn-sm btn-primary" onclick="viewProject(<?php echo $projet['id']; ?>)">
                                <i class="fas fa-eye"></i> Voir
                            </button>
                        </div>
                    </div>
                    
                    <div class="project-content">
                        <div class="project-header">
                            <h4><?php echo htmlspecialchars($projet['nom']); ?></h4>
                            <span class="service-badge"><?php echo htmlspecialchars($projet['service_nom']); ?></span>
                        </div>
                        
                        <div class="project-client">
                            <i class="fas fa-user"></i>
                            <span><?php echo htmlspecialchars($projet['client_nom']); ?></span>
                            <?php if (!empty($projet['entreprise'])): ?>
                                <small>(<?php echo htmlspecialchars($projet['entreprise']); ?>)</small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="project-meta">
                            <div class="meta-item">
                                <i class="fas fa-calendar"></i>
                                <span><?php echo $projet['date_fin_reelle'] ? date('d/m/Y', strtotime($projet['date_fin_reelle'])) : 'N/A'; ?></span>
                            </div>
                            <?php if ($projet['montant_final']): ?>
                            <div class="meta-item">
                                <i class="fas fa-euro-sign"></i>
                                <span><?php echo number_format($projet['montant_final'], 0, ',', ' '); ?> FCFA</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($projet['description'])): ?>
                        <div class="project-description">
                            <?php echo substr(htmlspecialchars($projet['description']), 0, 120); ?>...
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="project-actions">
                        <button class="btn btn-sm btn-outline" onclick="viewProject(<?php echo $projet['id']; ?>)">
                            <i class="fas fa-eye"></i> Voir
                        </button>
                        <button class="btn btn-sm btn-<?php echo $projet['featured'] ? 'warning' : 'success'; ?>" 
                                onclick="toggleFeatured(<?php echo $projet['id']; ?>, <?php echo $projet['featured'] ? 0 : 1; ?>)">
                            <i class="fas fa-star"></i> 
                            <?php echo $projet['featured'] ? 'Retirer' : 'Mettre en avant'; ?>
                        </button>
                        <button class="btn btn-sm btn-outline" onclick="removeFromPortfolio(<?php echo $projet['id']; ?>, '<?php echo addslashes($projet['nom']); ?>')">
                            <i class="fas fa-trash"></i> Retirer
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination-container">
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&service=<?php echo urlencode($service_filter); ?>&featured=<?php echo $featured_filter; ?>" 
                       class="<?php echo $page == $i ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>
</main>

<!-- Modal Ajouter au Portfolio -->
<div class="modal" id="addToPortfolioModal">
    <div class="modal-content large">
        <div class="modal-header">
            <h3>Ajouter des Projets au Portfolio</h3>
            <button class="close" onclick="closeModal('addToPortfolioModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <input type="text" id="searchProjects" class="filter-search" placeholder="Rechercher un projet terminé...">
            </div>
            <div id="projectsList" class="projects-list">
                <?php if (empty($projets_disponibles)): ?>
                    <div class="empty-state">
                        <i class="fas fa-info-circle"></i>
                        <p>Aucun projet terminé disponible pour le portfolio.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($projets_disponibles as $projet): ?>
                        <div class="project-item">
                            <div class="project-info">
                                <h5><?php echo htmlspecialchars($projet['nom']); ?></h5>
                                <p><?php echo htmlspecialchars($projet['client_nom']); ?> - <?php echo htmlspecialchars($projet['service_nom']); ?></p>
                            </div>
                            <button class="btn btn-sm btn-primary" onclick="addToPortfolio(<?php echo $projet['id']; ?>)">
                                <i class="fas fa-plus"></i> Ajouter
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.portfolio-item {
    position: relative;
}

.featured-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: var(--admin-warning);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: var(--admin-radius-sm);
    font-size: 0.75rem;
    font-weight: 500;
    z-index: 1;
}

.project-image {
    position: relative;
    height: 200px;
    overflow: hidden;
    border-radius: var(--admin-radius-lg) var(--admin-radius-lg) 0 0;
}

.project-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.project-image:hover img {
    transform: scale(1.05);
}

.image-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.project-image:hover .image-overlay {
    opacity: 1;
}

.project-client {
    display: flex;
    align-items: center;
    gap: var(--admin-space-sm);
    margin: var(--admin-space-md) 0;
    font-size: 0.875rem;
    color: var(--admin-text-secondary);
}

.project-meta {
    display: flex;
    gap: var(--admin-space-lg);
    margin: var(--admin-space-md) 0;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: var(--admin-space-xs);
    font-size: 0.75rem;
    color: var(--admin-text-muted);
}

.project-description {
    font-size: 0.875rem;
    color: var(--admin-text-secondary);
    line-height: 1.4;
    margin: var(--admin-space-md) 0;
}

.projects-list {
    max-height: 400px;
    overflow-y: auto;
}

.project-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--admin-space-md);
    border: 1px solid var(--admin-border);
    border-radius: var(--admin-radius-md);
    margin-bottom: var(--admin-space-sm);
}

.project-item:hover {
    background: var(--admin-border-light);
}

.project-info h5 {
    margin: 0 0 var(--admin-space-xs) 0;
    font-size: 1rem;
}

.project-info p {
    margin: 0;
    font-size: 0.875rem;
    color: var(--admin-text-secondary);
}
</style>

<script>
// Fonctions JavaScript
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function viewProject(id) {
    window.location.href = 'projet-details.php?id=' + id;
}

function toggleFeatured(id, featured) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="toggle_featured">
        <input type="hidden" name="projet_id" value="${id}">
        <input type="hidden" name="featured" value="${featured}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function removeFromPortfolio(id, nom) {
    if (confirm(`Êtes-vous sûr de vouloir retirer "${nom}" du portfolio ?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="remove_from_portfolio">
            <input type="hidden" name="projet_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function addToPortfolio(id) {
    fetch('ajax/add_to_portfolio.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            projet_id: id
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Projet ajouté au portfolio avec succès.');
            closeModal('addToPortfolioModal');
            location.reload();
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        alert('Erreur lors de l\'ajout du projet au portfolio.');
    });
}

function exportPortfolio() {
    window.open('ajax/export_portfolio.php', '_blank');
}

// Recherche de projets
document.getElementById('searchProjects')?.addEventListener('input', function() {
    const search = this.value.toLowerCase();
    const items = document.querySelectorAll('.project-item');
    
    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(search) ? 'flex' : 'none';
    });
});

// Fermer les modals en cliquant à l'extérieur
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
    }
});
</script>

<?php $conn->close(); ?>
