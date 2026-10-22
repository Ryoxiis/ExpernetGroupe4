<?php

declare(strict_types=1);

namespace App\Support;

/** Lit les formulaires et les requêtes JSON avec les mêmes contrôles. */
final class RequestData
{
    public static function read(): array
    {
        if (!str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
            return $_POST;
        }
        try {
            $input = json_decode(file_get_contents('php://input'), true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new PortalException('Le contenu JSON est invalide.', 400);
        }
        if (!is_array($input)) {
            throw new PortalException('Un objet JSON est attendu.', 400);
        }
        return $input;
    }
}
