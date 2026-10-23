import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import { contrast } from '../public/assets/js/modules/palettes.js';

const source = readFileSync(new URL('../public/assets/js/theme.js', import.meta.url), 'utf8');
function browser({ saved = null, dark = false, blocked = false } = {}) {
    const values = new Map(saved ? [['expernet-appearance', saved]] : []);
    const listeners = {};
    const button = { hidden: true, setAttribute(key, value) { this[key] = value; } };
    const system = { matches: dark, addEventListener(event, callback) { listeners.system = callback; } };
    const document = {
        documentElement: { dataset: {} },
        querySelectorAll() { return [button]; },
        addEventListener(event, callback) { listeners[event] = callback; },
    };
    const window = {
        matchMedia: () => system,
        dispatchEvent() {},
        addEventListener(event, callback) { listeners[event] = callback; },
    };
    const localStorage = {
        getItem(key) { if (blocked) throw Error('Storage denied'); return values.get(key); },
        setItem(key, value) { if (blocked) throw Error('Storage denied'); values.set(key, value); },
    };
    vm.runInNewContext(source, { window, document, localStorage, CustomEvent: class {} });
    return { document, button, system, listeners, values, click() { listeners.click({ target: { closest: () => button } }); } };
}
const automatic = browser({ dark: true });
assert.equal(automatic.document.documentElement.dataset.appearance, 'dark');
automatic.system.matches = false;
automatic.listeners.system();
assert.equal(automatic.document.documentElement.dataset.appearance, 'light');
automatic.click();
assert.equal(automatic.values.get('expernet-appearance'), 'dark');
assert.equal(automatic.button['aria-pressed'], 'true');
automatic.listeners.system();
assert.equal(automatic.document.documentElement.dataset.appearance, 'dark');
assert.equal(browser({ saved: 'light', dark: true }).document.documentElement.dataset.appearance, 'light');
const unavailable = browser({ blocked: true });
unavailable.click();
assert.equal(unavailable.document.documentElement.dataset.appearance, 'dark');
assert.equal(unavailable.button.hidden, false);
automatic.listeners.storage({ key: 'expernet-appearance', newValue: 'light' });
assert.equal(automatic.button['aria-pressed'], 'false');
automatic.listeners.storage({ key: 'expernet-appearance', newValue: null });
automatic.system.matches = true;
automatic.listeners.system();
assert.equal(automatic.document.documentElement.dataset.appearance, 'dark');
for (const text of ['#e4f0f5', '#b2c8d2', '#72c9ec']) {
    for (const background of ['#102530', '#173340', '#234553']) {
        assert.ok(contrast(text, background) >= 4.5, `${text} on ${background}`);
    }
}
console.log('Thème : système, choix persistant, changement système, synchronisation, stockage bloqué et contrastes vérifiés.');
