<?php

declare(strict_types=1);

namespace App\Support;

final class PortalException extends \RuntimeException
{
    public function __construct(string $message, public readonly int $status = 422)
    {
        parent::__construct($message);
    }
}
