import { normalize } from './utils.js';

// Les interactions améliorent les formulaires ; PHP vérifie les droits et enregistre les données.
document.querySelectorAll('[data-password]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.password);
        if (!input) return;
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        button.textContent = visible ? 'Masquer' : 'Afficher';
        button.setAttribute(
            'aria-label',
            `${visible ? 'Masquer' : 'Afficher'} le mot de passe`,
        );
    });
});
document.querySelectorAll('[data-filter]').forEach((input) => {
    input.addEventListener('input', () => {
        const query = normalize(input.value).trim();
        let visible = 0;
        document
            .querySelectorAll(`.${input.dataset.filter}`)
            .forEach((item) => {
                const text = normalize(item.textContent);
                item.hidden = !text.includes(query);
                if (!item.hidden) visible++;
            });
        const empty = document.querySelector('[data-filter-empty]');
        if (empty) empty.hidden = visible !== 0 || query === '';
    });
});
document
    .querySelector('[data-print]')
    ?.addEventListener('click', () => window.print());
