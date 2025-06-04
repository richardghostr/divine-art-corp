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
    COALESCE(AVG(progression), 0) as progression_moyenne,
    COALESCE(SUM(budget_alloue), 0) as budget_total,
    COALESCE(SUM(cout_reel), 0) as cout_total
FROM projets";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

include 'header.php';
?>

<div class="admin-main">
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
                    <h3><?php echo isset($stats['total']) ? number_format($stats['total']) : 0; ?></h3>
                    <p>Total Projets</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-orange">
                    <i class="fas fa-play"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo isset($stats['en_cours']) ? number_format($stats['en_cours']) : 0; ?></h3>
                    <p>En Cours</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-green">
                    <i class="fas fa-check"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo isset($stats['termines']) ? number_format($stats['termines']) : 0; ?></h3>
                    <p>Terminés</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-purple">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo isset($stats['progression_moyenne']) ? number_format($stats['progression_moyenne'], 1) : '0.0'; ?>%</h3>
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
                                        <strong><?php echo number_format((float)$projet['budget_alloue'], 0, ',', ' '); ?> FCFA</strong>
                                    <?php else: ?>
                                        <strong>0 FCFA</strong>
                                    <?php endif; ?>
                                    <?php if ($projet['cout_reel']): ?>
                                        <small>Coût: <?php echo number_format((float)$projet['cout_reel'], 0, ',', ' '); ?> FCFA</small>
                                    <?php else: ?>
                                        <small>Coût: 0 FCFA</small>
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
<!-- Ajoutez ce code juste après la balise </main> et avant les autres modals -->
<div id="createProjectModal" class="modal">
    <div class="modal-content large">
        <div class="modal-header">
            <h2>Créer un Nouveau Projet</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form id="createProjectForm" method="POST" action="../api/create_project.php">
                <div class="form-group">
                    <label for="nom">Nom du projet *</label>
                    <input type="text" id="nom" name="nom" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="date_debut">Date de début *</label>
                        <input type="date" id="date_debut" name="date_debut" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="date_fin_prevue">Date de fin prévue</label>
                        <input type="date" id="date_fin_prevue" name="date_fin_prevue">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="budget_alloue">Budget alloué (FCFA)</label>
                        <input type="number" id="budget_alloue" name="budget_alloue" min="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_responsable">Responsable *</label>
                        <select id="admin_responsable" name="admin_responsable" required>
                            <option value="">Sélectionnez un responsable</option>
                            <?php foreach ($admins as $admin): ?>
                                <option value="<?php echo $admin['id']; ?>">
                                    <?php echo htmlspecialchars($admin['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="client_id">Client associé</label>
                    <select id="client_id" name="client_id">
                        <option value="">Sélectionnez un client</option>
                        <?php
                        $clients_query = "SELECT id, nom, entreprise FROM clients ORDER BY nom";
                        $clients_result = mysqli_query($conn, $clients_query);
                        while ($client = mysqli_fetch_assoc($clients_result)): ?>
                            <option value="<?php echo $client['id']; ?>">
                                <?php echo htmlspecialchars($client['nom'] . ' (' . $client['entreprise'] . ')'); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="devis_id">Devis associé</label>
                    <select id="devis_id" name="devis_id">
                        <option value="">Sélectionnez un devis</option>
                        <?php
                        $devis_query = "SELECT id, numero_devis, nom FROM devis ORDER BY date_creation DESC";
                        $devis_result = mysqli_query($conn, $devis_query);
                        while ($devis = mysqli_fetch_assoc($devis_result)): ?>
                            <option value="<?php echo $devis['id']; ?>">
                                <?php echo htmlspecialchars($devis['numero_devis'] . ' - ' . $devis['nom']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeCreateProjectModal()">Annuler</button>
                    <button type="submit" class="btn btn-primary">Créer le Projet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Fonction pour afficher le modal
    function showCreateProjectModal() {
        document.getElementById('createProjectModal').style.display = 'block';
    }

    // Fonction pour fermer le modal
    function closeCreateProjectModal() {
        document.getElementById('createProjectModal').style.display = 'none';
    }

    // Gestion de la soumission du formulaire
    document.getElementById('createProjectForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch(this.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Projet créé avec succès!');
                closeCreateProjectModal();
                // Recharger la page ou mettre à jour le tableau
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Une erreur est survenue');
        });
    });

    // Ajouter la gestion de fermeture pour ce modal
    document.querySelectorAll('#createProjectModal .close').forEach(closeBtn => {
        closeBtn.onclick = function() {
            closeCreateProjectModal();
        }
    });
</script>

<style>
/* Styles supplémentaires pour le formulaire */
.form-group {
    margin-bottom: 1.5rem;
}

.form-row {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.form-row .form-group {
    flex: 1;
    margin-bottom: 0;
}

label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--admin-text-primary);
}

input, select, textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--admin-border);
    border-radius: var(--admin-radius-md);
    background: var(--admin-card-bg);
    font-size: 1rem;
}

input:focus, select:focus, textarea:focus {
    outline: none;
    border-color: var(--admin-accent);
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 2rem;
}
</style>
<script>
    // Dans la section <script> existante
function showCreateProjectModal() {
    document.getElementById('createProjectModal').style.display = 'block';
}
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


<style>
   /* Styles spécifiques pour la page projets.php */

.admin-content {
  display: flex;
  min-height: calc(100vh - var(--admin-header-height));
}


.content-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--admin-space-xl);
    padding: var(--admin-space-lg);
    background: var(--admin-card-bg);
    border-radius: var(--admin-radius-xl);
    box-shadow: var(--admin-shadow-sm);
}


.header-left h1 {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--admin-text-primary);
  margin-bottom: var(--admin-space-xs);
}

.header-left p {
  color: var(--admin-text-secondary);
}

.header-actions .btn {
  display: inline-flex;
  align-items: center;
  gap: var(--admin-space-sm);
}

/* Alertes */
.alert {
  padding: var(--admin-space-md);
  border-radius: var(--admin-radius-md);
  margin-bottom: var(--admin-space-xl);
  display: flex;
  align-items: center;
  gap: var(--admin-space-md);
}

.alert-success {
  background: rgba(39, 174, 96, 0.1);
  color: var(--admin-success);
  border-left: 4px solid var(--admin-success);
}

.alert-error {
  background: rgba(231, 76, 60, 0.1);
  color: var(--admin-danger);
  border-left: 4px solid var(--admin-danger);
}

.alert i {
  font-size: 1.25rem;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: var(--admin-space-xl);
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
  transform: translateY(-2px);
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
  background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
}

.stat-icon.bg-orange {
  background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
}

.stat-icon.bg-green {
  background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
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
}

/* Filtres */
.filters-section {
  margin-bottom: var(--admin-space-2xl);
}

.filters-form {
  display: flex;
  gap: var(--admin-space-md);
  align-items: center;
  flex-wrap: wrap;
}

.filter-group {
  flex: 1;
  min-width: 200px;
}

.filter-group input,
.filter-group select {
  width: 100%;
  padding: var(--admin-space-md);
  border: 1px solid var(--admin-border);
  border-radius: var(--admin-radius-md);
  background: var(--admin-card-bg);
  font-size: 0.875rem;
}

/* Tableau */
.table-container {
  background: var(--admin-card-bg);
  border-radius: var(--admin-radius-xl);
  box-shadow: var(--admin-shadow-sm);
  overflow: hidden;
  margin-bottom: var(--admin-space-xl);
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th,
.data-table td {
  padding: var(--admin-space-lg);
  text-align: left;
  border-bottom: 1px solid var(--admin-border-light);
}

.data-table th {
  background: var(--admin-bg);
  font-weight: 600;
  color: var(--admin-text-primary);
  font-size: 0.875rem;
}

.data-table tr:hover {
  background: var(--admin-border-light);
}

/* Styles spécifiques des cellules */
.project-info {
  display: flex;
  flex-direction: column;
}

.project-info strong {
  margin-bottom: var(--admin-space-xs);
}

.project-info small {
  font-size: 0.75rem;
  color: var(--admin-text-secondary);
}

.client-info {
  display: flex;
  flex-direction: column;
}

.status-badge {
  display: inline-block;
  padding: var(--admin-space-xs) var(--admin-space-sm);
  border-radius: var(--admin-radius-sm);
  font-size: 0.75rem;
  font-weight: 500;
}

.status-badge.status-planifie {
  background: rgba(243, 156, 18, 0.1);
  color: var(--admin-warning);
}

.status-badge.status-en_cours {
  background: rgba(52, 152, 219, 0.1);
  color: var(--admin-info);
}

.status-badge.status-en_pause {
  background: rgba(155, 89, 182, 0.1);
  color: #9b59b6;
}

.status-badge.status-termine {
  background: rgba(46, 204, 113, 0.1);
  color: var(--admin-success);
}

.status-badge.status-annule {
  background: rgba(231, 76, 60, 0.1);
  color: var(--admin-danger);
}

.progress-container {
  display: flex;
  align-items: center;
  gap: var(--admin-space-md);
}

.progress-bar {
  flex: 1;
  height: 6px;
  background: var(--admin-border);
  border-radius: 3px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--admin-accent) 0%, #c0392b 100%);
  border-radius: 3px;
}

.progress-text {
  font-size: 0.875rem;
  color: var(--admin-text-secondary);
  min-width: 40px;
  text-align: right;
}

.task-count {
  font-size: 0.875rem;
  color: var(--admin-text-secondary);
}

.budget-info {
  display: flex;
  flex-direction: column;
}

.budget-info strong {
  font-weight: 600;
}

.budget-info small {
  font-size: 0.75rem;
  color: var(--admin-text-secondary);
}

.date-info {
  display: flex;
  flex-direction: column;
}

.date-info small {
  font-size: 0.75rem;
  color: var(--admin-text-secondary);
}

.action-buttons {
  display: flex;
  gap: var(--admin-space-sm);
}

.action-buttons .btn {
  padding: var(--admin-space-sm);
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Pagination */
.pagination {
  display: flex;
  gap: var(--admin-space-xs);
  justify-content: center;
}

.pagination a {
  padding: var(--admin-space-sm) var(--admin-space-md);
  border-radius: var(--admin-radius-sm);
  background: var(--admin-border-light);
  color: var(--admin-text-secondary);
  text-decoration: none;
  transition: var(--admin-transition);
}

.pagination a:hover {
  background: var(--admin-accent);
  color: white;
}

.pagination a.active {
  background: var(--admin-accent);
  color: white;
  font-weight: 600;
}

/* Modals */
.modal {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
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
}

.modal-content.large {
  width: 800px;
}

.modal-header {
  padding: var(--admin-space-xl);
  border-bottom: 1px solid var(--admin-border-light);
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.modal-header h2 {
  font-size: 1.5rem;
  font-weight: 600;
}

.modal-header .close {
  font-size: 1.5rem;
  cursor: pointer;
  color: var(--admin-text-muted);
}

.modal-body {
  padding: var(--admin-space-xl);
}

/* Responsive */
@media (max-width: 768px) {
  .main-content {
    margin-left: 0;
    padding: var(--admin-space-lg);
  }
  
  .content-header {
    flex-direction: column;
    align-items: flex-start;
    gap: var(--admin-space-lg);
  }
  
  .filters-form {
    flex-direction: column;
    align-items: stretch;
  }
  
  .data-table th, 
  .data-table td {
    padding: var(--admin-space-sm);
    font-size: 0.75rem;
  }
  
  .modal-content.large {
    width: 95%;
  }
} 
</style>