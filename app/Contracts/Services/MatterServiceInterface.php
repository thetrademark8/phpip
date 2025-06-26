<?php

namespace App\Contracts\Services;

use App\Models\Matter;
use Illuminate\Support\Collection;

interface MatterServiceInterface
{
    /**
     * Create a new matter
     *
     * @param array $data
     * @return Matter
     */
    public function create(array $data): Matter;
    
    /**
     * Update an existing matter
     *
     * @param Matter $matter
     * @param array $data
     * @return Matter
     */
    public function update(Matter $matter, array $data): Matter;
    
    /**
     * Delete a matter
     *
     * @param Matter $matter
     * @return bool
     */
    public function delete(Matter $matter): bool;
    
    /**
     * Clone a matter
     *
     * @param Matter $matter
     * @param array $overrides
     * @return Matter
     */
    public function clone(Matter $matter, array $overrides = []): Matter;
    
    /**
     * Create matter family from patent data
     *
     * @param array $patentData
     * @return Collection
     */
    public function createFamily(array $patentData): Collection;
    
    /**
     * Copy matter to different countries for international filing
     *
     * @param Matter $matter
     * @param array $countries
     * @return Collection
     */
    public function copyToCountries(Matter $matter, array $countries): Collection;
    
    /**
     * Get matters expiring within days
     *
     * @param int $days
     * @return Collection
     */
    public function getExpiringMatters(int $days = 30): Collection;
    
    /**
     * Calculate renewal deadline for a matter
     *
     * @param Matter $matter
     * @return string|null ISO date format
     */
    public function calculateRenewalDeadline(Matter $matter): ?string;
}