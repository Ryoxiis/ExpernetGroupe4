<?php

declare(strict_types=1);
require __DIR__.'/../bootstrap/app.php';
use App\Services\PlanningCalendar;
function check(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
$base = ['id'=>1,'title'=>'PHP','trainer_name'=>'Alice','location'=>'Océan','cohort_name'=>'CDA','mode'=>'onsite','starts_at'=>'2026-10-23 04:30:00','ends_at'=>'2026-10-23 08:00:00'];
$week = PlanningCalendar::build([$base], '2026-10-23', 'week');
check(count($week['days']) === 7 && $week['days'][0]['date'] === '2026-10-19', 'Monday start');
check($week['days'][4]['events'][0]['time'] === '08:30–12:00', 'UTC to Reunion');
check($week['previous'] === '2026-10-16' && $week['next'] === '2026-10-30', 'Week navigation');
$day = PlanningCalendar::build([$base], '2026-10-23', 'day');
check(count($day['days']) === 1 && count($day['days'][0]['events']) === 1, 'Day view');
check(!PlanningCalendar::build([$base], '2026-10-24', 'day')['days'][0]['events'], 'Empty day');
check(!PlanningCalendar::build([$base], '2026-10-23', 'day', 'Réseau')['days'][0]['events'], 'Search');
check(count(PlanningCalendar::build([$base], '2026-10-23', 'day', 'alice')['days'][0]['events']) === 1, 'Search trainer');
$overlap = $base; $overlap['id'] = 2;
$events = PlanningCalendar::build([$base,$overlap], '2026-10-23', 'day')['days'][0]['events'];
check($events[0]['lanes'] === 2 && $events[1]['lane'] === 1, 'Overlapping sessions separate');
$night = $base; $night['starts_at']='2026-10-23 19:30:00'; $night['ends_at']='2026-10-23 21:00:00';
$nightView = PlanningCalendar::build([$night], '2026-10-23', 'week');
check($nightView['days'][4]['events'][0]['to'] === 1440 && $nightView['days'][5]['events'][0]['from'] === 0, 'Midnight split');
check($nightView['startHour'] === 0 && $nightView['endHour'] === 24, 'Full time range');
$invalid = PlanningCalendar::build([], ['invalid'], ['invalid'], ['invalid']);
check($invalid['view'] === 'week' && $invalid['search'] === '', 'Untrusted arrays');
check(PlanningCalendar::build([], '2026-02-31', 'day')['date'] !== '2026-03-03', 'Reject invalid dates');
echo "OK : semaine, journée, navigation, heure Réunion, recherche, chevauchements, minuit et entrées invalides.\n";
