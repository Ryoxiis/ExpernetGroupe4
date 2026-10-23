import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

// Run the production handlers with storage and DOM boundaries replaced.
const source = readFileSync(new URL('../public/assets/js/modules/chat.js', import.meta.url), 'utf8');
const handlers = source.slice(source.indexOf('    function saveDraft()'), source.indexOf('    function renderMessages()'));
const input = source.slice(source.indexOf("    form.addEventListener('input'"), source.indexOf("    form.addEventListener(\n        'submit'"));
const drafts = new Map();
let onInput;
const form = {
    elements: { content: { value: 'premier brouillon' } },
    reset() { this.elements.content.value = ''; },
    addEventListener(event, callback) { assert.equal(event, 'input'); onInput = callback; },
};
const context = vm.createContext({
    active: 1, generation: 0, history: [], renderedMessages: '', pendingKey: 'old',
    form, draftKey: id => `user:7:draft:${id}`,
    sessionStorage: { setItem: (key, value) => drafts.set(key, value), getItem: key => drafts.get(key) },
    host: { classList: { remove() {} } }, thread: { replaceChildren() {} },
    node() {}, persist() {}, show() {}, controls() {},
    q: () => ({ focus() {}, textContent: '' }), async loadHistory() {}, renderInbox() {},
});
vm.runInContext(handlers + input, context);
drafts.set('user:7:draft:2', 'deuxième brouillon');
await vm.runInContext('select(2)', context);
assert.equal(drafts.get('user:7:draft:1'), 'premier brouillon');
assert.equal(form.elements.content.value, 'deuxième brouillon');
form.elements.content.value = 'saisie';
onInput();
assert.equal(drafts.get('user:7:draft:2'), 'saisie');
assert.equal(context.pendingKey, null);
form.elements.content.value = '';
onInput();
assert.equal(drafts.get('user:7:draft:2'), '');
context.active = null;
form.elements.content.value = 'sans conversation';
onInput();
assert.equal(drafts.size, 2);
context.active = 2;
context.sessionStorage = { setItem() { throw Error('quota'); }, getItem() { throw Error('disabled'); } };
assert.doesNotThrow(() => onInput());
await vm.runInContext('select(3)', context);
assert.equal(context.active, 3);
assert.equal(form.elements.content.value, '');
console.log('Brouillons : saisie, changement, restauration, suppression et stockage indisponible vérifiés.');
