<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;

/** Presentation of already-authorized sessions; never fetches or widens access. */
final class PlanningCalendar
{
    public static function build(array $sessions, mixed $date, mixed $view, mixed $search = ''): array
    {
        $zone = new DateTimeZone('Indian/Reunion');
        $today = new DateTimeImmutable('today', $zone);
        $anchor = false;
        if (is_string($date) && strlen($date) === 10 && !str_contains($date, "\0")) {
            $anchor = DateTimeImmutable::createFromFormat('!Y-m-d', $date, $zone);
        }
        if (!$anchor || $anchor->format('Y-m-d') !== $date || $anchor->format('Y') < '1900' || $anchor->format('Y') > '2100') {
            $anchor = $today;
        }
        $view = $view === 'day' ? 'day' : 'week';
        $search = is_string($search) ? mb_substr(trim($search), 0, 100) : '';
        $first = $view === 'week' ? $anchor->modify('-'.((int)$anchor->format('N') - 1).' days') : $anchor;
        $days = [];
        $startHour = 8;
        $endHour = 18;
        $weekdays = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
        for ($i = 0; $i < ($view === 'week' ? 7 : 1); $i++) {
            $day = $first->modify('+'.$i.' days');
            $next = $day->modify('+1 day');
            $events = [];
            foreach ($sessions as $session) {
                if (!self::matchesSearch($session, $search)) {
                    continue;
                }
                $start = (new DateTimeImmutable($session['starts_at'], new DateTimeZone('UTC')))->setTimezone($zone);
                $end = (new DateTimeImmutable($session['ends_at'], new DateTimeZone('UTC')))->setTimezone($zone);
                if ($start >= $next || $end <= $day || $end <= $start) {
                    continue;
                }
                $from = $start < $day ? 0 : (int)$start->format('H') * 60 + (int)$start->format('i');
                $to = $end >= $next ? 1440 : (int)$end->format('H') * 60 + (int)$end->format('i');
                $startHour = min($startHour, (int)floor($from / 60));
                $endHour = max($endHour, (int)ceil($to / 60));
                $events[] = ['session' => $session, 'from' => $from, 'to' => $to, 'time' => $start->format('H:i').'–'.$end->format('H:i'), 'lane' => 0];
            }
            usort($events, static fn ($a, $b) => [$a['from'], $a['to'], $a['session']['id']] <=> [$b['from'], $b['to'], $b['session']['id']]);
            $events = self::arrangeOverlaps($events);
            $days[] = ['date' => $day->format('Y-m-d'), 'label' => $weekdays[(int)$day->format('N') - 1], 'short' => $day->format('d/m'), 'today' => $day == $today, 'events' => $events];
        }
        return compact('days', 'view', 'search', 'startHour', 'endHour') + [
            'date' => $anchor->format('Y-m-d'), 'today' => $today->format('Y-m-d'),
            'previous' => $anchor->modify($view === 'week' ? '-7 days' : '-1 day')->format('Y-m-d'),
            'next' => $anchor->modify($view === 'week' ? '+7 days' : '+1 day')->format('Y-m-d'),
            'label' => $view === 'week' ? 'Du '.$first->format('d/m/Y').' au '.$first->modify('+6 days')->format('d/m/Y') : $weekdays[(int)$anchor->format('N') - 1].' '.$anchor->format('d/m/Y'),
        ];
    }

    private static function matchesSearch(array $session, string $search): bool
    {
        if ($search === '') {
            return true;
        }
        $text = '';
        foreach (['title', 'trainer_name', 'location', 'cohort_name'] as $field) {
            $text .= ' ' . ($session[$field] ?? '');
        }
        return mb_stripos($text, $search) !== false;
    }

    /** Regroupe les cours qui se chevauchent, puis leur attribue une colonne. */
    private static function arrangeOverlaps(array $events): array
    {
        $groups = [];
        $group = [];
        $groupEnd = -1;
        foreach ($events as $event) {
            if ($group && $event['from'] >= $groupEnd) {
                $groups[] = $group;
                $group = [];
            }
            $group[] = $event;
            $groupEnd = max($groupEnd, $event['to']);
        }
        if ($group) {
            $groups[] = $group;
        }

        $result = [];
        foreach ($groups as $group) {
            $laneEnds = [];
            foreach ($group as $index => $event) {
                $lane = 0;
                while (isset($laneEnds[$lane]) && $laneEnds[$lane] > $event['from']) {
                    $lane++;
                }
                $laneEnds[$lane] = $event['to'];
                $group[$index]['lane'] = $lane;
            }
            foreach ($group as $event) {
                $event['lanes'] = count($laneEnds);
                $result[] = $event;
            }
        }
        return $result;
    }

}
