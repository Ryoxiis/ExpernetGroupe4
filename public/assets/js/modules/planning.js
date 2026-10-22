import { escapeHtml as e, getCollection, normalize, showLoadError, url } from './utils.js';

export async function initPlanning() {
    const list = document.querySelector('#sessions-list');
    if (!list) return;
    const search = document.querySelector('#session-search');
    const month = document.querySelector('#session-month');
    const count = document.querySelector('#session-count');
    const date = (value, options = {}) => new Date(`${value}T12:00:00`).toLocaleDateString('fr-FR', options);
    let sessions;
    try {
        sessions = await getCollection('api/sessions/');
    } catch (error) {
        showLoadError(list, initPlanning);
        return;
    }
    function render() {
        const query = normalize(search.value);
        const items = sessions.filter((session) => (month.value === 'all' || session.date.startsWith(month.value))
            && normalize(`${session.title} ${session.location}`).includes(query));
        count.textContent = `${items.length} sessions publiées`;
        list.innerHTML = items.map((session) => `<a class="planning-row" href="${e(url(session.path))}">
            <time datetime="${e(session.date)}"><strong>${date(session.date, { day: 'numeric' })}</strong>${date(session.date, { month: 'short', year: 'numeric' })}</time>
            <div><small>${e(session.category)}</small><h2>${e(session.title)}</h2>
                <p>${e(session.location)}${session.end ? ` · Jusqu’au ${date(session.end)}` : ''}</p></div>
            <span>Programme ↗</span></a>`).join('') || '<p class="notice">Aucune session ne correspond à ces critères.</p>';
    }
    search.addEventListener('input', render);
    month.addEventListener('change', render);
    render();
}
