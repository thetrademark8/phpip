<?php

namespace App\Repositories\Contracts;

use App\Models\Event;
use Illuminate\Support\Collection;

interface EventRepositoryInterface
{
    /**
     * Find an event by ID
     */
    public function find(int $id): ?Event;

    /**
     * Find events by matter ID and code
     */
    public function findByMatterAndCode(int $matterId, string $code): Collection;

    /**
     * Get renewal events for a matter
     */
    public function getRenewalEvents(int $matterId): Collection;

    /**
     * Create a new event
     */
    public function create(array $data): Event;

    /**
     * Update an event
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete an event
     */
    public function delete(int $id): bool;

    /**
     * Get events by date range
     */
    public function getByDateRange(\DateTime $from, \DateTime $to): Collection;

    /**
     * Get overdue events
     */
    public function getOverdueEvents(): Collection;

    /**
     * Mark event as completed
     */
    public function markAsCompleted(int $id, ?\DateTime $completedDate = null): bool;

    /**
     * Get event templates
     */
    public function getTemplates(): Collection;

    /**
     * Get events for export
     */
    public function getForExport(array $filters = []): Collection;
}