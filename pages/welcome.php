<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue - Divine Art Corporation</title>
    <meta name="description" content="Divine Art Corporation - Votre partenaire créatif au Cameroun pour tous vos besoins en marketing, design et impression.">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        /* Variables CSS pour la page de bienvenue */
        :root {
            --primary-color: #e74c3c;
            --primary-light: #ff6b5a;
            --primary-dark: #c0392b;
            --secondary-color: #3498db;
            --accent-color: #f39c12;
            --dark-color: #2c3e50;
            --white: #ffffff;
            --glass-bg: rgba(255, 255, 255, 0.15);
            --glass-border: rgba(255, 255, 255, 0.2);
            --shadow_glow: 0 8px 32px rgba(231, 76, 60, 0.3);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            overflow: hidden;
            height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            position: relative;
        }

        /* Arrière-plan animé */
        .welcome-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            overflow: hidden;
        }

        .bg-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
        }

        .shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 6s ease-in-out infinite;
        }

        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 60%;
            right: 15%;
            animation-delay: 2s;
        }

        .shape:nth-child(3) {
            width: 60px;
            height: 60px;
            bottom: 30%;
            left: 20%;
            animation-delay: 4s;
        }

        .shape:nth-child(4) {
            width: 100px;
            height: 100px;
            top: 10%;
            right: 30%;
            animation-delay: 1s;
        }

        .shape:nth-child(5) {
            width: 140px;
            height: 140px;
            bottom: 20%;
            right: 40%;
            animation-delay: 3s;
        }

        .shape:nth-child(6) {
            width: 90px;
            height: 90px;
            top: 40%;
            left: 5%;
            animation-delay: 5s;
        }

        /* Particules flottantes */
        .particles {
            position: absolute;
            width: 100%;
            height: 100%;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 50%;
            animation: particle-float 8s linear infinite;
        }

        .particle:nth-child(odd) {
            animation-duration: 10s;
            background: rgba(231, 76, 60, 0.4);
        }

        .particle:nth-child(even) {
            animation-duration: 12s;
            background: rgba(52, 152, 219, 0.4);
        }

        /* Container principal */
        .welcome-container {
            position: relative;
            z-index: 10;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        /* Cercle glassmorphism principal */
        .glass-circle {
            position: relative;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.2),
                0 0 60px rgba(231, 76, 60, 0.2);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: var(--transition-slow);
            animation: glass-glow 4s ease-in-out infinite alternate;
            cursor: pointer;
        }

        .glass-circle:hover {
            transform: scale(1.05);
            box-shadow: 
                0 12px 40px rgba(0, 0, 0, 0.15),
                inset 0 1px 0 rgba(255, 255, 255, 0.3),
                0 0 80px rgba(231, 76, 60, 0.4);
        }

        /* Logo container */
        .logo-container {
            position: relative;
            margin-bottom: 2rem;
            animation: logo-pulse 3s ease-in-out infinite;
           
        }

        .logo {
            width: 120px;
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .logo::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 300%;
            height: 300%;
            transform: rotate(45deg);
            animation: logo-shine 5s ease-in-out infinite;
        }

        .logo-text {
            font-family: 'Poppins', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            color: var(--white);
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            z-index: 2;
            position: relative;
        }

        /* Texte de bienvenue */
        .welcome-text {
            text-align: center;
            color: var(--white);
            margin-top: -2rem;
            
        }

        .welcome-title {
            font-family: 'Poppins', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            animation: text-glow 2s ease-in-out infinite alternate;
        }

        .welcome-subtitle {
            font-size: 1.2rem;
            font-weight: 300;
            opacity: 0.9;
            margin-bottom: 2rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        /* Bouton d'entrée */
        .enter-button {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: var(--white);
            border: none;
            padding: 1rem 2.5rem;
            border-radius: 50px;
            font-family: 'Poppins', sans-serif;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 
                0 8px 25px rgba(231, 76, 60, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .enter-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: var(--transition-slow);
        }

        .enter-button:hover::before {
            left: 100%;
        }

        .enter-button:hover {
            transform: translateY(-3px);
            box-shadow: 
                0 12px 35px rgba(231, 76, 60, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        .enter-button:active {
            transform: translateY(-1px);
        }

        /* Indicateurs décoratifs */
        .decorative-rings {
            position: absolute;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: ring-rotate 20s linear infinite;
        }

        .decorative-rings::before {
            content: '';
            position: absolute;
            top: 50px;
            left: 50px;
            right: 50px;
            bottom: 50px;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, 0.05);
            animation: ring-rotate 15s linear infinite reverse;
        }

        .decorative-rings::after {
            content: '';
            position: absolute;
            top: 100px;
            left: 100px;
            right: 100px;
            bottom: 100px;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, 0.08);
            animation: ring-rotate 25s linear infinite;
        }

        /* Points décoratifs autour du cercle */
        .orbit-dots {
            position: absolute;
            width: 480px;
            height: 480px;
            border-radius: 50%;
        }

        .orbit-dot {
            position: absolute;
            width: 8px;
            height: 8px;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 50%;
            animation: orbit 8s linear infinite;
        }

        .orbit-dot:nth-child(1) {
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            animation-delay: 0s;
        }

        .orbit-dot:nth-child(2) {
            top: 50%;
            right: 0;
            transform: translateY(-50%);
            animation-delay: 2s;
        }

        .orbit-dot:nth-child(3) {
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            animation-delay: 4s;
        }

        .orbit-dot:nth-child(4) {
            top: 50%;
            left: 0;
            transform: translateY(-50%);
            animation-delay: 6s;
        }

        /* Loading indicator */
        .loading-indicator {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .loading-dots {
            display: flex;
            gap: 0.3rem;
        }

        .loading-dot {
            width: 6px;
            height: 6px;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 50%;
            animation: loading-bounce 1.4s ease-in-out infinite both;
        }

        .loading-dot:nth-child(1) { animation-delay: -0.32s; }
        .loading-dot:nth-child(2) { animation-delay: -0.16s; }
        .loading-dot:nth-child(3) { animation-delay: 0s; }

        /* Animations */
        @keyframes float {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
            }
        }

        @keyframes particle-float {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) rotate(360deg);
                opacity: 0;
            }
        }

        @keyframes glass-glow {
            0% {
                box-shadow: 
                    0 8px 32px rgba(0, 0, 0, 0.1),
                    inset 0 1px 0 rgba(255, 255, 255, 0.2),
                    0 0 60px rgba(231, 76, 60, 0.2);
            }
            100% {
                box-shadow: 
                    0 8px 32px rgba(0, 0, 0, 0.1),
                    inset 0 1px 0 rgba(255, 255, 255, 0.2),
                    0 0 80px rgba(231, 76, 60, 0.4);
            }
        }

        @keyframes logo-pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        @keyframes logo-shine {
            0% {
                transform: translateX(-100%) translateY(-100%) rotate(45deg);
            }
            50% {
                transform: translateX(100%) translateY(100%) rotate(45deg);
            }
            100% {
                transform: translateX(-100%) translateY(-100%) rotate(45deg);
            }
        }

        @keyframes text-glow {
            0% {
                text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            }
            100% {
                text-shadow: 
                    0 4px 20px rgba(0, 0, 0, 0.3),
                    0 0 30px rgba(255, 255, 255, 0.3);
            }
        }

        @keyframes ring-rotate {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes orbit {
            from {
                transform: rotate(0deg) translateX(240px) rotate(0deg);
            }
            to {
                transform: rotate(360deg) translateX(240px) rotate(-360deg);
            }
        }

        @keyframes loading-bounce {
            0%, 80%, 100% {
                transform: scale(0);
            }
            40% {
                transform: scale(1);
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: scale(1);
            }
            to {
                opacity: 0;
                transform: scale(1.1);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .glass-circle {
                width: 320px;
                height: 320px;
            }

            .logo {
                width: 100px;
                height: 100px;
            }

            .logo-text {
                font-size: 1.5rem;
            }

            .welcome-title {
                font-size: 2rem;
            }

            .welcome-subtitle {
                font-size: 1rem;
            }

            .decorative-rings {
                width: 400px;
                height: 400px;
            }

            .orbit-dots {
                width: 380px;
                height: 380px;
            }
        }

        @media (max-width: 480px) {
            .welcome-container {
                padding: 1rem;
            }

            .glass-circle {
                width: 280px;
                height: 280px;
            }

            .logo {
                width: 80px;
                height: 80px;
            }

            .logo-text {
                font-size: 1.2rem;
            }

            .welcome-title {
                font-size: 1.5rem;
            }

            .welcome-subtitle {
                font-size: 0.9rem;
            }

            .enter-button {
                padding: 0.8rem 2rem;
                font-size: 1rem;
            }
        }

        /* Animation de sortie */
        .welcome-container.fade-out {
            animation: fadeOut 0.8s ease-in-out forwards;
        }
    </style>
</head>
<body>
    <!-- Arrière-plan animé -->
    <div class="welcome-background">
        <div class="bg-shapes">
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
        </div>
        
        <div class="particles" id="particles"></div>
    </div>

    <!-- Container principal -->
    <div class="welcome-container" id="welcomeContainer">
        <!-- Anneaux décoratifs -->
        <div class="decorative-rings"></div>
        
        <!-- Points orbitaux -->
        <div class="orbit-dots">
            <div class="orbit-dot"></div>
            <div class="orbit-dot"></div>
            <div class="orbit-dot"></div>
            <div class="orbit-dot"></div>
        </div>

        <!-- Cercle glassmorphism principal -->
        <div class="glass-circle" id="glassCircle">
            <!-- Logo -->
            <div class="logo-container">
                <div class="logo">
                    <img src="assets/images/logo.svg" alt="DAC Logo" class="logo">
                </div>
            </div>

            <!-- Texte de bienvenue -->
            <div class="welcome-text">
                <h1 class="welcome-title">Bienvenue</h1>
                <p class="welcome-subtitle">Divine Art Corporation</p>
            </div>

            <!-- Bouton d'entrée -->
            <a href="?page=home" class="enter-button" id="enterButton">
                <i class="fas fa-arrow-right"></i>
                Commencer
            </a>
        </div>

       
    </div>

    <script>
        // Génération des particules
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 50;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                // Position aléatoire
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 8 + 's';
                particle.style.animationDuration = (Math.random() * 4 + 8) + 's';
                
                particlesContainer.appendChild(particle);
            }
        }

        // Animation d'entrée au site
        function enterSite() {
            const welcomeContainer = document.getElementById('welcomeContainer');
            welcomeContainer.classList.add('fade-out');
            
            setTimeout(() => {
                window.location.href = '?page=home';
            }, 800);
        }

        // Gestion des événements
        document.addEventListener('DOMContentLoaded', function() {
            // Créer les particules
            createParticles();
            
            // Gestion du clic sur le cercle
            const glassCircle = document.getElementById('glassCircle');
            const enterButton = document.getElementById('enterButton');
            
            glassCircle.addEventListener('click', function(e) {
                if (e.target === glassCircle || e.target.closest('.logo-container')) {
                    enterSite();
                }
            });
            
            enterButton.addEventListener('click', function(e) {
                e.preventDefault();
                enterSite();
            });
            
            // // Auto-redirect après 10 secondes
            // setTimeout(() => {
            //     if (document.getElementById('welcomeContainer')) {
            //         enterSite();
            //     }
            // }, 10000);
            
            // Gestion du clavier
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    enterSite();
                }
            });
        });

        // Effet de parallax sur le mouvement de la souris
        document.addEventListener('mousemove', function(e) {
            const shapes = document.querySelectorAll('.shape');
            const mouseX = e.clientX / window.innerWidth;
            const mouseY = e.clientY / window.innerHeight;
            
            shapes.forEach((shape, index) => {
                const speed = (index + 1) * 0.5;
                const x = (mouseX - 0.5) * speed * 20;
                const y = (mouseY - 0.5) * speed * 20;
                
                shape.style.transform = `translate(${x}px, ${y}px)`;
            });
            
            // Effet sur le cercle principal
            const glassCircle = document.getElementById('glassCircle');
            const moveX = (mouseX - 0.5) * 10;
            const moveY = (mouseY - 0.5) * 10;
            
            glassCircle.style.transform = `translate(${moveX}px, ${moveY}px)`;
        });

        // Animation de typing pour le texte
        function typeWriter(element, text, speed = 100) {
            let i = 0;
            element.innerHTML = '';
            
            function type() {
                if (i < text.length) {
                    element.innerHTML += text.charAt(i);
                    i++;
                    setTimeout(type, speed);
                }
            }
            
            type();
        }

        // Démarrer l'animation de typing après un délai
        setTimeout(() => {
            const subtitle = document.querySelector('.welcome-subtitle');
            typeWriter(subtitle, 'Divine Art Corporation', 80);
        }, 1000);
    </script>
</body>
</html>