<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Récupérer la page demandée
$page = $_GET['page'] ?? 'welcome';

// Pages autorisées
$allowed_pages = [
    'welcome',
    'home', 
    'marketing', 
    'graphique', 
    'multimedia', 
    'imprimerie', 
    'contact', 
    'devis',
    'admin'
];

// Vérifier si la page existe
if (!in_array($page, $allowed_pages)) {
    $page = 'welcome';
}

// Afficher la page welcome sans header/footer
if ($page === 'welcome') {
    include 'pages/welcome.php';
    exit;
}

// Pour toutes les autres pages, inclure header et footer
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo getPageTitle($page); ?> - Divine Art Corporation</title>
    <meta name="description" content="<?php echo getPageDescription($page); ?>">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    
    <!-- Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="main-content">
        <?php
        $page_file = "pages/{$page}.php";
        if (file_exists($page_file)) {
            include $page_file;
        } else {
            include 'pages/404.php';
        }
        ?>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <!-- Scripts -->
    <script src="assets/js/main.js"></script>
    <script src="assets/js/devis.js"></script>
</body>
</html>