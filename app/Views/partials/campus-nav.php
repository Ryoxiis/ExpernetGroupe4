<nav class="campus-nav" aria-label="Explorer Expernet Campus">
    <div class="wrap">
        <a class="campus-nav-brand" href="<?= e(url('/campus/')) ?>">Expernet <strong>Campus</strong></a>
        <?php foreach (['/campus/informatique/' => 'Informatique', '/campus/marketing/' => 'Marketing digital', '/campus/campus-a-mayotte/' => 'Mayotte', '/campus/campus/' => 'L’école', '/campus/inscription/' => 'Candidater', '/campus/faq/' => 'FAQ', '/campus/blog/' => 'Actualités', '/campus/contact/' => 'Contact'] as $destination => $label): ?>
            <a href="<?= e(url($destination)) ?>"><?= e($label) ?></a>
        <?php endforeach ?>
    </div>
</nav>
