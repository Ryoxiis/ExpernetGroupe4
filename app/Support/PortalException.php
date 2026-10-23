<?php

declare(strict_types=1);

namespace App\Support;

final class PortalException extends \RuntimeException
{
    public int $status;

    public function __construct(string $message, int $status = 422)
    {
        $this->status = $status;
        parent::__construct($message);
    }
}
