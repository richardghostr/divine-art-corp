/**
 * DIVINE ART CORPORATION - ADMIN JAVASCRIPT
 * Gestion de l'interface d'administration responsive
 */

class AdminInterface {
  constructor() {
    this.sidebar = document.querySelector(".admin-sidebar")
    this.sidebarOverlay = document.querySelector(".sidebar-overlay")
    this.menuToggle = document.querySelector(".menu-toggle")
    this.userDropdown = document.querySelector(".user-dropdown")
    this.userMenu = document.querySelector(".user-menu")

    this.init()
  }

  init() {
    this.setupEventListeners()
    this.setupResponsive()
    this.setupModals()
    this.setupTables()
    this.setupSearch()
    this.setupNotifications()
    this.autoRefreshStats()
  }

  setupEventListeners() {
    // Menu toggle pour mobile
    if (this.menuToggle) {
      this.menuToggle.addEventListener("click", () => {
        this.toggleSidebar()
      })
    }

    // Overlay pour fermer le sidebar
    if (this.sidebarOverlay) {
      this.sidebarOverlay.addEventListener("click", () => {
        this.closeSidebar()
      })
    }

    // User dropdown
    if (this.userMenu) {
      this.userMenu.addEventListener("click", (e) => {
        e.stopPropagation()
        this.toggleUserDropdown()
      })
    }

    // Fermer les dropdowns en cliquant ailleurs
    document.addEventListener("click", () => {
      this.closeAllDropdowns()
    })

    // Gestion du redimensionnement
    window.addEventListener("resize", () => {
      this.handleResize()
    })

    // Navigation avec clavier
    document.addEventListener("keydown", (e) => {
      this.handleKeyboardNavigation(e)
    })
  }

  setupResponsive() {
    // Vérifier la taille d'écran au chargement
    this.handleResize()

    // Détecter les changements d'orientation
    window.addEventListener("orientationchange", () => {
      setTimeout(() => {
        this.handleResize()
      }, 100)
    })
  }

  toggleSidebar() {
    if (this.sidebar) {
      const isOpen = this.sidebar.classList.contains("open")

      if (isOpen) {
        this.closeSidebar()
      } else {
        this.openSidebar()
      }
    }
  }

  openSidebar() {
    if (this.sidebar && this.sidebarOverlay) {
      this.sidebar.classList.add("open")
      this.sidebarOverlay.classList.add("active")
      document.body.style.overflow = "hidden"

      // Focus sur le premier lien du menu
      const firstLink = this.sidebar.querySelector(".nav-link")
      if (firstLink) {
        firstLink.focus()
      }
    }
  }

  closeSidebar() {
    if (this.sidebar && this.sidebarOverlay) {
      this.sidebar.classList.remove("open")
      this.sidebarOverlay.classList.remove("active")
      document.body.style.overflow = ""

      // Remettre le focus sur le bouton menu
      if (this.menuToggle) {
        this.menuToggle.focus()
      }
    }
  }

  toggleUserDropdown() {
    if (this.userDropdown) {
      const isVisible = this.userDropdown.style.display === "block"
      this.closeAllDropdowns()

      if (!isVisible) {
        this.userDropdown.style.display = "block"

        // Focus sur le premier élément du dropdown
        const firstItem = this.userDropdown.querySelector(".dropdown-item")
        if (firstItem) {
          firstItem.focus()
        }
      }
    }
  }

  closeAllDropdowns() {
    // Fermer le user dropdown
    if (this.userDropdown) {
      this.userDropdown.style.display = "none"
    }

    // Fermer tous les autres dropdowns
    const dropdowns = document.querySelectorAll(".dropdown-menu, .user-dropdown")
    dropdowns.forEach((dropdown) => {
      dropdown.style.display = "none"
    })
  }

  handleResize() {
    const width = window.innerWidth

    if (width > 768) {
      // Desktop: fermer le sidebar mobile et overlay
      this.closeSidebar()
      document.body.style.overflow = ""
    } else {
      // Mobile: s'assurer que le sidebar est fermé
      if (this.sidebar && !this.sidebar.classList.contains("open")) {
        this.sidebar.classList.remove("open")
      }
    }
  }

  handleKeyboardNavigation(e) {
    // Échapper pour fermer les modals et dropdowns
    if (e.key === "Escape") {
      this.closeAllDropdowns()
      this.closeAllModals()
      this.closeSidebar()
    }

    // Navigation dans le sidebar avec les flèches
    if (e.target.closest(".admin-sidebar")) {
      const links = Array.from(this.sidebar.querySelectorAll(".nav-link"))
      const currentIndex = links.indexOf(e.target)

      if (e.key === "ArrowDown" && currentIndex < links.length - 1) {
        e.preventDefault()
        links[currentIndex + 1].focus()
      } else if (e.key === "ArrowUp" && currentIndex > 0) {
        e.preventDefault()
        links[currentIndex - 1].focus()
      }
    }
  }

  setupModals() {
    // Gestion des modals
    const modalTriggers = document.querySelectorAll("[data-modal]")
    const modals = document.querySelectorAll(".modal")
    const closeButtons = document.querySelectorAll(".modal .close")

    modalTriggers.forEach((trigger) => {
      trigger.addEventListener("click", (e) => {
        e.preventDefault()
        const modalId = trigger.getAttribute("data-modal")
        this.openModal(modalId)
      })
    })

    closeButtons.forEach((button) => {
      button.addEventListener("click", () => {
        this.closeAllModals()
      })
    })

    // Fermer modal en cliquant sur l'overlay
    modals.forEach((modal) => {
      modal.addEventListener("click", (e) => {
        if (e.target === modal) {
          this.closeAllModals()
        }
      })
    })
  }

  openModal(modalId) {
    const modal = document.getElementById(modalId)
    if (modal) {
      modal.style.display = "flex"
      document.body.style.overflow = "hidden"

      // Focus sur le premier input du modal
      const firstInput = modal.querySelector("input, select, textarea")
      if (firstInput) {
        setTimeout(() => firstInput.focus(), 100)
      }
    }
  }

  closeAllModals() {
    const modals = document.querySelectorAll(".modal")
    modals.forEach((modal) => {
      modal.style.display = "none"
    })
    document.body.style.overflow = ""
  }

  setupTables() {
    // Rendre les tables responsive
    const tables = document.querySelectorAll(".data-table")

    tables.forEach((table) => {
      // Ajouter un wrapper si nécessaire
      if (!table.parentElement.classList.contains("table-responsive")) {
        const wrapper = document.createElement("div")
        wrapper.className = "table-responsive"
        table.parentElement.insertBefore(wrapper, table)
        wrapper.appendChild(table)
      }

      // Tri des colonnes
      const headers = table.querySelectorAll("th[data-sort]")
      headers.forEach((header) => {
        header.style.cursor = "pointer"
        header.addEventListener("click", () => {
          this.sortTable(table, header)
        })
      })
    })
  }

  sortTable(table, header) {
    const column = header.getAttribute("data-sort")
    const tbody = table.querySelector("tbody")
    const rows = Array.from(tbody.querySelectorAll("tr"))
    const isAscending = header.classList.contains("sort-asc")

    // Supprimer les classes de tri existantes
    table.querySelectorAll("th").forEach((th) => {
      th.classList.remove("sort-asc", "sort-desc")
    })

    // Ajouter la nouvelle classe de tri
    header.classList.add(isAscending ? "sort-desc" : "sort-asc")

    // Trier les lignes
    rows.sort((a, b) => {
      const aValue = a.querySelector(`td:nth-child(${header.cellIndex + 1})`).textContent.trim()
      const bValue = b.querySelector(`td:nth-child(${header.cellIndex + 1})`).textContent.trim()

      if (isAscending) {
        return bValue.localeCompare(aValue, undefined, { numeric: true })
      } else {
        return aValue.localeCompare(bValue, undefined, { numeric: true })
      }
    })

    // Réinsérer les lignes triées
    rows.forEach((row) => tbody.appendChild(row))
  }

  setupSearch() {
    const searchInput = document.getElementById("globalSearch")
    if (searchInput) {
      let searchTimeout

      searchInput.addEventListener("input", (e) => {
        clearTimeout(searchTimeout)
        searchTimeout = setTimeout(() => {
          this.performSearch(e.target.value)
        }, 300)
      })

      searchInput.addEventListener("keypress", (e) => {
        if (e.key === "Enter") {
          e.preventDefault()
          this.performSearch(e.target.value)
        }
      })
    }
  }

  performSearch(query) {
    if (query.length < 2) return

    // Recherche dans les tables visibles
    const tables = document.querySelectorAll(".data-table tbody")
    tables.forEach((tbody) => {
      const rows = tbody.querySelectorAll("tr")
      rows.forEach((row) => {
        const text = row.textContent.toLowerCase()
        const matches = text.includes(query.toLowerCase())
        row.style.display = matches ? "" : "none"
      })
    })

    // Recherche dans les cartes
    const cards = document.querySelectorAll(".project-card, .devis-card, .client-card")
    cards.forEach((card) => {
      const text = card.textContent.toLowerCase()
      const matches = text.includes(query.toLowerCase())
      card.style.display = matches ? "" : "none"
    })
  }

  setupNotifications() {
    // Auto-fermeture des alertes
    const alerts = document.querySelectorAll(".alert")
    alerts.forEach((alert) => {
      setTimeout(() => {
        alert.style.opacity = "0"
        setTimeout(() => {
          alert.remove()
        }, 300)
      }, 5000)
    })

    // Gestion des notifications en temps réel
    this.checkNotifications()
    setInterval(() => {
      this.checkNotifications()
    }, 60000) // Vérifier toutes les minutes
  }

  checkNotifications() {
    fetch("ajax/get_notifications.php")
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          this.updateNotificationBadges(data.notifications)
        }
      })
      .catch((error) => {
        console.error("Erreur lors de la récupération des notifications:", error)
      })
  }

  updateNotificationBadges(notifications) {
    // Mettre à jour les badges dans la sidebar
    const badges = document.querySelectorAll(".nav-badge")
    badges.forEach((badge) => {
      const link = badge.closest(".nav-link")
      const href = link.getAttribute("href")

      if (notifications[href]) {
        badge.textContent = notifications[href]
        badge.style.display = "inline"
      } else {
        badge.style.display = "none"
      }
    })

    // Mettre à jour le badge de notification principal
    const notificationBadge = document.querySelector(".notification-badge")
    if (notificationBadge) {
      const total = Object.values(notifications).reduce((sum, count) => sum + count, 0)
      if (total > 0) {
        notificationBadge.textContent = total
        notificationBadge.style.display = "inline"
      } else {
        notificationBadge.style.display = "none"
      }
    }
  }

  autoRefreshStats() {
    // Actualiser les statistiques toutes les 5 minutes
    setInterval(() => {
      this.refreshStats()
    }, 300000)
  }

  refreshStats() {
    const statCards = document.querySelectorAll(".stat-card")
    if (statCards.length === 0) return

    fetch("ajax/get_stats.php")
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          this.updateStats(data.stats)
        }
      })
      .catch((error) => {
        console.error("Erreur lors de la mise à jour des statistiques:", error)
      })
  }

  updateStats(stats) {
    Object.keys(stats).forEach((key) => {
      const statElement = document.querySelector(`[data-stat="${key}"] .stat-value`)
      if (statElement) {
        // Animation du changement de valeur
        const currentValue = Number.parseInt(statElement.textContent)
        const newValue = stats[key]

        if (currentValue !== newValue) {
          this.animateValue(statElement, currentValue, newValue, 1000)
        }
      }
    })
  }

  animateValue(element, start, end, duration) {
    const startTime = performance.now()
    const difference = end - start

    const step = (currentTime) => {
      const elapsed = currentTime - startTime
      const progress = Math.min(elapsed / duration, 1)

      const current = Math.floor(start + difference * progress)
      element.textContent = current.toLocaleString()

      if (progress < 1) {
        requestAnimationFrame(step)
      }
    }

    requestAnimationFrame(step)
  }

  // Méthodes utilitaires
  showAlert(message, type = "info") {
    const alert = document.createElement("div")
    alert.className = `alert alert-${type}`
    alert.innerHTML = `
            <i class="fas fa-${this.getAlertIcon(type)}"></i>
            <span>${message}</span>
            <button class="alert-close">&times;</button>
        `

    document.body.appendChild(alert)

    // Auto-fermeture
    setTimeout(() => {
      alert.remove()
    }, 5000)

    // Fermeture manuelle
    alert.querySelector(".alert-close").addEventListener("click", () => {
      alert.remove()
    })
  }

  getAlertIcon(type) {
    const icons = {
      success: "check-circle",
      error: "exclamation-circle",
      warning: "exclamation-triangle",
      info: "info-circle",
    }
    return icons[type] || "info-circle"
  }

  // Méthode pour confirmer les actions
  confirm(message, callback) {
    if (window.confirm(message)) {
      callback()
    }
  }

  // Méthode pour formater les nombres
  formatNumber(number) {
    return new Intl.NumberFormat("fr-FR").format(number)
  }

  // Méthode pour formater les dates
  formatDate(date) {
    return new Intl.DateTimeFormat("fr-FR", {
      year: "numeric",
      month: "long",
      day: "numeric",
    }).format(new Date(date))
  }
}

// Initialisation de l'interface admin
document.addEventListener("DOMContentLoaded", () => {
  window.adminInterface = new AdminInterface()
})

// Fonctions globales pour la compatibilité
function toggleSidebar() {
  if (window.adminInterface) {
    window.adminInterface.toggleSidebar()
  }
}

function openModal(modalId) {
  if (window.adminInterface) {
    window.adminInterface.openModal(modalId)
  }
}

function closeModal() {
  if (window.adminInterface) {
    window.adminInterface.closeAllModals()
  }
}

function showAlert(message, type) {
  if (window.adminInterface) {
    window.adminInterface.showAlert(message, type)
  }
}



/**
 * DIVINE ART CORPORATION - ADMIN JAVASCRIPT
 * Gestion de l'interface d'administration responsive
 */

class AdminInterface {
  constructor() {
    this.sidebar = document.querySelector(".admin-sidebar")
    this.sidebarOverlay = document.querySelector(".sidebar-overlay")
    this.menuToggle = document.querySelector(".menu-toggle")
    this.userDropdown = document.querySelector(".user-dropdown")
    this.userMenu = document.querySelector(".user-menu")

    this.init()
  }

  init() {
    this.setupEventListeners()
    this.setupResponsive()
    this.setupModals()
    this.setupTables()
    this.setupSearch()
    this.setupNotifications()
    this.autoRefreshStats()
  }

  setupEventListeners() {
    // Menu toggle pour mobile
    if (this.menuToggle) {
      this.menuToggle.addEventListener("click", () => {
        this.toggleSidebar()
      })
    }

    // Overlay pour fermer le sidebar
    if (this.sidebarOverlay) {
      this.sidebarOverlay.addEventListener("click", () => {
        this.closeSidebar()
      })
    }

    // User dropdown
    if (this.userMenu) {
      this.userMenu.addEventListener("click", (e) => {
        e.stopPropagation()
        this.toggleUserDropdown()
      })
    }

    // Fermer les dropdowns en cliquant ailleurs
    document.addEventListener("click", () => {
      this.closeAllDropdowns()
    })

    // Gestion du redimensionnement
    window.addEventListener("resize", () => {
      this.handleResize()
    })

    // Navigation avec clavier
    document.addEventListener("keydown", (e) => {
      this.handleKeyboardNavigation(e)
    })
  }

  setupResponsive() {
    // Vérifier la taille d'écran au chargement
    this.handleResize()

    // Détecter les changements d'orientation
    window.addEventListener("orientationchange", () => {
      setTimeout(() => {
        this.handleResize()
      }, 100)
    })
  }

  toggleSidebar() {
    if (this.sidebar) {
      const isOpen = this.sidebar.classList.contains("open")

      if (isOpen) {
        this.closeSidebar()
      } else {
        this.openSidebar()
      }
    }
  }

  openSidebar() {
    if (this.sidebar && this.sidebarOverlay) {
      this.sidebar.classList.add("open")
      this.sidebarOverlay.classList.add("active")
      document.body.style.overflow = "hidden"

      // Focus sur le premier lien du menu
      const firstLink = this.sidebar.querySelector(".nav-link")
      if (firstLink) {
        firstLink.focus()
      }
    }
  }

  closeSidebar() {
    if (this.sidebar && this.sidebarOverlay) {
      this.sidebar.classList.remove("open")
      this.sidebarOverlay.classList.remove("active")
      document.body.style.overflow = ""

      // Remettre le focus sur le bouton menu
      if (this.menuToggle) {
        this.menuToggle.focus()
      }
    }
  }

  toggleUserDropdown() {
    if (this.userDropdown) {
      const isVisible = this.userDropdown.style.display === "block"
      this.closeAllDropdowns()

      if (!isVisible) {
        this.userDropdown.style.display = "block"

        // Focus sur le premier élément du dropdown
        const firstItem = this.userDropdown.querySelector(".dropdown-item")
        if (firstItem) {
          firstItem.focus()
        }
      }
    }
  }

  closeAllDropdowns() {
    // Fermer le user dropdown
    if (this.userDropdown) {
      this.userDropdown.style.display = "none"
    }

    // Fermer tous les autres dropdowns
    const dropdowns = document.querySelectorAll(".dropdown-menu, .user-dropdown")
    dropdowns.forEach((dropdown) => {
      dropdown.style.display = "none"
    })
  }

  handleResize() {
    const width = window.innerWidth

    if (width > 768) {
      // Desktop: fermer le sidebar mobile et overlay
      this.closeSidebar()
      document.body.style.overflow = ""
    } else {
      // Mobile: s'assurer que le sidebar est fermé
      if (this.sidebar && !this.sidebar.classList.contains("open")) {
        this.sidebar.classList.remove("open")
      }
    }
  }

  handleKeyboardNavigation(e) {
    // Échapper pour fermer les modals et dropdowns
    if (e.key === "Escape") {
      this.closeAllDropdowns()
      this.closeAllModals()
      this.closeSidebar()
    }

    // Navigation dans le sidebar avec les flèches
    if (e.target.closest(".admin-sidebar")) {
      const links = Array.from(this.sidebar.querySelectorAll(".nav-link"))
      const currentIndex = links.indexOf(e.target)

      if (e.key === "ArrowDown" && currentIndex < links.length - 1) {
        e.preventDefault()
        links[currentIndex + 1].focus()
      } else if (e.key === "ArrowUp" && currentIndex > 0) {
        e.preventDefault()
        links[currentIndex - 1].focus()
      }
    }
  }

  setupModals() {
    // Gestion des modals
    const modalTriggers = document.querySelectorAll("[data-modal]")
    const modals = document.querySelectorAll(".modal")
    const closeButtons = document.querySelectorAll(".modal .close")

    modalTriggers.forEach((trigger) => {
      trigger.addEventListener("click", (e) => {
        e.preventDefault()
        const modalId = trigger.getAttribute("data-modal")
        this.openModal(modalId)
      })
    })

    closeButtons.forEach((button) => {
      button.addEventListener("click", () => {
        this.closeAllModals()
      })
    })

    // Fermer modal en cliquant sur l'overlay
    modals.forEach((modal) => {
      modal.addEventListener("click", (e) => {
        if (e.target === modal) {
          this.closeAllModals()
        }
      })
    })
  }

  openModal(modalId) {
    const modal = document.getElementById(modalId)
    if (modal) {
      modal.style.display = "flex"
      document.body.style.overflow = "hidden"

      // Focus sur le premier input du modal
      const firstInput = modal.querySelector("input, select, textarea")
      if (firstInput) {
        setTimeout(() => firstInput.focus(), 100)
      }
    }
  }

  closeAllModals() {
    const modals = document.querySelectorAll(".modal")
    modals.forEach((modal) => {
      modal.style.display = "none"
    })
    document.body.style.overflow = ""
  }

  setupTables() {
    // Rendre les tables responsive
    const tables = document.querySelectorAll(".data-table")

    tables.forEach((table) => {
      // Ajouter un wrapper si nécessaire
      if (!table.parentElement.classList.contains("table-responsive")) {
        const wrapper = document.createElement("div")
        wrapper.className = "table-responsive"
        table.parentElement.insertBefore(wrapper, table)
        wrapper.appendChild(table)
      }

      // Tri des colonnes
      const headers = table.querySelectorAll("th[data-sort]")
      headers.forEach((header) => {
        header.style.cursor = "pointer"
        header.addEventListener("click", () => {
          this.sortTable(table, header)
        })
      })
    })
  }

  sortTable(table, header) {
    const column = header.getAttribute("data-sort")
    const tbody = table.querySelector("tbody")
    const rows = Array.from(tbody.querySelectorAll("tr"))
    const isAscending = header.classList.contains("sort-asc")

    // Supprimer les classes de tri existantes
    table.querySelectorAll("th").forEach((th) => {
      th.classList.remove("sort-asc", "sort-desc")
    })

    // Ajouter la nouvelle classe de tri
    header.classList.add(isAscending ? "sort-desc" : "sort-asc")

    // Trier les lignes
    rows.sort((a, b) => {
      const aValue = a.querySelector(`td:nth-child(${header.cellIndex + 1})`).textContent.trim()
      const bValue = b.querySelector(`td:nth-child(${header.cellIndex + 1})`).textContent.trim()

      if (isAscending) {
        return bValue.localeCompare(aValue, undefined, { numeric: true })
      } else {
        return aValue.localeCompare(bValue, undefined, { numeric: true })
      }
    })

    // Réinsérer les lignes triées
    rows.forEach((row) => tbody.appendChild(row))
  }

  setupSearch() {
    const searchInput = document.getElementById("globalSearch")
    if (searchInput) {
      let searchTimeout

      searchInput.addEventListener("input", (e) => {
        clearTimeout(searchTimeout)
        searchTimeout = setTimeout(() => {
          this.performSearch(e.target.value)
        }, 300)
      })

      searchInput.addEventListener("keypress", (e) => {
        if (e.key === "Enter") {
          e.preventDefault()
          this.performSearch(e.target.value)
        }
      })
    }
  }

  performSearch(query) {
    if (query.length < 2) return

    // Recherche dans les tables visibles
    const tables = document.querySelectorAll(".data-table tbody")
    tables.forEach((tbody) => {
      const rows = tbody.querySelectorAll("tr")
      rows.forEach((row) => {
        const text = row.textContent.toLowerCase()
        const matches = text.includes(query.toLowerCase())
        row.style.display = matches ? "" : "none"
      })
    })

    // Recherche dans les cartes
    const cards = document.querySelectorAll(".project-card, .devis-card, .client-card")
    cards.forEach((card) => {
      const text = card.textContent.toLowerCase()
      const matches = text.includes(query.toLowerCase())
      card.style.display = matches ? "" : "none"
    })
  }

  setupNotifications() {
    // Auto-fermeture des alertes
    const alerts = document.querySelectorAll(".alert")
    alerts.forEach((alert) => {
      setTimeout(() => {
        alert.style.opacity = "0"
        setTimeout(() => {
          alert.remove()
        }, 300)
      }, 5000)
    })

    // Gestion des notifications en temps réel
    this.checkNotifications()
    setInterval(() => {
      this.checkNotifications()
    }, 60000) // Vérifier toutes les minutes
  }

  checkNotifications() {
    fetch("ajax/get_notifications.php")
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          this.updateNotificationBadges(data.notifications)
        }
      })
      .catch((error) => {
        console.error("Erreur lors de la récupération des notifications:", error)
      })
  }

  updateNotificationBadges(notifications) {
    // Mettre à jour les badges dans la sidebar
    const badges = document.querySelectorAll(".nav-badge")
    badges.forEach((badge) => {
      const link = badge.closest(".nav-link")
      const href = link.getAttribute("href")

      if (notifications[href]) {
        badge.textContent = notifications[href]
        badge.style.display = "inline"
      } else {
        badge.style.display = "none"
      }
    })

    // Mettre à jour le badge de notification principal
    const notificationBadge = document.querySelector(".notification-badge")
    if (notificationBadge) {
      const total = Object.values(notifications).reduce((sum, count) => sum + count, 0)
      if (total > 0) {
        notificationBadge.textContent = total
        notificationBadge.style.display = "inline"
      } else {
        notificationBadge.style.display = "none"
      }
    }
  }

  autoRefreshStats() {
    // Actualiser les statistiques toutes les 5 minutes
    setInterval(() => {
      this.refreshStats()
    }, 300000)
  }

  refreshStats() {
    const statCards = document.querySelectorAll(".stat-card")
    if (statCards.length === 0) return

    fetch("ajax/get_stats.php")
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          this.updateStats(data.stats)
        }
      })
      .catch((error) => {
        console.error("Erreur lors de la mise à jour des statistiques:", error)
      })
  }

  updateStats(stats) {
    Object.keys(stats).forEach((key) => {
      const statElement = document.querySelector(`[data-stat="${key}"] .stat-value`)
      if (statElement) {
        // Animation du changement de valeur
        const currentValue = Number.parseInt(statElement.textContent)
        const newValue = stats[key]

        if (currentValue !== newValue) {
          this.animateValue(statElement, currentValue, newValue, 1000)
        }
      }
    })
  }

  animateValue(element, start, end, duration) {
    const startTime = performance.now()
    const difference = end - start

    const step = (currentTime) => {
      const elapsed = currentTime - startTime
      const progress = Math.min(elapsed / duration, 1)

      const current = Math.floor(start + difference * progress)
      element.textContent = current.toLocaleString()

      if (progress < 1) {
        requestAnimationFrame(step)
      }
    }

    requestAnimationFrame(step)
  }

  // Méthodes utilitaires
  showAlert(message, type = "info") {
    const alert = document.createElement("div")
    alert.className = `alert alert-${type}`
    alert.innerHTML = `
            <i class="fas fa-${this.getAlertIcon(type)}"></i>
            <span>${message}</span>
            <button class="alert-close">&times;</button>
        `

    document.body.appendChild(alert)

    // Auto-fermeture
    setTimeout(() => {
      alert.remove()
    }, 5000)

    // Fermeture manuelle
    alert.querySelector(".alert-close").addEventListener("click", () => {
      alert.remove()
    })
  }

  getAlertIcon(type) {
    const icons = {
      success: "check-circle",
      error: "exclamation-circle",
      warning: "exclamation-triangle",
      info: "info-circle",
    }
    return icons[type] || "info-circle"
  }

  // Méthode pour confirmer les actions
  confirm(message, callback) {
    if (window.confirm(message)) {
      callback()
    }
  }

  // Méthode pour formater les nombres
  formatNumber(number) {
    return new Intl.NumberFormat("fr-FR").format(number)
  }

  // Méthode pour formater les dates
  formatDate(date) {
    return new Intl.DateTimeFormat("fr-FR", {
      year: "numeric",
      month: "long",
      day: "numeric",
    }).format(new Date(date))
  }
}

// Initialisation de l'interface admin
document.addEventListener("DOMContentLoaded", () => {
  window.adminInterface = new AdminInterface()
})

// Fonctions globales pour la compatibilité
function toggleSidebar() {
  if (window.adminInterface) {
    window.adminInterface.toggleSidebar()
  }
}

function openModal(modalId) {
  if (window.adminInterface) {
    window.adminInterface.openModal(modalId)
  }
}

function closeModal() {
  if (window.adminInterface) {
    window.adminInterface.closeAllModals()
  }
}

function showAlert(message, type) {
  if (window.adminInterface) {
    window.adminInterface.showAlert(message, type)
  }
}
/**
 * DIVINE ART CORPORATION - ADMIN JAVASCRIPT RESPONSIVE
 * Code à ajouter pour la gestion responsive
 */

// Gestion du menu responsive
document.addEventListener("DOMContentLoaded", () => {
  const menuToggle = document.getElementById("menuToggle")
  const sidebar = document.querySelector(".admin-sidebar")
  const sidebarOverlay = document.querySelector(".sidebar-overlay")
  const userDropdown = document.querySelector(".user-dropdown")
  const userMenu = document.querySelector(".user-menu")

  // Toggle menu mobile
  if (menuToggle && sidebar && sidebarOverlay) {
    menuToggle.addEventListener("click", () => {
      const isOpen = sidebar.classList.contains("open")

      if (isOpen) {
        closeSidebar()
      } else {
        openSidebar()
      }
    })

    // Fermer avec overlay
    sidebarOverlay.addEventListener("click", () => {
      closeSidebar()
    })
  }

  // Fonctions sidebar
  function openSidebar() {
    sidebar.classList.add("open")
    sidebarOverlay.classList.add("active")
    document.body.style.overflow = "hidden"

    // Focus sur le premier lien
    const firstLink = sidebar.querySelector(".nav-link")
    if (firstLink) {
      setTimeout(() => firstLink.focus(), 100)
    }
  }

  function closeSidebar() {
    sidebar.classList.remove("open")
    sidebarOverlay.classList.remove("active")
    document.body.style.overflow = ""

    // Remettre focus sur menu toggle
    if (menuToggle) {
      menuToggle.focus()
    }
  }

  // User dropdown
  if (userMenu && userDropdown) {
    userMenu.addEventListener("click", (e) => {
      e.stopPropagation()
      const isVisible = userDropdown.style.display === "block"

      // Fermer tous les dropdowns
      closeAllDropdowns()

      if (!isVisible) {
        userDropdown.style.display = "block"
      }
    })
  }

  // Fermer dropdowns en cliquant ailleurs
  document.addEventListener("click", () => {
    closeAllDropdowns()
  })

  function closeAllDropdowns() {
    if (userDropdown) {
      userDropdown.style.display = "none"
    }
  }

  // Gestion responsive au redimensionnement
  function handleResize() {
    const width = window.innerWidth

    if (width > 768) {
      // Desktop: fermer sidebar mobile
      closeSidebar()
    }
  }

  window.addEventListener("resize", handleResize)
  window.addEventListener("orientationchange", () => {
    setTimeout(handleResize, 100)
  })

  // Navigation clavier
  document.addEventListener("keydown", (e) => {
    // Échapper pour fermer
    if (e.key === "Escape") {
      closeSidebar()
      closeAllDropdowns()
    }

    // Navigation dans sidebar
    if (e.target.closest(".admin-sidebar")) {
      const links = Array.from(sidebar.querySelectorAll(".nav-link"))
      const currentIndex = links.indexOf(e.target)

      if (e.key === "ArrowDown" && currentIndex < links.length - 1) {
        e.preventDefault()
        links[currentIndex + 1].focus()
      } else if (e.key === "ArrowUp" && currentIndex > 0) {
        e.preventDefault()
        links[currentIndex - 1].focus()
      }
    }
  })

  // Tables responsive
  const tables = document.querySelectorAll(".data-table")
  tables.forEach((table) => {
    if (!table.parentElement.classList.contains("table-responsive")) {
      const wrapper = document.createElement("div")
      wrapper.className = "table-responsive"
      table.parentElement.insertBefore(wrapper, table)
      wrapper.appendChild(table)
    }
  })

  // Recherche responsive
  const globalSearch = document.getElementById("globalSearch")
  if (globalSearch) {
    globalSearch.addEventListener("keypress", function (e) {
      if (e.key === "Enter") {
        const query = this.value.trim()
        if (query) {
          performSearch(query)
        }
      }
    })
  }

  function performSearch(query) {
    // Recherche dans les tables
    const tables = document.querySelectorAll(".data-table tbody")
    tables.forEach((tbody) => {
      const rows = tbody.querySelectorAll("tr")
      rows.forEach((row) => {
        const text = row.textContent.toLowerCase()
        const matches = text.includes(query.toLowerCase())
        row.style.display = matches ? "" : "none"
      })
    })

    // Recherche dans les cartes
    const cards = document.querySelectorAll(".project-card, .devis-card, .stat-card")
    cards.forEach((card) => {
      const text = card.textContent.toLowerCase()
      const matches = text.includes(query.toLowerCase())
      card.style.display = matches ? "" : "none"
    })
  }
})

// Fonctions globales pour compatibilité
function toggleSidebar() {
  const sidebar = document.querySelector(".admin-sidebar")
  if (sidebar) {
    sidebar.classList.toggle("open")
    document.querySelector(".sidebar-overlay").classList.toggle("active")
  }
}

function closeSidebar() {
  const sidebar = document.querySelector(".admin-sidebar")
  const overlay = document.querySelector(".sidebar-overlay")
  if (sidebar && overlay) {
    sidebar.classList.remove("open")
    overlay.classList.remove("active")
    document.body.style.overflow = ""
  }
}
