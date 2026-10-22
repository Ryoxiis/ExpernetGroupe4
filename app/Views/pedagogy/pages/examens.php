<?php if ($admin): ?>
<details class="portal-card">
    <summary>Planifier un examen et composer son jury
    </summary><?php $begin('exams');
    $select('cohort_id', 'Promotion', $state['cohorts']);
    $input('title', 'Intitulé de l’examen');
    $input('starts_at', 'Date et heure', 'datetime-local');
    $input('location', 'Salle ou lieu'); ?>
    <fieldset><legend>Jury : formateurs affectés à cette promotion</legend><?php foreach ($trainers as $t): ?>
        <label class="check-label">
            <input type="checkbox" name="jury[]" value="<?= e($t['id']) ?>"><?= e($t['name']) ?>
        </label><?php endforeach; ?>
    </fieldset><?php $area('criteria', 'Compétences évaluées (une par ligne)');
    $end('Planifier l’examen'); ?>
</details><?php endif; ?>
<?php foreach ($state['exams'] as $exam): ?>
<section class="portal-card">
    <p class="portal-eyebrow"><?= e(($cohortNames[(int) $exam['cohort_id']] ?? 'Promotion')) ?> · <?= e($fmt($exam['starts_at'])) ?>
    </p>
    <h2><?= e($exam['title']) ?>
    </h2>
    <p><?= e($exam['location']) ?>
    </p>
    <p>Jury : <?= e(implode(', ', array_column(array_filter($state['jury'], static fn ($j) => (int)$j['exam_id'] === (int)$exam['id']), 'name'))) ?>
    </p><?php foreach ($state['criteria'] as $criterion): if ((int)$criterion['exam_id'] !== (int)$exam['id']) {
        continue;
    } ?>
    <details class="portal-note">
        <summary><?= e($criterion['label']) ?>
        </summary><?php foreach ($state['evaluations'] as $evaluation): if ((int)$evaluation['competency_id'] !== (int)$criterion['id']) {
            continue;
        } ?>
        <p><?= e(($peopleNames[(int) $evaluation['student_id']] ?? 'Membre de la promotion')) ?> · <?= e(['not_acquired' => 'Non acquis','developing' => 'En cours d’acquisition','acquired' => 'Acquis'][$evaluation['level']]) ?> · <?= e($evaluation['comment']) ?>
        </p><?php endforeach; ?><?php if (!$student): ?><?php $begin('evaluations'); ?>
        <input type="hidden" name="competency_id" value="<?= e($criterion['id']) ?>"><?php $select('student_id', 'Étudiant', $students);
            $select('level', 'Niveau constaté', [['id' => 'not_acquired','name' => 'Non acquis'],['id' => 'developing','name' => 'En cours d’acquisition'],['id' => 'acquired','name' => 'Acquis']]);
            $area('comment', 'Observation du jury');
            $end('Enregistrer l’évaluation'); ?><?php endif; ?>
    </details><?php endforeach; ?>
</section><?php endforeach; ?><?php if (!$state['exams']): ?>
<p class="portal-empty">Aucun examen affecté à votre espace.
</p><?php endif; ?>
