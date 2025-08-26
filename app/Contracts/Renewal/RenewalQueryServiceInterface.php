<?php

namespace App\Contracts\Renewal;

use App\DataTransferObjects\Renewal\RenewalFilterDTO;
use Illuminate\Database\Eloquent\Builder;

interface RenewalQueryServiceInterface
{
    /**
     * Build the base query for renewals
     */
    public function buildQuery(RenewalFilterDTO $filters): Builder;

    /**
     * Apply filters to the query
     */
    public function applyFilters(Builder $query, array $filters): Builder;

    /**
     * Apply query optimizations based on filters
     */
    public function applyOptimizations(Builder $query, RenewalFilterDTO $filters): Builder;

    /**
     * Apply sorting to the query
     */
    public function applySorting(Builder $query, ?int $step, ?int $invoiceStep): Builder;
}