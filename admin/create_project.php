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
$page_title = "Créer un Nouveau Projet";

// Traitement du formulaire de création
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et validation des données
    $nom = mysqli_real_escape_string($conn, $_POST['project_name']);
    $description = mysqli_real_escape_string($conn, $_POST['project_description']);
    $date_debut = $_POST['start_date'];
    $date_fin_prevue = $_POST['end_date'] ?: null;
    $budget_alloue = $_POST['budget'] ? (float)$_POST['budget'] : null;
    $admin_responsable = (int)$_POST['project_manager'];
    $client_id = $_POST['client'] ? (int)$_POST['client'] : null;
    $devis_id = $_POST['quote'] ? (int)$_POST['quote'] : null;

    // Validation des champs obligatoires
    if (empty($nom)) {
        $error_message = "Le nom du projet est obligatoire.";
    } elseif (empty($date_debut)) {
        $error_message = "La date de début est obligatoire.";
    } elseif (empty($admin_responsable)) {
        $error_message = "Le responsable du projet est obligatoire.";
    } else {
        // Insertion dans la base de données
        $query = "INSERT INTO projets (devis_id, nom, description, statut, date_debut, date_fin_prevue, budget_alloue, admin_responsable) 
                  VALUES (?, ?, ?, 'planifie', ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "issssdi", $devis_id, $nom, $description, $date_debut, $date_fin_prevue, $budget_alloue, $admin_responsable);

        if (mysqli_stmt_execute($stmt)) {
            $projet_id = mysqli_insert_id($conn);
            
            // Log de l'activité
            logActivity($currentAdmin['id'], 'create_project', 'projets', $projet_id, "Projet créé: $nom");
            
            // Redirection vers la page du projet ou liste des projets
            $_SESSION['success_message'] = "Projet créé avec succès!";
            header("Location: projets.php");
            exit();
        } else {
            $error_message = "Erreur lors de la création du projet: " . mysqli_error($conn);
        }
    }
}

// Récupération des données pour les listes déroulantes
$admins_query = "SELECT id, nom FROM admins WHERE statut = 'actif' ORDER BY nom";
$admins_result = mysqli_query($conn, $admins_query);
$admins = mysqli_fetch_all($admins_result, MYSQLI_ASSOC);

$clients_query = "SELECT id, nom, entreprise FROM clients ORDER BY nom";
$clients_result = mysqli_query($conn, $clients_query);
$clients = mysqli_fetch_all($clients_result, MYSQLI_ASSOC);

$devis_query = "SELECT id, numero_devis, nom FROM devis ORDER BY date_creation DESC";
$devis_result = mysqli_query($conn, $devis_query);
$devis = mysqli_fetch_all($devis_result, MYSQLI_ASSOC);

include 'header.php';
?>

<div class="admin-main">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="content-header">
            <div class="header-left">
                <h1><i class="fas fa-plus-circle"></i> <?php echo $page_title; ?></h1>
                <p>Créez un nouveau projet pour vos clients</p>
            </div>
            <div class="header-actions">
                <a href="projets.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour à la liste
                </a>
            </div>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" action="create_project.php">
                <div class="form-group">
                    <label for="project_name">Nom du projet *</label>
                    <input type="text" id="project_name" name="project_name" required 
                           value="<?php echo isset($_POST['project_name']) ? htmlspecialchars($_POST['project_name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="project_description">Description</label>
                    <textarea id="project_description" name="project_description" rows="5"><?php 
                        echo isset($_POST['project_description']) ? htmlspecialchars($_POST['project_description']) : ''; 
                    ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="start_date">Date de début *</label>
                        <input type="date" id="start_date" name="start_date" required 
                               value="<?php echo isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="end_date">Date de fin prévue</label>
                        <input type="date" id="end_date" name="end_date" 
                               value="<?php echo isset($_POST['end_date']) ? $_POST['end_date'] : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="budget">Budget alloué (FCFA)</label>
                        <input type="number" id="budget" name="budget" min="0" step="0.01"
                               value="<?php echo isset($_POST['budget']) ? $_POST['budget'] : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="project_manager">Responsable *</label>
                        <select id="project_manager" name="project_manager" required>
                            <option value="">Sélectionnez un responsable</option>
                            <?php foreach ($admins as $admin): ?>
                                <option value="<?php echo $admin['id']; ?>"
                                    <?php echo (isset($_POST['project_manager']) && $_POST['project_manager'] == $admin['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($admin['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="client">Client associé</label>
                    <select id="client" name="client">
                        <option value="">Sélectionnez un client</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client['id']; ?>"
                                <?php echo (isset($_POST['client']) && $_POST['client'] == $client['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($client['nom'] . ' (' . $client['entreprise'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="quote">Devis associé</label>
                    <select id="quote" name="quote">
                        <option value="">Sélectionnez un devis</option>
                        <?php foreach ($devis as $devi): ?>
                            <option value="<?php echo $devi['id']; ?>"
                                <?php echo (isset($_POST['quote']) && $_POST['quote'] == $devi['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($devi['numero_devis'] . ' - ' . $devi['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Créer le Projet
                    </button>
                    <a href="projets.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
// Script pour améliorer l'expérience utilisateur
document.addEventListener('DOMContentLoaded', function() {
    // Définir la date de fin minimum comme la date de début
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    
    startDate.addEventListener('change', function() {
        endDate.min = this.value;
        if (endDate.value && endDate.value < this.value) {
            endDate.value = this.value;
        }
    });
    
    // Si la date de début est vide, définir la date du jour
    if (!startDate.value) {
        const today = new Date().toISOString().split('T')[0];
        startDate.value = today;
    }
    
    // Activer le sélecteur de date avec un meilleur UX
    [startDate, endDate].forEach(input => {
        input.addEventListener('focus', function() {
            this.type = 'date';
        });
    });
});
</script>

