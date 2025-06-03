<?php
session_start();

require_once '../config/database.php';
require_once '../includes/auth.php';

$auth = new Auth();

// Redirection si déjà connecté
if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error_message = '';
$success_message = '';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']);
        
        // Validation basique
        if (empty($email) || empty($password)) {
            $error_message = 'Veuillez remplir tous les champs.';
        } elseif (!validateEmail($email)) {
            $error_message = 'Format d\'email invalide.';
        } else {
            if ($auth->login($email, $password)) {
                // Gestion du "Se souvenir de moi"
                if ($remember) {
                    $token = generateToken();
                    setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
                    // TODO: Sauvegarder le token en base de données
                }
                
                // Redirection vers la page demandée ou dashboard
                $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
                header('Location: ' . $redirect);
                exit;
            } else {
                $error_message = 'Identifiants incorrects.';
                // Log de tentative de connexion échouée
                error_log("Tentative de connexion échouée pour: " . $email . " depuis " . ($_SERVER['REMOTE_ADDR'] ?? 'IP inconnue'));
            }
        }
    }
    
    // Traitement de la récupération de mot de passe
    if (isset($_POST['reset_password'])) {
        $email = sanitizeInput($_POST['reset_email']);
        
        if (empty($email)) {
            $error_message = 'Veuillez saisir votre adresse email.';
        } elseif (!validateEmail($email)) {
            $error_message = 'Adresse email invalide.';
        } else {
            if ($auth->generateResetToken($email)) {
                $success_message = 'Si cette adresse email est associée à un compte, vous recevrez un lien de réinitialisation.';
            } else {
                $success_message = 'Si cette adresse email est associée à un compte, vous recevrez un lien de réinitialisation.';
            }
        }
    }
}

$page_title = 'Connexion - Divine Art Corporation';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <!-- Meta tags -->
    <meta name="description" content="Connexion à l'interface d'administration Divine Art Corporation">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow: hidden;
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="hexagon" width="20" height="20" patternUnits="userSpaceOnUse"><polygon points="10,2 18,7 18,13 10,18 2,13 2,7" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23hexagon)"/></svg>');
            opacity: 0.3;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
            position: relative;
            z-index: 2;
            margin: 2rem;
        }
        
        .login-header {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .login-logo {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }
        
        .login-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .login-subtitle {
            opacity: 0.9;
            font-size: 0.875rem;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .login-form {
            display: block;
        }
        
        .reset-form {
            display: none;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #2c3e50;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
            box-sizing: border-box;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #e74c3c;
            background: white;
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
        }
        
        .input-group {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 2;
        }
        
        .input-group .form-control {
            padding-left: 2.5rem;
        }
        
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            z-index: 2;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #e74c3c;
        }
        
        .checkbox-group label {
            font-size: 0.875rem;
            color: #6c757d;
            cursor: pointer;
        }
        
        .btn-login {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        }
        
        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-secondary {
            width: 100%;
            padding: 0.75rem;
            background: transparent;
            color: #6c757d;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            border-color: #e74c3c;
            color: #e74c3c;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }
        
        .alert-error {
            background: #fee;
            color: #c53030;
            border: 1px solid #fed7d7;
        }
        
        .alert-success {
            background: #f0fff4;
            color: #22543d;
            border: 1px solid #c6f6d5;
        }
        
        .login-footer {
            text-align: center;
            padding: 1rem 2rem 2rem;
            border-top: 1px solid #e9ecef;
        }
        
        .login-links {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.875rem;
        }
        
        .login-link {
            color: #e74c3c;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .login-link:hover {
            color: #c0392b;
            text-decoration: underline;
        }
        
        .back-to-site {
            color: #6c757d;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .back-to-site:hover {
            color: #2c3e50;
        }
        
        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 0.5rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .form-help {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
        
        @media (max-width: 480px) {
            .login-card {
                margin: 1rem;
                border-radius: 15px;
            }
            
            .login-header,
            .login-body {
                padding: 1.5rem;
            }
            
            .login-footer {
                padding: 1rem 1.5rem 1.5rem;
            }
            
            .login-links {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <div class="login-logo">
                    <i class="fas fa-palette"></i>
                </div>
                <h1 class="login-title">Divine Art Corp</h1>
                <p class="login-subtitle">Interface d'Administration</p>
            </div>
            
            <!-- Body -->
            <div class="login-body">
                <!-- Messages d'erreur/succès -->
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Formulaire de connexion -->
                <form method="POST" class="login-form" id="loginForm">
                    <div class="form-group">
                        <label for="email" class="form-label">Adresse email</label>
                        <div class="input-group">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   class="form-control" 
                                   placeholder="admin@divineartcorp.cm"
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                   required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Mot de passe</label>
                        <div class="input-group">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="form-control" 
                                   placeholder="••••••••"
                                   required>
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye" id="passwordIcon"></i>
                            </button>
                        </div>
                        <div class="form-help">
                            Utilisez admin@divineartcorp.cm / password pour la démo
                        </div>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Se souvenir de moi</label>
                    </div>
                    
                    <button type="submit" name="login" class="btn-login" id="loginBtn">
                        <span class="loading-spinner" id="loadingSpinner"></span>
                        <span id="loginText">Se connecter</span>
                    </button>
                </form>
                
                <!-- Formulaire de récupération -->
                <form method="POST" class="reset-form" id="resetForm">
                    <div class="form-group">
                        <label for="reset_email" class="form-label">Adresse email</label>
                        <div class="input-group">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" 
                                   id="reset_email" 
                                   name="reset_email" 
                                   class="form-control" 
                                   placeholder="Votre adresse email"
                                   required>
                        </div>
                        <div class="form-help">
                            Nous vous enverrons un lien pour réinitialiser votre mot de passe
                        </div>
                    </div>
                    
                    <button type="submit" name="reset_password" class="btn-login">
                        <i class="fas fa-paper-plane"></i>
                        Envoyer le lien
                    </button>
                    
                    <button type="button" class="btn-secondary" onclick="showLoginForm()">
                        <i class="fas fa-arrow-left"></i>
                        Retour à la connexion
                    </button>
                </form>
            </div>
            
            <!-- Footer -->
            <div class="login-footer">
                <div class="login-links">
                    <a href="#" class="login-link" onclick="showResetForm()" id="forgotLink">
                        Mot de passe oublié ?
                    </a>
                    <a href="../index.php" class="back-to-site">
                        <i class="fas fa-arrow-left"></i>
                        Retour au site
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('passwordIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                passwordIcon.className = 'fas fa-eye';
            }
        }
        
        // Show reset form
        function showResetForm() {
            document.querySelector('.login-form').style.display = 'none';
            document.querySelector('.reset-form').style.display = 'block';
            document.getElementById('forgotLink').style.display = 'none';
        }
        
        // Show login form
        function showLoginForm() {
            document.querySelector('.login-form').style.display = 'block';
            document.querySelector('.reset-form').style.display = 'none';
            document.getElementById('forgotLink').style.display = 'inline';
        }
        
        // Form submission with loading state
        document.getElementById('loginForm').addEventListener('submit', function() {
            const loginBtn = document.getElementById('loginBtn');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const loginText = document.getElementById('loginText');
            
            loginBtn.disabled = true;
            loadingSpinner.style.display = 'inline-block';
            loginText.textContent = 'Connexion...';
        });
        
        // Auto-focus on email field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('email').focus();
        });
        
        // Enter key handling
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const activeForm = document.querySelector('.login-form').style.display !== 'none' ? 
                    document.getElementById('loginForm') : 
                    document.getElementById('resetForm');
                activeForm.submit();
            }
        });
        
        // Remove alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s ease';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            });
        }, 5000);
        
        // Prevent multiple form submissions
        let formSubmitted = false;
        document.querySelectorAll('form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                if (formSubmitted) {
                    e.preventDefault();
                    return false;
                }
                formSubmitted = true;
            });
        });
    </script>
</body>
</html>
