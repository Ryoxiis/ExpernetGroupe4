import { url } from './utils.js';

export function initNavigation() {
    const button = document.querySelector('.menu-toggle');
    const navigation = document.querySelector('#navigation');
    if (button && navigation) {
        document.documentElement.classList.add('navigation-ready');
        const group = navigation.querySelector('.navigation-group');
        const closeMenu = () => {
            navigation.classList.remove('open');
            if (group) group.open = false;
            button.setAttribute('aria-expanded', 'false');
            button.setAttribute('aria-label', 'Ouvrir le menu');
        };
        button.addEventListener('click', () => {
            const open = navigation.classList.toggle('open');
            button.setAttribute('aria-expanded', String(open));
            button.setAttribute(
                'aria-label',
                open ? 'Fermer le menu' : 'Ouvrir le menu',
            );
        });
        navigation
            .querySelectorAll('a')
            .forEach((link) => link.addEventListener('click', closeMenu));
        // Un clic hors de l'en-tête referme les menus sans déplacer le focus.
        document.addEventListener('click', (event) => {
            if (!event.target.closest('header')) closeMenu();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;
            if (group?.open) {
                group.open = false;
                group.querySelector('summary').focus();
            } else if (navigation.classList.contains('open')) {
                closeMenu();
                button.focus();
            }
        });
    }
    document.querySelectorAll('[data-devis]').forEach((button) => {
        button.addEventListener('click', () => {
            window.location.href = url('demande-de-devis/');
        });
    });
}
