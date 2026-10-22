// Progressive enhancement only. Permissions and persistence are handled by PHP.
document.querySelectorAll('[data-password]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.password);
        if (!input) return;
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        button.textContent = visible ? 'Masquer' : 'Afficher';
        button.setAttribute('aria-label', `${visible ? 'Masquer' : 'Afficher'} le mot de passe`);
    });
});
document.querySelectorAll('[data-filter]').forEach((input) => {
    input.addEventListener('input', () => {
        const query = input.value.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLocaleLowerCase('fr').trim();
        let visible = 0;
        document.querySelectorAll(`.${input.dataset.filter}`).forEach((item) => {
            const text = item.textContent.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLocaleLowerCase('fr');
            item.hidden = !text.includes(query);
            if (!item.hidden) visible++;
        });
        const empty = document.querySelector('[data-filter-empty]');
        if (empty) empty.hidden = visible !== 0 || query === '';
    });
});
document.querySelector('[data-print]')?.addEventListener('click', () => window.print());
