<?php

namespace App\Contracts\Services;

use Carbon\Carbon;

interface DateServiceInterface
{
    /**
     * Format a date to ISO format (YYYY-MM-DD)
     *
     * @param  Carbon|string|null  $date
     */
    public function toIso($date): ?string;

    /**
     * Parse a date from ISO format
     */
    public function parseIso(?string $date): ?Carbon;

    /**
     * Format a date for display (always ISO format)
     *
     * @param  Carbon|string|null  $date
     */
    public function formatForDisplay($date): ?string;

    /**
     * Format a datetime with time (YYYY-MM-DD HH:MM:SS)
     *
     * @param  Carbon|string|null  $datetime
     */
    public function toIsoDateTime($datetime): ?string;

    /**
     * Parse a date from any common format and return ISO
     */
    public function normalizeToIso(?string $date): ?string;

    /**
     * Validate if a string is in ISO date format
     */
    public function isIsoFormat(string $date): bool;

    /**
     * Get today's date in ISO format
     */
    public function today(): string;

    /**
     * Add days to a date and return in ISO format
     *
     * @param  Carbon|string  $date
     */
    public function addDays($date, int $days): string;
}
