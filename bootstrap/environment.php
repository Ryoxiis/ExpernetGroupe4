<?php

declare(strict_types=1);

// Le fichier .env est lu comme du texte, jamais exécuté comme une commande.
$file = dirname(__DIR__) . '/.env';
if (!is_file($file)) {
    return;
}
foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (!preg_match('/^\s*([A-Z][A-Z0-9_]*)=(.*)$/D', $line, $match)) {
        continue;
    }
    $name = $match[1];
    if (getenv($name) !== false) {
        continue; // Une variable fournie par le terminal reste prioritaire.
    }
    $value = trim($match[2]);
    $quote = $value[0] ?? '';
    if (strlen($value) >= 2 && in_array($quote, ['"', "'"], true) && str_ends_with($value, $quote)) {
        $value = substr($value, 1, -1);
    }
    putenv($name . '=' . $value);
}
