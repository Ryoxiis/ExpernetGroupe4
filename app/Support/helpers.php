<?php

declare(strict_types=1);

/** Escape all text and HTML attribute values at the last possible moment. */
function e(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = '/'): string
{
    static $basePath;
    $basePath ??= (require PROJECT_ROOT . '/config/app.php')['base_path'];

    return $basePath . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return e(url($path));
}

/** UTF-8 excerpt without requiring the optional mbstring extension. */
function excerpt(string $text, int $length = 190): string
{
    preg_match('/^.{0,' . max(0, min(1000, $length)) . '}/us', $text, $matches);
    return $matches[0] ?? '';
}
