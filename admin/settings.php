<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

check_admin_auth();

$db = Database::getInstance();
$conn = $db->getConnection();

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_profile':
            $nom = sanitize_string($_POST['nom']);
            $email = sanitize_email($_POST['email']);
            $telephone = sanitize_string($_POST['telephone']);
            
            if (validate_email($email)) {
                $stmt = $conn->prepare("UPDATE admins SET nom = ?, email = ?, telephone = ? WHERE id = ?");
                $stmt->bind_param("sssi", $nom, $email, $telephone, $_SESSION['admin_id']);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Profil mis à jour avec succès.";
                } else {
                    $_SESSION['error_message'] = "Erreur lors de la mise à jour.";
                }
            } else {
                $_SESSION['error_message'] = "Email invalide.";
            }
            break;
            
        case 'change_password':
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            if ($new_password !== $confirm_password) {
                $_SESSION['error_message'] = "Les mots de passe ne correspondent pas.";
                break;
            }
            
            // Vérifier le mot de passe actuel
            $stmt = $conn->prepare("SELECT mot_de_passe FROM admins WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['admin_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $admin = $result->fetch_assoc();
            
            if (verify_password($current_password, $admin['mot_de_passe'])) {
                $new_hash = hash_password($new_password);
                $stmt = $conn->prepare("UPDATE admins SET mot_de_passe = ? WHERE id = ?");
                $stmt->bind_param("si", $new_hash, $_SESSION['admin_id']);
                
                if ($stmt->execute()) {
                    log_activity($_SESSION['admin_id'], 'password_changed');
                    $_SESSION['success_message'] = "Mot de passe modifié avec succès.";
                } else {
                    $_SESSION['error_message'] = "Erreur lors du changement de mot de passe.";
                }
            } else {
                $_SESSION['error_message'] = "Mot de passe actuel incorrect.";
            }
            break;
            
        case 'update_settings':
            $settings = [
                'site_name' => sanitize_string($_POST['site_name']),
                'site_email' => sanitize_email($_POST['site_email']),
                'site_phone' => sanitize_string($_POST['site_phone']),
                'site_address' => sanitize_string($_POST['site_address']),
                'notifications_email' => isset($_POST['notifications_email']) ? 1 : 0,
                'notifications_sms' => isset($_POST['notifications_sms']) ? 1 : 0
            ];
            
            foreach ($settings as $key => $value) {
                set_config($key, $value);
            }
            
            $_SESSION['success_message'] = "Paramètres sauvegardés avec succès.";
            break;
    }
    
    redirect($_SERVER['PHP_SELF']);
}

// Récupération des données admin
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->bind_param("i", $_SESSION['admin_id']);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

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
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <div class="section-header">
        <div class="section-title">
            <h2>Paramètres</h2>
            <p>Configuration de l'administration</p>
        </div>
    </div>

    <div class="settings-tabs">
        <button class="tab-btn active" data-tab="profile">
            <i class="fas fa-user"></i> Profil
        </button>
        <button class="tab-btn" data-tab="security">
            <i class="fas fa-shield-alt"></i> Sécurité
        </button>
        <button class="tab-btn" data-tab="general">
            <i class="fas fa-cogs"></i> Général
        </button>
        <button class="tab-btn" data-tab="notifications">
            <i class="fas fa-bell"></i> Notifications
        </button>
    </div>

    <!-- Onglet Profil -->
    <div class="tab-content active" data-tab="profile">
        <div class="settings-card">
            <div class="card-header">
                <h3>Informations du profil</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-group">
                        <label for="nom">Nom complet</label>
                        <input type="text" id="nom" name="nom" class="form-control" 
                               value="<?php echo htmlspecialchars($admin['nom']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="telephone">Téléphone</label>
                        <input type="tel" id="telephone" name="telephone" class="form-control" 
                               value="<?php echo htmlspecialchars($admin['telephone']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Dernière connexion</label>
                        <input type="text" class="form-control" 
                               value="<?php echo format_date($admin['derniere_connexion'], 'd/m/Y H:i'); ?>" readonly>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Sauvegarder
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Onglet Sécurité -->
    <div class="tab-content" data-tab="security">
        <div class="settings-card">
            <div class="card-header">
                <h3>Changer le mot de passe</h3>
            </div>
            <div class="card-body">
                <form method="POST" id="passwordForm">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="form-group">
                        <label for="current_password">Mot de passe actuel</label>
                        <input type="password" id="current_password" name="current_password" 
                               class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">Nouveau mot de passe</label>
                        <input type="password" id="new_password" name="new_password" 
                               class="form-control" required>
                        <div id="password-strength" class="password-strength"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirmer le mot de passe</label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-key"></i> Changer le mot de passe
                    </button>
                </form>
            </div>
        </div>
        
        <div class="settings-card">
            <div class="card-header">
                <h3>Sessions actives</h3>
            </div>
            <div class="card-body">
                <div class="session-item current">
                    <div class="session-info">
                        <i class="fas fa-desktop"></i>
                        <div>
                            <strong>Session actuelle</strong>
                            <small>IP: <?php echo $_SERVER['REMOTE_ADDR']; ?></small>
                        </div>
                    </div>
                    <span class="badge badge-success">Actuelle</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Onglet Général -->
    <div class="tab-content" data-tab="general">
        <div class="settings-card">
            <div class="card-header">
                <h3>Paramètres généraux</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_settings">
                    
                    <div class="form-group">
                        <label for="site_name">Nom du site</label>
                        <input type="text" id="site_name" name="site_name" class="form-control" 
                               value="<?php echo htmlspecialchars(get_config('site_name', 'Divine Art Corporation')); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="site_email">Email du site</label>
                        <input type="email" id="site_email" name="site_email" class="form-control" 
                               value="<?php echo htmlspecialchars(get_config('site_email', 'contact@divineart.fr')); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="site_phone">Téléphone du site</label>
                        <input type="tel" id="site_phone" name="site_phone" class="form-control" 
                               value="<?php echo htmlspecialchars(get_config('site_phone', '01 23 45 67 89')); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="site_address">Adresse</label>
                        <textarea id="site_address" name="site_address" class="form-control" rows="3"><?php echo htmlspecialchars(get_config('site_address', 'Paris, France')); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Sauvegarder
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Onglet Notifications -->
    <div class="tab-content" data-tab="notifications">
        <div class="settings-card">
            <div class="card-header">
                <h3>Préférences de notifications</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_settings">
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" id="notifications_email" name="notifications_email" 
                                   class="form-check-input" <?php echo get_config('notifications_email', 1) ? 'checked' : ''; ?>>
                            <label for="notifications_email" class="form-check-label">
                                Notifications par email
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" id="notifications_sms" name="notifications_sms" 
                                   class="form-check-input" <?php echo get_config('notifications_sms', 0) ? 'checked' : ''; ?>>
                            <label for="notifications_sms" class="form-check-label">
                                Notifications par SMS
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Sauvegarder
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
// Gestion des onglets
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const tab = this.getAttribute('data-tab');
        
        // Désactiver tous les onglets
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        
        // Activer l'onglet sélectionné
        this.classList.add('active');
        document.querySelector(`[data-tab="${tab}"]`).classList.add('active');
    });
});

// Validation du mot de passe
document.getElementById('new_password').addEventListener('input', function() {
    const password = this.value;
    const strengthDiv = document.getElementById('password-strength');
    
    // Calculer la force du mot de passe
    let score = 0;
    let feedback = [];
    
    if (password.length >= 8) score++;
    else feedback.push('Au moins 8 caractères');
    
    if (/[a-z]/.test(password)) score++;
    else feedback.push('Une minuscule');
    
    if (/[A-Z]/.test(password)) score++;
    else feedback.push('Une majuscule');
    
    if (/[0-9]/.test(password)) score++;
    else feedback.push('Un chiffre');
    
    if (/[^a-zA-Z0-9]/.test(password)) score++;
    else feedback.push('Un caractère spécial');
    
    const levels = ['Très faible', 'Faible', 'Moyen', 'Fort', 'Très fort'];
    const colors = ['#e74c3c', '#f39c12', '#f1c40f', '#27ae60', '#2ecc71'];
    
    strengthDiv.innerHTML = `
        <div class="strength-bar">
            <div class="strength-fill" style="width: ${(score/5)*100}%; background: ${colors[score]}"></div>
        </div>
        <div class="strength-text" style="color: ${colors[score]}">
            ${levels[score]} ${feedback.length > 0 ? '- ' + feedback.join(', ') : ''}
        </div>
    `;
});

// Validation du formulaire de mot de passe
document.getElementById('passwordForm').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        alert('Les mots de passe ne correspondent pas.');
    }
});
</script>

<?php include '../includes/footer.php'; ?>
