<?php

namespace App\Exceptions\Dolibarr;

use RuntimeException;

class DolibarrException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly string $operation,
        private readonly ?int $statusCode = null,
    ) {
        parent::__construct($message);
    }

    public function operation(): string
    {
        return $this->operation;
    }

    public function statusCode(): ?int
    {
        return $this->statusCode;
    }
}
