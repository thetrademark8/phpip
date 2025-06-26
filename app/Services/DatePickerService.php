<?php

namespace App\Services;

use App\Contracts\Services\DatePickerServiceInterface;
use App\Contracts\Services\DateServiceInterface;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;

class DatePickerService implements DatePickerServiceInterface
{
    private const DISPLAY_FORMAT = 'd/m/Y';

    private const ISO_FORMAT = 'Y-m-d';

    public function __construct(
        private DateServiceInterface $dateService
    ) {}

    /**
     * Convert user input date to ISO format for storage
     */
    public function convertToIso(?string $userInput): ?string
    {
        if ($userInput === null || trim($userInput) === '') {
            return null;
        }

        $parsed = $this->parseFlexibleDate($userInput);

        return $parsed ? $this->dateService->toIso($parsed) : null;
    }

    /**
     * Convert ISO date to display format for user
     *
     * @param  string|Carbon|null  $isoDate
     */
    public function convertToDisplay($isoDate): ?string
    {
        if ($isoDate === null) {
            return null;
        }

        try {
            if ($isoDate instanceof Carbon) {
                return $isoDate->format(self::DISPLAY_FORMAT);
            }

            if (is_string($isoDate)) {
                $carbon = $this->dateService->parseIso($isoDate);

                return $carbon ? $carbon->format(self::DISPLAY_FORMAT) : null;
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Validate date input format
     */
    public function isValidDate(?string $dateInput): bool
    {
        if ($dateInput === null || trim($dateInput) === '') {
            return true; // Allow empty dates
        }

        return $this->parseFlexibleDate($dateInput) !== null;
    }

    /**
     * Parse multiple date formats and return standardized format
     */
    public function parseFlexibleDate(?string $dateInput): ?Carbon
    {
        if ($dateInput === null || trim($dateInput) === '') {
            return null;
        }

        $dateInput = trim($dateInput);

        // Try ISO format first (YYYY-MM-DD)
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $dateInput)) {
            try {
                return Carbon::createFromFormat(self::ISO_FORMAT, $dateInput)->startOfDay();
            } catch (InvalidFormatException $e) {
                // Continue to other formats
            }
        }

        // Define formats to try in order of preference
        $formats = [
            'd/m/Y',      // 25/12/2023
            'd-m-Y',      // 25-12-2023
            'd.m.Y',      // 25.12.2023
            'm/d/Y',      // 12/25/2023
            'm-d-Y',      // 12-25-2023
            'd/m/y',      // 25/12/23
            'd-m-y',      // 25-12-23
            'd.m.y',      // 25.12.23
            'm/d/y',      // 12/25/23
            'm-d-y',      // 12-25-23
        ];

        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $dateInput);

                // Validate the parsed date makes sense and wasn't auto-corrected
                if ($parsed && $this->isReasonableDate($parsed) && $this->isStrictlyValid($parsed, $format, $dateInput)) {
                    return $parsed->startOfDay();
                }
            } catch (InvalidFormatException $e) {
                // Try next format
                continue;
            }
        }

        // Try natural language parsing as last resort
        try {
            $parsed = Carbon::parse($dateInput);
            if ($this->isReasonableDate($parsed)) {
                return $parsed->startOfDay();
            }
        } catch (\Exception $e) {
            // No valid date found
        }

        return null;
    }

    /**
     * Check if a date is reasonable (not too far in past/future)
     */
    private function isReasonableDate(Carbon $date): bool
    {
        $now = Carbon::now();

        // Allow dates from 1900 to 100 years in the future
        $minDate = Carbon::create(1900, 1, 1);
        $maxDate = $now->copy()->addYears(100);

        return $date->between($minDate, $maxDate);
    }

    /**
     * Check if parsed date matches original input (prevents auto-correction)
     */
    private function isStrictlyValid(Carbon $parsed, string $format, string $originalInput): bool
    {
        // Format the parsed date back to the original format
        $reformatted = $parsed->format($format);

        // Compare with original input (normalize separators)
        $normalizedInput = $originalInput;
        $normalizedReformatted = $reformatted;

        // Handle different separators
        $normalizedInput = str_replace(['-', '.'], '/', $normalizedInput);
        $normalizedReformatted = str_replace(['-', '.'], '/', $normalizedReformatted);

        return $normalizedInput === $normalizedReformatted;
    }

    /**
     * Get JavaScript configuration for date picker
     */
    public function getJavaScriptConfig(): array
    {
        return [
            'dateFormat' => 'Y-m-d',      // Internal format (ISO)
            'altFormat' => 'd/m/Y',       // Display format for user
            'altInput' => true,           // Use alternative input for display
            'allowInput' => true,         // Allow manual typing
            'allowInvalidPreload' => false,
            'clickOpens' => true,
            'wrap' => false,
            'locale' => app()->getLocale(),
            'placeholder' => $this->getDisplayFormat(),
        ];
    }

    /**
     * Get the expected input format for user guidance
     */
    public function getDisplayFormat(): string
    {
        return 'dd/mm/yyyy';
    }

    /**
     * Get the ISO format used for storage
     */
    public function getIsoFormat(): string
    {
        return self::ISO_FORMAT;
    }

    /**
     * Detect if a date string is likely in DD/MM/YYYY or MM/DD/YYYY format
     *
     * @return string Either 'd/m/Y' or 'm/d/Y'
     */
    public function detectDateFormat(string $dateString): string
    {
        // Extract parts
        preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{2,4})$/', $dateString, $matches);

        if (! $matches) {
            return 'd/m/Y'; // Default to DD/MM/YYYY
        }

        $part1 = (int) $matches[1];
        $part2 = (int) $matches[2];

        // If first part > 12, it must be day (DD/MM/YYYY)
        if ($part1 > 12) {
            return 'd/m/Y';
        }

        // If second part > 12, it must be day (MM/DD/YYYY)
        if ($part2 > 12) {
            return 'm/d/Y';
        }

        // Both parts <= 12, ambiguous
        // Use application locale to decide
        $locale = app()->getLocale();

        // US format for US locale
        if ($locale === 'en_US' || $locale === 'en-US') {
            return 'm/d/Y';
        }

        // European format for most other locales
        return 'd/m/Y';
    }

    /**
     * Convert legacy date format to new format
     */
    public function convertLegacyDate(?string $legacyDate): ?string
    {
        if (! $legacyDate) {
            return null;
        }

        // Handle Carbon isoFormat('L') outputs
        $parsed = $this->parseFlexibleDate($legacyDate);

        return $parsed ? $this->convertToDisplay($parsed) : null;
    }
}
