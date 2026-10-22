<section class="page-hero">
    <div class="wrap">
        <div class="breadcrumbs">
            <a href="<?= e(url('/')) ?>">Accueil</a><span>/</span><span><?= e($title) ?></span>
        </div>
        <div class="eyebrow"><?= e($kicker ?? 'EXPERNET · LA RÉUNION & MAYOTTE') ?></div>
        <h1><?= e($title) ?></h1>
        <?php if (!empty($description)): ?><p><?= e($description) ?></p><?php endif; ?>
        <?php if (!empty($tags)): ?>
            <div class="topic-tags"><?php foreach ($tags as $tag): ?><span><?= e($tag) ?></span><?php endforeach; ?></div>
        <?php endif; ?>
    </div>
</section>
