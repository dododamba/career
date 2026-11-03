import { Controller } from '@hotwired/stimulus';

/**
 * PRIMATECH — Contrôleur Stimulus Table & Listes Metronic
 * Filtre instantané des lignes de tableau et gestion des sélections groupées
 */
export default class extends Controller {
    static targets = ['row', 'input', 'checkbox', 'checkAll', 'empty'];

    filter() {
        const query = (this.inputTarget.value || '').toLowerCase().trim();
        let visibleCount = 0;

        this.rowTargets.forEach((row) => {
            const text = row.textContent.toLowerCase();
            const matches = !query || text.includes(query);
            row.style.display = matches ? '' : 'none';
            if (matches) visibleCount++;
        });

        if (this.hasEmptyTarget) {
            this.emptyTarget.style.display = (visibleCount === 0 && query !== '') ? '' : 'none';
        }
    }

    toggleAll(event) {
        const isChecked = event.target.checked;
        this.checkboxTargets.forEach((cb) => {
            cb.checked = isChecked;
        });
    }
}
