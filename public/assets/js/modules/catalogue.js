import {
    escapeHtml as e,
    getCollection,
    normalize,
    showLoadError,
    url,
} from './utils.js';

const FEATURED = [
    'ia-decouverte-et-automatisation',
    'copilot-pour-microsoft-365-2',
    'word-initiation',
    'fortinet-fortigate-niveau-1',
    'microsoft-365-outils-collaboratifs',
    'adobe-photoshop-initiation',
];
const OFFICE_THEMES = [
    'Google Workspace',
    'Microsoft Office',
    'Libre Office',
    'Access',
];
const CREATIVE_THEMES = ['PAO', 'Communication digitale', 'CAO'];

function courseCard(course) {
    const image = course.image
        ? `<img src="${e(url(course.image))}" alt="" loading="lazy">`
        : `<div class="card-art">${e(course.category.slice(0, 2))}</div>`;
    const href = e(url(course.path));
    return `<article class="course-card">
        <a class="course-image" href="${href}">${image}<span class="tag">${e(course.category)}</span></a>
        <div class="course-body">
            <h3><a href="${href}">${e(course.title)}</a></h3>
            <p>${e(course.audience)}</p>
            <div class="course-bottom"><span>${e(course.duration)}</span>
                <a class="text-link" href="${href}" aria-label="Voir le programme : ${e(course.title)}">Voir le programme <span>↗</span></a>
            </div>
        </div>
    </article>`;
}

export function initCatalogue() {
    const grid = document.querySelector('#course-grid');
    const input = document.querySelector('#course-search');
    if (!grid || !input) return;

    const form = document.querySelector('#search-form');
    const themeSelect = document.querySelector('#theme-filter');
    const sortSelect = document.querySelector('#sort-filter');
    const pagination = document.querySelector('#pagination');
    const count = document.querySelector('#result-count');
    const empty = document.querySelector('#empty-state');
    const mode = grid.dataset.mode || 'catalogue';
    const pageSize = mode === 'home' ? 6 : 12;
    const state = { courses: [], theme: themeSelect?.value || 'all', page: 1 };
    input.value = new URLSearchParams(window.location.search).get('q') || '';

    function matchesTheme(course) {
        return (
            state.theme === 'all' ||
            course.categories.includes(state.theme) ||
            (state.theme === 'Bureautique' &&
                course.categories.some((theme) =>
                    OFFICE_THEMES.includes(theme),
                )) ||
            (state.theme === 'Création digitale' &&
                course.categories.some((theme) =>
                    CREATIVE_THEMES.includes(theme),
                ))
        );
    }

    function render() {
        const query = normalize(input.value.trim());
        const courses = state.courses.filter(
            (course) =>
                matchesTheme(course) &&
                normalize(
                    [
                        course.title,
                        course.categories.join(' '),
                        course.audience,
                    ].join(' '),
                ).includes(query),
        );
        courses.sort((a, b) => {
            if (mode === 'home' && !query && state.theme === 'all') {
                const rank = (course) =>
                    FEATURED.includes(course.slug)
                        ? FEATURED.indexOf(course.slug)
                        : 999;
                return rank(a) - rank(b);
            }
            return sortSelect?.value === 'duration'
                ? parseFloat(a.duration) - parseFloat(b.duration)
                : a.title.localeCompare(b.title, 'fr');
        });
        const pages = Math.max(1, Math.ceil(courses.length / pageSize));
        state.page = Math.max(1, Math.min(state.page, pages));
        const visible = courses.slice(
            (state.page - 1) * pageSize,
            state.page * pageSize,
        );
        grid.innerHTML = visible.map(courseCard).join('');
        count.textContent =
            mode === 'home'
                ? `${visible.length} formations à découvrir · ${courses.length} au catalogue`
                : `${courses.length} formation${courses.length === 1 ? '' : 's'}`;
        empty.hidden = courses.length > 0;
        if (pagination) {
            pagination.innerHTML =
                pages > 1
                    ? `<button type="button" class="button button-dark" data-page="${state.page - 1}" ${state.page === 1 ? 'disabled' : ''}>← Précédent</button>
                   <span>Page ${state.page} sur ${pages}</span>
                   <button type="button" class="button button-dark" data-page="${state.page + 1}" ${state.page === pages ? 'disabled' : ''}>Suivant →</button>`
                    : '';
        }
    }

    const resetPage = () => {
        state.page = 1;
        render();
    };
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        resetPage();
    });
    input.addEventListener('input', resetPage);
    themeSelect?.addEventListener('change', () => {
        state.theme = themeSelect.value;
        resetPage();
    });
    sortSelect?.addEventListener('change', resetPage);
    document.querySelector('#clear-filters')?.addEventListener('click', () => {
        state.theme = 'all';
        if (themeSelect) themeSelect.value = 'all';
        input.value = '';
        resetPage();
    });
    document.querySelectorAll('[data-filter]').forEach((button) => {
        button.addEventListener('click', () => {
            state.theme = button.dataset.filter;
            document.querySelectorAll('[data-filter]').forEach((item) => {
                item.classList.toggle('active', item === button);
                item.setAttribute('aria-pressed', String(item === button));
            });
            resetPage();
        });
    });
    pagination?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-page]');
        if (!button || button.disabled) return;
        state.page = Number(button.dataset.page);
        render();
        document
            .querySelector('.catalog-controls')
            ?.scrollIntoView({ block: 'start', behavior: 'auto' });
    });

    async function load() {
        count.textContent = 'Chargement des formations…';
        grid.setAttribute('aria-busy', 'true');
        try {
            state.courses = await getCollection('api/formations/');
            render();
        } catch (error) {
            count.textContent = 'Catalogue indisponible';
            showLoadError(grid, load);
        } finally {
            grid.setAttribute('aria-busy', 'false');
        }
    }
    load();
}
