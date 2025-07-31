<?php

namespace App\Services\Renewal\Contracts;

use Illuminate\Database\Eloquent\Builder;
use App\DataTransferObjects\Renewal\RenewalFilterDTO;

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