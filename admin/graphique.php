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
$page_title = "Conception Graphique";

// Récupérer les projets graphiques
$graphique_query = "SELECT d.*, p.id as projet_id, p.statut as projet_statut, p.progression,
                           c.nom as client_nom, c.entreprise
                    FROM devis d 
                    LEFT JOIN projets p ON d.id = p.devis_id
                    LEFT JOIN clients c ON d.email = c.email
                    WHERE d.service = 'graphique' 
                    ORDER BY d.date_creation DESC";
$graphique_result = mysqli_query($conn, $graphique_query);

// Statistiques graphiques
$stats_query = "SELECT 
    COUNT(*) as total_projets,
    SUM(CASE WHEN d.statut = 'termine' THEN 1 ELSE 0 END) as projets_termines,
    SUM(CASE WHEN d.statut = 'en_cours' THEN 1 ELSE 0 END) as projets_en_cours,
    SUM(d.montant_final) as ca_total,
    AVG(d.montant_final) as montant_moyen
FROM devis d WHERE d.service = 'graphique'";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Sous-services graphiques les plus demandés
$sous_services_query = "SELECT sous_service, COUNT(*) as count 
                       FROM devis 
                       WHERE service = 'graphique' AND sous_service IS NOT NULL 
                       GROUP BY sous_service 
                       ORDER BY count DESC 
                       LIMIT 5";
$sous_services_result = mysqli_query($conn, $sous_services_query);

include 'header.php';
?>

<div class="admin-content">
    <?php include 'sidebar.php'; ?>
    
    <main class="main-content">
        <div class="content-header">
            <div class="header-left">
                <h1><i class="fas fa-paint-brush"></i> <?php echo $page_title; ?></h1>
                <p>Gérez vos projets de conception graphique</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="showDesignModal()">
                    <i class="fas fa-plus"></i> Nouveau Design
                </button>
                <button class="btn btn-secondary" onclick="showPortfolioModal()">
                    <i class="fas fa-images"></i> Portfolio
                </button>
            </div>
        </div>

        <!-- Statistiques Graphiques -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon bg-purple">
                    <i class="fas fa-paint-brush"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['total_projets']); ?></h3>
                    <p>Projets Graphiques</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-green">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['projets_termines']); ?></h3>
                    <p>Designs Terminés</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-orange">
                    <i class="fas fa-palette"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['projets_en_cours']); ?></h3>
                    <p>En Création</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-blue">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['ca_total'], 0, ',', ' '); ?> FCFA</h3>
                    <p>Chiffre d'Affaires</p>
                </div>
            </div>
        </div>

        <!-- Services Graphiques Populaires -->
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-pie"></i> Services les Plus Demandés</h3>
                </div>
                <div class="card-content">
                    <div class="services-chart">
                        <?php while ($service = mysqli_fetch_assoc($sous_services_result)): ?>
                            <div class="service-item">
                                <div class="service-name"><?php echo htmlspecialchars($service['sous_service']); ?></div>
                                <div class="service-bar">
                                    <div class="service-progress" style="width: <?php echo ($service['count'] / $stats['total_projets']) * 100; ?>%"></div>
                                </div>
                                <div class="service-count"><?php echo $service['count']; ?></div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-tools"></i> Outils de Design</h3>
                </div>
                <div class="card-content">
                    <div class="design-tools">
                        <div class="tool-item">
                            <i class="fab fa-adobe"></i>
                            <span>Adobe Creative Suite</span>
                        </div>
                        <div class="tool-item">
                            <i class="fas fa-vector-square"></i>
                            <span>Illustrator</span>
                        </div>
                        <div class="tool-item">
                            <i class="fas fa-image"></i>
                            <span>Photoshop</span>
                        </div>
                        <div class="tool-item">
                            <i class="fas fa-file-pdf"></i>
                            <span>InDesign</span>
                        </div>
                        <div class="tool-item">
                            <i class="fas fa-cube"></i>
                            <span>Figma</span>
                        </div>
                        <div class="tool-item">
                            <i class="fas fa-pencil-ruler"></i>
                            <span>Canva Pro</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions Rapides -->
        <div class="quick-actions-section">
            <h3><i class="fas fa-bolt"></i> Actions Rapides</h3>
            <div class="quick-actions-grid">
                <button class="action-card" onclick="createLogo()">
                    <i class="fas fa-copyright"></i>
                    <h4>Créer un Logo</h4>
                    <p>Nouveau design de logo</p>
                </button>
                <button class="action-card" onclick="createBranding()">
                    <i class="fas fa-palette"></i>
                    <h4>Charte Graphique</h4>
                    <p>Identité visuelle complète</p>
                </button>
                <button class="action-card" onclick="createFlyer()">
                    <i class="fas fa-file-image"></i>
                    <h4>Support Print</h4>
                    <p>Flyers, brochures, cartes</p>
                </button>
                <button class="action-card" onclick="createWebDesign()">
                    <i class="fas fa-desktop"></i>
                    <h4>Design Web</h4>
                    <p>Maquettes et interfaces</p>
                </button>
            </div>
        </div>

        <!-- Liste des Projets Graphiques -->
        <div class="projects-section">
            <div class="section-header">
                <h2><i class="fas fa-project-diagram"></i> Projets Graphiques</h2>
                <div class="section-filters">
                    <select id="statusFilter" onchange="filterProjects()">
                        <option value="">Tous les statuts</option>
                        <option value="nouveau">Nouveau</option>
                        <option value="en_cours">En cours</option>
                        <option value="termine">Terminé</option>
                        <option value="annule">Annulé</option>
                    </select>
                    <select id="typeFilter" onchange="filterProjects()">
                        <option value="">Tous les types</option>
                        <option value="creation-logo">Logo</option>
                        <option value="charte-graphique">Charte Graphique</option>
                        <option value="supports-print">Supports Print</option>
                        <option value="web-design">Web Design</option>
                    </select>
                </div>
            </div>

            <div class="projects-grid">
                <?php while ($projet = mysqli_fetch_assoc($graphique_result)): ?>
                    <div class="project-card design-card" data-status="<?php echo $projet['statut']; ?>" data-type="<?php echo $projet['sous_service']; ?>">
                        <div class="project-header">
                            <div class="project-title">
                                <h4><?php echo htmlspecialchars($projet['client_nom'] ?: $projet['nom']); ?></h4>
                                <span class="project-type"><?php echo htmlspecialchars($projet['sous_service'] ?: 'Design Général'); ?></span>
                            </div>
                            <div class="project-status">
                                <span class="status-badge status-<?php echo $projet['statut']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $projet['statut'])); ?>
                                </span>
                            </div>
                        </div>

                        <div class="project-preview">
                            <div class="preview-placeholder">
                                <i class="fas fa-image"></i>
                                <span>Aperçu du design</span>
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
                            <button class="btn btn-sm btn-primary" onclick="viewDesign(<?php echo $projet['id']; ?>)">
                                <i class="fas fa-eye"></i> Voir
                            </button>
                            <?php if ($projet['projet_id']): ?>
                                <button class="btn btn-sm btn-info" onclick="editDesign(<?php echo $projet['projet_id']; ?>)">
                                    <i class="fas fa-edit"></i> Modifier
                                </button>
                            <?php else: ?>
                                <button class="btn btn-sm btn-success" onclick="startDesign(<?php echo $projet['id']; ?>)">
                                    <i class="fas fa-play"></i> Commencer
                                </button>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-secondary" onclick="downloadFiles(<?php echo $projet['id']; ?>)">
                                <i class="fas fa-download"></i> Fichiers
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </main>
</div>

<!-- Modal pour nouveau design -->
<div id="designModal" class="modal">
    <div class="modal-content large">
        <div class="modal-header">
            <h2>Nouveau Projet de Design</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form id="designForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="designType">Type de design</label>
                        <select id="designType" required>
                            <option value="">Sélectionner un type</option>
                            <option value="logo">Logo</option>
                            <option value="charte-graphique">Charte Graphique</option>
                            <option value="flyer">Flyer</option>
                            <option value="brochure">Brochure</option>
                            <option value="carte-visite">Carte de Visite</option>
                            <option value="affiche">Affiche</option>
                            <option value="web-design">Design Web</option>
                            <option value="packaging">Packaging</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="designClient">Client</label>
                        <select id="designClient" required>
                            <option value="">Sélectionner un client</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="designTitle">Titre du projet</label>
                    <input type="text" id="designTitle" required>
                </div>
                
                <div class="form-group">
                    <label for="designDescription">Description détaillée</label>
                    <textarea id="designDescription" rows="4" placeholder="Décrivez le projet, les objectifs, le style souhaité..."></textarea>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="designBudget">Budget (FCFA)</label>
                        <input type="number" id="designBudget" min="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="designDeadline">Date limite</label>
                        <input type="date" id="designDeadline">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="designSpecs">Spécifications techniques</label>
                    <textarea id="designSpecs" rows="3" placeholder="Dimensions, formats, couleurs, contraintes techniques..."></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Créer Projet
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeDesignModal()">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showDesignModal() {
    document.getElementById('designModal').style.display = 'block';
    loadClients();
}

function closeDesignModal() {
    document.getElementById('designModal').style.display = 'none';
}

function loadClients() {
    fetch('../api/get_clients_list.php')
        .then(response => response.json())
        .then(clients => {
            const select = document.getElementById('designClient');
            select.innerHTML = '<option value="">Sélectionner un client</option>';
            clients.forEach(client => {
                select.innerHTML += `<option value="${client.id}">${client.nom} - ${client.entreprise || client.email}</option>`;
            });
        });
}

function filterProjects() {
    const statusFilter = document.getElementById('statusFilter').value;
    const typeFilter = document.getElementById('typeFilter').value;
    const cards = document.querySelectorAll('.project-card');
    
    cards.forEach(card => {
        const matchStatus = statusFilter === '' || card.dataset.status === statusFilter;
        const matchType = typeFilter === '' || card.dataset.type === typeFilter;
        
        if (matchStatus && matchType) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function createLogo() {
    document.getElementById('designType').value = 'logo';
    showDesignModal();
}

function createBranding() {
    document.getElementById('designType').value = 'charte-graphique';
    showDesignModal();
}

function createFlyer() {
    document.getElementById('designType').value = 'flyer';
    showDesignModal();
}

function createWebDesign() {
    document.getElementById('designType').value = 'web-design';
    showDesignModal();
}

function viewDesign(id) {
    window.location.href = `design_details.php?id=${id}`;
}

function editDesign(id) {
    window.location.href = `edit_design.php?id=${id}`;
}

function startDesign(devisId) {
    if (confirm('Commencer ce projet de design ?')) {
        fetch('../api/start_design_project.php', {
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
                alert('Erreur lors du démarrage du projet');
            }
        });
    }
}

function downloadFiles(id) {
    window.location.href = `../api/download_project_files.php?id=${id}`;
}

// Gestion des modals
document.querySelector('.close').onclick = closeDesignModal;

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>

<?php include 'footer.php'; ?>
