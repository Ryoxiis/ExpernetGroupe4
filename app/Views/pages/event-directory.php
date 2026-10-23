<?= $view->render('partials/hero', [
    'title' => 'Les événements Expernet',
    'kicker' => 'RENCONTRES & VIE DU CENTRE',
    'description' => 'Retrouvez les publications événementielles et contactez l’équipe pour connaître les prochains rendez-vous.',
]) ?>
<section class="section">
<div class="wrap">
<div class="notice">La page d’origine expose un annuaire embarqué sans événements récupérables dans son contenu public. Aucun événement à venir n’est inventé dans ce prototype.</div>
<a href="<?= e(url('/categorie/evenements/')) ?>" class="button button-dark">Consulter les publications événementielles ↗</a>
<?= $view->render('partials/source-note', ['source' => 'https://expernet.re/event-directory/']) ?>
</div>
</section>
