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
   
</style>