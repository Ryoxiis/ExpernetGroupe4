import assert from 'node:assert/strict';
import { escapeHtml, normalize } from '../public/assets/js/modules/utils.js';
import { contrast } from '../public/assets/js/modules/palettes.js';

assert.equal(normalize('Cybersécurité'), 'cybersecurite');
assert.equal(escapeHtml('<img onerror="x">'), '&lt;img onerror=&quot;x&quot;&gt;');
assert.equal(contrast('#FFFFFF', '#FFFFFF'), 1);
assert.ok(Math.abs(contrast('#000000', '#FFFFFF') - 21) < 0.001);
assert.ok(contrast('#07354A', '#F0A029') >= 4.5);
console.log('5 contrôles JavaScript réussis.');
