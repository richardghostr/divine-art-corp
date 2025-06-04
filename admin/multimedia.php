<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Vérification de l'authentification
$auth = new Auth();
$auth->requireAuth();


// Récupération de l'admin connecté
$currentAdmin = $auth->getCurrentUser();
$page_title = "Conception Multimédia";

// Récupérer les projets multimédia
$multimedia_query = "SELECT d.*, p.id as projet_id, p.statut as projet_statut, p.progression,
                            c.nom as client_nom, c.entreprise
                     FROM devis d 
                     LEFT JOIN projets p ON d.id = p.devis_id
                     LEFT JOIN clients c ON d.email = c.email
                     WHERE d.service = 'multimedia' 
                     ORDER BY d.date_creation DESC";
$multimedia_result = mysqli_query($conn, $multimedia_query);

// Statistiques multimédia
$stats_query = "SELECT 
    COUNT(*) as total_projets,
    SUM(CASE WHEN d.statut = 'termine' THEN 1 ELSE 0 END) as projets_termines,
    SUM(CASE WHEN d.statut = 'en_cours' THEN 1 ELSE 0 END) as projets_en_cours,
    SUM(d.montant_final) as ca_total,
    AVG(d.montant_final) as montant_moyen
FROM devis d WHERE d.service = 'multimedia'";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

include 'header.php';
?>

<div class="admin-main">
    <?php include 'sidebar.php'; ?>
    
    <main class="main-content">
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

        <!-- Statistiques Multimédia -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon bg-blue">
                    <i class="fas fa-video"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['total_projets'] ??0); ?></h3>
                    <p>Projets Multimédia</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-green">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['projets_termines'] ??0); ?></h3>
                    <p>Projets Terminés</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-orange">
                    <i class="fas fa-play-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['projets_en_cours'] ??0); ?></h3>
                    <p>En Production</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-purple">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-content">

                    <h3><?php  echo isset($stats['ca_total']) ? number_format($stats['ca_total'], 0, ',', ' '): '0.0';  ?> FCFA</h3>
                    <p>Chiffre d'Affaires</p>
                </div>
            </div>
        </div>

        <!-- Services Multimédia -->
        <div class="services-overview">
            <h3><i class="fas fa-cogs"></i> Services Multimédia</h3>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-video"></i>
                    </div>
                    <h4>Vidéo Promotionnelle</h4>
                    <p>Création de vidéos marketing et promotionnelles</p>
                    <button class="btn btn-sm btn-primary" onclick="createVideoProject('promotional')">
                        Créer
                    </button>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-magic"></i>
                    </div>
                    <h4>Animation 2D/3D</h4>
                    <p>Animations graphiques et motion design</p>
                    <button class="btn btn-sm btn-primary" onclick="createVideoProject('animation')">
                        Créer
                    </button>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-cut"></i>
                    </div>
                    <h4>Montage Vidéo</h4>
                    <p>Post-production et montage professionnel</p>
                    <button class="btn btn-sm btn-primary" onclick="createVideoProject('editing')">
                        Créer
                    </button>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-microphone"></i>
                    </div>
                    <h4>Production Audio</h4>
                    <p>Enregistrement et mixage audio</p>
                    <button class="btn btn-sm btn-primary" onclick="createVideoProject('audio')">
                        Créer
                    </button>
                </div>
            </div>
        </div>

        <!-- Projets en cours -->
        <div class="current-projects">
            <h3><i class="fas fa-play"></i> Projets en Production</h3>
            <div class="projects-timeline">
                <?php 
                mysqli_data_seek($multimedia_result, 0);
                while ($projet = mysqli_fetch_assoc($multimedia_result)): 
                    if ($projet['statut'] === 'en_cours'):
                ?>
                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <div class="project-info">
                                <h4><?php echo htmlspecialchars($projet['client_nom'] ?: $projet['nom']); ?></h4>
                                <p><?php echo htmlspecialchars($projet['sous_service'] ?: 'Projet Multimédia'); ?></p>
                                <span class="project-date"><?php echo date('d/m/Y', strtotime($projet['date_creation'])); ?></span>
                            </div>
                            <div class="project-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $projet['progression']; ?>%"></div>
                                </div>
                                <span><?php echo $projet['progression']; ?>%</span>
                            </div>
                            <div class="project-actions">
                                <button class="btn btn-sm btn-info" onclick="manageProject(<?php echo $projet['projet_id']; ?>)">
                                    <i class="fas fa-cogs"></i> Gérer
                                </button>
                            </div>
                        </div>
                    </div>
                <?php 
                    endif;
                endwhile; 
                ?>
            </div>
        </div>

        <!-- Liste complète des projets -->
        <div class="projects-section">
            <div class="section-header">
                <h2><i class="fas fa-project-diagram"></i> Tous les Projets Multimédia</h2>
                <div class="section-filters">
                    <select id="statusFilter" onchange="filterProjects()">
                        <option value="">Tous les statuts</option>
                        <option value="nouveau">Nouveau</option>
                        <option value="en_cours">En cours</option>
                        <option value="termine">Terminé</option>
                        <option value="annule">Annulé</option>
                    </select>
                </div>
            </div>

            <div class="projects-grid">
                <?php 
                mysqli_data_seek($multimedia_result, 0);
                while ($projet = mysqli_fetch_assoc($multimedia_result)): 
                ?>
                    <div class="project-card multimedia-card" data-status="<?php echo $projet['statut']; ?>">
                        <div class="project-header">
                            <div class="project-title">
                                <h4><?php echo htmlspecialchars($projet['client_nom'] ?: $projet['nom']); ?></h4>
                                <span class="project-type"><?php echo htmlspecialchars($projet['sous_service'] ?: 'Multimédia'); ?></span>
                            </div>
                            <div class="project-status">
                                <span class="status-badge status-<?php echo $projet['statut']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $projet['statut'])); ?>
                                </span>
                            </div>
                        </div>

                        <div class="project-preview">
                            <div class="video-placeholder">
                                <i class="fas fa-play-circle"></i>
                                <span>Aperçu vidéo</span>
                            </div>
                        </div>

                        <div class="project-details">
                            <div class="detail-row">
                                <span class="label">Client:</span>
                                <span class="value"><?php echo htmlspecialchars($projet['entreprise'] ?: $projet['nom']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Budget:</span>
                                <span class="value"><?php echo $projet['montant_final'] ? number_format($projet['montant_final'], 0, ',', ' ') . ' FCFA' : 'Non défini'; ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Date:</span>
                                <span class="value"><?php echo date('d/m/Y', strtotime($projet['date_creation'])); ?></span>
                            </div>
                            <?php if ($projet['projet_id']): ?>
                                <div class="detail-row">
                                    <span class="label">Progression:</span>
                                    <div class="progress-container">
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $projet['progression']; ?>%"></div>
                                        </div>
                                        <span class="progress-text"><?php echo $projet['progression']; ?>%</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="project-description">
                            <p><?php echo htmlspecialchars(substr($projet['description'], 0, 100)) . (strlen($projet['description']) > 100 ? '...' : ''); ?></p>
                        </div>

                        <div class="project-actions">
                            <button class="btn btn-sm btn-primary" onclick="viewProject(<?php echo $projet['id']; ?>)">
                                <i class="fas fa-eye"></i> Voir
                            </button>
                            <?php if ($projet['projet_id']): ?>
                                <button class="btn btn-sm btn-info" onclick="editProject(<?php echo $projet['projet_id']; ?>)">
                                    <i class="fas fa-edit"></i> Modifier
                                </button>
                            <?php else: ?>
                                <button class="btn btn-sm btn-success" onclick="startProduction(<?php echo $projet['id']; ?>)">
                                    <i class="fas fa-play"></i> Démarrer
                                </button>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-secondary" onclick="previewMedia(<?php echo $projet['id']; ?>)">
                                <i class="fas fa-play-circle"></i> Aperçu
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </main>
</div>

<!-- Modal pour nouveau projet vidéo -->
<div id="videoModal" class="modal">
    <div class="modal-content large">
        <div class="modal-header">
            <h2>Nouveau Projet Multimédia</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form id="videoForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="videoType">Type de projet</label>
                        <select id="videoType" required>
                            <option value="">Sélectionner un type</option>
                            <option value="video-promotionnelle">Vidéo Promotionnelle</option>
                            <option value="animation-2d-3d">Animation 2D/3D</option>
                            <option value="montage-video">Montage Vidéo</option>
                            <option value="motion-design">Motion Design</option>
                            <option value="video-corporate">Vidéo Corporate</option>
                            <option value="spot-publicitaire">Spot Publicitaire</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="videoClient">Client</label>
                        <select id="videoClient" required>
                            <option value="">Sélectionner un client</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="videoTitle">Titre du projet</label>
                    <input type="text" id="videoTitle" required>
                </div>
                
                <div class="form-group">
                    <label for="videoDescription">Description du projet</label>
                    <textarea id="videoDescription" rows="4" placeholder="Décrivez le concept, l'objectif, le style souhaité..."></textarea>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="videoDuration">Durée estimée (minutes)</label>
                        <input type="number" id="videoDuration" min="0" step="0.5">
                    </div>
                    
                    <div class="form-group">
                        <label for="videoFormat">Format de sortie</label>
                        <select id="videoFormat">
                            <option value="mp4">MP4 (Standard)</option>
                            <option value="mov">MOV (Haute qualité)</option>
                            <option value="avi">AVI</option>
                            <option value="web">Optimisé Web</option>
                            <option value="social">Réseaux Sociaux</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="videoBudget">Budget (FCFA)</label>
                        <input type="number" id="videoBudget" min="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="videoDeadline">Date limite</label>
                        <input type="date" id="videoDeadline">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="videoSpecs">Spécifications techniques</label>
                    <textarea id="videoSpecs" rows="3" placeholder="Résolution, codec, contraintes techniques..."></textarea>
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
    loadClients();
}

function closeVideoModal() {
    document.getElementById('videoModal').style.display = 'none';
}

function loadClients() {
    fetch('../api/get_clients_list.php')
        .then(response => response.json())
        .then(clients => {
            const select = document.getElementById('videoClient');
            select.innerHTML = '<option value="">Sélectionner un client</option>';
            clients.forEach(client => {
                select.innerHTML += `<option value="${client.id}">${client.nom} - ${client.entreprise || client.email}</option>`;
            });
        });
}

function createVideoProject(type) {
    const typeMap = {
        'promotional': 'video-promotionnelle',
        'animation': 'animation-2d-3d',
        'editing': 'montage-video',
        'audio': 'production-audio'
    };
    
    document.getElementById('videoType').value = typeMap[type] || type;
    showVideoModal();
}

function filterProjects() {
    const filter = document.getElementById('statusFilter').value;
    const cards = document.querySelectorAll('.project-card');
    
    cards.forEach(card => {
        if (filter === '' || card.dataset.status === filter) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function viewProject(id) {
    window.location.href = `multimedia_details.php?id=${id}`;
}

function editProject(id) {
    window.location.href = `edit_multimedia.php?id=${id}`;
}

function startProduction(devisId) {
    if (confirm('Démarrer la production de ce projet ?')) {
        fetch('../api/start_multimedia_project.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({devis_id: devisId})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur lors du démarrage de la production');
            }
        });
    }
}

function previewMedia(id) {
    // Ouvrir un modal de prévisualisation
    window.open(`preview_media.php?id=${id}`, '_blank', 'width=800,height=600');
}

function manageProject(id) {
    window.location.href = `projets.php?id=${id}`;
}

function showMediaLibrary() {
    window.location.href = `media_library.php`;
}

// Gestion des modals
document.querySelector('.close').onclick = closeVideoModal;

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>


<style>
    /* ========================================
   CONCEPTION MULTIMEDIA - STYLES SPECIFIQUES
   Styles pour la page Multimedia
   ======================================== */

/* Contenu principal */
.admin-content {
    display: flex;
    min-height: 100vh;
}



/* Header du contenu */
.content-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--admin-space-2xl);
    padding-bottom: var(--admin-space-lg);
    border-bottom: 1px solid var(--admin-border);
}

.header-left h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--admin-text-primary);
    margin-bottom: var(--admin-space-xs);
    display: flex;
    align-items: center;
    gap: var(--admin-space-sm);
}

.header-left p {
    color: var(--admin-text-secondary);
    font-size: 1rem;
}

.header-actions {
    display: flex;
    gap: var(--admin-space-md);
}

/* Grille de statistiques */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--admin-space-lg);
    margin-bottom: var(--admin-space-2xl);
}

.stat-card {
    background: var(--admin-card-bg);
    padding: var(--admin-space-xl);
    border-radius: var(--admin-radius-xl);
    box-shadow: var(--admin-shadow-sm);
    display: flex;
    align-items: center;
    gap: var(--admin-space-lg);
    transition: var(--admin-transition);
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--admin-shadow-md);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: var(--admin-radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
}

.stat-icon.bg-blue {
    background: linear-gradient(135deg, var(--admin-info) 0%, #2980b9 100%);
}

.stat-icon.bg-green {
    background: linear-gradient(135deg, var(--admin-success) 0%, #229954 100%);
}

.stat-icon.bg-orange {
    background: linear-gradient(135deg, var(--admin-warning) 0%, #d35400 100%);
}

.stat-icon.bg-purple {
    background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
}

.stat-content h3 {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--admin-text-primary);
    margin-bottom: var(--admin-space-xs);
}

.stat-content p {
    color: var(--admin-text-secondary);
    font-size: 0.875rem;
}

/* Vue d'ensemble des services */
.services-overview {
    margin-bottom: var(--admin-space-2xl);
}

.services-overview h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--admin-text-primary);
    margin-bottom: var(--admin-space-lg);
    display: flex;
    align-items: center;
    gap: var(--admin-space-sm);
}

.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: var(--admin-space-lg);
}

.service-card {
    background: var(--admin-card-bg);
    border-radius: var(--admin-radius-xl);
    box-shadow: var(--admin-shadow-sm);
    padding: var(--admin-space-xl);
    text-align: center;
    transition: var(--admin-transition);
    display: flex;
    flex-direction: column;
    align-items: center;
}

.service-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--admin-shadow-md);
}

.service-icon {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    color: white;
    margin-bottom: var(--admin-space-lg);
    background: linear-gradient(135deg, var(--admin-accent) 0%, #c0392b 100%);
}

.service-card h4 {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--admin-text-primary);
    margin-bottom: var(--admin-space-sm);
}

.service-card p {
    color: var(--admin-text-secondary);
    font-size: 0.875rem;
    margin-bottom: var(--admin-space-lg);
    flex-grow: 1;
}

/* Projets en cours */
.current-projects {
    margin-bottom: var(--admin-space-2xl);
}

.current-projects h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--admin-text-primary);
    margin-bottom: var(--admin-space-lg);
    display: flex;
    align-items: center;
    gap: var(--admin-space-sm);
}

.projects-timeline {
    position: relative;
    padding-left: var(--admin-space-xl);
}

.projects-timeline::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: var(--admin-border);
}

.timeline-item {
    position: relative;
    margin-bottom: var(--admin-space-xl);
}

.timeline-marker {
    position: absolute;
    left: -20px;
    top: 10px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--admin-accent);
    border: 3px solid var(--admin-card-bg);
    z-index: 1;
}

.timeline-content {
    background: var(--admin-card-bg);
    border-radius: var(--admin-radius-lg);
    box-shadow: var(--admin-shadow-sm);
    padding: var(--admin-space-lg);
    display: flex;
    flex-wrap: wrap;
    gap: var(--admin-space-lg);
}

.project-info {
    flex: 1;
    min-width: 200px;
}

.project-info h4 {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--admin-text-primary);
    margin-bottom: var(--admin-space-xs);
}

.project-info p {
    color: var(--admin-text-secondary);
    font-size: 0.875rem;
    margin-bottom: var(--admin-space-sm);
}

.project-date {
    font-size: 0.75rem;
    color: var(--admin-text-muted);
    display: block;
}

.project-progress {
    flex: 1;
    min-width: 250px;
    display: flex;
    align-items: center;
    gap: var(--admin-space-md);
}

.progress-bar {
    flex: 1;
    height: 8px;
    background: var(--admin-border);
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--admin-info) 0%, #2980b9 100%);
    border-radius: 4px;
}

.project-actions {
    align-self: center;
}

/* Liste complète des projets */
.projects-section {
    margin-bottom: var(--admin-space-2xl);
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--admin-space-xl);
}

.section-header h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--admin-text-primary);
    display: flex;
    align-items: center;
    gap: var(--admin-space-sm);
}

.section-filters select {
    padding: var(--admin-space-sm) var(--admin-space-md);
    border: 1px solid var(--admin-border);
    border-radius: var(--admin-radius-md);
    background: var(--admin-card-bg);
    color: var(--admin-text-primary);
    font-size: 0.875rem;
}

.projects-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: var(--admin-space-xl);
}

.multimedia-card {
    background: var(--admin-card-bg);
    border-radius: var(--admin-radius-xl);
    box-shadow: var(--admin-shadow-sm);
    overflow: hidden;
    transition: var(--admin-transition);
    display: flex;
    flex-direction: column;
}

.multimedia-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--admin-shadow-lg);
}

.project-header {
    padding: var(--admin-space-lg);
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--admin-border-light);
}

.project-title h4 {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--admin-text-primary);
    margin-bottom: var(--admin-space-xs);
}

.project-type {
    font-size: 0.75rem;
    background: rgba(52, 152, 219, 0.1);
    color: var(--admin-info);
    padding: 0.25rem 0.5rem;
    border-radius: var(--admin-radius-sm);
    display: inline-block;
}

.project-status .status-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.75rem;
    border-radius: var(--admin-radius-sm);
    font-weight: 500;
}

.status-nouveau {
    background: rgba(52, 152, 219, 0.1);
    color: var(--admin-info);
}

.status-en_cours {
    background: rgba(243, 156, 18, 0.1);
    color: var(--admin-warning);
}

.status-termine {
    background: rgba(39, 174, 96, 0.1);
    color: var(--admin-success);
}

.status-annule {
    background: rgba(231, 76, 60, 0.1);
    color: var(--admin-danger);
}

.project-preview {
    height: 180px;
    background: linear-gradient(135deg, #1a2a6c, #b21f1f, #1a2a6c);
    background-size: 200% 200%;
    animation: gradientBG 10s ease infinite;
    display: flex;
    align-items: center;
    justify-content: center;
    color: rgba(255, 255, 255, 0.7);
    font-size: 3rem;
}

@keyframes gradientBG {
    0% { background-position: 0% 50% }
    50% { background-position: 100% 50% }
    100% { background-position: 0% 50% }
}

.project-details {
    padding: var(--admin-space-lg);
    flex-grow: 1;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: var(--admin-space-sm);
    font-size: 0.875rem;
}

.detail-row .label {
    color: var(--admin-text-secondary);
}

.detail-row .value {
    color: var(--admin-text-primary);
    font-weight: 500;
}

.progress-container {
    display: flex;
    align-items: center;
    gap: var(--admin-space-sm);
    width: 100%;
}

.progress-container .progress-bar {
    flex: 1;
    height: 6px;
    background: var(--admin-border);
    border-radius: 3px;
    overflow: hidden;
}

.progress-container .progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--admin-success) 0%, #229954 100%);
}

.progress-text {
    font-size: 0.75rem;
    color: var(--admin-text-secondary);
    min-width: 40px;
    text-align: right;
}

.project-description {
    padding: 0 var(--admin-space-lg) var(--admin-space-lg);
    font-size: 0.875rem;
    color: var(--admin-text-secondary);
    border-top: 1px solid var(--admin-border-light);
    padding-top: var(--admin-space-lg);
}

.project-actions {
    padding: var(--admin-space-lg);
    display: flex;
    gap: var(--admin-space-sm);
    flex-wrap: wrap;
    border-top: 1px solid var(--admin-border-light);
}

/* Modal */
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
    border-radius: var(--admin-radius-xl);
    box-shadow: var(--admin-shadow-lg);
    max-width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
}

.modal-content.large {
    width: 800px;
}

.modal-header {
    padding: var(--admin-space-xl);
    border-bottom: 1px solid var(--admin-border-light);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--admin-text-primary);
}

.close {
    font-size: 1.5rem;
    color: var(--admin-text-muted);
    cursor: pointer;
    transition: var(--admin-transition);
}

.close:hover {
    color: var(--admin-accent);
}

.modal-body {
    padding: var(--admin-space-xl);
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--admin-space-lg);
    margin-bottom: var(--admin-space-lg);
}

.form-group {
    margin-bottom: var(--admin-space-lg);
}

.form-group label {
    display: block;
    margin-bottom: var(--admin-space-sm);
    font-weight: 500;
    color: var(--admin-text-primary);
    font-size: 0.875rem;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: var(--admin-space-md);
    border: 1px solid var(--admin-border);
    border-radius: var(--admin-radius-md);
    background: var(--admin-card-bg);
    color: var(--admin-text-primary);
    font-size: 0.875rem;
    transition: var(--admin-transition);
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--admin-accent);
    box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
}

.form-actions {
    display: flex;
    gap: var(--admin-space-sm);
    justify-content: flex-end;
    margin-top: var(--admin-space-xl);
}

/* Responsive */
@media (max-width: 992px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .content-header {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--admin-space-lg);
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--admin-space-lg);
    }
    
    .projects-grid {
        grid-template-columns: 1fr;
    }
    
    .timeline-content {
        flex-direction: column;
    }
    
    .modal-content.large {
        width: 95%;
    }
}

@media (max-width: 480px) {
    .header-actions {
        flex-direction: column;
        width: 100%;
    }
    
    .header-actions .btn {
        width: 100%;
        justify-content: center;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .services-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions .btn {
        width: 100%;
    }
}
</style>