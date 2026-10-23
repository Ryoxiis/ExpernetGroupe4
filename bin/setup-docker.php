<?php

/** Prépare une configuration Docker indépendante du .env LAMP. */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$root = dirname(__DIR__);
$path = $root.'/.env.docker';
if (is_file($path)) {
    echo ".env.docker existe déjà : aucun secret ni fichier remplacé.\n";
    exit;
}
$template = file_get_contents($root.'/.env.docker.example');
if ($template === false) {
    fwrite(STDERR, "Modèle .env.docker.example introuvable.\n");
    exit(1);
}
foreach (['DOCKER_DB_PASSWORD', 'DOCKER_ROOT_PASSWORD', 'APP_KEY'] as $key) {
    $template = str_replace($key.'=', $key.'='.bin2hex(random_bytes(32)), $template);
}
// Création exclusive : ne pas remplacer une configuration créée en parallèle.
$previousMask = umask(0077);
$handle = fopen($path, 'x');
umask($previousMask);
if ($handle === false) {
    fwrite(STDERR, "Impossible de créer .env.docker.\n");
    exit(1);
}
$written = fwrite($handle, $template);
fclose($handle);
if ($written !== strlen($template)) {
    fwrite(STDERR, "Écriture incomplète : vérifiez .env.docker avant de démarrer.\n");
    exit(1);
}
echo "Configuration Docker créée, sans modifier .env ni MySQL local.\n";
echo "Démarrage : docker compose --env-file .env.docker up -d --build\n";
