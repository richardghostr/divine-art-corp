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
$page_title = "Marketing Digital";

// Récupérer les projets marketing
$marketing_query = "SELECT d.*, p.id as projet_id, p.statut as projet_statut, p.progression,
                           c.nom as client_nom, c.entreprise
                    FROM devis d 
                    LEFT JOIN projets p ON d.id = p.devis_id
                    LEFT JOIN clients c ON d.email = c.email
                    WHERE d.service = 'marketing' 
                    ORDER BY d.date_creation DESC";
$marketing_result = mysqli_query($conn, $marketing_query);

// Statistiques marketing
$stats_query = "SELECT 
    COUNT(*) as total_projets,
    SUM(CASE WHEN d.statut = 'termine' THEN 1 ELSE 0 END) as projets_termines,
    SUM(CASE WHEN d.statut = 'en_cours' THEN 1 ELSE 0 END) as projets_en_cours,
    SUM(d.montant_final) as ca_total,
    AVG(d.montant_final) as montant_moyen
FROM devis d WHERE d.service = 'marketing'";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Sous-services marketing les plus demandés
$sous_services_query = "SELECT sous_service, COUNT(*) as count 
                       FROM devis 
                       WHERE service = 'marketing' AND sous_service IS NOT NULL 
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
                <h1><i class="fas fa-bullhorn"></i> <?php echo $page_title; ?></h1>
                <p>Gérez vos projets de marketing digital</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="showCampaignModal()">
                    <i class="fas fa-plus"></i> Nouvelle Campagne
                </button>
                <button class="btn btn-secondary" onclick="showAnalyticsModal()">
                    <i class="fas fa-chart-line"></i> Analytics
                </button>
            </div>
        </div>

        <!-- Statistiques Marketing -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon bg-red">
                    <i class="fas fa-bullhorn"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['total_projets']); ?></h3>
                    <p>Projets Marketing</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-green">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['projets_termines']); ?></h3>
                    <p>Projets Terminés</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-orange">
                    <i class="fas fa-play-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['projets_en_cours']); ?></h3>
                    <p>En Cours</p>
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

        <!-- Services Marketing Populaires -->
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
                    <h3><i class="fas fa-tasks"></i> Actions Rapides</h3>
                </div>
                <div class="card-content">
                    <div class="quick-actions">
                        <button class="action-btn" onclick="createSocialMediaPlan()">
                            <i class="fab fa-facebook"></i>
                            <span>Plan Social Media</span>
                        </button>
                        <button class="action-btn" onclick="createAdCampaign()">
                            <i class="fas fa-ad"></i>
                            <span>Campagne Pub</span>
                        </button>
                        <button class="action-btn" onclick="createSEOAudit()">
                            <i class="fas fa-search"></i>
                            <span>Audit SEO</span>
                        </button>
                        <button class="action-btn" onclick="createEmailCampaign()">
                            <i class="fas fa-envelope"></i>
                            <span>Email Marketing</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des Projets Marketing -->
        <div class="projects-section">
            <div class="section-header">
                <h2><i class="fas fa-project-diagram"></i> Projets Marketing</h2>
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
                <?php while ($projet = mysqli_fetch_assoc($marketing_result)): ?>
                    <div class="project-card" data-status="<?php echo $projet['statut']; ?>">
                        <div class="project-header">
                            <div class="project-title">
                                <h4><?php echo htmlspecialchars($projet['client_nom'] ?: $projet['nom']); ?></h4>
                                <span class="project-type"><?php echo htmlspecialchars($projet['sous_service'] ?: 'Marketing Général'); ?></span>
                            </div>
                            <div class="project-status">
                                <span class="status-badge status-<?php echo $projet['statut']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $projet['statut'])); ?>
                                </span>
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
                            <p><?php echo htmlspecialchars(substr($projet['description'], 0, 150)) . (strlen($projet['description']) > 150 ? '...' : ''); ?></p>
                        </div>

                        <div class="project-actions">
                            <button class="btn btn-sm btn-primary" onclick="viewProject(<?php echo $projet['id']; ?>)">
                                <i class="fas fa-eye"></i> Voir
                            </button>
                            <?php if ($projet['projet_id']): ?>
                                <button class="btn btn-sm btn-info" onclick="manageProject(<?php echo $projet['projet_id']; ?>)">
                                    <i class="fas fa-cogs"></i> Gérer
                                </button>
                            <?php else: ?>
                                <button class="btn btn-sm btn-success" onclick="createProject(<?php echo $projet['id']; ?>)">
                                    <i class="fas fa-plus"></i> Créer Projet
                                </button>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-secondary" onclick="contactClient('<?php echo $projet['email']; ?>')">
                                <i class="fas fa-envelope"></i> Contact
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </main>
</div>

<!-- Modal pour nouvelle campagne -->
<div id="campaignModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Nouvelle Campagne Marketing</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form id="campaignForm">
                <div class="form-group">
                    <label for="campaignType">Type de campagne</label>
                    <select id="campaignType" required>
                        <option value="">Sélectionner un type</option>
                        <option value="social-media">Social Media</option>
                        <option value="google-ads">Google Ads</option>
                        <option value="facebook-ads">Facebook Ads</option>
                        <option value="email-marketing">Email Marketing</option>
                        <option value="seo">SEO</option>
                        <option value="content-marketing">Content Marketing</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="campaignName">Nom de la campagne</label>
                    <input type="text" id="campaignName" required>
                </div>
                
                <div class="form-group">
                    <label for="campaignClient">Client</label>
                    <select id="campaignClient" required>
                        <option value="">Sélectionner un client</option>
                        <!-- Options chargées dynamiquement -->
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="campaignBudget">Budget (FCFA)</label>
                    <input type="number" id="campaignBudget" min="0">
                </div>
                
                <div class="form-group">
                    <label for="campaignDescription">Description</label>
                    <textarea id="campaignDescription" rows="4"></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Créer Campagne
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeCampaignModal()">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showCampaignModal() {
    document.getElementById('campaignModal').style.display = 'block';
    loadClients();
}

function closeCampaignModal() {
    document.getElementById('campaignModal').style.display = 'none';
}

function loadClients() {
    fetch('../api/get_clients_list.php')
        .then(response => response.json())
        .then(clients => {
            const select = document.getElementById('campaignClient');
            select.innerHTML = '<option value="">Sélectionner un client</option>';
            clients.forEach(client => {
                select.innerHTML += `<option value="${client.id}">${client.nom} - ${client.entreprise || client.email}</option>`;
            });
        });
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

function createSocialMediaPlan() {
    // Logique pour créer un plan social media
    alert('Fonctionnalité en développement');
}

function createAdCampaign() {
    // Logique pour créer une campagne publicitaire
    alert('Fonctionnalité en développement');
}

function createSEOAudit() {
    // Logique pour créer un audit SEO
    alert('Fonctionnalité en développement');
}

function createEmailCampaign() {
    // Logique pour créer une campagne email
    alert('Fonctionnalité en développement');
}

function viewProject(id) {
    window.location.href = `devis.php?id=${id}`;
}

function manageProject(id) {
    window.location.href = `projets.php?id=${id}`;
}

function createProject(devisId) {
    if (confirm('Créer un projet à partir de ce devis ?')) {
        fetch('../api/create_project.php', {
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
                alert('Erreur lors de la création du projet');
            }
        });
    }
}

function contactClient(email) {
    window.location.href = `mailto:${email}`;
}

// Gestion des modals
document.querySelector('.close').onclick = closeCampaignModal;

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>

<?php include 'footer.php'; ?>
