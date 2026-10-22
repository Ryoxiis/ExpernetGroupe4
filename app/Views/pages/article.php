<?= $view->render('partials/hero', ['title' => $article['title'], 'kicker' => $article['category']]) ?>
<section class="section">
    <div class="wrap article-layout">
        <div class="article-meta">Publication : <?= e($article['date']) ?> · EXPERNET</div>
        <?php if ($article['image']): ?><img class="article-cover" src="<?= asset($article['image']) ?>" alt="<?= e($article['title']) ?>"><?php endif; ?>
        <article class="prose article-prose">
            <?= $html->render($article['blocks']) ?>
            <?php foreach ($article['inlineImages'] ?? [] as $image): ?>
                <?php if ($image !== $article['image']): ?><img class="article-inline" src="<?= asset($image) ?>" alt="Illustration de l’article" loading="lazy"><?php endif; ?>
            <?php endforeach; ?>
            <?php foreach ($article['videos'] ?? [] as $video): ?><p><a href="<?= e($video) ?>" target="_blank" rel="noopener">Voir la vidéo de l’article ↗</a></p><?php endforeach; ?>
        </article>
        <?= $view->render('partials/source', ['source' => $article['source']]) ?>
        <div class="article-back"><a class="text-link" href="<?= e(url('/nos-actualites/')) ?>">← Toutes les actualités</a></div>
    </div>
</section>
