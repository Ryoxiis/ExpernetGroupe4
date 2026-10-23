/* Loaded synchronously before CSS to avoid flashing the wrong palette. */
(() => {
    'use strict';
    const allowed = ['signature', 'ocean', 'lumiere', 'expert'];
    const storageKey = 'expernet-starter-palette';
    let selected = 'ocean';
    try {
        const saved = localStorage.getItem(storageKey);
        if (allowed.includes(saved)) selected = saved;
    } catch (error) {
        // The default remains usable when browser storage is disabled.
    }
    document.documentElement.dataset.theme = selected;
    window.setExpernetTheme = (id) => {
        if (!allowed.includes(id)) return;
        document.documentElement.dataset.theme = id;
        try {
            localStorage.setItem(storageKey, id);
        } catch (error) {
            /* Storage is optional. */
        }
        window.dispatchEvent(new CustomEvent('expernet-theme', { detail: id }));
    };
})();

/* Appearance is independent from the color palette chosen in the workshop. */
(() => {
    'use strict';
    const key = 'expernet-appearance';
    const system = window.matchMedia('(prefers-color-scheme: dark)');
    let preference = null;
    try {
        const saved = localStorage.getItem(key);
        if (saved === 'light' || saved === 'dark') preference = saved;
    } catch (error) {
        // Browser storage is optional; the control still works for this page.
    }
    const apply = () => {
        const dark = preference ? preference === 'dark' : system.matches;
        document.documentElement.dataset.appearance = dark ? 'dark' : 'light';
        document.querySelectorAll('[data-appearance-toggle]').forEach(button => {
            button.hidden = false;
            button.setAttribute('aria-pressed', String(dark));
        });
    };
    apply();
    system.addEventListener('change', apply);
    window.addEventListener('storage', event => {
        if (event.key !== key && event.key !== null) return;
        preference = event.newValue === 'dark' || event.newValue === 'light' ? event.newValue : null;
        apply();
    });
    document.addEventListener('DOMContentLoaded', apply, { once: true });
    document.addEventListener('click', event => {
        if (!event.target.closest('[data-appearance-toggle]')) return;
        preference = document.documentElement.dataset.appearance === 'dark' ? 'light' : 'dark';
        try {
            localStorage.setItem(key, preference);
        } catch (error) {
            // Keep the user's choice in memory when persistence is unavailable.
        }
        apply();
    });
})();
