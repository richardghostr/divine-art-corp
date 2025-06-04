<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$auth = new Auth();
$error = '';
$success = '';

// Redirection si déjà connecté
if ($auth->isLoggedIn()) {
    header("Location: index.php");
    exit;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs';
    } else {
        $result = $auth->login($email, $password);
        
        if ($result['success']) {
            header("Location: index.php");
            exit;
        } else {
            $error = $result['error'];
            
            if (isset($result['remaining_attempts'])) {
                $error .= " (Il vous reste {$result['remaining_attempts']} tentatives)";
            }
        }
    }
}

// Traitement de la réinitialisation du mot de passe
if (isset($_POST['reset_password'])) {
    $email = filter_input(INPUT_POST, 'reset_email', FILTER_SANITIZE_EMAIL);
    
    if (empty($email)) {
        $error = 'Veuillez entrer votre adresse email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide';
    } else {
        $result = $auth->generateResetToken($email);
        
        if ($result['success']) {
            $success = 'Un email de réinitialisation a été envoyé si l\'adresse existe dans notre système';
        } else {
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Admin - Divine Art Corporation</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #e74c3c;
            --primary-dark: #c0392b;
            --secondary-color: #3498db;
            --dark-color: #2c3e50;
            --light-color: #f8f9fa;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .login-card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            max-width: 450px;
            width: 100%;
            margin: 0 auto;
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .login-logo {
            width: 90px;
            height: 90px;
            background: rgba(255, 255, 255, 0.07);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
            padding: 10px;
        }
        
        .login-body {
            background: white;
            padding: 2rem;
        }
        
        .form-control {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(231, 76, 60, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            border: none;
            padding: 0.75rem;
            border-radius: 8px;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%);
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }
        
        .login-footer {
            background: #f8f9fa;
            padding: 1.5rem;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        
        .login-link {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .login-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        
        /* Animation pour les messages d'alerte */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert {
            animation: fadeIn 0.3s ease-out;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-card">
            <!-- En-tête -->
            <div class="login-header">
                <div class="login-logo">
                    <img src="../assets/images/Logo.svg" alt="">
                </div>
                <h2>Divine Art Corporation</h2>
                <p class="mb-0">Interface d'administration</p>
            </div>
            
            <!-- Corps -->
            <div class="login-body">
                <!-- Messages d'erreur/succès -->
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Formulaire de connexion -->
                <form method="POST" id="loginForm" class="<?php echo isset($_POST['reset_password']) ? 'd-none' : ''; ?>">
                    <div class="mb-3">
                        <label for="email" class="form-label">Adresse email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                   required autofocus>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <span class="password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Se souvenir de moi</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <i class="fas fa-sign-in-alt me-2"></i> Se connecter
                    </button>
                    
                    <div class="text-center">
                        <a href="#" class="login-link" onclick="showResetForm()">Mot de passe oublié ?</a>
                    </div>
                </form>
                
                <!-- Formulaire de réinitialisation -->
                <form method="POST" id="resetForm" class="<?php echo !isset($_POST['reset_password']) ? 'd-none' : ''; ?>">
                    <div class="mb-3">
                        <label for="reset_email" class="form-label">Adresse email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="reset_email" name="reset_email" 
                                   value="<?php echo htmlspecialchars($_POST['reset_email'] ?? ''); ?>" required>
                        </div>
                        <div class="form-text">Nous vous enverrons un lien pour réinitialiser votre mot de passe</div>
                    </div>
                    
                    <button type="submit" name="reset_password" class="btn btn-primary w-100 mb-3">
                        <i class="fas fa-paper-plane me-2"></i> Envoyer le lien
                    </button>
                    
                    <button type="button" class="btn btn-outline-secondary w-100" onclick="showLoginForm()">
                        <i class="fas fa-arrow-left me-2"></i> Retour à la connexion
                    </button>
                </form>
            </div>
            
            <!-- Pied de page -->
            <div class="login-footer">
                <a href="../index.php" class="login-link">
                    <i class="fas fa-arrow-left me-1"></i> Retour au site public
                </a>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Basculer la visibilité du mot de passe
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const icon = document.querySelector('#password + .password-toggle i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
        
        // Afficher le formulaire de réinitialisation
        function showResetForm() {
            document.getElementById('loginForm').classList.add('d-none');
            document.getElementById('resetForm').classList.remove('d-none');
            document.getElementById('reset_email').focus();
        }
        
        // Afficher le formulaire de connexion
        function showLoginForm() {
            document.getElementById('resetForm').classList.add('d-none');
            document.getElementById('loginForm').classList.remove('d-none');
            document.getElementById('email').focus();
        }
        
        // Fermer automatiquement les alertes après 5 secondes
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>