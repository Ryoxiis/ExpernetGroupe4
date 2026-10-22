<?= $view->render('partials/hero', ['title' => $page['title'], 'kicker' => 'LE NUMÉRIQUE N’ATTEND PAS', 'description' => 'Décryptages, conseils, vie du centre : les publications d’Expernet, dans une lecture plus claire.']) ?>
<section class="section">
    <div class="wrap">
        <div class="article-grid">
            <?php $articles = array_filter($repository->articles(), static fn (array $item): bool => empty($page['category']) || strcasecmp($item['category'], $page['category']) === 0); ?>
            <?php foreach ($articles as $article): ?>
                <article class="article-card">
                    <a href="<?= e(url($article['path'])) ?>"><?php if ($article['image']): ?><img src="<?= asset($article['image']) ?>" alt="<?= e($article['title']) ?>" loading="lazy"><?php endif; ?></a>
                    <div>
                        <small><?= e($article['category']) ?> · <?= e($article['date']) ?></small>
                        <h3><a href="<?= e(url($article['path'])) ?>"><?= e($article['title']) ?></a></h3>
                        <p><?= e(excerpt($article['excerpt'])) ?></p>
                        <a class="text-link" href="<?= e(url($article['path'])) ?>">Lire l’article ↗</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php if (!$articles): ?><p>Aucune publication recensée dans cette catégorie lors du relevé.</p><?php endif; ?>
        <?= $view->render('partials/source', ['source' => 'https://expernet.re' . $path]) ?>
    </div>
</section>
