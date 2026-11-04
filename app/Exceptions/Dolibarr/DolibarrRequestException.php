<?php

namespace App\Exceptions\Dolibarr;

class DolibarrRequestException extends DolibarrException
{
    public static function connection(string $operation): self
    {
        return new self(
            __('dolibarr.errors.unreachable'),
            $operation,
        );
    }

    public static function http(string $operation, int $statusCode): self
    {
        $message = match ($statusCode) {
            401, 403 => __('dolibarr.errors.authentication'),
            404 => __('dolibarr.errors.not_found'),
            408, 429 => __('dolibarr.errors.unavailable'),
            default => $statusCode >= 500
                ? __('dolibarr.errors.unavailable')
                : __('dolibarr.errors.rejected'),
        };

        return new self($message, $operation, $statusCode);
    }

    public static function invalidResponse(string $operation, ?int $statusCode = null): self
    {
        return new self(
            __('dolibarr.errors.invalid_response'),
            $operation,
            $statusCode,
        );
    }
}
