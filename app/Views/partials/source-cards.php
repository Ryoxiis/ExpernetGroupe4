<div class="campus-cards">
<?php foreach ($items as $item): ?>
    <article class="campus-card">
        <?php if (!empty($item['images'][0])): $image = $item['images'][0]; ?>
            <img src="<?= asset($image['path']) ?>" alt="<?= e($image['alt']) ?>" loading="lazy">
        <?php endif ?>
        <div>
            <span class="eyebrow"><?= $item['kind'] === 'formation' ? 'DIPLÔME EN ALTERNANCE' : 'EXPERNET CAMPUS' ?></span>
            <h3><a href="<?= e(url($item['path'])) ?>"><?= e($item['title']) ?></a></h3>
            <a class="text-link" href="<?= e(url($item['path'])) ?>">Découvrir <span aria-hidden="true">↗</span></a>
        </div>
    </article>
<?php endforeach ?>
</div>
