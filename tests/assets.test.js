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
    const bytes = fs.readFileSync(file);
    const extension = path.extname(file).toLowerCase();
    const signatures = {
        '.png': () => bytes.subarray(0, 8).equals(Buffer.from([137, 80, 78, 71, 13, 10, 26, 10])),
        '.jpg': () => bytes[0] === 255 && bytes[1] === 216 && bytes[2] === 255,
        '.jpeg': () => bytes[0] === 255 && bytes[1] === 216 && bytes[2] === 255,
        '.gif': () => ['GIF87a', 'GIF89a'].includes(bytes.subarray(0, 6).toString()),
        '.webp': () => bytes.subarray(0, 4).toString() === 'RIFF' && bytes.subarray(8, 12).toString() === 'WEBP',
    };
    if (signatures[extension]) assert.ok(signatures[extension](), `Format image invalide : ${file}`);
    const hash = createHash('sha256').update(bytes).digest('hex');
    assert.ok(!hashes.has(hash), `Images identiques : ${hashes.get(hash)} et ${file}`);
    hashes.set(hash, file);
}
console.log(`${references} références d'images valides ; aucune image identique en double.`);
