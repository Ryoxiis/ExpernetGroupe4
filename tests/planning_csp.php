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
$style = $xpath->query('//style')->item(0);
if (!$style || $style->getAttribute('nonce') !== App\Support\PortalSecurity::styleNonce()
    || $xpath->query('//*[@style]')->length !== 0 || $xpath->query('//script')->length !== 0
    || !str_contains($style->textContent, '--offset: 30; --duration: 210; --lane: 0;')
    || $xpath->query('//*[@id="calendar-event-4-0"]')->length !== 1) {
    throw new RuntimeException('Le planning doit utiliser des styles numériques autorisés par nonce, sans attribut style.');
}
echo "Planning CSP : nonce, placement horaire et échappement du contenu vérifiés.\n";
