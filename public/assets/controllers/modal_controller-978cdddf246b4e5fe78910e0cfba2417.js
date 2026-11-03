import { Controller } from '@hotwired/stimulus';

/**
 * PRIMATECH — Contrôleur Stimulus Modal Bootstrap 5 / Metronic
 * Gère l'ouverture, la fermeture et le cycle de vie des modales sous Turbo
 */
export default class extends Controller {
    connect() {
        if (window.bootstrap?.Modal) {
            this.modal = new window.bootstrap.Modal(this.element);
        }
    }

    disconnect() {
        if (this.modal) {
            this.modal.hide();
            this.modal.dispose();
        }
        document.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.remove());
        document.body.classList.remove('modal-open');
    }

    open() {
        if (this.modal) {
            this.modal.show();
        }
    }

    close() {
        if (this.modal) {
            this.modal.hide();
        }
    }
}
