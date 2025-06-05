<?php
// Activer l'affichage des erreurs pour le débogage (à désactiver en production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérification de la connexion à la base de données
if (!isset($conn)) {
    require_once 'config/database.php';
}

// Test de la connexion
if (!$conn) {
    die("Erreur de connexion à la base de données: " . mysqli_connect_error());
}

// Initialisation des variables
$success_message = '';
$error_message = '';
$debug_message = ''; // Pour le débogage
$form_data = [
    'nom' => '',
    'email' => '',
    'telephone' => '',
    'entreprise' => '',
    'poste' => '',
    'service' => '',
    'sous_service' => '',
    'description' => '',
    'budget' => '',
    'delai' => ''
];

// Fonction pour vérifier si une table existe
function table_exists($conn, $table_name) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table_name'");
    return mysqli_num_rows($result) > 0;
}

// Services par défaut (toujours disponibles)
$services = [
    ['id' => 'marketing', 'nom' => 'Marketing Digital', 'slug' => 'marketing', 'prix_min' => 75000],
    ['id' => 'graphique', 'nom' => 'Conception Graphique', 'slug' => 'graphique', 'prix_min' => 25000],
    ['id' => 'multimedia', 'nom' => 'Conception Multimédia', 'slug' => 'multimedia', 'prix_min' => 100000],
    ['id' => 'imprimerie', 'nom' => 'Imprimerie', 'slug' => 'imprimerie', 'prix_min' => 5000]
];

$sous_services_by_service = [
    'marketing' => [
        ['id' => 'seo-sem', 'nom' => 'SEO & SEM', 'prix' => 150000],
        ['id' => 'social-media', 'nom' => 'Réseaux Sociaux', 'prix' => 100000],
        ['id' => 'email-marketing', 'nom' => 'Email Marketing', 'prix' => 75000]
    ],
    'graphique' => [
        ['id' => 'identite-visuelle', 'nom' => 'Identité Visuelle', 'prix' => 200000],
        ['id' => 'supports-communication', 'nom' => 'Supports de Communication', 'prix' => 25000],
        ['id' => 'packaging', 'nom' => 'Packaging & Étiquetage', 'prix' => 150000]
    ],
    'multimedia' => [
        ['id' => 'production-video', 'nom' => 'Production Vidéo', 'prix' => 200000],
        ['id' => 'photographie', 'nom' => 'Photographie Professionnelle', 'prix' => 100000],
        ['id' => 'motion-design', 'nom' => 'Motion Design', 'prix' => 150000]
    ],
    'imprimerie' => [
        ['id' => 'impression-numerique', 'nom' => 'Impression Numérique', 'prix' => 5000],
        ['id' => 'impression-offset', 'nom' => 'Impression Offset', 'prix' => 10000],
        ['id' => 'grands-formats', 'nom' => 'Grands Formats', 'prix' => 25000]
    ]
];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $debug_message .= "Début du traitement du formulaire...<br>";
    
    // Récupération et nettoyage des données
    $form_data = [
        'nom' => trim($_POST['nom'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'telephone' => trim($_POST['telephone'] ?? ''),
        'entreprise' => trim($_POST['entreprise'] ?? ''),
        'poste' => trim($_POST['poste'] ?? ''),
        'service' => trim($_POST['service'] ?? ''),
        'sous_service' => trim($_POST['sous_service'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'budget' => trim($_POST['budget'] ?? ''),
        'delai' => trim($_POST['delai'] ?? ''),
        'newsletter' => isset($_POST['newsletter']) ? 1 : 0,
        'rgpd' => isset($_POST['rgpd']) ? 1 : 0
    ];
    
    $debug_message .= "Données récupérées...<br>";
    
    // Validation des données
    $errors = [];
    
    if (empty($form_data['nom'])) {
        $errors[] = 'Le nom est requis';
    }
    
    if (empty($form_data['email'])) {
        $errors[] = 'L\'email est requis';
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'L\'email n\'est pas valide';
    }
    
    if (empty($form_data['telephone'])) {
        $errors[] = 'Le téléphone est requis';
    }
    
    if (empty($form_data['service'])) {
        $errors[] = 'Le service est requis';
    }
    
    if (empty($form_data['description'])) {
        $errors[] = 'La description est requise';
    }
    
    if (!$form_data['rgpd']) {
        $errors[] = 'Vous devez accepter la politique de confidentialité';
    }
    
    $debug_message .= "Validation terminée. Erreurs: " . count($errors) . "<br>";
    
    // Si pas d'erreurs, enregistrer le devis
    if (empty($errors)) {
        $debug_message .= "Début de l'enregistrement...<br>";
        
        try {
            // Vérifier si la table devis existe, sinon la créer
            if (!table_exists($conn, 'devis')) {
                $debug_message .= "Création de la table devis...<br>";
                
                $create_table_query = "
                CREATE TABLE devis (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    numero_devis VARCHAR(50) UNIQUE NOT NULL,
                    nom VARCHAR(100) NOT NULL,
                    email VARCHAR(100) NOT NULL,
                    telephone VARCHAR(20) NOT NULL,
                    entreprise VARCHAR(100),
                    poste VARCHAR(100),
                    service VARCHAR(100) NOT NULL,
                    sous_service VARCHAR(100),
                    description TEXT NOT NULL,
                    budget VARCHAR(50),
                    delai VARCHAR(50),
                    newsletter TINYINT(1) DEFAULT 0,
                    statut ENUM('nouveau', 'en_cours', 'termine', 'annule') DEFAULT 'nouveau',
                    priorite ENUM('basse', 'normale', 'haute', 'urgente') DEFAULT 'normale',
                    montant_estime DECIMAL(10,2) DEFAULT NULL,
                    notes_admin TEXT,
                    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    ip_address VARCHAR(45),
                    user_agent TEXT
                )";
                
                if (!mysqli_query($conn, $create_table_query)) {
                    throw new Exception("Erreur lors de la création de la table devis: " . mysqli_error($conn));
                }
                
                $debug_message .= "Table devis créée avec succès...<br>";
            } else {
                $debug_message .= "Table devis existe déjà...<br>";
            }
            
            // Générer un numéro de devis unique
            $numero_devis = 'DEV' . date('Ymd') . sprintf('%04d', rand(1, 9999));
            $debug_message .= "Numéro de devis généré: $numero_devis<br>";
            
            // Échapper les données pour mysqli
            $nom_escaped = mysqli_real_escape_string($conn, $form_data['nom']);
            $email_escaped = mysqli_real_escape_string($conn, $form_data['email']);
            $telephone_escaped = mysqli_real_escape_string($conn, $form_data['telephone']);
            $entreprise_escaped = mysqli_real_escape_string($conn, $form_data['entreprise']);
            $poste_escaped = mysqli_real_escape_string($conn, $form_data['poste']);
            $service_escaped = mysqli_real_escape_string($conn, $form_data['service']);
            $sous_service_escaped = mysqli_real_escape_string($conn, $form_data['sous_service']);
            $description_escaped = mysqli_real_escape_string($conn, $form_data['description']);
            $budget_escaped = mysqli_real_escape_string($conn, $form_data['budget']);
            $delai_escaped = mysqli_real_escape_string($conn, $form_data['delai']);
            
            // Récupérer les informations IP et User Agent
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $ip_escaped = mysqli_real_escape_string($conn, $ip_address);
            $user_agent_escaped = mysqli_real_escape_string($conn, $user_agent);
            
            $debug_message .= "Données échappées...<br>";
            
            // Insertion dans la base de données avec requête simple
            $insert_query = "INSERT INTO devis (
                numero_devis, nom, email, telephone, entreprise, poste, 
                service, sous_service, description, budget, delai, 
                newsletter, ip_address, user_agent
            ) VALUES (
                '$numero_devis', '$nom_escaped', '$email_escaped', '$telephone_escaped', 
                '$entreprise_escaped', '$poste_escaped', '$service_escaped', '$sous_service_escaped', 
                '$description_escaped', '$budget_escaped', '$delai_escaped', 
                {$form_data['newsletter']}, '$ip_escaped', '$user_agent_escaped'
            )";
            
            $debug_message .= "Requête préparée...<br>";
            
            if (mysqli_query($conn, $insert_query)) {
                $devis_id = mysqli_insert_id($conn);
                $debug_message .= "Devis inséré avec ID: $devis_id<br>";
                
                $success_message = "Votre demande de devis a été envoyée avec succès ! Nous vous contacterons rapidement pour discuter de votre projet. Votre numéro de référence est : <strong>$numero_devis</strong>";
                
                // Réinitialiser le formulaire
                $form_data = [
                    'nom' => '',
                    'email' => '',
                    'telephone' => '',
                    'entreprise' => '',
                    'poste' => '',
                    'service' => '',
                    'sous_service' => '',
                    'description' => '',
                    'budget' => '',
                    'delai' => ''
                ];
            } else {
                throw new Exception("Erreur lors de l'insertion: " . mysqli_error($conn));
            }
            
        } catch (Exception $e) {
            $error_message = "Erreur détaillée: " . $e->getMessage();
            $debug_message .= "Exception capturée: " . $e->getMessage() . "<br>";
            error_log("Erreur devis: " . $e->getMessage());
        }
    } else {
        $error_message = implode(', ', $errors);
    }
}

// Récupération des statistiques
$stats = [
    'total_devis' => 0,
    'devis_mois' => 0,
    'temps_reponse' => '24h'
];

try {
    if (table_exists($conn, 'devis')) {
        // Total des devis
        $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM devis");
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $stats['total_devis'] = $row['total'];
            mysqli_free_result($result);
        }
        
        // Devis ce mois
        $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM devis WHERE MONTH(date_creation) = MONTH(CURRENT_DATE()) AND YEAR(date_creation) = YEAR(CURRENT_DATE())");
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $stats['devis_mois'] = $row['total'];
            mysqli_free_result($result);
        }
    }
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des statistiques: " . $e->getMessage());
}
?>

<section class="devis-hero">
    <div class="container">
        <div class="devis-hero-content">
            <h1>Demander un Devis</h1>
            <p>Obtenez une estimation personnalisée pour votre projet en quelques minutes</p>
        </div>
    </div>
</section>

<!-- Devis Section -->
<section class="devis-section">
    <div class="container">
        <div class="devis-intro">
            <h2>Comment pouvons-nous vous aider ?</h2>
            <p>Remplissez le formulaire ci-dessous pour nous faire part de votre projet. Notre équipe vous contactera dans les plus brefs délais avec une proposition adaptée à vos besoins.</p>
            
            <!-- Statistiques -->
    
        </div>
        
        <?php if ($debug_message && isset($_GET['debug'])): ?>
            <div class="alert alert-info">
                <strong>Debug:</strong><br>
                <?php echo $debug_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="devis-form-container">
            <form id="devisForm" method="POST" action="?page=devis" enctype="multipart/form-data">
                <div class="form-section">
                    <h3><i class="fas fa-user"></i> Informations personnelles</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nom">Nom complet <span class="required">*</span></label>
                            <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($form_data['nom']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email <span class="required">*</span></label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($form_data['email']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="telephone">Téléphone <span class="required">*</span></label>
                            <input type="tel" id="telephone" name="telephone" value="<?php echo htmlspecialchars($form_data['telephone']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="entreprise">Entreprise</label>
                            <input type="text" id="entreprise" name="entreprise" value="<?php echo htmlspecialchars($form_data['entreprise']); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="poste">Poste/Fonction</label>
                        <input type="text" id="poste" name="poste" value="<?php echo htmlspecialchars($form_data['poste']); ?>" placeholder="Ex: Directeur Marketing, CEO, Responsable Communication...">
                    </div>
                </div>
                
                <div class="form-section">
                    <h3><i class="fas fa-project-diagram"></i> Détails du projet</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="service">Service <span class="required">*</span></label>
                            <select id="service" name="service" required>
                                <option value="">Sélectionnez un service</option>
                                <?php foreach ($services as $service): ?>
                                    <option value="<?php echo htmlspecialchars($service['slug'] ?? $service['id']); ?>" 
                                            <?php echo $form_data['service'] == ($service['slug'] ?? $service['id']) ? 'selected' : ''; ?>
                                            data-service-id="<?php echo htmlspecialchars($service['id']); ?>">
                                        <?php echo htmlspecialchars($service['nom']); ?>
                                        <?php if (isset($service['prix_min'])): ?>
                                            - À partir de <?php echo number_format($service['prix_min'], 0, ',', ' '); ?> FCFA
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="sous_service">Sous-service</label>
                            <select id="sous_service" name="sous_service" disabled>
                                <option value="">Sélectionnez d'abord un service</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="budget">Budget estimé</label>
                            <select id="budget" name="budget">
                                <option value="">Sélectionnez votre budget</option>
                                <option value="moins-100k" <?php echo $form_data['budget'] == 'moins-100k' ? 'selected' : ''; ?>>Moins de 100 000 FCFA</option>
                                <option value="100k-300k" <?php echo $form_data['budget'] == '100k-300k' ? 'selected' : ''; ?>>100 000 - 300 000 FCFA</option>
                                <option value="300k-500k" <?php echo $form_data['budget'] == '300k-500k' ? 'selected' : ''; ?>>300 000 - 500 000 FCFA</option>
                                <option value="500k-1m" <?php echo $form_data['budget'] == '500k-1m' ? 'selected' : ''; ?>>500 000 - 1 000 000 FCFA</option>
                                <option value="plus-1m" <?php echo $form_data['budget'] == 'plus-1m' ? 'selected' : ''; ?>>Plus de 1 000 000 FCFA</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="delai">Délai souhaité</label>
                            <select id="delai" name="delai">
                                <option value="">Sélectionnez un délai</option>
                                <option value="urgent" <?php echo $form_data['delai'] == 'urgent' ? 'selected' : ''; ?>>Urgent (moins d'une semaine)</option>
                                <option value="1-2-semaines" <?php echo $form_data['delai'] == '1-2-semaines' ? 'selected' : ''; ?>>1-2 semaines</option>
                                <option value="3-4-semaines" <?php echo $form_data['delai'] == '3-4-semaines' ? 'selected' : ''; ?>>3-4 semaines</option>
                                <option value="1-2-mois" <?php echo $form_data['delai'] == '1-2-mois' ? 'selected' : ''; ?>>1-2 mois</option>
                                <option value="flexible" <?php echo $form_data['delai'] == 'flexible' ? 'selected' : ''; ?>>Flexible</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description détaillée du projet <span class="required">*</span></label>
                        <textarea id="description" name="description" rows="6" required placeholder="Décrivez votre projet, vos objectifs, vos contraintes, votre public cible..."><?php echo htmlspecialchars($form_data['description']); ?></textarea>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3><i class="fas fa-shield-alt"></i> Consentements</h3>
                    
                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="newsletter" <?php echo isset($form_data['newsletter']) && $form_data['newsletter'] ? 'checked' : ''; ?>>
                            <span class="checkbox-custom"></span>
                            Je souhaite recevoir la newsletter et les conseils de Divine Art Corporation
                        </label>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="rgpd" required <?php echo isset($form_data['rgpd']) && $form_data['rgpd'] ? 'checked' : ''; ?>>
                            <span class="checkbox-custom"></span>
                            J'accepte que mes données soient utilisées pour traiter ma demande de devis et que Divine Art Corporation me contacte à ce sujet <span class="required">*</span>
                        </label>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="submit-btn">
                        <i class="fas fa-paper-plane"></i> Envoyer ma demande
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- Process Section -->
<section class="process-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Notre processus</h2>
            <p class="section-subtitle">Comment nous traitons votre demande de devis</p>
        </div>
        
        <div class="process-steps">
            <div class="process-step">
                <div class="step-number">1</div>
                <div class="step-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h3>Demande</h3>
                <p>Vous remplissez le formulaire de demande de devis avec les détails de votre projet.</p>
            </div>
            
            <div class="process-step">
                <div class="step-number">2</div>
                <div class="step-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h3>Analyse</h3>
                <p>Notre équipe analyse votre demande et évalue les besoins spécifiques de votre projet.</p>
            </div>
            
            <div class="process-step">
                <div class="step-number">3</div>
                <div class="step-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <h3>Discussion</h3>
                <p>Nous vous contactons pour discuter des détails et affiner les spécifications du projet.</p>
            </div>
            
            <div class="process-step">
                <div class="step-number">4</div>
                <div class="step-icon">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <h3>Proposition</h3>
                <p>Nous vous envoyons un devis détaillé avec les coûts, délais et spécifications.</p>
            </div>
            
            <div class="process-step">
                <div class="step-number">5</div>
                <div class="step-icon">
                    <i class="fas fa-rocket"></i>
                </div>
                <h3>Démarrage</h3>
                <p>Après validation du devis, nous commençons immédiatement à travailler sur votre projet.</p>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Données des sous-services par service (depuis PHP)
    const sousServicesData = <?php echo json_encode($sous_services_by_service); ?>;
    
    // Éléments du formulaire
    const serviceSelect = document.getElementById('service');
    const sousServiceSelect = document.getElementById('sous_service');
    const devisForm = document.getElementById('devisForm');
    const submitBtn = document.getElementById('submit-btn');
    
    // Gestion des sous-services
    function updateSousServices() {
        const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
        const serviceId = selectedOption.getAttribute('data-service-id') || serviceSelect.value;
        
        // Réinitialiser le select des sous-services
        sousServiceSelect.innerHTML = '<option value="">Sélectionnez un sous-service</option>';
        
        if (serviceId && sousServicesData[serviceId]) {
            const sousServices = sousServicesData[serviceId];
            
            if (sousServices.length > 0) {
                sousServices.forEach(function(sousService) {
                    const option = document.createElement('option');
                    option.value = sousService.id;
                    option.textContent = sousService.nom;
                    if (sousService.prix) {
                        option.textContent += ' - ' + new Intl.NumberFormat('fr-FR').format(sousService.prix) + ' FCFA';
                    }
                    sousServiceSelect.appendChild(option);
                });
                
                sousServiceSelect.disabled = false;
                
                // Restaurer la valeur précédente si disponible
                const previousValue = '<?php echo htmlspecialchars($form_data['sous_service']); ?>';
                if (previousValue) {
                    sousServiceSelect.value = previousValue;
                }
            } else {
                sousServiceSelect.disabled = true;
            }
        } else {
            sousServiceSelect.disabled = true;
        }
    }
    
    // Écouter les changements sur le select de service
    serviceSelect.addEventListener('change', updateSousServices);
    
    // Initialiser les sous-services au chargement
    updateSousServices();
    
    // Validation du formulaire
    devisForm.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Réinitialiser les erreurs
        document.querySelectorAll('.error-message').forEach(el => el.remove());
        document.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
        
        // Validation des champs requis
        const requiredFields = ['nom', 'email', 'telephone', 'service', 'description'];
        
        requiredFields.forEach(function(fieldName) {
            const field = document.getElementById(fieldName);
            if (!field.value.trim()) {
                showError(field, 'Ce champ est requis');
                isValid = false;
            }
        });
        
        // Validation email
        const email = document.getElementById('email');
        if (email.value.trim() && !isValidEmail(email.value)) {
            showError(email, 'Email invalide');
            isValid = false;
        }
        
        // Validation RGPD
        const rgpd = document.querySelector('input[name="rgpd"]');
        if (!rgpd.checked) {
            showError(rgpd.closest('.checkbox-group'), 'Vous devez accepter la politique de confidentialité');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            
            // Scroll vers la première erreur
            const firstError = document.querySelector('.error-message');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        } else {
            // Désactiver le bouton et afficher le loader
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi en cours...';
        }
    });
    
    // Fonctions utilitaires
    function showError(element, message) {
        const errorElement = document.createElement('div');
        errorElement.className = 'error-message';
        errorElement.textContent = message;
        
        element.parentElement.appendChild(errorElement);
        element.classList.add('error');
    }
    
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    // Auto-fermeture des alertes
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 300);
        }, 5000);
    });
});
</script>


<style>
/* Styles spécifiques pour la page devis */
.devis-section {
    padding: 80px 0;
}

.devis-intro {
    text-align: center;
    max-width: 800px;
    margin: 0 auto 50px;
}

.devis-intro h2 {
    margin-bottom: 20px;
    font-size: 32px;
    color: var(--text-color);
}

.devis-intro p {
    font-size: 18px;
    color: var(--text-secondary);
}

.devis-form-container {
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 5px 30px rgba(0, 0, 0, 0.1);
    padding: 40px;
}

.form-section {
    margin-bottom: 40px;
    padding-bottom: 30px;
    border-bottom: 1px solid #eee;
}

.form-section:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.form-section h3 {
    margin-bottom: 25px;
    font-size: 22px;
    color: var(--primary-color);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--text-color);
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 16px;
    transition: all 0.3s ease;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
    outline: none;
}

.checkbox-group {
    display: flex;
    align-items: center;
}

.checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    user-select: none;
}

.checkbox-custom {
    width: 20px;
    height: 20px;
    border: 2px solid #ddd;
    border-radius: 4px;
    margin-right: 10px;
    position: relative;
}

.checkbox-label input {
    position: absolute;
    opacity: 0;
    cursor: pointer;
}

.checkbox-label input:checked + .checkbox-custom::after {
    content: '\f00c';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: var(--primary-color);
    font-size: 12px;
}

.required {
    color: var(--primary-color);
}

.file-upload {
    margin-top: 10px;
}

.file-upload input[type="file"] {
    width: 0.1px;
    height: 0.1px;
    opacity: 0;
    overflow: hidden;
    position: absolute;
    z-index: -1;
}

.file-upload-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 30px;
    border: 2px dashed #ddd;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
}

.file-upload-label:hover {
    border-color: var(--primary-color);
    background-color: rgba(231, 76, 60, 0.05);
}

.file-upload-label i {
    font-size: 48px;
    color: var(--primary-color);
    margin-bottom: 15px;
}

.file-upload-label span {
    font-size: 16px;
    color: var(--text-color);
    margin-bottom: 10px;
}

.file-upload-label small {
    font-size: 14px;
    color: var(--text-secondary);
}

.file-list {
    margin-top: 15px;
}

.file-list ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.file-list li {
    display: flex;
    align-items: center;
    padding: 10px;
    background-color: #f8f9fa;
    border-radius: 5px;
    margin-bottom: 5px;
}

.file-list li i {
    margin-right: 10px;
    color: var(--primary-color);
}

.file-size {
    margin-left: auto;
    font-size: 14px;
    color: var(--text-secondary);
}

.form-actions {
    margin-top: 30px;
    text-align: center;
}

.alert {
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 30px;
    display: flex;
    align-items: center;
}

.alert i {
    margin-right: 10px;
    font-size: 20px;
}

.alert-success {
    background-color: rgba(46, 204, 113, 0.1);
    color: #27ae60;
    border-left: 4px solid #27ae60;
}

.alert-error {
    background-color: rgba(231, 76, 60, 0.1);
    color: #e74c3c;
    border-left: 4px solid #e74c3c;
}

.error-message {
    color: #e74c3c;
    font-size: 14px;
    margin-top: 5px;
}

.form-group input.error,
.form-group select.error,
.form-group textarea.error {
    border-color: #e74c3c;
}

.process-section {
    padding: 80px 0;
    background-color: #f8f9fa;
}

.process-steps {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 30px;
    margin-top: 50px;
}

.process-step {
    text-align: center;
    position: relative;
}

.process-step::after {
    content: '';
    position: absolute;
    top: 50px;
    right: -15px;
    width: 30px;
    height: 2px;
    background-color: var(--primary-color);
    z-index: 1;
}

.process-step:last-child::after {
    display: none;
}

.step-number {
    position: absolute;
    top: -10px;
    right: -10px;
    width: 30px;
    height: 30px;
    background-color: var(--primary-color);
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 14px;
    z-index: 2;
}

.step-icon {
    width: 100px;
    height: 100px;
    background-color: #fff;
    border: 3px solid var(--primary-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    position: relative;
}

.step-icon i {
    font-size: 36px;
    color: var(--primary-color);
}

.process-step h3 {
    margin-bottom: 15px;
    font-size: 20px;
    color: var(--text-color);
}

.process-step p {
    color: var(--text-secondary);
    font-size: 14px;
    line-height: 1.6;
}

@media (max-width: 1200px) {
    .process-steps {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .process-step:nth-child(3)::after,
    .process-step:nth-child(5)::after {
        display: none;
    }
}

@media (max-width: 992px) {
    .devis-form-container {
        padding: 30px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .process-steps {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .process-step:nth-child(2)::after,
    .process-step:nth-child(4)::after {
        display: none;
    }
}

@media (max-width: 768px) {
    .devis-form-container {
        padding: 20px;
    }
    
    .process-steps {
        grid-template-columns: 1fr;
    }
    
    .process-step::after {
        display: none;
    }
    
    .step-icon {
        width: 80px;
        height: 80px;
    }
    
    .step-icon i {
        font-size: 28px;
    }
}
</style>
