<?php if ($trainer): ?>
<details class="portal-card">
    <summary>Créer un devoir
    </summary><?php $begin('assignments');
    $select('cohort_id', 'Promotion', $state['cohorts']);
    $select('session_id', 'Séance associée (facultatif)', $state['sessions'], 'id', 'title', true);
    $input('title', 'Titre du devoir');
    $area('description', 'Consignes');
    $input('due_at', 'Date limite de rendu', 'datetime-local');
    $end('Créer le devoir'); ?>
</details><?php endif; ?>
<?php foreach ($state['assignments'] as $a): ?>
<section class="portal-card">
    <p class="portal-eyebrow"><?= e($a['cohort_name']) ?> · échéance <?= e($fmt($a['due_at'])) ?>
    </p>
    <h2><?= e($a['title']) ?>
    </h2>
    <p class="preserve-lines"><?= e($a['description']) ?>
    </p><?php if ($student): ?><?php $mine = array_values(array_filter($state['submissions'], static fn ($s) => (int)$s['assignment_id'] === (int)$a['id']));
        if ($mine): ?>
    <p class="portal-pill">Rendu enregistré le <?= e($fmt($mine[0]['submitted_at'])) ?>
    </p><?php endif;
        if ($a['due_at'] >= gmdate('Y-m-d H:i:s')): ?><?php $begin('submissions'); ?>
    <input type="hidden" name="assignment_id" value="<?= e($a['id']) ?>"><?php $area('body', 'Mon rendu (texte ou lien HTTPS vers mon travail)', $mine[0]['body'] ?? '');
            $end($mine ? 'Mettre à jour mon rendu' : 'Déposer mon travail'); ?><?php else: ?>
    <p>L’échéance est passée. Contactez le formateur si nécessaire.
    </p><?php endif; ?><?php else: ?><?php foreach ($state['submissions'] as $submission): if ((int)$submission['assignment_id'] !== (int)$a['id']) {
        continue;
    } ?>
    <details class="portal-note">
        <summary><?= e(($peopleNames[(int) $submission['student_id']] ?? 'Membre de la promotion')) ?> · rendu du <?= e($fmt($submission['submitted_at'])) ?>
        </summary>
        <p class="preserve-lines"><?= e($submission['body']) ?>
        </p>
    </details><?php endforeach; ?><a class="portal-link" href="<?= e(url('/espace-pedagogique/resultats/')) ?>">Saisir les notes →</a><?php endif; ?>
</section><?php endforeach; ?><?php if (!$state['assignments']): ?>
<p class="portal-empty">Aucun devoir disponible pour vos affectations actuelles.
</p><?php endif; ?>
