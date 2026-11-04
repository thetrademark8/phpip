<?php

namespace App\Exceptions\Dolibarr;

class DolibarrConfigurationException extends DolibarrException
{
    public static function missing(string $setting): self
    {
        return new self(
            __('dolibarr.errors.configuration_missing', ['setting' => $setting]),
            'configuration',
        );
    }

    public static function invalidUrl(): self
    {
        return new self(
            __('dolibarr.errors.invalid_url'),
            'configuration',
        );
    }

    public static function unsupportedVersion(string $version): self
    {
        return new self(
            __('dolibarr.errors.unsupported_version', ['version' => $version]),
            'configuration',
        );
    }
}
