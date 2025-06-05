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
        $contact_id = isset($_POST['contact_id']) ? (int)$_POST['contact_id'] : 0;
        
        try {
            switch ($action) {
                case 'mark_read':
                    $result = $databaseHelper->execute(
                        "UPDATE contacts SET statut = 'lu', date_modification = NOW() WHERE id = ?",
                        [$contact_id]
                    );
                    
                    if ($result) {
                        $auth->logActivity($_SESSION['admin']['id'], 'contact_read', 'contacts', $contact_id);
                        $success_message = "Message marqué comme lu.";
                    }
                    break;
                    
                case 'mark_replied':
                    $result = $databaseHelper->execute(
                        "UPDATE contacts SET statut = 'repondu', date_reponse = NOW(), date_modification = NOW() WHERE id = ?",
                        [$contact_id]
                    );
                    
                    if ($result) {
                        $auth->logActivity($_SESSION['admin']['id'], 'contact_replied', 'contacts', $contact_id);
                        $success_message = "Message marqué comme répondu.";
                    }
                    break;
                    
                case 'mark_archived':
                    $result = $databaseHelper->execute(
                        "UPDATE contacts SET statut = 'archive', date_modification = NOW() WHERE id = ?",
                        [$contact_id]
                    );
                    
                    if ($result) {
                        $auth->logActivity($_SESSION['admin']['id'], 'contact_archived', 'contacts', $contact_id);
                        $success_message = "Message archivé.";
                    }
                    break;
                    
                case 'add_note':
                    $note = sanitizeInput($_POST['note']);
                    $result = $databaseHelper->execute(
                        "UPDATE contacts SET notes_admin = ?, date_modification = NOW() WHERE id = ?",
                        [$note, $contact_id]
                    );
                    
                    if ($result) {
                        $auth->logActivity($_SESSION['admin']['id'], 'contact_note_added', 'contacts', $contact_id);
                        $success_message = "Note ajoutée avec succès.";
                    }
                    break;
                    
                case 'assign_admin':
                    $admin_id = (int)$_POST['admin_id'];
                    $result = $databaseHelper->execute(
                        "UPDATE contacts SET admin_assigne = ?, date_modification = NOW() WHERE id = ?",
                        [$admin_id, $contact_id]
                    );
                    
                    if ($result) {
                        $auth->logActivity($_SESSION['admin']['id'], 'contact_assigned', 'contacts', $contact_id);
                        $success_message = "Contact assigné avec succès.";
                    }
                    break;
                    
                case 'update_priority':
                    $priorite = sanitizeInput($_POST['priorite']);
                    $result = $databaseHelper->execute(
                        "UPDATE contacts SET priorite = ?, date_modification = NOW() WHERE id = ?",
                        [$priorite, $contact_id]
                    );
                    
                    if ($result) {
                        $auth->logActivity($_SESSION['admin']['id'], 'contact_priority_update', 'contacts', $contact_id);
                        $success_message = "Priorité mise à jour avec succès.";
                    }
                    break;
                    
                case 'delete':
                    $result = $databaseHelper->execute(
                        "DELETE FROM contacts WHERE id = ?",
                        [$contact_id]
                    );
                    
                    if ($result) {
                        $auth->logActivity($_SESSION['admin']['id'], 'contact_deleted', 'contacts', $contact_id);
                        $success_message = "Message supprimé avec succès.";
                    }
                    break;
                    
                case 'mark_all_read':
                    $result = $databaseHelper->execute(
                        "UPDATE contacts SET statut = 'lu', date_modification = NOW() WHERE statut = 'nouveau'"
                    );
                    
                    if ($result) {
                        $auth->logActivity($_SESSION['admin']['id'], 'contacts_all_read', 'contacts');
                        $success_message = "Tous les messages ont été marqués comme lus.";
                    }
                    break;
            }
        } catch (Exception $e) {
            $error_message = "Erreur lors de l'opération: " . $e->getMessage();
            error_log("Erreur contacts: " . $e->getMessage());
        }
    }
}

// Filtres et pagination
$status_filter = $_GET['status'] ?? 'all';
$search_query = $_GET['search'] ?? '';
$priority_filter = $_GET['priority'] ?? 'all';
$page = (int)($_GET['page'] ?? 1);
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Construction de la requête WHERE
$where_conditions = [];
$params = [];
$param_types = '';

if ($status_filter !== 'all') {
    $where_conditions[] = "c.statut = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

if ($priority_filter !== 'all') {
    $where_conditions[] = "c.priorite = ?";
    $params[] = $priority_filter;
    $param_types .= 's';
}

if (!empty($search_query)) {
    $where_conditions[] = "(c.nom LIKE ? OR c.email LIKE ? OR c.sujet LIKE ? OR c.message LIKE ? OR c.numero_contact LIKE ?)";
    $search_param = "%$search_query%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
    $param_types .= 'sssss';
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Statistiques
$stats_query = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN statut = 'nouveau' THEN 1 ELSE 0 END) as unread,
        SUM(CASE WHEN statut = 'lu' THEN 1 ELSE 0 END) as 'read',
        SUM(CASE WHEN statut = 'repondu' THEN 1 ELSE 0 END) as replied,
        SUM(CASE WHEN statut = 'archive' THEN 1 ELSE 0 END) as archived,
        SUM(CASE WHEN priorite = 'haute' OR priorite = 'urgente' THEN 1 ELSE 0 END) as 'high_priority'
    FROM contacts
";

$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Récupération des administrateurs pour l'assignation
$admins_query = "SELECT id, nom FROM admins WHERE statut = 'actif' ORDER BY nom";
$admins_result = $conn->query($admins_query);
$admins_list = [];
while ($row = $admins_result->fetch_assoc()) {
    $admins_list[] = $row;
}

// Récupération des contacts
$contacts_query = "
    SELECT c.*, a.nom as admin_nom
    FROM contacts c
    LEFT JOIN admins a ON c.admin_assigne = a.id
    $where_clause 
    ORDER BY 
        CASE 
            WHEN c.priorite = 'urgente' THEN 1
            WHEN c.priorite = 'haute' THEN 2
            WHEN c.priorite = 'normale' THEN 3
            WHEN c.priorite = 'basse' THEN 4
        END,
        CASE 
            WHEN c.statut = 'nouveau' THEN 1
            WHEN c.statut = 'lu' THEN 2
            WHEN c.statut = 'repondu' THEN 3
            WHEN c.statut = 'archive' THEN 4
        END,
        c.date_creation DESC 
    LIMIT $per_page OFFSET $offset
";

if (!empty($params)) {
    $stmt = $conn->prepare($contacts_query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $contacts_result = $stmt->get_result();
} else {
    $contacts_result = $conn->query($contacts_query);
}

$contacts_list = [];
while ($row = $contacts_result->fetch_assoc()) {
    $contacts_list[] = $row;
}

// Comptage total
$count_query = "SELECT COUNT(*) as total FROM contacts c $where_clause";
if (!empty($params)) {
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $count_result = $stmt->get_result();
} else {
    $count_result = $conn->query($count_query);
}

$total_contacts = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_contacts / $per_page);

// Fonction pour formater la date relative


// Fonction pour tronquer le texte

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
            <h2>Gestion des Contacts</h2>
            <p>Messages et demandes de contact</p>
        </div>
        <div class="section-actions">
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="mark_all_read">
                <button type="submit" class="btn btn-outline">
                    <i class="fas fa-check-double"></i>
                    Tout marquer lu
                </button>
            </form>
            <button class="btn btn-primary" onclick="exportContacts()">
                <i class="fas fa-download"></i>
                Exporter
            </button>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon total">
                <i class="fas fa-envelope"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total messages</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon unread">
                <i class="fas fa-envelope-open"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $stats['unread']; ?></div>
                <div class="stat-label">Non lus</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon read">
                <i class="fas fa-eye"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $stats['read']; ?></div>
                <div class="stat-label">Lus</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon replied">
                <i class="fas fa-reply"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $stats['replied']; ?></div>
                <div class="stat-label">Répondus</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon priority">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $stats['high_priority']; ?></div>
                <div class="stat-label">Haute priorité</div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="filters-bar">
        <div class="filter-tabs">
            <a href="?status=all&priority=<?php echo $priority_filter; ?>&search=<?php echo urlencode($search_query); ?>" 
               class="filter-tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
                Tous (<?php echo $stats['total']; ?>)
            </a>
            <a href="?status=nouveau&priority=<?php echo $priority_filter; ?>&search=<?php echo urlencode($search_query); ?>" 
               class="filter-tab <?php echo $status_filter === 'nouveau' ? 'active' : ''; ?>">
                Non lus (<?php echo $stats['unread']; ?>)
            </a>
            <a href="?status=lu&priority=<?php echo $priority_filter; ?>&search=<?php echo urlencode($search_query); ?>" 
               class="filter-tab <?php echo $status_filter === 'lu' ? 'active' : ''; ?>">
                Lus (<?php echo $stats['read']; ?>)
            </a>
            <a href="?status=repondu&priority=<?php echo $priority_filter; ?>&search=<?php echo urlencode($search_query); ?>" 
               class="filter-tab <?php echo $status_filter === 'repondu' ? 'active' : ''; ?>">
                Répondus (<?php echo $stats['replied']; ?>)
            </a>
            <a href="?status=archive&priority=<?php echo $priority_filter; ?>&search=<?php echo urlencode($search_query); ?>" 
               class="filter-tab <?php echo $status_filter === 'archive' ? 'active' : ''; ?>">
                Archives (<?php echo $stats['archived']; ?>)
            </a>
        </div>
        <div class="filter-actions">
            <form method="GET" class="filter-form">
                <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                
                <select name="priority" class="filter-select" onchange="this.form.submit()">
                    <option value="all" <?php echo $priority_filter === 'all' ? 'selected' : ''; ?>>Toutes priorités</option>
                    <option value="urgente" <?php echo $priority_filter === 'urgente' ? 'selected' : ''; ?>>Urgente</option>
                    <option value="haute" <?php echo $priority_filter === 'haute' ? 'selected' : ''; ?>>Haute</option>
                    <option value="normale" <?php echo $priority_filter === 'normale' ? 'selected' : ''; ?>>Normale</option>
                    <option value="basse" <?php echo $priority_filter === 'basse' ? 'selected' : ''; ?>>Basse</option>
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

    <!-- Liste des contacts -->
    <div class="contacts-list">
        <?php if (empty($contacts_list)): ?>
            <div class="empty-state">
                <i class="fas fa-envelope"></i>
                <h3>Aucun message trouvé</h3>
                <p>Aucun message ne correspond à vos critères.</p>
            </div>
        <?php else: ?>
            <div class="contacts-grid">
                <?php foreach ($contacts_list as $contact): ?>
                    <div class="contact-card <?php echo $contact['statut']; ?> priority-<?php echo $contact['priorite']; ?>">
                        <div class="contact-header">
                            <div class="contact-info">
                                <div class="contact-name">
                                    <?php echo htmlspecialchars($contact['nom']); ?>
                                    <?php if ($contact['statut'] === 'nouveau'): ?>
                                        <span class="new-badge">Nouveau</span>
                                    <?php endif; ?>
                                </div>
                                <div class="contact-meta">
                                    <span class="contact-number"><?php echo htmlspecialchars($contact['numero_contact']); ?></span>
                                    <span class="contact-date"><?php echo time_ago($contact['date_creation']); ?></span>
                                </div>
                            </div>
                            <div class="contact-actions">
                                <div class="priority-indicator priority-<?php echo $contact['priorite']; ?>" 
                                     title="Priorité <?php echo $contact['priorite']; ?>">
                                    <i class="fas fa-circle"></i>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline dropdown-toggle">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <?php if ($contact['statut'] === 'nouveau'): ?>
                                            <a class="dropdown-item" onclick="markAsRead(<?php echo $contact['id']; ?>)">
                                                <i class="fas fa-eye"></i> Marquer comme lu
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($contact['statut'] !== 'repondu'): ?>
                                            <a class="dropdown-item" onclick="markAsReplied(<?php echo $contact['id']; ?>)">
                                                <i class="fas fa-reply"></i> Marquer comme répondu
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($contact['statut'] !== 'archive'): ?>
                                            <a class="dropdown-item" onclick="markAsArchived(<?php echo $contact['id']; ?>)">
                                                <i class="fas fa-archive"></i> Archiver
                                            </a>
                                        <?php endif; ?>
                                        
                                        <div class="dropdown-divider"></div>
                                        
                                        <a class="dropdown-item" onclick="updatePriority(<?php echo $contact['id']; ?>)">
                                            <i class="fas fa-flag"></i> Changer priorité
                                        </a>
                                        
                                        <a class="dropdown-item" onclick="assignAdmin(<?php echo $contact['id']; ?>)">
                                            <i class="fas fa-user-plus"></i> Assigner
                                        </a>
                                        
                                        <a class="dropdown-item" onclick="addNote(<?php echo $contact['id']; ?>)">
                                            <i class="fas fa-sticky-note"></i> Ajouter note
                                        </a>
                                        
                                        <div class="dropdown-divider"></div>
                                        
                                        <a class="dropdown-item" href="mailto:<?php echo $contact['email']; ?>">
                                            <i class="fas fa-envelope"></i> Répondre par email
                                        </a>
                                        
                                        <a class="dropdown-item text-danger" onclick="deleteContact(<?php echo $contact['id']; ?>)">
                                            <i class="fas fa-trash"></i> Supprimer
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="contact-content">
                            <div class="contact-subject">
                                <?php echo htmlspecialchars($contact['sujet']); ?>
                            </div>
                            <div class="contact-message">
                                <?php echo nl2br(htmlspecialchars(truncate_text($contact['message'], 200))); ?>
                            </div>
                            
                            <div class="contact-details">
                                <div class="contact-detail">
                                    <i class="fas fa-envelope"></i>
                                    <span><?php echo htmlspecialchars($contact['email']); ?></span>
                                </div>
                                <?php if ($contact['telephone']): ?>
                                    <div class="contact-detail">
                                        <i class="fas fa-phone"></i>
                                        <span><?php echo htmlspecialchars($contact['telephone']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($contact['entreprise']): ?>
                                    <div class="contact-detail">
                                        <i class="fas fa-building"></i>
                                        <span><?php echo htmlspecialchars($contact['entreprise']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($contact['admin_nom']): ?>
                                <div class="contact-assigned">
                                    <i class="fas fa-user"></i>
                                    Assigné à: <?php echo htmlspecialchars($contact['admin_nom']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($contact['notes_admin']): ?>
                                <div class="contact-notes">
                                    <i class="fas fa-sticky-note"></i>
                                    <span><?php echo nl2br(htmlspecialchars($contact['notes_admin'])); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="contact-footer">
                            <button class="btn btn-sm btn-primary" onclick="viewContact(<?php echo $contact['id']; ?>)">
                                <i class="fas fa-eye"></i>
                                Voir détails
                            </button>
                            <button class="btn btn-sm btn-outline" onclick="replyToContact(<?php echo $contact['id']; ?>)">
                                <i class="fas fa-reply"></i>
                                Répondre
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination-container">
            <ul class="pagination">
                <?php if ($page > 1): ?>
                    <li>
                        <a href="?status=<?php echo $status_filter; ?>&priority=<?php echo $priority_filter; ?>&search=<?php echo urlencode($search_query); ?>&page=<?php echo $page - 1; ?>">
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
                        <a href="?status=<?php echo $status_filter; ?>&priority=<?php echo $priority_filter; ?>&search=<?php echo urlencode($search_query); ?>&page=<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <li>
                        <a href="?status=<?php echo $status_filter; ?>&priority=<?php echo $priority_filter; ?>&search=<?php echo urlencode($search_query); ?>&page=<?php echo $page + 1; ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    <?php endif; ?>
</main>

<!-- Modal détails contact -->
<div id="contactModal" class="modal" style="display: none;">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3 id="contactModalTitle">Détails du contact</h3>
            <button class="modal-close" onclick="closeModal('contactModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="contactModalBody">
            <!-- Contenu dynamique -->
        </div>
    </div>
</div>

<!-- Modal priorité -->
<div id="priorityModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Modifier la priorité</h3>
            <button class="modal-close" onclick="closeModal('priorityModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="priorityForm" method="POST">
                <input type="hidden" name="action" value="update_priority">
                <input type="hidden" name="contact_id" id="priority_contact_id">
                
                <div class="form-group">
                    <label for="priorite">Priorité</label>
                    <select name="priorite" id="priorite" class="form-control" required>
                        <option value="basse">Basse</option>
                        <option value="normale">Normale</option>
                        <option value="haute">Haute</option>
                        <option value="urgente">Urgente</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal('priorityModal')">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal assignation -->
<div id="assignModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Assigner à un administrateur</h3>
            <button class="modal-close" onclick="closeModal('assignModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="assignForm" method="POST">
                <input type="hidden" name="action" value="assign_admin">
                <input type="hidden" name="contact_id" id="assign_contact_id">
                
                <div class="form-group">
                    <label for="admin_id">Administrateur</label>
                    <select name="admin_id" id="admin_id" class="form-control" required>
                        <option value="">Sélectionner un administrateur</option>
                        <?php foreach ($admins_list as $admin): ?>
                            <option value="<?php echo $admin['id']; ?>">
                                <?php echo htmlspecialchars($admin['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal('assignModal')">Annuler</button>
                    <button type="submit" class="btn btn-primary">Assigner</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal note -->
<div id="noteModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Ajouter une note</h3>
            <button class="modal-close" onclick="closeModal('noteModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="noteForm" method="POST">
                <input type="hidden" name="action" value="add_note">
                <input type="hidden" name="contact_id" id="note_contact_id">
                
                <div class="form-group">
                    <label for="note">Note administrative</label>
                    <textarea name="note" id="note" class="form-control" rows="4" placeholder="Ajouter une note..."></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal('noteModal')">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function viewContact(id) {
    // Récupérer les détails via AJAX
    fetch(`ajax/get_contact_details.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('contactModalTitle').textContent = `Contact ${data.contact.numero_contact}`;
                
                document.getElementById('contactModalBody').innerHTML = `
                    <div class="contact-details-full">
                        <div class="detail-section">
                            <h4>Informations de contact</h4>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <span class="detail-label">Nom</span>
                                    <span class="detail-value">${data.contact.nom}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Email</span>
                                    <span class="detail-value">${data.contact.email}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Téléphone</span>
                                    <span class="detail-value">${data.contact.telephone || 'Non spécifié'}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Entreprise</span>
                                    <span class="detail-value">${data.contact.entreprise || 'Non spécifié'}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="detail-section">
                            <h4>Message</h4>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <span class="detail-label">Sujet</span>
                                    <span class="detail-value">${data.contact.sujet}</span>
                                </div>
                                <div class="detail-item full-width">
                                    <span class="detail-label">Message</span>
                                    <div class="detail-text">${data.contact.message.replace(/\n/g, '<br>')}</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="detail-section">
                            <h4>Informations administratives</h4>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <span class="detail-label">Statut</span>
                                    <span class="status-badge ${data.contact.statut}">${data.contact.statut}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Priorité</span>
                                    <span class="priority-badge ${data.contact.priorite}">${data.contact.priorite}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Date de création</span>
                                    <span class="detail-value">${new Date(data.contact.date_creation).toLocaleString()}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Dernière modification</span>
                                    <span class="detail-value">${data.contact.date_modification ? new Date(data.contact.date_modification).toLocaleString() : 'Aucune'}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Assigné à</span>
                                    <span class="detail-value">${data.contact.admin_nom || 'Non assigné'}</span>
                                </div>
                                <div class="detail-item full-width">
                                    <span class="detail-label">Notes administratives</span>
                                    <div class="detail-text">${data.contact.notes_admin ? data.contact.notes_admin.replace(/\n/g, '<br>') : 'Aucune note'}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                document.getElementById('contactModal').style.display = 'flex';
            }
        })
        .catch(error => console.error('Erreur:', error));
}

function markAsRead(id) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="mark_read">
        <input type="hidden" name="contact_id" value="${id}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function markAsReplied(id) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="mark_replied">
        <input type="hidden" name="contact_id" value="${id}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function markAsArchived(id) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="mark_archived">
        <input type="hidden" name="contact_id" value="${id}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function updatePriority(id) {
    // Récupérer les détails via AJAX pour pré-remplir le formulaire
    fetch(`ajax/get_contact_details.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('priority_contact_id').value = id;
                document.getElementById('priorite').value = data.contact.priorite;
                
                document.getElementById('priorityModal').style.display = 'flex';
            }
        })
        .catch(error => console.error('Erreur:', error));
}

function assignAdmin(id) {
    document.getElementById('assign_contact_id').value = id;
    document.getElementById('assignModal').style.display = 'flex';
}

function addNote(id) {
    // Récupérer les détails via AJAX pour pré-remplir le formulaire
    fetch(`ajax/get_contact_details.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('note_contact_id').value = id;
                document.getElementById('note').value = data.contact.notes_admin || '';
                
                document.getElementById('noteModal').style.display = 'flex';
            }
        })
        .catch(error => console.error('Erreur:', error));
}

function replyToContact(id) {
    // Récupérer les détails via AJAX
    fetch(`ajax/get_contact_details.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const subject = `Re: ${data.contact.sujet}`;
                const body = `Bonjour ${data.contact.nom},\n\nMerci pour votre message.\n\n---\nMessage original:\n${data.contact.message}`;
                
                window.location.href = `mailto:${data.contact.email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
            }
        })
        .catch(error => console.error('Erreur:', error));
}

function deleteContact(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce message ? Cette action est irréversible.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="contact_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function exportContacts() {
    const status = '<?php echo $status_filter; ?>';
    const priority = '<?php echo $priority_filter; ?>';
    const search = '<?php echo $search_query; ?>';
    
    window.location.href = `export-contacts.php?status=${status}&priority=${priority}&search=${search}`;
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

// Auto-refresh pour les nouveaux messages
setInterval(function() {
    // Vérifier s'il y a de nouveaux messages
    fetch('ajax/check_new_contacts.php')
        .then(response => response.json())
        .then(data => {
            if (data.new_count > 0) {
                // Afficher une notification ou recharger la page
                const notification = document.createElement('div');
                notification.className = 'notification';
                notification.innerHTML = `
                    <i class="fas fa-envelope"></i>
                    ${data.new_count} nouveau${data.new_count > 1 ? 'x' : ''} message${data.new_count > 1 ? 's' : ''}
                    <button onclick="location.reload()">Actualiser</button>
                `;
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.remove();
                }, 5000);
            }
        })
        .catch(error => console.error('Erreur:', error));
}, 30000); // Vérifier toutes les 30 secondes
</script>

<style>/* Gestion des Contacts - Style Principal */
</style>