<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use App\DataTransferObjects\Renewal\RenewalFilterDTO;

interface RenewalRepositoryInterface
{
    /**
     * Find renewals by IDs
     */
    public function findByIds(array $ids): Collection;

    /**
     * Find a single renewal by ID
     */
    public function findById(int $id);

    /**
     * Paginate renewal results
     */
    public function paginate(Builder $query, int $perPage = 25): LengthAwarePaginator;

    /**
     * Get renewals by filters
     */
    public function getByFilters(RenewalFilterDTO $filters): Collection;

    /**
     * Update renewal step
     */
    public function updateStep(array $ids, int $step): int;

    /**
     * Update renewal invoice step
     */
    public function updateInvoiceStep(array $ids, int $invoiceStep): int;

    /**
     * Update renewal grace period
     */
    public function updateGracePeriod(array $ids, int $gracePeriod): int;

    /**
     * Mark renewals as done
     */
    public function markAsDone(array $ids, ?string $doneDate = null): int;

    /**
     * Update renewal cost and fee
     */
    public function updateFees(int $id, float $cost, float $fee): bool;

    /**
     * Get renewals for export
     */
    public function getForExport(array $filters = []): Collection;

    /**
     * Get renewals grouped by client
     */
    public function getGroupedByClient(array $ids): Collection;
}