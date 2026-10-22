<?= $view->render('partials/hero', ['title' => $page['title'], 'kicker' => 'LE BON SAVOIR, AU BON MOMENT', 'description' => '149 formations pour développer vos compétences en informatique et en marketing digital. Explorez les programmes et trouvez le parcours adapté à votre objectif.']) ?>
<section class="section">
    <div class="wrap">
        <form id="search-form" class="search-form" role="search">
            <span class="search-icon" aria-hidden="true">⌕</span>
            <label class="sr-only" for="course-search">Rechercher une formation</label>
            <input id="course-search" type="search" placeholder="Excel, cybersécurité, IA, développement…">
            <button class="button button-dark" type="submit">Rechercher <span>→</span></button>
        </form>
        <div class="catalog-controls">
            <label>Thématique
                <select id="theme-filter">
                    <option value="all">Toutes les thématiques</option>
                    <?php foreach ($repository->themes() as $theme): ?>
                        <option value="<?= e($theme['name']) ?>" <?= ($page['theme'] ?? '') === $theme['name'] ? 'selected' : '' ?>><?= e($theme['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Trier par
                <select id="sort-filter"><option value="title">Nom de la formation</option><option value="duration">Durée croissante</option></select>
            </label>
            <button id="clear-filters" class="text-link" type="button">Réinitialiser ↺</button>
        </div>
        <div class="theme-links">
            <?php foreach ($repository->themes() as $theme): ?>
                <?php if (in_array($theme['name'], ['Bureautique', 'Intelligence artificielle', 'Cybersécurité', 'Communication digitale', 'Langages et développement'], true)): ?>
                    <a href="<?= e(url($theme['path'])) ?>"><?= e($theme['name']) ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <div class="results-heading"><span id="result-count" aria-live="polite"></span><span>Catalogue Expernet · relevé au 22/10/2026</span></div>
        <div class="course-grid" id="course-grid" data-mode="catalogue"></div>
        <p id="empty-state" hidden>Aucune formation ne correspond. Essayez un autre terme ou réinitialisez les filtres.</p>
        <noscript><p>Le filtre interactif nécessite JavaScript. Les programmes restent accessibles depuis le <a href="<?= e(url('/plan-du-site/')) ?>">plan du site</a>.</p></noscript>
        <div id="pagination" class="pagination-bar"></div>
        <?= $view->render('partials/source', ['source' => 'https://expernet.re' . $path]) ?>
    </div>
</section>
