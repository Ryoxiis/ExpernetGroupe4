<?php

declare(strict_types=1);

// Le fichier .env est lu comme du texte, jamais exécuté comme une commande.
$file = dirname(__DIR__) . '/.env';
if (!is_file($file)) {
    return;
}
foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $parts = explode('=', ltrim($line), 2);
    if (count($parts) !== 2) {
        continue;
    }
    [$name, $value] = $parts;
    $allowed = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_';
    if ($name === '' || !ctype_upper($name[0]) || strspn($name, $allowed) !== strlen($name)) {
        continue;
    }
    if (getenv($name) !== false) {
        continue; // Une variable fournie par le terminal reste prioritaire.
    }
    $value = trim($value);
    $quote = $value[0] ?? '';
    if (strlen($value) >= 2 && in_array($quote, ['"', "'"], true) && str_ends_with($value, $quote)) {
        $value = substr($value, 1, -1);
    }
    putenv($name . '=' . $value);
}
