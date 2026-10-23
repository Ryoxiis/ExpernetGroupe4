import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';
const source = readFileSync(new URL('../public/assets/js/modules/chat.js', import.meta.url), 'utf8');
const handler = source.slice(source.indexOf('    async function loadHistory('), source.indexOf('    let readTimer;'));
const cutoff = '2026-10-22 12:00:00';
const context = vm.createContext({
    active: 1, generation: 0, history: [{ id: 1, created_at: '2026-10-22 11:59:59' }, { id: 2, created_at: cutoff }, { id: 3, created_at: '2026-10-22 12:00:01' }],
    policy: {}, inbox: null, renderedMessages: '',
    api: async () => ({ messages: [], meetings: [], policy: {}, expires_before: cutoff, has_more: false }),
    q: () => ({ replaceChildren() {}, textContent: '', hidden: false }),
    renderMessages() {}, controls() {}, async markRead() {},
});
vm.runInContext(handler, context);
await vm.runInContext('loadHistory()', context);
assert.deepEqual(Array.from(context.history, m => m.id), [3]);
console.log('Chat : les messages expirés déjà chargés disparaissent même sans nouveau message.');
