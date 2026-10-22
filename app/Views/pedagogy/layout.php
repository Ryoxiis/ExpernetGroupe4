<!doctype html>
<html lang="fr" data-theme="ocean" data-root="<?= e(url('/')) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?= e($title) ?> — Expernet</title>
    <link rel="stylesheet" href="<?= asset('assets/css/base.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/pages.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/pedagogy.css') ?>">
    <?php if ($user): ?><link rel="stylesheet" href="<?= asset('assets/css/chat.css') ?>"><?php endif; ?>
</head>
<body class="pedagogy">
<a class="skip" href="#contenu">Aller au contenu</a>
<header class="portal-header"><a href="<?= e(url('/')) ?>" class="official-brand"><img class="official-logo" src="<?= asset('assets/images/logo-officiel-bleu.png') ?>" alt="Expernet — retour au site"></a><a class="portal-wordmark" href="<?= e(url('/espace-pedagogique/')) ?>">Espace pédagogique</a><div class="portal-header-actions"><?php if ($user): ?><span><?= e($user['name']) ?></span><form action="<?= e(url('/connexion/deconnexion/')) ?>" method="post"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><button class="portal-link" type="submit">Se déconnecter</button></form><?php else: ?><a href="<?= e(url('/')) ?>">← Retour au site</a><?php endif; ?></div></header>
<?= $body ?>
<?php if ($user): ?><?= $view->render('pedagogy/chat', ['user'=>$user,'csrf'=>$csrf]) ?>
<script type="module" src="<?= asset('assets/js/modules/chat.js') ?>"></script><?php endif; ?>
<script type="module" src="<?= asset('assets/js/modules/pedagogy.js') ?>"></script>
</body>
</html>
