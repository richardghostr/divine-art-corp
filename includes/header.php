<header class="header">
        <nav class="navbar">
            <div class="container">
                <div class="nav-brand">
                    <img src="assets/images/logo.svg" alt="DAC Logo" class="logo">
                    <span class="brand-text">Divine Art Corporation</span>
                </div>
                
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
                        <li><a href="?page=devis" class="btn btn-primary " >&nbsp; Demander un Devis &nbsp;</a></li>
                    </ul>
                </div>
                
                <div class="nav-toggle" id="nav-toggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        </nav>
    </header>