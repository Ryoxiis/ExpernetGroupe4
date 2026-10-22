<a class="skip" href="#contenu">Aller au contenu</a>
<div class="topbar">
    <div class="wrap">
        <span>Le numérique évolue. Vous aussi.</span>
        <span><a href="tel:+262262219045">02 62 21 90 45</a><span class="top-divider">|</span>La Réunion & Mayotte</span>
    </div>
</div>
<header>
    <div class="wrap header-inner">
        <a href="<?= e(url('/')) ?>" class="brand official-brand">
            <img src="<?= asset('assets/images/logo-officiel-bleu.png') ?>" alt="Expernet — retour à l’accueil" class="official-logo">
        </a>
        <button class="menu-toggle" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="navigation">☰</button>
        <nav id="navigation" aria-label="Navigation principale">
            <a href="<?= e(url('/formation/')) ?>">Nos formations</a>
            <a href="<?= e(url('/campus/')) ?>">Campus · Alternance</a>
            <a href="<?= e(url('/planning/')) ?>">Calendrier des formations</a>
            <details class="navigation-group">
                <summary>Expernet</summary>
                <div class="navigation-submenu">
                    <a href="<?= e(url('/notre-histoire/')) ?>">À propos</a>
                    <a href="<?= e(url('/notre-equipe/')) ?>">L’équipe</a>
                    <a href="<?= e(url('/nos-actualites/')) ?>">Actualités</a>
                </div>
            </details>
            <a class="navigation-contact" href="<?= e(url('/demande-de-devis/')) ?>">Demander un devis</a>
        </nav>
        <a href="<?= e(url('/espace-pedagogique/')) ?>" class="pedagogy-access">Espace pédagogique <span aria-hidden="true">↗</span></a>
        <a href="<?= e(url('/demande-de-devis/')) ?>" class="button button-dark header-cta">Demander un devis <span aria-hidden="true">↗</span></a>
    </div>
</header>
