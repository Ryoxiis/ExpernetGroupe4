<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeImmutable;
use DateTimeZone;

final class Dates
{
    /** Accepte une date ISO avec fuseau explicite, avec ou sans millisecondes. */
    public static function isoTimestamp(string $value): ?DateTimeImmutable
    {
        if (str_contains($value, "\0") || strlen($value) > 30) {
            return null;
        }
        $formats = ['Y-m-d\TH:i:s\Z', 'Y-m-d\TH:i:s.v\Z', 'Y-m-d\TH:i:sP', 'Y-m-d\TH:i:s.vP'];
        foreach ($formats as $format) {
            $date = DateTimeImmutable::createFromFormat('!'.$format, $value, new DateTimeZone('UTC'));
            if ($date && DateTimeImmutable::getLastErrors() === false && $date->format($format) === $value) {
                return $date;
            }
        }
        return null;
    }
}
