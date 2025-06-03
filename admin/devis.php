<?php 
require_once 'header.php';
require_once 'sidebar.php';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $devis_id = $_POST['devis_id'] ?? null;
        
        try {
            switch ($action) {
                case 'update_status':
                    $new_status = $_POST['status'];
                    $result = $db->execute(
                        "UPDATE devis SET statut = ?, date_modification = NOW() WHERE id = ?",
                        [$new_status, $devis_id]
                    );
                    
                    if ($result) {
                        logActivity($_SESSION['admin_id'], 'devis_status_update', 'devis', $devis_id, "Statut changé vers: $new_status");
                        $success_message = "Statut du devis mis à jour avec succès.";
                    }
                    break;
                    
                case 'update_priority':
                    $new_priority = $_POST['priority'];
                    $result = $db->execute(
                        "UPDATE devis SET priorite = ?, date_modification = NOW() WHERE id = ?",
                        [$new_priority, $devis_id]
                    );
                    
                    if ($result) {
                        logActivity($_SESSION['admin_id'], 'devis_priority_update', 'devis', $devis_id, "Priorité changée vers: $new_priority");
                        $success_message = "Priorité du devis mise à jour avec succès.";
                    }
                    break;
                    
                case 'add_notes':
                    $notes = sanitizeInput($_POST['notes']);
                    $result = $db->execute(
                        "UPDATE devis SET notes_admin = ?, date_modification = NOW() WHERE id = ?",
                        [$notes, $devis_id]
                    );
                    
                    if ($result) {
                        logActivity($_SESSION['admin_id'], 'devis_notes_update', 'devis', $devis_id);
                        $success_message = "Notes ajoutées avec succès.";
                    }
                    break;
                    
                case 'delete':
                    $result = $db->execute("DELETE FROM devis WHERE id = ?", [$devis_id]);
                    
                    if ($result) {
                        logActivity($_SESSION['admin_id'], 'devis_delete', 'devis', $devis_id);
                        $success_message = "Devis supprimé avec succès.";
                    }
                    break;
            }
        } catch (Exception $e) {
            $error_message = "Erreur lors de l'opération: " . $e->getMessage();
            error_log("Erreur devis: " . $e->getMessage());
        }
    }
}

// Filtres
$status_filter = $_GET['status'] ?? 'all';
$service_filter = $_GET['service'] ?? 'all';
$search_query = $_GET['search'] ?? '';

// Construction de la requête
$where_conditions = [];
$params = [];

if ($status_filter !== 'all') {
    $where_conditions[] = "d.statut = ?";
    $params[] = $status_filter;
}

if ($service_filter !== 'all') {
    $where_conditions[] = "d.service = ?";
    $params[] = $service_filter;
}

if (!empty($search_query)) {
    $where_conditions[] = "(d.nom LIKE ? OR d.email LIKE ? OR d.entreprise LIKE ? OR d.numero_devis LIKE ?)";
    $search_param = "%$search_query%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

try {
    // Récupération des statistiques
    $stats = [
        'pending' => $db->selectOne("SELECT COUNT(*) as count FROM devis WHERE statut = 'nouveau'")['count'],
        'accepted' => $db->selectOne("SELECT COUNT(*) as count FROM devis WHERE statut = 'en_cours'")['count'],
        'completed' => $db->selectOne("SELECT COUNT(*) as count FROM devis WHERE statut = 'termine'")['count'],
        'total_amount' => $db->selectOne("SELECT COALESCE(SUM(montant_final), 0) as total FROM devis WHERE statut = 'termine'")['total']
    ];
    
    // Récupération des devis avec pagination
    $page = $_GET['page'] ?? 1;
    $per_page = 10;
    $offset = ($page - 1) * $per_page;
    
    $devis_query = "
        SELECT d.*, s.nom as service_nom 
        FROM devis d 
        LEFT JOIN services s ON s.slug = d.service 
        $where_clause 
        ORDER BY d.date_creation DESC 
        LIMIT $per_page OFFSET $offset
    ";
    
    $devis_list = $db->selectAll($devis_query, $params);
    
    // Comptage total pour la pagination
    $total_query = "SELECT COUNT(*) as total FROM devis d $where_clause";
    $total_devis = $db->selectOne($total_query, $params)['total'];
    $total_pages = ceil($total_devis / $per_page);
    
} catch (Exception $e) {
    error_log("Erreur récupération devis: " . $e->getMessage());
    $devis_list = [];
    $total_devis = 0;
    $total_pages = 1;
    $stats = ['pending' => 0, 'accepted' => 0, 'completed' => 0, 'total_amount' => 0];
}

// Fonctions utilitaires
function getStatusBadge($status) {
    $badges = [
        'nouveau' => '<span class="status-badge pending">En attente</span>',
        'en_cours' => '<span class="status-badge accepted">En cours</span>',
        'termine' => '<span class="status-badge completed">Terminé</span>',
        'annule' => '<span class="status-badge rejected">Annulé</span>'
    ];
    return $badges[$status] ?? '<span class="status-badge">' . ucfirst($status) . '</span>';
}

function getPriorityBadge($priority) {
    $badges = [
        'basse' => '<span class="priority-badge low">Basse</span>',
        'normale' => '<span class="priority-badge normal">Normale</span>',
        'haute' => '<span class="priority-badge high">Haute</span>',
        'urgente' => '<span class="priority-badge urgent">Urgente</span>'
    ];
    return $badges[$priority] ?? '<span class="priority-badge">' . ucfirst($priority) . '</span>';
}

function getServiceIcon($service) {
    $icons = [
        'marketing' => 'fas fa-bullhorn',
        'graphique' => 'fas fa-paint-brush',
        'multimedia' => 'fas fa-video',
        'imprimerie' => 'fas fa-print'
    ];
    return $icons[$service] ?? 'fas fa-briefcase';
}
?>

<!-- Contenu Principal -->
<main class="admin-main">
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $error_message; ?>
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

    <!-- Statistiques Devis -->
    <div class="devis-stats">
        <div class="stat-item">
            <div class="stat-icon pending">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">En attente</div>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon accepted">
                <i class="fas fa-check"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $stats['accepted']; ?></div>
                <div class="stat-label">En cours</div>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon completed">
                <i class="fas fa-check-double"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $stats['completed']; ?></div>
                <div class="stat-label">Terminés</div>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon total">
                <i class="fas fa-euro-sign"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo formatMontant($stats['total_amount']); ?></div>
                <div class="stat-label">Montant total</div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="filters-bar">
        <div class="filter-tabs">
            <a href="?status=all&service=<?php echo $service_filter; ?>&search=<?php echo urlencode($search_query); ?>" 
               class="filter-tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
                Tous (<?php echo $total_devis; ?>)
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

    <!-- Liste des Devis -->
    <div class="devis-list" id="devisList">
        <?php if (empty($devis_list)): ?>
            <div class="empty-state">
                <i class="fas fa-file-invoice"></i>
                <h3>Aucun devis trouvé</h3>
                <p>Aucun devis ne correspond à vos critères de recherche.</p>
            </div>
        <?php else: ?>
            <?php foreach ($devis_list as $devis): ?>
                <div class="devis-card" data-status="<?php echo $devis['statut']; ?>" data-service="<?php echo $devis['service']; ?>">
                    <div class="devis-header">
                        <div class="devis-number"><?php echo htmlspecialchars($devis['numero_devis']); ?></div>
                        <?php echo getStatusBadge($devis['statut']); ?>
                        <?php echo getPriorityBadge($devis['priorite']); ?>
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
                        <div class="devis-services">
                            <span class="service-tag">
                                <i class="<?php echo getServiceIcon($devis['service']); ?>"></i>
                                <?php echo htmlspecialchars($devis['service_nom'] ?: ucfirst($devis['service'])); ?>
                            </span>
                            <?php if ($devis['sous_service']): ?>
                                <span class="service-tag secondary"><?php echo htmlspecialchars($devis['sous_service']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="devis-meta">
                            <div class="devis-amount">
                                <?php if ($devis['montant_final']): ?>
                                    <?php echo formatMontant($devis['montant_final']); ?>
                                <?php elseif ($devis['montant_estime']): ?>
                                    ~<?php echo formatMontant($devis['montant_estime']); ?>
                                <?php else: ?>
                                    <span class="text-muted">Non estimé</span>
                                <?php endif; ?>
                            </div>
                            <div class="devis-date">
                                Créé le <?php echo date('d/m/Y', strtotime($devis['date_creation'])); ?>
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
                        <button class="btn btn-sm btn-outline" onclick="viewDevis(<?php echo $devis['id']; ?>)" title="Voir">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-primary" onclick="editDevis(<?php echo $devis['id']; ?>)" title="Modifier">
                            <i class="fas fa-edit"></i>
                        </button>
                        <?php if ($devis['statut'] === 'nouveau'): ?>
                            <button class="btn btn-sm btn-success" onclick="updateStatus(<?php echo $devis['id']; ?>, 'en_cours')" title="Accepter">
                                <i class="fas fa-check"></i>
                            </button>
                        <?php endif; ?>
                        <?php if ($devis['statut'] === 'en_cours'): ?>
                            <button class="btn btn-sm btn-info" onclick="updateStatus(<?php echo $devis['id']; ?>, 'termine')" title="Terminer">
                                <i class="fas fa-check-double"></i>
                            </button>
                        <?php endif; ?>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline dropdown-toggle" data-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="mailto:<?php echo $devis['email']; ?>">
                                    <i class="fas fa-envelope"></i> Envoyer email
                                </a>
                                <a class="dropdown-item" href="tel:<?php echo $devis['telephone']; ?>">
                                    <i class="fas fa-phone"></i> Appeler
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" onclick="addNotes(<?php echo $devis['id']; ?>)">
                                    <i class="fas fa-sticky-note"></i> Ajouter notes
                                </a>
                                <a class="dropdown-item" onclick="changePriority(<?php echo $devis['id']; ?>)">
                                    <i class="fas fa-flag"></i> Changer priorité
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
            <div class="pagination-info">
                Affichage de <?php echo (($page - 1) * $per_page) + 1; ?> à <?php echo min($page * $per_page, $total_devis); ?> sur <?php echo $total_devis; ?> devis
            </div>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&service=<?php echo $service_filter; ?>&search=<?php echo urlencode($search_query); ?>" class="pagination-btn">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <a href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&service=<?php echo $service_filter; ?>&search=<?php echo urlencode($search_query); ?>" 
                       class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&service=<?php echo $service_filter; ?>&search=<?php echo urlencode($search_query); ?>" class="pagination-btn">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</main>

</div> <!-- Fin admin-layout -->

<!-- Modal Détails Devis -->
<div id="devisDetailsModal" class="modal" style="display: none;">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3 id="devisDetailsTitle">Détails du Devis</h3>
            <button class="modal-close" onclick="closeModal('devisDetailsModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div id="devisDetailsContent">
                <!-- Contenu dynamique -->
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="../assets/js/admin.js"></script>
<script>
// Actions sur les devis
function viewDevis(id) {
    // Récupération des détails via AJAX
    fetch(`ajax/get_devis_details.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('devisDetailsTitle').textContent = `Devis ${data.devis.numero_devis}`;
                document.getElementById('devisDetailsContent').innerHTML = generateDevisDetailsHTML(data.devis);
                document.getElementById('devisDetailsModal').style.display = 'flex';
            } else {
                alert('Erreur lors du chargement des détails');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur de communication avec le serveur');
        });
}

function generateDevisDetailsHTML(devis) {
    return `
        <div class="devis-details-grid">
            <div class="detail-section">
                <h4>Informations Client</h4>
                <p><strong>Nom:</strong> ${devis.nom}</p>
                <p><strong>Email:</strong> ${devis.email}</p>
                <p><strong>Téléphone:</strong> ${devis.telephone}</p>
                <p><strong>Entreprise:</strong> ${devis.entreprise || 'Non spécifiée'}</p>
            </div>
            <div class="detail-section">
                <h4>Détails du Projet</h4>
                <p><strong>Service:</strong> ${devis.service}</p>
                <p><strong>Description:</strong> ${devis.description}</p>
                <p><strong>Budget:</strong> ${devis.budget || 'Non spécifié'}</p>
                <p><strong>Délai:</strong> ${devis.delai || 'Non spécifié'}</p>
            </div>
            <div class="detail-section">
                <h4>Statut et Suivi</h4>
                <p><strong>Statut:</strong> ${devis.statut}</p>
                <p><strong>Priorité:</strong> ${devis.priorite}</p>
                <p><strong>Date de création:</strong> ${new Date(devis.date_creation).toLocaleDateString()}</p>
                ${devis.notes_admin ? `<p><strong>Notes:</strong> ${devis.notes_admin}</p>` : ''}
            </div>
        </div>
    `;
}

function editDevis(id) {
    window.location.href = `edit_devis.php?id=${id}`;
}

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
    const notes = prompt('Ajouter des notes administratives:');
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

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function exportDevis() {
    window.open('export_devis.php', '_blank');
}

// Gestion des dropdowns
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('dropdown-toggle')) {
        e.preventDefault();
        const dropdown = e.target.nextElementSibling;
        
        // Fermer tous les autres dropdowns
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            if (menu !== dropdown) {
                menu.style.display = 'none';
            }
        });
        
        // Toggle le dropdown actuel
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    }
    
    // Fermer les dropdowns si on clique ailleurs
    if (!e.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.style.display = 'none';
        });
    }
});

// Auto-refresh des notifications
setInterval(function() {
    // Vérifier s'il y a de nouveaux devis
    fetch('ajax/check_new_devis.php')
        .then(response => response.json())
        .then(data => {
            if (data.new_count > 0) {
                // Mettre à jour l'interface si nécessaire
                console.log(`${data.new_count} nouveaux devis`);
            }
        })
        .catch(error => console.error('Erreur vérification:', error));
}, 60000); // Toutes les minutes
</script>

<?php include '../includes/footer.php'; ?>
