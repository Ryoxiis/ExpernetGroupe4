<?php $stories = require PROJECT_ROOT . '/config/home-stories.php'; ?>
<section class="home-stories" aria-labelledby="stories-title" data-carousel aria-roledescription="carrousel">
    <div class="stories-shell">
        <div class="stories-heading sr-only">
            <div>
                <p class="eyebrow">BIEN PLUS QU’UNE FORMATION</p>
                <h2 id="stories-title">Des compétences.<br><span class="serif">Et des moments qui comptent.</span></h2>
            </div>
            <p>Des projets, des rencontres, des réussites.<br>Découvrez Expernet de l’intérieur.</p>
        </div>
        <div class="stories-slides">
            <?php foreach ($stories as $index => $story): ?>
            <article class="story-slide" data-slide role="group" aria-roledescription="diapositive" aria-label="<?= $index + 1 ?> sur <?= count($stories) ?>">
                <div class="story-image">
                    <img src="<?= asset($story['image']) ?>" alt="<?= e($story['alt']) ?>" loading="lazy" decoding="async" draggable="false" width="1280" height="960">
                </div>
                <div class="story-copy">
                    <p class="story-category"><?= e($story['category']) ?></p>
                    <h3><?= e($story['title']) ?></h3>
                    <p><?= e($story['text']) ?></p>
                    <a class="text-link" href="<?= e(url($story['link'])) ?>"><?= e($story['action']) ?> <span aria-hidden="true">↗</span></a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <div class="stories-controls" data-carousel-controls hidden>
            <div class="stories-arrows">
                <button type="button" data-previous aria-label="Photo précédente"><span aria-hidden="true">←</span></button>
                <span class="stories-counter" data-counter aria-hidden="true">01 / <?= sprintf('%02d', count($stories)) ?></span>
                <button type="button" data-next aria-label="Photo suivante"><span aria-hidden="true">→</span></button>
            </div>
            <div class="stories-dots" role="group" aria-label="Choisir une photo">
                <?php foreach ($stories as $index => $story): ?>
                <button type="button" data-go-to="<?= $index ?>" aria-label="Afficher la photo <?= $index + 1 ?> : <?= e($story['category']) ?>" <?= $index === 0 ? 'aria-current="true"' : '' ?>><span></span></button>
                <?php endforeach; ?>
            </div>
        </div>
        <p class="sr-only" aria-live="polite" aria-atomic="true" data-carousel-status></p>
    </div>
</section>
