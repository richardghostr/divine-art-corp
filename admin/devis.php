<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Vérification de l'authentification
check_admin_auth();

$db = Database::getInstance();
$conn = $db->getConnection();

// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $devis_id = (int)($_POST['devis_id'] ?? 0);
    
    switch ($action) {
        case 'update_status':
            $new_status = sanitize_string($_POST['status']);
            $stmt = $conn->prepare("UPDATE devis SET statut = ?, date_modification = NOW() WHERE id = ?");
            $stmt->bind_param("si", $new_status, $devis_id);
            
            if ($stmt->execute()) {
                log_activity($_SESSION['admin_id'], 'devis_status_update', 'devis', $devis_id, "Statut changé vers: $new_status");
                $_SESSION['success_message'] = "Statut du devis mis à jour avec succès.";
            } else {
                $_SESSION['error_message'] = "Erreur lors de la mise à jour du statut.";
            }
            break;
            
        case 'update_priority':
            $new_priority = sanitize_string($_POST['priority']);
            $stmt = $conn->prepare("UPDATE devis SET priorite = ?, date_modification = NOW() WHERE id = ?");
            $stmt->bind_param("si", $new_priority, $devis_id);
            
            if ($stmt->execute()) {
                log_activity($_SESSION['admin_id'], 'devis_priority_update', 'devis', $devis_id, "Priorité changée vers: $new_priority");
                $_SESSION['success_message'] = "Priorité du devis mise à jour avec succès.";
            } else {
                $_SESSION['error_message'] = "Erreur lors de la mise à jour de la priorité.";
            }
            break;
            
        case 'add_notes':
            $notes = sanitize_string($_POST['notes']);
            $stmt = $conn->prepare("UPDATE devis SET notes_admin = ?, date_modification = NOW() WHERE id = ?");
            $stmt->bind_param("si", $notes, $devis_id);
            
            if ($stmt->execute()) {
                log_activity($_SESSION['admin_id'], 'devis_notes_update', 'devis', $devis_id);
                $_SESSION['success_message'] = "Notes ajoutées avec succès.";
            } else {
                $_SESSION['error_message'] = "Erreur lors de l'ajout des notes.";
            }
            break;
            
        case 'delete':
            $stmt = $conn->prepare("DELETE FROM devis WHERE id = ?");
            $stmt->bind_param("i", $devis_id);
            
            if ($stmt->execute()) {
                log_activity($_SESSION['admin_id'], 'devis_delete', 'devis', $devis_id);
                $_SESSION['success_message'] = "Devis supprimé avec succès.";
            } else {
                $_SESSION['error_message'] = "Erreur lors de la suppression du devis.";
            }
            break;
    }
    
    redirect($_SERVER['PHP_SELF']);
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
    $where_conditions[] = "(d.nom LIKE ? OR d.email LIKE ? OR d.entreprise LIKE ? OR d.numero_devis LIKE ?)";
    $search_param = "%$search_query%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $param_types .= 'ssss';
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Récupération des statistiques
$stats_query = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN statut = 'nouveau' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) as accepted,
        SUM(CASE WHEN statut = 'termine' THEN 1 ELSE 0 END) as completed,
        COALESCE(SUM(CASE WHEN statut = 'termine' THEN montant_final ELSE 0 END), 0) as total_amount
    FROM devis
";

$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Récupération des devis
$devis_query = "
    SELECT d.*, s.nom as service_nom 
    FROM devis d 
    LEFT JOIN services s ON s.slug = d.service 
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

// Comptage total pour pagination
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

include 'header.php';
include 'sidebar.php';
?>

<main class="admin-main">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <div class="section-header">
        <div class="section-title">
            <h2>Gestion des Devis</h2>
            <p>Suivi des demandes et propositions commerciales</p>
        </div>
        <div class="section-actions">
            <button class="btn btn-outline" onclick="exportDevis()">
                <i class="fas fa-file-pdf"></i>
                Exporter PDF
            </button>
            <a href="../devis.php" class="btn btn-primary" target="_blank">
                <i class="fas fa-plus"></i>
                Nouveau Devis
            </a>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon pending">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">En attente</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon accepted">
                <i class="fas fa-check"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $stats['accepted']; ?></div>
                <div class="stat-label">En cours</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon completed">
                <i class="fas fa-check-double"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $stats['completed']; ?></div>
                <div class="stat-label">Terminés</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon total">
                <i class="fas fa-euro-sign"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo format_price($stats['total_amount']); ?></div>
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
                En attente (<?php echo $stats['pending']; ?>)
            </a>
            <a href="?status=en_cours&service=<?php echo $service_filter; ?>&search=<?php echo urlencode($search_query); ?>" 
               class="filter-tab <?php echo $status_filter === 'en_cours' ? 'active' : ''; ?>">
                En cours (<?php echo $stats['accepted']; ?>)
            </a>
            <a href="?status=termine&service=<?php echo $service_filter; ?>&search=<?php echo urlencode($search_query); ?>" 
               class="filter-tab <?php echo $status_filter === 'termine' ? 'active' : ''; ?>">
                Terminés (<?php echo $stats['completed']; ?>)
            </a>
        </div>
        <div class="filter-actions">
            <form method="GET" class="filter-form">
                <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                <select name="service" class="filter-select" onchange="this.form.submit()">
                    <option value="all">Tous les services</option>
                    <option value="marketing" <?php echo $service_filter === 'marketing' ? 'selected' : ''; ?>>Marketing</option>
                    <option value="graphique" <?php echo $service_filter === 'graphique' ? 'selected' : ''; ?>>Design Graphique</option>
                    <option value="multimedia" <?php echo $service_filter === 'multimedia' ? 'selected' : ''; ?>>Multimédia</option>
                    <option value="imprimerie" <?php echo $service_filter === 'imprimerie' ? 'selected' : ''; ?>>Imprimerie</option>
                </select>
                <input type="text" 
                       name="search" 
                       placeholder="Rechercher un devis..." 
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
                <p>Aucun devis ne correspond à vos critères de recherche.</p>
            </div>
        <?php else: ?>
            <?php foreach ($devis_list as $devis): ?>
                <div class="devis-card">
                    <div class="devis-header">
                        <div class="devis-number"><?php echo htmlspecialchars($devis['numero_devis']); ?></div>
                        <span class="status-badge <?php echo $devis['statut']; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $devis['statut'])); ?>
                        </span>
                        <span class="priority-badge <?php echo $devis['priorite']; ?>">
                            <?php echo ucfirst($devis['priorite']); ?>
                        </span>
                    </div>
                    <div class="devis-content">
                        <h3><?php echo htmlspecialchars($devis['description']); ?></h3>
                        <div class="devis-client">
                            <i class="fas fa-building"></i>
                            <span><?php echo htmlspecialchars($devis['entreprise'] ?: $devis['nom']); ?></span>
                        </div>
                        <div class="devis-contact">
                            <i class="fas fa-envelope"></i>
                            <span><?php echo htmlspecialchars($devis['email']); ?></span>
                            <i class="fas fa-phone"></i>
                            <span><?php echo htmlspecialchars($devis['telephone']); ?></span>
                        </div>
                        <div class="devis-meta">
                            <div class="devis-amount">
                                <?php if ($devis['montant_final']): ?>
                                    <?php echo format_price($devis['montant_final']); ?>
                                <?php elseif ($devis['montant_estime']): ?>
                                    ~<?php echo format_price($devis['montant_estime']); ?>
                                <?php else: ?>
                                    <span class="text-muted">Non estimé</span>
                                <?php endif; ?>
                            </div>
                            <div class="devis-date">
                                <?php echo time_ago($devis['date_creation']); ?>
                            </div>
                        </div>
                        <?php if ($devis['notes_admin']): ?>
                            <div class="devis-notes">
                                <i class="fas fa-sticky-note"></i>
                                <span><?php echo htmlspecialchars($devis['notes_admin']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="devis-actions">
                        <button class="btn btn-sm btn-outline" onclick="viewDevis(<?php echo $devis['id']; ?>)">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-primary" onclick="editDevis(<?php echo $devis['id']; ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <?php if ($devis['statut'] === 'nouveau'): ?>
                            <button class="btn btn-sm btn-success" onclick="updateStatus(<?php echo $devis['id']; ?>, 'en_cours')">
                                <i class="fas fa-check"></i>
                            </button>
                        <?php endif; ?>
                        <?php if ($devis['statut'] === 'en_cours'): ?>
                            <button class="btn btn-sm btn-info" onclick="updateStatus(<?php echo $devis['id']; ?>, 'termine')">
                                <i class="fas fa-check-double"></i>
                            </button>
                        <?php endif; ?>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline dropdown-toggle">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="mailto:<?php echo $devis['email']; ?>">
                                    <i class="fas fa-envelope"></i> Email
                                </a>
                                <a class="dropdown-item" onclick="addNotes(<?php echo $devis['id']; ?>)">
                                    <i class="fas fa-sticky-note"></i> Notes
                                </a>
                                <a class="dropdown-item" onclick="changePriority(<?php echo $devis['id']; ?>)">
                                    <i class="fas fa-flag"></i> Priorité
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-danger" onclick="deleteDevis(<?php echo $devis['id']; ?>)">
                                    <i class="fas fa-trash"></i> Supprimer
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination-container">
            <?php
            $base_url = "?status=$status_filter&service=$service_filter&search=" . urlencode($search_query);
            echo generate_pagination($page, $total_pages, $base_url);
            ?>
        </div>
    <?php endif; ?>
</main>

<script>
function updateStatus(id, status) {
    if (confirm(`Changer le statut vers "${status}" ?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="devis_id" value="${id}">
            <input type="hidden" name="status" value="${status}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function addNotes(id) {
    const notes = prompt('Ajouter des notes:');
    if (notes !== null) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="add_notes">
            <input type="hidden" name="devis_id" value="${id}">
            <input type="hidden" name="notes" value="${notes}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function changePriority(id) {
    const priority = prompt('Nouvelle priorité (basse/normale/haute/urgente):');
    if (priority && ['basse', 'normale', 'haute', 'urgente'].includes(priority)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="update_priority">
            <input type="hidden" name="devis_id" value="${id}">
            <input type="hidden" name="priority" value="${priority}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function deleteDevis(id) {
    if (confirm('Supprimer ce devis ?')) {
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
</script>

<?php include '../includes/footer.php'; ?>
