import { mkdirSync, writeFileSync } from 'node:fs';

// Only routing metadata is deployed; PHP, private files and secrets stay on Alwaysdata.
mkdirSync(new URL('./.vercel/output/static/', import.meta.url), { recursive: true });
writeFileSync(new URL('./.vercel/output/config.json', import.meta.url), JSON.stringify({
    version: 3,
    routes: [{ src: '/(.*)', dest: 'https://expernet.alwaysdata.net/$1' }],
}, null, 2) + '\n');
