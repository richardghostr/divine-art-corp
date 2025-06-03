<header class="header" style="margin-bottom:-5px;">
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="nav-brand">
                <img src="assets/images/logo.svg" alt="DAC Logo" class="logo">
                <span class="brand-text">Divine Art Corporation</span>
            </a>

            <div class="nav-menu" id="nav-menu">
                <ul class="nav-list">
                    <li><a href="?page=home" class="<?php echo $page == 'home' ? 'active' : ''; ?>">Accueil</a></li>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle">Services <i class="fas fa-chevron-down"></i></a>
                        <ul class="dropdown-menu">
                            <li><a href="?page=marketing">Marketing Digital</a></li>
                            <li><a href="?page=graphique">Conception Graphique</a></li>
                            <li><a href="?page=multimedia">Conception Multimédia</a></li>
                            <li><a href="?page=imprimerie">Imprimerie</a></li>
                        </ul>
                    </li>
                    <li><a href="?page=contact" class="<?php echo $page == 'contact' ? 'active' : ''; ?>">Contact</a></li>
                    <li><a href="?page=devis" class="btn btn-primary ">&nbsp; Demander un Devis &nbsp;</a></li>
                </ul>
            </div>

            <div class="nav-toggle" id="nav-toggle" aria-label="Menu">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>
</header>

<style>
    /* Header Révolutionnaire */
    .header {
        background: var(--white);
        box-shadow: var(--box-shadow);
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 999;
        transition: all 0.3s ease-in-out;
    }

    .header.shrink {
        padding: 0.3rem 0;
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 1px 10px rgba(0, 0, 0, 0.05);
    }

    /* Logo animation */
    .logo {
        height: 40px;
        transition: transform 0.3s ease;
    }

    .logo:hover {
        transform: scale(1.1) rotate(5deg);
    }

    /* Menu Principal */
    .nav-list a {
        position: relative;
    }

    .nav-list a::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: -4px;
        width: 0%;
        height: 2px;
        background: var(--primary-color);
        transition: width 0.3s;
    }

    .nav-list a:hover::after,
    .nav-list a.active::after {
        width: 100%;
    }

    /* Dropdown Animation */
    .dropdown-menu {
        transform: translateY(-10px);
        opacity: 0;
        visibility: hidden;
        transition: var(--transition);
    }

    .dropdown:hover .dropdown-menu {
        transform: translateY(0);
        opacity: 1;
        visibility: visible;
    }

    /* Burger menu animation */
    .nav-toggle.open span:nth-child(1) {
        transform: rotate(45deg) translate(5px, 5px);
    }

    .nav-toggle.open span:nth-child(2) {
        opacity: 0;
    }

    .nav-toggle.open span:nth-child(3) {
        transform: rotate(-45deg) translate(5px, -5px);
    }

    .nav-toggle span {
        transition: all 0.3s ease;
    }
</style>