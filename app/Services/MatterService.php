<?php

namespace App\Services;

use App\Contracts\Services\MatterServiceInterface;
use App\Models\Matter;
use App\Repositories\MatterRepository;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MatterService implements MatterServiceInterface
{
    public function __construct(
        protected MatterRepository $repository
    ) {}

    /**
     * Create a new matter
     */
    public function create(array $data): Matter
    {
        return $this->repository->create($data);
    }

    /**
     * Update an existing matter
     */
    public function update(Matter $matter, array $data): Matter
    {
        return $this->repository->update($matter, $data);
    }

    /**
     * Delete a matter
     */
    public function delete(Matter $matter): bool
    {
        return $this->repository->delete($matter);
    }

    /**
     * Clone a matter
     */
    public function clone(Matter $matter, array $overrides = []): Matter
    {
        $clone = $matter->replicate();
        $clone->fill($overrides);
        $clone->save();

        // Clone related entities if needed
        $this->cloneRelatedEntities($matter, $clone);

        return $clone;
    }

    /**
     * Create matter family from patent data
     */
    public function createFamily(array $patentData): Collection
    {
        // Implementation for creating patent families
        // This will be implemented when needed for patent functionality
        return collect();
    }

    /**
     * Copy matter to different countries for international filing
     */
    public function copyToCountries(Matter $matter, array $countries): Collection
    {
        $copies = collect();

        foreach ($countries as $country) {
            $copy = $this->clone($matter, [
                'country' => $country,
                'origin' => $matter->country,
                'parent_id' => $matter->id,
            ]);
            $copies->push($copy);
        }

        return $copies;
    }

    /**
     * Get matters expiring within days
     */
    public function getExpiringMatters(int $days = 30): Collection
    {
        return $this->repository->getExpiringWithinDays($days);
    }

    /**
     * Calculate renewal deadline for a matter
     */
    public function calculateRenewalDeadline(Matter $matter): ?string
    {
        // This should use RenewalService when fully implemented
        return $matter->expire_date?->format('Y-m-d');
    }

    /**
     * Search and filter matters
     */
    public function searchMatters(array $filters, array $options = []): LengthAwarePaginator
    {
        $sortKey = $options['sortkey'] ?? 'id';
        $sortDir = $options['sortdir'] ?? 'asc';
        $perPage = $options['per_page'] ?? 25;
        $displayWith = $options['display_with'] ?? null;
        $includeDead = $options['include_dead'] ?? false;

        return $this->repository->searchWithFilters(
            $filters,
            $sortKey,
            $sortDir,
            $perPage,
            $displayWith,
            $includeDead
        );
    }

    /**
     * Export matters based on filters
     */
    public function exportMatters(array $filters, array $options = []): Collection
    {
        $sortKey = $options['sortkey'] ?? 'id';
        $sortDir = $options['sortdir'] ?? 'asc';
        $displayWith = $options['display_with'] ?? null;
        $includeDead = $options['include_dead'] ?? false;

        return $this->repository->getFilteredForExport(
            $filters,
            $sortKey,
            $sortDir,
            $displayWith,
            $includeDead
        );
    }

    /**
     * Clone related entities
     */
    protected function cloneRelatedEntities(Matter $original, Matter $clone): void
    {
        // Clone classifiers
        foreach ($original->classifiers as $classifier) {
            $newClassifier = $classifier->replicate();
            $newClassifier->matter_id = $clone->id;
            $newClassifier->save();
        }

        // Clone actors with their pivot data
        foreach ($original->actors as $actor) {
            $clone->actors()->attach($actor->id, $actor->pivot->toArray());
        }
    }
}