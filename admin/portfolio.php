<?php
// Gestion du portfolio - Divine Art Corporation
// Version: 1.0
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
          SUM(CASE WHEN p.statut = 'termine' THEN 1 ELSE 0 END) as projets_termines,
          COUNT(DISTINCT d.service) as services_distincts
          FROM projets p 
          JOIN devis d ON p.devis_id = d.id 
          WHERE p.statut = 'termine'";
$result = $conn->query($query);
if ($result) {
    $stats = $result->fetch_assoc();
}

// Récupération des services pour le filtrage
$services = [];
$query = "SELECT id, nom, slug FROM services ORDER BY nom ASC";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
}

// Pagination et filtrage
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 12; // Afficher 12 projets par page (grille 3x4)
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$service_filter = isset($_GET['service']) ? $conn->real_escape_string($_GET['service']) : '';
$featured_filter = isset($_GET['featured']) ? (int)$_GET['featured'] : -1;

// Construction de la clause WHERE
$where_clause = "p.statut = 'termine'";
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
$query = "SELECT p.*, d.nom as client_nom, d.entreprise, d.service, 
          (SELECT chemin FROM fichiers WHERE table_liee = 'projets' AND id_enregistrement = p.id AND type_fichier = 'projet' ORDER BY id DESC LIMIT 1) as image_principale
          FROM projets p 
          JOIN devis d ON p.devis_id = d.id 
          WHERE $where_clause
          ORDER BY p.featured DESC, p.date_fin_reelle DESC
          LIMIT $offset, $limit";
$result = $conn->query($query);

$projets = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Si pas d'image principale, utiliser une image par défaut selon le service
        if (empty($row['image_principale'])) {
            switch ($row['service']) {
                case 'marketing':
                    $row['image_principale'] = '../assets/img/portfolio/default-marketing.jpg';
                    break;
                case 'graphique':
                    $row['image_principale'] = '../assets/img/portfolio/default-graphique.jpg';
                    break;
                case 'multimedia':
                    $row['image_principale'] = '../assets/img/portfolio/default-multimedia.jpg';
                    break;
                case 'imprimerie':
                    $row['image_principale'] = '../assets/img/portfolio/default-imprimerie.jpg';
                    break;
                default:
                    $row['image_principale'] = '../assets/img/portfolio/default.jpg';
            }
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
        // Sécuriser les données
        $projet_id = isset($_POST['projet_id']) ? intval($_POST['projet_id']) : 0;
        
        // Action: Mettre en avant un projet
        if ($_POST['action'] === 'toggle_featured' && $projet_id > 0) {
            $featured = isset($_POST['featured']) ? (int)$_POST['featured'] : 0;
            
            $query = "UPDATE projets SET featured = ?, date_modification = NOW() WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $featured, $projet_id);
            
            if ($stmt->execute()) {
                $message = $featured ? "Le projet a été mis en avant avec succès." : "Le projet n'est plus mis en avant.";
                $message_type = "success";
                
                // Enregistrer dans les logs
                logActivity($_SESSION['admin_id'], 'toggle_featured', 'projets', $projet_id, "Featured: " . ($featured ? 'Oui' : 'Non'));
            } else {
                $message = "Erreur lors de la mise à jour du projet: " . $stmt->error;
                $message_type = "danger";
            }
            $stmt->close();
        }
        
        // Action: Supprimer un projet du portfolio
        if ($_POST['action'] === 'remove_from_portfolio' && $projet_id > 0) {
            $query = "UPDATE projets SET in_portfolio = 0, date_modification = NOW() WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $projet_id);
            
            if ($stmt->execute()) {
                $message = "Le projet a été retiré du portfolio avec succès.";
                $message_type = "success";
                
                // Enregistrer dans les logs
                logActivity($_SESSION['admin_id'], 'remove_from_portfolio', 'projets', $projet_id, "Retiré du portfolio");
            } else {
                $message = "Erreur lors de la suppression du projet du portfolio: " . $stmt->error;
                $message_type = "danger";
            }
            $stmt->close();
        }
    }
}

// Titre de la page
$page_title = "Gestion du Portfolio";
?>

<?php // Inclure l'en-tête et la barre latérale
require_once 'header.php';
require_once 'sidebar.php'; ?>

<main class="admin-main">
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-images mr-2"></i> Gestion du Portfolio
        </h1>
        <div>
            <a href="#" class="btn btn-success mr-2" data-toggle="modal" data-target="#exportPortfolioModal">
                <i class="fas fa-file-export"></i> Exporter
            </a>
            <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#addToPortfolioModal">
                <i class="fas fa-plus-circle"></i> Ajouter au Portfolio
            </a>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Projets dans le Portfolio</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_projets'] ?? 0; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-images fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Projets Terminés</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['projets_termines'] ?? 0; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Services Représentés</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['services_distincts'] ?? 0; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tags fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres et recherche -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filtrer les Projets du Portfolio</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="mb-4">
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Rechercher..." name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search fa-sm"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <select class="form-control" name="service" onchange="this.form.submit()">
                            <option value="">Tous les services</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo $service['slug']; ?>" <?php echo $service_filter === $service['slug'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($service['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select class="form-control" name="featured" onchange="this.form.submit()">
                            <option value="-1" <?php echo $featured_filter === -1 ? 'selected' : ''; ?>>Tous</option>
                            <option value="1" <?php echo $featured_filter === 1 ? 'selected' : ''; ?>>Mis en avant</option>
                            <option value="0" <?php echo $featured_filter === 0 ? 'selected' : ''; ?>>Standard</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="portfolio.php" class="btn btn-secondary btn-block">Réinitialiser</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Portfolio Grid -->
    <div class="row">
        <?php if (empty($projets)): ?>
            <div class="col-12">
                <div class="alert alert-info">
                    Aucun projet trouvé dans le portfolio avec les critères sélectionnés.
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($projets as $projet): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card portfolio-item shadow h-100">
                        <?php if ($projet['featured']): ?>
                            <div class="ribbon ribbon-top-right"><span>Mis en avant</span></div>
                        <?php endif; ?>
                        <div class="portfolio-img-container">
                            <img src="<?php echo htmlspecialchars($projet['image_principale']); ?>" class="card-img-top portfolio-img" alt="<?php echo htmlspecialchars($projet['nom']); ?>">
                        </div>
                        <div class="card-body">
                            <h5 class="card-title font-weight-bold"><?php echo htmlspecialchars($projet['nom']); ?></h5>
                            <p class="card-text text-muted mb-1">
                                <small>Client: <?php echo htmlspecialchars($projet['client_nom']); ?> 
                                <?php if (!empty($projet['entreprise'])): ?>
                                    (<?php echo htmlspecialchars($projet['entreprise']); ?>)
                                <?php endif; ?>
                                </small>
                            </p>
                            <p class="card-text">
                                <?php 
                                $service_name = '';
                                foreach ($services as $service) {
                                    if ($service['slug'] === $projet['service']) {
                                        $service_name = $service['nom'];
                                        break;
                                    }
                                }
                                ?>
                                <span class="badge badge-primary"><?php echo htmlspecialchars($service_name); ?></span>
                                <span class="badge badge-secondary"><?php echo $projet['date_fin_reelle'] ? date('d/m/Y', strtotime($projet['date_fin_reelle'])) : 'N/A'; ?></span>
                            </p>
                            <p class="card-text">
                                <?php echo substr(htmlspecialchars($projet['description']), 0, 100); ?>...
                            </p>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <div class="btn-group w-100">
                                <button type="button" class="btn btn-sm btn-primary" onclick="viewProject(<?php echo $projet['id']; ?>)">
                                    <i class="fas fa-eye"></i> Voir
                                </button>
                                <button type="button" class="btn btn-sm btn-<?php echo $projet['featured'] ? 'warning' : 'success'; ?>" onclick="toggleFeatured(<?php echo $projet['id']; ?>, <?php echo $projet['featured'] ? 0 : 1; ?>)">
                                    <i class="fas fa-<?php echo $projet['featured'] ? 'star' : 'star'; ?>"></i> 
                                    <?php echo $projet['featured'] ? 'Retirer' : 'Mettre en avant'; ?>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger" onclick="confirmRemoveFromPortfolio(<?php echo $projet['id']; ?>, '<?php echo addslashes($projet['nom']); ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="row mt-4">
            <div class="col-12">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&service=<?php echo urlencode($service_filter); ?>&featured=<?php echo $featured_filter; ?>" aria-label="Précédent">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&service=<?php echo urlencode($service_filter); ?>&featured=<?php echo $featured_filter; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&service=<?php echo urlencode($service_filter); ?>&featured=<?php echo $featured_filter; ?>" aria-label="Suivant">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Ajouter au Portfolio -->
<div class="modal fade" id="addToPortfolioModal" tabindex="-1" role="dialog" aria-labelledby="addToPortfolioModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addToPortfolioModalLabel">Ajouter un Projet au Portfolio</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <input type="text" class="form-control" id="searchProjects" placeholder="Rechercher un projet...">
                </div>
                <div id="projectsList" class="mt-3">
                    <div class="text-center">
                        <p>Recherchez un projet terminé pour l'ajouter au portfolio.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Exporter Portfolio -->
<div class="modal fade" id="exportPortfolioModal" tabindex="-1" role="dialog" aria-labelledby="exportPortfolioModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportPortfolioModalLabel">Exporter le Portfolio</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="exportForm" method="POST" action="ajax/export_portfolio.php">
                    <div class="form-group">
                        <label for="export_format">Format d'export</label>
                        <select class="form-control" id="export_format" name="format">
                            <option value="pdf">PDF</option>
                            <option value="excel">Excel</option>
                            <option value="json">JSON</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="export_service">Service (optionnel)</label>
                        <select class="form-control" id="export_service" name="service">
                            <option value="">Tous les services</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo $service['slug']; ?>">
                                    <?php echo htmlspecialchars($service['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="export_featured" name="featured" value="1">
                            <label class="custom-control-label" for="export_featured">Uniquement les projets mis en avant</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="export_images" name="include_images" value="1">
                            <label class="custom-control-label" for="export_images">Inclure les images (PDF uniquement)</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('exportForm').submit();">Exporter</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Voir Projet -->
<div class="modal fade" id="viewProjectModal" tabindex="-1" role="dialog" aria-labelledby="viewProjectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewProjectModalLabel">Détails du Projet</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="viewProjectContent">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Chargement...</span>
                    </div>
                    <p class="mt-2">Chargement des détails du projet...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                <a href="#" id="editProjectLink" class="btn btn-primary">Éditer</a>
            </div>
        </div>
    </div>
</div>
</main>


<!-- CSS personnalisé pour le portfolio -->
<style>
.portfolio-img-container {
    height: 200px;
    overflow: hidden;
    position: relative;
}

.portfolio-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.portfolio-item:hover .portfolio-img {
    transform: scale(1.05);
}

.ribbon {
    width: 150px;
    height: 150px;
    overflow: hidden;
    position: absolute;
    z-index: 1;
}

.ribbon-top-right {
    top: -10px;
    right: -10px;
}

.ribbon-top-right::before,
.ribbon-top-right::after {
    border-top-color: transparent;
    border-right-color: transparent;
}

.ribbon-top-right::before {
    top: 0;
    left: 0;
}

.ribbon-top-right::after {
    bottom: 0;
    right: 0;
}

.ribbon span {
    position: absolute;
    display: block;
    width: 225px;
    padding: 15px 0;
    background-color: #e74c3c;
    box-shadow: 0 5px 10px rgba(0,0,0,.1);
    color: #fff;
    font: 700 18px/1 'Lato', sans-serif;
    text-shadow: 0 1px 1px rgba(0,0,0,.2);
    text-transform: uppercase;
    text-align: center;
}

.ribbon-top-right span {
    right: -25px;
    top: 30px;
    transform: rotate(45deg);
}

.portfolio-item {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.portfolio-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
}
</style>

<!-- JavaScript pour les interactions -->
<script>
// Fonction pour voir les détails d'un projet
function viewProject(id) {
    const modal = $('#viewProjectModal');
    const content = $('#viewProjectContent');
    
    // Afficher le modal avec un spinner de chargement
    modal.modal('show');
    content.html('<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="sr-only">Chargement...</span></div><p class="mt-2">Chargement des détails du projet...</p></div>');
    
    // Mettre à jour le lien d'édition
    $('#editProjectLink').attr('href', 'edit_projet.php?id=' + id);
    
    // Charger les détails du projet via AJAX
    $.ajax({
        url: 'ajax/get_projet_details.php',
        type: 'GET',
        data: { id: id },
        success: function(response) {
            content.html(response);
        },
        error: function() {
            content.html('<div class="alert alert-danger">Erreur lors du chargement des détails du projet.</div>');
        }
    });
}

// Fonction pour mettre en avant ou retirer un projet
function toggleFeatured(id, featured) {
    const form = $('<form method="POST" action="">' +
        '<input type="hidden" name="action" value="toggle_featured">' +
        '<input type="hidden" name="projet_id" value="' + id + '">' +
        '<input type="hidden" name="featured" value="' + featured + '">' +
        '</form>');
    $('body').append(form);
    form.submit();
}

// Fonction pour confirmer la suppression d'un projet du portfolio
function confirmRemoveFromPortfolio(id, nom) {
    if (confirm('Êtes-vous sûr de vouloir retirer le projet "' + nom + '" du portfolio ?')) {
        const form = $('<form method="POST" action="">' +
            '<input type="hidden" name="action" value="remove_from_portfolio">' +
            '<input type="hidden" name="projet_id" value="' + id + '">' +
            '</form>');
        $('body').append(form);
        form.submit();
    }
}

// Recherche de projets pour ajout au portfolio
$('#searchProjects').on('input', function() {
    const search = $(this).val();
    if (search.length >= 2) {
        $.ajax({
            url: 'ajax/search_projects_for_portfolio.php',
            type: 'GET',
            data: { search: search },
            success: function(response) {
                $('#projectsList').html(response);
            },
            error: function() {
                $('#projectsList').html('<div class="alert alert-danger">Erreur lors de la recherche.</div>');
            }
        });
    } else {
        $('#projectsList').html('<div class="text-center"><p>Recherchez un projet terminé pour l\'ajouter au portfolio.</p></div>');
    }
});

// Fonction pour ajouter un projet au portfolio
function addToPortfolio(id) {
    $.ajax({
        url: 'ajax/add_to_portfolio.php',
        type: 'POST',
        data: { projet_id: id },
        success: function(response) {
            const result = JSON.parse(response);
            if (result.success) {
                alert('Projet ajouté au portfolio avec succès.');
                $('#addToPortfolioModal').modal('hide');
                location.reload();
            } else {
                alert('Erreur: ' + result.message);
            }
        },
        error: function() {
            alert('Erreur lors de l\'ajout du projet au portfolio.');
        }
    });
}
</script>

<?php
// Fermer la connexion à la base de données
$conn->close();
?>

