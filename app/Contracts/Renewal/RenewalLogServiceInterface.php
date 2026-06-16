<?php

namespace App\Contracts\Renewal;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface RenewalLogServiceInterface
{
    /**
     * Get paginated renewal logs, optionally filtered
     *
     * @param  array<string, mixed>|null  $filters
     */
    public function getLogs(?array $filters = null, int $perPage = 25): LengthAwarePaginator;

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
