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
