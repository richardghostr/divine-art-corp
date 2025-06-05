<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once 'header.php';
require_once 'sidebar.php';

// Vérification de l'authentification
$auth = new Auth();
$auth->requireAuth();

$db = Database::getInstance();
$conn = $db->getConnection();
$databaseHelper = new DatabaseHelper();
$success_message = '';
$error_message = '';

// Récupération des services et sous-services
$services = $databaseHelper->select("SELECT * FROM services WHERE actif = 1 ORDER BY ordre");

$sous_services = [];
foreach ($services as $service) {
$serviceID=$service['id'];
    $sub_services = $conn->query(
        "SELECT * FROM sous_services WHERE service_id = $serviceID AND actif = 1 ORDER BY ordre",
    );
    $sous_services[$service['id']] = $sub_services;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validation des données
        $required_fields = ['nom', 'email', 'telephone', 'service_id', 'description'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Le champ " . ucfirst($field) . " est obligatoire.");
            }
        }

        // Préparation des données
        $data = [
            'nom' => sanitizeInput($_POST['nom']),
            'email' => sanitizeInput($_POST['email']),
            'telephone' => sanitizeInput($_POST['telephone']),
            'entreprise' => sanitizeInput($_POST['entreprise'] ?? null),
            'poste' => sanitizeInput($_POST['poste'] ?? null),
            'service_id' => (int)$_POST['service_id'],
            'sous_service_id' => !empty($_POST['sous_service_id']) ? (int)$_POST['sous_service_id'] : null,
            'description' => sanitizeInput($_POST['description']),
            'budget' => sanitizeInput($_POST['budget'] ?? null),
            'delai' => sanitizeInput($_POST['delai'] ?? null),
            'priorite' => sanitizeInput($_POST['priorite'] ?? 'normale'),
            'admin_assigne' => $_SESSION['admin']['id']
        ];

        // Récupération des noms de service et sous-service
        $service_name = $databaseHelper->selectOne(
            "SELECT nom FROM services WHERE id = ?",
            [$data['service_id']]
        )['nom'];

        $sous_service_name = null;
        if ($data['sous_service_id']) {
            $sous_service_name = $databaseHelper->selectOne(
                "SELECT nom FROM sous_services WHERE id = ?",
                [$data['sous_service_id']]
            )['nom'];
        }

        // Insertion dans la base de données
        $result = $databaseHelper->execute(
            "INSERT INTO devis (
                nom, email, telephone, entreprise, poste, 
                service, sous_service, description, budget, delai, 
                priorite, admin_assigne, date_creation
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                $data['nom'], $data['email'], $data['telephone'], $data['entreprise'], $data['poste'],
                $service_name, $sous_service_name, $data['description'], $data['budget'], $data['delai'],
                $data['priorite'], $data['admin_assigne']
            ]
        );

        if ($result) {
            $devis_id = $conn->insert_id;
            
            // Gestion des fichiers joints
            if (!empty($_FILES['fichiers']['name'][0])) {
                $uploaded_files = [];
                
                foreach ($_FILES['fichiers']['tmp_name'] as $key => $tmp_name) {
                    $file_name = $_FILES['fichiers']['name'][$key];
                    $file_size = $_FILES['fichiers']['size'][$key];
                    $file_type = $_FILES['fichiers']['type'][$key];
                    $file_error = $_FILES['fichiers']['error'][$key];
                    
                    // Validation du fichier
                    if ($file_error !== UPLOAD_ERR_OK) {
                        throw new Exception("Erreur lors du téléchargement du fichier: " . $file_name);
                    }
                    
                    // Vérification de la taille
                    $max_size = 5 * 1024 * 1024; // 5MB
                    if ($file_size > $max_size) {
                        throw new Exception("Le fichier " . $file_name . " dépasse la taille maximale autorisée (5MB)");
                    }
                    
                    // Vérification du type
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'zip'];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    if (!in_array($file_ext, $allowed_types)) {
                        throw new Exception("Type de fichier non autorisé: " . $file_name);
                    }
                    
                    // Déplacement du fichier
                    $upload_dir = '../uploads/devis/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $new_file_name = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\.\-_]/', '', $file_name);
                    $destination = $upload_dir . $new_file_name;
                    
                    if (!move_uploaded_file($tmp_name, $destination)) {
                        throw new Exception("Impossible de sauvegarder le fichier: " . $file_name);
                    }
                    
                    $uploaded_files[] = [
                        'nom' => $file_name,
                        'chemin' => $destination,
                        'type' => $file_type,
                        'taille' => $file_size
                    ];
                }
                
                // Mise à jour du devis avec les fichiers joints
                if (!empty($uploaded_files)) {
                    $databaseHelper->execute(
                        "UPDATE devis SET fichiers_joints = ? WHERE id = ?",
                        [json_encode($uploaded_files), $devis_id]
                    );
                }
            }
            
            // Journalisation
            $auth->logActivity($_SESSION['admin']['id'], 'devis_create', 'devis', $devis_id);
            
            // Redirection avec message de succès
            $_SESSION['success_message'] = "Devis créé avec succès!";
            // header("Location: devis.php");
            exit();
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        error_log("Erreur création devis: " . $e->getMessage());
    }
}
?>

<!-- Contenu Principal -->
<main class="admin-main">
    <div class="section-header">
        <div class="section-title">
            <h2>Créer un nouveau devis</h2>
            <p>Remplissez le formulaire pour créer un nouveau devis</p>
        </div>
        <div class="section-actions">
            <a href="devis.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i>
                Retour
            </a>
        </div>
    </div>

    <?php if ($error_message): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="form-container">
        <div class="form-section">
            <h3>Informations client</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="nom">Nom complet <span class="required">*</span></label>
                    <!-- <input type="text" name="nom" id="nom" class="form-control" required> -->
                      <select id="nom" name="nom" class="form-control" required>
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
                    <label for="email">Email <span class="required">*</span></label>
                    <input type="email" name="email" id="email" class="form-control" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="telephone">Téléphone <span class="required">*</span></label>
                    <input type="tel" name="telephone" id="telephone" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="entreprise">Entreprise</label>
                    <input type="text" name="entreprise" id="entreprise" class="form-control">
                </div>
            </div>
            
            <div class="form-group">
                <label for="poste">Poste/Fonction</label>
                <input type="text" name="poste" id="poste" class="form-control">
            </div>
        </div>
        
        <div class="form-section">
            <h3>Détails de la demande</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="service_id">Service <span class="required">*</span></label>
                    <select name="service_id" id="service_id" class="form-control" required onchange="updateSousServices()">
                        <option value="">Sélectionnez un service</option>
                        <?php foreach ($services as $service): ?>
                            <option value="<?php echo $service['id']; ?>">
                                <?php echo htmlspecialchars($service['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="sous_service_id">Sous-service</label>
                    <select name="sous_service_id" id="sous_service_id" class="form-control" disabled>
                        <option value="">Sélectionnez d'abord un service</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="budget">Budget estimé</label>
                    <input type="text" name="budget" id="budget" class="form-control" placeholder="Ex: 500 000 FCFA">
                </div>
                
                <div class="form-group">
                    <label for="delai">Délai souhaité</label>
                    <input type="text" name="delai" id="delai" class="form-control" placeholder="Ex: 2 semaines">
                </div>
                
                <div class="form-group">
                    <label for="priorite">Priorité</label>
                    <select name="priorite" id="priorite" class="form-control">
                        <option value="normale">Normale</option>
                        <option value="basse">Basse</option>
                        <option value="haute">Haute</option>
                        <option value="urgente">Urgente</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="description">Description détaillée <span class="required">*</span></label>
                <textarea name="description" id="description" class="form-control" rows="6" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="fichiers">Fichiers joints</label>
                <div class="file-upload">
                    <input type="file" name="fichiers[]" id="fichiers" class="file-input" multiple>
                    <label for="fichiers" class="file-label">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span>Glissez-déposez vos fichiers ou cliquez pour sélectionner</span>
                        <small>Formats acceptés: JPG, PNG, PDF, DOC, DOCX, ZIP (max 5MB par fichier)</small>
                    </label>
                    <div id="file-list" class="file-list"></div>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="reset" class="btn btn-outline">
                <i class="fas fa-undo"></i>
                Réinitialiser
            </button>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i>
                Enregistrer le devis
            </button>
        </div>
    </form>
</main>

<script>
// Mise à jour dynamique des sous-services
function updateSousServices() {
    const serviceId = document.getElementById('service_id').value;
    const sousServiceSelect = document.getElementById('sous_service_id');
    
    if (!serviceId) {
        sousServiceSelect.innerHTML = '<option value="">Sélectionnez d\'abord un service</option>';
        sousServiceSelect.disabled = true;
        return;
    }
    
    // Récupération des sous-services via AJAX
    fetch(`get_sous_services.php?service_id=${serviceId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.sous_services.length > 0) {
                let options = '<option value="">Sélectionnez un sous-service</option>';
                data.sous_services.forEach(ss => {
                    options += `<option value="${ss.id}">${ss.nom}</option>`;
                });
                sousServiceSelect.innerHTML = options;
                sousServiceSelect.disabled = false;
            } else {
                sousServiceSelect.innerHTML = '<option value="">Aucun sous-service disponible</option>';
                sousServiceSelect.disabled = false;
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            sousServiceSelect.innerHTML = '<option value="">Erreur de chargement</option>';
        });
}

// Affichage des fichiers sélectionnés
document.getElementById('fichiers').addEventListener('change', function(e) {
    const fileList = document.getElementById('file-list');
    fileList.innerHTML = '';
    
    if (this.files.length > 0) {
        const list = document.createElement('ul');
        
        for (let i = 0; i < this.files.length; i++) {
            const file = this.files[i];
            const item = document.createElement('li');
            item.innerHTML = `
                <i class="fas fa-file"></i>
                <span>${file.name} (${formatFileSize(file.size)})</span>
            `;
            list.appendChild(item);
        }
        
        fileList.appendChild(list);
    }
});

// Formatage de la taille des fichiers
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2) + ' ' + sizes[i]);
}
</script>


