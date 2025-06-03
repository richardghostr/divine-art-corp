<section class="error-section">
    <div class="container">
        <div class="error-content">
            <div class="error-animation">
                <div class="error-number">404</div>
                <div class="error-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
            
            <div class="error-text">
                <h1>Page non trouvée</h1>
                <p>Désolé, la page que vous recherchez n'existe pas ou a été déplacée.</p>
                
                <div class="error-actions">
                    <a href="?page=welcome" class="btn btn-primary">
                        <i class="fas fa-home"></i>
                        Retour à l'accueil
                    </a>
                    <a href="?page=contact" class="btn btn-outline">
                        <i class="fas fa-envelope"></i>
                        Nous contacter
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.error-section {
    min-height: 80vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: var(--space-20) 0;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.error-content {
    text-align: center;
    max-width: 600px;
}

.error-animation {
    margin-bottom: var(--space-12);
    position: relative;
}

.error-number {
    font-size: 8rem;
    font-weight: 900;
    color: var(--primary-color);
    line-height: 1;
    margin-bottom: var(--space-4);
    text-shadow: 0 4px 20px rgba(231, 76, 60, 0.3);
    animation: error-pulse 2s ease-in-out infinite;
}

.error-icon {
    font-size: 3rem;
    color: var(--warning-color);
    animation: error-bounce 1s ease-in-out infinite;
}

.error-text h1 {
    font-size: var(--text-4xl);
    color: var(--dark-color);
    margin-bottom: var(--space-4);
}

.error-text p {
    font-size: var(--text-lg);
    color: var(--gray-600);
    margin-bottom: var(--space-8);
}

.error-actions {
    display: flex;
    gap: var(--space-4);
    justify-content: center;
    flex-wrap: wrap;
}

@keyframes error-pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

@keyframes error-bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-10px);
    }
    60% {
        transform: translateY(-5px);
    }
}

@media (max-width: 768px) {
    .error-number {
        font-size: 6rem;
    }
    
    .error-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .error-actions .btn {
        width: 100%;
        max-width: 300px;
    }
}
</style>