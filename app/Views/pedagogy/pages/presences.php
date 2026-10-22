<p class="portal-note">Une déclaration est distincte d’une présence validée. Règle du prototype : le formateur ouvre une période pendant la séance, puis contrôle les déclarations. La procédure officielle de signature électronique reste à définir.
</p><a class="portal-link" href="<?= e(url('/espace-pedagogique/attestation/')) ?>">Voir et imprimer mon relevé de démonstration →</a>
<?php foreach ($state['windows'] as $w): ?>
<section class="portal-card">
    <p class="portal-eyebrow"><?= e($fmt($w['opens_at'])) ?> → <?= e($fmt($w['closes_at'], 'H:i')) ?> · <?= $w['closes_at'] > gmdate('Y-m-d H:i:s') ? 'Ouverte' : 'Fermée' ?>
    </p>
    <h2><?= e(($sessionNames[(int) $w['session_id']] ?? 'Séance')) ?>
    </h2><?php foreach ($state['attendance'] as $a): if ((int)$a['window_id'] !== (int)$w['id']) {
        continue;
    } ?>
    <p><?= e(($peopleNames[(int) $a['student_id']] ?? 'Membre de la promotion')) ?> · <strong><?= e($statusLabels[$a['status']]) ?></strong> · déclaration <?= e($fmt($a['declared_at'])) ?>
    </p><?php endforeach; ?><a class="portal-link" href="<?= e(url('/espace-pedagogique/seance/?id=' . $w['session_id'])) ?>">Ouvrir la séance →</a><?php if (!$student): ?>
    <details>
        <summary>Valider ou corriger une présence
        </summary><?php $begin('attendance-review'); ?>
        <input type="hidden" name="window_id" value="<?= e($w['id']) ?>"><?php $select('student_id', 'Étudiant de cette promotion', $students);
        $select('status', 'Statut', array_map(static fn ($k, $v) => ['id' => $k,'name' => $v], ['validated','late','absent','excused'], ['Présence validée','Retard','Absence','Absence justifiée']));
        $area('reason', 'Motif de validation ou correction (conservé dans l’historique)');
        $end('Enregistrer le statut'); ?>
    </details><?php endif; ?>
</section><?php endforeach; ?><?php if (!$state['windows']): ?>
<p class="portal-empty">Aucune période de présence ouverte. <?= $student ? 'Consultez votre séance lorsque le formateur ouvre l’émargement.' : 'Ouvrez une séance en cours pour lancer une période de présence.' ?>
</p><?php endif; ?>
