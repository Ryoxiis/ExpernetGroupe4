<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;
use Throwable;

final class View
{
    private string $directory;

    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    /** @param array<string, mixed> $data */
    public function render(string $name, array $data = []): string
    {
        if (!Text::containsOnly($name, 'abcdefghijklmnopqrstuvwxyz0123456789_/-') || str_contains($name, '..')) {
            throw new RuntimeException('Nom de vue invalide.');
        }

        $file = $this->directory . '/' . $name . '.php';
        if (!is_file($file)) {
            throw new RuntimeException('Vue introuvable : ' . $name);
        }

        ob_start();
        try {
            // EXTR_SKIP prevents template data from replacing internal variables.
            $view = $this; // Les sous-vues peuvent réutiliser les mêmes composants.
            extract($data, EXTR_SKIP);
            require $file;
            return (string) ob_get_clean();
        } catch (Throwable $error) {
            ob_end_clean();
            throw $error;
        }
    }
}
