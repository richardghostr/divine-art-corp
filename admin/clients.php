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
$page_title = "Gestion des Clients";

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_client':
                $client_id = (int)$_POST['client_id'];
                $nom = mysqli_real_escape_string($conn, $_POST['nom']);
                $email = mysqli_real_escape_string($conn, $_POST['email']);
                $telephone = mysqli_real_escape_string($conn, $_POST['telephone']);
                $entreprise = mysqli_real_escape_string($conn, $_POST['entreprise']);
                $poste = mysqli_real_escape_string($conn, $_POST['poste']);
                $adresse = mysqli_real_escape_string($conn, $_POST['adresse']);
                $ville = mysqli_real_escape_string($conn, $_POST['ville']);
                $secteur_activite = mysqli_real_escape_string($conn, $_POST['secteur_activite']);
                $statut = mysqli_real_escape_string($conn, $_POST['statut']);
                $notes = mysqli_real_escape_string($conn, $_POST['notes']);
                
                $query = "UPDATE clients SET nom = ?, email = ?, telephone = ?, entreprise = ?, 
                         poste = ?, adresse = ?, ville = ?, secteur_activite = ?, statut = ?, 
                         notes = ?, date_modification = NOW() WHERE id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "ssssssssssi", $nom, $email, $telephone, $entreprise, 
                                     $poste, $adresse, $ville, $secteur_activite, $statut, $notes, $client_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    logActivity($current_admin['id'], 'update_client', 'clients', $client_id, "Client mis à jour: $nom");
                    $success_message = "Client mis à jour avec succès.";
                } else {
                    $error_message = "Erreur lors de la mise à jour du client.";
                }
                break;
                
            case 'add_client':
                $nom = mysqli_real_escape_string($conn, $_POST['nom']);
                $email = mysqli_real_escape_string($conn, $_POST['email']);
                $telephone = mysqli_real_escape_string($conn, $_POST['telephone']);
                $entreprise = mysqli_real_escape_string($conn, $_POST['entreprise']);
                $poste = mysqli_real_escape_string($conn, $_POST['poste']);
                $adresse = mysqli_real_escape_string($conn, $_POST['adresse']);
                $ville = mysqli_real_escape_string($conn, $_POST['ville']);
                $secteur_activite = mysqli_real_escape_string($conn, $_POST['secteur_activite']);
                $statut = mysqli_real_escape_string($conn, $_POST['statut']);
                $notes = mysqli_real_escape_string($conn, $_POST['notes']);
                
                $query = "INSERT INTO clients (nom, email, telephone, entreprise, poste, adresse, ville, 
                         secteur_activite, statut, notes, date_premier_contact) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "ssssssssss", $nom, $email, $telephone, $entreprise, 
                                     $poste, $adresse, $ville, $secteur_activite, $statut, $notes);
                
                if (mysqli_stmt_execute($stmt)) {
                    $client_id = mysqli_insert_id($conn);
                    logActivity($current_admin['id'], 'add_client', 'clients', $client_id, "Nouveau client ajouté: $nom");
                    $success_message = "Client ajouté avec succès.";
                } else {
                    $error_message = "Erreur lors de l'ajout du client.";
                }
                break;
                
            case 'delete_client':
                $client_id = (int)$_POST['client_id'];
                
                // Vérifier s'il y a des projets liés
                $check_query = "SELECT COUNT(*) as count FROM devis WHERE email = (SELECT email FROM clients WHERE id = ?)";
                $check_stmt = mysqli_prepare($conn, $check_query);
                mysqli_stmt_bind_param($check_stmt, "i", $client_id);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                $has_projects = mysqli_fetch_assoc($check_result)['count'] > 0;
                
                if ($has_projects) {
                    $error_message = "Impossible de supprimer ce client car il a des projets associés.";
                } else {
                    $query = "DELETE FROM clients WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "i", $client_id);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        logActivity($current_admin['id'], 'delete_client', 'clients', $client_id, "Client supprimé");
                        $success_message = "Client supprimé avec succès.";
                    } else {
                        $error_message = "Erreur lors de la suppression du client.";
                    }
                }
                break;
        }
    }
}

// Paramètres de pagination et filtres
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$secteur_filter = isset($_GET['secteur']) ? mysqli_real_escape_string($conn, $_GET['secteur']) : '';

// Construction de la requête
$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(nom LIKE ? OR email LIKE ? OR entreprise LIKE ? OR telephone LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ssss';
}

if (!empty($status_filter)) {
    $where_conditions[] = "statut = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($secteur_filter)) {
    $where_conditions[] = "secteur_activite = ?";
    $params[] = $secteur_filter;
    $types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Requête pour compter le total
$count_query = "SELECT COUNT(*) as total FROM clients $where_clause";

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
$query = "SELECT *, 
          (SELECT COUNT(*) FROM devis WHERE email = clients.email) as nb_devis,
          (SELECT SUM(montant_final) FROM devis WHERE email = clients.email AND statut = 'termine') as ca_reel
          FROM clients 
          $where_clause
          ORDER BY date_creation DESC 
          LIMIT ? OFFSET ?";

$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = mysqli_prepare($conn, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$clients = mysqli_stmt_get_result($stmt);

// Statistiques
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN statut = 'prospect' THEN 1 ELSE 0 END) as prospects,
    SUM(CASE WHEN statut = 'client' THEN 1 ELSE 0 END) as clients_actifs,
    SUM(CASE WHEN statut = 'client_vip' THEN 1 ELSE 0 END) as clients_vip,
    SUM(CASE WHEN statut = 'inactif' THEN 1 ELSE 0 END) as inactifs,
    COALESCE(SUM(ca_total), 0) as ca_total,
    COALESCE(AVG(ca_total), 0) as ca_moyen
FROM clients";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Secteurs d'activité pour le filtre
$secteurs_query = "SELECT DISTINCT secteur_activite FROM clients WHERE secteur_activite IS NOT NULL AND secteur_activite != '' ORDER BY secteur_activite";
$secteurs_result = mysqli_query($conn, $secteurs_query);
$secteurs = mysqli_fetch_all($secteurs_result, MYSQLI_ASSOC);

include 'header.php';
?>

<div class="admin-main">
    <?php include 'sidebar.php'; ?>
    
    <main class="main-content">
        <div class="content-header">
            <div class="header-left">
                <h1><i class="fas fa-users"></i> <?php echo $page_title; ?></h1>
                <p>Gérez votre base de données clients</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="showAddClientModal()">
                    <i class="fas fa-plus"></i> Nouveau Client
                </button>
                <button class="btn btn-secondary" onclick="exportClients()">
                    <i class="fas fa-download"></i> Exporter
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
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['total']?? 0); ?></h3>
                    <p>Total Clients</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-orange">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="stat-content">
                   <h3><?php echo number_format($stats['prospects'] ?? 0); ?></h3>
                    <p>Prospects</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-green">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['clients_actifs']?? 0); ?></h3>
                    <p>Clients Actifs</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-purple">
                    <i class="fas fa-crown"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['clients_vip']?? 0); ?></h3>
                    <p>Clients VIP</p>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="filters-section">
            <form method="GET" class="filters-form">
                <div class="filter-group">
                    <input type="text" name="search" placeholder="Rechercher un client..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-group">
                    <select name="status">
                        <option value="">Tous les statuts</option>
                        <option value="prospect" <?php echo $status_filter === 'prospect' ? 'selected' : ''; ?>>Prospect</option>
                        <option value="client" <?php echo $status_filter === 'client' ? 'selected' : ''; ?>>Client</option>
                        <option value="client_vip" <?php echo $status_filter === 'client_vip' ? 'selected' : ''; ?>>Client VIP</option>
                        <option value="inactif" <?php echo $status_filter === 'inactif' ? 'selected' : ''; ?>>Inactif</option>
                    </select>
                </div>
                <div class="filter-group">
                    <select name="secteur">
                        <option value="">Tous les secteurs</option>
                        <?php foreach ($secteurs as $secteur): ?>
                            <option value="<?php echo htmlspecialchars($secteur['secteur_activite']); ?>" 
                                    <?php echo $secteur_filter === $secteur['secteur_activite'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($secteur['secteur_activite']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Filtrer
                </button>
                <a href="clients.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Reset
                </a>
            </form>
        </div>

        <!-- Liste des clients -->
        <div class="clients-grid">
            <?php while ($client = mysqli_fetch_assoc($clients)): ?>
                <div class="client-card">
                    <div class="client-header">
                        <div class="client-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="client-info">
                            <h3><?php echo htmlspecialchars($client['nom']); ?></h3>
                            <p><?php echo htmlspecialchars($client['email']); ?></p>
                            <?php if ($client['entreprise']): ?>
                                <p class="company"><?php echo htmlspecialchars($client['entreprise']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="client-status">
                            <span class="status-badge status-<?php echo $client['statut']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $client['statut'])); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="client-details">
                        <?php if ($client['telephone']): ?>
                            <div class="detail-item">
                                <i class="fas fa-phone"></i>
                                <span><?php echo htmlspecialchars($client['telephone']); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($client['ville']): ?>
                            <div class="detail-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($client['ville']); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($client['secteur_activite']): ?>
                            <div class="detail-item">
                                <i class="fas fa-industry"></i>
                                <span><?php echo htmlspecialchars($client['secteur_activite']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="client-stats">
                        <div class="stat-item">
                            <span class="stat-value"><?php echo $client['nb_devis']; ?></span>
                            <span class="stat-label">Devis</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?php echo number_format($client['ca_reel'] ?: 0, 0, ',', ' '); ?></span>
                            <span class="stat-label">CA (FCFA)</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?php echo $client['date_premier_contact'] ? date('d/m/Y', strtotime($client['date_premier_contact'])) : 'N/A'; ?></span>
                            <span class="stat-label">Premier contact</span>
                        </div>
                    </div>
                    
                    <div class="client-actions">
                        <button class="btn btn-sm btn-primary" onclick="viewClient(<?php echo $client['id']; ?>)">
                            <i class="fas fa-eye"></i> Voir
                        </button>
                        <button class="btn btn-sm btn-secondary" onclick="editClient(<?php echo $client['id']; ?>)">
                            <i class="fas fa-edit"></i> Modifier
                        </button>
                        <button class="btn btn-sm btn-info" onclick="contactClient('<?php echo htmlspecialchars($client['email']); ?>')">
                            <i class="fas fa-envelope"></i> Contact
                        </button>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&secteur=<?php echo urlencode($secteur_filter); ?>" 
                       class="<?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<!-- Modal pour ajouter/modifier un client -->
<div id="clientModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="clientModalTitle">Nouveau Client</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form id="clientForm" method="POST">
                <input type="hidden" name="action" id="clientAction" value="add_client">
                <input type="hidden" name="client_id" id="clientId">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nom">Nom complet *</label>
                        <input type="text" name="nom" id="nom" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" name="email" id="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="telephone">Téléphone</label>
                        <input type="tel" name="telephone" id="telephone">
                    </div>
                    
                    <div class="form-group">
                        <label for="entreprise">Entreprise</label>
                        <input type="text" name="entreprise" id="entreprise">
                    </div>
                    
                    <div class="form-group">
                        <label for="poste">Poste</label>
                        <input type="text" name="poste" id="poste">
                    </div>
                    
                    <div class="form-group">
                        <label for="ville">Ville</label>
                        <input type="text" name="ville" id="ville">
                    </div>
                    
                    <div class="form-group">
                        <label for="secteur_activite">Secteur d'activité</label>
                        <input type="text" name="secteur_activite" id="secteur_activite">
                    </div>
                    
                    <div class="form-group">
                        <label for="statut">Statut</label>
                        <select name="statut" id="statut">
                            <option value="prospect">Prospect</option>
                            <option value="client">Client</option>
                            <option value="client_vip">Client VIP</option>
                            <option value="inactif">Inactif</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="adresse">Adresse complète</label>
                    <textarea name="adresse" id="adresse" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea name="notes" id="notes" rows="4"></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showAddClientModal() {
    document.getElementById('clientModalTitle').textContent = 'Nouveau Client';
    document.getElementById('clientAction').value = 'add_client';
    document.getElementById('clientForm').reset();
    document.getElementById('clientModal').style.display = 'block';
}

function editClient(id) {
    fetch(`../api/get_client_details.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('clientModalTitle').textContent = 'Modifier Client';
            document.getElementById('clientAction').value = 'update_client';
            document.getElementById('clientId').value = data.id;
            
            // Remplir le formulaire
            Object.keys(data).forEach(key => {
                const field = document.getElementById(key);
                if (field) {
                    field.value = data[key] || '';
                }
            });
            
            document.getElementById('clientModal').style.display = 'block';
        });
}

function viewClient(id) {
    window.location.href = `client_details.php?id=${id}`;
}

function contactClient(email) {
    window.location.href = `mailto:${email}`;
}

function exportClients() {
    window.location.href = '../api/export_clients.php';
}

function closeModal() {
    document.getElementById('clientModal').style.display = 'none';
}

// Gestion des modals
document.querySelector('.close').onclick = closeModal;

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>



<style>

</style>