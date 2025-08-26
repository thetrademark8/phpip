<?php

namespace App\Services\Renewal\Contracts;

use Illuminate\Support\Collection;

interface RenewalLogServiceInterface
{
    /**
     * Log a single renewal action
     */
    public function logAction(int $taskId, string $action, array $data = []): void;

    /**
     * Log a bulk renewal action
     */
    public function logBulkAction(string $action, Collection $renewals, array $additionalData = []): void;

    /**
     * Log a step transition
     */
    public function logStepTransition(int $taskId, int $fromStep, int $toStep): void;

    /**
     * Log an invoice step transition
     */
    public function logInvoiceStepTransition(int $taskId, int $fromStep, int $toStep): void;

    /**
     * Get the next job ID for batch operations
     */
    public function getNextJobId(): int;

    /**
     * Create log entries for email actions
     */
    public function logEmailAction(array $taskIds, string $emailType, int $fromStep, int $toStep): void;
}