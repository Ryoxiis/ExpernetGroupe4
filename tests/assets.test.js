import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { createHash } from 'node:crypto';

const root = path.resolve(import.meta.dirname, '..');
function files(directory) {
    return fs.readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
        const file = path.join(directory, entry.name);
        return entry.isDirectory() ? files(file) : [file];
    });
}

// Après regroupement des images, chaque référence locale doit encore exister.
let references = 0;
for (const directory of ['app', 'data', 'public/assets/js', 'public/assets/css']) {
    for (const file of files(path.join(root, directory))) {
        if (!/\.(php|json|js|css)$/.test(file)) continue;
        const source = fs.readFileSync(file, 'utf8').replaceAll('\\/', '/');
        for (const match of source.matchAll(/assets\/images\/[a-zA-Z0-9_./-]+\.(?:png|jpe?g|webp|svg|gif)/g)) {
            assert.ok(fs.existsSync(path.join(root, 'public', match[0])), `Image manquante : ${match[0]} dans ${file}`);
            references++;
        }
    }
}
const hashes = new Map();
for (const file of files(path.join(root, 'public/assets/images'))) {
    const hash = createHash('sha256').update(fs.readFileSync(file)).digest('hex');
    assert.ok(!hashes.has(hash), `Images identiques : ${hashes.get(hash)} et ${file}`);
    hashes.set(hash, file);
}
console.log(`${references} références d'images valides ; aucune image identique en double.`);
