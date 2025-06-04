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
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3979.808258706028!2d9.735686!3d4.01498!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNMKwMDAnNTMuOSJOIDnCsDQ0JzA4LjUiRQ!5e0!3m2!1sfr!2scm!4v1717500000000!5m2!1sfr!2scm"
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

<style>
    /* Contact Page Styles */
    .contact-hero {
        padding: 120px 0 80px;
        position: relative;
        overflow: hidden;
        background: linear-gradient(135deg, var(--dark-color) 0%, var(--primary-color) 100%);
        color: var(--white);
        text-align: center;
    }

    .contact-hero h1 {
        font-size: 2.5rem;
        font-weight: bold;
        margin-bottom: 1rem;
    }

    .contact-hero p {
        font-size: 1.2rem;
        opacity: 0.9;
        max-width: 700px;
        margin: 0 auto;
    }

    .contact-section {
        padding: 80px 0;
        background: var(--white);
    }

    .contact-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 3rem;
    }

    .contact-info {
        padding: 2rem;
        background: var(--gray-100);
        border-radius: var(--border-radius);
    }

    .contact-info h2 {
        font-size: 1.8rem;
        font-weight: bold;
        color: var(--dark-color);
        margin-bottom: 1.5rem;
        position: relative;
        padding-bottom: 0.5rem;
    }

    .contact-info h2::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 50px;
        height: 2px;
        background: var(--primary-color);
    }

    .contact-info p {
        color: var(--gray-600);
        margin-bottom: 2rem;
        line-height: 1.6;
    }

    .contact-details {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .contact-item {
        display: flex;
        gap: 1rem;
        align-items: flex-start;
    }

    .contact-icon {
        width: 50px;
        height: 50px;
        background: var(--primary-color);
        color: var(--white);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    .contact-text h3 {
        font-size: 1.1rem;
        font-weight: bold;
        color: var(--dark-color);
        margin-bottom: 0.3rem;
    }

    .contact-text p {
        color: var(--gray-600);
        margin-bottom: 0;
        font-size: 0.95rem;
        line-height: 1.5;
    }

    .social-links h3 {
        font-size: 1.1rem;
        font-weight: bold;
        color: var(--dark-color);
        margin-bottom: 1rem;
    }

    .social-icons {
        display: flex;
        gap: 0.8rem;
    }

    .social-link {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--white);
        font-size: 1rem;
        transition: var(--transition);
    }

    .social-link.facebook {
        background: #3b5998;
    }

    .social-link.instagram {
        background: #e1306c;
    }

    .social-link.linkedin {
        background: #0077b5;
    }

    .social-link.twitter {
        background: #1da1f2;
    }

    .social-link.whatsapp {
        background: #25d366;
    }

    .social-link:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .contact-form-container {
        padding: 2rem;
        background: var(--white);
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
    }

    .contact-form h2 {
        font-size: 1.8rem;
        font-weight: bold;
        color: var(--dark-color);
        margin-bottom: 1.5rem;
        position: relative;
        padding-bottom: 0.5rem;
    }

    .contact-form h2::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 50px;
        height: 2px;
        background: var(--primary-color);
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: var(--gray-700);
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid var(--gray-300);
        border-radius: var(--border-radius);
        font-family: var(--font-family);
        transition: var(--transition);
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.2);
    }

    .form-group textarea {
        min-height: 150px;
        resize: vertical;
    }

    .checkbox-group {
        position: relative;
        padding-left: 30px;
        cursor: pointer;
        margin-bottom: 1rem;
        font-size: 0.9rem;
        color: var(--gray-700);
    }

    .checkbox-group input {
        position: absolute;
        opacity: 0;
        cursor: pointer;
        height: 0;
        width: 0;
    }

    .checkmark {
        position: absolute;
        top: 0;
        left: 0;
        height: 20px;
        width: 20px;
        background-color: var(--gray-100);
        border: 1px solid var(--gray-300);
        border-radius: 4px;
    }

    .checkbox-group:hover input~.checkmark {
        background-color: var(--gray-200);
    }

    .checkbox-group input:checked~.checkmark {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .checkmark:after {
        content: "";
        position: absolute;
        display: none;
    }

    .checkbox-group input:checked~.checkmark:after {
        display: block;
    }

    .checkbox-group .checkmark:after {
        left: 7px;
        top: 3px;
        width: 5px;
        height: 10px;
        border: solid white;
        border-width: 0 2px 2px 0;
        transform: rotate(45deg);
    }

    .btn-large {
        padding: 1rem 2rem;
        font-size: 1.1rem;
        width: 100%;
    }

    .map-section {
        padding: 80px 0;
        background: var(--gray-100);
    }

    .map-section h2 {
        font-size: 2rem;
        font-weight: bold;
        color: var(--dark-color);
        margin-bottom: 1.5rem;
        text-align: center;
    }

    .map-container {
        border-radius: var(--border-radius);
        overflow: hidden;
        box-shadow: var(--box-shadow);
        height: 400px;
    }

    .faq-section {
        padding: 80px 0;
        background: var(--white);
    }

    .faq-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
        margin-top: 2rem;
    }

    .faq-item {
        border: 1px solid var(--gray-200);
        border-radius: var(--border-radius);
        overflow: hidden;
        transition: var(--transition);
    }

    .faq-item:hover {
        border-color: var(--primary-color);
    }

    .faq-question {
        padding: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        background: var(--gray-100);
    }

    .faq-question h3 {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--dark-color);
        margin: 0;
    }

    .faq-question i {
        transition: var(--transition);
    }

    .faq-item.active .faq-question i {
        transform: rotate(180deg);
    }

    .faq-answer {
        padding: 0 1.5rem;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-out;
    }

    .faq-item.active .faq-answer {
        padding: 0 1.5rem 1.5rem;
        max-height: 300px;
    }

    .faq-answer p {
        color: var(--gray-600);
        line-height: 1.6;
    }

    /* Responsive Adjustments */
    @media (max-width: 992px) {
        .contact-grid {
            grid-template-columns: 1fr;
        }

        .contact-info {
            order: 2;
            margin-top: 3rem;
        }
    }

    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .contact-hero {
            padding: 100px 0 60px;
        }

        .contact-hero h1 {
            font-size: 2rem;
        }

        .faq-question h3 {
            font-size: 1rem;
        }
    }

    @media (max-width: 576px) {
        .contact-details {
            grid-template-columns: 1fr;
        }

        .social-icons {
            flex-wrap: wrap;
        }

        .map-section {
            padding: 60px 0;
        }

        .map-container {
            height: 300px;
        }
    }
</style>