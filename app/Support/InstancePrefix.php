<?php

namespace App\Support;

use Illuminate\Support\Str;

class InstancePrefix
{
    /**
     * Resolve the storage prefix for the current instance.
     *
     * Values are passed in from the configuration files so that no
     * environment variable is read outside of the config directory.
     */
    public static function resolve(?string $explicit = null, ?string $appUrl = null): ?string
    {
        if ($explicit) {
            return trim($explicit, '/');
        }

        $host = parse_url((string) $appUrl, PHP_URL_HOST);

        return $host ? Str::slug($host) : null;
    }
}
