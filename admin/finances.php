<?php
// Gestion des finances - Divine Art Corporation
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

// Récupération des statistiques financières
$stats = [];
$query = "SELECT 
          COUNT(*) as total_factures,
          SUM(CASE WHEN statut = 'payee' THEN montant_ttc ELSE 0 END) as ca_realise,
          SUM(CASE WHEN statut = 'envoyee' THEN montant_ttc ELSE 0 END) as ca_en_attente,
          SUM(CASE WHEN statut = 'en_retard' THEN montant_ttc ELSE 0 END) as ca_en_retard,
          SUM(montant_ttc) as ca_total,
          AVG(montant_ttc) as montant_moyen
          FROM factures";
$result = $conn->query($query);
if ($result) {
    $stats = $result->fetch_assoc();
}

// Statistiques mensuelles pour le graphique
$stats_mensuelles = [];
$query = "SELECT 
          MONTH(date_emission) as mois,
          YEAR(date_emission) as annee,
          COUNT(*) as nb_factures,
          SUM(montant_ttc) as montant_total,
          SUM(CASE WHEN statut = 'payee' THEN montant_ttc ELSE 0 END) as montant_paye
          FROM factures 
          WHERE YEAR(date_emission) = YEAR(CURDATE())
          GROUP BY YEAR(date_emission), MONTH(date_emission)
          ORDER BY annee DESC, mois DESC
          LIMIT 12";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $stats_mensuelles[] = $row;
    }
}

// Pagination et filtrage
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
$month_filter = isset($_GET['month']) ? $conn->real_escape_string($_GET['month']) : '';

// Construction de la clause WHERE
$where_clause = "1=1";
if (!empty($search)) {
    $where_clause .= " AND (f.numero_facture LIKE '%$search%' OR c.nom LIKE '%$search%' OR c.entreprise LIKE '%$search%')";
}
if (!empty($status_filter)) {
    $where_clause .= " AND f.statut = '$status_filter'";
}
if (!empty($month_filter)) {
    $where_clause .= " AND DATE_FORMAT(f.date_emission, '%Y-%m') = '$month_filter'";
}

// Récupération des factures
$query = "SELECT f.*, c.nom as client_nom, c.entreprise, c.email, d.numero_devis
          FROM factures f 
          LEFT JOIN clients c ON f.client_id = c.id 
          LEFT JOIN devis d ON f.devis_id = d.id
          WHERE $where_clause
          ORDER BY f.date_emission DESC
          LIMIT $offset, $limit";
$result = $conn->query($query);

$factures = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $factures[] = $row;
    }
}

// Compter le nombre total de factures pour la pagination
$query = "SELECT COUNT(*) as total FROM factures f LEFT JOIN clients c ON f.client_id = c.id WHERE $where_clause";
$result = $conn->query($query);
$total_factures = $result->fetch_assoc()['total'];
$total_pages = ceil($total_factures / $limit);

// Traitement des actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $facture_id = isset($_POST['facture_id']) ? intval($_POST['facture_id']) : 0;
        
        // Action: Mettre à jour le statut d'une facture
        if ($_POST['action'] === 'update_status' && $facture_id > 0) {
            $statut = $conn->real_escape_string($_POST['statut']);
            $date_paiement = null;
            $mode_paiement = null;
            $reference_paiement = null;
            
            if ($statut === 'payee') {
                $date_paiement = date('Y-m-d');
                $mode_paiement = isset($_POST['mode_paiement']) ? $conn->real_escape_string($_POST['mode_paiement']) : '';
                $reference_paiement = isset($_POST['reference_paiement']) ? $conn->real_escape_string($_POST['reference_paiement']) : '';
            }
            
            $query = "UPDATE factures SET statut = ?, date_paiement = ?, mode_paiement = ?, reference_paiement = ?, date_modification = NOW() WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssssi", $statut, $date_paiement, $mode_paiement, $reference_paiement, $facture_id);
            
            if ($stmt->execute()) {
                $message = "Le statut de la facture a été mis à jour avec succès.";
                $message_type = "success";
                logActivity($_SESSION['admin_id'], 'update_facture_status', 'factures', $facture_id, "Statut: $statut");
            } else {
                $message = "Erreur lors de la mise à jour: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }
        
        // Action: Générer une nouvelle facture
        if ($_POST['action'] === 'generate_facture') {
            $devis_id = isset($_POST['devis_id']) ? intval($_POST['devis_id']) : null;
            $client_id = intval($_POST['client_id']);
            $montant_ht = floatval($_POST['montant_ht']);
            $taux_tva = floatval($_POST['taux_tva']);
            $date_echeance = $conn->real_escape_string($_POST['date_echeance']);
            
            $montant_tva = $montant_ht * ($taux_tva / 100);
            $montant_ttc = $montant_ht + $montant_tva;
            
            // Générer le numéro de facture
            $year = date('Y');
            $month = date('m');
            $query = "SELECT COUNT(*) as count FROM factures WHERE YEAR(date_emission) = $year AND MONTH(date_emission) = $month";
            $result = $conn->query($query);
            $count = $result->fetch_assoc()['count'] + 1;
            $numero_facture = "FAC" . $year . $month . str_pad($count, 4, '0', STR_PAD_LEFT);
            
            $query = "INSERT INTO factures (numero_facture, devis_id, client_id, montant_ht, taux_tva, montant_tva, montant_ttc, date_emission, date_echeance) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("siidddds", $numero_facture, $devis_id, $client_id, $montant_ht, $taux_tva, $montant_tva, $montant_ttc, $date_echeance);
            
            if ($stmt->execute()) {
                $message = "La facture $numero_facture a été générée avec succès.";
                $message_type = "success";
                logActivity($_SESSION['admin_id'], 'generate_facture', 'factures', $stmt->insert_id, "Facture: $numero_facture");
            } else {
                $message = "Erreur lors de la génération: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }
    }
}

// Récupérer les clients pour la génération de factures
$clients = [];
$query = "SELECT id, nom, entreprise FROM clients WHERE statut IN ('client', 'client_vip') ORDER BY nom ASC";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $clients[] = $row;
    }
}

// Récupérer les devis terminés sans facture
$devis_sans_facture = [];
$query = "SELECT d.id, d.numero_devis, d.nom, d.entreprise, d.montant_final 
          FROM devis d 
          LEFT JOIN factures f ON d.id = f.devis_id 
          WHERE d.statut = 'termine' AND f.id IS NULL 
          ORDER BY d.date_creation DESC
          LIMIT 10";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $devis_sans_facture[] = $row;
    }
}

$page_title = "Gestion Financière";
require_once 'header.php';
require_once 'sidebar.php';
?>

<main class="admin-main">
    <div class="content-header">
        <div class="header-left">
            <h1><i class="fas fa-chart-line"></i> Gestion Financière</h1>
            <p>Gérez vos factures et suivez votre chiffre d'affaires</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-outline" onclick="exportFinances()">
                <i class="fas fa-file-export"></i> Exporter
            </button>
            <button class="btn btn-outline" onclick="openModal('rapportsModal')">
                <i class="fas fa-chart-bar"></i> Rapports
            </button>
            <button class="btn btn-primary" onclick="openModal('generateFactureModal')">
                <i class="fas fa-plus"></i> Nouvelle Facture
            </button>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <!-- Statistiques financières -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon bg-green">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['ca_realise'] ?? 0, 0, ',', ' '); ?> FCFA</h3>
                <p>CA Réalisé</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-orange">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['ca_en_attente'] ?? 0, 0, ',', ' '); ?> FCFA</h3>
                <p>CA En Attente</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-red">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['ca_en_retard'] ?? 0, 0, ',', ' '); ?> FCFA</h3>
                <p>CA En Retard</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-blue">
                <i class="fas fa-file-invoice"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['total_factures'] ?? 0; ?></h3>
                <p>Total Factures</p>
            </div>
        </div>
    </div>

    <!-- Graphique des revenus -->
    <div class="dashboard-grid">
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-chart-area"></i> Évolution du CA</h3>
                <button class="btn btn-sm btn-outline" onclick="toggleChartPeriod()">
                    <i class="fas fa-calendar"></i> Période
                </button>
            </div>
            <div class="card-content">
                <canvas id="revenueChart" width="400" height="200"></canvas>
            </div>
        </div>
        
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-chart-pie"></i> Répartition des Statuts</h3>
            </div>
            <div class="card-content">
                <canvas id="statusChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Devis sans facture -->
    <?php if (!empty($devis_sans_facture)): ?>
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-exclamation-triangle"></i> Devis Terminés Sans Facture</h3>
            <span class="badge status-pending"><?php echo count($devis_sans_facture); ?></span>
        </div>
        <div class="card-content">
            <div class="devis-list">
                <?php foreach ($devis_sans_facture as $devis): ?>
                    <div class="devis-item">
                        <div class="devis-info">
                            <h5><?php echo htmlspecialchars($devis['numero_devis']); ?></h5>
                            <p><?php echo htmlspecialchars($devis['nom']); ?> - <?php echo htmlspecialchars($devis['entreprise']); ?></p>
                            <span class="amount"><?php echo number_format($devis['montant_final'], 0, ',', ' '); ?> FCFA</span>
                        </div>
                        <button class="btn btn-sm btn-primary" onclick="generateFactureFromDevis(<?php echo $devis['id']; ?>, '<?php echo addslashes($devis['nom']); ?>', <?php echo $devis['montant_final']; ?>)">
                            <i class="fas fa-file-invoice"></i> Générer Facture
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filtres -->
    <div class="filters-bar">
        <div class="filter-tabs">
            <a href="?status=" class="filter-tab <?php echo empty($status_filter) ? 'active' : ''; ?>">
                Toutes
            </a>
            <a href="?status=brouillon" class="filter-tab <?php echo $status_filter === 'brouillon' ? 'active' : ''; ?>">
                Brouillons
            </a>
            <a href="?status=envoyee" class="filter-tab <?php echo $status_filter === 'envoyee' ? 'active' : ''; ?>">
                Envoyées
            </a>
            <a href="?status=payee" class="filter-tab <?php echo $status_filter === 'payee' ? 'active' : ''; ?>">
                Payées
            </a>
            <a href="?status=en_retard" class="filter-tab <?php echo $status_filter === 'en_retard' ? 'active' : ''; ?>">
                En retard
            </a>
        </div>
        <div class="filter-actions">
            <form method="GET" class="filter-form">
                <input type="month" name="month" class="filter-select" value="<?php echo htmlspecialchars($month_filter); ?>" onchange="this.form.submit()">
                <input type="text" name="search" class="filter-search" placeholder="Rechercher..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- Liste des factures -->
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Numéro</th>
                    <th>Client</th>
                    <th>Montant TTC</th>
                    <th>Date Émission</th>
                    <th>Date Échéance</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($factures)): ?>
                    <tr>
                        <td colspan="7" class="text-center">
                            <div class="empty-state">
                                <i class="fas fa-file-invoice"></i>
                                <p>Aucune facture trouvée</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($factures as $facture): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($facture['numero_facture']); ?></strong>
                                <?php if ($facture['numero_devis']): ?>
                                    <br><small>Devis: <?php echo htmlspecialchars($facture['numero_devis']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="client-info">
                                    <div><?php echo htmlspecialchars($facture['client_nom'] ?? 'Client supprimé'); ?></div>
                                    <small><?php echo htmlspecialchars($facture['entreprise'] ?? ''); ?></small>
                                </div>
                            </td>
                            <td>
                                <div class="budget-info">
                                    <strong><?php echo number_format($facture['montant_ttc'], 0, ',', ' '); ?> FCFA</strong>
                                    <small>HT: <?php echo number_format($facture['montant_ht'], 0, ',', ' '); ?> FCFA</small>
                                </div>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($facture['date_emission'])); ?></td>
                            <td>
                                <?php 
                                $date_echeance = strtotime($facture['date_echeance']);
                                $today = time();
                                $is_overdue = ($facture['statut'] !== 'payee' && $date_echeance < $today);
                                ?>
                                <span class="<?php echo $is_overdue ? 'text-danger' : ''; ?>">
                                    <?php echo date('d/m/Y', $date_echeance); ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                $status_class = '';
                                switch ($facture['statut']) {
                                    case 'brouillon': $status_class = 'status-pending'; break;
                                    case 'envoyee': $status_class = 'status-nouveau'; break;
                                    case 'payee': $status_class = 'status-termine'; break;
                                    case 'en_retard': $status_class = 'status-annule'; break;
                                    case 'annulee': $status_class = 'status-inactif'; break;
                                }
                                ?>
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $facture['statut'])); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-outline" onclick="viewFacture(<?php echo $facture['id']; ?>)" title="Voir">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline" onclick="updateFactureStatus(<?php echo $facture['id']; ?>, '<?php echo $facture['statut']; ?>')" title="Statut">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                    <a href="ajax/generate_facture_pdf.php?id=<?php echo $facture['id']; ?>" class="btn btn-sm btn-outline" target="_blank" title="PDF">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
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
        <div class="pagination-container">
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&month=<?php echo urlencode($month_filter); ?>" 
                       class="<?php echo $page == $i ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>
</main>

<!-- Modal Générer Facture -->
<div class="modal" id="generateFactureModal">
    <div class="modal-content large">
        <div class="modal-header">
            <h3>Générer une Nouvelle Facture</h3>
            <button class="close" onclick="closeModal('generateFactureModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="generate_facture">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="devis_id">Devis (optionnel)</label>
                        <select id="devis_id" name="devis_id">
                            <option value="">Sélectionner un devis</option>
                            <?php foreach ($devis_sans_facture as $devis): ?>
                                <option value="<?php echo $devis['id']; ?>" data-montant="<?php echo $devis['montant_final']; ?>">
                                    <?php echo htmlspecialchars($devis['numero_devis'] . ' - ' . $devis['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="client_id">Client *</label>
                        <select id="client_id" name="client_id" required>
                            <option value="">Sélectionner un client</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?php echo $client['id']; ?>">
                                    <?php echo htmlspecialchars($client['nom'] . ' - ' . $client['entreprise']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="montant_ht">Montant HT (FCFA) *</label>
                        <input type="number" id="montant_ht" name="montant_ht" min="0" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="taux_tva">Taux TVA (%)</label>
                        <input type="number" id="taux_tva" name="taux_tva" value="19.25" min="0" max="100" step="0.01">
                    </div>
                </div>
                <div class="form-group">
                    <label for="date_echeance">Date d'échéance *</label>
                    <input type="date" id="date_echeance" name="date_echeance" required>
                </div>
                <div class="alert alert-info">
                    <strong>Montant TTC calculé:</strong> <span id="montant_ttc_display">0 FCFA</span>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal('generateFactureModal')">Annuler</button>
                    <button type="submit" class="btn btn-primary">Générer la Facture</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Mettre à jour le statut de facture -->
<div class="modal" id="updateFactureStatusModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Mettre à jour le statut de la facture</h3>
            <button class="close" onclick="closeModal('updateFactureStatusModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="facture_id" id="status_facture_id">
                <div class="form-group">
                    <label for="statut">Statut</label>
                    <select id="statut" name="statut" required onchange="togglePaymentFields()">
                        <option value="brouillon">Brouillon</option>
                        <option value="envoyee">Envoyée</option>
                        <option value="payee">Payée</option>
                        <option value="en_retard">En retard</option>
                        <option value="annulee">Annulée</option>
                    </select>
                </div>
                <div id="payment_fields" style="display: none;">
                    <div class="form-group">
                        <label for="mode_paiement">Mode de paiement</label>
                        <select id="mode_paiement" name="mode_paiement">
                            <option value="">Sélectionner</option>
                            <option value="virement">Virement bancaire</option>
                            <option value="cheque">Chèque</option>
                            <option value="especes">Espèces</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="carte">Carte bancaire</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="reference_paiement">Référence de paiement</label>
                        <input type="text" id="reference_paiement" name="reference_paiement" placeholder="Numéro de transaction, chèque, etc.">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal('updateFactureStatusModal')">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Voir Facture -->
<div class="modal" id="viewFactureModal">
    <div class="modal-content large">
        <div class="modal-header">
            <h3>Détails de la Facture</h3>
            <button class="close" onclick="closeModal('viewFactureModal')">&times;</button>
        </div>
        <div class="modal-body" id="viewFactureContent">
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Chargement...</span>
                </div>
                <p class="mt-2">Chargement des détails de la facture...</p>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('viewFactureModal')">Fermer</button>
            <a href="#" id="downloadFacturePdf" class="btn btn-primary" target="_blank">Télécharger PDF</a>
        </div>
    </div>
</div>

<style>
.devis-list {
    display: flex;
    flex-direction: column;
    gap: var(--admin-space-md);
}

.devis-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--admin-space-md);
    border: 1px solid var(--admin-border);
    border-radius: var(--admin-radius-md);
    transition: var(--admin-transition);
}

.devis-item:hover {
    background: var(--admin-border-light);
}

.devis-info h5 {
    margin: 0 0 var(--admin-space-xs) 0;
    font-size: 1rem;
    color: var(--admin-text-primary);
}

.devis-info p {
    margin: 0 0 var(--admin-space-xs) 0;
    font-size: 0.875rem;
    color: var(--admin-text-secondary);
}

.devis-info .amount {
    font-weight: 600;
    color: var(--admin-success);
}

.text-danger {
    color: var(--admin-danger) !important;
    font-weight: 600;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: var(--admin-space-md);
    padding: var(--admin-space-lg);
    border-top: 1px solid var(--admin-border-light);
}

.spinner-border {
    width: 2rem;
    height: 2rem;
    border: 0.25em solid currentColor;
    border-right-color: transparent;
    border-radius: 50%;
    animation: spinner-border 0.75s linear infinite;
}

@keyframes spinner-border {
    to { transform: rotate(360deg); }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Fonctions JavaScript
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Calcul automatique du montant TTC
function calculateTTC() {
    const montantHT = parseFloat(document.getElementById('montant_ht').value) || 0;
    const tauxTVA = parseFloat(document.getElementById('taux_tva').value) || 0;
    const montantTVA = montantHT * (tauxTVA / 100);
    const montantTTC = montantHT + montantTVA;
    
    document.getElementById('montant_ttc_display').textContent = new Intl.NumberFormat('fr-FR').format(montantTTC) + ' FCFA';
}

// Événements pour le calcul automatique
document.getElementById('montant_ht')?.addEventListener('input', calculateTTC);
document.getElementById('taux_tva')?.addEventListener('input', calculateTTC);

// Remplir automatiquement les champs lors de la sélection d'un devis
document.getElementById('devis_id')?.addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    if (selectedOption.value) {
        const montant = selectedOption.getAttribute('data-montant');
        if (montant) {
            document.getElementById('montant_ht').value = montant;
            calculateTTC();
        }
    }
});

// Définir la date d'échéance par défaut (30 jours)
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date();
    const echeance = new Date(today.getTime() + (30 * 24 * 60 * 60 * 1000));
    const dateInput = document.getElementById('date_echeance');
    if (dateInput) {
        dateInput.value = echeance.toISOString().split('T')[0];
    }
});

function viewFacture(id) {
    const modal = document.getElementById('viewFactureModal');
    const content = document.getElementById('viewFactureContent');
    
    modal.style.display = 'flex';
    content.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="sr-only">Chargement...</span></div><p class="mt-2">Chargement des détails de la facture...</p></div>';
    
    document.getElementById('downloadFacturePdf').href = 'ajax/generate_facture_pdf.php?id=' + id;
    
    fetch('ajax/get_facture_details.php?id=' + id)
        .then(response => response.text())
        .then(data => {
            content.innerHTML = data;
        })
        .catch(error => {
            content.innerHTML = '<div class="alert alert-error">Erreur lors du chargement des détails de la facture.</div>';
        });
}

function updateFactureStatus(id, currentStatus) {
    document.getElementById('status_facture_id').value = id;
    document.getElementById('statut').value = currentStatus;
    togglePaymentFields();
    openModal('updateFactureStatusModal');
}

function togglePaymentFields() {
    const statut = document.getElementById('statut').value;
    const paymentFields = document.getElementById('payment_fields');
    
    if (statut === 'payee') {
        paymentFields.style.display = 'block';
        document.getElementById('mode_paiement').required = true;
    } else {
        paymentFields.style.display = 'none';
        document.getElementById('mode_paiement').required = false;
    }
}

function generateFactureFromDevis(devisId, clientNom, montant) {
    document.getElementById('devis_id').value = devisId;
    document.getElementById('montant_ht').value = montant;
    calculateTTC();
    
    // Trouver le client correspondant
    const clientSelect = document.getElementById('client_id');
    for (let i = 0; i < clientSelect.options.length; i++) {
        if (clientSelect.options[i].text.includes(clientNom)) {
            clientSelect.selectedIndex = i;
            break;
        }
    }
    
    openModal('generateFactureModal');
}

function exportFinances() {
    window.open('ajax/export_finances.php', '_blank');
}

// Graphique des revenus mensuels
const revenueCtx = document.getElementById('revenueChart');
if (revenueCtx) {
    const revenueChart = new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: [
                <?php 
                $mois = ['', 'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
                foreach (array_reverse($stats_mensuelles) as $stat) {
                    echo "'" . $mois[$stat['mois']] . " " . $stat['annee'] . "',";
                }
                ?>
            ],
            datasets: [{
                label: 'CA Réalisé',
                data: [
                    <?php 
                    foreach (array_reverse($stats_mensuelles) as $stat) {
                        echo $stat['montant_paye'] . ',';
                    }
                    ?>
                ],
                borderColor: 'rgb(39, 174, 96)',
                backgroundColor: 'rgba(39, 174, 96, 0.1)',
                tension: 0.1
            }, {
                label: 'CA Total',
                data: [
                    <?php 
                    foreach (array_reverse($stats_mensuelles) as $stat) {
                        echo $stat['montant_total'] . ',';
                    }
                    ?>
                ],
                borderColor: 'rgb(52, 152, 219)',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('fr-FR').format(value) + ' FCFA';
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + new Intl.NumberFormat('fr-FR').format(context.parsed.y) + ' FCFA';
                        }
                    }
                }
            }
        }
    });
}

// Graphique des statuts
const statusCtx = document.getElementById('statusChart');
if (statusCtx) {
    const statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Payées', 'Envoyées', 'En retard', 'Brouillons'],
            datasets: [{
                data: [
                    <?php echo $stats['ca_realise'] ?? 0; ?>,
                    <?php echo $stats['ca_en_attente'] ?? 0; ?>,
                    <?php echo $stats['ca_en_retard'] ?? 0; ?>,
                    <?php echo ($stats['ca_total'] ?? 0) - ($stats['ca_realise'] ?? 0) - ($stats['ca_en_attente'] ?? 0) - ($stats['ca_en_retard'] ?? 0); ?>
                ],
                backgroundColor: [
                    '#27ae60',
                    '#3498db',
                    '#e74c3c',
                    '#95a5a6'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + new Intl.NumberFormat('fr-FR').format(context.parsed) + ' FCFA';
                        }
                    }
                }
            }
        }
    });
}

// Fermer les modals en cliquant à l'extérieur
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
    }
});
</script>

<?php $conn->close(); ?>
