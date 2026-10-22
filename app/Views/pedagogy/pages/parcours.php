<?php foreach ($state['cohorts'] as $c): ?>
<section class="portal-card">
    <h2><?= e($c['name']) ?>
    </h2>
    <p>Du <?= e($c['starts_on']) ?> au <?= e($c['ends_on']) ?>
    </p><a class="portal-link" href="<?= e(url('/espace-pedagogique/planning/')) ?>">Voir les séances et périodes en entreprise →</a>
</section><?php endforeach; ?>
<?php if ($student): ?>
<details class="portal-card">
    <summary>Compléter mon livret d’apprentissage
    </summary><?php $begin('journal');
    $select('cohort_id', 'Promotion', $state['cohorts']);
    $area('body', 'Activités réalisées, compétences travaillées et difficultés');
    $end('Ajouter au livret'); ?>
</details>
<details class="portal-card">
    <summary>Donner mon avis sur ma formation
    </summary>
    <p>Votre avis est identifié, non anonyme. Seule l’administration accède au retour détaillé ; ce prototype en affiche une synthèse chiffrée.
    </p><?php $begin('feedback');
    $select('cohort_id', 'Promotion', $state['cohorts']);
    $select('rating', 'Satisfaction globale', array_map(static fn ($v) => ['id' => $v,'name' => $v . '/5'], range(1, 5)));
    $area('body', 'Ce qui fonctionne et ce que nous pouvons améliorer');
    $end('Enregistrer mon avis'); ?>
</details><?php endif; ?>
<section class="portal-card">
    <h2>Livret d’apprentissage
    </h2><?php foreach ($state['journal'] as $j): ?>
    <article class="portal-note"><strong><?= e(($peopleNames[(int) $j['student_id']] ?? 'Membre de la promotion')) ?> · <?= e($fmt($j['created_at'])) ?></strong>
        <p class="preserve-lines"><?= e($j['body']) ?>
        </p>
    </article><?php endforeach; ?><?php if (!$state['journal']): ?>
    <p>Aucune activité renseignée dans le livret.
    </p><?php endif; ?>
</section>
