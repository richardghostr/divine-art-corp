<?php include 'header.php'; ?>

<?php include 'sidebar.php'; ?>

<!-- Contenu Principal -->
<main class="admin-main">
    <div class="section-header">
        <div class="section-title">
            <h2>Paramètres</h2>
            <p>Configuration de la plateforme et préférences</p>
        </div>
        <div class="section-actions">
            <button class="btn btn-outline" onclick="resetSettings()">
                <i class="fas fa-undo"></i>
                Réinitialiser
            </button>
            <button class="btn btn-primary" onclick="saveAllSettings()">
                <i class="fas fa-save"></i>
                Sauvegarder Tout
            </button>
        </div>
    </div>

    <!-- Navigation des paramètres -->
    <div class="settings-nav">
        <button class="settings-nav-btn active" data-section="profile">
            <i class="fas fa-user"></i>
            Profil
        </button>
        <button class="settings-nav-btn" data-section="company">
            <i class="fas fa-building"></i>
            Entreprise
        </button>
        <button class="settings-nav-btn" data-section="security">
            <i class="fas fa-shield-alt"></i>
            Sécurité
        </button>
        <button class="settings-nav-btn" data-section="notifications">
            <i class="fas fa-bell"></i>
            Notifications
        </button>
        <button class="settings-nav-btn" data-section="integrations">
            <i class="fas fa-plug"></i>
            Intégrations
        </button>
        <button class="settings-nav-btn" data-section="system">
            <i class="fas fa-cogs"></i>
            Système
        </button>
    </div>

    <!-- Section Profil -->
    <div class="settings-section active" data-section="profile">
        <div class="settings-card">
            <div class="settings-header">
                <h3>Profil Administrateur</h3>
                <p>Gérez vos informations personnelles</p>
            </div>
            <div class="settings-content">
                <form id="profileForm">
                    <div class="profile-avatar-section">
                        <div class="current-avatar">
                            <img src="/placeholder.svg?height=80&width=80" alt="Avatar" id="avatarPreview">
                        </div>
                        <div class="avatar-actions">
                            <input type="file" id="avatarInput" accept="image/*" style="display: none;">
                            <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('avatarInput').click()">
                                <i class="fas fa-camera"></i>
                                Changer Photo
                            </button>
                            <button type="button" class="btn btn-outline btn-sm" onclick="removeAvatar()">
                                <i class="fas fa-trash"></i>
                                Supprimer
                            </button>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstName">Prénom</label>
                            <input type="text" id="firstName" class="form-control" value="Admin" required>
                        </div>
                        <div class="form-group">
                            <label for="lastName">Nom</label>
                            <input type="text" id="lastName" class="form-control" value="DAC" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Adresse email</label>
                        <input type="email" id="email" class="form-control" value="admin@divineartcorp.cm" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Téléphone</label>
                            <input type="tel" id="phone" class="form-control" value="+237 6XX XXX XXX">
                        </div>
                        <div class="form-group">
                            <label for="timezone">Fuseau horaire</label>
                            <select id="timezone" class="form-control">
                                <option value="Africa/Douala" selected>Afrique/Douala (GMT+1)</option>
                                <option value="Europe/Paris">Europe/Paris (GMT+1)</option>
                                <option value="America/Montreal">Amérique/Montréal (GMT-5)</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="bio">Biographie</label>
                        <textarea id="bio" class="form-control" rows="4" placeholder="Parlez-nous de vous...">Administrateur principal de Divine Art Corporation, spécialisé dans la gestion de projets créatifs et le développement commercial.</textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Sauvegarder le Profil
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Section Entreprise -->
    <div class="settings-section" data-section="company">
        <div class="settings-card">
            <div class="settings-header">
                <h3>Informations de l'Entreprise</h3>
                <p>Configurez les détails de Divine Art Corporation</p>
            </div>
            <div class="settings-content">
                <form id="companyForm">
                    <div class="company-logo-section">
                        <div class="current-logo">
                            <img src="/placeholder.svg?height=100&width=100" alt="Logo" id="logoPreview">
                        </div>
                        <div class="logo-actions">
                            <input type="file" id="logoInput" accept="image/*" style="display: none;">
                            <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('logoInput').click()">
                                <i class="fas fa-upload"></i>
                                Changer Logo
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="companyName">Nom de l'entreprise</label>
                        <input type="text" id="companyName" class="form-control" value="Divine Art Corporation" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="companyEmail">Email principal</label>
                            <input type="email" id="companyEmail" class="form-control" value="contact@divineartcorp.cm" required>
                        </div>
                        <div class="form-group">
                            <label for="companyPhone">Téléphone principal</label>
                            <input type="tel" id="companyPhone" class="form-control" value="+237 6XX XXX XXX" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="companyAddress">Adresse complète</label>
                        <textarea id="companyAddress" class="form-control" rows="3" required>Douala, Cameroun</textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="companyWebsite">Site web</label>
                            <input type="url" id="companyWebsite" class="form-control" value="https://divineartcorp.cm">
                        </div>
                        <div class="form-group">
                            <label for="companySiret">SIRET / Numéro d'entreprise</label>
                            <input type="text" id="companySiret" class="form-control" placeholder="123456789">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="companyDescription">Description de l'entreprise</label>
                        <textarea id="companyDescription" class="form-control" rows="4">Divine Art Corporation est une agence créative spécialisée dans le marketing digital, le design graphique, la production multimédia et l'imprimerie. Nous accompagnons nos clients dans la réalisation de leurs projets créatifs avec passion et expertise.</textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Sauvegarder les Informations
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Section Sécurité -->
    <div class="settings-section" data-section="security">
        <div class="settings-grid">
            <!-- Changement de mot de passe -->
            <div class="settings-card">
                <div class="settings-header">
                    <h3>Mot de Passe</h3>
                    <p>Modifiez votre mot de passe de connexion</p>
                </div>
                <div class="settings-content">
                    <form id="passwordForm">
                        <div class="form-group">
                            <label for="currentPassword">Mot de passe actuel</label>
                            <input type="password" id="currentPassword" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="newPassword">Nouveau mot de passe</label>
                            <input type="password" id="newPassword" class="form-control" required>
                            <div class="password-strength" id="passwordStrength"></div>
                        </div>
                        <div class="form-group">
                            <label for="confirmPassword">Confirmer le mot de passe</label>
                            <input type="password" id="confirmPassword" class="form-control" required>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-key"></i>
                                Changer le Mot de Passe
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Authentification à deux facteurs -->
            <div class="settings-card">
                <div class="settings-header">
                    <h3>Authentification à Deux Facteurs</h3>
                    <p>Renforcez la sécurité de votre compte</p>
                </div>
                <div class="settings-content">
                    <div class="security-option">
                        <div class="option-info">
                            <h4>Authentification par SMS</h4>
                            <p>Recevez un code par SMS lors de la connexion</p>
                        </div>
                        <div class="option-toggle">
                            <label class="switch">
                                <input type="checkbox" id="smsAuth">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    <div class="security-option">
                        <div class="option-info">
                            <h4>Application d'authentification</h4>
                            <p>Utilisez Google Authenticator ou similaire</p>
                        </div>
                        <div class="option-toggle">
                            <label class="switch">
                                <input type="checkbox" id="appAuth">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    <div class="security-option">
                        <div class="option-info">
                            <h4>Codes de récupération</h4>
                            <p>Générez des codes de sauvegarde</p>
                        </div>
                        <div class="option-action">
                            <button class="btn btn-outline btn-sm" onclick="generateRecoveryCodes()">
                                <i class="fas fa-download"></i>
                                Générer
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sessions actives -->
            <div class="settings-card">
                <div class="settings-header">
                    <h3>Sessions Actives</h3>
                    <p>Gérez vos connexions actives</p>
                </div>
                <div class="settings-content">
                    <div class="session-list">
                        <div class="session-item current">
                            <div class="session-info">
                                <div class="session-device">
                                    <i class="fas fa-desktop"></i>
                                    <span>Ordinateur de bureau - Chrome</span>
                                </div>
                                <div class="session-details">
                                    <span class="session-location">Douala, Cameroun</span>
                                    <span class="session-time">Session actuelle</span>
                                </div>
                            </div>
                            <div class="session-status">
                                <span class="status-badge active">Actuelle</span>
                            </div>
                        </div>

                        <div class="session-item">
                            <div class="session-info">
                                <div class="session-device">
                                    <i class="fas fa-mobile-alt"></i>
                                    <span>iPhone - Safari</span>
                                </div>
                                <div class="session-details">
                                    <span class="session-location">Yaoundé, Cameroun</span>
                                    <span class="session-time">Il y a 2 heures</span>
                                </div>
                            </div>
                            <div class="session-actions">
                                <button class="btn btn-outline btn-sm" onclick="revokeSession('mobile-1')">
                                    <i class="fas fa-sign-out-alt"></i>
                                    Déconnecter
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button class="btn btn-danger" onclick="revokeAllSessions()">
                            <i class="fas fa-sign-out-alt"></i>
                            Déconnecter Toutes les Sessions
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Notifications -->
    <div class="settings-section" data-section="notifications">
        <div class="settings-card">
            <div class="settings-header">
                <h3>Préférences de Notifications</h3>
                <p>Configurez comment vous souhaitez être notifié</p>
            </div>
            <div class="settings-content">
                <div class="notification-categories">
                    <!-- Notifications Email -->
                    <div class="notification-category">
                        <h4>Notifications par Email</h4>
                        
                        <div class="notification-option">
                            <div class="option-info">
                                <h5>Nouveaux devis</h5>
                                <p>Recevoir un email lors de nouvelles demandes de devis</p>
                            </div>
                            <div class="option-toggle">
                                <label class="switch">
                                    <input type="checkbox" id="emailNewQuotes" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="notification-option">
                            <div class="option-info">
                                <h5>Projets terminés</h5>
                                <p>Notification quand un projet est marqué comme terminé</p>
                            </div>
                            <div class="option-toggle">
                                <label class="switch">
                                    <input type="checkbox" id="emailProjectCompleted" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="notification-option">
                            <div class="option-info">
                                <h5>Nouveaux contacts</h5>
                                <p>Email lors de l'ajout de nouveaux contacts</p>
                            </div>
                            <div class="option-toggle">
                                <label class="switch">
                                    <input type="checkbox" id="emailNewContacts">
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="notification-option">
                            <div class="option-info">
                                <h5>Rappels d'échéances</h5>
                                <p>Rappels automatiques pour les projets en retard</p>
                            </div>
                            <div class="option-toggle">
                                <label class="switch">
                                    <input type="checkbox" id="emailDeadlineReminders" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Notifications Push -->
                    <div class="notification-category">
                        <h4>Notifications Push</h4>
                        
                        <div class="notification-option">
                            <div class="option-info">
                                <h5>Activité en temps réel</h5>
                                <p>Notifications instantanées dans l'interface</p>
                            </div>
                            <div class="option-toggle">
                                <label class="switch">
                                    <input type="checkbox" id="pushRealtime" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="notification-option">
                            <div class="option-info">
                                <h5>Messages urgents</h5>
                                <p>Notifications pour les messages marqués comme urgents</p>
                            </div>
                            <div class="option-toggle">
                                <label class="switch">
                                    <input type="checkbox" id="pushUrgent" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Rapports -->
                    <div class="notification-category">
                        <h4>Rapports Automatiques</h4>
                        
                        <div class="notification-option">
                            <div class="option-info">
                                <h5>Rapport hebdomadaire</h5>
                                <p>Résumé des activités de la semaine</p>
                            </div>
                            <div class="option-controls">
                                <label class="switch">
                                    <input type="checkbox" id="weeklyReport" checked>
                                    <span class="slider"></span>
                                </label>
                                <select class="form-control form-control-sm" id="weeklyReportDay">
                                    <option value="monday" selected>Lundi</option>
                                    <option value="friday">Vendredi</option>
                                    <option value="sunday">Dimanche</option>
                                </select>
                            </div>
                        </div>

                        <div class="notification-option">
                            <div class="option-info">
                                <h5>Rapport mensuel</h5>
                                <p>Bilan financier et statistiques mensuelles</p>
                            </div>
                            <div class="option-controls">
                                <label class="switch">
                                    <input type="checkbox" id="monthlyReport" checked>
                                    <span class="slider"></span>
                                </label>
                                <select class="form-control form-control-sm" id="monthlyReportDate">
                                    <option value="1" selected>1er du mois</option>
                                    <option value="15">15 du mois</option>
                                    <option value="last">Dernier jour</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-primary" onclick="saveNotificationSettings()">
                        <i class="fas fa-save"></i>
                        Sauvegarder les Préférences
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Intégrations -->
    <div class="settings-section" data-section="integrations">
        <div class="settings-grid">
            <!-- Intégrations disponibles -->
            <div class="settings-card">
                <div class="settings-header">
                    <h3>Intégrations Disponibles</h3>
                    <p>Connectez vos outils préférés</p>
                </div>
                <div class="settings-content">
                    <div class="integration-list">
                        <div class="integration-item">
                            <div class="integration-info">
                                <div class="integration-icon">
                                    <i class="fab fa-google"></i>
                                </div>
                                <div class="integration-details">
                                    <h4>Google Workspace</h4>
                                    <p>Synchronisation avec Gmail, Drive et Calendar</p>
                                </div>
                            </div>
                            <div class="integration-status">
                                <span class="status-badge connected">Connecté</span>
                                <button class="btn btn-outline btn-sm" onclick="configureIntegration('google')">
                                    <i class="fas fa-cog"></i>
                                    Configurer
                                </button>
                            </div>
                        </div>

                        <div class="integration-item">
                            <div class="integration-info">
                                <div class="integration-icon">
                                    <i class="fab fa-slack"></i>
                                </div>
                                <div class="integration-details">
                                    <h4>Slack</h4>
                                    <p>Notifications et collaboration d'équipe</p>
                                </div>
                            </div>
                            <div class="integration-status">
                                <span class="status-badge disconnected">Non connecté</span>
                                <button class="btn btn-primary btn-sm" onclick="connectIntegration('slack')">
                                    <i class="fas fa-plug"></i>
                                    Connecter
                                </button>
                            </div>
                        </div>

                        <div class="integration-item">
                            <div class="integration-info">
                                <div class="integration-icon">
                                    <i class="fab fa-dropbox"></i>
                                </div>
                                <div class="integration-details">
                                    <h4>Dropbox</h4>
                                    <p>Stockage et partage de fichiers</p>
                                </div>
                            </div>
                            <div class="integration-status">
                                <span class="status-badge disconnected">Non connecté</span>
                                <button class="btn btn-primary btn-sm" onclick="connectIntegration('dropbox')">
                                    <i class="fas fa-plug"></i>
                                    Connecter
                                </button>
                            </div>
                        </div>

                        <div class="integration-item">
                            <div class="integration-info">
                                <div class="integration-icon">
                                    <i class="fab fa-stripe"></i>
                                </div>
                                <div class="integration-details">
                                    <h4>Stripe</h4>
                                    <p>Paiements en ligne et facturation</p>
                                </div>
                            </div>
                            <div class="integration-status">
                                <span class="status-badge connected">Connecté</span>
                                <button class="btn btn-outline btn-sm" onclick="configureIntegration('stripe')">
                                    <i class="fas fa-cog"></i>
                                    Configurer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- API et Webhooks -->
            <div class="settings-card">
                <div class="settings-header">
                    <h3>API et Webhooks</h3>
                    <p>Intégrations personnalisées</p>
                </div>
                <div class="settings-content">
                    <div class="api-section">
                        <h4>Clés API</h4>
                        <div class="api-key-item">
                            <div class="api-key-info">
                                <label>Clé API principale</label>
                                <input type="text" class="form-control" value="dac_live_xxxxxxxxxxxxxxxx" readonly>
                            </div>
                            <div class="api-key-actions">
                                <button class="btn btn-outline btn-sm" onclick="regenerateApiKey()">
                                    <i class="fas fa-sync"></i>
                                    Régénérer
                                </button>
                                <button class="btn btn-outline btn-sm" onclick="copyApiKey()">
                                    <i class="fas fa-copy"></i>
                                    Copier
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="webhook-section">
                        <h4>Webhooks</h4>
                        <div class="webhook-list">
                            <div class="webhook-item">
                                <div class="webhook-info">
                                    <div class="webhook-url">https://example.com/webhook/projects</div>
                                    <div class="webhook-events">Événements: project.created, project.completed</div>
                                </div>
                                <div class="webhook-actions">
                                    <button class="btn btn-outline btn-sm" onclick="editWebhook(1)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteWebhook(1)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button class="btn btn-primary btn-sm" onclick="addWebhook()">
                            <i class="fas fa-plus"></i>
                            Ajouter Webhook
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Système -->
    <div class="settings-section" data-section="system">
        <div class="settings-grid">
            <!-- Sauvegarde -->
            <div class="settings-card">
                <div class="settings-header">
                    <h3>Sauvegarde et Restauration</h3>
                    <p>Protégez vos données importantes</p>
                </div>
                <div class="settings-content">
                    <div class="backup-info">
                        <div class="backup-status">
                            <div class="status-item">
                                <span class="status-label">Dernière sauvegarde:</span>
                                <span class="status-value">Aujourd'hui à 03:00</span>
                            </div>
                            <div class="status-item">
                                <span class="status-label">Taille de la sauvegarde:</span>
                                <span class="status-value">2.3 GB</span>
                            </div>
                            <div class="status-item">
                                <span class="status-label">Statut:</span>
                                <span class="status-badge success">Réussie</span>
                            </div>
                        </div>
                    </div>

                    <div class="backup-settings">
                        <div class="setting-option">
                            <div class="option-info">
                                <h4>Sauvegarde automatique</h4>
                                <p>Sauvegarde quotidienne à 3h du matin</p>
                            </div>
                            <div class="option-toggle">
                                <label class="switch">
                                    <input type="checkbox" id="autoBackup" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="setting-option">
                            <div class="option-info">
                                <h4>Rétention des sauvegardes</h4>
                                <p>Conserver les sauvegardes pendant</p>
                            </div>
                            <div class="option-control">
                                <select class="form-control">
                                    <option value="7">7 jours</option>
                                    <option value="30" selected>30 jours</option>
                                    <option value="90">90 jours</option>
                                    <option value="365">1 an</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="backup-actions">
                        <button class="btn btn-primary" onclick="createBackup()">
                            <i class="fas fa-download"></i>
                            Créer Sauvegarde Maintenant
                        </button>
                        <button class="btn btn-outline" onclick="downloadBackup()">
                            <i class="fas fa-cloud-download-alt"></i>
                            Télécharger Dernière Sauvegarde
                        </button>
                    </div>
                </div>
            </div>

            <!-- Maintenance -->
            <div class="settings-card">
                <div class="settings-header">
                    <h3>Maintenance du Système</h3>
                    <p>Outils de maintenance et optimisation</p>
                </div>
                <div class="settings-content">
                    <div class="maintenance-tools">
                        <div class="tool-item">
                            <div class="tool-info">
                                <h4>Nettoyer le cache</h4>
                                <p>Supprime les fichiers temporaires et optimise les performances</p>
                            </div>
                            <div class="tool-action">
                                <button class="btn btn-outline" onclick="clearCache()">
                                    <i class="fas fa-broom"></i>
                                    Nettoyer
                                </button>
                            </div>
                        </div>

                        <div class="tool-item">
                            <div class="tool-info">
                                <h4>Optimiser la base de données</h4>
                                <p>Réorganise et optimise les tables de la base de données</p>
                            </div>
                            <div class="tool-action">
                                <button class="btn btn-outline" onclick="optimizeDatabase()">
                                    <i class="fas fa-database"></i>
                                    Optimiser
                                </button>
                            </div>
                        </div>

                        <div class="tool-item">
                            <div class="tool-info">
                                <h4>Vérifier l'intégrité</h4>
                                <p>Vérifie l'intégrité des fichiers et de la base de données</p>
                            </div>
                            <div class="tool-action">
                                <button class="btn btn-outline" onclick="checkIntegrity()">
                                    <i class="fas fa-shield-alt"></i>
                                    Vérifier
                                </button>
                            </div>
                        </div>

                        <div class="tool-item">
                            <div class="tool-info">
                                <h4>Logs du système</h4>
                                <p>Consulter et télécharger les journaux d'activité</p>
                            </div>
                            <div class="tool-action">
                                <button class="btn btn-outline" onclick="viewLogs()">
                                    <i class="fas fa-file-alt"></i>
                                    Voir Logs
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informations système -->
            <div class="settings-card">
                <div class="settings-header">
                    <h3>Informations Système</h3>
                    <p>État et configuration du serveur</p>
                </div>
                <div class="settings-content">
                    <div class="system-info">
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Version de l'application:</span>
                                <span class="info-value">v2.1.0</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Version PHP:</span>
                                <span class="info-value">8.2.0</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Base de données:</span>
                                <span class="info-value">MySQL 8.0.32</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Serveur web:</span>
                                <span class="info-value">Apache 2.4.54</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Espace disque utilisé:</span>
                                <span class="info-value">6.5 GB / 50 GB</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Mémoire utilisée:</span>
                                <span class="info-value">128 MB / 512 MB</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

</div> <!-- Fin admin-layout -->

<!-- Scripts -->
<script src="../assets/js/admin.js"></script>
<script>
// Navigation des paramètres
document.querySelectorAll('.settings-nav-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const section = this.getAttribute('data-section');
        
        // Désactiver tous les boutons et sections
        document.querySelectorAll('.settings-nav-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.settings-section').forEach(s => s.classList.remove('active'));
        
        // Activer le bouton et la section sélectionnés
        this.classList.add('active');
        document.querySelector(`.settings-section[data-section="${section}"]`).classList.add('active');
    });
});

// Gestion de l'avatar
document.getElementById('avatarInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatarPreview').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
});

function removeAvatar() {
    document.getElementById('avatarPreview').src = '/placeholder.svg?height=80&width=80';
    document.getElementById('avatarInput').value = '';
}

// Gestion du logo
document.getElementById('logoInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('logoPreview').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
});

// Validation du mot de passe
document.getElementById('newPassword').addEventListener('input', function() {
    const password = this.value;
    const strengthIndicator = document.getElementById('passwordStrength');
    
    let strength = 0;
    let feedback = [];
    
    if (password.length >= 8) strength++;
    else feedback.push('Au moins 8 caractères');
    
    if (/[A-Z]/.test(password)) strength++;
    else feedback.push('Une majuscule');
    
    if (/[a-z]/.test(password)) strength++;
    else feedback.push('Une minuscule');
    
    if (/[0-9]/.test(password)) strength++;
    else feedback.push('Un chiffre');

// Validation du mot de passe (suite)
    if (/[!@#$%^&*]/.test(password)) strength++;
    else feedback.push('Un caractère spécial');
    
    const strengthLevels = ['Très faible', 'Faible', 'Moyen', 'Fort', 'Très fort'];
    const strengthColors = ['#e74c3c', '#f39c12', '#f1c40f', '#27ae60', '#2ecc71'];
    
    strengthIndicator.innerHTML = `
        <div class="strength-bar">
            <div class="strength-fill" style="width: ${(strength / 4) * 100}%; background: ${strengthColors[strength]}"></div>
        </div>
        <div class="strength-text" style="color: ${strengthColors[strength]}">
            ${strengthLevels[strength]} ${feedback.length > 0 ? '- ' + feedback.join(', ') : ''}
        </div>
    `;
});

// Confirmation du mot de passe
document.getElementById('confirmPassword').addEventListener('input', function() {
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = this.value;
    
    if (confirmPassword && newPassword !== confirmPassword) {
        this.setCustomValidity('Les mots de passe ne correspondent pas');
    } else {
        this.setCustomValidity('');
    }
});

// Soumission des formulaires
document.getElementById('profileForm').addEventListener('submit', function(e) {
    e.preventDefault();
    console.log('Sauvegarde du profil');
    showNotification('Profil sauvegardé avec succès', 'success');
});

document.getElementById('companyForm').addEventListener('submit', function(e) {
    e.preventDefault();
    console.log('Sauvegarde des informations entreprise');
    showNotification('Informations entreprise sauvegardées', 'success');
});

document.getElementById('passwordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    if (newPassword !== confirmPassword) {
        showNotification('Les mots de passe ne correspondent pas', 'error');
        return;
    }
    
    console.log('Changement de mot de passe');
    showNotification('Mot de passe modifié avec succès', 'success');
    this.reset();
});

// Fonctions des paramètres de sécurité
function generateRecoveryCodes() {
    const codes = [];
    for (let i = 0; i < 10; i++) {
        codes.push(Math.random().toString(36).substring(2, 10).toUpperCase());
    }
    
    const codesText = codes.join('\n');
    const blob = new Blob([codesText], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    
    const a = document.createElement('a');
    a.href = url;
    a.download = 'codes-recuperation-dac.txt';
    a.click();
    
    window.URL.revokeObjectURL(url);
    showNotification('Codes de récupération générés et téléchargés', 'success');
}

function revokeSession(sessionId) {
    if (confirm('Déconnecter cette session ?')) {
        console.log('Révocation session:', sessionId);
        showNotification('Session déconnectée', 'success');
    }
}

function revokeAllSessions() {
    if (confirm('Déconnecter toutes les autres sessions ? Vous resterez connecté sur cette session.')) {
        console.log('Révocation de toutes les sessions');
        showNotification('Toutes les sessions ont été déconnectées', 'success');
    }
}

// Fonctions des notifications
function saveNotificationSettings() {
    console.log('Sauvegarde des préférences de notifications');
    showNotification('Préférences de notifications sauvegardées', 'success');
}

// Fonctions des intégrations
function connectIntegration(service) {
    console.log('Connexion à:', service);
    showNotification(`Connexion à ${service} en cours...`, 'info');
    
    // Simulation de connexion
    setTimeout(() => {
        showNotification(`${service} connecté avec succès`, 'success');
    }, 2000);
}

function configureIntegration(service) {
    console.log('Configuration de:', service);
    showNotification(`Ouverture de la configuration ${service}`, 'info');
}

function regenerateApiKey() {
    if (confirm('Régénérer la clé API ? L\'ancienne clé sera invalidée.')) {
        const newKey = 'dac_live_' + Math.random().toString(36).substring(2, 18);
        document.querySelector('.api-key-item input').value = newKey;
        showNotification('Nouvelle clé API générée', 'success');
    }
}

function copyApiKey() {
    const apiKeyInput = document.querySelector('.api-key-item input');
    apiKeyInput.select();
    document.execCommand('copy');
    showNotification('Clé API copiée dans le presse-papiers', 'success');
}

function addWebhook() {
    console.log('Ajout d\'un webhook');
    showNotification('Fonctionnalité en développement', 'info');
}

function editWebhook(id) {
    console.log('Édition webhook:', id);
    showNotification('Fonctionnalité en développement', 'info');
}

function deleteWebhook(id) {
    if (confirm('Supprimer ce webhook ?')) {
        console.log('Suppression webhook:', id);
        showNotification('Webhook supprimé', 'success');
    }
}

// Fonctions système
function createBackup() {
    console.log('Création de sauvegarde');
    showNotification('Création de la sauvegarde en cours...', 'info');
    
    setTimeout(() => {
        showNotification('Sauvegarde créée avec succès', 'success');
    }, 3000);
}

function downloadBackup() {
    console.log('Téléchargement de sauvegarde');
    showNotification('Téléchargement de la sauvegarde...', 'info');
}

function clearCache() {
    console.log('Nettoyage du cache');
    showNotification('Cache nettoyé avec succès', 'success');
}

function optimizeDatabase() {
    console.log('Optimisation de la base de données');
    showNotification('Optimisation en cours...', 'info');
    
    setTimeout(() => {
        showNotification('Base de données optimisée', 'success');
    }, 2000);
}

function checkIntegrity() {
    console.log('Vérification de l\'intégrité');
    showNotification('Vérification en cours...', 'info');
    
    setTimeout(() => {
        showNotification('Intégrité vérifiée - Aucun problème détecté', 'success');
    }, 1500);
}

function viewLogs() {
    console.log('Affichage des logs');
    window.open('logs.php', '_blank');
}

// Fonctions utilitaires
function resetSettings() {
    if (confirm('Réinitialiser tous les paramètres ? Cette action est irréversible.')) {
        console.log('Réinitialisation des paramètres');
        showNotification('Paramètres réinitialisés', 'success');
        setTimeout(() => {
            location.reload();
        }, 1000);
    }
}

function saveAllSettings() {
    console.log('Sauvegarde de tous les paramètres');
    showNotification('Tous les paramètres ont été sauvegardés', 'success');
}

// Fonction de notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${getNotificationIcon(type)}"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    Object.assign(notification.style, {
        position: 'fixed',
        top: '20px',
        right: '20px',
        background: getNotificationColor(type),
        color: 'white',
        padding: '1rem',
        borderRadius: '0.5rem',
        boxShadow: '0 4px 6px rgba(0, 0, 0, 0.1)',
        zIndex: '9999',
        display: 'flex',
        alignItems: 'center',
        gap: '1rem',
        minWidth: '300px',
        animation: 'slideInRight 0.3s ease-out'
    });
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

function getNotificationIcon(type) {
    const icons = {
        success: 'check-circle',
        error: 'exclamation-circle',
        warning: 'exclamation-triangle',
        info: 'info-circle'
    };
    return icons[type] || 'info-circle';
}

function getNotificationColor(type) {
    const colors = {
        success: '#27ae60',
        error: '#e74c3c',
        warning: '#f39c12',
        info: '#3498db'
    };
    return colors[type] || '#3498db';
}

// Styles CSS supplémentaires
const additionalStyles = `
    .strength-bar {
        height: 4px;
        background: #e9ecef;
        border-radius: 2px;
        overflow: hidden;
        margin-bottom: 0.5rem;
    }
    
    .strength-fill {
        height: 100%;
        transition: all 0.3s ease;
    }
    
    .strength-text {
        font-size: 0.75rem;
        font-weight: 500;
    }
    
    .switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
    }
    
    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: 0.3s;
        border-radius: 24px;
    }
    
    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: 0.3s;
        border-radius: 50%;
    }
    
    input:checked + .slider {
        background-color: #e74c3c;
    }
    
    input:checked + .slider:before {
        transform: translateX(26px);
    }
    
    .notification-content {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex: 1;
    }
    
    .notification-close {
        background: none;
        border: none;
        color: inherit;
        cursor: pointer;
        padding: 0.25rem;
        border-radius: 0.25rem;
        opacity: 0.8;
        transition: opacity 0.2s;
    }
    
    .notification-close:hover {
        opacity: 1;
        background: rgba(255, 255, 255, 0.1);
    }
    
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;

const styleSheet = document.createElement('style');
styleSheet.textContent = additionalStyles;
document.head.appendChild(styleSheet);
</script>

<?php include '../includes/footer.php'; ?>
