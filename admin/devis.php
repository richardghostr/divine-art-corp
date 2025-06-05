<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once 'header.php';
require_once 'sidebar.php';

// Vérification de l'authentification
$auth = new Auth();
$auth->requireAuth();

$db = Database::getInstance();
$conn = $db->getConnection();
$databaseHelper = new DatabaseHelper();
$success_message = '';
$error_message = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $devis_id = isset($_POST['devis_id']) ? (int)$_POST['devis_id'] : 0;
        
        try {
            switch ($action) {
                case 'update_status':
                    $statut = sanitizeInput($_POST['statut']);
                    $priorite = sanitizeInput($_POST['priorite']);
                    $notes = sanitizeInput($_POST['notes_admin']);
                    $admin_id = $_SESSION['admin']['id'];
                    
                    $result = $databaseHelper->execute(
                        "UPDATE devis SET statut = ?, priorite = ?, notes_admin = ?, admin_assigne = ?, date_modification = NOW() WHERE id = ?",
                        [$statut, $priorite, $notes, $admin_id, $devis_id]
                    );
                    
                    if ($result) {
                        $auth->logActivity($admin_id, 'devis_update_status', 'devis', $devis_id);
                        $success_message = "Statut du devis mis à jour avec succès.";
                    }
                    break;
                    
                case 'update_montant':
                    $montant_estime = (float)sanitizeInput($_POST['montant_estime']);
                    $montant_final = (float)sanitizeInput($_POST['montant_final']);
                    $date_debut = !empty($_POST['date_debut']) ? $_POST['date_debut'] : null;
                    $date_fin_prevue = !empty($_POST['date_fin_prevue']) ? $_POST['date_fin_prevue'] : null;
                    
                    $result = $databaseHelper->execute(
                        "UPDATE devis SET montant_estime = ?, montant_final = ?, date_debut = ?, date_fin_prevue = ?, date_modification = NOW() WHERE id = ?",
                        [$montant_estime, $montant_final, $date_debut, $date_fin_prevue, $devis_id]
                    );
                    
                    if ($result) {
                        $auth->logActivity($_SESSION['admin']['id'], 'devis_update_montant', 'devis', $devis_id);
                        $success_message = "Informations financières mises à jour avec succès.";
                    }
                    break;
                    
                case 'delete':
                    // Vérifier si le devis a des projets associés
                    $projet_count = $databaseHelper->selectOne(
                        "SELECT COUNT(*) as count FROM projets WHERE devis_id = ?",
                        [$devis_id]
                    );
                    
                    if ($projet_count && $projet_count['count'] > 0) {
                        $error_message = "Impossible de supprimer ce devis car il a des projets associés.";
                        break;
                    }
                    
                    $result = $databaseHelper->execute(
                        "DELETE FROM devis WHERE id = ?",
                        [$devis_id]
                    );
                    
                    if ($result) {
                        $auth->logActivity($_SESSION['admin']['id'], 'devis_delete', 'devis', $devis_id);
                        $success_message = "Devis supprimé avec succès.";
                    }
                    break;
                    
                case 'create_project':
                    $nom_projet = sanitizeInput($_POST['nom_projet']);
                    $description = sanitizeInput($_POST['description_projet']);
                    $admin_responsable = $_SESSION['admin']['id'];
                    $budget_alloue = (float)sanitizeInput($_POST['budget_alloue']);
                    
                    // Récupérer les informations du devis
                    $devis = $databaseHelper->selectOne(
                        "SELECT * FROM devis WHERE id = ?",
                        [$devis_id]
                    );
                    
                    if (!$devis) {
                        $error_message = "Devis introuvable.";
                        break;
                    }
                    
                    // Créer le projet
                    $result = $databaseHelper->execute(
                        "INSERT INTO projets (devis_id, nom, description, statut, budget_alloue, admin_responsable, date_debut, date_fin_prevue) 
                         VALUES (?, ?, ?, 'planifie', ?, ?, NOW(), ?)",
                        [$devis_id, $nom_projet, $description, $budget_alloue, $admin_responsable, $devis['date_fin_prevue']]
                    );
                    
                    if ($result) {
                        // Mettre à jour le statut du devis
                        $databaseHelper->execute(
                            "UPDATE devis SET statut = 'en_cours', date_modification = NOW() WHERE id = ?",
                            [$devis_id]
                        );
                        
                        $lastProjectId = $conn->insert_id;
                        $auth->logActivity($admin_responsable, 'projet_create', 'projets', $lastProjectId);
                        $success_message = "Projet créé avec succès.";
                    }
                    break;
            }
        } catch (Exception $e) {
            $error_message = "Erreur lors de l'opération: " . $e->getMessage();
            error_log("Erreur devis: " . $e->getMessage());
        }
    }
}

// Filtres et pagination
$status_filter = $_GET['status'] ?? 'all';
$service_filter = $_GET['service'] ?? 'all';
$search_query = $_GET['search'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Construction de la requête WHERE
$where_conditions = [];
$params = [];
$param_types = '';

if ($status_filter !== 'all') {
    $where_conditions[] = "d.statut = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

if ($service_filter !== 'all') {
    $where_conditions[] = "d.service = ?";
    $params[] = $service_filter;
    $param_types .= 's';
}

if (!empty($search_query)) {
    $where_conditions[] = "(d.nom LIKE ? OR d.email LIKE ? OR d.entreprise LIKE ? OR d.description LIKE ? OR d.numero_devis LIKE ?)";
    $search_param = "%$search_query%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
    $param_types .= 'sssss';
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Statistiques
$stats_query = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN statut = 'nouveau' THEN 1 ELSE 0 END) as nouveaux,
        SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) as en_cours,
        SUM(CASE WHEN statut = 'termine' THEN 1 ELSE 0 END) as termines,
        SUM(CASE WHEN statut = 'annule' THEN 1 ELSE 0 END) as annules,
        COALESCE(SUM(montant_final), 0) as montant_total
    FROM devis
";

$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Récupération des services pour le filtre
$services_query = "SELECT DISTINCT service FROM devis ORDER BY service";
$services_result = $conn->query($services_query);
$services_list = [];
while ($row = $services_result->fetch_assoc()) {
    $services_list[] = $row['service'];
}

// Récupération des devis
$devis_query = "
    SELECT d.*, 
           COALESCE(p.id, 0) as projet_id,
           COALESCE(p.nom, '') as projet_nom,
           COALESCE(p.statut, '') as projet_statut,
           COALESCE(a.nom, 'Non assigné') as admin_nom
    FROM devis d
    LEFT JOIN projets p ON d.id = p.devis_id
    LEFT JOIN admins a ON d.admin_assigne = a.id
    $where_clause 
    ORDER BY d.date_creation DESC 
    LIMIT $per_page OFFSET $offset
";

if (!empty($params)) {
    $stmt = $conn->prepare($devis_query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $devis_result = $stmt->get_result();
} else {
    $devis_result = $conn->query($devis_query);
}

$devis_list = [];
while ($row = $devis_result->fetch_assoc()) {
    $devis_list[] = $row;
}

// Comptage total
$count_query = "SELECT COUNT(*) as total FROM devis d $where_clause";
if (!empty($params)) {
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $count_result = $stmt->get_result();
} else {
    $count_result = $conn->query($count_query);
}

$total_devis = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_devis / $per_page);
?>

<!-- Contenu Principal -->
<main class="admin-main">
    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <div class="section-header">
        <div class="section-title">
            <h2>Gestion des Devis</h2>
            <p>Suivi et traitement des demandes de devis</p>
        </div>
        <div class="section-actions">
            <button class="btn btn-outline" onclick="exportDevis()">
                <i class="fas fa-download"></i>
                Exporter
            </button>
            <a href="nouveau-devis.php" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Nouveau Devis
            </a>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon total">
                <i class="fas fa-file-invoice"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total devis</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon nouveaux">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $stats['nouveaux']; ?></div>
                <div class="stat-label">Nouveaux</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon en-cours">
                <i class="fas fa-spinner"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $stats['en_cours']; ?></div>
                <div class="stat-label">En cours</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon termines">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $stats['termines']; ?></div>
                <div class="stat-label">Terminés</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon montant">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo number_format($stats['montant_total'], 0, ',', ' '); ?> FCFA</div>
                <div class="stat-label">Montant total</div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="filters-bar">
        <div class="filter-tabs">
            <a href="?status=all&service=<?php echo $service_filter; ?>&search=<?php echo urlencode($search_query); ?>" 
               class="filter-tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
                Tous (<?php echo $stats['total']; ?>)
            </a>
            <a href="?status=nouveau&service=<?php echo $service_filter; ?>&search=<?php echo urlencode($search_query); ?>" 
               class="filter-tab <?php echo $status_filter === 'nouveau' ? 'active' : ''; ?>">
                Nouveaux (<?php echo $stats['nouveaux']; ?>)
            </a>
            <a href="?status=en_cours&service=<?php echo $service_filter; ?>&search=<?php echo urlencode($search_query); ?>" 
               class="filter-tab <?php echo $status_filter === 'en_cours' ? 'active' : ''; ?>">
                En cours (<?php echo $stats['en_cours']; ?>)
            </a>
            <a href="?status=termine&service=<?php echo $service_filter; ?>&search=<?php echo urlencode($search_query); ?>" 
               class="filter-tab <?php echo $status_filter === 'termine' ? 'active' : ''; ?>">
                Terminés (<?php echo $stats['termines']; ?>)
            </a>
            <a href="?status=annule&service=<?php echo $service_filter; ?>&search=<?php echo urlencode($search_query); ?>" 
               class="filter-tab <?php echo $status_filter === 'annule' ? 'active' : ''; ?>">
                Annulés (<?php echo $stats['annules']; ?>)
            </a>
        </div>
        <div class="filter-actions">
            <form method="GET" class="filter-form">
                <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                
                <select name="service" class="filter-select" onchange="this.form.submit()">
                    <option value="all" <?php echo $service_filter === 'all' ? 'selected' : ''; ?>>Tous les services</option>
                    <?php foreach ($services_list as $service): ?>
                        <option value="<?php echo htmlspecialchars($service); ?>" <?php echo $service_filter === $service ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($service); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <input type="text" 
                       name="search" 
                       placeholder="Rechercher..." 
                       class="filter-search" 
                       value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="btn btn-outline btn-sm">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- Liste des devis -->
    <div class="devis-list">
        <?php if (empty($devis_list)): ?>
            <div class="empty-state">
                <i class="fas fa-file-invoice"></i>
                <h3>Aucun devis trouvé</h3>
                <p>Aucun devis ne correspond à vos critères.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>N° Devis</th>
                            <th>Client</th>
                            <th>Service</th>
                            <th>Date</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Priorité</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($devis_list as $devis): ?>
                            <tr class="devis-row <?php echo $devis['statut']; ?>">
                                <td>
                                    <strong><?php echo htmlspecialchars($devis['numero_devis']); ?></strong>
                                    <?php if ($devis['projet_id']): ?>
                                        <span class="badge badge-info">
                                            <i class="fas fa-project-diagram"></i> Projet
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="client-info">
                                        <div><?php echo htmlspecialchars($devis['nom']); ?></div>
                                        <small><?php echo htmlspecialchars($devis['email']); ?></small>
                                        <?php if ($devis['entreprise']): ?>
                                            <small class="text-muted"><?php echo htmlspecialchars($devis['entreprise']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="service-badge">
                                        <?php echo htmlspecialchars($devis['service']); ?>
                                    </span>
                                    <?php if ($devis['sous_service']): ?>
                                        <small class="d-block"><?php echo htmlspecialchars($devis['sous_service']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div><?php echo date('d/m/Y', strtotime($devis['date_creation'])); ?></div>
                                    <small class="text-muted"><?php echo date('H:i', strtotime($devis['date_creation'])); ?></small>
                                </td>
                                <td>
                                    <?php if ($devis['montant_final']): ?>
                                        <strong><?php echo number_format($devis['montant_final'], 0, ',', ' '); ?> FCFA</strong>
                                    <?php elseif ($devis['montant_estime']): ?>
                                        <span><?php echo number_format($devis['montant_estime'], 0, ',', ' '); ?> FCFA</span>
                                        <small class="text-muted">Estimé</small>
                                    <?php else: ?>
                                        <span class="text-muted">Non défini</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $devis['statut']; ?>">
                                        <?php 
                                            $statut_labels = [
                                                'nouveau' => 'Nouveau',
                                                'en_cours' => 'En cours',
                                                'termine' => 'Terminé',
                                                'annule' => 'Annulé',
                                                'en_attente' => 'En attente'
                                            ];
                                            echo $statut_labels[$devis['statut']] ?? ucfirst($devis['statut']);
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="priority-badge <?php echo $devis['priorite']; ?>">
                                        <?php 
                                            $priorite_labels = [
                                                'basse' => 'Basse',
                                                'normale' => 'Normale',
                                                'haute' => 'Haute',
                                                'urgente' => 'Urgente'
                                            ];
                                            echo $priorite_labels[$devis['priorite']] ?? ucfirst($devis['priorite']);
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-sm btn-outline" onclick="viewDevis(<?php echo $devis['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-primary" onclick="editDevis(<?php echo $devis['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline dropdown-toggle">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right">
                                                <?php if ($devis['statut'] !== 'termine' && $devis['statut'] !== 'annule'): ?>
                                                    <a class="dropdown-item" onclick="updateStatus(<?php echo $devis['id']; ?>)">
                                                        <i class="fas fa-sync-alt"></i> Changer statut
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($devis['statut'] !== 'annule'): ?>
                                                    <a class="dropdown-item" onclick="updateMontant(<?php echo $devis['id']; ?>)">
                                                        <i class="fas fa-money-bill"></i> Définir montant
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if (!$devis['projet_id'] && $devis['statut'] !== 'annule'): ?>
                                                    <a class="dropdown-item" onclick="createProject(<?php echo $devis['id']; ?>)">
                                                        <i class="fas fa-project-diagram"></i> Créer projet
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($devis['projet_id']): ?>
                                                    <a class="dropdown-item" href="projet.php?id=<?php echo $devis['projet_id']; ?>">
                                                        <i class="fas fa-tasks"></i> Voir projet
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <div class="dropdown-divider"></div>
                                                
                                                <a class="dropdown-item" href="mailto:<?php echo $devis['email']; ?>">
                                                    <i class="fas fa-envelope"></i> Contacter client
                                                </a>
                                                
                                                <a class="dropdown-item" onclick="generatePDF(<?php echo $devis['id']; ?>)">
                                                    <i class="fas fa-file-pdf"></i> Générer PDF
                                                </a>
                                                
                                                <?php if (!$devis['projet_id']): ?>
                                                    <div class="dropdown-divider"></div>
                                                    <a class="dropdown-item text-danger" onclick="deleteDevis(<?php echo $devis['id']; ?>)">
                                                        <i class="fas fa-trash"></i> Supprimer
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination-container">
            <ul class="pagination">
                <?php if ($page > 1): ?>
                    <li>
                        <a href="?status=<?php echo $status_filter; ?>&service=<?php echo $service_filter; ?>&search=<?php echo urlencode($search_query); ?>&page=<?php echo $page - 1; ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $start_page + 4);
                if ($end_page - $start_page < 4) {
                    $start_page = max(1, $end_page - 4);
                }
                ?>
                
                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <li class="<?php echo $i === $page ? 'active' : ''; ?>">
                        <a href="?status=<?php echo $status_filter; ?>&service=<?php echo $service_filter; ?>&search=<?php echo urlencode($search_query); ?>&page=<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <li>
                        <a href="?status=<?php echo $status_filter; ?>&service=<?php echo $service_filter; ?>&search=<?php echo urlencode($search_query); ?>&page=<?php echo $page + 1; ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    <?php endif; ?>
</main>

<!-- Modal détails devis -->
<div id="devisModal" class="modal" style="display: none;">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3 id="devisModalTitle">Détails du devis</h3>
            <button class="modal-close" onclick="closeModal('devisModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="devisModalBody">
            <!-- Contenu dynamique -->
        </div>
    </div>
</div>

<!-- Modal statut -->
<div id="statusModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Modifier le statut</h3>
            <button class="modal-close" onclick="closeModal('statusModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="statusForm" method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="devis_id" id="status_devis_id">
                
                <div class="form-group">
                    <label for="statut">Statut</label>
                    <select name="statut" id="statut" class="form-control" required>
                        <option value="nouveau">Nouveau</option>
                        <option value="en_cours">En cours</option>
                        <option value="termine">Terminé</option>
                        <option value="annule">Annulé</option>
                        <option value="en_attente">En attente</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="priorite">Priorité</label>
                    <select name="priorite" id="priorite" class="form-control" required>
                        <option value="basse">Basse</option>
                        <option value="normale">Normale</option>
                        <option value="haute">Haute</option>
                        <option value="urgente">Urgente</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="notes_admin">Notes administratives</label>
                    <textarea name="notes_admin" id="notes_admin" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal('statusModal')">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal montant -->
<div id="montantModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Définir le montant</h3>
            <button class="modal-close" onclick="closeModal('montantModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="montantForm" method="POST">
                <input type="hidden" name="action" value="update_montant">
                <input type="hidden" name="devis_id" id="montant_devis_id">
                
                <div class="form-group">
                    <label for="montant_estime">Montant estimé (FCFA)</label>
                    <input type="number" name="montant_estime" id="montant_estime" class="form-control" min="0" step="1000">
                </div>
                
                <div class="form-group">
                    <label for="montant_final">Montant final (FCFA)</label>
                    <input type="number" name="montant_final" id="montant_final" class="form-control" min="0" step="1000">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="date_debut">Date de début</label>
                        <input type="date" name="date_debut" id="date_debut" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="date_fin_prevue">Date de fin prévue</label>
                        <input type="date" name="date_fin_prevue" id="date_fin_prevue" class="form-control">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal('montantModal')">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal projet -->
<div id="projectModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Créer un projet</h3>
            <button class="modal-close" onclick="closeModal('projectModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="projectForm" method="POST">
                <input type="hidden" name="action" value="create_project">
                <input type="hidden" name="devis_id" id="project_devis_id">
                
                <div class="form-group">
                    <label for="nom_projet">Nom du projet</label>
                    <input type="text" name="nom_projet" id="nom_projet" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="description_projet">Description</label>
                    <textarea name="description_projet" id="description_projet" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="budget_alloue">Budget alloué (FCFA)</label>
                    <input type="number" name="budget_alloue" id="budget_alloue" class="form-control" min="0" step="1000">
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal('projectModal')">Annuler</button>
                    <button type="submit" class="btn btn-primary">Créer le projet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function viewDevis(id) {
    // Récupérer les détails via AJAX
    fetch(`ajax/get_devis_details.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('devisModalTitle').textContent = `Devis ${data.devis.numero_devis}`;
                
                let fichiers = '';
                if (data.devis.fichiers_joints) {
                    const fichiersList = JSON.parse(data.devis.fichiers_joints);
                    fichiers = `
                        <div class="detail-section">
                            <h4>Fichiers joints</h4>
                            <div class="files-list">
                                ${fichiersList.map(file => `
                                    <div class="file-item">
                                        <i class="fas fa-file"></i>
                                        <span>${file.nom}</span>
                                        <a href="${file.chemin}" target="_blank" class="btn btn-sm btn-outline">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    `;
                }
                
                document.getElementById('devisModalBody').innerHTML = `
                    <div class="devis-details">
                        <div class="detail-section">
                            <h4>Informations client</h4>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <span class="detail-label">Nom</span>
                                    <span class="detail-value">${data.devis.nom}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Email</span>
                                    <span class="detail-value">${data.devis.email}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Téléphone</span>
                                    <span class="detail-value">${data.devis.telephone}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Entreprise</span>
                                    <span class="detail-value">${data.devis.entreprise || 'Non spécifié'}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Poste</span>
                                    <span class="detail-value">${data.devis.poste || 'Non spécifié'}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="detail-section">
                            <h4>Détails de la demande</h4>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <span class="detail-label">Service</span>
                                    <span class="detail-value">${data.devis.service}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Sous-service</span>
                                    <span class="detail-value">${data.devis.sous_service || 'Non spécifié'}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Budget</span>
                                    <span class="detail-value">${data.devis.budget || 'Non spécifié'}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Délai</span>
                                    <span class="detail-value">${data.devis.delai || 'Non spécifié'}</span>
                                </div>
                                <div class="detail-item full-width">
                                    <span class="detail-label">Description</span>
                                    <div class="detail-text">${data.devis.description.replace(/\n/g, '<br>')}</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="detail-section">
                            <h4>Informations administratives</h4>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <span class="detail-label">Statut</span>
                                    <span class="status-badge ${data.devis.statut}">${data.devis.statut}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Priorité</span>
                                    <span class="priority-badge ${data.devis.priorite}">${data.devis.priorite}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Montant estimé</span>
                                    <span class="detail-value">${data.devis.montant_estime ? data.devis.montant_estime + ' FCFA' : 'Non défini'}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Montant final</span>
                                    <span class="detail-value">${data.devis.montant_final ? data.devis.montant_final + ' FCFA' : 'Non défini'}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Date de création</span>
                                    <span class="detail-value">${new Date(data.devis.date_creation).toLocaleString()}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Dernière modification</span>
                                    <span class="detail-value">${data.devis.date_modification ? new Date(data.devis.date_modification).toLocaleString() : 'Aucune'}</span>
                                </div>
                                <div class="detail-item full-width">
                                    <span class="detail-label">Notes administratives</span>
                                    <div class="detail-text">${data.devis.notes_admin ? data.devis.notes_admin.replace(/\n/g, '<br>') : 'Aucune note'}</div>
                                </div>
                            </div>
                        </div>
                        
                        ${fichiers}
                    </div>
                `;
                
                document.getElementById('devisModal').style.display = 'flex';
            }
        })
        .catch(error => console.error('Erreur:', error));
}

function updateStatus(id) {
    // Récupérer les détails via AJAX pour pré-remplir le formulaire
    fetch(`ajax/get_devis_details.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('status_devis_id').value = id;
                document.getElementById('statut').value = data.devis.statut;
                document.getElementById('priorite').value = data.devis.priorite;
                document.getElementById('notes_admin').value = data.devis.notes_admin || '';
                
                document.getElementById('statusModal').style.display = 'flex';
            }
        })
        .catch(error => console.error('Erreur:', error));
}

function updateMontant(id) {
    // Récupérer les détails via AJAX pour pré-remplir le formulaire
    fetch(`ajax/get_devis_details.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('montant_devis_id').value = id;
                document.getElementById('montant_estime').value = data.devis.montant_estime || '';
                document.getElementById('montant_final').value = data.devis.montant_final || '';
                document.getElementById('date_debut').value = data.devis.date_debut || '';
                document.getElementById('date_fin_prevue').value = data.devis.date_fin_prevue || '';
                
                document.getElementById('montantModal').style.display = 'flex';
            }
        })
        .catch(error => console.error('Erreur:', error));
}

function createProject(id) {
    // Récupérer les détails via AJAX pour pré-remplir le formulaire
    fetch(`ajax/get_devis_details.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('project_devis_id').value = id;
                document.getElementById('nom_projet').value = `Projet - ${data.devis.service} - ${data.devis.nom}`;
                document.getElementById('description_projet').value = data.devis.description || '';
                document.getElementById('budget_alloue').value = data.devis.montant_final || data.devis.montant_estime || '';
                
                document.getElementById('projectModal').style.display = 'flex';
            }
        })
        .catch(error => console.error('Erreur:', error));
}

function editDevis(id) {
    window.location.href = `edit-devis.php?id=${id}`;
}

function deleteDevis(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce devis ? Cette action est irréversible.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="devis_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function generatePDF(id) {
    window.open(`generate-devis-pdf.php?id=${id}`, '_blank');
}

function exportDevis() {
    const status = '<?php echo $status_filter; ?>';
    const service = '<?php echo $service_filter; ?>';
    const search = '<?php echo $search_query; ?>';
    
    window.location.href = `export-devis.php?status=${status}&service=${service}&search=${search}`;
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Gestion des dropdowns
document.addEventListener('click', function(event) {
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    
    dropdownToggles.forEach(toggle => {
        const dropdown = toggle.nextElementSibling;
        
        if (toggle.contains(event.target)) {
            // Fermer tous les autres dropdowns
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                if (menu !== dropdown) {
                    menu.classList.remove('show');
                }
            });
            
            // Toggle le dropdown actuel
            dropdown.classList.toggle('show');
        } else if (!dropdown.contains(event.target)) {
            dropdown.classList.remove('show');
        }
    });
});
</script>

<style>

</style>