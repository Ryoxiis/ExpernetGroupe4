<?php if (!$student): ?>
<details class="portal-card">
    <summary>Saisir une note
    </summary>
    <p>Une note reste en brouillon tant que vous ne cochez pas « Publier ». Cette règle est proposée pour le prototype.
    </p><?php $begin('grades');
    $select('assignment_id', 'Devoir', $state['assignments'], 'id', 'title');
    $select('student_id', 'Étudiant', $students);
    $input('value', 'Note sur 20', 'number');
    $area('comment', 'Appréciation'); ?>
    <label class="check-label">
        <input type="checkbox" name="published" value="1"> Publier la note à l’étudiant
    </label><?php $end('Enregistrer la note'); ?>
</details><?php endif; ?>
<section class="portal-card">
    <h2><?= $student ? 'Mes notes publiées' : 'Notes enregistrées' ?>
    </h2><?php if (!$state['grades']): ?>
    <p class="portal-empty">Aucune note <?= $student ? 'publiée pour votre compte' : 'enregistrée pour vos devoirs' ?>. Les résultats apparaîtront ici dès leur publication.
    </p><?php else: ?>
    <div class="portal-table-wrap">
        <table class="portal-table">
            <thead>
                <tr>
                    <th>Devoir
                    </th><?php if (!$student): ?>
                    <th>Étudiant
                    </th><?php endif; ?>
                    <th>Note / 20
                    </th>
                    <th>Statut
                    </th>
                    <th>Appréciation
                    </th>
                </tr>
            </thead>
            <tbody><?php foreach ($state['grades'] as $g): ?>
                <tr>
                    <td><?= e(($assignmentNames[(int) $g['assignment_id']] ?? 'Devoir')) ?>
                    </td><?php if (!$student): ?>
                    <td><?= e(($peopleNames[(int) $g['student_id']] ?? 'Membre de la promotion')) ?>
                    </td><?php endif; ?>
                    <td><strong><?= e($g['value']) ?></strong>
                    </td>
                    <td><?= $g['published'] ? 'Publiée' : 'Brouillon' ?>
                    </td>
                    <td><?= e($g['comment']) ?>
                    </td>
                </tr><?php endforeach; ?>
            </tbody>
        </table>
    </div><?php endif; ?>
</section>
<?php if ($student && $state['grades']): ?>
<section class="portal-card">
    <h2>Ma progression
    </h2>
    <p>Moyenne indicative des notes publiées : <strong><?= e(number_format(array_sum(array_column($state['grades'], 'value')) / count($state['grades']), 1, ',', ' ')) ?>/20</strong>. Les coefficients et règles de bulletin restent à définir.
    </p><?php foreach ($state['grades'] as $g): ?>
    <div class="grade-progress"><span><?= e(($assignmentNames[(int) $g['assignment_id']] ?? 'Devoir')) ?></span><meter min="0" max="20" value="<?= e($g['value']) ?>"><?= e($g['value']) ?>/20</meter><strong><?= e($g['value']) ?>/20</strong>
    </div><?php endforeach; ?>
</section><?php endif; ?>
