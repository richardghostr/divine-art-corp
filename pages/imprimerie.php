<section class="service-hero imprimerie-hero"  style=" background-image: url('assets/images/green.jpg');background-size: cover; background-position: center;margin-top: -5px;">
    <div class="container">
        <div class="service-hero-content">
            <div class="service-hero-text">
                <h1 class="service-title">
                    <i class="fas fa-print"></i>
                    Imprimerie
                </h1>
                <p class="service-subtitle">Impression haute qualité pour tous vos supports marketing</p>
                <p class="service-description">
                    Donnez une dimension tangible à vos créations avec nos services d'impression professionnelle. 
                    Du petit format aux grands panneaux publicitaires, nous maîtrisons toutes les techniques 
                    d'impression pour sublimer vos projets.
                </p>
                <div class="service-cta">
                    <a href="?page=devis&service=imprimerie" class="btn btn-primary btn-large">
                        <i class="fas fa-print"></i> Demander un Devis
                    </a>
                    <a href="#calculator" class="btn btn-outline btn-large">
                        <i class="fas fa-calculator"></i> Calculateur de Prix
                    </a>
                </div>
            </div>
            <div class="service-hero-image">
                <!-- <img src="assets/images/services/marketing-hero.svg" alt="Marketing Digital" class="hero-img"> -->
            </div>
        </div>
    </div>
</section>

<section class="service-details">
    <div class="container">
        <div class="services-grid">
            <div class="service-item">
                <div class="service-icon">
                    <i class="fas fa-desktop"></i>
                </div>
                <h3>Impression Numérique</h3>
                <p>Impression rapide et économique pour vos petites et moyennes séries.</p>
                <ul class="service-features">
                    <li>Cartes de visite</li>
                    <li>Flyers et dépliants</li>
                    <li>Brochures</li>
                    <li>Rapports annuels</li>
                    <li>Livrets</li>
                </ul>
                <div class="service-price">À partir de 5 000 FCFA</div>
            </div>

            <div class="service-item">
                <div class="service-icon">
                    <i class="fas fa-industry"></i>
                </div>
                <h3>Impression Offset</h3>
                <p>Qualité supérieure pour vos gros tirages et supports premium.</p>
                <ul class="service-features">
                    <li>Catalogues</li>
                    <li>Magazines</li>
                    <li>Livres</li>
                    <li>Packaging</li>
                    <li>Étiquettes</li>
                </ul>
                <div class="service-price">À partir de 10 000 FCFA</div>
            </div>

            <div class="service-item">
                <div class="service-icon">
                    <i class="fas fa-expand-arrows-alt"></i>
                </div>
                <h3>Grands Formats</h3>
                <p>Impression grand format pour vos communications extérieures.</p>
                <ul class="service-features">
                    <li>Bâches publicitaires</li>
                    <li>Roll-ups</li>
                    <li>Panneaux rigides</li>
                    <li>Kakémonos</li>
                    <li>Adhésifs véhicules</li>
                </ul>
                <div class="service-price">À partir de 25 000 FCFA</div>
            </div>

            <div class="service-item">
                <div class="service-icon">
                    <i class="fas fa-bullhorn"></i>
                </div>
                <h3>Supports Marketing</h3>
                <p>Tous vos supports de communication imprimés.</p>
                <ul class="service-features">
                    <li>Affiches</li>
                    <li>Calendriers</li>
                    <li>Menus restaurants</li>
                    <li>Cartes de fidélité</li>
                    <li>Bons de réduction</li>
                </ul>
                <div class="service-price">À partir de 15 000 FCFA</div>
            </div>

            <div class="service-item">
                <div class="service-icon">
                    <i class="fas fa-gift"></i>
                </div>
                <h3>Objets Publicitaires</h3>
                <p>Personnalisation d'objets promotionnels pour votre marque.</p>
                <ul class="service-features">
                    <li>T-shirts personnalisés</li>
                    <li>Stylos gravés</li>
                    <li>Clés USB</li>
                    <li>Mugs</li>
                    <li>Sacs promotionnels</li>
                </ul>
                <div class="service-price">À partir de 20 000 FCFA</div>
            </div>

            <div class="service-item">
                <div class="service-icon">
                    <i class="fas fa-book"></i>
                </div>
                <h3>Reliure & Finitions</h3>
                <p>Finitions professionnelles pour vos documents importants.</p>
                <ul class="service-features">
                    <li>Reliure spirale</li>
                    <li>Reliure thermique</li>
                    <li>Plastification</li>
                    <li>Découpe laser</li>
                    <li>Dorure à chaud</li>
                </ul>
                <div class="service-price">À partir de 8 000 FCFA</div>
            </div>
        </div>
    </div>
</section>

<section class="printing-calculator" id="calculator">
    <div class="container">
        <div class="section-header">
            <h2>Calculateur de Prix</h2>
            <p>Obtenez une estimation instantanée pour votre projet d'impression</p>
        </div>
        
        <div class="calculator-container">
            <div class="calculator-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="print-type">Type d'impression</label>
                        <select id="print-type" name="print-type">
                            <option value="">Sélectionnez un type</option>
                            <option value="cartes-visite">Cartes de visite</option>
                            <option value="flyers">Flyers</option>
                            <option value="brochures">Brochures</option>
                            <option value="affiches">Affiches</option>
                            <option value="banners">Banners</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="quantity">Quantité</label>
                        <input type="number" id="quantity" name="quantity" min="1" value="100">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="format">Format</label>
                        <select id="format" name="format">
                            <option value="a4">A4 (21x29.7 cm)</option>
                            <option value="a5">A5 (14.8x21 cm)</option>
                            <option value="a6">A6 (10.5x14.8 cm)</option>
                            <option value="custom">Format personnalisé</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="paper">Type de papier</label>
                        <select id="paper" name="paper">
                            <option value="standard">Standard 80g</option>
                            <option value="premium">Premium 120g</option>
                            <option value="luxury">Luxe 250g</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="colors">Couleurs</label>
                        <select id="colors" name="colors">
                            <option value="bw">Noir et blanc</option>
                            <option value="color">Couleur</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="finishing">Finitions</label>
                        <select id="finishing" name="finishing">
                            <option value="none">Aucune</option>
                            <option value="lamination">Plastification</option>
                            <option value="varnish">Vernis</option>
                            <option value="embossing">Gaufrage</option>
                        </select>
                    </div>
                </div>
                
                <button type="button" class="btn btn-primary" id="calculate-price">
                    <i class="fas fa-calculator"></i> Calculer le Prix
                </button>
            </div>
            
            <div class="calculator-result">
                <div class="price-display">
                    <h3>Estimation</h3>
                    <div class="price-amount" id="estimated-price">0 FCFA</div>
                    <p class="price-note">Prix indicatif hors taxes</p>
                </div>
                
                <div class="price-breakdown" id="price-breakdown" style="display: none;">
                    <h4>Détail du calcul</h4>
                    <div class="breakdown-item">
                        <span>Impression de base :</span>
                        <span id="base-price">0 FCFA</span>
                    </div>
                    <div class="breakdown-item">
                        <span>Papier premium :</span>
                        <span id="paper-price">0 FCFA</span>
                    </div>
                    <div class="breakdown-item">
                        <span>Finitions :</span>
                        <span id="finishing-price">0 FCFA</span>
                    </div>
                    <div class="breakdown-total">
                        <span>Total :</span>
                        <span id="total-price">0 FCFA</span>
                    </div>
                </div>
                
                <a href="?page=devis&service=imprimerie" class="btn btn-outline">
                    <i class="fas fa-file-alt"></i> Demander un Devis Précis
                </a>
            </div>
        </div>
    </div>
</section>

<section class="printing-showcase">
    <div class="container">
        <div class="section-header">
            <h2>Nos Réalisations</h2>
            <p>Découvrez la qualité de nos impressions</p>
        </div>
        
        <div class="showcase-grid">
            <div class="showcase-item">
                <img src="assets/images/IMG-20191011-WA0012 (1).jpg" alt="Cartes de visite">
                <div class="showcase-overlay">
                    <h3>Cartes de Visite</h3>
                    <p>Impression premium avec finitions spéciales</p>
                </div>
            </div>
            
            <div class="showcase-item">
                <img src="assets/images/IMG-20191011-WA0029.jpg" alt="Brochures">
                <div class="showcase-overlay">
                    <h3>Brochures</h3>
                    <p>Catalogues et supports commerciaux</p>
                </div>
            </div>
            
            <div class="showcase-item">
                <img src="assets/images/IMG-20200312-WA0010.jpg" alt="Banners">
                <div class="showcase-overlay">
                    <h3>Banners</h3>
                    <p>Signalétique et communication extérieure</p>
                </div>
            </div>
            
            <div class="showcase-item">
                <img src="assets/images/Logo Vestidor-01.jpg" alt="Packaging">
                <div class="showcase-overlay">
                    <h3>Packaging</h3>
                    <p>Emballages personnalisés</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="quality-guarantee">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <div class="quality-content">
                    <h2>Garantie Qualité</h2>
                    <p>Nous nous engageons à vous livrer des impressions de la plus haute qualité, respectant vos délais et votre budget.</p>
                    
                    <div class="quality-features">
                        <div class="quality-item">
                            <i class="fas fa-award"></i>
                            <div>
                                <h4>Qualité Certifiée</h4>
                                <p>Contrôle qualité à chaque étape de production</p>
                            </div>
                        </div>
                        
                        <div class="quality-item">
                            <i class="fas fa-clock"></i>
                            <div>
                                <h4>Délais Respectés</h4>
                                <p>Livraison dans les temps convenus</p>
                            </div>
                        </div>
                        
                        <div class="quality-item">
                            <i class="fas fa-redo"></i>
                            <div>
                                <h4>Satisfaction Garantie</h4>
                                <p>Réimpression gratuite en cas de défaut</p>
                            </div>
                        </div>
                        
                        <div class="quality-item">
                            <i class="fas fa-leaf"></i>
                            <div>
                                <h4>Éco-Responsable</h4>
                                <p>Papiers certifiés FSC et encres écologiques</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="why-image" >
                    <img src="assets/images/modern-printing-press-produces-multi-colored-printouts-accurately-generated-by-ai.jpg" alt="Qualité d'impression" class="img-fluid">
                </div>
            </div>
        </div>
    </div>
</section>
