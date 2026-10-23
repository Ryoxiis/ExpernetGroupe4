<?php

declare(strict_types=1);
$app = require __DIR__.'/../bootstrap/app.php';
$base = ['id'=>1,'title'=>'PHP <script>alert(1)</script>','trainer_name'=>'Alice & Bob','location'=>'Niagara','cohort_name'=>'CDA','mode'=>'onsite','starts_at'=>'2026-10-23 04:30:00','ends_at'=>'2026-10-23 08:00:00'];
$tomorrow = array_replace($base, ['id'=>2,'title'=>'Cours du lendemain','starts_at'=>'2026-10-24 04:30:00','ends_at'=>'2026-10-24 08:00:00']);
$state = ['sessions'=>[$base,$tomorrow], 'unavailability'=>[]]; $student=true; $trainer=false;
$render = static function (array $query) use ($app,$state,$student,$trainer): array {
 $_GET=$query;
 $html=$app['view']->render('pedagogy/pages/planning',compact('state','student','trainer'));
 $dom=new DOMDocument(); libxml_use_internal_errors(true); $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
 return [$html,new DOMXPath($dom)];
};
[$html,$xpath]=$render(['date'=>'2026-10-23','view'=>'week']);
if ($xpath->query('//ol/li')->length!==1 || !str_contains($html,'Alice &amp; Bob') || !str_contains($html,'08:30') || !str_contains($html,'12:00') || str_contains($html,'Cours du lendemain') || $xpath->query('//script|//style|//*[@style]')->length || $xpath->query('//nav[@aria-label="Choisir un jour"]/a[@aria-current="date"]')->length!==1) throw new RuntimeException('Single day, full data, escaping or CSP regression');
[$html,$xpath]=$render(['date'=>'2026-10-23','q'=>'introuvable']);
if ($xpath->query('//ol/li')->length || !str_contains($html,'Aucun cours ne correspond')) throw new RuntimeException('Search empty state');
[$html,$xpath]=$render(['date'=>'2026-10-25']);
if (!str_contains($html,'Aucun cours programmé ce jour')) throw new RuntimeException('Empty day');
$calendar=App\Services\PlanningCalendar::build([$base],'2026-10-23','day');
if ($calendar['longLabel']!=='Vendredi 23 octobre 2026' || count($calendar['navigation'])!==7 || $calendar['previous']!=='2026-10-22' || $calendar['next']!=='2026-10-24') throw new RuntimeException('Date navigation');
echo "Agenda : un seul jour, cours/professeur/horaires, dates, recherche, états vides et CSP sans styles inline vérifiés.\n";
