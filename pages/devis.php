<section class="devis-hero">
    <div class="container">
        <div class="devis-hero-content">
            <h1>Demander un Devis</h1>
            <p>Obtenez une estimation personnalisée pour votre projet en quelques minutes</p>
        </div>
    </div>
</section>

<section class="devis-section">
    <div class="container">
        <div class="devis-container">
            <div class="devis-sidebar">
                <div class="devis-progress">
                    <h3>Progression</h3>
                    <div class="progress-steps">
                        <div class="progress-step active" data-step="1">
                            <div class="step-number">1</div>
                            <div class="step-label">Service</div>
                        </div>
                        <div class="progress-step" data-step="2">
                            <div class="step-number">2</div>
                            <div class="step-label">Détails</div>
                        </div>
                        <div class="progress-step" data-step="3">
                            <div class="step-number">3</div>
                            <div class="step-label">Contact</div>
                        </div>
                        <div class="progress-step" data-step="4">
                            <div class="step-number">4</div>
                            <div class="step-label">Confirmation</div>
                        </div>
                    </div>
                </div>
                
                <div class="devis-summary">
                    <h3>Résumé</h3>
                    <div class="summary-content">
                        <div class="summary-item">
                            <span class="summary-label">Service :</span>
                            <span class="summary-value" id="selected-service">Non sélectionné</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Sous-service :</span>
                            <span class="summary-value" id="selected-subservice">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Budget estimé :</span>
                            <span class="summary-value" id="estimated-budget">À définir</span>
                        </div>
                    </div>
                </div>
                
                <div class="contact-info">
                    <h3>Besoin d'aide ?</h3>
                    <p>Notre équipe est disponible pour vous accompagner</p>
                    <a href="tel:+237XXXXXXX" class="btn btn-outline btn-sm">
                        <i class="fas fa-phone"></i> Nous appeler
                    </a>
                </div>
            </div>
            
            <div class="devis-form-container">
                <form class="devis-form" id="devisForm" action="api/devis.php" method="POST">
                    <!-- Étape 1: Sélection du service -->
                    <div class="form-step active" data-step="1">
                        <h2>Quel service vous intéresse ?</h2>
                        <p>Sélectionnez le service principal pour votre projet</p>
                        
                        <div class="service-selection">
                            <div class="service-option" data-service="marketing">
                                <div class="service-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <h3>Marketing Digital</h3>
                                <p>SEO, SEM, réseaux sociaux, email marketing</p>
                                <div class="service-price">À partir de 75 000 FCFA</div>
                            </div>
                            
                            <div class="service-option" data-service="graphique">
                                <div class="service-icon">
                                    <i class="fas fa-palette"></i>
                                </div>
                                <h3>Conception Graphique</h3>
                                <p>Logo, identité visuelle, supports de communication</p>
                                <div class="service-price">À partir de 25 000 FCFA</div>
                            </div>
                            
                            <div class="service-option" data-service="multimedia">
                                <div class="service-icon">
                                    <i class="fas fa-video"></i>
                                </div>
                                <h3>Conception Multimédia</h3>
                                <p>Vidéo, photographie, motion design</p>
                                <div class="service-price">À partir de 100 000 FCFA</div>
                            </div>
                            
                            <div class="service-option" data-service="imprimerie">
                                <div class="service-icon">
                                    <i class="fas fa-print"></i>
                                </div>
                                <h3>Imprimerie</h3>
                                <p>Impression numérique, offset, grands formats</p>
                                <div class="service-price">À partir de 5 000 FCFA</div>
                            </div>
                        </div>
                        
                        <input type="hidden" name="service" id="selected-service-input">
                        
                        <div class="form-navigation">
                            <button type="button" class="btn btn-primary" id="next-step-1" disabled>
                                Continuer <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Étape 2: Détails du projet -->
                    <div class="form-step" data-step="2">
                        <h2>Détails de votre projet</h2>
                        <p>Aidez-nous à mieux comprendre vos besoins</p>
                        
                        <div class="subservice-selection" id="subservice-container">
                            <!-- Contenu dynamique selon le service sélectionné -->
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description du projet *</label>
                            <textarea id="description" name="description" rows="5" required placeholder="Décrivez votre projet, vos objectifs, vos contraintes..."></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="budget">Budget approximatif</label>
                                <select id="budget" name="budget">
                                    <option value="">Sélectionnez votre budget</option>
                                    <option value="moins-100k">Moins de 100 000 FCFA</option>
                                    <option value="100k-300k">100 000 - 300 000 FCFA</option>
                                    <option value="300k-500k">300 000 - 500 000 FCFA</option>
                                    <option value="500k-1m">500 000 FCFA - 1 000 000 FCFA</option>
                                    <option value="plus-1m">Plus de 1 000 000 FCFA</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="delai">Délai souhaité</label>
                                <select id="delai" name="delai">
                                    <option value="">Sélectionnez un délai</option>
                                    <option value="urgent">Urgent (moins d'une semaine)</option>
                                    <option value="1-2-semaines">1-2 semaines</option>
                                    <option value="3-4-semaines">3-4 semaines</option>
                                    <option value="1-2-mois">1-2 mois</option>
                                    <option value="flexible">Flexible</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-navigation">
                            <button type="button" class="btn btn-outline" id="prev-step-2">
                                <i class="fas fa-arrow-left"></i> Retour
                            </button>
                            <button type="button" class="btn btn-primary" id="next-step-2">
                                Continuer <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Étape 3: Informations de contact -->
                    <div class="form-step" data-step="3">
                        <h2>Vos informations</h2>
                        <p>Pour que nous puissions vous recontacter rapidement</p>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nom">Nom complet *</label>
                                <input type="text" id="nom" name="nom" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="telephone">Téléphone *</label>
                                <input type="tel" id="telephone" name="telephone" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="entreprise">Entreprise</label>
                                <input type="text" id="entreprise" name="entreprise">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="poste">Votre fonction</label>
                            <input type="text" id="poste" name="poste" placeholder="Ex: Directeur Marketing, CEO, Responsable Communication...">
                        </div>
                        
                        <div class="form-group checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="newsletter" value="1">
                                <span class="checkmark"></span>
                                Je souhaite recevoir des conseils et actualités de Divine Art Corporation
                            </label>
                        </div>
                        
                        <div class="form-group checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="rgpd" value="1" required>
                                <span class="checkmark"></span>
                                J'accepte que mes données soient utilisées pour traiter ma demande de devis *
                            </label>
                        </div>
                        
                        <div class="form-navigation">
                            <button type="button" class="btn btn-outline" id="prev-step-3">
                                <i class="fas fa-arrow-left"></i> Retour
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Envoyer la Demande
                            </button>
                        </div>
                    </div>
                    
                    <!-- Étape 4: Confirmation -->
                    <div class="form-step" data-step="4">
                        <div class="confirmation-content">
                            <div class="confirmation-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h2>Demande envoyée avec succès !</h2>
                            <p>Merci pour votre confiance. Nous avons bien reçu votre demande de devis.</p>
                            
                            <div class="next-steps">
                                <h3>Prochaines étapes :</h3>
                                <ul>
                                    <li><i class="fas fa-clock"></i> Nous étudions votre demande sous 24h</li>
                                    <li><i class="fas fa-phone"></i> Un expert vous contacte pour affiner le projet</li>
                                    <li><i class="fas fa-file-alt"></i> Vous recevez un devis détaillé sous 48h</li>
                                </ul>
                            </div>
                            
                            <div class="confirmation-actions">
                                <a href="?page=home" class="btn btn-primary">
                                    <i class="fas fa-home"></i> Retour à l'accueil
                                </a>
                                <a href="?page=contact" class="btn btn-outline">
                                    <i class="fas fa-phone"></i> Nous contacter
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
// Données des sous-services
const subServices = {
    marketing: [
        { id: 'seo-sem', name: 'SEO & SEM', price: '150 000 FCFA' },
        { id: 'social-media', name: 'Réseaux Sociaux', price: '100 000 FCFA/mois' },
        { id: 'email-marketing', name: 'Email Marketing', price: '75 000 FCFA' },
        { id: 'campagnes-pub', name: 'Campagnes Publicitaires', price: '200 000 FCFA' },
        { id: 'etudes-marche', name: 'Études de Marché', price: '300 000 FCFA' },
        { id: 'relations-publiques', name: 'Relations Publiques', price: '250 000 FCFA' }
    ],
    graphique: [
        { id: 'identite-visuelle', name: 'Identité Visuelle', price: '200 000 FCFA' },
        { id: 'supports-communication', name: 'Supports de Communication', price: '25 000 FCFA' },
        { id: 'packaging', name: 'Packaging & Étiquetage', price: '150 000 FCFA' },
        { id: 'illustrations', name: 'Illustrations & Infographies', price: '75 000 FCFA' },
        { id: 'ui-ux', name: 'UI/UX Design', price: '300 000 FCFA' },
        { id: 'design-textile', name: 'Design Textile', price: '50 000 FCFA' }
    ],
    multimedia: [
        { id: 'production-video', name: 'Production Vidéo', price: '200 000 FCFA' },
        { id: 'photographie', name: 'Photographie Professionnelle', price: '100 000 FCFA' },
        { id: 'motion-design', name: 'Motion Design', price: '150 000 FCFA' },
        { id: 'contenu-social', name: 'Contenu Réseaux Sociaux', price: '75 000 FCFA' },
        { id: 'presentations', name: 'Présentations Interactives', price: '100 000 FCFA' },
        { id: 'podcasts', name: 'Podcasts & Audio', price: '80 000 FCFA' }
    ],
    imprimerie: [
        { id: 'impression-numerique', name: 'Impression Numérique', price: '5 000 FCFA' },
        { id: 'impression-offset', name: 'Impression Offset', price: '10 000 FCFA' },
        { id: 'grands-formats', name: 'Grands Formats', price: '25 000 FCFA' },
        { id: 'supports-marketing', name: 'Supports Marketing', price: '15 000 FCFA' },
        { id: 'objets-publicitaires', name: 'Objets Publicitaires', price: '20 000 FCFA' },
        { id: 'reliure-finitions', name: 'Reliure & Finitions', price: '8 000 FCFA' }
    ]
};
</script>