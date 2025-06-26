<?php

namespace App\Contracts\Services;

use Carbon\Carbon;

interface DatePickerServiceInterface
{
    /**
     * Convert user input date to ISO format for storage
     */
    public function convertToIso(?string $userInput): ?string;

    /**
     * Convert ISO date to display format for user
     *
     * @param  string|Carbon|null  $isoDate
     */
    public function convertToDisplay($isoDate): ?string;

    /**
     * Validate date input format
     */
    public function isValidDate(?string $dateInput): bool;

    /**
     * Parse multiple date formats and return standardized format
     */
    public function parseFlexibleDate(?string $dateInput): ?Carbon;

    /**
     * Get JavaScript configuration for date picker
     */
    public function getJavaScriptConfig(): array;

    /**
     * Get the expected input format for user guidance
     */
    public function getDisplayFormat(): string;

    /**
     * Get the ISO format used for storage
     */
    public function getIsoFormat(): string;
}
