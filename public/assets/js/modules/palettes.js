import { escapeHtml as e, getCollection, showLoadError } from './utils.js';

function luminance(hex) {
    const values = [1, 3, 5]
        .map((index) => parseInt(hex.slice(index, index + 2), 16) / 255)
        .map((value) =>
            value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4,
        );
    return values[0] * 0.2126 + values[1] * 0.7152 + values[2] * 0.0722;
}

export function contrast(a, b) {
    const values = [luminance(a), luminance(b)].sort((x, y) => x - y);
    return (values[1] + 0.05) / (values[0] + 0.05);
}

export async function initPalettes() {
    const buttons = document.querySelectorAll('[data-palette]');
    if (!buttons.length) return;
    const results = document.querySelector('#contrast-results');
    let palettes;
    try {
        palettes = await getCollection('api/palettes/');
    } catch (error) {
        showLoadError(results, initPalettes);
        return;
    }
    function refresh() {
        const selected = document.documentElement.dataset.theme;
        const palette = palettes.find((item) => item.id === selected);
        buttons.forEach((button) => {
            const active = button.dataset.palette === selected;
            button.setAttribute('aria-pressed', String(active));
            button.textContent = active
                ? 'Palette appliquée ✓'
                : 'Essayer cette palette ↗';
        });
        document.querySelectorAll('[data-palette-card]').forEach((card) => {
            card.classList.toggle(
                'selected',
                card.dataset.paletteCard === selected,
            );
        });
        if (!palette) return;
        const pairs = [
            ['Texte principal / blanc', palette.ink, '#FFFFFF'],
            ['Lien bleu / blanc', palette.blue, '#FFFFFF'],
            ['Texte foncé / orange', palette.ink, palette.orange],
            ['Blanc / bleu officiel', '#FFFFFF', '#009DE0'],
        ];
        results.innerHTML = pairs
            .map(([label, a, b]) => {
                const ratio = contrast(a, b);
                return `<div class="contrast-row"><span>${e(label)}</span><strong>${ratio.toFixed(2)}:1</strong>
                <span class="contrast-tag ${ratio >= 4.5 ? 'pass' : 'review'}">${ratio >= 4.5 ? 'AA texte courant' : 'Accent / grand texte'}</span></div>`;
            })
            .join('');
    }
    buttons.forEach((button) =>
        button.addEventListener('click', () =>
            window.setExpernetTheme(button.dataset.palette),
        ),
    );
    window.addEventListener('expernet-theme', refresh);
    refresh();
}
