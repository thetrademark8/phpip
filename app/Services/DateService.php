<?php

namespace App\Services;

use App\Contracts\Services\DateServiceInterface;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;

class DateService implements DateServiceInterface
{
    private const ISO_FORMAT = 'Y-m-d';

    private const ISO_DATETIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * Format a date to ISO format (YYYY-MM-DD)
     *
     * @param  Carbon|string|null  $date
     */
    public function toIso($date): ?string
    {
        if ($date === null) {
            return null;
        }

        if ($date instanceof Carbon) {
            return $date->format(self::ISO_FORMAT);
        }

        try {
            return Carbon::parse($date)->format(self::ISO_FORMAT);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse a date from ISO format
     */
    public function parseIso(?string $date): ?Carbon
    {
        if ($date === null || $date === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat(self::ISO_FORMAT, $date)->startOfDay();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Format a date for display (always ISO format)
     *
     * @param  Carbon|string|null  $date
     */
    public function formatForDisplay($date): ?string
    {
        return $this->toIso($date);
    }

    /**
     * Format a datetime with time (YYYY-MM-DD HH:MM:SS)
     *
     * @param  Carbon|string|null  $datetime
     */
    public function toIsoDateTime($datetime): ?string
    {
        if ($datetime === null) {
            return null;
        }

        if ($datetime instanceof Carbon) {
            return $datetime->format(self::ISO_DATETIME_FORMAT);
        }

        try {
            return Carbon::parse($datetime)->format(self::ISO_DATETIME_FORMAT);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse a date from any common format and return ISO
     * Note: For ambiguous dates like 01/02/2025, this assumes European format (d/m/Y)
     * unless the day value is > 12, then it assumes American format (m/d/Y)
     */
    public function normalizeToIso(?string $date): ?string
    {
        if ($date === null || $date === '') {
            return null;
        }

        // If already in ISO format, return as is
        if ($this->isIsoFormat($date)) {
            return $date;
        }

        // Try to detect format based on values
        if (preg_match('#^(\d{1,2})[/\-.](\d{1,2})[/\-.](\d{4})$#', $date, $matches)) {
            $part1 = (int) $matches[1];
            $part2 = (int) $matches[2];
            $year = $matches[3];

            // If first part > 12, it must be day (European format)
            if ($part1 > 12) {
                $day = str_pad($part1, 2, '0', STR_PAD_LEFT);
                $month = str_pad($part2, 2, '0', STR_PAD_LEFT);

                return "$year-$month-$day";
            }

            // If second part > 12, it must be day (American format)
            if ($part2 > 12) {
                $month = str_pad($part1, 2, '0', STR_PAD_LEFT);
                $day = str_pad($part2, 2, '0', STR_PAD_LEFT);

                return "$year-$month-$day";
            }

            // For ambiguous dates, prefer European format (d/m/Y) as per project locale
            $day = str_pad($part1, 2, '0', STR_PAD_LEFT);
            $month = str_pad($part2, 2, '0', STR_PAD_LEFT);

            // Validate the date
            if (checkdate((int) $month, (int) $day, (int) $year)) {
                return "$year-$month-$day";
            }
        }

        // Try parsing with strict formats
        $formats = [
            'Y-m-d',        // ISO
            'Y/m/d',        // Alternative ISO
            'Y.m.d',        // Alternative ISO with dots
            'd-m-Y',        // European with dashes
            'd.m.Y',        // German
        ];

        foreach ($formats as $format) {
            try {
                $carbon = Carbon::createFromFormat($format, $date);

                return $carbon->format(self::ISO_FORMAT);
            } catch (InvalidFormatException $e) {
                continue;
            }
        }

        // If no format matches, try Carbon's parse as last resort
        try {
            return Carbon::parse($date)->format(self::ISO_FORMAT);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Validate if a string is in ISO date format
     */
    public function isIsoFormat(string $date): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
    }

    /**
     * Get today's date in ISO format
     */
    public function today(): string
    {
        return Carbon::today()->format(self::ISO_FORMAT);
    }

    /**
     * Add days to a date and return in ISO format
     *
     * @param  Carbon|string  $date
     */
    public function addDays($date, int $days): string
    {
        if ($date instanceof Carbon) {
            return $date->copy()->addDays($days)->format(self::ISO_FORMAT);
        }

        return Carbon::parse($date)->addDays($days)->format(self::ISO_FORMAT);
    }
}
