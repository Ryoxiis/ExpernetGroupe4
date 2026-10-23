<?php

declare(strict_types=1);
$app = require __DIR__ . '/../bootstrap/app.php';
$session = ['id' => 1, 'title' => '<script>interdit</script>', 'trainer_name' => 'Formateur fictif', 'location' => 'Salle', 'cohort_name' => 'QA', 'mode' => 'onsite', 'starts_at' => '2026-10-23 04:30:00', 'ends_at' => '2026-10-23 08:00:00'];
$_GET = ['date' => '2026-10-23'];
$state = ['sessions' => [$session], 'unavailability' => []];
$student = true;
$trainer = false;
$html = $app['view']->render('pedagogy/pages/planning', compact('state', 'student', 'trainer'));
$dom = new DOMDocument();
libxml_use_internal_errors(true);
$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
$xpath = new DOMXPath($dom);
if ($xpath->query('//*[@style]')->length !== 0 || $xpath->query('//script')->length !== 0
    || $xpath->query('//link[@rel="stylesheet"]')->length === 0
    || $xpath->query('//ol[contains(@class,"agenda-lessons")]/li')->length !== 1
    || !str_contains($html, '&lt;script&gt;interdit&lt;/script&gt;')) {
    throw new RuntimeException('Le planning doit employer une feuille externe, sans style inline ni injection.');
}
echo "Planning CSP : feuille externe, cours et échappement du contenu vérifiés.\n";
