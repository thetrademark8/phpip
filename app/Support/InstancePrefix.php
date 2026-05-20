<?php

namespace App\Support;

use Illuminate\Support\Str;

class InstancePrefix
{
    public static function resolve(): ?string
    {
        $explicit = env('AWS_BUCKET_PREFIX');
        if ($explicit) {
            return trim($explicit, '/');
        }

        $host = parse_url((string) env('APP_URL'), PHP_URL_HOST);

        return $host ? Str::slug($host) : null;
    }
}
