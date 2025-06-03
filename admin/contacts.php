<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

check_admin_auth();
// Vérifie si l'administrateur est authentifié, sinon redirige vers la page de connexion
function check_admin_auth() {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit();
    }
}
$db = Database::getInstance();
$conn = $db->getConnection();

// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $contact_id = (int)($_POST['contact_id'] ?? 0);
    
    switch ($action) {
        case 'mark_read':
            $stmt = $conn->prepare("UPDATE contacts SET statut = 'lu', date_modification = NOW() WHERE id = ?");
            $stmt->bind_param("i", $contact_id);
            
            if ($stmt->execute()) {
                log_activity($_SESSION['admin_id'], 'contact_read', 'contacts', $contact_id);
                $_SESSION['success_message'] = "Message marqué comme lu.";
            }
            break;
            
        case 'mark_replied':
            $stmt = $conn->prepare("UPDATE contacts SET statut = 'repondu', date_modification = NOW() WHERE id = ?");
            $stmt->bind_param("i", $contact_id);
            
            if ($stmt->execute()) {
                log_activity($_SESSION['admin_id'], 'contact_replied', 'contacts', $contact_id);
                $_SESSION['success_message'] = "Message marqué comme répondu.";
            }
            break;
            
        case 'add_note':
            $note = sanitize_string($_POST['note']);
            $stmt = $conn->prepare("UPDATE contacts SET notes_admin = ?, date_modification = NOW() WHERE id = ?");
            $stmt->bind_param("si", $note, $contact_id);
            
            if ($stmt->execute()) {
                log_activity($_SESSION['admin_id'], 'contact_note_added', 'contacts', $contact_id);
                $_SESSION['success_message'] = "Note ajoutée avec succès.";
            }
            break;
            
        case 'delete':
            $stmt = $conn->prepare("DELETE FROM contacts WHERE id = ?");
            $stmt->bind_param("i", $contact_id);
            
            if ($stmt->execute()) {
                log_activity($_SESSION['admin_id'], 'contact_deleted', 'contacts', $contact_id);
                $_SESSION['success_message'] = "Message supprimé avec succès.";
            }
            break;
    }
    
    redirect($_SERVER['PHP_SELF']);
}

// Filtres et pagination
$status_filter = $_GET['status'] ?? 'all';
$search_query = $_GET['search'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Construction de la requête WHERE
$where_conditions = [];
$params = [];
$param_types = '';

if ($status_filter !== 'all') {
    $where_conditions[] = "statut = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

if (!empty($search_query)) {
    $where_conditions[] = "(nom LIKE ? OR email LIKE ? OR sujet LIKE ? OR message LIKE ?)";
    $search_param = "%$search_query%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $param_types .= 'ssss';
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Statistiques
$stats_query = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN statut = 'nouveau' THEN 1 ELSE 0 END) as unread,
        SUM(CASE WHEN statut = 'lu' THEN 1 ELSE 0 END) as read,
        SUM(CASE WHEN statut = 'repondu' THEN 1 ELSE 0 END) as replied
    FROM contacts
";

$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Récupération des contacts
$contacts_query = "
    SELECT * FROM contacts 
    $where_clause 
    ORDER BY date_creation DESC 
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
$count_query = "SELECT COUNT(*) as total FROM contacts $where_clause";
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

    <div class="section-header">
        <div class="section-title">
            <h2>Gestion des Contacts</h2>
            <p>Messages et demandes de contact</p>
        </div>
        <div class="section-actions">
            <button class="btn btn-outline" onclick="exportContacts()">
                <i class="fas fa-download"></i>
                Exporter
            </button>
            <button class="btn btn-primary" onclick="markAllRead()">
                <i class="fas fa-check-double"></i>
                Tout marquer lu
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
    </div>

    <!-- Filtres -->
    <div class="filters-bar">
        <div class="filter-tabs">
            <a href="?status=all&search=<?php echo urlencode($search_query); ?>" 
               class="filter-tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
                Tous (<?php echo $stats['total']; ?>)
            </a>
            <a href="?status=nouveau&search=<?php echo urlencode($search_query); ?>" 
               class="filter-tab <?php echo $status_filter === 'nouveau' ? 'active' : ''; ?>">
                Non lus (<?php echo $stats['unread']; ?>)
            </a>
            <a href="?status=lu&search=<?php echo urlencode($search_query); ?>" 
               class="filter-tab <?php echo $status_filter === 'lu' ? 'active' : ''; ?>">
                Lus (<?php echo $stats['read']; ?>)
            </a>
            <a href="?status=repondu&search=<?php echo urlencode($search_query); ?>" 
               class="filter-tab <?php echo $status_filter === 'repondu' ? 'active' : ''; ?>">
                Répondus (<?php echo $stats['replied']; ?>)
            </a>
        </div>
        <div class="filter-actions">
            <form method="GET" class="filter-form">
                <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
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
                <i class="fas fa-inbox"></i>
                <h3>Aucun message trouvé</h3>
                <p>Aucun message ne correspond à vos critères.</p>
            </div>
        <?php else: ?>
            <?php foreach ($contacts_list as $contact): ?>
                <div class="contact-card <?php echo $contact['statut']; ?>">
                    <div class="contact-header">
                        <div class="contact-info">
                            <h3><?php echo htmlspecialchars($contact['nom']); ?></h3>
                            <span class="contact-email"><?php echo htmlspecialchars($contact['email']); ?></span>
                        </div>
                        <div class="contact-meta">
                            <span class="status-badge <?php echo $contact['statut']; ?>">
                                <?php echo ucfirst($contact['statut']); ?>
                            </span>
                            <span class="contact-date"><?php echo time_ago($contact['date_creation']); ?></span>
                        </div>
                    </div>
                    <div class="contact-content">
                        <h4><?php echo htmlspecialchars($contact['sujet']); ?></h4>
                        <p><?php echo nl2br(htmlspecialchars(truncate_text($contact['message'], 200))); ?></p>
                        <?php if ($contact['notes_admin']): ?>
                            <div class="admin-note">
                                <i class="fas fa-sticky-note"></i>
                                <span><?php echo htmlspecialchars($contact['notes_admin']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="contact-actions">
                        <button class="btn btn-sm btn-outline" onclick="viewContact(<?php echo $contact['id']; ?>)">
                            <i class="fas fa-eye"></i> Voir
                        </button>
                        <a href="mailto:<?php echo $contact['email']; ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-reply"></i> Répondre
                        </a>
                        <?php if ($contact['statut'] === 'nouveau'): ?>
                            <button class="btn btn-sm btn-success" onclick="markRead(<?php echo $contact['id']; ?>)">
                                <i class="fas fa-check"></i> Marquer lu
                            </button>
                        <?php endif; ?>
                        <?php if ($contact['statut'] === 'lu'): ?>
                            <button class="btn btn-sm btn-info" onclick="markReplied(<?php echo $contact['id']; ?>)">
                                <i class="fas fa-reply"></i> Marquer répondu
                            </button>
                        <?php endif; ?>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline dropdown-toggle">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" onclick="addNote(<?php echo $contact['id']; ?>)">
                                    <i class="fas fa-sticky-note"></i> Ajouter note
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-danger" onclick="deleteContact(<?php echo $contact['id']; ?>)">
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
            $base_url = "?status=$status_filter&search=" . urlencode($search_query);
            echo generate_pagination($page, $total_pages, $base_url);
            ?>
        </div>
    <?php endif; ?>
</main>

<!-- Modal détails contact -->
<div id="contactModal" class="modal" style="display: none;">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3 id="contactModalTitle">Détails du message</h3>
            <button class="modal-close" onclick="closeModal('contactModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="contactModalBody">
            <!-- Contenu dynamique -->
        </div>
    </div>
</div>

<script>
function markRead(id) {
    submitAction('mark_read', id);
}

function markReplied(id) {
    submitAction('mark_replied', id);
}

function addNote(id) {
    const note = prompt('Ajouter une note:');
    if (note !== null) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="add_note">
            <input type="hidden" name="contact_id" value="${id}">
            <input type="hidden" name="note" value="${note}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function deleteContact(id) {
    if (confirm('Supprimer ce message ?')) {
        submitAction('delete', id);
    }
}

function submitAction(action, id) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="${action}">
        <input type="hidden" name="contact_id" value="${id}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function viewContact(id) {
    // Récupérer les détails via AJAX
    fetch(`ajax/get_contact_details.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('contactModalTitle').textContent = `Message de ${data.contact.nom}`;
                document.getElementById('contactModalBody').innerHTML = `
                    <div class="contact-details">
                        <div class="detail-row">
                            <strong>Nom:</strong> ${data.contact.nom}
                        </div>
                        <div class="detail-row">
                            <strong>Email:</strong> ${data.contact.email}
                        </div>
                        <div class="detail-row">
                            <strong>Sujet:</strong> ${data.contact.sujet}
                        </div>
                        <div class="detail-row">
                            <strong>Date:</strong> ${new Date(data.contact.date_creation).toLocaleString()}
                        </div>
                        <div class="detail-row">
                            <strong>Message:</strong><br>
                            <div class="message-content">${data.contact.message.replace(/\n/g, '<br>')}</div>
                        </div>
                    </div>
                `;
                document.getElementById('contactModal').style.display = 'flex';
            }
        })
        .catch(error => console.error('Erreur:', error));
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}
</script>

<?php include '../includes/footer.php'; ?>
