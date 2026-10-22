<?php
$isCampus = str_starts_with($path, '/campus/');
$isHome = $path === '/campus/';
$sourcePage ??= ['title' => $page['title'], 'blocks' => [], 'images' => [], 'documents' => [], 'kind' => 'page', 'source' => ''];
$campusPages = array_filter($sourcePages, static fn (array $item): bool => str_starts_with($item['path'], '/campus/'));
$displayBlocks = array_values(array_filter($sourcePage['blocks'], static fn (array $block): bool => !in_array($block['tag'], ['h2', 'h3', 'h4', 'h5'], true) || $block['text'] !== $sourcePage['title']));
if ($sourcePage['kind'] === 'formation') {
    $displayBlocks = array_map(static function (array $block): array {
        $block['tag'] = ['h4' => 'h2', 'h5' => 'h3'][$block['tag']] ?? $block['tag'];
        return $block;
    }, $displayBlocks);
}
?>
<?php if ($isCampus): ?><?= $view->render('partials/campus-nav') ?><?php endif ?>
<?php if ($isHome): ?>
<section class="section campus-welcome">
    <div class="wrap campus-split">
        <div>
            <div class="eyebrow">EXPERNET CAMPUS · ALTERNANCE</div>
            <h1>Votre talent.<br>Un métier.<br><em>Le numérique.</em></h1>
            <p class="hero-lead">Du BAC+2 au BAC+5, donnez une nouvelle dimension à votre avenir.</p>
            <p>Informatique, cybersécurité et marketing digital : découvrez les parcours du Campus à La Réunion et à Mayotte.</p>
            <div class="hero-actions"><a class="button button-orange" href="#diplomes">Choisir mon parcours ↗</a><a class="text-link" href="<?= e(url('/campus/inscription/')) ?>">Préparer ma candidature →</a></div>
        </div>
        <div class="campus-welcome-visual"><img class="campus-logo" src="<?= asset('assets/images/campus-logo.png') ?>" alt="Expernet Campus"><img src="<?= asset('assets/images/campus-intro.png') ?>" alt="Expernet Campus, informatique et marketing digital"></div>
    </div>
</section>
<div class="wrap campus-shortcuts"><?php foreach (['/campus/campus/' => 'Notre école', '/campus/notre-equipe/' => 'Notre équipe', '/campus/nos-locaux/' => 'Les locaux', '/campus/nos-partenaires/' => 'Nos partenaires', '/campus/campus-a-mayotte/' => 'Le Campus à Mayotte'] as $destination => $label): ?><a href="<?= e(url($destination)) ?>"><?= e($label) ?> ↗</a><?php endforeach ?></div>
<section class="section" id="diplomes"><div class="wrap"><div class="eyebrow">TROUVEZ VOTRE VOIE</div><h2>Des diplômes, des perspectives.</h2><p>Les 11 parcours du Campus, à La Réunion et à Mayotte.</p><?= $view->render('partials/source-cards', ['items' => array_filter($campusPages, static fn (array $item): bool => $item['kind'] === 'formation')]) ?></div></section>
<?php else: ?>
<section class="page-hero"><div class="wrap">
    <div class="breadcrumbs"><a href="<?= e(url('/')) ?>">Accueil</a><span>/</span><?php if ($isCampus): ?><a href="<?= e(url('/campus/')) ?>">Campus</a><span>/</span><?php endif ?><span><?= e($sourcePage['title']) ?></span></div>
    <div class="eyebrow"><?= $isCampus ? 'EXPERNET CAMPUS' : 'EXPERNET' ?><?= $sourcePage['kind'] === 'formation' ? ' · ALTERNANCE' : '' ?></div>
    <h1><?= e($sourcePage['title']) ?></h1>
    <?php if ($sourcePage['kind'] === 'posts' && $sourcePage['date']): ?><p>Publié le <time datetime="<?= e($sourcePage['date']) ?>"><?= e(date('d/m/Y', strtotime($sourcePage['date']))) ?></time></p><?php endif ?>
    <?php if ($sourcePage['kind'] === 'formation'): ?><p>Votre parcours, ses objectifs, ses débouchés et toutes les informations pour candidater.</p><a class="button button-orange" href="<?= e(url('/campus/inscription/')) ?>">Parlons de votre candidature ↗</a><?php endif ?>
</div></section>
<?php endif ?>
<?php if (str_contains($path, '/remerciement-')): ?><div class="wrap"><p class="source-notice">Cette page présente le contenu de confirmation du site d’origine. Aucune demande n’a été envoyée depuis ce prototype.</p></div><?php endif ?>
<?php if (in_array($path, ['/campus/inscription/', '/campus/contact/'], true)): ?><div class="wrap"><div class="source-notice"><strong>Préparons votre projet.</strong> Les démarches du prototype ne transmettent aucune donnée. Pour contacter l’équipe Campus : <a href="mailto:campus@expernet.re">campus@expernet.re</a> · <a href="tel:+262262219045">02 62 21 90 45</a>. <a href="<?= e($sourcePage['source']) ?>" target="_blank" rel="noopener">Accéder au formulaire officiel ↗</a></div></div><?php endif ?>
<section class="section"><div class="wrap source-reading">
<?php if ($isHome || $sourcePage['kind'] === 'archive'): ?><details class="campus-details"><summary><?= $isHome ? 'Le Campus en détail : école, admissions et partenaires' : 'Consulter les informations et liens de cette rubrique' ?></summary><?php endif ?>
<div class="source-content"><?= $html->render($displayBlocks) ?></div>
<?php if ($sourcePage['documents']): ?><aside class="source-documents"><h2>Documents utiles</h2><?php foreach ($sourcePage['documents'] as $document): $destination = $html->safeUrl($document['href']); if (!$destination) continue; ?><a class="button button-outline" href="<?= e($destination) ?>"><?= e($document['text']) ?> ↗</a><?php endforeach ?></aside><?php endif ?>
<?php if ($isHome || $sourcePage['kind'] === 'archive'): ?></details><?php endif ?>
</div></section>
<?php if ($sourcePage['images']): ?><section class="section campus-gallery"><div class="wrap"><h2>En images</h2><div class="campus-image-grid"><?php foreach ($sourcePage['images'] as $image): ?><figure><img src="<?= asset($image['path']) ?>" alt="<?= e($image['alt']) ?>" loading="lazy"></figure><?php endforeach ?></div></div></section><?php endif ?>
<?php if ($isCampus && ($path === '/campus/blog/' || $isHome || $path === '/campus/actualites-a-mayotte/')): ?><section class="section"><div class="wrap"><div class="eyebrow">LA VIE DU CAMPUS</div><h2>Les dernières actualités</h2><a class="text-link" href="<?= e(url('/campus/blog/')) ?>">Toutes les actualités →</a><?= $view->render('partials/source-cards', ['items' => ($isHome ? array_slice(array_filter($campusPages, static fn (array $item): bool => $item['kind'] === 'posts'), 0, 3) : array_filter($campusPages, static fn (array $item): bool => $item['kind'] === 'posts'))]) ?></div></section><?php endif ?>
<?php if ($path === '/campus/plan-du-site/'): ?><section class="section"><div class="wrap"><h2>Toutes les pages Campus</h2><ul class="campus-directory"><?php foreach ($campusPages as $item): ?><li><a href="<?= e(url($item['path'])) ?>"><?= e($item['title']) ?></a></li><?php endforeach ?></ul></div></section><?php endif ?>
<?php if ($isCampus): ?><section class="section campus-next"><div class="wrap"><h2>Et si on parlait de votre avenir ?</h2><p>Une question sur le diplôme, l’alternance ou votre candidature ? L’équipe Campus vous accompagne.</p><a class="button button-orange" href="<?= e(url('/campus/contact/')) ?>">Contacter le Campus ↗</a> <a class="text-link" href="<?= e(url('/campus/plan-du-site/')) ?>">Toutes les pages Campus →</a></div></section><?php endif ?>
