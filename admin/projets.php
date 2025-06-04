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
$page_title = "Gestion des Projets";

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_status':
                $projet_id = (int)$_POST['projet_id'];
                $statut = mysqli_real_escape_string($conn, $_POST['statut']);
                $progression = (int)$_POST['progression'];
                
                $query = "UPDATE projets SET statut = ?, progression = ?, date_modification = NOW() WHERE id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "sii", $statut, $progression, $projet_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    // Log de l'activité
                    logActivity($current_admin['id'], 'update_project_status', 'projets', $projet_id, "Statut mis à jour: $statut ($progression%)");
                    $success_message = "Statut du projet mis à jour avec succès.";
                } else {
                    $error_message = "Erreur lors de la mise à jour du statut.";
                }
                break;
                
            case 'assign_admin':
                $projet_id = (int)$_POST['projet_id'];
                $admin_id = (int)$_POST['admin_id'];
                
                $query = "UPDATE projets SET admin_responsable = ?, date_modification = NOW() WHERE id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "ii", $admin_id, $projet_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    logActivity($current_admin['id'], 'assign_project', 'projets', $projet_id, "Projet assigné à l'admin ID: $admin_id");
                    $success_message = "Projet assigné avec succès.";
                } else {
                    $error_message = "Erreur lors de l'assignation.";
                }
                break;
                
            case 'add_task':
                $projet_id = (int)$_POST['projet_id'];
                $nom = mysqli_real_escape_string($conn, $_POST['nom']);
                $description = mysqli_real_escape_string($conn, $_POST['description']);
                $priorite = mysqli_real_escape_string($conn, $_POST['priorite']);
                $date_fin_prevue = $_POST['date_fin_prevue'];
                $temps_estime = (int)$_POST['temps_estime'];
                
                $query = "INSERT INTO taches (projet_id, nom, description, priorite, date_fin_prevue, temps_estime, admin_assigne) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "isssiii", $projet_id, $nom, $description, $priorite, $date_fin_prevue, $temps_estime, $current_admin['id']);
                
                if (mysqli_stmt_execute($stmt)) {
                    logActivity($current_admin['id'], 'add_task', 'taches', mysqli_insert_id($conn), "Tâche ajoutée: $nom");
                    $success_message = "Tâche ajoutée avec succès.";
                } else {
                    $error_message = "Erreur lors de l'ajout de la tâche.";
                }
                break;
        }
    }
}

// Paramètres de pagination et filtres
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$admin_filter = isset($_GET['admin']) ? (int)$_GET['admin'] : 0;

// Construction de la requête
$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(p.nom LIKE ? OR d.nom LIKE ? OR d.entreprise LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

if (!empty($status_filter)) {
    $where_conditions[] = "p.statut = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($admin_filter > 0) {
    $where_conditions[] = "p.admin_responsable = ?";
    $params[] = $admin_filter;
    $types .= 'i';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Requête pour compter le total
$count_query = "SELECT COUNT(*) as total FROM projets p 
                LEFT JOIN devis d ON p.devis_id = d.id 
                LEFT JOIN admins a ON p.admin_responsable = a.id 
                $where_clause";

if (!empty($params)) {
    $count_stmt = mysqli_prepare($conn, $count_query);
    mysqli_stmt_bind_param($count_stmt, $types, ...$params);
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
} else {
    $count_result = mysqli_query($conn, $count_query);
}

$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $per_page);

// Requête principale
$query = "SELECT p.*, d.nom as client_nom, d.email as client_email, d.entreprise, 
                 a.nom as admin_nom, d.numero_devis,
                 (SELECT COUNT(*) FROM taches WHERE projet_id = p.id) as nb_taches,
                 (SELECT COUNT(*) FROM taches WHERE projet_id = p.id AND statut = 'termine') as taches_terminees
          FROM projets p 
          LEFT JOIN devis d ON p.devis_id = d.id 
          LEFT JOIN admins a ON p.admin_responsable = a.id 
          $where_clause
          ORDER BY p.date_creation DESC 
          LIMIT ? OFFSET ?";

$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = mysqli_prepare($conn, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$projets = mysqli_stmt_get_result($stmt);

// Récupérer les admins pour les filtres et assignations
$admins_query = "SELECT id, nom FROM admins WHERE statut = 'actif' ORDER BY nom";
$admins_result = mysqli_query($conn, $admins_query);
$admins = mysqli_fetch_all($admins_result, MYSQLI_ASSOC);

// Statistiques
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN statut = 'planifie' THEN 1 ELSE 0 END) as planifies,
    SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) as en_cours,
    SUM(CASE WHEN statut = 'en_pause' THEN 1 ELSE 0 END) as en_pause,
    SUM(CASE WHEN statut = 'termine' THEN 1 ELSE 0 END) as termines,
    SUM(CASE WHEN statut = 'annule' THEN 1 ELSE 0 END) as annules,
    AVG(progression) as progression_moyenne,
    SUM(budget_alloue) as budget_total,
    SUM(cout_reel) as cout_total
FROM projets";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

include 'header.php';
?>

<div class="admin-content">
    <?php include 'sidebar.php'; ?>
    
    <main class="main-content">
        <div class="content-header">
            <div class="header-left">
                <h1><i class="fas fa-project-diagram"></i> <?php echo $page_title; ?></h1>
                <p>Gérez vos projets et suivez leur progression</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="showCreateProjectModal()">
                    <i class="fas fa-plus"></i> Nouveau Projet
                </button>
            </div>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon bg-blue">
                    <i class="fas fa-project-diagram"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['total']); ?></h3>
                    <p>Total Projets</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-orange">
                    <i class="fas fa-play"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['en_cours']); ?></h3>
                    <p>En Cours</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-green">
                    <i class="fas fa-check"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['termines']); ?></h3>
                    <p>Terminés</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-purple">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['progression_moyenne'], 1); ?>%</h3>
                    <p>Progression Moyenne</p>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="filters-section">
            <form method="GET" class="filters-form">
                <div class="filter-group">
                    <input type="text" name="search" placeholder="Rechercher..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-group">
                    <select name="status">
                        <option value="">Tous les statuts</option>
                        <option value="planifie" <?php echo $status_filter === 'planifie' ? 'selected' : ''; ?>>Planifié</option>
                        <option value="en_cours" <?php echo $status_filter === 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                        <option value="en_pause" <?php echo $status_filter === 'en_pause' ? 'selected' : ''; ?>>En pause</option>
                        <option value="termine" <?php echo $status_filter === 'termine' ? 'selected' : ''; ?>>Terminé</option>
                        <option value="annule" <?php echo $status_filter === 'annule' ? 'selected' : ''; ?>>Annulé</option>
                    </select>
                </div>
                <div class="filter-group">
                    <select name="admin">
                        <option value="">Tous les responsables</option>
                        <?php foreach ($admins as $admin): ?>
                            <option value="<?php echo $admin['id']; ?>" 
                                    <?php echo $admin_filter == $admin['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($admin['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Filtrer
                </button>
                <a href="projets.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Reset
                </a>
            </form>
        </div>

        <!-- Liste des projets -->
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Projet</th>
                        <th>Client</th>
                        <th>Statut</th>
                        <th>Progression</th>
                        <th>Responsable</th>
                        <th>Tâches</th>
                        <th>Budget</th>
                        <th>Dates</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($projet = mysqli_fetch_assoc($projets)): ?>
                        <tr>
                            <td>
                                <div class="project-info">
                                    <strong><?php echo htmlspecialchars($projet['nom']); ?></strong>
                                    <small>Devis: <?php echo htmlspecialchars($projet['numero_devis']); ?></small>
                                </div>
                            </td>
                            <td>
                                <div class="client-info">
                                    <strong><?php echo htmlspecialchars($projet['client_nom']); ?></strong>
                                    <?php if ($projet['entreprise']): ?>
                                        <small><?php echo htmlspecialchars($projet['entreprise']); ?></small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $projet['statut']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $projet['statut'])); ?>
                                </span>
                            </td>
                            <td>
                                <div class="progress-container">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $projet['progression']; ?>%"></div>
                                    </div>
                                    <span class="progress-text"><?php echo $projet['progression']; ?>%</span>
                                </div>
                            </td>
                            <td>
                                <?php echo $projet['admin_nom'] ? htmlspecialchars($projet['admin_nom']) : 'Non assigné'; ?>
                            </td>
                            <td>
                                <span class="task-count">
                                    <?php echo $projet['taches_terminees']; ?>/<?php echo $projet['nb_taches']; ?>
                                </span>
                            </td>
                            <td>
                                <div class="budget-info">
                                    <?php if ($projet['budget_alloue']): ?>
                                        <strong><?php echo number_format($projet['budget_alloue'], 0, ',', ' '); ?> FCFA</strong>
                                    <?php endif; ?>
                                    <?php if ($projet['cout_reel']): ?>
                                        <small>Coût: <?php echo number_format($projet['cout_reel'], 0, ',', ' '); ?> FCFA</small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="date-info">
                                    <?php if ($projet['date_debut']): ?>
                                        <small>Début: <?php echo date('d/m/Y', strtotime($projet['date_debut'])); ?></small>
                                    <?php endif; ?>
                                    <?php if ($projet['date_fin_prevue']): ?>
                                        <small>Fin prévue: <?php echo date('d/m/Y', strtotime($projet['date_fin_prevue'])); ?></small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-primary" onclick="viewProject(<?php echo $projet['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-secondary" onclick="editProject(<?php echo $projet['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-info" onclick="manageTasks(<?php echo $projet['id']; ?>)">
                                        <i class="fas fa-tasks"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&admin=<?php echo $admin_filter; ?>" 
                       class="<?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<!-- Modal pour voir les détails du projet -->
<div id="projectModal" class="modal">
    <div class="modal-content large">
        <div class="modal-header">
            <h2>Détails du Projet</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body" id="projectModalBody">
            <!-- Contenu chargé dynamiquement -->
        </div>
    </div>
</div>

<!-- Modal pour gérer les tâches -->
<div id="tasksModal" class="modal">
    <div class="modal-content large">
        <div class="modal-header">
            <h2>Gestion des Tâches</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body" id="tasksModalBody">
            <!-- Contenu chargé dynamiquement -->
        </div>
    </div>
</div>

<script>
function viewProject(id) {
    fetch(`../api/get_project_details.php?id=${id}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('projectModalBody').innerHTML = data;
            document.getElementById('projectModal').style.display = 'block';
        });
}

function editProject(id) {
    // Rediriger vers la page d'édition ou ouvrir un modal d'édition
    window.location.href = `edit_project.php?id=${id}`;
}

function manageTasks(id) {
    fetch(`../api/get_project_tasks.php?id=${id}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('tasksModalBody').innerHTML = data;
            document.getElementById('tasksModal').style.display = 'block';
        });
}

function updateProjectStatus(projectId, status, progression) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="projet_id" value="${projectId}">
        <input type="hidden" name="statut" value="${status}">
        <input type="hidden" name="progression" value="${progression}">
    `;
    document.body.appendChild(form);
    form.submit();
}

// Gestion des modals
document.querySelectorAll('.close').forEach(closeBtn => {
    closeBtn.onclick = function() {
        this.closest('.modal').style.display = 'none';
    }
});

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>

