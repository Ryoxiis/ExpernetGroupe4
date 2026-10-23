<?= $view->render('partials/hero', [
    'title' => 'Donnons forme à votre projet',
    'kicker' => 'UNE DEMANDE SIMPLE, UN ÉCHANGE HUMAIN',
    'description' => 'Individuel, intra-entreprise ou sur mesure : précisez votre besoin pour préparer votre échange avec l’équipe Expernet.',
]) ?>
<section class="section">
<div class="wrap quote-layout">
<noscript><p class="notice">JavaScript est nécessaire pour préparer le récapitulatif local. Vous pouvez aussi contacter directement Expernet.</p></noscript>
<form id="quote-form" class="quote-form" method="post" action="<?= e(url('/demande-de-devis/')) ?>">
<div class="notice">Démonstration : ce formulaire prépare un récapitulatif local. Aucune donnée n’est enregistrée ni envoyée automatiquement.</div>
<div class="form-grid">
<label>Prénom *<input name="prenom" autocomplete="given-name" required>
</label>
<label>Nom *<input name="nom" autocomplete="family-name" required>
</label>
<label>E-mail *<input name="email" type="email" autocomplete="email" required>
</label>
<label>Téléphone<input name="telephone" type="tel" autocomplete="tel">
</label>
<label class="full">Entreprise / organisme<input name="entreprise" autocomplete="organization">
</label>
<label>Votre besoin<select name="format">
<option>Une formation individuelle</option>
<option>Former mon équipe</option>
<option>Un parcours sur mesure</option>
</select>
</label>
<label>Lieu souhaité<select name="lieu">
<option>À préciser</option>
<option>Le Port</option>
<option>Sainte-Clotilde</option>
<option>Mayotte</option>
</select>
</label>
<label class="full">Formation souhaitée<select name="formation" id="quote-course">
<option value="">Je souhaite être conseillé</option>
<?php foreach ($repository->coursesByTitle() as $course): ?>
<option value="<?= e($course['title']) ?>">
<?= e($course['title']) ?>
</option>
<?php endforeach; ?>
</select>
</label>
<label class="full">Votre projet<textarea name="message" rows="5" placeholder="Vos objectifs, votre niveau, vos disponibilités…">
</textarea>
</label>
</div>
<button class="button button-orange" type="submit">Préparer ma demande <span>↗</span>
</button>
<div id="quote-result" hidden role="status">
</div>
</form>
<aside class="quote-aside">
<h2>Un conseil ?<br>
<span class="serif">Parlons-en.</span>
</h2>
<p>Notre équipe vous accompagne pour préciser votre besoin et choisir un parcours adapté.</p>
<a href="tel:+262262219045">02 62 21 90 45</a>
<a href="mailto:formation@expernet.re">formation@expernet.re</a>
<a href="<?= e(url('/notre-equipe/')) ?>" class="text-link">Rencontrer l’équipe ↗</a>
<div class="aside-note">
<strong>Un besoin d’accessibilité ?</strong>
<p>Contactez notre référente handicap, Murielle Chamand : m.chamand@expernet.re.</p>
</div>
</aside>
</div>
</section>
