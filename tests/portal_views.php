<?php

declare(strict_types=1);

// Vérifie le chargement des écrans séparés, y compris les formulaires de chaque rôle.
$app = require __DIR__ . '/../bootstrap/app.php';
$config = require PROJECT_ROOT . '/config/pedagogy.php';
$keys = ['cohorts', 'sessions', 'assignments', 'windows', 'attendance', 'grades', 'submissions', 'people', 'exams', 'criteria', 'resources', 'memberships', 'messages', 'unavailability', 'journal', 'feedback', 'jury', 'evaluations', 'audit'];
$state = array_fill_keys($keys, []);
$session = ['id' => 1, 'cohort_id' => 1, 'trainer_id' => 2, 'title' => 'Séance fictive', 'starts_at' => '2026-10-22 08:00:00', 'ends_at' => '2026-10-22 10:00:00', 'location' => 'Salle', 'mode' => 'onsite', 'meeting_url' => null];
$csrf = str_repeat('a', 64);
$flash = null;
set_error_handler(static function ($severity, $message, $file, $line): never {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$checks = 0;
foreach (['student', 'trainer', 'admin'] as $role) {
    $user = ['id' => 1, 'role' => $role, 'name' => 'Personne Fictive'];
    foreach (array_keys($config['pages']) as $page) {
        $html = $app['view']->render('pedagogy/portal', compact('state', 'config', 'user', 'page', 'session', 'csrf', 'flash'));
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($dom);
        if ($xpath->query('//main[@id="contenu"]')->length !== 1 || $xpath->query('//h1')->length !== 1) {
            throw new RuntimeException("Structure manquante pour $role / $page.");
        }
        foreach ($xpath->query('//form') as $form) {
            if (strtolower($form->getAttribute('method')) === 'get' && $page === 'planning' && $form->getAttribute('class') === 'calendar-filters') {
                if ($xpath->query('.//input[@name="date"]', $form)->length !== 1 || $xpath->query('.//input[@name="view"]', $form)->length !== 1) {
                    throw new RuntimeException('Filtres du calendrier incomplets.');
                }
                continue; // Read-only calendar navigation does not need a CSRF token.
            }
            $tokens = $xpath->query('.//input[@name="csrf"]', $form);
            if (strtolower($form->getAttribute('method')) !== 'post' || $tokens->length !== 1 || $tokens->item(0)->getAttribute('value') !== $csrf) {
                throw new RuntimeException("Formulaire non protégé pour $role / $page.");
            }
        }
        $checks++;
    }
}
restore_error_handler();
echo "$checks écrans pédagogiques rendus sans avertissement, avec structure et formulaires vérifiés.\n";
