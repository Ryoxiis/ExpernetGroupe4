<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e($status) ?> - Expernet</title>
    <script src="<?= asset('assets/js/theme.js') ?>"></script>
    <link rel="stylesheet" href="<?= asset('assets/css/base.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/pages.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/appearance.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/responsive.css') ?>">
</head>
<body>
    <main class="wrap section">
        <?= $view->render('partials/appearance') ?>
        <h1><?= e($message) ?></h1>
        <p><a class="text-link" href="<?= e(url('/')) ?>">Revenir à l’accueil</a></p>
    </main>
</body>
</html>
