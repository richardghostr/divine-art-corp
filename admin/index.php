<?php 
require_once 'header.php';
require_once 'sidebar.php';

// Récupération des statistiques du dashboard
try {
    // Statistiques principales
    $stats = [
        'projets_actifs' => $db->selectOne("SELECT COUNT(*) as count FROM devis WHERE statut = 'en_cours'")['count'],
        'projets_termines' => $db->selectOne("SELECT COUNT(*) as count FROM devis WHERE statut = 'termine'")['count'],
        'devis_attente' => $db->selectOne("SELECT COUNT(*) as count FROM devis WHERE statut = 'nouveau'")['count'],
        'chiffre_affaires' => $db->selectOne("SELECT COALESCE(SUM(montant_final), 0) as total FROM devis WHERE statut = 'termine' AND YEAR(date_creation) = YEAR(CURDATE())")['total']
    ];
    
    // Projets en cours
    $projets_en_cours = $db->selectAll("
        SELECT d.*, s.nom as service_nom 
        FROM devis d 
        LEFT JOIN services s ON s.slug = d.service 
        WHERE d.statut = 'en_cours' 
        ORDER BY d.date_creation DESC 
        LIMIT 3
    ");
    
    // Activité récente
    $activite_recente = $db->selectAll("
        SELECT 
            'devis' as type,
            CONCAT('Nouveau devis de ', nom) as titre,
            CONCAT('Devis ', service) as description,
            date_creation,
            id
        FROM devis 
        WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        
        UNION ALL
        
        SELECT 
            'contact' as type,
            CONCAT('Nouveau contact de ', nom) as titre,
            CONCAT('Sujet: ', COALESCE(sujet, 'Non spécifié')) as description,
            date_creation,
            id
        FROM contacts 
        WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        
        ORDER BY date_creation DESC 
        LIMIT 5
    ");
    
    // Clients récents
    $clients_recents = $db->selectAll("
        SELECT DISTINCT 
            nom,
            email,
            entreprise,
            MAX(date_creation) as derniere_activite,
            COUNT(*) as nb_projets
        FROM devis 
        GROUP BY email 
        ORDER BY derniere_activite DESC 
        LIMIT 3
    ");
    
    // Prochaines échéances
    $echeances = $db->selectAll("
        SELECT 
            numero_devis,
            nom,
            entreprise,
            service,
            date_fin_prevue,
            DATEDIFF(date_fin_prevue, CURDATE()) as jours_restants
        FROM devis 
        WHERE statut = 'en_cours' 
        AND date_fin_prevue IS NOT NULL 
        AND date_fin_prevue >= CURDATE()
        ORDER BY date_fin_prevue ASC 
        LIMIT 3
    ");
    
} catch (Exception $e) {
    error_log("Erreur dashboard: " . $e->getMessage());
    $stats = ['projets_actifs' => 0, 'projets_termines' => 0, 'devis_attente' => 0, 'chiffre_affaires' => 0];
    $projets_en_cours = [];
    $activite_recente = [];
    $clients_recents = [];
    $echeances = [];
}

// Fonction pour formater les montants
function formatMontant($montant) {
    return number_format($montant, 0, ',', ' ') . ' FCFA';
}

// Fonction pour calculer le pourcentage de progression
function calculerProgression($date_debut, $date_fin_prevue) {
    if (!$date_debut || !$date_fin_prevue) return 0;
    
    $debut = new DateTime($date_debut);
    $fin = new DateTime($date_fin_prevue);
    $maintenant = new DateTime();
    
    $duree_totale = $fin->diff($debut)->days;
    $duree_ecoulee = $maintenant->diff($debut)->days;
    
    if ($duree_totale <= 0) return 100;
    
    $progression = ($duree_ecoulee / $duree_totale) * 100;
    return min(100, max(0, $progression));
}
?>

<!-- Contenu Principal -->
<main class="admin-main">
    <!-- Welcome Header -->
    <div class="welcome-header">
        <div class="welcome-content">
            <div class="welcome-avatar">
                <img src="/placeholder.svg?height=60&width=60" alt="Admin">
            </div>
            <div class="welcome-text">
                <h1>Bienvenue, <?php echo htmlspecialchars($user_name); ?></h1>
                <p>Voici un aperçu de votre plateforme</p>
            </div>
        </div>
        <div class="welcome-actions">
            <button class="btn btn-primary" onclick="openNewProjectModal()">
                <i class="fas fa-plus"></i>
                Nouveau Projet
            </button>
        </div>
    </div>

    <!-- Statistiques Principales -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Projets Actifs</div>
                <div class="stat-value" data-count="<?php echo $stats['projets_actifs']; ?>">0</div>
            </div>
            <div class="stat-trend positive">
                <i class="fas fa-arrow-up"></i>
                +12%
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Projets Terminés</div>
                <div class="stat-value" data-count="<?php echo $stats['projets_termines']; ?>">0</div>
            </div>
            <div class="stat-trend positive">
                <i class="fas fa-arrow-up"></i>
                +8%
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-question-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Devis en Attente</div>
                <div class="stat-value" data-count="<?php echo $stats['devis_attente']; ?>">0</div>
            </div>
            <div class="stat-trend <?php echo $stats['devis_attente'] > 5 ? 'negative' : 'positive'; ?>">
                <i class="fas fa-arrow-<?php echo $stats['devis_attente'] > 5 ? 'up' : 'down'; ?>"></i>
                <?php echo $stats['devis_attente'] > 5 ? '+' : '-'; ?>3%
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-euro-sign"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Chiffre d'Affaires</div>
                <div class="stat-value" data-count="<?php echo $stats['chiffre_affaires']; ?>">0</div>
            </div>
            <div class="stat-trend positive">
                <i class="fas fa-arrow-up"></i>
                +15%
            </div>
        </div>
    </div>

    <!-- Projets et Activité -->
    <div class="dashboard-grid">
        <div class="dashboard-card">
            <div class="card-header">
                <h3>Projets en Cours</h3>
                <button class="card-action">
                    <i class="fas fa-ellipsis-h"></i>
                </button>
            </div>
            <div class="card-content">
                <?php if (empty($projets_en_cours)): ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <p>Aucun projet en cours</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($projets_en_cours as $projet): ?>
                        <div class="project-item">
                            <div class="project-icon">
                                <i class="fas fa-<?php echo $projet['service'] === 'marketing' ? 'bullhorn' : ($projet['service'] === 'graphique' ? 'paint-brush' : ($projet['service'] === 'multimedia' ? 'video' : 'print')); ?>"></i>
                            </div>
                            <div class="project-info">
                                <h4><?php echo htmlspecialchars($projet['description']); ?></h4>
                                <div class="project-meta">
                                    <span class="project-client"><?php echo htmlspecialchars($projet['entreprise'] ?: $projet['nom']); ?></span>
                                    <span class="project-service"><?php echo htmlspecialchars($projet['service_nom'] ?: ucfirst($projet['service'])); ?></span>
                                </div>
                                <div class="project-progress">
                                    <?php $progression = calculerProgression($projet['date_debut'], $projet['date_fin_prevue']); ?>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $progression; ?>%"></div>
                                    </div>
                                    <span class="progress-text"><?php echo round($progression); ?>% Terminé</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="card-header">
                <h3>Activité Récente</h3>
                <div class="card-tabs">
                    <button class="tab-btn active" data-tab="all">Tout</button>
                    <button class="tab-btn" data-tab="new">Nouveau</button>
                    <button class="tab-btn" data-tab="urgent">Urgent</button>
                </div>
            </div>
            <div class="card-content">
                <div class="activity-list">
                    <?php if (empty($activite_recente)): ?>
                        <div class="empty-state">
                            <i class="fas fa-clock"></i>
                            <p>Aucune activité récente</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($activite_recente as $activite): ?>
                            <div class="activity-item">
                                <div class="activity-icon <?php echo $activite['type']; ?>">
                                    <i class="fas fa-<?php echo $activite['type'] === 'devis' ? 'file-invoice' : 'envelope'; ?>"></i>
                                </div>
                                <div class="activity-content">
                                    <h4><?php echo htmlspecialchars($activite['titre']); ?></h4>
                                    <p><?php echo htmlspecialchars($activite['description']); ?></p>
                                </div>
                                <div class="activity-time">
                                    <?php 
                                    $date = new DateTime($activite['date_creation']);
                                    $maintenant = new DateTime();
                                    $diff = $maintenant->diff($date);
                                    
                                    if ($diff->days > 0) {
                                        echo $diff->days . 'j';
                                    } elseif ($diff->h > 0) {
                                        echo $diff->h . 'h';
                                    } else {
                                        echo $diff->i . 'min';
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Clients Récents et Échéances -->
    <div class="dashboard-bottom">
        <div class="dashboard-card">
            <div class="card-header">
                <h3>Clients Récents</h3>
                <div class="card-actions">
                    <a href="contacts.php" class="btn btn-outline btn-sm">Voir Tout</a>
                </div>
            </div>
            <div class="card-content">
                <div class="client-list">
                    <?php if (empty($clients_recents)): ?>
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <p>Aucun client récent</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($clients_recents as $client): ?>
                            <div class="client-item">
                                <div class="client-avatar">
                                    <img src="/placeholder.svg?height=40&width=40" alt="<?php echo htmlspecialchars($client['nom']); ?>">
                                </div>
                                <div class="client-info">
                                    <h4><?php echo htmlspecialchars($client['entreprise'] ?: $client['nom']); ?></h4>
                                    <p><?php echo $client['nb_projets']; ?> projet(s)</p>
                                </div>
                                <div class="client-status">
                                    <span class="status-badge active">Actif</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="card-header">
                <h3>Prochaines Échéances</h3>
                <button class="card-action" onclick="openCalendarModal()">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
            <div class="card-content">
                <div class="deadline-list">
                    <?php if (empty($echeances)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar"></i>
                            <p>Aucune échéance prochaine</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($echeances as $echeance): ?>
                            <?php 
                            $urgence = 'normal';
                            if ($echeance['jours_restants'] <= 3) $urgence = 'urgent';
                            elseif ($echeance['jours_restants'] <= 7) $urgence = 'warning';
                            
                            $date_echeance = new DateTime($echeance['date_fin_prevue']);
                            ?>
                            <div class="deadline-item <?php echo $urgence; ?>">
                                <div class="deadline-date">
                                    <span class="date-day"><?php echo $date_echeance->format('d'); ?></span>
                                    <span class="date-month"><?php echo $date_echeance->format('M'); ?></span>
                                </div>
                                <div class="deadline-info">
                                    <h4><?php echo htmlspecialchars($echeance['numero_devis']); ?></h4>
                                    <p><?php echo htmlspecialchars($echeance['entreprise'] ?: $echeance['nom']); ?></p>
                                </div>
                                <div class="deadline-priority">
                                    <span class="priority-badge <?php echo $urgence; ?>">
                                        <?php echo $echeance['jours_restants']; ?> jour(s)
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphiques et Analytics -->
    <div class="analytics-section">
        <div class="dashboard-card">
            <div class="card-header">
                <h3>Évolution du Chiffre d'Affaires</h3>
                <div class="card-tabs">
                    <button class="tab-btn active" data-period="month">Mois</button>
                    <button class="tab-btn" data-period="quarter">Trimestre</button>
                    <button class="tab-btn" data-period="year">Année</button>
                </div>
            </div>
            <div class="card-content">
                <div class="chart-container">
                    <canvas id="revenueChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
</main>

</div> <!-- Fin admin-layout -->

<!-- Modales -->
<div id="newProjectModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Nouveau Projet</h3>
            <button class="modal-close" onclick="closeModal('newProjectModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="newProjectForm" action="devis.php" method="GET">
                <div class="form-group">
                    <label for="projectName">Nom du projet</label>
                    <input type="text" id="projectName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="projectClient">Client</label>
                    <select id="projectClient" class="form-control" required>
                        <option value="">Sélectionner un client</option>
                        <?php
                        // Récupération des clients existants
                        try {
                            $clients = $db->selectAll("SELECT DISTINCT nom, email, entreprise FROM devis ORDER BY nom");
                            foreach ($clients as $client) {
                                $display_name = $client['entreprise'] ?: $client['nom'];
                                echo '<option value="' . htmlspecialchars($client['email']) . '">' . htmlspecialchars($display_name) . '</option>';
                            }
                        } catch (Exception $e) {
                            echo '<option value="">Erreur de chargement</option>';
                        }
                        ?>
                        <option value="new">+ Nouveau client</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="projectService">Service</label>
                    <select id="projectService" class="form-control" required>
                        <option value="">Sélectionner un service</option>
                        <option value="marketing">Marketing Digital</option>
                        <option value="graphique">Conception Graphique</option>
                        <option value="multimedia">Conception Multimédia</option>
                        <option value="imprimerie">Imprimerie</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="projectDeadline">Date limite</label>
                    <input type="date" id="projectDeadline" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="projectBudget">Budget estimé</label>
                    <input type="number" id="projectBudget" class="form-control" placeholder="0" min="0">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('newProjectModal')">Annuler</button>
            <button type="submit" form="newProjectForm" class="btn btn-primary">Créer le Projet</button>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="../assets/js/admin.js"></script>
<script>
// Animation des compteurs
document.addEventListener('DOMContentLoaded', function() {
    const counters = document.querySelectorAll('.stat-value[data-count]');
    
    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-count'));
        const duration = 2000;
        const step = target / (duration / 16);
        let current = 0;
        
        const timer = setInterval(() => {
            current += step;
            if (current >= target) {
                counter.textContent = target.toLocaleString();
                clearInterval(timer);
            } else {
                counter.textContent = Math.floor(current).toLocaleString();
            }
        }, 16);
    });
});

// Gestion des modales
function openNewProjectModal() {
    document.getElementById('newProjectModal').style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function openCalendarModal() {
    // Implémentation du calendrier
    console.log('Ouverture du calendrier');
}

// Gestion des onglets
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const parent = this.closest('.card-header');
        parent.querySelectorAll('.tab-btn').forEach(tab => tab.classList.remove('active'));
        this.classList.add('active');
    });
});

// Recherche globale
document.getElementById('globalSearch').addEventListener('input', function() {
    const query = this.value.toLowerCase();
    // Implémentation de la recherche en temps réel
    console.log('Recherche:', query);
});

// Actualisation automatique des notifications
setInterval(function() {
    // Ici vous pourriez faire un appel AJAX pour actualiser les notifications
    console.log('Vérification des nouvelles notifications...');
}, 30000); // Toutes les 30 secondes
</script>

<?php include '../includes/footer.php'; ?>
