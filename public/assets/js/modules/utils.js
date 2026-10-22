export function url(path = '') {
    const base = document.documentElement.dataset.base || '/';
    return `${base}${String(path).replace(/^\/+/, '')}`;
}

export function escapeHtml(value) {
    const entities = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
    return String(value).replace(/[&<>"']/g, (character) => entities[character]);
}

export function normalize(value) {
    return String(value).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
}

export async function getCollection(endpoint) {
    const response = await fetch(url(endpoint), { headers: { Accept: 'application/json' } });
    if (!response.ok) {
        throw new Error(`Impossible de charger les données (${response.status}).`);
    }
    const collection = await response.json();
    if (!Array.isArray(collection)) {
        throw new Error('Format de réponse inattendu.');
    }
    return collection;
}

export function showLoadError(container, retry) {
    container.replaceChildren();
    const message = document.createElement('p');
    message.setAttribute('role', 'alert');
    message.className = 'notice';
    message.textContent = 'Les données n’ont pas pu être chargées. Vérifiez votre connexion puis réessayez.';
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'button button-dark';
    button.textContent = 'Réessayer';
    button.addEventListener('click', retry);
    container.append(message, button);
}
