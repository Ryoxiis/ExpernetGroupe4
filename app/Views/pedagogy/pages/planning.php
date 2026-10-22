<div class="portal-toolbar">
    <label for="planning-search">Rechercher dans mes séances
    </label>
    <input id="planning-search" type="search" data-filter="session-card" placeholder="Matière, formateur, salle…"><a class="button button-dark" href="<?= e(url('/espace-pedagogique/calendrier/')) ?>">Télécharger mon calendrier (.ics)</a>
</div>
<p class="portal-help">Les horaires sont affichés à l’heure de La Réunion. L’export est un téléchargement ponctuel, sans abonnement automatique.
</p>
<div class="session-list"><?php foreach ($state['sessions'] as $s): ?>
    <article class="portal-card session-card">
        <p class="portal-eyebrow"><?= e($fmt($s['starts_at'], 'd/m/Y')) ?> · <?= e($fmt($s['starts_at'], 'H:i')) ?>–<?= e($fmt($s['ends_at'], 'H:i')) ?>
        </p>
        <h2><?= e($s['title']) ?>
        </h2>
        <p><?= e($s['cohort_name']) ?> · <?= e($s['trainer_name']) ?>
        </p>
        <p><?= e($s['location']) ?> · <?= $s['mode'] === 'remote' ? 'À distance' : ($s['mode'] === 'company' ? 'En entreprise' : 'En présentiel') ?>
        </p><a class="portal-link" href="<?= e(url('/espace-pedagogique/seance/?id=' . $s['id'])) ?>">Ouvrir la séance →</a>
    </article><?php endforeach; ?>
</div>
<p class="portal-empty" data-filter-empty hidden>Aucune séance ne correspond à votre recherche.
</p><?php if (!$state['sessions']): ?>
<p class="portal-empty">Aucune séance programmée pour le moment.
</p><?php endif; ?>
<?php if ($trainer): ?>
<details class="portal-card">
    <summary>Déclarer une indisponibilité
    </summary>
    <p>Prise en compte immédiate. Les séances déjà programmées sont conservées et les conflits signalés.
    </p><?php $begin('unavailability');
    $input('starts_at', 'Début de l’indisponibilité', 'datetime-local');
    $input('ends_at', 'Fin de l’indisponibilité', 'datetime-local');
    $input('reason', 'Motif');
    $end('Enregistrer mon indisponibilité'); ?>
</details><?php endif; ?>
<?php if (!$student): ?>
<section class="portal-card">
    <h2>Indisponibilités enregistrées
    </h2><?php foreach ($state['unavailability'] as $u): ?>
    <p><?= e(($peopleNames[(int) $u['trainer_id']] ?? 'Membre de la promotion')) ?> · <?= e($fmt($u['starts_at'])) ?> → <?= e($fmt($u['ends_at'])) ?> · <?= e($u['reason']) ?>
    </p><?php endforeach; ?><?php if (!$state['unavailability']): ?>
    <p>Aucune indisponibilité enregistrée.
    </p><?php endif; ?>
</section><?php endif; ?>
