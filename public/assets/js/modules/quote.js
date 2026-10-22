import { escapeHtml as e } from './utils.js';

export function initQuote() {
    const form = document.querySelector('#quote-form');
    if (!form) return;
    const course = document.querySelector('#quote-course');
    const prefill = new URLSearchParams(window.location.search).get(
        'formation',
    );
    if (Array.from(course.options).some((option) => option.value === prefill))
        course.value = prefill;

    // This demo deliberately prepares a local summary; no API endpoint sends or stores the request.
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        if (!form.reportValidity()) return;
        const data = Object.fromEntries(new FormData(form));
        const text = `Demande de formation Expernet\n\n${Object.entries(data)
            .map(([key, value]) => `${key} : ${value}`)
            .join('\n')}`;
        const result = document.querySelector('#quote-result');
        const subject = encodeURIComponent(
            `Projet de formation — ${data.formation || 'Conseil'}`,
        );
        result.hidden = false;
        result.innerHTML = `<h2>Votre demande est préparée.</h2>
            <p>Rien n’a été envoyé. Vous pouvez ouvrir votre messagerie ou poursuivre sur le formulaire officiel.</p>
            <pre>${e(text)}</pre>
            <a class="button button-dark" href="mailto:formation@expernet.re?subject=${subject}&body=${encodeURIComponent(text)}">Ouvrir ma messagerie ↗</a>
            <a class="text-link" href="https://expernet.re/demande-de-devis/" target="_blank" rel="noopener">Formulaire officiel ↗</a>`;
        result.scrollIntoView({ block: 'nearest', behavior: 'auto' });
    });
}
