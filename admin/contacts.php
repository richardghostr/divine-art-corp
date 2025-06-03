<?php include 'header.php'; ?>

<?php include 'sidebar.php'; ?>

<!-- Contenu Principal -->
<main class="admin-main">
    <div class="section-header">
        <div class="section-title">
            <h2>Gestion des Contacts</h2>
            <p>Base de données clients et prospects</p>
        </div>
        <div class="section-actions">
            <button class="btn btn-outline" onclick="exportContacts()">
                <i class="fas fa-download"></i>
                Exporter
            </button>
            <button class="btn btn-outline" onclick="importContacts()">
                <i class="fas fa-upload"></i>
                Importer
            </button>
            <button class="btn btn-primary" onclick="openNewContactModal()">
                <i class="fas fa-plus"></i>
                Nouveau Contact
            </button>
        </div>
    </div>

    <!-- Statistiques Contacts -->
    <div class="contacts-stats">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Total Contacts</div>
                <div class="stat-value">247</div>
            </div>
            <div class="stat-trend positive">
                <i class="fas fa-arrow-up"></i>
                +12%
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-building"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Entreprises</div>
                <div class="stat-value">89</div>
            </div>
            <div class="stat-trend positive">
                <i class="fas fa-arrow-up"></i>
                +8%
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-user"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Particuliers</div>
                <div class="stat-value">158</div>
            </div>
            <div class="stat-trend positive">
                <i class="fas fa-arrow-up"></i>
                +15%
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Clients VIP</div>
                <div class="stat-value">23</div>
            </div>
            <div class="stat-trend positive">
                <i class="fas fa-arrow-up"></i>
                +5%
            </div>
        </div>
    </div>

    <!-- Filtres et Recherche -->
    <div class="filters-bar">
        <div class="filter-tabs">
            <button class="filter-tab active" data-type="all">Tous (247)</button>
            <button class="filter-tab" data-type="entreprise">Entreprises (89)</button>
            <button class="filter-tab" data-type="particulier">Particuliers (158)</button>
            <button class="filter-tab" data-type="prospect">Prospects (45)</button>
        </div>
        <div class="filter-actions">
            <select class="filter-select" id="statusFilter">
                <option value="all">Tous les statuts</option>
                <option value="actif">Actif</option>
                <option value="prospect">Prospect</option>
                <option value="inactif">Inactif</option>
                <option value="vip">VIP</option>
            </select>
            <select class="filter-select" id="serviceFilter">
                <option value="all">Tous les services</option>
                <option value="marketing">Marketing</option>
                <option value="graphique">Design Graphique</option>
                <option value="multimedia">Multimédia</option>
                <option value="imprimerie">Imprimerie</option>
            </select>
            <input type="text" placeholder="Rechercher un contact..." class="filter-search" id="contactSearch">
        </div>
    </div>

    <!-- Table des Contacts -->
    <div class="table-container">
        <table class="data-table" id="contactsTable">
            <thead>
                <tr>
                    <th>
                        <input type="checkbox" id="selectAll">
                    </th>
                    <th>Contact</th>
                    <th>Type</th>
                    <th>Email / Téléphone</th>
                    <th>Projets</th>
                    <th>Chiffre d'Affaires</th>
                    <th>Dernière Activité</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr data-type="entreprise" data-status="actif">
                    <td>
                        <input type="checkbox" class="contact-checkbox" value="1">
                    </td>
                    <td>
                        <div class="contact-cell">
                            <div class="contact-avatar">
                                <img src="/placeholder.svg?height=40&width=40" alt="TechStart">
                            </div>
                            <div class="contact-info">
                                <div class="contact-name">TechStart SARL</div>
                                <div class="contact-person">Jean Dupont - Directeur</div>
                                <div class="contact-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    Douala, Cameroun
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="type-badge entreprise">
                            <i class="fas fa-building"></i>
                            Entreprise
                        </span>
                    </td>
                    <td>
                        <div class="contact-details">
                            <div class="contact-email">
                                <i class="fas fa-envelope"></i>
                                contact@techstart.cm
                            </div>
                            <div class="contact-phone">
                                <i class="fas fa-phone"></i>
                                +237 6XX XXX XXX
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="projects-info">
                            <div class="projects-count">3 projets</div>
                            <div class="projects-services">Marketing, Design</div>
                        </div>
                    </td>
                    <td>
                        <div class="revenue-info">
                            <div class="revenue-amount">5,200€</div>
                            <div class="revenue-trend positive">+15%</div>
                        </div>
                    </td>
                    <td>
                        <div class="activity-info">
                            <div class="activity-date">Il y a 2 jours</div>
                            <div class="activity-action">Devis envoyé</div>
                        </div>
                    </td>
                    <td>
                        <span class="status-badge actif">Actif</span>
                    </td>
                    <td>
                        <div class="table-actions">
                            <button class="action-btn" onclick="viewContact(1)" title="Voir">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="action-btn" onclick="editContact(1)" title="Modifier">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-btn" onclick="emailContact(1)" title="Email">
                                <i class="fas fa-envelope"></i>
                            </button>
                            <button class="action-btn" onclick="callContact(1)" title="Appeler">
                                <i class="fas fa-phone"></i>
                            </button>
                            <button class="action-btn danger" onclick="deleteContact(1)" title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                <tr data-type="entreprise" data-status="prospect">
                    <td>
                        <input type="checkbox" class="contact-checkbox" value="2">
                    </td>
                    <td>
                        <div class="contact-cell">
                            <div class="contact-avatar">
                                <img src="/placeholder.svg?height=40&width=40" alt="Restaurant">
                            </div>
                            <div class="contact-info">
                                <div class="contact-name">Restaurant Saveurs</div>
                                <div class="contact-person">Marie Kouam - Propriétaire</div>
                                <div class="contact-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    Yaoundé, Cameroun
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="type-badge entreprise">
                            <i class="fas fa-utensils"></i>
                            Restaurant
                        </span>
                    </td>
                    <td>
                        <div class="contact-details">
                            <div class="contact-email">
                                <i class="fas fa-envelope"></i>
                                info@saveurs.cm
                            </div>
                            <div class="contact-phone">
                                <i class="fas fa-phone"></i>
                                +237 6XX XXX XXX
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="projects-info">
                            <div class="projects-count">1 projet</div>
                            <div class="projects-services">Site Web</div>
                        </div>
                    </td>
                    <td>
                        <div class="revenue-info">
                            <div class="revenue-amount">3,100€</div>
                            <div class="revenue-trend positive">Nouveau</div>
                        </div>
                    </td>
                    <td>
                        <div class="activity-info">
                            <div class="activity-date">Il y a 1 semaine</div>
                            <div class="activity-action">Premier contact</div>
                        </div>
                    </td>
                    <td>
                        <span class="status-badge prospect">Prospect</span>
                    </td>
                    <td>
                        <div class="table-actions">
                            <button class="action-btn" onclick="viewContact(2)" title="Voir">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="action-btn" onclick="editContact(2)" title="Modifier">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-btn" onclick="emailContact(2)" title="Email">
                                <i class="fas fa-envelope"></i>
                            </button>
                            <button class="action-btn" onclick="callContact(2)" title="Appeler">
                                <i class="fas fa-phone"></i>
                            </button>
                            <button class="action-btn danger" onclick="deleteContact(2)" title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                <tr data-type="particulier" data-status="vip">
                    <td>
                        <input type="checkbox" class="contact-checkbox" value="3">
                    </td>
                    <td>
                        <div class="contact-cell">
                            <div class="contact-avatar">
                                <img src="/placeholder.svg?height=40&width=40" alt="Client">
                            </div>
                            <div class="contact-&width=40" alt="Client">
                            </div>
                            <div class="contact-info">
                                <div class="contact-name">Dr. Paul Mbarga</div>
                                <div class="contact-person">Médecin - Clinique Privée</div>
                                <div class="contact-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    Douala, Cameroun
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="type-badge particulier">
                            <i class="fas fa-user-md"></i>
                            Particulier
                        </span>
                    </td>
                    <td>
                        <div class="contact-details">
                            <div class="contact-email">
                                <i class="fas fa-envelope"></i>
                                dr.mbarga@email.cm
                            </div>
                            <div class="contact-phone">
                                <i class="fas fa-phone"></i>
                                +237 6XX XXX XXX
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="projects-info">
                            <div class="projects-count">5 projets</div>
                            <div class="projects-services">Tous services</div>
                        </div>
                    </td>
                    <td>
                        <div class="revenue-info">
                            <div class="revenue-amount">12,800€</div>
                            <div class="revenue-trend positive">+25%</div>
                        </div>
                    </td>
                    <td>
                        <div class="activity-info">
                            <div class="activity-date">Hier</div>
                            <div class="activity-action">Projet livré</div>
                        </div>
                    </td>
                    <td>
                        <span class="status-badge vip">VIP</span>
                    </td>
                    <td>
                        <div class="table-actions">
                            <button class="action-btn" onclick="viewContact(3)" title="Voir">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="action-btn" onclick="editContact(3)" title="Modifier">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-btn" onclick="emailContact(3)" title="Email">
                                <i class="fas fa-envelope"></i>
                            </button>
                            <button class="action-btn" onclick="callContact(3)" title="Appeler">
                                <i class="fas fa-phone"></i>
                            </button>
                            <button class="action-btn" onclick="createProject(3)" title="Nouveau Projet">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                <tr data-type="entreprise" data-status="inactif">
                    <td>
                        <input type="checkbox" class="contact-checkbox" value="4">
                    </td>
                    <td>
                        <div class="contact-cell">
                            <div class="contact-avatar">
                                <img src="/placeholder.svg?height=40&width=40" alt="EcoTech">
                            </div>
                            <div class="contact-info">
                                <div class="contact-name">EcoTech Solutions</div>
                                <div class="contact-person">Samuel Nkomo - CEO</div>
                                <div class="contact-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    Bafoussam, Cameroun
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="type-badge entreprise">
                            <i class="fas fa-leaf"></i>
                            Startup
                        </span>
                    </td>
                    <td>
                        <div class="contact-details">
                            <div class="contact-email">
                                <i class="fas fa-envelope"></i>
                                contact@ecotech.cm
                            </div>
                            <div class="contact-phone">
                                <i class="fas fa-phone"></i>
                                +237 6XX XXX XXX
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="projects-info">
                            <div class="projects-count">2 projets</div>
                            <div class="projects-services">Multimédia</div>
                        </div>
                    </td>
                    <td>
                        <div class="revenue-info">
                            <div class="revenue-amount">4,500€</div>
                            <div class="revenue-trend neutral">Stable</div>
                        </div>
                    </td>
                    <td>
                        <div class="activity-info">
                            <div class="activity-date">Il y a 3 mois</div>
                            <div class="activity-action">Projet terminé</div>
                        </div>
                    </td>
                    <td>
                        <span class="status-badge inactif">Inactif</span>
                    </td>
                    <td>
                        <div class="table-actions">
                            <button class="action-btn" onclick="viewContact(4)" title="Voir">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="action-btn" onclick="editContact(4)" title="Modifier">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-btn" onclick="reactivateContact(4)" title="Réactiver">
                                <i class="fas fa-redo"></i>
                            </button>
                            <button class="action-btn danger" onclick="deleteContact(4)" title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Actions en lot -->
    <div class="bulk-actions" id="bulkActions" style="display: none;">
        <div class="bulk-actions-content">
            <span class="selected-count">0 contact(s) sélectionné(s)</span>
            <div class="bulk-buttons">
                <button class="btn btn-outline btn-sm" onclick="bulkEmail()">
                    <i class="fas fa-envelope"></i>
                    Envoyer Email
                </button>
                <button class="btn btn-outline btn-sm" onclick="bulkExport()">
                    <i class="fas fa-download"></i>
                    Exporter
                </button>
                <button class="btn btn-outline btn-sm" onclick="bulkTag()">
                    <i class="fas fa-tag"></i>
                    Ajouter Tag
                </button>
                <button class="btn btn-danger btn-sm" onclick="bulkDelete()">
                    <i class="fas fa-trash"></i>
                    Supprimer
                </button>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <div class="pagination-container">
        <div class="pagination-info">
            Affichage de 1 à 4 sur 247 contacts
        </div>
        <div class="pagination">
            <button class="pagination-btn" disabled>
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="pagination-btn active">1</button>
            <button class="pagination-btn">2</button>
            <button class="pagination-btn">3</button>
            <button class="pagination-btn">...</button>
            <button class="pagination-btn">62</button>
            <button class="pagination-btn">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
</main>

</div> <!-- Fin admin-layout -->

<!-- Modal Nouveau Contact -->
<div id="newContactModal" class="modal" style="display: none;">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3>Nouveau Contact</h3>
            <button class="modal-close" onclick="closeModal('newContactModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="newContactForm">
                <div class="form-tabs">
                    <button type="button" class="tab-btn active" data-tab="general">Informations Générales</button>
                    <button type="button" class="tab-btn" data-tab="contact">Contact</button>
                    <button type="button" class="tab-btn" data-tab="business">Business</button>
                    <button type="button" class="tab-btn" data-tab="notes">Notes</button>
                </div>

                <!-- Onglet Général -->
                <div class="tab-content active" data-tab="general">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="contactType">Type de contact</label>
                            <select id="contactType" class="form-control" required>
                                <option value="">Sélectionner le type</option>
                                <option value="entreprise">Entreprise</option>
                                <option value="particulier">Particulier</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="contactStatus">Statut</label>
                            <select id="contactStatus" class="form-control" required>
                                <option value="prospect">Prospect</option>
                                <option value="actif">Client Actif</option>
                                <option value="vip">Client VIP</option>
                                <option value="inactif">Inactif</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="contactName">Nom de l'entreprise / Nom complet</label>
                        <input type="text" id="contactName" class="form-control" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="contactPerson">Personne de contact</label>
                            <input type="text" id="contactPerson" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="contactPosition">Poste / Fonction</label>
                            <input type="text" id="contactPosition" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="contactSector">Secteur d'activité</label>
                        <select id="contactSector" class="form-control">
                            <option value="">Sélectionner un secteur</option>
                            <option value="technologie">Technologie</option>
                            <option value="restauration">Restauration</option>
                            <option value="sante">Santé</option>
                            <option value="education">Éducation</option>
                            <option value="commerce">Commerce</option>
                            <option value="industrie">Industrie</option>
                            <option value="services">Services</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>
                </div>

                <!-- Onglet Contact -->
                <div class="tab-content" data-tab="contact">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="contactEmail">Email principal</label>
                            <input type="email" id="contactEmail" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="contactEmailSecondary">Email secondaire</label>
                            <input type="email" id="contactEmailSecondary" class="form-control">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="contactPhone">Téléphone principal</label>
                            <input type="tel" id="contactPhone" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="contactPhoneSecondary">Téléphone secondaire</label>
                            <input type="tel" id="contactPhoneSecondary" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="contactAddress">Adresse complète</label>
                        <textarea id="contactAddress" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="contactCity">Ville</label>
                            <input type="text" id="contactCity" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="contactCountry">Pays</label>
                            <select id="contactCountry" class="form-control">
                                <option value="CM">Cameroun</option>
                                <option value="FR">France</option>
                                <option value="CA">Canada</option>
                                <option value="SN">Sénégal</option>
                                <option value="CI">Côte d'Ivoire</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="contactWebsite">Site web</label>
                        <input type="url" id="contactWebsite" class="form-control" placeholder="https://">
                    </div>
                </div>

                <!-- Onglet Business -->
                <div class="tab-content" data-tab="business">
                    <div class="form-group">
                        <label for="contactServices">Services d'intérêt</label>
                        <div class="checkbox-group">
                            <label class="checkbox-item">
                                <input type="checkbox" value="marketing">
                                <span>Marketing Digital</span>
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" value="graphique">
                                <span>Design Graphique</span>
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" value="multimedia">
                                <span>Multimédia</span>
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" value="imprimerie">
                                <span>Imprimerie</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="contactBudget">Budget estimé</label>
                            <select id="contactBudget" class="form-control">
                                <option value="">Non spécifié</option>
                                <option value="500-1000">500€ - 1,000€</option>
                                <option value="1000-5000">1,000€ - 5,000€</option>
                                <option value="5000-10000">5,000€ - 10,000€</option>
                                <option value="10000+">10,000€+</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="contactSource">Source de contact</label>
                            <select id="contactSource" class="form-control">
                                <option value="">Sélectionner la source</option>
                                <option value="site-web">Site Web</option>
                                <option value="reseaux-sociaux">Réseaux Sociaux</option>
                                <option value="recommandation">Recommandation</option>
                                <option value="publicite">Publicité</option>
                                <option value="evenement">Événement</option>
                                <option value="autre">Autre</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="contactTags">Tags</label>
                        <input type="text" id="contactTags" class="form-control" placeholder="Séparer par des virgules">
                        <small class="form-help">Ex: urgent, gros client, design moderne</small>
                    </div>
                </div>

                <!-- Onglet Notes -->
                <div class="tab-content" data-tab="notes">
                    <div class="form-group">
                        <label for="contactNotes">Notes internes</label>
                        <textarea id="contactNotes" class="form-control" rows="6" placeholder="Notes privées sur le contact, historique des interactions, préférences, etc."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="contactObjectives">Objectifs commerciaux</label>
                        <textarea id="contactObjectives" class="form-control" rows="4" placeholder="Objectifs à atteindre avec ce contact, stratégie de vente, etc."></textarea>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('newContactModal')">Annuler</button>
            <button type="button" class="btn btn-secondary">Sauvegarder Brouillon</button>
            <button type="submit" form="newContactForm" class="btn btn-primary">Créer le Contact</button>
        </div>
    </div>
</div>

<!-- Modal Détails Contact -->
<div id="contactDetailsModal" class="modal" style="display: none;">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3 id="contactDetailsTitle">Détails du Contact</h3>
            <button class="modal-close" onclick="closeModal('contactDetailsModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div id="contactDetailsContent">
                <!-- Contenu dynamique -->
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="../assets/js/admin.js"></script>
<script>
// Gestion des filtres
document.querySelectorAll('.filter-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        
        const type = this.getAttribute('data-type');
        filterContactsByType(type);
    });
});

function filterContactsByType(type) {
    const rows = document.querySelectorAll('#contactsTable tbody tr');
    
    rows.forEach(row => {
        if (type === 'all' || row.getAttribute('data-type') === type) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Recherche
document.getElementById('contactSearch').addEventListener('input', function() {
    const query = this.value.toLowerCase();
    const rows = document.querySelectorAll('#contactsTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(query) ? '' : 'none';
    });
});

// Sélection multiple
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.contact-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    updateBulkActions();
});

document.querySelectorAll('.contact-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateBulkActions);
});

function updateBulkActions() {
    const selected = document.querySelectorAll('.contact-checkbox:checked');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.querySelector('.selected-count');
    
    if (selected.length > 0) {
        bulkActions.style.display = 'flex';
        selectedCount.textContent = `${selected.length} contact(s) sélectionné(s)`;
    } else {
        bulkActions.style.display = 'none';
    }
}

// Actions sur les contacts
function viewContact(id) {
    // Simulation des données du contact
    const contactData = {
        1: {
            name: 'TechStart SARL',
            person: 'Jean Dupont',
            position: 'Directeur',
            email: 'contact@techstart.cm',
            phone: '+237 6XX XXX XXX',
            projects: 3,
            revenue: '5,200€',
            status: 'Actif'
        }
    };
    
    const contact = contactData[id];
    if (contact) {
        document.getElementById('contactDetailsTitle').textContent = contact.name;
        document.getElementById('contactDetailsContent').innerHTML = `
            <div class="contact-details-grid">
                <div class="detail-section">
                    <h4>Informations Générales</h4>
                    <p><strong>Personne de contact:</strong> ${contact.person}</p>
                    <p><strong>Poste:</strong> ${contact.position}</p>
                    <p><strong>Email:</strong> ${contact.email}</p>
                    <p><strong>Téléphone:</strong> ${contact.phone}</p>
                </div>
                <div class="detail-section">
                    <h4>Activité Commerciale</h4>
                    <p><strong>Projets:</strong> ${contact.projects}</p>
                    <p><strong>Chiffre d'affaires:</strong> ${contact.revenue}</p>
                    <p><strong>Statut:</strong> ${contact.status}</p>
                </div>
            </div>
        `;
        document.getElementById('contactDetailsModal').style.display = 'flex';
    }
}

function editContact(id) {
    console.log('Éditer contact:', id);
    // Ouvrir le modal d'édition avec les données pré-remplies
}

function emailContact(id) {
    console.log('Envoyer email à:', id);
    // Ouvrir le client email ou modal de composition
}

function callContact(id) {
    console.log('Appeler contact:', id);
    // Intégration avec système de téléphonie
}

function deleteContact(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce contact ?')) {
        console.log('Supprimer contact:', id);
        // Suppression du contact
    }
}

function reactivateContact(id) {
    if (confirm('Réactiver ce contact ?')) {
        console.log('Réactiver contact:', id);
        // Réactivation du contact
    }
}

function createProject(id) {
    console.log('Créer projet pour:', id);
    // Redirection vers création de projet
}

// Actions en lot
function bulkEmail() {
    const selected = document.querySelectorAll('.contact-checkbox:checked');
    console.log('Email en lot pour:', selected.length, 'contacts');
}

function bulkExport() {
    const selected = document.querySelectorAll('.contact-checkbox:checked');
    console.log('Export de:', selected.length, 'contacts');
}

function bulkTag() {
    const selected = document.querySelectorAll('.contact-checkbox:checked');
    const tag = prompt('Entrez le tag à ajouter:');
    if (tag) {
        console.log('Ajouter tag "' + tag + '" à:', selected.length, 'contacts');
    }
}

function bulkDelete() {
    const selected = document.querySelectorAll('.contact-checkbox:checked');
    if (confirm(`Supprimer ${selected.length} contact(s) sélectionné(s) ?`)) {
        console.log('Suppression en lot de:', selected.length, 'contacts');
    }
}

// Modal nouveau contact
function openNewContactModal() {
    document.getElementById('newContactModal').style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Gestion des onglets dans le modal
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const tabName = this.getAttribute('data-tab');
        const modal = this.closest('.modal');
        
        // Désactiver tous les onglets et contenus
        modal.querySelectorAll('.tab-btn').forEach(tab => tab.classList.remove('active'));
        modal.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        
        // Activer l'onglet et le contenu sélectionnés
        this.classList.add('active');
        modal.querySelector(`.tab-content[data-tab="${tabName}"]`).classList.add('active');
    });
});

// Import/Export
function exportContacts() {
    console.log('Export des contacts');
    // Génération du fichier d'export
}

function importContacts() {
    console.log('Import des contacts');
    // Ouverture du sélecteur de fichier
}

// Soumission du formulaire
document.getElementById('newContactForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    console.log('Création du contact:', Object.fromEntries(formData));
    
    // Simulation de la création
    setTimeout(() => {
        closeModal('newContactModal');
        // Actualiser la liste des contacts
        location.reload();
    }, 1000);
});
</script>

<?php include '../includes/footer.php'; ?>
