import { url } from './utils.js';

export function initNavigation() {
    const button = document.querySelector('.menu-toggle');
    const navigation = document.querySelector('#navigation');
    if (button && navigation) {
        const closeMenu = () => {
            navigation.classList.remove('open');
            button.setAttribute('aria-expanded', 'false');
            button.setAttribute('aria-label', 'Ouvrir le menu');
        };
        button.addEventListener('click', () => {
            const open = navigation.classList.toggle('open');
            button.setAttribute('aria-expanded', String(open));
            button.setAttribute('aria-label', open ? 'Fermer le menu' : 'Ouvrir le menu');
        });
        navigation.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeMenu));
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeMenu();
        });
    }
    document.querySelectorAll('[data-devis]').forEach((button) => {
        button.addEventListener('click', () => { window.location.href = url('demande-de-devis/'); });
    });
}
