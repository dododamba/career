import './bootstrap.js';
import './styles/app.css';

/**
 * PRIMATECH — Cycle de vie Frontend : Réconciliation Turbo Drive & Metronic 8
 */

// Avant la mise en cache Turbo : nettoyer les éléments volatiles pour éviter les doublons/orphelins
document.addEventListener('turbo:before-cache', () => {
    // 1. Fermer et nettoyer les tooltips Bootstrap
    if (window.bootstrap?.Tooltip) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
            const instance = window.bootstrap.Tooltip.getInstance(el);
            if (instance) {
                instance.hide();
            }
        });
    }

    // 2. Fermer et nettoyer les popovers Bootstrap
    if (window.bootstrap?.Popover) {
        document.querySelectorAll('[data-bs-toggle="popover"]').forEach((el) => {
            const instance = window.bootstrap.Popover.getInstance(el);
            if (instance) {
                instance.hide();
            }
        });
    }

    // 3. Fermer les modales ouvertes et détruire les backdrops résiduels
    if (window.bootstrap?.Modal) {
        document.querySelectorAll('.modal.show').forEach((modalEl) => {
            const modal = window.bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }
        });
    }
    document.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.remove());
    document.body.classList.remove('modal-open');
    document.body.style.removeProperty('padding-right');
    document.body.style.removeProperty('overflow');

    // 4. Replier les tiroirs (drawers) et menus Metronic
    if (window.KTDrawer && typeof window.KTDrawer.hideAll === 'function') {
        window.KTDrawer.hideAll();
    }
    if (window.KTMenu && typeof window.KTMenu.hideDropdowns === 'function') {
        window.KTMenu.hideDropdowns();
    }
});

// À chaque chargement de page Turbo (initial ou navigation interne)
document.addEventListener('turbo:load', () => {
    // 1. Réinitialisation des composants Core Metronic 8
    if (window.KTApp && typeof window.KTApp.init === 'function') {
        try {
            window.KTApp.init();
        } catch (e) {
            console.debug('KTApp init notice:', e);
        }
    }

    if (window.KTMenu && typeof window.KTMenu.init === 'function') {
        try {
            window.KTMenu.init();
        } catch (e) {
            console.debug('KTMenu init notice:', e);
        }
    }

    if (window.KTDrawer && typeof window.KTDrawer.init === 'function') {
        try {
            window.KTDrawer.init();
        } catch (e) {
            console.debug('KTDrawer init notice:', e);
        }
    }

    if (window.KTScroll && typeof window.KTScroll.init === 'function') {
        try {
            window.KTScroll.init();
        } catch (e) {
            console.debug('KTScroll init notice:', e);
        }
    }

    if (window.KTSticky && typeof window.KTSticky.init === 'function') {
        try {
            window.KTSticky.init();
        } catch (e) {
            console.debug('KTSticky init notice:', e);
        }
    }

    // 2. Initialisation des tooltips Bootstrap 5
    if (window.bootstrap?.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach((el) => {
            if (!window.bootstrap.Tooltip.getInstance(el)) {
                new window.bootstrap.Tooltip(el);
            }
        });
    }

    // 3. Initialisation des popovers Bootstrap 5
    if (window.bootstrap?.Popover) {
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.forEach((el) => {
            if (!window.bootstrap.Popover.getInstance(el)) {
                new window.bootstrap.Popover(el);
            }
        });
    }
});
