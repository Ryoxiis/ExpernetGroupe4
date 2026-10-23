<?php
$calendar = \App\Services\PlanningCalendar::build($state['sessions'], $_GET['date'] ?? null, $_GET['view'] ?? null, $_GET['q'] ?? '');
$calendarUrl = static fn (array $changes = []) => url('/espace-pedagogique/planning/?'.http_build_query(array_merge(['date' => $calendar['date'], 'view' => $calendar['view'], 'q' => $calendar['search']], $changes)));
?>
<section class="portal-card calendar-panel" aria-label="Emploi du temps">
    <div class="calendar-toolbar">
        <nav class="calendar-switch" aria-label="Affichage du calendrier">
            <a href="<?= e($calendarUrl(['view' => 'day'])) ?>" <?= $calendar['view'] === 'day' ? 'aria-current="page"' : '' ?>>Journée</a>
            <a href="<?= e($calendarUrl(['view' => 'week'])) ?>" <?= $calendar['view'] === 'week' ? 'aria-current="page"' : '' ?>>Semaine</a>
        </nav>
        <a class="portal-link" href="<?= e($calendarUrl(['date' => $calendar['today']])) ?>">Aujourd’hui</a>
        <a class="portal-link" href="<?= e(url('/espace-pedagogique/calendrier/')) ?>">Exporter (.ics)</a>
    </div>
    <form method="get" action="<?= e(url('/espace-pedagogique/planning/')) ?>" class="calendar-filters">
        <input type="hidden" name="view" value="<?= e($calendar['view']) ?>">
        <label>Date <input type="date" name="date" value="<?= e($calendar['date']) ?>" min="1900-01-01" max="2100-12-31"></label>
        <label>Rechercher un cours <input type="search" name="q" value="<?= e($calendar['search']) ?>" maxlength="100" placeholder="Matière, formateur, salle…"></label>
        <button class="button button-dark" type="submit">Afficher</button>
    </form>
    <div class="calendar-navigation">
        <a href="<?= e($calendarUrl(['date' => $calendar['previous']])) ?>" aria-label="<?= $calendar['view'] === 'week' ? 'Semaine précédente' : 'Jour précédent' ?>">←</a>
        <h2><?= e($calendar['label']) ?></h2>
        <a href="<?= e($calendarUrl(['date' => $calendar['next']])) ?>" aria-label="<?= $calendar['view'] === 'week' ? 'Semaine suivante' : 'Jour suivant' ?>">→</a>
    </div>
    <p class="portal-help">Heure de La Réunion. Sélectionnez un cours pour retrouver sa salle, ses documents et ses informations.</p>
    <div class="planning-calendar" style="--days:<?= count($calendar['days']) ?>;--hours:<?= $calendar['endHour'] - $calendar['startHour'] ?>">
        <div class="calendar-hours" aria-hidden="true"><div class="calendar-day-heading">Jour / heure</div><div class="calendar-hour-grid">
            <?php for ($hour = $calendar['startHour']; $hour < $calendar['endHour']; $hour++): ?><span><?= sprintf('%02d:00', $hour) ?></span><?php endfor; ?>
        </div></div>
        <?php foreach ($calendar['days'] as $day): ?>
        <section class="calendar-day <?= $day['today'] ? 'is-today' : '' ?> <?= !$day['events'] ? 'is-empty' : '' ?>" style="--day-lanes:<?= max([1, ...array_column($day['events'], 'lanes')]) ?>" aria-label="<?= e($day['label'].' '.$day['short']) ?>">
            <h3 class="calendar-day-heading"><a href="<?= e($calendarUrl(['date' => $day['date'], 'view' => 'day'])) ?>"><?= e($day['label']) ?><span><?= e($day['short']) ?><?= $day['today'] ? ' · Aujourd’hui' : '' ?></span></a></h3>
            <div class="calendar-day-grid">
                <?php if (!$day['events']): ?><p class="calendar-no-course"><?= $calendar['search'] !== '' ? 'Aucun cours trouvé' : 'Aucun cours' ?></p><?php endif; ?>
                <?php foreach ($day['events'] as $event): $s = $event['session']; ?>
                <a class="calendar-event calendar-event-<?= e($s['mode']) ?>" style="--offset:<?= $event['from'] - $calendar['startHour'] * 60 ?>;--duration:<?= $event['to'] - $event['from'] ?>;--lane:<?= $event['lane'] ?>;--lanes:<?= $event['lanes'] ?>" href="<?= e(url('/espace-pedagogique/seance/?id='.$s['id'])) ?>">
                    <span class="calendar-time"><?= e($event['time']) ?></span>
                    <strong><?= e($s['title']) ?></strong>
                    <span><?= e($s['trainer_name']) ?></span>
                    <span><?= e($s['location']) ?></span>
                    <span class="calendar-mode"><?= $s['mode'] === 'remote' ? 'À distance' : ($s['mode'] === 'company' ? 'En entreprise' : 'En présentiel') ?></span>
                    <span><?= e($s['cohort_name']) ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endforeach; ?>
    </div>
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
