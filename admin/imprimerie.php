<?php
// Gestion des services d'imprimerie - Divine Art Corporation
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

// Récupération des statistiques d'imprimerie
$stats = [];
$query = "SELECT COUNT(*) as total_projets, 
          SUM(CASE WHEN p.statut = 'en_cours' THEN 1 ELSE 0 END) as projets_en_cours,
          SUM(CASE WHEN p.statut = 'termine' THEN 1 ELSE 0 END) as projets_termines
          FROM projets p 
          JOIN devis d ON p.devis_id = d.id 
          WHERE d.service = 'imprimerie'";
$result = $conn->query($query);
if ($result) {
    $stats = $result->fetch_assoc();
}

// Récupération des sous-services d'imprimerie
$sous_services = [];
$query = "SELECT * FROM sous_services WHERE service_id = (SELECT id FROM services WHERE slug = 'imprimerie') ORDER BY ordre ASC";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $sous_services[] = $row;
    }
}

// Récupération des projets d'imprimerie avec pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';

$where_clause = "d.service = 'imprimerie'";
if (!empty($search)) {
    $where_clause .= " AND (p.nom LIKE '%$search%' OR d.nom LIKE '%$search%' OR d.entreprise LIKE '%$search%')";
}
if (!empty($status_filter)) {
    $where_clause .= " AND p.statut = '$status_filter'";
}

$query = "SELECT p.*, d.nom as client_nom, d.entreprise, d.email, d.telephone, a.nom as responsable_nom
          FROM projets p 
          JOIN devis d ON p.devis_id = d.id 
          LEFT JOIN admins a ON p.admin_responsable = a.id
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
        // Sécuriser les données
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
                
                // Enregistrer dans les logs
                logActivity($_SESSION['admin_id'], 'update_status', 'projets', $projet_id, "Statut mis à jour: $statut, Progression: $progression%");
            } else {
                $message = "Erreur lors de la mise à jour du statut: " . $stmt->error;
                $message_type = "danger";
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
                $message = "Le responsable du projet a été assigné avec succès.";
                $message_type = "success";
                
                // Enregistrer dans les logs
                logActivity($_SESSION['admin_id'], 'assign_admin', 'projets', $projet_id, "Admin assigné: $admin_id");
            } else {
                $message = "Erreur lors de l'assignation du responsable: " . $stmt->error;
                $message_type = "danger";
            }
            $stmt->close();
        }
    }
}

// Récupérer la liste des administrateurs pour l'assignation
$admins = [];
$query = "SELECT id, nom FROM admins WHERE statut = 'actif' ORDER BY nom ASC";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
}

// Titre de la page
$page_title = "Gestion des Services d'Imprimerie";
// Inclure l'en-tête et la barre latérale
require_once 'header.php';
require_once 'sidebar.php';
?>



<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-print mr-2"></i> Gestion des Services d'Imprimerie
        </h1>
        <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#addServiceModal">
            <i class="fas fa-plus-circle"></i> Ajouter un service
        </a>
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
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Projets</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_projets'] ?? 0; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
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

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Projets En Cours</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['projets_en_cours'] ?? 0; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-spinner fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Services Proposés</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($sous_services); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tags fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Services d'imprimerie -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Services d'Imprimerie Proposés</h6>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in"
                    aria-labelledby="dropdownMenuLink">
                    <div class="dropdown-header">Actions:</div>
                    <a class="dropdown-item" href="#" data-toggle="modal" data-target="#addServiceModal">Ajouter un service</a>
                    <a class="dropdown-item" href="#">Réorganiser</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="#">Exporter la liste</a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($sous_services as $service): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card border-left-primary h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title font-weight-bold"><?php echo htmlspecialchars($service['nom']); ?></h5>
                                <span class="badge badge-<?php echo $service['actif'] ? 'success' : 'danger'; ?>">
                                    <?php echo $service['actif'] ? 'Actif' : 'Inactif'; ?>
                                </span>
                            </div>
                            <p class="card-text text-gray-600"><?php echo htmlspecialchars($service['description']); ?></p>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>
                                    <span class="font-weight-bold"><?php echo number_format($service['prix_base'], 0, ',', ' '); ?> FCFA</span>
                                    <small class="text-muted ml-2">(base)</small>
                                </div>
                                <div>
                                    <span class="text-info"><?php echo $service['duree_estimee']; ?> jours</span>
                                    <small class="text-muted ml-1">estimés</small>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0 d-flex justify-content-between">
                            <button class="btn btn-sm btn-outline-primary" onclick="editService(<?php echo $service['id']; ?>)">
                                <i class="fas fa-edit"></i> Modifier
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="confirmDeleteService(<?php echo $service['id']; ?>, '<?php echo addslashes($service['nom']); ?>')">
                                <i class="fas fa-trash"></i> Supprimer
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Filtres et recherche -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Projets d'Imprimerie</h6>
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
                        <select class="form-control" name="status" onchange="this.form.submit()">
                            <option value="">Tous les statuts</option>
                            <option value="planifie" <?php echo $status_filter === 'planifie' ? 'selected' : ''; ?>>Planifié</option>
                            <option value="en_cours" <?php echo $status_filter === 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                            <option value="en_pause" <?php echo $status_filter === 'en_pause' ? 'selected' : ''; ?>>En pause</option>
                            <option value="termine" <?php echo $status_filter === 'termine' ? 'selected' : ''; ?>>Terminé</option>
                            <option value="annule" <?php echo $status_filter === 'annule' ? 'selected' : ''; ?>>Annulé</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button type="submit" class="btn btn-primary btn-block">Filtrer</button>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="imprimerie.php" class="btn btn-secondary btn-block">Réinitialiser</a>
                    </div>
                </div>
            </form>

            <!-- Liste des projets -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="projetsTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>Projet</th>
                            <th>Client</th>
                            <th>Statut</th>
                            <th>Progression</th>
                            <th>Responsable</th>
                            <th>Dates</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($projets)): ?>
                            <tr>
                                <td colspan="8" class="text-center">Aucun projet d'imprimerie trouvé</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($projets as $projet): ?>
                                <tr>
                                    <td><?php echo $projet['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($projet['nom']); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($projet['client_nom']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($projet['entreprise']); ?></small>
                                    </td>
                                    <td>
                                        <?php 
                                        $status_class = '';
                                        switch ($projet['statut']) {
                                            case 'planifie': $status_class = 'secondary'; break;
                                            case 'en_cours': $status_class = 'primary'; break;
                                            case 'en_pause': $status_class = 'warning'; break;
                                            case 'termine': $status_class = 'success'; break;
                                            case 'annule': $status_class = 'danger'; break;
                                        }
                                        ?>
                                        <span class="badge badge-<?php echo $status_class; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $projet['statut'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar bg-<?php echo $status_class; ?>" role="progressbar" 
                                                style="width: <?php echo $projet['progression']; ?>%" 
                                                aria-valuenow="<?php echo $projet['progression']; ?>" aria-valuemin="0" aria-valuemax="100">
                                                <?php echo $projet['progression']; ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($projet['responsable_nom']): ?>
                                            <?php echo htmlspecialchars($projet['responsable_nom']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Non assigné</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small>Début: <?php echo date('d/m/Y', strtotime($projet['date_debut'] ?? $projet['date_creation'])); ?></small><br>
                                        <small>Fin prévue: <?php echo $projet['date_fin_prevue'] ? date('d/m/Y', strtotime($projet['date_fin_prevue'])) : 'Non définie'; ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-primary" onclick="viewProject(<?php echo $projet['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-info" onclick="updateStatus(<?php echo $projet['id']; ?>, '<?php echo $projet['statut']; ?>', <?php echo $projet['progression']; ?>)">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-warning" onclick="assignAdmin(<?php echo $projet['id']; ?>, <?php echo $projet['admin_responsable'] ?? 'null'; ?>)">
                                                <i class="fas fa-user-tag"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" aria-label="Précédent">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" aria-label="Suivant">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Ajouter Service -->
<div class="modal fade" id="addServiceModal" tabindex="-1" role="dialog" aria-labelledby="addServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addServiceModalLabel">Ajouter un Service d'Imprimerie</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addServiceForm" method="POST" action="ajax/save_service.php">
                <div class="modal-body">
                    <input type="hidden" name="service_id" value="4"> <!-- ID du service Imprimerie -->
                    <div class="form-group">
                        <label for="nom">Nom du service</label>
                        <input type="text" class="form-control" id="nom" name="nom" required>
                    </div>
                    <div class="form-group">
                        <label for="slug">Slug</label>
                        <input type="text" class="form-control" id="slug" name="slug" required>
                        <small class="form-text text-muted">Identifiant unique pour les URLs (ex: impression-grand-format)</small>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="prix_base">Prix de base (FCFA)</label>
                                <input type="number" class="form-control" id="prix_base" name="prix_base" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="duree_estimee">Durée estimée (jours)</label>
                                <input type="number" class="form-control" id="duree_estimee" name="duree_estimee" min="1" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="actif" name="actif" value="1" checked>
                            <label class="custom-control-label" for="actif">Service actif</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Mettre à jour le statut -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" role="dialog" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateStatusModalLabel">Mettre à jour le statut du projet</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="projet_id" id="status_projet_id">
                    <div class="form-group">
                        <label for="statut">Statut</label>
                        <select class="form-control" id="statut" name="statut" required>
                            <option value="planifie">Planifié</option>
                            <option value="en_cours">En cours</option>
                            <option value="en_pause">En pause</option>
                            <option value="termine">Terminé</option>
                            <option value="annule">Annulé</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="progression">Progression (%)</label>
                        <input type="range" class="custom-range" id="progression" name="progression" min="0" max="100" step="5" value="0" oninput="progressionValue.innerText = this.value + '%'">
                        <div class="text-center mt-2">
                            <span id="progressionValue">0%</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Assigner un responsable -->
<div class="modal fade" id="assignAdminModal" tabindex="-1" role="dialog" aria-labelledby="assignAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assignAdminModalLabel">Assigner un responsable</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="assign_admin">
                    <input type="hidden" name="projet_id" id="assign_projet_id">
                    <div class="form-group">
                        <label for="admin_id">Responsable</label>
                        <select class="form-control" id="admin_id" name="admin_id" required>
                            <option value="">Sélectionner un responsable</option>
                            <?php foreach ($admins as $admin): ?>
                                <option value="<?php echo $admin['id']; ?>"><?php echo htmlspecialchars($admin['nom']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Assigner</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Voir Projet -->
<div class="modal fade" id="viewProjectModal" tabindex="-1" role="dialog" aria-labelledby="viewProjectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
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

<!-- JavaScript pour les interactions -->
<script>
// Fonction pour générer automatiquement le slug à partir du nom
document.getElementById('nom')?.addEventListener('input', function() {
    const nom = this.value;
    const slug = nom.toLowerCase()
        .replace(/[^\w\s-]/g, '') // Supprimer les caractères spéciaux
        .replace(/\s+/g, '-')     // Remplacer les espaces par des tirets
        .replace(/--+/g, '-');    // Éviter les tirets multiples
    document.getElementById('slug').value = slug;
});

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

// Fonction pour mettre à jour le statut d'un projet
function updateStatus(id, currentStatus, currentProgress) {
    $('#status_projet_id').val(id);
    $('#statut').val(currentStatus);
    $('#progression').val(currentProgress);
    $('#progressionValue').text(currentProgress + '%');
    $('#updateStatusModal').modal('show');
}

// Fonction pour assigner un responsable
function assignAdmin(id, currentAdmin) {
    $('#assign_projet_id').val(id);
    if (currentAdmin) {
        $('#admin_id').val(currentAdmin);
    } else {
        $('#admin_id').val('');
    }
    $('#assignAdminModal').modal('show');
}

// Fonction pour éditer un service
function editService(id) {
    // Rediriger vers la page d'édition ou charger les données via AJAX
    window.location.href = 'edit_service.php?id=' + id;
}

// Fonction pour confirmer la suppression d'un service
function confirmDeleteService(id, nom) {
    if (confirm('Êtes-vous sûr de vouloir supprimer le service "' + nom + '" ?')) {
        // Envoyer la requête de suppression via AJAX
        $.ajax({
            url: 'ajax/delete_service.php',
            type: 'POST',
            data: { id: id },
            success: function(response) {
                const result = JSON.parse(response);
                if (result.success) {
                    alert('Service supprimé avec succès.');
                    location.reload();
                } else {
                    alert('Erreur: ' + result.message);
                }
            },
            error: function() {
                alert('Erreur lors de la suppression du service.');
            }
        });
    }
}
</script>

<?php
// Fermer la connexion à la base de données
$conn->close();
?>

<?php include 'footer.php'; ?>
