<?php
$authTitles = [
    'password' => 'Bienvenue chez vous.',
    'mfa' => 'Une dernière vérification.',
    'help' => 'Retrouvons votre accès.',
];
?>
<main id="contenu" class="auth-shell">
    <section class="auth-story">
        <p class="portal-eyebrow">Votre formation, au même endroit
        </p>
        <h1>Un espace pour<br>avancer <em>ensemble.</em>
        </h1>
        <p>Retrouvez vos cours, vos ressources et votre suivi pédagogique dans un seul espace.
        </p>
        <div class="auth-benefits"><span><strong>Votre planning, en un regard</strong></span><span><strong>Vos documents à portée de main</strong></span><span><strong>Un suivi tout au long du parcours</strong></span>
        </div>
        <p class="auth-caption">Expernet · La Réunion & Mayotte
        </p>
    </section>
    <section class="auth-panel" aria-labelledby="auth-title">
        <div class="auth-card">
            <p class="portal-eyebrow">Espace réservé à la communauté Expernet
            </p>
            <h2 id="auth-title"><?= e($authTitles[$stage] ?? 'Heureux de vous retrouver.') ?>
            </h2>
<?php if (isset($error)): ?>
            <div class="portal-alert error" role="alert"><?= e($error) ?>
            </div><?php endif; ?>
<?php if ($stage === 'help'): ?>
            <p>Votre compte est créé par l’administration. Si votre mot de passe est oublié, si l’accès temporaire a expiré ou si vous avez perdu votre application d’authentification, demandez un nouvel accès après vérification de votre identité.
            </p><a class="button button-dark" href="mailto:formation@expernet.re">Contacter l’administration</a><a class="portal-link" href="<?= e(url('/connexion/')) ?>">← Revenir à la connexion</a>
<?php elseif ($stage === 'mfa'): ?>
            <p>Confirmez votre identité avec votre application d’authentification.
            </p>
<?php if ($mfa['setup']): ?>
            <div class="portal-note"><strong>Configurer la double authentification</strong>
                <p>Dans Microsoft Authenticator ou une application compatible, ajoutez un compte avec une clé de configuration. Choisissez un code basé sur le temps (TOTP).
                </p>
                <label for="mfa-key">Clé à saisir dans votre application
                </label>
                <input id="mfa-key" value="<?= e($mfa['secret']) ?>" readonly spellcheck="false">
                <p>Conservez l’accès à cette application. En cas de perte, contactez l’administration.
                </p>
            </div><?php endif; ?>
            <form action="<?= e(url('/connexion/verification/')) ?>" method="post" class="portal-form">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <label for="code">Code à six chiffres
                </label>
                <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required autofocus>
                <button class="button button-dark" type="submit">Vérifier et ouvrir mon espace →
                </button>
            </form>
<?php elseif ($stage === 'password'): ?>
            <p>Votre accès temporaire a été reconnu. Choisissez maintenant votre mot de passe personnel pour continuer.
            </p>
            <form action="<?= e(url('/connexion/premiere-connexion/')) ?>" method="post" class="portal-form">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <label for="password">Nouveau mot de passe
                </label>
                <div class="password-field">
                    <input id="password" name="password" type="password" minlength="12" maxlength="72" autocomplete="new-password" aria-describedby="password-help" required>
                    <button type="button" data-password="password" aria-label="Afficher le mot de passe">Afficher
                    </button>
                </div>
                <p id="password-help" class="portal-help">Au moins 12 caractères. Une phrase de passe est plus facile à retenir. Maximum 72 octets.
                </p>
                <label for="confirmation">Confirmer mon mot de passe
                </label>
                <input id="confirmation" name="confirmation" type="password" minlength="12" maxlength="72" autocomplete="new-password" required>
                <button class="button button-dark" type="submit">Enregistrer mon mot de passe →
                </button>
            </form>
<?php else: ?>
            <p>Connectez-vous avec votre adresse Expernet et le mot de passe de votre espace pédagogique.
            </p>
            <form action="<?= e(url('/connexion/')) ?>" method="post" class="portal-form">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <label for="email">Adresse e-mail Expernet
                </label>
                <input id="email" name="email" type="email" autocomplete="username" placeholder="prenom.nom@<?= e($config['email_domain']) ?>" maxlength="190" required>
                <label for="password">Mot de passe
                </label>
                <div class="password-field">
                    <input id="password" name="password" type="password" autocomplete="current-password" maxlength="256" required>
                    <button type="button" data-password="password" aria-label="Afficher le mot de passe">Afficher
                    </button>
                </div><a class="portal-forgot" href="<?= e(url('/connexion/aide/')) ?>">Mot de passe oublié ou première connexion ?</a>
                <button class="button button-dark" type="submit">Accéder à mon espace →
                </button>
            </form>
            <p class="portal-note">Première visite ? Utilisez le mot de passe temporaire remis par l’administration. Vous choisirez ensuite votre mot de passe personnel. Aucun compte ne peut être créé depuis cette page.
            </p>
<?php endif; ?>
            <p class="auth-footnote">Ce mot de passe est propre au site pédagogique, indépendant de votre messagerie Microsoft 365.
            </p>
        </div>
    </section>
</main>
