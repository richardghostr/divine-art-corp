<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
// Récupérer les statistiques
$db = new Database();

// Nombre total de devis
$db->query("SELECT COUNT(*) as total FROM devis");
$totalDevis = $db->single()['total'];

// Devis en attente
$db->query("SELECT COUNT(*) as total FROM devis WHERE statut = 'nouveau'");
$devisEnAttente = $db->single()['total'];

// Nombre total de contacts
$db->query("SELECT COUNT(*) as total FROM contacts");
$totalContacts = $db->single()['total'];

// Contacts ce mois
$db->query("SELECT COUNT(*) as total FROM contacts WHERE MONTH(date_creation) = MONTH(CURRENT_DATE()) AND YEAR(date_creation) = YEAR(CURRENT_DATE())");
$contactsCeMois = $db->single()['total'];

// Derniers devis
$db->query("SELECT * FROM devis ORDER BY date_creation DESC LIMIT 5");
$derniersDevis = $db->resultset();

// Derniers contacts
$db->query("SELECT * FROM contacts ORDER BY date_creation DESC LIMIT 5");
$derniersContacts = $db->resultset();
?>

<div class="admin-container">
    <?php include 'sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="admin-header">
            <h1>Tableau de Bord</h1>
            <div class="admin-user">
                <span>Bonjour, <?php echo $_SESSION['admin_user']['username']; ?></span>
                <a href="api/admin.php?action=logout" class="btn btn-outline btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </div>
        </div>
        
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $totalDevis; ?></h3>
                    <p>Total Devis</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $devisEnAttente; ?></h3>
                    <p>En Attente</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $totalContacts; ?></h3>
                    <p>Total Contacts</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $contactsCeMois; ?></h3>
                    <p>Ce Mois</p>
                </div>
            </div>
        </div>
        
        <div class="dashboard-grid">
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Derniers Devis</h2>
                    <a href="?page=admin&section=devis" class="btn btn-outline btn-sm">Voir tout</a>
                </div>
                
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Numéro</th>
                                <th>Client</th>
                                <th>Service</th>
                                <th>Statut</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($derniersDevis as $devis): ?>
                            <tr>
                                <td><?php echo $devis['numero_devis'] ?? 'N/A'; ?></td>
                                <td><?php echo $devis['nom']; ?></td>
                                <td><?php echo ucfirst($devis['service']); ?></td>
                                <td>
                                    <span class="status status-<?php echo $devis['statut']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $devis['statut'])); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($devis['date_creation']); ?></td>
                                <td>
                                    <a href="?page=admin&section=devis&action=view&id=<?php echo $devis['id']; ?>" class="btn btn-sm btn-outline">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Derniers Contacts</h2>
                    <a href="?page=admin&section=contacts" class="btn btn-outline btn-sm">Voir tout</a>
                </div>
                
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Sujet</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($derniersContacts as $contact): ?>
                            <tr>
                                <td><?php echo $contact['nom']; ?></td>
                                <td><?php echo $contact['email']; ?></td>
                                <td><?php echo $contact['sujet'] ?: 'Général'; ?></td>
                                <td><?php echo formatDate($contact['date_creation']); ?></td>
                                <td>
                                    <a href="?page=admin&section=contacts&action=view&id=<?php echo $contact['id']; ?>" class="btn btn-sm btn-outline">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="dashboard-charts">
            <div class="chart-section">
                <h2>Évolution des Demandes</h2>
                <canvas id="demandesChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Graphique des demandes
const ctx = document.getElementById('demandesChart').getContext('2d');
const demandesChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun'],
        datasets: [{
            label: 'Devis',
            data: [12, 19, 15, 25, 22, 30],
            borderColor: '#e74c3c',
            backgroundColor: 'rgba(231, 76, 60, 0.1)',
            tension: 0.4
        }, {
            label: 'Contacts',
            data: [8, 15, 12, 18, 16, 24],
            borderColor: '#3498db',
            backgroundColor: 'rgba(52, 152, 219, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>