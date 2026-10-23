<?php

declare(strict_types=1);

use App\Support\Text;

$basePath = trim(getenv('APP_BASE_PATH') ?: '', '/');
$allowedCharacters = Text::LETTERS . Text::DIGITS . '_/-';
if ($basePath !== '' && !Text::containsOnly($basePath, $allowedCharacters)) {
    throw new RuntimeException('APP_BASE_PATH doit être un chemin relatif simple.');
}

return [
    'name' => 'Expernet - Projet de refonte',
    'environment' => getenv('APP_ENV') ?: 'local',
    'base_path' => $basePath === '' ? '' : '/' . $basePath,
    'timezone' => 'Indian/Reunion',
    'snapshot_date' => '2026-10-22',
];
