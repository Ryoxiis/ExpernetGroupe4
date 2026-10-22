<?php

declare(strict_types=1);

return [
    // Une seule liste pour les titres affichés et les pages autorisées.
    'pages' => ['accueil' => 'Mon tableau de bord', 'planning' => 'Mon planning', 'classes' => 'Mes classes', 'travaux' => 'Mes travaux', 'resultats' => 'Mes résultats', 'ressources' => 'Mes ressources', 'presences' => 'Les présences', 'messages' => 'Les échanges', 'parcours' => 'Mon parcours', 'examens' => 'Examens et compétences', 'administration' => 'Administration', 'seance' => 'Ma séance'],
    'demo' => filter_var(getenv('PORTAL_DEMO') ?: 'true', FILTER_VALIDATE_BOOLEAN),
    'mfa' => filter_var(getenv('PORTAL_MFA') ?: 'true', FILTER_VALIDATE_BOOLEAN),
    'email_domain' => getenv('PORTAL_EMAIL_DOMAIN') ?: 'expernet.re',
    'temporary_hours' => 24,
    'activation_minutes' => 15,
    'storage' => getenv('PORTAL_STORAGE') ?: PROJECT_ROOT . '/storage/pedagogy',
    'key' => getenv('APP_KEY') ?: '',
];
