import { Controller } from '@hotwired/stimulus';

/**
 * PRIMATECH — Contrôleur Stimulus Notification & Flash messages
 * Gère l'auto-dismiss et la fermeture des alertes Metronic
 */
export default class extends Controller {
    static values = {
        autoDismiss: { type: Boolean, default: true },
        duration: { type: Number, default: 5000 }
    };

    connect() {
        if (this.autoDismissValue) {
            this.timeout = setTimeout(() => {
                this.close();
            }, this.durationValue);
        }
    }

    disconnect() {
        if (this.timeout) {
            clearTimeout(this.timeout);
        }
    }

    close() {
        this.element.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
        this.element.style.opacity = '0';
        this.element.style.transform = 'translateY(-10px)';
        setTimeout(() => {
            this.element.remove();
        }, 400);
    }
}
