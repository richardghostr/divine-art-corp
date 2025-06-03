// Gestion du formulaire de devis multi-étapes
document.addEventListener('DOMContentLoaded', function() {
    initDevisForm();
});

function initDevisForm() {
    const form = document.getElementById('devisForm');
    const steps = document.querySelectorAll('.form-step');
    const progressSteps = document.querySelectorAll('.progress-step');
    const serviceOptions = document.querySelectorAll('.service-option');
    
    let currentStep = 1;
    let selectedService = '';
    let selectedSubservice = '';
    
    // Déclaration des variables nécessaires
    const subServices = {
        'marketing': [
            { id: 'seo', name: 'SEO', price: '100€' },
            { id: 'smm', name: 'SMO', price: '150€' }
        ],
        'graphique': [
            { id: 'logo', name: 'Logo', price: '200€' },
            { id: 'flyer', name: 'Flyer', price: '100€' }
        ],
        'multimedia': [
            { id: 'video', name: 'Vidéo', price: '300€' },
            { id: 'animation', name: 'Animation', price: '250€' }
        ],
        'imprimerie': [
            { id: 'brochure', name: 'Brochure', price: '150€' },
            { id: 'flyer', name: 'Flyer', price: '100€' }
        ]
    };
    
    function showToast(message, type) {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
    
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    function isValidPhone(phone) {
        const re = /^\+?[0-9]{7,15}$/;
        return re.test(phone);
    }
    
    function showFieldError(field, message) {
        const errorMsg = document.createElement('div');
        errorMsg.className = 'error-message';
        errorMsg.textContent = message;
        field.parentNode.appendChild(errorMsg);
        field.classList.add('error');
    }
    
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            const context = this;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), wait);
        };
    }
    
    // Gestion de la sélection de service
    serviceOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Retirer la sélection précédente
            serviceOptions.forEach(opt => opt.classList.remove('selected'));
            
            // Ajouter la sélection actuelle
            this.classList.add('selected');
            
            selectedService = this.dataset.service;
            document.getElementById('selected-service-input').value = selectedService;
            
            // Mettre à jour le résumé
            updateSummary();
            
            // Activer le bouton suivant
            document.getElementById('next-step-1').disabled = false;
        });
    });
    
    // Navigation entre les étapes
    document.getElementById('next-step-1').addEventListener('click', () => goToStep(2));
    document.getElementById('prev-step-2').addEventListener('click', () => goToStep(1));
    document.getElementById('next-step-2').addEventListener('click', () => goToStep(3));
    document.getElementById('prev-step-3').addEventListener('click', () => goToStep(2));
    
    // Soumission du formulaire
    form.addEventListener('submit', handleFormSubmit);
    
    function goToStep(step) {
        if (step === 2 && selectedService) {
            generateSubservices();
        }
        
        // Cacher toutes les étapes
        steps.forEach(s => s.classList.remove('active'));
        progressSteps.forEach(s => s.classList.remove('active'));
        
        // Afficher l'étape actuelle
        document.querySelector(`[data-step="${step}"]`).classList.add('active');
        document.querySelector(`.progress-step[data-step="${step}"]`).classList.add('active');
        
        currentStep = step;
        
        // Scroll vers le haut
        document.querySelector('.devis-section').scrollIntoView({ behavior: 'smooth' });
    }
    
    function generateSubservices() {
        const container = document.getElementById('subservice-container');
        const services = subServices[selectedService] || [];
        
        let html = '<h3>Choisissez un sous-service (optionnel)</h3>';
        html += '<div class="subservice-grid">';
        
        services.forEach(service => {
            html += `
                <div class="subservice-option" data-subservice="${service.id}">
                    <h4>${service.name}</h4>
                    <div class="subservice-price">À partir de ${service.price}</div>
                </div>
            `;
        });
        
        html += '</div>';
        html += '<input type="hidden" name="sous_service" id="selected-subservice-input">';
        
        container.innerHTML = html;
        
        // Ajouter les événements aux sous-services
        const subserviceOptions = container.querySelectorAll('.subservice-option');
        subserviceOptions.forEach(option => {
            option.addEventListener('click', function() {
                subserviceOptions.forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                
                selectedSubservice = this.dataset.subservice;
                document.getElementById('selected-subservice-input').value = selectedSubservice;
                
                updateSummary();
            });
        });
    }
    
    function updateSummary() {
        const serviceNames = {
            'marketing': 'Marketing Digital',
            'graphique': 'Conception Graphique',
            'multimedia': 'Conception Multimédia',
            'imprimerie': 'Imprimerie'
        };
        
        document.getElementById('selected-service').textContent = serviceNames[selectedService] || 'Non sélectionné';
        
        if (selectedSubservice) {
            const subserviceData = subServices[selectedService]?.find(s => s.id === selectedSubservice);
            document.getElementById('selected-subservice').textContent = subserviceData?.name || '-';
            document.getElementById('estimated-budget').textContent = subserviceData?.price || 'À définir';
        } else {
            document.getElementById('selected-subservice').textContent = '-';
            document.getElementById('estimated-budget').textContent = 'À définir';
        }
    }
    
    function handleFormSubmit(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        
        // Désactiver le bouton et afficher le loader
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi en cours...';
        
        fetch('api/devis.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Afficher l'étape de confirmation
                goToStep(4);
                
                // Afficher le numéro de devis
                if (data.devis_number) {
                    const confirmationContent = document.querySelector('[data-step="4"] .confirmation-content');
                    const devisNumber = document.createElement('div');
                    devisNumber.className = 'devis-number';
                    devisNumber.innerHTML = `<p><strong>Numéro de référence:</strong> ${data.devis_number}</p>`;
                    confirmationContent.insertBefore(devisNumber, confirmationContent.querySelector('.next-steps'));
                }
                
                // Réinitialiser le formulaire
                form.reset();
                selectedService = '';
                selectedSubservice = '';
                updateSummary();
                
            } else {
                showToast(data.message || 'Une erreur est survenue', 'error');
                
                // Réactiver le bouton
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Envoyer la Demande';
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showToast('Une erreur est survenue lors de l\'envoi', 'error');
            
            // Réactiver le bouton
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Envoyer la Demande';
        });
    }
}

// Gestion des FAQ
document.addEventListener('DOMContentLoaded', function() {
    const faqItems = document.querySelectorAll('.faq-item');
    
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        const answer = item.querySelector('.faq-answer');
        const icon = question.querySelector('i');
        
        question.addEventListener('click', function() {
            const isOpen = item.classList.contains('open');
            
            // Fermer tous les autres items
            faqItems.forEach(otherItem => {
                otherItem.classList.remove('open');
                otherItem.querySelector('.faq-answer').style.maxHeight = '0';
                otherItem.querySelector('.faq-question i').style.transform = 'rotate(0deg)';
            });
            
            // Ouvrir/fermer l'item actuel
            if (!isOpen) {
                item.classList.add('open');
                answer.style.maxHeight = answer.scrollHeight + 'px';
                icon.style.transform = 'rotate(180deg)';
            }
        });
    });
});

// Gestion du portfolio avec filtres
document.addEventListener('DOMContentLoaded', function() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    const portfolioItems = document.querySelectorAll('.portfolio-item');
    
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const filter = this.dataset.filter;
            
            // Mettre à jour les boutons actifs
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Filtrer les éléments
            portfolioItems.forEach(item => {
                if (filter === 'all' || item.dataset.category === filter) {
                    item.style.display = 'block';
                    item.style.animation = 'fadeInUp 0.5s ease-out';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
});

// Validation en temps réel des formulaires
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('input[required], textarea[required], select[required]');
    
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            if (this.classList.contains('error')) {
                validateField(this);
            }
        });
    });
    
    function validateField(field) {
        const errorMsg = field.parentNode.querySelector('.error-message');
        
        // Supprimer l'erreur existante
        if (errorMsg) {
            errorMsg.remove();
        }
        field.classList.remove('error');
        
        // Valider le champ
        let isValid = true;
        let message = '';
        
        if (field.hasAttribute('required') && !field.value.trim()) {
            isValid = false;
            message = 'Ce champ est requis';
        } else if (field.type === 'email' && field.value && !isValidEmail(field.value)) {
            isValid = false;
            message = 'Veuillez entrer une adresse email valide';
        } else if (field.type === 'tel' && field.value && !isValidPhone(field.value)) {
            isValid = false;
            message = 'Veuillez entrer un numéro de téléphone valide';
        }
        
        if (!isValid) {
            showFieldError(field, message);
        }
        
        return isValid;
    }
});

// Sauvegarde automatique du formulaire de devis
document.addEventListener('DOMContentLoaded', function() {
    const devisForm = document.getElementById('devisForm');
    if (!devisForm) return;
    
    const inputs = devisForm.querySelectorAll('input, select, textarea');
    
    // Charger les données sauvegardées
    loadFormData();
    
    // Sauvegarder à chaque modification
    inputs.forEach(input => {
        input.addEventListener('input', debounce(saveFormData, 1000));
    });
    
    function saveFormData() {
        const formData = {};
        inputs.forEach(input => {
            if (input.type === 'checkbox') {
                formData[input.name] = input.checked;
            } else {
                formData[input.name] = input.value;
            }
        });
        
        localStorage.setItem('devis_form_data', JSON.stringify(formData));
    }
    
    function loadFormData() {
        const savedData = localStorage.getItem('devis_form_data');
        if (!savedData) return;
        
        try {
            const formData = JSON.parse(savedData);
            
            inputs.forEach(input => {
                if (formData.hasOwnProperty(input.name)) {
                    if (input.type === 'checkbox') {
                        input.checked = formData[input.name];
                    } else {
                        input.value = formData[input.name];
                    }
                }
            });
        } catch (e) {
            console.error('Erreur lors du chargement des données sauvegardées:', e);
        }
    }
    
    // Nettoyer les données sauvegardées après envoi réussi
    devisForm.addEventListener('submit', function() {
        setTimeout(() => {
            localStorage.removeItem('devis_form_data');
        }, 2000);
    });
});