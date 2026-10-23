<?php
// Only one day is rendered, including when following an old week-view link.
$calendar = \App\Services\PlanningCalendar::build($state['sessions'], $_GET['date'] ?? null, 'day', $_GET['q'] ?? '');
$calendarUrl = static fn (array $changes = []) => url('/espace-pedagogique/planning/?'.http_build_query(array_merge(['date' => $calendar['date'], 'view' => 'day', 'q' => $calendar['search']], $changes)));
$day = $calendar['days'][0];
$courseCount = count($day['events']);
?>
<link rel="stylesheet" href="<?= asset('assets/css/planning.css') ?>">
<section class="portal-card daily-agenda" aria-labelledby="agenda-date">
    <header class="agenda-heading">
        <div>
            <p class="agenda-caption">Votre journée<?= $day['today'] ? ' d’aujourd’hui' : '' ?></p>
            <h2 id="agenda-date"><time datetime="<?= e($calendar['date']) ?>"><?= e($calendar['longLabel']) ?></time></h2>
            <p class="agenda-summary"><?= $courseCount ?> cours<?= $calendar['search'] !== '' ? ' trouvé'.($courseCount > 1 ? 's' : '') : ' programmé'.($courseCount > 1 ? 's' : '') ?> <span>Heure de La Réunion (UTC+4)</span></p>
        </div>
        <div class="agenda-controls">
            <a class="agenda-today" href="<?= e($calendarUrl(['date' => $calendar['today']])) ?>">Aujourd’hui</a>
            <nav class="agenda-arrows" aria-label="Changer de journée">
                <a href="<?= e($calendarUrl(['date' => $calendar['previous']])) ?>" aria-label="Jour précédent"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m14 6-6 6 6 6"/></svg></a>
                <a href="<?= e($calendarUrl(['date' => $calendar['next']])) ?>" aria-label="Jour suivant"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m10 6 6 6-6 6"/></svg></a>
            </nav>
        </div>
    </header>
    <nav class="agenda-dates" aria-label="Choisir un jour">
        <?php foreach ($calendar['navigation'] as $date): ?>
        <a href="<?= e($calendarUrl(['date' => $date['date']])) ?>" <?= $date['selected'] ? 'aria-current="date"' : '' ?> aria-label="<?= e($date['label'].($date['today'] ? ', aujourd’hui' : '')) ?>">
            <span><?= e($date['weekday']) ?></span><strong><?= e($date['number']) ?></strong>
            <?php if ($date['today']): ?><span class="agenda-today-marker" aria-hidden="true"></span><?php endif; ?>
        </a>
        <?php endforeach; ?>
    </nav>
    <details class="agenda-tools" <?= $calendar['search'] !== '' ? 'open' : '' ?>>
        <summary>Choisir une date ou rechercher un cours</summary>
    <form method="get" action="<?= e(url('/espace-pedagogique/planning/')) ?>" class="calendar-filters">
        <input type="hidden" name="view" value="day">
        <label>Aller au <input type="date" name="date" value="<?= e($calendar['date']) ?>" min="1900-01-01" max="2100-12-31" required></label>
        <label>Rechercher dans la journée <input type="search" name="q" value="<?= e($calendar['search']) ?>" maxlength="100" placeholder="Cours, professeur, salle…"></label>
        <button class="agenda-filter-button" type="submit">Afficher</button>
        <?php if ($calendar['search'] !== ''): ?><a class="agenda-reset" href="<?= e($calendarUrl(['q' => ''])) ?>">Effacer la recherche</a><?php endif; ?>
    </form>
    </details>
    <?php if ($courseCount): ?>
    <ol class="agenda-lessons" aria-label="Cours de la journée">
        <?php foreach ($day['events'] as $event): $s = $event['session']; $minutes = $event['to'] - $event['from']; ?>
        <li class="agenda-lesson">
            <div class="agenda-hours" id="agenda-time-<?= (int) $s['id'] ?>">
                <strong><?= e(sprintf('%02d:%02d', intdiv($event['from'], 60), $event['from'] % 60)) ?></strong>
                <span>à <?= e(sprintf('%02d:%02d', intdiv($event['to'], 60), $event['to'] % 60)) ?></span>
                <small><?= $minutes < 60 ? $minutes.' min' : intdiv($minutes, 60).' h'.($minutes % 60 ? ' '.sprintf('%02d', $minutes % 60) : '') ?></small>
            </div>
            <a class="agenda-course agenda-course-<?= e($s['mode']) ?>" href="<?= e(url('/espace-pedagogique/seance/?id='.(int)$s['id'])) ?>" aria-describedby="agenda-time-<?= (int) $s['id'] ?>">
                <div class="agenda-course-top"><span class="agenda-mode"><?= $s['mode'] === 'remote' ? 'À distance' : ($s['mode'] === 'company' ? 'En entreprise' : 'En présentiel') ?></span><span class="agenda-cohort"><?= e($s['cohort_name']) ?></span></div>
                <h3><?= e($s['title']) ?></h3>
                <dl class="agenda-course-details">
                    <div><dt>Professeur</dt><dd><?= e($s['trainer_name']) ?></dd></div>
                    <div><dt><?= $s['mode'] === 'remote' ? 'Lieu de connexion' : 'Salle / lieu' ?></dt><dd><?= e($s['location'] ?: 'Non renseigné') ?></dd></div>
                </dl>
                <span class="agenda-open">Voir le cours <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg></span>
            </a>
        </li>
        <?php endforeach; ?>
    </ol>
    <?php else: ?>
    <div class="agenda-empty">
        <svg viewBox="0 0 48 48" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="8" y="11" width="32" height="30" rx="5"/><path d="M16 7v8m16-8v8M8 21h32m-23 9 5 5 9-9"/></svg>
        <h3><?= $calendar['search'] !== '' ? 'Aucun cours ne correspond à votre recherche' : 'Aucun cours programmé ce jour' ?></h3>
        <p><?= $calendar['search'] !== '' ? 'Essayez un autre nom de cours ou de professeur.' : 'Choisissez une autre date pour consulter vos prochains cours.' ?></p>
        <a href="<?= e($calendarUrl($calendar['search'] !== '' ? ['q' => ''] : ['date' => $calendar['next']])) ?>"><?= $calendar['search'] !== '' ? 'Voir toute la journée' : 'Voir le jour suivant' ?></a>
    </div>
    <?php endif; ?>
    <footer class="agenda-footer"><span>Les horaires sont affichés à l’heure de La Réunion.</span><a href="<?= e(url('/espace-pedagogique/calendrier/')) ?>">Exporter le calendrier (.ics)</a></footer>
</section>
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
