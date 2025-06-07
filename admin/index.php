<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Vérification de l'authentification
$auth = new Auth();
$auth->requireAuth();

// Récupération de l'admin connecté
$currentAdmin = $auth->getCurrentUser();

// Fonction pour calculer la progression
function calculateProgress($start_date, $end_date)
{
    if (!$start_date || !$end_date) return 0;

    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $now = new DateTime();

    if ($now < $start) return 0;
    if ($now > $end) return 100;

    $total = $end->diff($start)->days;
    $elapsed = $now->diff($start)->days;

    if ($total <= 0) return 100;

    $progress = ($elapsed / $total) * 100;
    return min(100, max(0, round($progress)));
}

$db = new DatabaseHelper();

try {
    // Statistiques principales
    $stats = [
        'active_projects' => $db->selectOne("SELECT COUNT(*) as count FROM projets WHERE statut = 'en_cours'")['count'] ?? 0,
        'completed_projects' => $db->selectOne("SELECT COUNT(*) as count FROM projets WHERE statut = 'termine'")['count'] ?? 0,
        'pending_quotes' => $db->selectOne("SELECT COUNT(*) as count FROM devis WHERE statut = 'nouveau'")['count'] ?? 0,
        'revenue' => $db->selectOne("
            SELECT COALESCE(SUM(f.montant_ttc), 0) as total 
            FROM factures f
            JOIN devis d ON f.devis_id = d.id
            WHERE f.statut = 'payee' AND YEAR(f.date_paiement) = YEAR(CURDATE())
        ")['total'] ?? 0
    ];

    // Projets en cours avec leurs devis associés
    $active_projects = $db->select("
        SELECT p.*, d.nom as client_name, d.entreprise, s.nom as service_name, s.icone
        FROM projets p
        JOIN devis d ON p.devis_id = d.id
        LEFT JOIN services s ON d.service = s.slug
        WHERE p.statut = 'en_cours'
        ORDER BY p.date_fin_prevue ASC
        LIMIT 3
    ");

    // Activité récente (combinaison devis, contacts et actions admin)
    $recent_activity = $db->select("
        (SELECT 
            'devis' as type,
            CONCAT('Nouveau devis #', numero_devis) as title,
            CONCAT('De ', nom, IF(entreprise IS NOT NULL, CONCAT(' (', entreprise, ')'), '')) as description,
            date_creation as created_at,
            '/admin/devis.php?action=view&id=' as url_path,
            id
        FROM devis
        ORDER BY date_creation DESC
        LIMIT 2)
        
        UNION ALL
        
        (SELECT 
            'contact' as type,
            CONCAT('Nouveau contact #', numero_contact) as title,
            CONCAT('Sujet: ', sujet) as description,
            date_creation as created_at,
            '/admin/contacts.php?action=view&id=' as url_path,
            id
        FROM contacts
        ORDER BY date_creation DESC
        LIMIT 2)
        
        UNION ALL
        
        (SELECT 
            'log' as type,
            action as title,
            CONCAT('Par admin #', user_id) as description,
            date_creation as created_at,
            '#' as url_path,
            id
        FROM logs_activite
        WHERE user_id = ?
        ORDER BY date_creation DESC
        LIMIT 1)
        
        ORDER BY created_at DESC
        LIMIT 5
    ", [$currentAdmin['id']]);

    // Clients récents avec leur CA
    $recent_clients = $db->select("
        SELECT 
            c.id,
            c.nom,
            c.email,
            c.entreprise,
            c.statut,
            c.nb_projets as project_count,
            c.ca_total as total_spent,
            c.date_dernier_contact as last_project_date
        FROM clients c
        ORDER BY c.date_dernier_contact DESC
        LIMIT 3
    ");

    // Échéances proches
    $upcoming_deadlines = $db->select("
        SELECT 
            p.id,
            p.nom as project_name,
            p.date_fin_prevue,
            DATEDIFF(p.date_fin_prevue, CURDATE()) as days_remaining,
            d.nom as client_name,
            d.entreprise,
            s.nom as service_name
        FROM projets p
        JOIN devis d ON p.devis_id = d.id
        LEFT JOIN services s ON d.service = s.slug
        WHERE p.statut = 'en_cours'
        AND p.date_fin_prevue >= CURDATE()
        ORDER BY p.date_fin_prevue ASC
        LIMIT 3
    ");

    // Données pour le graphique de revenus
    $revenue_data = $db->select("
        SELECT 
            DATE_FORMAT(date_paiement, '%Y-%m') as month,
            SUM(montant_ttc) as amount
        FROM factures
        WHERE statut = 'payee'
        AND date_paiement >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(date_paiement, '%Y-%m')
        ORDER BY month ASC
    ");

    // Si pas de données, créer des données de démo pour le graphique
    if (empty($revenue_data)) {
        $revenue_data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $revenue_data[] = [
                'month' => $month,
                'amount' => rand(500000, 2000000) // Valeurs aléatoires pour la démo
            ];
        }
    }
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    // Valeurs par défaut en cas d'erreur
    $stats = [
        'active_projects' => 0,
        'completed_projects' => 0,
        'pending_quotes' => 0,
        'revenue' => 0
    ];
    $active_projects = [];
    $recent_activity = [];
    $recent_clients = [];
    $upcoming_deadlines = [];
    $revenue_data = [];
}

// Inclure l'en-tête et la barre latérale
require_once 'header.php';
require_once 'sidebar.php';
?>

<!-- Contenu Principal -->
<main class="admin-main">
    <!-- En-tête de bienvenue -->
    <div class="welcome-header">
        <div class="welcome-content">
            <div class="welcome-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="welcome-text">
                <h1>Bienvenue, <?= htmlspecialchars($currentAdmin['nom']) ?></h1>
                <p>Tableau de bord - <?= date('d/m/Y') ?></p>
            </div>
        </div>
        <div class="welcome-actions">
            <button class="btn btn-primary" onclick="openModal('new-project-modal')">
                <i class="fas fa-plus"></i><a href="create_project.php" style="text-decoration: none;color:#fff">Nouveau Projet</a> 
            </button>
        </div>
    </div>

    <!-- Statistiques principales -->
    <div class="stats-grid">
        <!-- Projets Actifs -->
        <div class="stat-card">
            <div class="stat-icon bg-primary-light">
                <i class="fas fa-tasks text-primary"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-label">Projets Actifs</h3>
                <p class="stat-value" data-count="<?= htmlspecialchars($stats['revenue'] ?? 0) ?>">0</p>
            </div>
            <div class="stat-trend positive">
                <i class="fas fa-arrow-up"></i>
                <span class="trend-value">12%</span>
            </div>
        </div>

        <!-- Projets Terminés -->
        <div class="stat-card">
            <div class="stat-icon bg-success-light">
                <i class="fas fa-check-circle text-success"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-label">Projets Terminés</h3>

                <p class="stat-value" data-count="<?= htmlspecialchars($stats['completed_projects'] ?? 0) ?>">0</p>
            </div>
            <div class="stat-trend positive">
                <i class="fas fa-arrow-up"></i>
                <span class="trend-value">8%</span>
            </div>
        </div>

        <!-- Devis en Attente -->
        <div class="stat-card">
            <div class="stat-icon bg-warning-light">
                <i class="fas fa-file-invoice text-warning"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-label">Devis en Attente</h3>
                <!-- Utilisation de ?? pour valeur par défaut -->
                <p class="stat-value" data-count="<?= htmlspecialchars($stats['pending_quotes'] ?? 0) ?>">0</p>
            </div>
            <?php
            // Récupération sécurisée de la valeur
            $pendingCount = $stats['pending_quotes'] ?? 0;
            $trendClass = $pendingCount > 5 ? 'negative' : 'positive';
            $arrowDirection = $pendingCount > 5 ? 'up' : 'down';
            $trendValue = $pendingCount > 5 ? '+3%' : '-3%';
            ?>
            <div class="stat-trend <?= $trendClass ?>">
                <i class="fas fa-arrow-<?= $arrowDirection ?>"></i>
                <span class="trend-value"><?= $trendValue ?></span>
            </div>
        </div>

        <!-- Chiffre d'Affaires -->
        <div class="stat-card">
            <div class="stat-icon bg-info-light">
                <i class="fas fa-money-bill-wave text-info"></i>
            </div>
            <div class="stat-content">
                <h3 class="stat-label">Chiffre d'Affaires</h3>
                <p class="stat-value" data-count="<?= htmlspecialchars($stats['revenue'] ?? 0) ?>">0</p>

            </div>
            <div class="stat-trend positive">
                <i class="fas fa-arrow-up"></i>
                <span class="trend-value">15%</span>
            </div>
        </div>
    </div>

    <!-- Section principale -->
    <div class="dashboard-grid">
        <!-- Projets en Cours -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-tasks"></i> Projets en Cours</h3>
                <a href="projets.php" class="btn btn-sm btn-outline">Voir Tout</a>
            </div>
            <div class="card-body">
                <?php if (empty($active_projects)): ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <p>Aucun projet en cours</p>
                    </div>
                <?php else: ?>
                    <div class="project-list">
                        <?php foreach ($active_projects as $project): ?>
                            <?php
                            $progress = calculateProgress($project['date_debut'], $project['date_fin_prevue']);
                            $days_remaining = (new DateTime($project['date_fin_prevue']))->diff(new DateTime())->days;
                            ?>
                            <div class="project-item">
                                <div class="project-icon">
                                    <i class="<?= htmlspecialchars($project['icone'] ?? 'fas fa-project-diagram') ?>"></i>
                                </div>
                                <div class="project-details">
                                    <h4><?= htmlspecialchars($project['nom']) ?></h4>
                                    <p class="project-client">
                                        <?= htmlspecialchars($project['entreprise'] ?: $project['client_name']) ?>
                                    </p>
                                    <div class="project-progress">
                                        <div class="progress">
                                            <div class="progress-bar" style="width: <?= $progress ?>%"></div>
                                        </div>
                                        <span class="progress-text">
                                            <?= $progress ?>% - <?= $days_remaining ?> jours restants
                                        </span>
                                    </div>
                                </div>
                                <div class="project-actions">
                                    <a href="projets.php?action=view&id=<?= $project['id'] ?>" class="btn btn-sm btn-outline">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Activité Récente -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-history"></i> Activité Récente</h3>
                <div class="tabs">
                    <button class="tab-btn active" data-tab="all">Tout</button>
                    <button class="tab-btn" data-tab="devis">Devis</button>
                    <button class="tab-btn" data-tab="contact">Contacts</button>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($recent_activity)): ?>
                    <div class="empty-state">
                        <i class="fas fa-clock"></i>
                        <p>Aucune activité récente</p>
                    </div>
                <?php else: ?>
                    <div class="activity-list">
                        <?php foreach ($recent_activity as $activity): ?>
                            <div class="activity-item" data-type="<?= htmlspecialchars($activity['type']) ?>">
                                <div class="activity-icon <?= htmlspecialchars($activity['type']) ?>">
                                    <?php if ($activity['type'] === 'devis'): ?>
                                        <i class="fas fa-file-invoice"></i>
                                    <?php elseif ($activity['type'] === 'contact'): ?>
                                        <i class="fas fa-envelope"></i>
                                    <?php else: ?>
                                        <i class="fas fa-user-cog"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="activity-content">
                                    <h4><?= htmlspecialchars($activity['title']) ?></h4>
                                    <p><?= htmlspecialchars($activity['description']) ?></p>
                                    <small class="text-muted">
                                        <?= (new DateTime($activity['created_at']))->format('d/m/Y H:i') ?>
                                    </small>
                                </div>
                                <div class="activity-actions">
                                    <a href="<?= htmlspecialchars($activity['url_path']) . $activity['id'] ?>"
                                        class="btn btn-sm btn-outline">
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Section inférieure -->
    <div class="dashboard-grid">
        <!-- Clients Récents -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-users"></i> Clients Récents</h3>
                <a href="clients.php" class="btn btn-sm btn-outline">Voir Tout</a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_clients)): ?>
                    <div class="empty-state">
                        <i class="fas fa-user-friends"></i>
                        <p>Aucun client récent</p>
                    </div>
                <?php else: ?>
                    <div class="client-list">
                        <?php foreach ($recent_clients as $client): ?>
                            <div class="client-item">
                                <div class="client-avatar">
                                    <?= strtoupper(substr($client['nom'], 0, 1)) ?>
                                </div>
                                <div class="client-details">
                                    <h4><?= htmlspecialchars($client['entreprise'] ?: $client['nom']) ?></h4>
                                    <p>
                                        <?= $client['project_count'] ?> projet(s) |
                                        <?= number_format($client['total_spent'], 0, ',', ' ') ?> FCFA
                                    </p>
                                    <div class="client-status">
                                        <span class="badge <?= $client['statut'] === 'client_vip' ? 'bg-gold' : 'bg-primary' ?>">
                                            <?= ucfirst(str_replace('_', ' ', $client['statut'])) ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="client-actions">
                                    <a href="clients.php?action=view&id=<?= $client['id'] ?>" class="btn btn-sm btn-outline">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Échéances Proches -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><i class="fas fa-calendar-alt"></i> Échéances Proches</h3>
                <button class="btn btn-sm btn-outline" onclick="openCalendar()">
                    <i class="fas fa-calendar-plus"></i>
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($upcoming_deadlines)): ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-check"></i>
                        <p>Aucune échéance proche</p>
                    </div>
                <?php else: ?>
                    <div class="deadline-list">
                        <?php foreach ($upcoming_deadlines as $deadline): ?>
                            <?php
                            $deadline_date = new DateTime($deadline['date_fin_prevue']);
                            $days_remaining = $deadline['days_remaining'];

                            if ($days_remaining <= 3) {
                                $priority = 'urgent';
                                $priority_text = 'Urgent';
                            } elseif ($days_remaining <= 7) {
                                $priority = 'warning';
                                $priority_text = 'Bientôt';
                            } else {
                                $priority = 'normal';
                                $priority_text = 'Planifié';
                            }
                            ?>
                            <div class="deadline-item <?= $priority ?>">
                                <div class="deadline-date">
                                    <span class="day"><?= $deadline_date->format('d') ?></span>
                                    <span class="month"><?= strtoupper($deadline_date->format('M')) ?></span>
                                </div>
                                <div class="deadline-details">
                                    <h4><?= htmlspecialchars($deadline['project_name']) ?></h4>
                                    <p><?= htmlspecialchars($deadline['client_name']) ?></p>
                                    <span class="badge bg-<?= $priority ?>">
                                        <?= $days_remaining ?> jour(s) - <?= $priority_text ?>
                                    </span>
                                </div>
                                <div class="deadline-actions">
                                    <a href="projets.php?action=view&id=<?= $deadline['id'] ?>" class="btn btn-sm btn-outline">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Graphique de revenus -->
    <div class="dashboard-card full-width">
        <div class="card-header">
            <h3><i class="fas fa-chart-line"></i> Évolution du Chiffre d'Affaires</h3>
            <div class="tabs">
                <button class="tab-btn active" data-period="month">Mensuel</button>
                <button class="tab-btn" data-period="quarter">Trimestriel</button>
                <button class="tab-btn" data-period="year">Annuel</button>
            </div>
        </div>
        <div class="card-body">
            <canvas id="revenueChart" height="300"></canvas>
        </div>
    </div>
</main>

<!-- Modale Nouveau Projet -->
<div id="new-project-modal" class="modal">
    <div class="modal-content large">
        <div class="modal-header">
            <h3>Nouveau Projet</h3>
            <button class="modal-close" onclick="closeModal('new-project-modal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="new-project-form" action="projets.php" method="POST">
                <div class="form-group">
                    <label for="project-name">Nom du Projet</label>
                    <input type="text" id="project-name" name="name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="project-client">Client</label>
                    <select id="project-client" name="client_id" class="form-control" required>
                        <option value="">Sélectionner un client</option>
                        <?php
                        $clients = $conn->query("SELECT id, nom, entreprise FROM clients ORDER BY nom");
                        if ($clients) {
                            foreach ($clients as $client) {
                                echo '<option value="' . $client['id'] . '">' 
                                    . htmlspecialchars($client['entreprise'] ?: $client['nom']) 
                                    . '</option>';
                            }
                        }
                        ?>
                        <option value="new">+ Nouveau Client</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="project-devis">Devis associé</label>
                    <select id="project-devis" name="devis_id" class="form-control" required>
                        <option value="">Sélectionner un devis</option>
                        <?php
                        $devis_list = $conn->query("SELECT id, numero_devis, description FROM devis WHERE statut IN ('nouveau', 'en_cours') ORDER BY date_creation DESC");
                        if ($devis_list) {
                            foreach ($devis_list as $devis) {
                                echo '<option value="' . $devis['id'] . '">' 
                                    . htmlspecialchars($devis['numero_devis'] . ' - ' . $devis['description']) 
                                    . '</option>';
                            }
                        }
                        ?>
                        <option value="new">+ Nouveau Devis</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="project-service">Service</label>
                    <select id="project-service" name="service" class="form-control" required>
                        <option value="">Sélectionner un service</option>
                        <?php
                        $services = $conn->query("SELECT slug, nom FROM services WHERE actif = 1 ORDER BY ordre");
                        if ($services) {
                            foreach ($services as $service) {
                                echo '<option value="' . $service['slug'] . '">' 
                                    . htmlspecialchars($service['nom']) 
                                    . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                
                <div class="row" >
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="project-start">Date de Début</label>
                            <input type="date" id="project-start" name="start_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="project-end">Date de Fin</label>
                            <input type="date" id="project-end" name="end_date" class="form-control" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="project-budget">Budget (FCFA)</label>
                    <input type="number" id="project-budget" name="budget" class="form-control" min="0" step="1">
                </div>
                
                <div class="form-group">
                    <label for="project-description">Description</label>
                    <textarea id="project-description" name="description" class="form-control" rows="3"></textarea>
                </div>
                
                <input type="hidden" name="action" value="create_project">
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('new-project-modal')">Annuler</button>
            <button type="submit" form="new-project-form" class="btn btn-primary">Créer</button>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Animation des compteurs
    document.addEventListener('DOMContentLoaded', function() {
        // Animer les valeurs des statistiques
        const counters = document.querySelectorAll('.stat-value[data-count]');
        const animationDuration = 2000;
        const frameDuration = 1000 / 60;

        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-count'));
            const start = 0;
            const frames = Math.floor(animationDuration / frameDuration);
            const increment = (target - start) / frames;
            let current = start;

            const animate = () => {
                current += increment;
                if (current >= target) {
                    counter.textContent = target.toLocaleString('fr-FR');
                } else {
                    counter.textContent = Math.floor(current).toLocaleString('fr-FR');
                    requestAnimationFrame(animate);
                }
            };

            animate();
        });

        // Initialiser le graphique de revenus
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_map(function ($item) {
                            $date = DateTime::createFromFormat('Y-m', $item['month']);
                            return $date ? $date->format('M Y') : $item['month'];
                        }, $revenue_data)) ?>,
                datasets: [{
                    label: 'Chiffre d\'Affaires (FCFA)',
                    data: <?= json_encode(array_column($revenue_data, 'amount')) ?>,
                    backgroundColor: 'rgba(231, 76, 60, 0.2)',
                    borderColor: 'rgba(231, 76, 60, 1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.raw.toLocaleString('fr-FR') + ' FCFA';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('fr-FR') + ' FCFA';
                            }
                        }
                    }
                }
            }
        });

        // Gestion des onglets
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const tabType = this.getAttribute('data-tab') || this.getAttribute('data-period');
                const parent = this.closest('.tabs');

                // Mettre à jour l'état actif des onglets
                parent.querySelectorAll('.tab-btn').forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                // Filtrer les éléments si nécessaire
                if (this.hasAttribute('data-tab')) {
                    const container = this.closest('.dashboard-card').querySelector('.activity-list');
                    if (container) {
                        container.querySelectorAll('.activity-item').forEach(item => {
                            const itemType = item.getAttribute('data-type');
                            if (tabType === 'all' || itemType === tabType) {
                                item.style.display = 'flex';
                            } else {
                                item.style.display = 'none';
                            }
                        });
                    }
                }

                // Mettre à jour le graphique si on change la période
                if (this.hasAttribute('data-period')) {
                    // Ici vous pourriez faire un appel AJAX pour charger les données de la période sélectionnée
                    console.log('Chargement des données pour la période:', tabType);
                }
            });
        });
    });

    // Gestion des modales
    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'block';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    function openCalendar() {
        // Implémenter l'ouverture du calendrier
        alert('Fonctionnalité de calendrier en développement');
    }

    // Gestion du formulaire de nouveau projet
    document.getElementById('project-client').addEventListener('change', function() {
        if (this.value === 'new') {
            // Rediriger vers la page de création de client
            window.location.href = 'clients.php?action=new&redirect=new_project';
        }
    });

    document.getElementById('project-devis').addEventListener('change', function() {
        if (this.value === 'new') {
            // Rediriger vers la page de création de devis
            window.location.href = 'devis.php?action=new&redirect=new_project';
        }
    });
</script>
<script>
    // Gestion de la création de projet
    document.getElementById('new-project-form').addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        try {
            const response = await fetch('projets.php?action=create_project', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                // Fermer la modale et recharger la page
                closeModal('new-project-modal');
                location.reload();
            } else {
                alert('Erreur: ' + (result.error || 'Échec de la création du projet'));
            }
        } catch (error) {
            console.error('Erreur:', error);
            alert('Une erreur réseau est survenue');
        }
    });

    // Pré-remplir le devis si ID passé dans l'URL
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const devisId = urlParams.get('devis_id');

        if (devisId) {
            document.getElementById('project-devis').value = devisId;
        }
    });
</script>

