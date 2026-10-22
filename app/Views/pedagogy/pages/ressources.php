<div class="portal-toolbar">
    <label for="resource-search">Rechercher un document
    </label>
    <input id="resource-search" type="search" data-filter="resource-item" placeholder="Titre, promotion…">
</div>
<?php if (!$student): ?>
<details class="portal-card">
    <summary>Partager un document
    </summary>
    <p>PDF, texte, PNG ou JPG · 5 Mo maximum. Le fichier restera dans l’espace privé.
    </p><?php $begin('resources');
    $select('cohort_id', 'Promotion', $state['cohorts']);
    $select('session_id', 'Séance associée (facultatif)', $state['sessions'], 'id', 'title', true);
    $input('title', 'Titre du document');
    $input('document', 'Fichier à partager', 'file');
    $end('Partager le document'); ?>
</details><?php endif; ?>
<?php foreach ($state['resources'] as $r): ?>
<article class="portal-card resource-item">
    <p class="portal-eyebrow"><?= e(($cohortNames[(int) $r['cohort_id']] ?? 'Promotion')) ?> · <?= e($fmt($r['created_at'], 'd/m/Y')) ?>
    </p>
    <h2><?= e($r['title']) ?>
    </h2>
    <p><?= e($r['original_name']) ?>
    </p><a class="portal-link" href="<?= e(url('/espace-pedagogique/document/?id=' . $r['id'])) ?>">Télécharger le document ↓</a>
</article><?php endforeach; ?><?php if (!$state['resources']): ?>
<p class="portal-empty">Aucun document partagé pour le moment. Les supports déposés pour vos promotions apparaîtront ici.
</p><?php endif; ?>
<p class="portal-empty" data-filter-empty hidden>Aucun document ne correspond à votre recherche.
</p>
