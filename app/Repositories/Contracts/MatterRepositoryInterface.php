<?php

namespace App\Repositories\Contracts;

use App\Models\Matter;
use Illuminate\Support\Collection;

interface MatterRepositoryInterface
{
    /**
     * Find a matter by ID
     */
    public function find(int $id): ?Matter;

    /**
     * Find matter with related data for renewal
     */
    public function findWithRenewalData(int $id): ?Matter;

    /**
     * Find multiple matters by IDs
     */
    public function findByIds(array $ids): Collection;

    /**
     * Get matter's current annuity
     */
    public function getCurrentAnnuity(int $matterId): ?int;

    /**
     * Get matter's applicants
     */
    public function getApplicants(int $matterId): Collection;

    /**
     * Get matter's owner
     */
    public function getOwner(int $matterId);

    /**
     * Update matter's responsible
     */
    public function updateResponsible(int $matterId, string $responsible): bool;

    /**
     * Get matters by client
     */
    public function getByClient(int $clientId): Collection;

    /**
     * Get active matters count
     */
    public function getActiveCount(): int;
}