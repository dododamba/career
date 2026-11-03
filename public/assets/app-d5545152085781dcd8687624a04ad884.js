import './bootstrap.js';
import './styles/app.css';

/**
 * Suivi des Carrières — Cycle de vie Frontend : Réconciliation Turbo Drive & Metronic 8
 * Ministère du Commerce et de l'Industrie — République du Tchad
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
    // 1. Initialisation unique des écouteurs globaux Metronic 8 (évite la multiplication d'écouteurs)
    if (!window.__ktGlobalHandlersInitialized) {
        window.__ktGlobalHandlersInitialized = true;
        if (window.KTMenu && typeof window.KTMenu.initGlobalHandlers === 'function') {
            try {
                window.KTMenu.initGlobalHandlers();
            } catch (e) {
                console.debug('KTMenu initGlobalHandlers notice:', e);
            }
        }
    }

    // 2. Création d'instances fraîches pour les nouveaux éléments DOM (sans réattacher d'écouteurs globaux)
    if (window.KTMenu && typeof window.KTMenu.createInstances === 'function') {
        try {
            window.KTMenu.createInstances();
        } catch (e) {
            console.debug('KTMenu createInstances notice:', e);
        }
    }

    if (window.KTDrawer && typeof window.KTDrawer.createInstances === 'function') {
        try {
            window.KTDrawer.createInstances();
        } catch (e) {
            console.debug('KTDrawer createInstances notice:', e);
        }
    }

    if (window.KTScroll && typeof window.KTScroll.createInstances === 'function') {
        try {
            window.KTScroll.createInstances();
        } catch (e) {
            console.debug('KTScroll createInstances notice:', e);
        }
    }

    if (window.KTSticky && typeof window.KTSticky.createInstances === 'function') {
        try {
            window.KTSticky.createInstances();
        } catch (e) {
            console.debug('KTSticky createInstances notice:', e);
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

    // 4. Fermeture automatique du menu latéral mobile lors du clic sur un lien
    document.querySelectorAll('#kt_aside .menu-link').forEach((link) => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 992 && window.KTDrawer) {
                const asideEl = document.getElementById('kt_aside');
                if (asideEl) {
                    const drawer = window.KTDrawer.getInstance(asideEl);
                    if (drawer) {
                        drawer.hide();
                    }
                }
            }
        });
    });
});
