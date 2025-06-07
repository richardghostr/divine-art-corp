<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Vérification de l'authentification
$auth = new Auth();
$auth->requireAuth();

$currentAdmin = $auth->getCurrentUser();
$page_title = "Conception Multimédia";

// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_video':
            $type = $_POST['video_type'];
            $title = $_POST['video_title'];
            $client_id = $_POST['client_id'];
            $budget = $_POST['budget'];
            $description = $_POST['description'];
            $duration = $_POST['duration'];
            $format = $_POST['format'];
            $deadline = $_POST['deadline'];
            
            $query = "INSERT INTO projets_multimedia (type, titre, client_id, budget, description, duree, format, deadline, statut, date_creation) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'nouveau', NOW())";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ssidsdss", $type, $title, $client_id, $budget, $description, $duration, $format, $deadline);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Projet multimédia créé avec succès";
            } else {
                $error_message = "Erreur lors de la création du projet";
            }
            break;
            
        case 'update_video_status':
            $video_id = $_POST['video_id'];
            $new_status = $_POST['new_status'];
            
            $query = "UPDATE projets_multimedia SET statut = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "si", $new_status, $video_id);
            
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['success' => true]);
                exit;
            }
            break;
    }
}

// Récupération des statistiques
$stats_query = "SELECT 
    COUNT(*) as total_projets,
    SUM(CASE WHEN statut = 'production' THEN 1 ELSE 0 END) as projets_production,
    SUM(CASE WHEN statut = 'termine' THEN 1 ELSE 0 END) as projets_termines,
    SUM(budget_final) as budget_total,
    SUM(duree_prevue) as duree_totale
FROM projets_multimedia";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Récupération des projets multimédia
$filter_status = $_GET['status'] ?? '';
$filter_type = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';

$where_conditions = ["1=1"];
$params = [];
$types = "";

if ($filter_status) {
    $where_conditions[] = "pm.statut = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if ($filter_type) {
    $where_conditions[] = "pm.type_service = ?";
    $params[] = $filter_type;
    $types .= "s";
}

if ($search) {
    $where_conditions[] = "(pm.titre LIKE ? OR c.nom LIKE ? OR c.entreprise LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$where_clause = implode(" AND ", $where_conditions);

$multimedia_query = "SELECT pm.*, c.nom as client_nom, c.entreprise, c.email
                    FROM projets_multimedia pm
                    LEFT JOIN clients c ON pm.client_id = c.id
                    WHERE $where_clause
                    ORDER BY pm.date_creation DESC";

if ($params) {
    $stmt = mysqli_prepare($conn, $multimedia_query);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $multimedia_result = mysqli_stmt_get_result($stmt);
} else {
    $multimedia_result = mysqli_query($conn, $multimedia_query);
}

// Récupération des clients pour le formulaire
$clients_query = "SELECT id, nom, entreprise, email FROM clients ORDER BY nom";
$clients_result = mysqli_query($conn, $clients_query);

include 'header.php';
?>

<div class="admin-main">
    <?php include 'sidebar.php'; ?>
    
    <main class="main-content">
        <!-- Header -->
        <div class="content-header">
            <div class="header-left">
                <h1><i class="fas fa-video"></i> <?php echo $page_title; ?></h1>
                <p>Gérez vos projets multimédia et vidéo</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="showVideoModal()">
                    <i class="fas fa-plus"></i> Nouveau Projet
                </button>
                <button class="btn btn-secondary" onclick="showMediaLibrary()">
                    <i class="fas fa-folder"></i> Médiathèque
                </button>
            </div>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon bg-blue">
                    <i class="fas fa-video"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['total_projets'] ?? 0); ?></h3>
                    <p>Total Projets</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-orange">
                    <i class="fas fa-play-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['projets_production'] ?? 0); ?></h3>
                    <p>En Production</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-green">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['projets_termines'] ?? 0); ?></h3>
                    <p>Projets Terminés</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-purple">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['duree_totale'] ?? 0, 1); ?> min</h3>
                    <p>Durée Totale</p>
                </div>
            </div>
        </div>

        <!-- Services Multimédia -->
        <div class="services-overview">
            <h3><i class="fas fa-cogs"></i> Services Multimédia</h3>
            <div class="services-grid">
                <div class="service-card" onclick="createVideo('video-promotionnelle')">
                    <div class="service-icon">
                        <i class="fas fa-video"></i>
                    </div>
                    <h4>Vidéo Promotionnelle</h4>
                    <p>Vidéos marketing et commerciales</p>
                </div>
                
                <div class="service-card" onclick="createVideo('animation-2d-3d')">
                    <div class="service-icon">
                        <i class="fas fa-magic"></i>
                    </div>
                    <h4>Animation 2D/3D</h4>
                    <p>Animations et motion design</p>
                </div>
                
                <div class="service-card" onclick="createVideo('montage-video')">
                    <div class="service-icon">
                        <i class="fas fa-cut"></i>
                    </div>
                    <h4>Montage Vidéo</h4>
                    <p>Post-production professionnelle</p>
                </div>
                
                <div class="service-card" onclick="createVideo('spot-publicitaire')">
                    <div class="service-icon">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <h4>Spot Publicitaire</h4>
                    <p>Publicités TV et web</p>
                </div>
                
                <div class="service-card" onclick="createVideo('video-corporate')">
                    <div class="service-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <h4>Vidéo Corporate</h4>
                    <p>Présentation d'entreprise</p>
                </div>
                
                <div class="service-card" onclick="createVideo('motion-design')">
                    <div class="service-icon">
                        <i class="fas fa-play"></i>
                    </div>
                    <h4>Motion Design</h4>
                    <p>Graphismes animés</p>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="filters-bar">
            <div class="filter-tabs">
                <button class="filter-tab <?php echo !$filter_status ? 'active' : ''; ?>" 
                        onclick="filterVideos('')">Tous</button>
                <button class="filter-tab <?php echo $filter_status === 'nouveau' ? 'active' : ''; ?>" 
                        onclick="filterVideos('nouveau')">Nouveaux</button>
                <button class="filter-tab <?php echo $filter_status === 'pre_production' ? 'active' : ''; ?>" 
                        onclick="filterVideos('pre_production')">Pré-production</button>
                <button class="filter-tab <?php echo $filter_status === 'production' ? 'active' : ''; ?>" 
                        onclick="filterVideos('production')">Production</button>
                <button class="filter-tab <?php echo $filter_status === 'post_production' ? 'active' : ''; ?>" 
                        onclick="filterVideos('post_production')">Post-production</button>
                <button class="filter-tab <?php echo $filter_status === 'termine' ? 'active' : ''; ?>" 
                        onclick="filterVideos('termine')">Terminés</button>
            </div>
            
            <div class="filters-controls">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Rechercher un projet..." 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           onkeyup="searchVideos(this.value)" >
                </div>
                <select onchange="filterByType(this.value)">
                    <option value="">Tous les types</option>
                    <option value="video-promotionnelle" <?php echo $filter_type === 'video-promotionnelle' ? 'selected' : ''; ?>>Vidéo Promotionnelle</option>
                    <option value="animation-2d-3d" <?php echo $filter_type === 'animation-2d-3d' ? 'selected' : ''; ?>>Animation 2D/3D</option>
                    <option value="montage-video" <?php echo $filter_type === 'montage-video' ? 'selected' : ''; ?>>Montage Vidéo</option>
                    <option value="spot-publicitaire" <?php echo $filter_type === 'spot-publicitaire' ? 'selected' : ''; ?>>Spot Publicitaire</option>
                    <option value="video-corporate" <?php echo $filter_type === 'video-corporate' ? 'selected' : ''; ?>>Vidéo Corporate</option>
                    <option value="motion-design" <?php echo $filter_type === 'motion-design' ? 'selected' : ''; ?>>Motion Design</option>
                </select>
            </div>
        </div>

        <!-- Liste des projets -->
        <div class="videos-grid">
            <?php while ($video = mysqli_fetch_assoc($multimedia_result)): ?>
                <div class="video-card">
                    <div class="video-preview">
                        <div class="preview-placeholder">
                            <?php
                            $type_icons = [
                                'video-promotionnelle' => 'fas fa-video',
                                'animation-2d-3d' => 'fas fa-magic',
                                'montage-video' => 'fas fa-cut',
                                'spot-publicitaire' => 'fas fa-bullhorn',
                                'video-corporate' => 'fas fa-building',
                                'motion-design' => 'fas fa-play'
                            ];
                            $icon = $type_icons[$video['type']] ?? 'fas fa-video';
                            ?>
                            <i class="<?php echo $icon; ?>"></i>
                            <span>Aperçu vidéo</span>
                        </div>
                        <div class="video-overlay">
                            <button class="btn btn-sm btn-primary" onclick="previewVideo(<?php echo $video['id']; ?>)">
                                <i class="fas fa-play"></i> Aperçu
                            </button>
                        </div>
                        <div class="video-duration">
                            <?php if ($video['duree']): ?>
                                <span><i class="fas fa-clock"></i> <?php echo $video['duree']; ?> min</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="video-content">
                        <div class="video-header">
                            <div class="video-type">
                                <i class="<?php echo $icon; ?>"></i>
                                <span><?php echo ucfirst(str_replace('-', ' ', $video['type'])); ?></span>
                            </div>
                            <div class="video-status">
                                <span class="status-badge status-<?php echo $video['statut']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $video['statut'])); ?>
                                </span>
                            </div>
                        </div>

                        <h4><?php echo htmlspecialchars($video['titre']); ?></h4>
                        <p class="video-client">
                            <i class="fas fa-user"></i>
                            <?php echo htmlspecialchars($video['client_nom'] . ' - ' . ($video['entreprise'] ?: $video['email'])); ?>
                        </p>

                        <div class="video-details">
                            <div class="detail-item">
                                <span class="label">Budget:</span>
                                <span class="value"><?php echo $video['budget'] ? number_format($video['budget'], 0, ',', ' ') . ' FCFA' : 'Non défini'; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Format:</span>
                                <span class="value"><?php echo strtoupper($video['format'] ?: 'MP4'); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Deadline:</span>
                                <span class="value"><?php echo $video['deadline'] ? date('d/m/Y', strtotime($video['deadline'])) : 'Non définie'; ?></span>
                            </div>
                        </div>

                        <div class="video-description">
                            <p><?php echo htmlspecialchars(substr($video['description'], 0, 100)) . (strlen($video['description']) > 100 ? '...' : ''); ?></p>
                        </div>

                        <div class="video-actions">
                            <button class="btn btn-sm btn-primary" onclick="viewVideo(<?php echo $video['id']; ?>)">
                                <i class="fas fa-eye"></i> Voir
                            </button>
                            <button class="btn btn-sm btn-info" onclick="editVideo(<?php echo $video['id']; ?>)">
                                <i class="fas fa-edit"></i> Modifier
                            </button>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-secondary dropdown-toggle">
                                    <i class="fas fa-cog"></i> Actions
                                </button>
                                <div class="dropdown-menu">
                                    <a href="#" onclick="updateVideoStatus(<?php echo $video['id']; ?>, 'pre_production')">
                                        <i class="fas fa-clipboard-list"></i> Pré-production
                                    </a>
                                    <a href="#" onclick="updateVideoStatus(<?php echo $video['id']; ?>, 'production')">
                                        <i class="fas fa-video"></i> Production
                                    </a>
                                    <a href="#" onclick="updateVideoStatus(<?php echo $video['id']; ?>, 'post_production')">
                                        <i class="fas fa-cut"></i> Post-production
                                    </a>
                                    <a href="#" onclick="updateVideoStatus(<?php echo $video['id']; ?>, 'termine')">
                                        <i class="fas fa-check"></i> Terminer
                                    </a>
                                    <a href="#" onclick="downloadVideo(<?php echo $video['id']; ?>)">
                                        <i class="fas fa-download"></i> Télécharger
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </main>
</div>

<!-- Modal Nouveau Projet Vidéo -->
<div id="videoModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Nouveau Projet Multimédia</h2>
            <span class="close" onclick="closeVideoModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="create_video">
                
                <div class="form-group">
                    <label for="video_type">Type de projet</label>
                    <select name="video_type" id="video_type" required>
                        <option value="">Sélectionner un type</option>
                        <option value="video-promotionnelle">Vidéo Promotionnelle</option>
                        <option value="animation-2d-3d">Animation 2D/3D</option>
                        <option value="montage-video">Montage Vidéo</option>
                        <option value="spot-publicitaire">Spot Publicitaire</option>
                        <option value="video-corporate">Vidéo Corporate</option>
                        <option value="motion-design">Motion Design</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="video_title">Titre du projet</label>
                    <input type="text" name="video_title" id="video_title" required>
                </div>

                <div class="form-group">
                    <label for="client_id">Client</label>
                    <select name="client_id" id="client_id" required>
                        <option value="">Sélectionner un client</option>
                        <?php while ($client = mysqli_fetch_assoc($clients_result)): ?>
                            <option value="<?php echo $client['id']; ?>">
                                <?php echo htmlspecialchars($client['nom'] . ' - ' . ($client['entreprise'] ?: $client['email'])); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="budget">Budget (FCFA)</label>
                        <input type="number" name="budget" id="budget" min="0" step="1000">
                    </div>
                    <div class="form-group">
                        <label for="duration">Durée (minutes)</label>
                        <input type="number" name="duration" id="duration" min="0" step="0.5">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="format">Format de sortie</label>
                        <select name="format" id="format">
                            <option value="mp4">MP4 (Standard)</option>
                            <option value="mov">MOV (Haute qualité)</option>
                            <option value="avi">AVI</option>
                            <option value="web">Optimisé Web</option>
                            <option value="social">Réseaux Sociaux</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="deadline">Date limite</label>
                        <input type="date" name="deadline" id="deadline">
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description du projet</label>
                    <textarea name="description" id="description" rows="4" 
                              placeholder="Décrivez le concept, l'objectif, le style souhaité..."></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Créer Projet
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeVideoModal()">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showVideoModal() {
    document.getElementById('videoModal').style.display = 'block';
}

function closeVideoModal() {
    document.getElementById('videoModal').style.display = 'none';
}

function createVideo(type) {
    document.getElementById('video_type').value = type;
    showVideoModal();
}

function filterVideos(status) {
    const url = new URL(window.location);
    if (status) {
        url.searchParams.set('status', status);
    } else {
        url.searchParams.delete('status');
    }
    window.location.href = url.toString();
}

function filterByType(type) {
    const url = new URL(window.location);
    if (type) {
        url.searchParams.set('type', type);
    } else {
        url.searchParams.delete('type');
    }
    window.location.href = url.toString();
}

function searchVideos(query) {
    const url = new URL(window.location);
    if (query) {
        url.searchParams.set('search', query);
    } else {
        url.searchParams.delete('search');
    }
    
    clearTimeout(window.searchTimeout);
    window.searchTimeout = setTimeout(() => {
        window.location.href = url.toString();
    }, 500);
}

function updateVideoStatus(videoId, newStatus) {
    if (confirm('Confirmer le changement de statut ?')) {
        fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=update_video_status&video_id=${videoId}&new_status=${newStatus}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur lors de la mise à jour');
            }
        });
    }
}

function viewVideo(id) {
    window.location.href = `video_details.php?id=${id}`;
}

function editVideo(id) {
    window.location.href = `edit_video.php?id=${id}`;
}

function previewVideo(id) {
    window.open(`preview_video.php?id=${id}`, '_blank', 'width=800,height=600');
}

function downloadVideo(id) {
    window.location.href = `../api/download_video.php?id=${id}`;
}

function showMediaLibrary() {
    window.location.href = 'media_library.php';
}

// Gestion des dropdowns
document.addEventListener('click', function(e) {
    if (!e.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.style.display = 'none';
        });
    }
});

document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
    toggle.addEventListener('click', function(e) {
        e.preventDefault();
        const menu = this.nextElementSibling;
        menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
    });
});
</script>

<style>
/* Video Grid */
.videos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: var(--admin-space-xl);
}

.video-card {
    background: var(--admin-card-bg);
    border-radius: var(--admin-radius-xl);
    box-shadow: var(--admin-shadow-sm);
    overflow: hidden;
    transition: var(--admin-transition);
}

.video-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--admin-shadow-md);
}

.video-preview {
    height: 200px;
    background: linear-gradient(135deg, #1a2a6c 0%, #b21f1f 50%, #1a2a6c 100%);
    background-size: 200% 200%;
    animation: gradientBG 10s ease infinite;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

@keyframes gradientBG {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

.preview-placeholder {
    text-align: center;
    color: rgba(255, 255, 255, 0.8);
}

.preview-placeholder i {
    font-size: 3rem;
    margin-bottom: var(--admin-space-sm);
    display: block;
}

.video-overlay {
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
    transition: var(--admin-transition);
}

.video-card:hover .video-overlay {
    opacity: 1;
}

.video-duration {
    position: absolute;
    bottom: var(--admin-space-sm);
    right: var(--admin-space-sm);
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: var(--admin-radius-sm);
    font-size: 0.75rem;
}

.video-content {
    padding: var(--admin-space-lg);
}

.video-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--admin-space-md);
}

.video-type {
    display: flex;
    align-items: center;
    gap: var(--admin-space-sm);
    font-size: 0.875rem;
    color: var(--admin-text-secondary);
}

.video-type i {
    color: #e74c3c;
}

.video-content h4 {
    margin-bottom: var(--admin-space-sm);
    color: var(--admin-text-primary);
}

.video-client {
    color: var(--admin-text-secondary);
    font-size: 0.875rem;
    margin-bottom: var(--admin-space-md);
    display: flex;
    align-items: center;
    gap: var(--admin-space-xs);
}

.video-details {
    margin-bottom: var(--admin-space-md);
}

.video-description {
    margin-bottom: var(--admin-space-lg);
    padding: var(--admin-space-md);
    background: var(--admin-border-light);
    border-radius: var(--admin-radius-md);
    font-size: 0.875rem;
    color: var(--admin-text-secondary);
}

.video-actions {
    display: flex;
    gap: var(--admin-space-sm);
    align-items: center;
    flex-wrap: wrap;
}

/* Status badges */
.status-nouveau {
    background: rgba(52, 152, 219, 0.1);
    color: var(--admin-info);
}

.status-pre_production {
    background: rgba(243, 156, 18, 0.1);
    color: var(--admin-warning);
}

.status-production {
    background: rgba(231, 76, 60, 0.1);
    color: var(--admin-accent);
}

.status-post_production {
    background: rgba(155, 89, 182, 0.1);
    color: #9b59b6;
}

.status-termine {
    background: rgba(39, 174, 96, 0.1);
    color: var(--admin-success);
}

/* Form styles */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--admin-space-lg);
}

/* Services grid specific for multimedia */
.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--admin-space-lg);
}

.service-card .service-icon {
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
}

/* Responsive */
@media (max-width: 768px) {
    .videos-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .services-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .video-actions {
        flex-direction: column;
        align-items: stretch;
    }
}

@media (max-width: 480px) {
    .services-grid {
        grid-template-columns: 1fr;
    }
}

.filters-controls {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: var(--admin-space-lg);
    gap: var(--admin-space-md);
    width: 100%;
}
.search-box {
    position: relative;
    margin-right: var(--admin-space-lg);
}
.search-box i {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--admin-text-secondary);
}
.search-box input {
    padding-left: 30px;
    width: 250px;
    height: 40px;
    border-radius: var(--admin-radius-md);
    border: 1px solid var(--admin-border-light);
    font-size: 0.875rem;
}
</style>


