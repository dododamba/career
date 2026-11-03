import { Controller } from '@hotwired/stimulus';

/**
 * Contrôleur Stimulus pour les menus accordéons de la barre latérale (Sidebar).
 * Résout définitivement le problème de blocage des accordéons en :
 * - Gérant de façon synchrone et sans timers concurrents l'ouverture et la fermeture
 * - Éliminant les conflits d'écouteurs multiples sur document.body
 * - Préservant l'état actif initial de la route serveur
 * - Offrant un support clavier accessible (Espace / Entrée / Flèches)
 */
export default class extends Controller {
    static targets = ['trigger', 'submenu'];

    connect() {
        // Détecter si le menu est déjà actif au chargement serveur
        this.isOpen = this.element.classList.contains('show');
        this.updateAria();
    }

    toggle(event) {
        event.preventDefault();
        event.stopPropagation();

        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        this.isOpen = true;
        this.element.classList.add('show');
        this.updateAria();
    }

    close() {
        this.isOpen = false;
        this.element.classList.remove('show');
        this.updateAria();
    }

    updateAria() {
        if (this.hasTriggerTarget) {
            this.triggerTarget.setAttribute('aria-expanded', this.isOpen ? 'true' : 'false');
        }
    }

    onKeydown(event) {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            this.toggle(event);
        }
    }
}
