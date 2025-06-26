<?php

namespace App\Contracts\Services;

use App\Models\Matter;
use Illuminate\Support\Collection;

interface MatterServiceInterface
{
    /**
     * Create a new matter
     */
    public function create(array $data): Matter;

    /**
     * Update an existing matter
     */
    public function update(Matter $matter, array $data): Matter;

    /**
     * Delete a matter
     */
    public function delete(Matter $matter): bool;

    /**
     * Clone a matter
     */
    public function clone(Matter $matter, array $overrides = []): Matter;

    /**
     * Create matter family from patent data
     */
    public function createFamily(array $patentData): Collection;

    /**
     * Copy matter to different countries for international filing
     */
    public function copyToCountries(Matter $matter, array $countries): Collection;

    /**
     * Get matters expiring within days
     */
    public function getExpiringMatters(int $days = 30): Collection;

    /**
     * Calculate renewal deadline for a matter
     *
     * @return string|null ISO date format
     */
    public function calculateRenewalDeadline(Matter $matter): ?string;
}
