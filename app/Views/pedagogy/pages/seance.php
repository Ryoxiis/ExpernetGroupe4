<?php $beginSession = $session; ?>
<section class="next-session">
    <p class="portal-eyebrow"><?= e(($cohortNames[(int) $session['cohort_id']] ?? 'Promotion')) ?>
    </p>
    <h2><?= e($session['title']) ?>
    </h2>
    <p><?= e($fmt($session['starts_at'])) ?> → <?= e($fmt($session['ends_at'], 'H:i')) ?><br><?= e(($peopleNames[(int) $session['trainer_id']] ?? 'Membre de la promotion')) ?> · <?= e($session['location']) ?>
    </p><?php if ($session['mode'] === 'remote' && $session['meeting_url']): ?><a class="button button-orange" href="<?= e($session['meeting_url']) ?>" target="_blank" rel="noopener noreferrer">Rejoindre le cours à distance ↗</a><?php elseif ($session['mode'] === 'remote'): ?>
    <p>Le lien de réunion n’a pas encore été renseigné. Contactez le formateur.
    </p><?php endif; ?>
</section>
<section class="portal-card">
    <h2>Présence à cette séance
    </h2>
    <p>L’ouverture d’un cours à distance ne confirme pas votre présence. L’émargement est une action séparée.
    </p><?php $sessionWindows = array_filter($state['windows'], static fn ($w) => (int)$w['session_id'] === (int)$session['id']);
foreach ($sessionWindows as $w): ?>
    <div class="portal-note"><strong>Période du <?= e($fmt($w['opens_at'])) ?> au <?= e($fmt($w['closes_at'])) ?></strong><?php $mine = array_values(array_filter($state['attendance'], static fn ($a) => (int)$a['window_id'] === (int)$w['id'] && (int)$a['student_id'] === (int)$user['id']));
    if ($student && $mine): ?>
        <p><?= e($statusLabels[$mine[0]['status']]) ?>
        </p><?php elseif ($student && $w['opens_at'] <= gmdate('Y-m-d H:i:s') && $w['closes_at'] > gmdate('Y-m-d H:i:s')): ?><?php $begin('attendance', 'seance'); ?>
        <input type="hidden" name="session_id" value="<?= e($session['id']) ?>">
        <input type="hidden" name="window_id" value="<?= e($w['id']) ?>"><?php $end($config['demo'] ? 'Confirmer ma présence (simulation)' : 'Déclarer ma présence'); ?><?php else: ?>
        <p><?= $w['closes_at'] > gmdate('Y-m-d H:i:s') ? 'Période ouverte.' : 'Cette période est fermée.' ?>
        </p><?php endif; ?>
    </div><?php endforeach; ?><?php if (!$sessionWindows): ?>
    <p class="portal-empty">L’émargement n’a pas encore été ouvert par le formateur.
    </p><?php endif; ?><?php if (!$student): ?><?php $begin('windows', 'seance'); ?>
    <input type="hidden" name="session_id" value="<?= e($session['id']) ?>"><?php $end('Ouvrir une période de présence'); ?><a class="portal-link" href="<?= e(url('/espace-pedagogique/presences/')) ?>">Consulter et valider les présences →</a><?php endif; ?>
</section>
<section class="portal-card">
    <h2>Ressources de la séance
    </h2><?php $resources = array_filter($state['resources'], static fn ($r) => (int)$r['session_id'] === (int)$session['id']);
foreach ($resources as $r): ?><a class="resource-row" href="<?= e(url('/espace-pedagogique/document/?id=' . $r['id'])) ?>"><strong><?= e($r['title']) ?></strong><span>Télécharger ↓</span></a><?php endforeach; ?><?php if (!$resources): ?>
    <p>Aucun document associé à cette séance. Consultez aussi les ressources de la promotion.
    </p><?php endif; ?><a class="portal-link" href="<?= e(url('/espace-pedagogique/ressources/')) ?>">Toutes mes ressources →</a>
</section>
<section class="portal-card">
    <h2>Travaux associés
    </h2><?php $work = array_filter($state['assignments'], static fn ($a) => (int)$a['session_id'] === (int)$session['id']);
foreach ($work as $a): ?>
    <p><?= e($a['title']) ?> · à rendre le <?= e($fmt($a['due_at'])) ?>
    </p><?php endforeach; ?><?php if (!$work): ?>
    <p>Aucun travail associé à cette séance.
    </p><?php endif; ?><a class="portal-link" href="<?= e(url('/espace-pedagogique/travaux/')) ?>">Ouvrir les travaux →</a>
</section>
