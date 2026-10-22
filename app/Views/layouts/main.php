<!doctype html>
<html lang="fr" data-root="<?= e(url('/')) ?>" data-base="<?= e(url('/')) ?>">
<head>
    <meta charset="utf-8">
    <link rel="preload" href="<?= asset('assets/fonts/inter-variable.woff2') ?>" as="font" type="font/woff2" crossorigin>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e($page['title']) ?> — Prototype de refonte Expernet.">
    <title><?= e($page['title']) ?> — Expernet</title>
    <script src="<?= asset('assets/js/theme.js') ?>"></script>
    <link rel="stylesheet" href="<?= asset('assets/vendor/bootstrap/bootstrap.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/base.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/pages.css') ?>">
</head>
<body>
    <?= $view->render('partials/header') ?>
    <main id="contenu">
        <?= $body ?>
    </main>
    <?= $view->render('partials/footer') ?>
    <a class="palette-shortcut" href="<?= e(url('/palettes/')) ?>" aria-label="Comparer les palettes de couleurs">
        <span class="palette-dots" aria-hidden="true"><i></i><i></i><i></i></span> Couleurs
    </a>
    <script type="module" src="<?= asset('assets/js/main.js') ?>"></script>
</body>
</html>
