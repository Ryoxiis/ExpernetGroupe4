<?php

declare(strict_types=1);

namespace App\Support;

/** Petits contrôles de texte partagés, sans expression régulière. */
final class Text
{
    public const LETTERS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    public const DIGITS = '0123456789';

    public static function containsOnly(string $value, string $allowed): bool
    {
        return $value !== '' && strspn($value, $allowed) === strlen($value);
    }

    public static function hasControlCharacters(string $value, bool $includeSpace = false): bool
    {
        $limit = $includeSpace ? 32 : 31;
        for ($index = 0; $index < strlen($value); $index++) {
            if (ord($value[$index]) <= $limit) {
                return true;
            }
        }
        return false;
    }
}
