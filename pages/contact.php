<section class="contact-hero">
    <div class="container">
        <div class="contact-hero-content">
            <h1>Contactez-Nous</h1>
            <p>Prêt à donner vie à vos projets ? Notre équipe est là pour vous accompagner</p>
        </div>
    </div>
</section>

<section class="contact-section">
    <div class="container">
        <div class="contact-grid">
            <div class="contact-info">
                <h2>Parlons de Votre Projet</h2>
                <p>Que vous ayez besoin d'une stratégie marketing, d'un design graphique, de contenus multimédia ou de services d'impression, nous sommes là pour vous aider.</p>
                
                <div class="contact-details">
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="contact-text">
                            <h3>Adresse</h3>
                            <p>Douala, Akwa Nord<br>Cameroun</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="contact-text">
                            <h3>Téléphone</h3>
                            <p>+237 6XX XXX XXX<br>+237 6XX XXX XXX</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="contact-text">
                            <h3>Email</h3>
                            <p>contact@divineartcorp.cm<br>info@divineartcorp.cm</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="contact-text">
                            <h3>Horaires</h3>
                            <p>Lun - Ven: 8h00 - 18h00<br>Sam: 9h00 - 15h00</p>
                        </div>
                    </div>
                </div>
                
                <div class="social-links">
                    <h3>Suivez-nous</h3>
                    <div class="social-icons">
                        <a href="#" class="social-link facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-link instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-link linkedin">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="#" class="social-link twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="social-link whatsapp">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="contact-form-container">
                <form class="contact-form" id="contactForm" action="api/contact.php" method="POST">
                    <h2>Envoyez-nous un Message</h2>
                    
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
                            <label for="telephone">Téléphone</label>
                            <input type="tel" id="telephone" name="telephone">
                        </div>
                        
                        <div class="form-group">
                            <label for="entreprise">Entreprise</label>
                            <input type="text" id="entreprise" name="entreprise">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="sujet">Sujet</label>
                        <select id="sujet" name="sujet">
                            <option value="">Sélectionnez un sujet</option>
                            <option value="marketing">Marketing Digital</option>
                            <option value="graphique">Conception Graphique</option>
                            <option value="multimedia">Conception Multimédia</option>
                            <option value="imprimerie">Imprimerie</option>
                            <option value="devis">Demande de devis</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" rows="6" required placeholder="Décrivez votre projet ou votre demande..."></textarea>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="newsletter" value="1">
                            <span class="checkmark"></span>
                            Je souhaite recevoir la newsletter de Divine Art Corporation
                        </label>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="rgpd" value="1" required>
                            <span class="checkmark"></span>
                            J'accepte que mes données soient utilisées pour me recontacter *
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-large">
                        <i class="fas fa-paper-plane"></i>
                        Envoyer le Message
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<section class="map-section">
    <div class="container">
        <h2>Notre Localisation</h2>
        <div class="map-container">
            <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3979.8234567890123!2d9.7123456789!3d4.0123456789!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNMKwMDAnMjguNCJOIDnCsDQyJzQ0LjQiRQ!5e0!3m2!1sfr!2scm!4v1234567890123!5m2!1sfr!2scm" 
                width="100%" 
                height="400" 
                style="border:0;" 
                allowfullscreen="" 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>
    </div>
</section>

<section class="faq-section">
    <div class="container">
        <div class="section-header">
            <h2>Questions Fréquentes</h2>
            <p>Trouvez rapidement les réponses à vos questions</p>
        </div>
        
        <div class="faq-grid">
            <div class="faq-item">
                <div class="faq-question">
                    <h3>Quels sont vos délais de réalisation ?</h3>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Les délais varient selon le type de projet. En général, comptez 3-5 jours pour un logo, 1-2 semaines pour une identité complète, et 2-4 semaines pour une stratégie marketing.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <h3>Proposez-vous des révisions ?</h3>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Oui, nous incluons 3 révisions gratuites dans tous nos projets. Des révisions supplémentaires peuvent être facturées selon la complexité.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <h3>Travaillez-vous avec des entreprises de toutes tailles ?</h3>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Absolument ! Nous accompagnons aussi bien les startups que les grandes entreprises, en adaptant nos services à vos besoins et budget.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <h3>Quels formats de fichiers livrez-vous ?</h3>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Nous livrons tous les formats nécessaires : AI, EPS, PDF, PNG, JPG en haute résolution, ainsi que les fichiers sources modifiables.</p>
                </div>
            </div>
        </div>
    </div>
</section>