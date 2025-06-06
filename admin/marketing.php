<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Vérification de l'authentification
$auth = new Auth();
$auth->requireAuth();

$currentAdmin = $auth->getCurrentUser();
$page_title = "Marketing Digital";

// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_campaign':
            $type = $_POST['campaign_type'];
            $name = $_POST['campaign_name'];
            $client_id = $_POST['client_id'];
            $budget = $_POST['budget'];
            $description = $_POST['description'];
            
            $query = "INSERT INTO campagnes_marketing (type, nom, client_id, budget, description, statut, date_creation) 
                     VALUES (?, ?, ?, ?, ?, 'planifiee', NOW())";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ssids", $type, $name, $client_id, $budget, $description);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Campagne créée avec succès";
            } else {
                $error_message = "Erreur lors de la création de la campagne";
            }
            break;
            
        case 'update_campaign_status':
            $campaign_id = $_POST['campaign_id'];
            $new_status = $_POST['new_status'];
            
            $query = "UPDATE campagnes_marketing SET statut = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "si", $new_status, $campaign_id);
            
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['success' => true]);
                exit;
            }
            break;
    }
}

// Récupération des statistiques
$stats_query = "SELECT 
    COUNT(*) as total_campagnes,
    SUM(CASE WHEN statut = 'active' THEN 1 ELSE 0 END) as campagnes_actives,
    SUM(CASE WHEN statut = 'terminee' THEN 1 ELSE 0 END) as campagnes_terminees,
    SUM(budget_total) as budget_total,
    AVG(budget_depense) as budget_moyen
FROM campagnes_marketing";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Récupération des projets marketing
$filter_status = $_GET['status'] ?? '';
$filter_type = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';

$where_conditions = ["1=1"];
$params = [];
$types = "";

if ($filter_status) {
    $where_conditions[] = "cm.statut = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if ($filter_type) {
    $where_conditions[] = "cm.type = ?";
    $params[] = $filter_type;
    $types .= "s";
}

if ($search) {
    $where_conditions[] = "(cm.nom LIKE ? OR c.nom LIKE ? OR c.entreprise LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$where_clause = implode(" AND ", $where_conditions);

$marketing_query = "SELECT cm.*, c.nom as client_nom, c.entreprise, c.email
                   FROM campagnes_marketing cm
                   LEFT JOIN clients c ON cm.client_id = c.id
                   WHERE $where_clause
                   ORDER BY cm.date_creation DESC";

if ($params) {
    $stmt = mysqli_prepare($conn, $marketing_query);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $marketing_result = mysqli_stmt_get_result($stmt);
} else {
    $marketing_result = mysqli_query($conn, $marketing_query);
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
                <h1><i class="fas fa-bullhorn"></i> <?php echo $page_title; ?></h1>
                <p>Gérez vos campagnes de marketing digital</p>
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

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon bg-red">
                    <i class="fas fa-bullhorn"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['total_campagnes'] ?? 0); ?></h3>
                    <p>Total Campagnes</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-green">
                    <i class="fas fa-play-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['campagnes_actives'] ?? 0); ?></h3>
                    <p>Campagnes Actives</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-blue">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['campagnes_terminees'] ?? 0); ?></h3>
                    <p>Campagnes Terminées</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-purple">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['budget_total'] ?? 0, 0, ',', ' '); ?> FCFA</h3>
                    <p>Budget Total</p>
                </div>
            </div>
        </div>

        <!-- Services Marketing -->
        <div class="services-overview">
            <h3><i class="fas fa-cogs"></i> Services Marketing</h3>
            <div class="services-grid">
                <div class="service-card" onclick="createCampaign('social-media')">
                    <div class="service-icon">
                        <i class="fab fa-facebook"></i>
                    </div>
                    <h4>Social Media</h4>
                    <p>Gestion des réseaux sociaux</p>
                </div>
                
                <div class="service-card" onclick="createCampaign('google-ads')">
                    <div class="service-icon">
                        <i class="fab fa-google"></i>
                    </div>
                    <h4>Google Ads</h4>
                    <p>Campagnes publicitaires Google</p>
                </div>
                
                <div class="service-card" onclick="createCampaign('email-marketing')">
                    <div class="service-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h4>Email Marketing</h4>
                    <p>Campagnes d'emailing</p>
                </div>
                
                <div class="service-card" onclick="createCampaign('seo')">
                    <div class="service-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h4>SEO</h4>
                    <p>Référencement naturel</p>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="filters-section">
            <div class="filters-tabs">
                <button class="filter-tab <?php echo !$filter_status ? 'active' : ''; ?>" 
                        onclick="filterCampaigns('')">Toutes</button>
                <button class="filter-tab <?php echo $filter_status === 'planifiee' ? 'active' : ''; ?>" 
                        onclick="filterCampaigns('planifiee')">Planifiées</button>
                <button class="filter-tab <?php echo $filter_status === 'active' ? 'active' : ''; ?>" 
                        onclick="filterCampaigns('active')">Actives</button>
                <button class="filter-tab <?php echo $filter_status === 'terminee' ? 'active' : ''; ?>" 
                        onclick="filterCampaigns('terminee')">Terminées</button>
            </div>
            
            <div class="filters-controls">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Rechercher une campagne..." 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           onkeyup="searchCampaigns(this.value)">
                </div>
                <select onchange="filterByType(this.value)">
                    <option value="">Tous les types</option>
                    <option value="social-media" <?php echo $filter_type === 'social-media' ? 'selected' : ''; ?>>Social Media</option>
                    <option value="google-ads" <?php echo $filter_type === 'google-ads' ? 'selected' : ''; ?>>Google Ads</option>
                    <option value="email-marketing" <?php echo $filter_type === 'email-marketing' ? 'selected' : ''; ?>>Email Marketing</option>
                    <option value="seo" <?php echo $filter_type === 'seo' ? 'selected' : ''; ?>>SEO</option>
                </select>
            </div>
        </div>

        <!-- Liste des campagnes -->
        <div class="campaigns-grid">
            <?php while ($campaign = mysqli_fetch_assoc($marketing_result)): ?>
                <div class="campaign-card">
                    <div class="campaign-header">
                        <div class="campaign-type">
                            <?php
                            $type_icons = [
                                'social-media' => 'fab fa-facebook',
                                'google-ads' => 'fab fa-google',
                                'email-marketing' => 'fas fa-envelope',
                                'seo' => 'fas fa-search'
                            ];
                            $icon = $type_icons[$campaign['type']] ?? 'fas fa-bullhorn';
                            ?>
                            <i class="<?php echo $icon; ?>"></i>
                            <span><?php echo ucfirst(str_replace('-', ' ', $campaign['type'])); ?></span>
                        </div>
                        <div class="campaign-status">
                            <span class="status-badge status-<?php echo $campaign['statut']; ?>">
                                <?php echo ucfirst($campaign['statut']); ?>
                            </span>
                        </div>
                    </div>

                    <div class="campaign-content">
                        <h4><?php echo htmlspecialchars($campaign['nom']); ?></h4>
                        <p class="campaign-client">
                            <i class="fas fa-user"></i>
                            <?php echo htmlspecialchars($campaign['client_nom'] . ' - ' . ($campaign['entreprise'] ?: $campaign['email'])); ?>
                        </p>
                        <p class="campaign-description">
                            <?php echo htmlspecialchars(substr($campaign['description'], 0, 100)) . (strlen($campaign['description']) > 100 ? '...' : ''); ?>
                        </p>
                    </div>

                    <div class="campaign-details">
                        <div class="detail-item">
                            <span class="label">Budget:</span>
                            <span class="value"><?php echo number_format($campaign['budget'], 0, ',', ' '); ?> FCFA</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Créée le:</span>
                            <span class="value"><?php echo date('d/m/Y', strtotime($campaign['date_creation'])); ?></span>
                        </div>
                    </div>

                    <div class="campaign-actions">
                        <button class="btn btn-sm btn-primary" onclick="viewCampaign(<?php echo $campaign['id']; ?>)">
                            <i class="fas fa-eye"></i> Voir
                        </button>
                        <button class="btn btn-sm btn-info" onclick="editCampaign(<?php echo $campaign['id']; ?>)">
                            <i class="fas fa-edit"></i> Modifier
                        </button>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-secondary dropdown-toggle">
                                <i class="fas fa-cog"></i> Actions
                            </button>
                            <div class="dropdown-menu">
                                <a href="#" onclick="updateCampaignStatus(<?php echo $campaign['id']; ?>, 'active')">
                                    <i class="fas fa-play"></i> Activer
                                </a>
                                <a href="#" onclick="updateCampaignStatus(<?php echo $campaign['id']; ?>, 'pause')">
                                    <i class="fas fa-pause"></i> Mettre en pause
                                </a>
                                <a href="#" onclick="updateCampaignStatus(<?php echo $campaign['id']; ?>, 'terminee')">
                                    <i class="fas fa-stop"></i> Terminer
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </main>
</div>

<!-- Modal Nouvelle Campagne -->
<div id="campaignModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Nouvelle Campagne Marketing</h2>
            <span class="close" onclick="closeCampaignModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="create_campaign">
                
                <div class="form-group">
                    <label for="campaign_type">Type de campagne</label>
                    <select name="campaign_type" id="campaign_type" required>
                        <option value="">Sélectionner un type</option>
                        <option value="social-media">Social Media</option>
                        <option value="google-ads">Google Ads</option>
                        <option value="email-marketing">Email Marketing</option>
                        <option value="seo">SEO</option>
                        <option value="content-marketing">Content Marketing</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="campaign_name">Nom de la campagne</label>
                    <input type="text" name="campaign_name" id="campaign_name" required>
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

                <div class="form-group">
                    <label for="budget">Budget (FCFA)</label>
                    <input type="number" name="budget" id="budget" min="0" step="1000">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" rows="4" 
                              placeholder="Décrivez les objectifs et la stratégie de la campagne..."></textarea>
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
}

function closeCampaignModal() {
    document.getElementById('campaignModal').style.display = 'none';
}

function createCampaign(type) {
    document.getElementById('campaign_type').value = type;
    showCampaignModal();
}

function filterCampaigns(status) {
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

function searchCampaigns(query) {
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

function updateCampaignStatus(campaignId, newStatus) {
    if (confirm('Confirmer le changement de statut ?')) {
        fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=update_campaign_status&campaign_id=${campaignId}&new_status=${newStatus}`
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

function viewCampaign(id) {
    window.location.href = `campaign_details.php?id=${id}`;
}

function editCampaign(id) {
    window.location.href = `edit_campaign.php?id=${id}`;
}

function showAnalyticsModal() {
    alert('Fonctionnalité Analytics en développement');
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
/* Services Overview */
.services-overview {
    background: var(--admin-card-bg);
    border-radius: var(--admin-radius-xl);
    box-shadow: var(--admin-shadow-sm);
    padding: var(--admin-space-xl);
    margin-bottom: var(--admin-space-xl);
}

.services-overview h3 {
    margin-bottom: var(--admin-space-lg);
    display: flex;
    align-items: center;
    gap: var(--admin-space-sm);
}

.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--admin-space-lg);
}

.service-card {
    background: var(--admin-bg);
    border: 1px solid var(--admin-border);
    border-radius: var(--admin-radius-lg);
    padding: var(--admin-space-lg);
    text-align: center;
    cursor: pointer;
    transition: var(--admin-transition);
}

.service-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--admin-shadow-md);
    border-color: var(--admin-accent);
}

.service-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--admin-accent) 0%, #c0392b 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto var(--admin-space-md);
    font-size: 1.5rem;
    color: white;
}

.service-card h4 {
    margin-bottom: var(--admin-space-sm);
    color: var(--admin-text-primary);
}

.service-card p {
    color: var(--admin-text-secondary);
    font-size: 0.875rem;
}

/* Campaigns Grid */
.campaigns-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: var(--admin-space-xl);
}

.campaign-card {
    background: var(--admin-card-bg);
    border-radius: var(--admin-radius-xl);
    box-shadow: var(--admin-shadow-sm);
    overflow: hidden;
    transition: var(--admin-transition);
}

.campaign-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--admin-shadow-md);
}

.campaign-header {
    padding: var(--admin-space-lg);
    border-bottom: 1px solid var(--admin-border-light);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.campaign-type {
    display: flex;
    align-items: center;
    gap: var(--admin-space-sm);
    font-weight: 600;
    color: var(--admin-text-primary);
}

.campaign-type i {
    font-size: 1.25rem;
    color: var(--admin-accent);
}

.campaign-content {
    padding: var(--admin-space-lg);
}

.campaign-content h4 {
    margin-bottom: var(--admin-space-sm);
    color: var(--admin-text-primary);
}

.campaign-client {
    color: var(--admin-text-secondary);
    font-size: 0.875rem;
    margin-bottom: var(--admin-space-sm);
    display: flex;
    align-items: center;
    gap: var(--admin-space-xs);
}

.campaign-description {
    color: var(--admin-text-secondary);
    font-size: 0.875rem;
    line-height: 1.5;
}

.campaign-details {
    padding: 0 var(--admin-space-lg) var(--admin-space-lg);
    border-top: 1px solid var(--admin-border-light);
    padding-top: var(--admin-space-lg);
}

.detail-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: var(--admin-space-xs);
    font-size: 0.875rem;
}

.detail-item .label {
    color: var(--admin-text-secondary);
}

.detail-item .value {
    color: var(--admin-text-primary);
    font-weight: 500;
}

.campaign-actions {
    padding: var(--admin-space-lg);
    border-top: 1px solid var(--admin-border-light);
    display: flex;
    gap: var(--admin-space-sm);
    flex-wrap: wrap;
}

/* Status badges */
.status-planifiee {
    background: rgba(52, 152, 219, 0.1);
    color: var(--admin-info);
}

.status-active {
    background: rgba(39, 174, 96, 0.1);
    color: var(--admin-success);
}

.status-pause {
    background: rgba(243, 156, 18, 0.1);
    color: var(--admin-warning);
}

.status-terminee {
    background: rgba(149, 165, 166, 0.1);
    color: #95a5a6;
}

/* Dropdown */
.dropdown {
    position: relative;
}

.dropdown-menu {
    display: none;
    position: absolute;
    top: 100%;
    right: 0;
    background: var(--admin-card-bg);
    border: 1px solid var(--admin-border);
    border-radius: var(--admin-radius-md);
    box-shadow: var(--admin-shadow-md);
    min-width: 150px;
    z-index: 1000;
}

.dropdown-menu a {
    display: flex;
    align-items: center;
    gap: var(--admin-space-sm);
    padding: var(--admin-space-sm) var(--admin-space-md);
    color: var(--admin-text-primary);
    text-decoration: none;
    font-size: 0.875rem;
    transition: var(--admin-transition);
}

.dropdown-menu a:hover {
    background: var(--admin-border-light);
}

/* Responsive */
@media (max-width: 768px) {
    .campaigns-grid {
        grid-template-columns: 1fr;
    }
    
    .services-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .campaign-actions {
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    .services-grid {
        grid-template-columns: 1fr;
    }
}
</style>
