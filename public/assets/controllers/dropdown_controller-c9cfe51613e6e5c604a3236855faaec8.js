import { Controller } from '@hotwired/stimulus';

/**
 * Contrôleur Stimulus pour le menu déroulant du profil utilisateur.
 * Remplace KTMenu pour ce composant afin de garantir :
 * - Fonctionnement 100% fiable avec Turbo Drive
 * - Aucun conflit d'écouteurs multiples
 * - Fermeture au clic extérieur, sur Escape, et lors de la navigation
 * - Accessibilité (aria-expanded, aria-haspopup, gestion du focus)
 */
export default class extends Controller {
    static targets = ['button', 'menu'];

    connect() {
        this.isOpen = false;
        this.onOutsideClick = this.onOutsideClick.bind(this);
        this.onKeydown = this.onKeydown.bind(this);
        this.closeOnNavigate = this.closeOnNavigate.bind(this);

        document.addEventListener('turbo:before-cache', this.closeOnNavigate);
    }

    disconnect() {
        this.close();
        document.removeEventListener('turbo:before-cache', this.closeOnNavigate);
        document.removeEventListener('click', this.onOutsideClick);
        document.removeEventListener('keydown', this.onKeydown);
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

        if (this.hasMenuTarget) {
            this.menuTarget.classList.add('show');
        }

        if (this.hasButtonTarget) {
            this.buttonTarget.setAttribute('aria-expanded', 'true');
        }

        // Écouteurs de fermeture
        setTimeout(() => {
            document.addEventListener('click', this.onOutsideClick);
            document.addEventListener('keydown', this.onKeydown);
        }, 10);
    }

    close() {
        if (!this.isOpen) return;

        this.isOpen = false;

        if (this.hasMenuTarget) {
            this.menuTarget.classList.remove('show');
        }

        if (this.hasButtonTarget) {
            this.buttonTarget.setAttribute('aria-expanded', 'false');
        }

        document.removeEventListener('click', this.onOutsideClick);
        document.removeEventListener('keydown', this.onKeydown);
    }

    onOutsideClick(event) {
        if (!this.element.contains(event.target)) {
            this.close();
        }
    }

    onKeydown(event) {
        if (event.key === 'Escape' || event.key === 'Esc') {
            event.preventDefault();
            this.close();
            if (this.hasButtonTarget) {
                this.buttonTarget.focus();
            }
        }
    }

    closeOnNavigate() {
        this.close();
    }
}
