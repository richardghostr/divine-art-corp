<?php
session_start();

require_once '../includes/auth.php';

$auth = new Auth();
$auth->logout();

// Supprimer le cookie "remember me" si il existe
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
}

// Log de déconnexion
error_log("Déconnexion admin depuis " . ($_SERVER['REMOTE_ADDR'] ?? 'IP inconnue') . " à " . date('Y-m-d H:i:s'));

// Redirection vers la page de connexion avec message
header('Location: login.php?message=logout_success');
exit;
?>
