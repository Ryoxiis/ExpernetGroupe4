<?= $view->render('partials/hero', [
    'title' => 'Votre prochain rendez-vous',
    'kicker' => 'LE PLANNING DES FORMATIONS',
    'description' => 'Explorez les sessions publiées. Ce planning est un relevé au 22 octobre 2026 : la disponibilité doit être confirmée par l’équipe.',
]) ?>
<section class="section">
<div class="wrap">
<div class="catalog-controls">
<label>Rechercher une session<input id="session-search" type="search" placeholder="Nom de formation ou lieu">
</label>
<label>Mois<select id="session-month">
<option value="all">Tous les mois</option>
<option value="2026-10">2026-10</option>
<option value="2026-11">2026-11</option>
<option value="2026-12">2026-12</option>
</select>
</label>
</div>
<p class="muted" id="session-count" aria-live="polite">
</p>
<div class="planning-table" id="sessions-list">
</div>
<?= $view->render('partials/source-note', ['source' => 'https://expernet.re/planning/']) ?>
</div>
</section>
