<?= $view->render('partials/hero', ['title' => $course['title'], 'kicker' => $course['category'], 'description' => $course['audience'], 'tags' => $course['categories']]) ?>
<section class="section">
    <div class="wrap detail-layout">
        <aside class="course-sidebar" aria-labelledby="course-summary-title">
            <div class="sticky-card">
                <h2 class="eyebrow" id="course-summary-title">L’ESSENTIEL</h2>
                <dl>
                    <div><dt>Durée</dt><dd><?= e($course['duration']) ?></dd></div>
                    <div><dt>Tarif relevé sur le site</dt><dd><?= e($course['price']) ?></dd></div>
                    <div><dt>Prochaine session annoncée</dt><dd><?= e($course['nextSession'] ?: 'Nous contacter') ?></dd></div>
                </dl>
                <a class="button button-orange" href="<?= e(url('/demande-de-devis/?formation=' . rawurlencode($course['title']))) ?>">Demander un devis <span>↗</span></a>
                <?php if ($course['pdf']): ?>
                    <a class="text-link" href="<?= e($course['pdf']) ?>" target="_blank" rel="noopener">Programme PDF officiel ↗</a>
                <?php endif; ?>
                <p class="demo-note">Tarif et disponibilité à confirmer. Le PDF est celui lié depuis la fiche officielle.</p>
                <a class="text-link" href="#course-programme">Lire le programme <span aria-hidden="true">↓</span></a>
                <a class="sidebar-phone" href="tel:+262262219045">02 62 21 90 45</a>
            </div>
        </aside>
        <article class="prose course-program">
            <div class="eyebrow">VOTRE PARCOURS EN DÉTAIL</div>
            <h2 id="course-programme">Objectifs & programme</h2>
            <?= $html->render(array_filter($course['blocks'], static fn (array $block): bool => !str_starts_with($block['text'], 'Public :') && !str_starts_with($block['text'], 'Prochaine session'))) ?>
        </article>

    </div>
</section>
<?php if ($course['sessions']): ?>
    <section class="section course-sessions">
        <div class="wrap">
            <div class="eyebrow">POUR ORGANISER VOTRE PARCOURS</div>
            <h2>Sessions publiées</h2>
            <p class="muted">Relevé du site source : dates et lieux à confirmer. Une incohérence éventuelle du calendrier d’origine doit être vérifiée auprès d’Expernet.</p>
            <div class="table-scroll">
                <table>
                    <thead><tr><?php foreach ($course['sessions'][0] as $cell): ?><th><?= e($cell) ?></th><?php endforeach; ?></tr></thead>
                    <tbody>
                        <?php foreach (array_slice($course['sessions'], 1) as $row): ?>
                            <tr><?php foreach ($row as $cell): ?><td><?= e($cell) ?></td><?php endforeach; ?></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
<?php endif; ?>
<section class="section related-section">
    <div class="wrap">
        <div class="section-heading"><h2>Pour aller <span class="serif">plus loin.</span></h2><a class="text-link" href="<?= e(url('/formation/')) ?>">Toutes les formations ↗</a></div>
        <div class="course-grid">
            <?php $related = array_filter($repository->courses(), static fn (array $item): bool => $item['id'] !== $course['id'] && count(array_intersect($item['categories'], $course['categories'])) > 0); ?>
            <?php foreach (array_slice(array_values($related), 0, 3) as $item): ?>
                <?= $view->render('partials/course-card', ['course' => $item]) ?>
            <?php endforeach; ?>
        </div>
        <?= $view->render('partials/source', ['source' => $course['source']]) ?>
    </div>
</section>
