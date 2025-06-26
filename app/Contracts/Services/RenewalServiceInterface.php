<?php

namespace App\Contracts\Services;

use App\Models\Matter;
use App\Models\Task;
use Illuminate\Support\Collection;

interface RenewalServiceInterface
{
    /**
     * Calculate renewal fees for a matter
     */
    public function calculateFees(Matter $matter): array;

    /**
     * Create renewal tasks for a matter
     *
     * @param  string  $startDate  ISO format
     */
    public function createRenewalTasks(Matter $matter, string $startDate): Collection;

    /**
     * Get upcoming renewals for a period
     */
    public function getUpcomingRenewals(int $months = 12): Collection;

    /**
     * Process renewal for a task
     */
    public function processRenewal(Task $task, array $data): bool;

    /**
     * Get renewal cycle for matter type
     *
     * @return int Years between renewals
     */
    public function getRenewalCycle(string $categoryCode): int;

    /**
     * Check if matter requires renewal
     */
    public function requiresRenewal(Matter $matter): bool;

    /**
     * Sync renewal data from external service (e.g., Renewr.io)
     */
    public function syncExternalData(Matter $matter): array;

    /**
     * Cancel renewal tasks for a matter
     *
     * @return int Number of tasks cancelled
     */
    public function cancelRenewalTasks(Matter $matter, string $reason): int;
}
