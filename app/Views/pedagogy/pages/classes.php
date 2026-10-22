<?php foreach ($state['cohorts'] as $c): ?>
<section class="portal-card">
    <p class="portal-eyebrow"><?= e($c['starts_on']) ?> → <?= e($c['ends_on']) ?>
    </p>
    <h2><?= e($c['name']) ?>
    </h2>
    <div class="people-list"><?php foreach ($students as $person): ?><?php if (array_filter($state['memberships'], static fn ($m) => (int)$m['cohort_id'] === (int)$c['id'] && (int)$m['user_id'] === (int)$person['id'])): ?><span><?= e($person['name']) ?></span><?php endif; ?><?php endforeach; ?>
    </div>
</section><?php endforeach; ?><?php if (!$state['cohorts']): ?>
<p class="portal-empty">Aucune classe affectée. Contactez l’administration.
</p><?php endif; ?>
