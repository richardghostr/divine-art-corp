<?php
// Gestion des finances - Divine Art Corporation
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

// Récupération des statistiques financières
$stats = [];

// Statistiques générales
$query = "SELECT 
          COUNT(*) as total_factures,
          SUM(CASE WHEN statut = 'payee' THEN montant_ttc ELSE 0 END) as ca_realise,
          SUM(CASE WHEN statut = 'envoyee' THEN montant_ttc ELSE 0 END) as ca_en_attente,
          SUM(CASE WHEN statut = 'en_retard' THEN montant_ttc ELSE 0 END) as ca_en_retard,
          SUM(montant_ttc) as ca_total
          FROM factures";
$result = $conn->query($query);
if ($result) {
    $stats = $result->fetch_assoc();
}

// Statistiques mensuelles
$query = "SELECT 
          MONTH(date_emission) as mois,
          YEAR(date_emission) as annee,
          COUNT(*) as nb_factures,
          SUM(montant_ttc) as montant_total,
          SUM(CASE WHEN statut = 'payee' THEN montant_ttc ELSE 0 END) as montant_paye
          FROM factures 
          WHERE YEAR(date_emission) = YEAR(CURDATE())
          GROUP BY YEAR(date_emission), MONTH(date_emission)
          ORDER BY annee DESC, mois DESC";
$result = $conn->query($query);

$stats_mensuelles = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $stats_mensuelles[] = $row;
    }
}

// Récupération des factures avec pagination
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

$query = "SELECT f.*, c.nom as client_nom, c.entreprise, c.email, d.numero_devis
          FROM factures f 
          JOIN clients c ON f.client_id = c.id 
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
$query = "SELECT COUNT(*) as total FROM factures f JOIN clients c ON f.client_id = c.id WHERE $where_clause";
$result = $conn->query($query);
$total_factures = $result->fetch_assoc()['total'];
$total_pages = ceil($total_factures / $limit);

// Traitement des actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Sécuriser les données
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
                
                // Enregistrer dans les logs
                logActivity($_SESSION['admin_id'], 'update_facture_status', 'factures', $facture_id, "Statut mis à jour: $statut");
            } else {
                $message = "Erreur lors de la mise à jour du statut: " . $stmt->error;
                $message_type = "danger";
            }
            $stmt->close();
        }
        
        // Action: Générer une nouvelle facture
        if ($_POST['action'] === 'generate_facture') {
            $devis_id = intval($_POST['devis_id']);
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
                
                // Enregistrer dans les logs
                logActivity($_SESSION['admin_id'], 'generate_facture', 'factures', $stmt->insert_id, "Facture générée: $numero_facture");
            } else {
                $message = "Erreur lors de la génération de la facture: " . $stmt->error;
                $message_type = "danger";
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
          ORDER BY d.date_creation DESC";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $devis_sans_facture[] = $row;
    }
}

// Titre de la page
$page_title = "Gestion Financière";
?>

<?php // Inclure l'en-tête et la barre latérale
require_once 'header.php';
require_once 'sidebar.php';?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-chart-line mr-2"></i> Gestion Financière
        </h1>
        <div>
            <a href="#" class="btn btn-success mr-2" data-toggle="modal" data-target="#generateFactureModal">
                <i class="fas fa-file-invoice"></i> Nouvelle Facture
            </a>
            <a href="#" class="btn btn-info mr-2" data-toggle="modal" data-target="#rapportsModal">
                <i class="fas fa-chart-bar"></i> Rapports
            </a>
            <a href="ajax/export_finances.php" class="btn btn-primary" target="_blank">
                <i class="fas fa-file-export"></i> Exporter
            </a>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <!-- Statistiques financières -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                CA Réalisé</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['ca_realise'] ?? 0, 0, ',', ' '); ?> FCFA
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
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
                                CA En Attente</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['ca_en_attente'] ?? 0, 0, ',', ' '); ?> FCFA
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                CA En Retard</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['ca_en_retard'] ?? 0, 0, ',', ' '); ?> FCFA
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Factures</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_factures'] ?? 0; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-invoice fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphique des revenus mensuels -->
    <div class="row mb-4">
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Évolution du Chiffre d'Affaires</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Répartition des Statuts</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Devis sans facture -->
    <?php if (!empty($devis_sans_facture)): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-warning">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                Devis Terminés Sans Facture (<?php echo count($devis_sans_facture); ?>)
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>Numéro Devis</th>
                            <th>Client</th>
                            <th>Montant</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($devis_sans_facture as $devis): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($devis['numero_devis']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($devis['nom']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($devis['entreprise']); ?></small>
                                </td>
                                <td><?php echo number_format($devis['montant_final'], 0, ',', ' '); ?> FCFA</td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="generateFactureFromDevis(<?php echo $devis['id']; ?>, '<?php echo addslashes($devis['nom']); ?>', <?php echo $devis['montant_final']; ?>)">
                                        <i class="fas fa-file-invoice"></i> Générer Facture
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filtres et recherche -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Liste des Factures</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="mb-4">
                <div class="row">
                    <div class="col-md-3 mb-2">
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
                            <option value="brouillon" <?php echo $status_filter === 'brouillon' ? 'selected' : ''; ?>>Brouillon</option>
                            <option value="envoyee" <?php echo $status_filter === 'envoyee' ? 'selected' : ''; ?>>Envoyée</option>
                            <option value="payee" <?php echo $status_filter === 'payee' ? 'selected' : ''; ?>>Payée</option>
                            <option value="en_retard" <?php echo $status_filter === 'en_retard' ? 'selected' : ''; ?>>En retard</option>
                            <option value="annulee" <?php echo $status_filter === 'annulee' ? 'selected' : ''; ?>>Annulée</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <input type="month" class="form-control" name="month" value="<?php echo htmlspecialchars($month_filter); ?>" onchange="this.form.submit()">
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="finances.php" class="btn btn-secondary btn-block">Réinitialiser</a>
                    </div>
                </div>
            </form>

            <!-- Liste des factures -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="facturesTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
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
                                <td colspan="7" class="text-center">Aucune facture trouvée</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($factures as $facture): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($facture['numero_facture']); ?></strong>
                                        <?php if ($facture['numero_devis']): ?>
                                            <br><small class="text-muted">Devis: <?php echo htmlspecialchars($facture['numero_devis']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($facture['client_nom']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($facture['entreprise']); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo number_format($facture['montant_ttc'], 0, ',', ' '); ?> FCFA</strong><br>
                                        <small class="text-muted">HT: <?php echo number_format($facture['montant_ht'], 0, ',', ' '); ?> FCFA</small>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($facture['date_emission'])); ?></td>
                                    <td>
                                        <?php 
                                        $date_echeance = strtotime($facture['date_echeance']);
                                        $today = time();
                                        $class = '';
                                        if ($facture['statut'] !== 'payee' && $date_echeance < $today) {
                                            $class = 'text-danger font-weight-bold';
                                        }
                                        ?>
                                        <span class="<?php echo $class; ?>">
                                            <?php echo date('d/m/Y', $date_echeance); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $status_class = '';
                                        switch ($facture['statut']) {
                                            case 'brouillon': $status_class = 'secondary'; break;
                                            case 'envoyee': $status_class = 'primary'; break;
                                            case 'payee': $status_class = 'success'; break;
                                            case 'en_retard': $status_class = 'danger'; break;
                                            case 'annulee': $status_class = 'dark'; break;
                                        }
                                        ?>
                                        <span class="badge badge-<?php echo $status_class; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $facture['statut'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-primary" onclick="viewFacture(<?php echo $facture['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-info" onclick="updateFactureStatus(<?php echo $facture['id']; ?>, '<?php echo $facture['statut']; ?>')">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                            <a href="ajax/generate_facture_pdf.php?id=<?php echo $facture['id']; ?>" class="btn btn-sm btn-success" target="_blank">
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
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&month=<?php echo urlencode($month_filter); ?>" aria-label="Précédent">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&month=<?php echo urlencode($month_filter); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&month=<?php echo urlencode($month_filter); ?>" aria-label="Suivant">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Générer Facture -->
<div class="modal fade" id="generateFactureModal" tabindex="-1" role="dialog" aria-labelledby="generateFactureModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="generateFactureModalLabel">Générer une Nouvelle Facture</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="generate_facture">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="devis_id">Devis (optionnel)</label>
                                <select class="form-control" id="devis_id" name="devis_id">
                                    <option value="">Sélectionner un devis</option>
                                    <?php foreach ($devis_sans_facture as $devis): ?>
                                        <option value="<?php echo $devis['id']; ?>" data-montant="<?php echo $devis['montant_final']; ?>">
                                            <?php echo htmlspecialchars($devis['numero_devis'] . ' - ' . $devis['nom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="client_id">Client *</label>
                                <select class="form-control" id="client_id" name="client_id" required>
                                    <option value="">Sélectionner un client</option>
                                    <?php foreach ($clients as $client): ?>
                                        <option value="<?php echo $client['id']; ?>">
                                            <?php echo htmlspecialchars($client['nom'] . ' - ' . $client['entreprise']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="montant_ht">Montant HT (FCFA) *</label>
                                <input type="number" class="form-control" id="montant_ht" name="montant_ht" min="0" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="taux_tva">Taux TVA (%)</label>
                                <input type="number" class="form-control" id="taux_tva" name="taux_tva" value="19.25" min="0" max="100" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="date_echeance">Date d'échéance *</label>
                                <input type="date" class="form-control" id="date_echeance" name="date_echeance" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <strong>Montant TTC calculé:</strong> <span id="montant_ttc_display">0 FCFA</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Générer la Facture</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Mettre à jour le statut de facture -->
<div class="modal fade" id="updateFactureStatusModal" tabindex="-1" role="dialog" aria-labelledby="updateFactureStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateFactureStatusModalLabel">Mettre à jour le statut de la facture</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="facture_id" id="status_facture_id">
                    <div class="form-group">
                        <label for="statut">Statut</label>
                        <select class="form-control" id="statut" name="statut" required onchange="togglePaymentFields()">
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
                            <select class="form-control" id="mode_paiement" name="mode_paiement">
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
                            <input type="text" class="form-control" id="reference_paiement" name="reference_paiement" placeholder="Numéro de transaction, chèque, etc.">
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

<!-- Modal Voir Facture -->
<div class="modal fade" id="viewFactureModal" tabindex="-1" role="dialog" aria-labelledby="viewFactureModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewFactureModalLabel">Détails de la Facture</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
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
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                <a href="#" id="downloadFacturePdf" class="btn btn-primary" target="_blank">Télécharger PDF</a>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript pour les interactions et graphiques -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Calcul automatique du montant TTC
function calculateTTC() {
    const montantHT = parseFloat(document.getElementById('montant_ht').value) || 0;
    const tauxTVA = parseFloat(document.getElementById('taux_tva').value) || 0;
    const montantTVA = montantHT * (tauxTVA / 100);
    const montantTTC = montantHT + montantTVA;
    
    document.getElementById('montant_ttc_display').textContent = new Intl.NumberFormat('fr-FR').format(montantTTC) + ' FCFA';
}

// Événements pour le calcul automatique
document.getElementById('montant_ht').addEventListener('input', calculateTTC);
document.getElementById('taux_tva').addEventListener('input', calculateTTC);

// Remplir automatiquement les champs lors de la sélection d'un devis
document.getElementById('devis_id').addEventListener('change', function() {
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
    document.getElementById('date_echeance').value = echeance.toISOString().split('T')[0];
});

// Fonction pour voir les détails d'une facture
function viewFacture(id) {
    const modal = $('#viewFactureModal');
    const content = $('#viewFactureContent');
    
    modal.modal('show');
    content.html('<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="sr-only">Chargement...</span></div><p class="mt-2">Chargement des détails de la facture...</p></div>');
    
    $('#downloadFacturePdf').attr('href', 'ajax/generate_facture_pdf.php?id=' + id);
    
    $.ajax({
        url: 'ajax/get_facture_details.php',
        type: 'GET',
        data: { id: id },
        success: function(response) {
            content.html(response);
        },
        error: function() {
            content.html('<div class="alert alert-danger">Erreur lors du chargement des détails de la facture.</div>');
        }
    });
}

// Fonction pour mettre à jour le statut d'une facture
function updateFactureStatus(id, currentStatus) {
    $('#status_facture_id').val(id);
    $('#statut').val(currentStatus);
    togglePaymentFields();
    $('#updateFactureStatusModal').modal('show');
}

// Afficher/masquer les champs de paiement
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

// Fonction pour générer une facture à partir d'un devis
function generateFactureFromDevis(devisId, clientNom, montant) {
    $('#devis_id').val(devisId);
    $('#montant_ht').val(montant);
    calculateTTC();
    
    // Trouver le client correspondant (approximatif)
    const clientSelect = document.getElementById('client_id');
    for (let i = 0; i < clientSelect.options.length; i++) {
        if (clientSelect.options[i].text.includes(clientNom)) {
            clientSelect.selectedIndex = i;
            break;
        }
    }
    
    $('#generateFactureModal').modal('show');
}

// Graphique des revenus mensuels
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: [
            <?php 
            foreach (array_reverse($stats_mensuelles) as $stat) {
                $mois = ['', 'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
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
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
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
            borderColor: 'rgb(255, 99, 132)',
            backgroundColor: 'rgba(255, 99, 132, 0.2)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
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

// Graphique des statuts
const statusCtx = document.getElementById('statusChart').getContext('2d');
const statusChart = new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: ['Payées', 'Envoyées', 'En retard', 'Brouillons', 'Annulées'],
        datasets: [{
            data: [
                <?php echo $stats['ca_realise'] ?? 0; ?>,
                <?php echo $stats['ca_en_attente'] ?? 0; ?>,
                <?php echo $stats['ca_en_retard'] ?? 0; ?>,
                0, // Brouillons - à calculer si nécessaire
                0  // Annulées - à calculer si nécessaire
            ],
            backgroundColor: [
                '#28a745',
                '#007bff',
                '#dc3545',
                '#6c757d',
                '#343a40'
            ]
        }]
    },
    options: {
        responsive: true,
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
</script>

<?php
// Fermer la connexion à la base de données
$conn->close();
?>

