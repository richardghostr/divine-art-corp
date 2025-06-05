<?php
// Gestion des services d'imprimerie - Divine Art Corporation
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

// Récupération des statistiques d'imprimerie
$stats = [];
$query = "SELECT 
          COUNT(*) as total_projets,
          SUM(CASE WHEN p.statut = 'en_cours' THEN 1 ELSE 0 END) as projets_en_cours,
          SUM(CASE WHEN p.statut = 'termine' THEN 1 ELSE 0 END) as projets_termines,
          SUM(CASE WHEN p.statut = 'planifie' THEN 1 ELSE 0 END) as projets_planifies,
          AVG(d.montant_final) as montant_moyen
          FROM projets p 
          JOIN devis d ON p.devis_id = d.id 
          WHERE d.service = 'imprimerie'";
$result = $conn->query($query);
if ($result) {
    $stats = $result->fetch_assoc();
}

// Récupération des sous-services d'imprimerie
$sous_services = [];
$query = "SELECT ss.*, COUNT(p.id) as nb_projets 
          FROM sous_services ss 
          LEFT JOIN devis d ON ss.slug = d.sous_service 
          LEFT JOIN projets p ON d.id = p.devis_id 
          WHERE ss.service_id = (SELECT id FROM services WHERE slug = 'imprimerie') 
          GROUP BY ss.id 
          ORDER BY ss.ordre ASC";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $sous_services[] = $row;
    }
}

// Pagination et filtrage
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
$service_filter = isset($_GET['service']) ? $conn->real_escape_string($_GET['service']) : '';

// Construction de la clause WHERE
$where_clause = "d.service = 'imprimerie'";
if (!empty($search)) {
    $where_clause .= " AND (p.nom LIKE '%$search%' OR d.nom LIKE '%$search%' OR d.entreprise LIKE '%$search%')";
}
if (!empty($status_filter)) {
    $where_clause .= " AND p.statut = '$status_filter'";
}
if (!empty($service_filter)) {
    $where_clause .= " AND d.sous_service = '$service_filter'";
}

// Récupération des projets d'imprimerie
$query = "SELECT p.*, d.nom as client_nom, d.entreprise, d.email, d.telephone, 
          d.sous_service, d.montant_final, a.nom as responsable_nom,
          ss.nom as service_nom
          FROM projets p 
          JOIN devis d ON p.devis_id = d.id 
          LEFT JOIN admins a ON p.admin_responsable = a.id
          LEFT JOIN sous_services ss ON d.sous_service = ss.slug
          WHERE $where_clause
          ORDER BY p.date_creation DESC
          LIMIT $offset, $limit";
$result = $conn->query($query);

$projets = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
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
        
        // Action: Mettre à jour le statut
        if ($_POST['action'] === 'update_status' && $projet_id > 0) {
            $statut = $conn->real_escape_string($_POST['statut']);
            $progression = intval($_POST['progression']);
            
            $query = "UPDATE projets SET statut = ?, progression = ?, date_modification = NOW() WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sii", $statut, $progression, $projet_id);
            
            if ($stmt->execute()) {
                $message = "Le statut du projet a été mis à jour avec succès.";
                $message_type = "success";
                logActivity($_SESSION['admin_id'], 'update_status', 'projets', $projet_id, "Statut: $statut, Progression: $progression%");
            } else {
                $message = "Erreur lors de la mise à jour: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }
        
        // Action: Assigner un responsable
        if ($_POST['action'] === 'assign_admin' && $projet_id > 0) {
            $admin_id = intval($_POST['admin_id']);
            
            $query = "UPDATE projets SET admin_responsable = ?, date_modification = NOW() WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $admin_id, $projet_id);
            
            if ($stmt->execute()) {
                $message = "Le responsable a été assigné avec succès.";
                $message_type = "success";
                logActivity($_SESSION['admin_id'], 'assign_admin', 'projets', $projet_id, "Admin assigné: $admin_id");
            } else {
                $message = "Erreur lors de l'assignation: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }
    }
}

// Récupérer la liste des administrateurs
$admins = [];
$query = "SELECT id, nom FROM admins WHERE statut = 'actif' ORDER BY nom ASC";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
}

$page_title = "Gestion des Services d'Imprimerie";
require_once 'header.php';
require_once 'sidebar.php';
?>

<main class="admin-main">
    <div class="content-header">
        <div class="header-left">
            <h1><i class="fas fa-print"></i> Gestion des Services d'Imprimerie</h1>
            <p>Gérez vos projets d'imprimerie et services associés</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-outline" onclick="exportData()">
                <i class="fas fa-file-export"></i> Exporter
            </button>
            <button class="btn btn-primary" onclick="openModal('addServiceModal')">
                <i class="fas fa-plus"></i> Nouveau Service
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
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['total_projets'] ?? 0; ?></h3>
                <p>Total Projets</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-orange">
                <i class="fas fa-spinner"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['projets_en_cours'] ?? 0; ?></h3>
                <p>En Cours</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-green">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['projets_termines'] ?? 0; ?></h3>
                <p>Terminés</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-purple">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['projets_planifies'] ?? 0; ?></h3>
                <p>Planifiés</p>
            </div>
        </div>
    </div>

    <!-- Services d'imprimerie -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-tags"></i> Services d'Imprimerie</h3>
            <button class="btn btn-sm btn-primary" onclick="openModal('addServiceModal')">
                <i class="fas fa-plus"></i> Ajouter
            </button>
        </div>
        <div class="card-content">
            <div class="services-grid">
                <?php foreach ($sous_services as $service): ?>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-print"></i>
                    </div>
                    <h4><?php echo htmlspecialchars($service['nom']); ?></h4>
                    <p><?php echo htmlspecialchars($service['description']); ?></p>
                    <div class="service-meta">
                        <div class="service-price">
                            <strong><?php echo number_format($service['prix_base'], 0, ',', ' '); ?> FCFA</strong>
                            <small>Prix de base</small>
                        </div>
                        <div class="service-duration">
                            <strong><?php echo $service['duree_estimee']; ?> jours</strong>
                            <small>Durée estimée</small>
                        </div>
                    </div>
                    <div class="service-stats">
                        <span class="badge <?php echo $service['actif'] ? 'status-active' : 'status-inactif'; ?>">
                            <?php echo $service['actif'] ? 'Actif' : 'Inactif'; ?>
                        </span>
                        <span class="project-count"><?php echo $service['nb_projets']; ?> projets</span>
                    </div>
                    <div class="service-actions">
                        <button class="btn btn-sm btn-outline" onclick="editService(<?php echo $service['id']; ?>)">
                            <i class="fas fa-edit"></i> Modifier
                        </button>
                        <button class="btn btn-sm btn-outline" onclick="toggleService(<?php echo $service['id']; ?>, <?php echo $service['actif'] ? 0 : 1; ?>)">
                            <i class="fas fa-<?php echo $service['actif'] ? 'eye-slash' : 'eye'; ?>"></i>
                            <?php echo $service['actif'] ? 'Désactiver' : 'Activer'; ?>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="filters-bar">
        <div class="filter-tabs">
            <a href="?status=" class="filter-tab <?php echo empty($status_filter) ? 'active' : ''; ?>">
                Tous
            </a>
            <a href="?status=planifie" class="filter-tab <?php echo $status_filter === 'planifie' ? 'active' : ''; ?>">
                Planifiés
            </a>
            <a href="?status=en_cours" class="filter-tab <?php echo $status_filter === 'en_cours' ? 'active' : ''; ?>">
                En cours
            </a>
            <a href="?status=termine" class="filter-tab <?php echo $status_filter === 'termine' ? 'active' : ''; ?>">
                Terminés
            </a>
        </div>
        <div class="filter-actions">
            <form method="GET" class="filter-form">
                <select name="service" class="filter-select" onchange="this.form.submit()">
                    <option value="">Tous les services</option>
                    <?php foreach ($sous_services as $service): ?>
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

    <!-- Liste des projets -->
    <div class="projects-grid">
        <?php if (empty($projets)): ?>
            <div class="empty-state">
                <i class="fas fa-print"></i>
                <h3>Aucun projet d'imprimerie</h3>
                <p>Aucun projet d'imprimerie trouvé avec les critères sélectionnés.</p>
            </div>
        <?php else: ?>
            <?php foreach ($projets as $projet): ?>
                <div class="project-card">
                    <div class="project-header">
                        <div class="project-title">
                            <h4><?php echo htmlspecialchars($projet['nom']); ?></h4>
                            <span class="project-type"><?php echo htmlspecialchars($projet['service_nom'] ?? 'Imprimerie'); ?></span>
                        </div>
                        <div class="project-status">
                            <span class="status-badge status-<?php echo $projet['statut']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $projet['statut'])); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="project-content">
                        <div class="client-info">
                            <div class="detail-row">
                                <span class="label">Client:</span>
                                <span class="value"><?php echo htmlspecialchars($projet['client_nom']); ?></span>
                            </div>
                            <?php if (!empty($projet['entreprise'])): ?>
                            <div class="detail-row">
                                <span class="label">Entreprise:</span>
                                <span class="value"><?php echo htmlspecialchars($projet['entreprise']); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="detail-row">
                                <span class="label">Responsable:</span>
                                <span class="value"><?php echo $projet['responsable_nom'] ? htmlspecialchars($projet['responsable_nom']) : 'Non assigné'; ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Budget:</span>
                                <span class="value"><?php echo number_format($projet['montant_final'] ?? 0, 0, ',', ' '); ?> FCFA</span>
                            </div>
                        </div>
                        
                        <div class="progress-container">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $projet['progression']; ?>%"></div>
                            </div>
                            <span class="progress-text"><?php echo $projet['progression']; ?>%</span>
                        </div>
                        
                        <div class="project-dates">
                            <small>Créé le <?php echo date('d/m/Y', strtotime($projet['date_creation'])); ?></small>
                            <?php if ($projet['date_fin_prevue']): ?>
                                <small>Échéance: <?php echo date('d/m/Y', strtotime($projet['date_fin_prevue'])); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="project-actions">
                        <button class="btn btn-sm btn-outline" onclick="viewProject(<?php echo $projet['id']; ?>)">
                            <i class="fas fa-eye"></i> Voir
                        </button>
                        <button class="btn btn-sm btn-outline" onclick="updateStatus(<?php echo $projet['id']; ?>, '<?php echo $projet['statut']; ?>', <?php echo $projet['progression']; ?>)">
                            <i class="fas fa-sync-alt"></i> Statut
                        </button>
                        <button class="btn btn-sm btn-outline" onclick="assignAdmin(<?php echo $projet['id']; ?>, <?php echo $projet['admin_responsable'] ?? 'null'; ?>)">
                            <i class="fas fa-user-tag"></i> Assigner
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
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&service=<?php echo urlencode($service_filter); ?>" 
                       class="<?php echo $page == $i ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>
</main>

<!-- Modal Ajouter Service -->
<div class="modal" id="addServiceModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Ajouter un Service d'Imprimerie</h3>
            <button class="close" onclick="closeModal('addServiceModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="addServiceForm" method="POST" action="ajax/save_service.php">
                <input type="hidden" name="service_id" value="4">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nom">Nom du service *</label>
                        <input type="text" id="nom" name="nom" required>
                    </div>
                    <div class="form-group">
                        <label for="slug">Slug *</label>
                        <input type="text" id="slug" name="slug" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" rows="3" required></textarea>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="prix_base">Prix de base (FCFA) *</label>
                        <input type="number" id="prix_base" name="prix_base" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="duree_estimee">Durée estimée (jours) *</label>
                        <input type="number" id="duree_estimee" name="duree_estimee" min="1" required>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal('addServiceModal')">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Mettre à jour le statut -->
<div class="modal" id="updateStatusModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Mettre à jour le statut</h3>
            <button class="close" onclick="closeModal('updateStatusModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="projet_id" id="status_projet_id">
                <div class="form-group">
                    <label for="statut">Statut</label>
                    <select id="statut" name="statut" required>
                        <option value="planifie">Planifié</option>
                        <option value="en_cours">En cours</option>
                        <option value="en_pause">En pause</option>
                        <option value="termine">Terminé</option>
                        <option value="annule">Annulé</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="progression">Progression (%)</label>
                    <input type="range" id="progression" name="progression" min="0" max="100" step="5" value="0" oninput="updateProgressValue(this.value)">
                    <div class="progress-display">
                        <span id="progressionValue">0%</span>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal('updateStatusModal')">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Assigner un responsable -->
<div class="modal" id="assignAdminModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Assigner un responsable</h3>
            <button class="close" onclick="closeModal('assignAdminModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="assign_admin">
                <input type="hidden" name="projet_id" id="assign_projet_id">
                <div class="form-group">
                    <label for="admin_id">Responsable</label>
                    <select id="admin_id" name="admin_id" required>
                        <option value="">Sélectionner un responsable</option>
                        <?php foreach ($admins as $admin): ?>
                            <option value="<?php echo $admin['id']; ?>"><?php echo htmlspecialchars($admin['nom']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal('assignAdminModal')">Annuler</button>
                    <button type="submit" class="btn btn-primary">Assigner</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Fonctions JavaScript
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function updateStatus(id, currentStatus, currentProgress) {
    document.getElementById('status_projet_id').value = id;
    document.getElementById('statut').value = currentStatus;
    document.getElementById('progression').value = currentProgress;
    document.getElementById('progressionValue').textContent = currentProgress + '%';
    openModal('updateStatusModal');
}

function assignAdmin(id, currentAdmin) {
    document.getElementById('assign_projet_id').value = id;
    if (currentAdmin && currentAdmin !== 'null') {
        document.getElementById('admin_id').value = currentAdmin;
    }
    openModal('assignAdminModal');
}

function updateProgressValue(value) {
    document.getElementById('progressionValue').textContent = value + '%';
}

function viewProject(id) {
    window.location.href = 'projet-details.php?id=' + id;
}

function editService(id) {
    window.location.href = 'edit-service.php?id=' + id;
}

function toggleService(id, status) {
    if (confirm('Êtes-vous sûr de vouloir ' + (status ? 'activer' : 'désactiver') + ' ce service ?')) {
        // Envoyer la requête AJAX
        fetch('ajax/toggle_service.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id: id,
                status: status
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        });
    }
}

function exportData() {
    window.open('ajax/export_imprimerie.php', '_blank');
}

// Génération automatique du slug
document.getElementById('nom')?.addEventListener('input', function() {
    const nom = this.value;
    const slug = nom.toLowerCase()
        .replace(/[^\w\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/--+/g, '-');
    document.getElementById('slug').value = slug;
});

// Fermer les modals en cliquant à l'extérieur
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
    }
});
</script>

<?php $conn->close(); ?>
