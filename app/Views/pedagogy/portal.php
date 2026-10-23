<?php
$pageDescriptions = [
    'accueil' => 'Votre journée, vos cours et les prochaines étapes. Tout est ici.',
    'planning' => 'Retrouvez vos séances et préparez votre prochaine intervention.',
    'ressources' => 'Les documents utiles, rassemblés au même endroit.',
    'travaux' => 'Des consignes au rendu, suivez chaque étape.',
    'presences' => 'Un suivi clair pour chaque séance, en classe ou à distance.',
    'resultats' => 'Consultez les résultats publiés et suivez la progression.',
];
?>
<?php
$student = $user['role'] === 'student';
$trainer = $user['role'] === 'trainer';
$admin = $user['role'] === 'admin';
$roleLabel = ['student' => 'Étudiant', 'trainer' => 'Formateur', 'admin' => 'Administration'][$user['role']];
$labels = $config['pages'];
if (!$student) {
    $labels['travaux'] = 'Devoirs et rendus';
    $labels['resultats'] = 'Notes et progression';
}
$nav = ['accueil' => '⌂', 'planning' => '▦', 'ressources' => '▤', 'travaux' => '✎', 'resultats' => '↗', 'presences' => '✓', 'messages' => '⋯', 'parcours' => '◇', 'examens' => '▣'];
if (!$student) {
    $nav = array_slice($nav, 0, 2, true) + ['classes' => '◎'] + array_slice($nav, 2, null, true);
}
if ($admin) {
    $nav['administration'] = '⚙';
}
$fmt = static fn (?string $s, string $f = 'd/m/Y à H:i'): string => $s ? (new DateTimeImmutable($s, new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Indian/Reunion'))->format($f) : 'Non renseigné';
// Les tableaux associent chaque identifiant à son libellé, sans rechercher toute la liste.
$peopleNames = array_column($state['people'], 'name', 'id');
$cohortNames = array_column($state['cohorts'], 'name', 'id');
$sessionNames = array_column($state['sessions'], 'title', 'id');
$assignmentNames = array_column($state['assignments'], 'title', 'id');
$students = array_values(array_filter($state['people'], static fn ($p) => $p['role'] === 'student'));
$trainers = array_values(array_filter($state['people'], static fn ($p) => $p['role'] === 'trainer'));
$begin = static function (string $action, string $return = '') use ($csrf, $page): void { ?>
<form method="post" action="<?= e(url('/espace-pedagogique/action/')) ?>" class="portal-form" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="action" value="<?= e($action) ?>">
    <input type="hidden" name="return" value="<?= e($return ?: $page) ?>"><?php };
$end = static function (string $text = 'Enregistrer'): void { ?>
    <button class="button button-dark" type="submit"><?= e($text) ?> →
    </button>
</form><?php };
$input = static function (string $key, string $label, string $type = 'text', string $value = '', bool $required = true): void {
    static $i = 0;
    $id = 'field-' . ++$i; ?>
<label for="<?= e($id) ?>"><?= e($label) ?>
</label>
<input id="<?= e($id) ?>" name="<?= e($key) ?>" type="<?= e($type) ?>" value="<?= e($value) ?>" <?= $required ? 'required' : '' ?> <?= $type === 'text' ? 'maxlength="180"' : '' ?>><?php };
$area = static function (string $key, string $label, string $value = ''): void {
    static $i = 0;
    $id = 'area-' . ++$i; ?>
<label for="<?= e($id) ?>"><?= e($label) ?>
</label>
<textarea id="<?= e($id) ?>" name="<?= e($key) ?>" rows="4" maxlength="10000" required><?= e($value) ?></textarea><?php };
$select = static function (string $key, string $label, array $options, string $valueKey = 'id', string $labelKey = 'name', bool $optional = false): void {
    static $i = 0;
    $id = 'select-' . ++$i; ?>
<label for="<?= e($id) ?>"><?= e($label) ?>
</label>
<select id="<?= e($id) ?>" name="<?= e($key) ?>" <?= $optional ? '' : 'required' ?>>
    <option value=""><?= $optional ? 'Aucune séance associée' : 'Choisir…' ?>
    </option><?php foreach ($options as $o): ?>
    <option value="<?= e($o[$valueKey]) ?>"><?= e($o[$labelKey]) ?>
    </option><?php endforeach; ?>
</select><?php };
$next = array_values(array_filter($state['sessions'], static fn ($s) => $s['ends_at'] > gmdate('Y-m-d H:i:s')));
$upcoming = $next[0] ?? null;
$statusLabels = ['declared' => 'Déclarée · à valider', 'validated' => 'Présence validée', 'late' => 'Retard', 'absent' => 'Absence', 'excused' => 'Absence justifiée'];
$futureAssignments = array_filter($state['assignments'], static fn ($a) => $a['due_at'] >= gmdate('Y-m-d H:i:s'));
?>
<div class="portal-shell">
    <aside class="portal-sidebar">
        <p class="portal-eyebrow">Mon espace
        </p>
        <p class="portal-role"><?= e($roleLabel) ?>
        </p>
        <nav aria-label="Navigation pédagogique"><?php foreach ($nav as $key => $icon): ?><a href="<?= e(url('/espace-pedagogique/' . ($key === 'accueil' ? '' : $key . '/'))) ?>" <?= $page === $key || ($page === 'seance' && $key === 'planning') ? 'aria-current="page"' : '' ?>><span aria-hidden="true"><?= e($icon) ?></span><?= e($labels[$key]) ?></a><?php endforeach; ?>
        </nav>
        <div class="sidebar-help"><strong>Un besoin, une question ?</strong>
            <p>L’équipe pédagogique vous accompagne.
            </p><a href="<?= e(url('/connexion/aide/')) ?>">Contacter l’administration ↗</a>
        </div><a class="portal-back" href="<?= e(url('/')) ?>">← Retour au site Expernet</a>
    </aside>
    <main id="contenu" class="portal-content">
        <div class="portal-breadcrumb">Espace pédagogique <span aria-hidden="true">/</span> <?= e($labels[$page]) ?>
        </div>
<?php if ($config['demo']): ?>
        <p class="portal-demo">Prototype · données fictives · les présences ne constituent pas une signature électronique ni une attestation officielle.
        </p><?php endif; ?>
<?php if (!$config['mfa']): ?>
        <p class="portal-demo">Double authentification désactivée dans cet environnement de démonstration.
        </p><?php endif; ?>
<?php if ($flash): ?>
        <div class="portal-alert" role="status">
            <p><?= e($flash['message']) ?>
            </p><?php if (isset($flash['temporary_password'])): ?>
            <p><strong>Mot de passe temporaire (affiché une seule fois)</strong>
            </p><code class="temporary-secret"><?= e($flash['temporary_password']) ?></code>
            <p>Expiration : <?= e($fmt($flash['expires_at'])) ?>. Aucun e-mail envoyé. Conservez-le avant de quitter cette page.
            </p><?php endif; ?>
        </div><?php endif; ?>
        <div class="portal-heading">
            <div>
                <p class="portal-eyebrow"><?= e($fmt(gmdate('Y-m-d H:i:s'), 'd/m/Y')) ?> · <?= e($roleLabel) ?>
                </p>
                <h1><?= $page === 'accueil' ? 'Bonjour, ' . e(explode(' ', $user['name'])[0]) . '<span class="hello-dot">.</span>' : e($labels[$page]) ?>
                </h1>
                <p><?= e($pageDescriptions[$page] ?? 'Les informations et les actions utiles à votre parcours.') ?>
                </p>
            </div><?php if ($page === 'accueil'): ?><a class="button button-dark" href="<?= e(url('/espace-pedagogique/planning/')) ?>">Voir mon planning ↗</a><?php endif; ?>
        </div>
<?php
// Le contrôleur et cette liste autorisée protègent le choix du fichier inclus.
if (!array_key_exists($page, $config['pages'])) {
    throw new RuntimeException('Page pédagogique inconnue.');
}
require __DIR__ . '/pages/' . $page . '.php';
?>

        <p class="portal-bottom-note">Expernet · Un espace pour apprendre et avancer ensemble.
        </p>
    </main>
</div>
