<?php

namespace App\Contracts\Services;

use App\Models\Matter;
use App\Models\Task;
use Illuminate\Support\Collection;

interface RenewalServiceInterface
{
    /**
     * Calculate renewal fees for a matter
     *
     * @param Matter $matter
     * @return array
     */
    public function calculateFees(Matter $matter): array;
    
    /**
     * Create renewal tasks for a matter
     *
     * @param Matter $matter
     * @param string $startDate ISO format
     * @return Collection
     */
    public function createRenewalTasks(Matter $matter, string $startDate): Collection;
    
    /**
     * Get upcoming renewals for a period
     *
     * @param int $months
     * @return Collection
     */
    public function getUpcomingRenewals(int $months = 12): Collection;
    
    /**
     * Process renewal for a task
     *
     * @param Task $task
     * @param array $data
     * @return bool
     */
    public function processRenewal(Task $task, array $data): bool;
    
    /**
     * Get renewal cycle for matter type
     *
     * @param string $categoryCode
     * @return int Years between renewals
     */
    public function getRenewalCycle(string $categoryCode): int;
    
    /**
     * Check if matter requires renewal
     *
     * @param Matter $matter
     * @return bool
     */
    public function requiresRenewal(Matter $matter): bool;
    
    /**
     * Sync renewal data from external service (e.g., Renewr.io)
     *
     * @param Matter $matter
     * @return array
     */
    public function syncExternalData(Matter $matter): array;
    
    /**
     * Cancel renewal tasks for a matter
     *
     * @param Matter $matter
     * @param string $reason
     * @return int Number of tasks cancelled
     */
    public function cancelRenewalTasks(Matter $matter, string $reason): int;
}