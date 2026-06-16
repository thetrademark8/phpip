<?php

namespace App\Repositories;

use App\Models\Actor;
use App\Repositories\Contracts\ActorRepositoryInterface;
use Illuminate\Support\Collection;

class ActorRepository implements ActorRepositoryInterface
{
    public function find(int $id): ?Actor
    {
        return Actor::find($id);
    }

    public function findByEmail(string $email): ?Actor
    {
        return Actor::where('email', $email)->first();
    }

    public function findByIds(array $ids): Collection
    {
        return Actor::whereIn('id', $ids)->get();
    }

    public function getFeeStructure(int $actorId)
    {
        $actor = Actor::find($actorId);

        if (!$actor) {
            return null;
        }

        return $actor->ren_discount;
    }

    public function getByRole(string $role): Collection
    {
        return Actor::whereHas('mattersWithLnk', function ($query) use ($role) {
            $query->where('role', $role);
        })->get();
    }

    public function getClients(int $actorId): Collection
    {
        $actor = Actor::find($actorId);

        if (!$actor) {
            return collect();
        }

        // Get matters where this actor is the agent
        return Actor::whereHas('mattersWithLnk', function ($query) use ($actorId) {
            $query->where('actor_id', $actorId)
                ->where('role', 'AGT');
        })
            ->whereHas('mattersWithLnk', function ($query) {
                $query->where('role', 'CLI');
            })
            ->distinct()
            ->get();
    }

    public function getInvoicingAddress(int $actorId): ?array
    {
        $actor = Actor::find($actorId);

        if (!$actor) {
            return null;
        }

        // Check if actor has a parent for invoicing
        if ($actor->parent_id) {
            $parent = Actor::find($actor->parent_id);
            if ($parent) {
                return [
                    'email' => $parent->email,
                    'address' => $parent->address_billing ?? $parent->address,
                    'country' => $parent->country_billing ?? $parent->country,
                ];
            }
        }

        // Return actor's own address
        return [
            'email' => $actor->email,
            'address' => $actor->address_billing ?? $actor->address,
            'country' => $actor->country_billing ?? $actor->country,
        ];
    }

    public function hasDiscount(int $actorId): bool
    {
        $actor = Actor::find($actorId);

        if (!$actor) {
            return false;
        }

        return $actor->ren_discount !== null && $actor->ren_discount > 0;
    }

    public function getDiscount(int $actorId): ?float
    {
        $actor = Actor::find($actorId);

        if (!$actor) {
            return null;
        }

        return $actor->ren_discount;
    }

    public function getVatRate(int $actorId): float
    {
        $actor = Actor::find($actorId);

        if (!$actor) {
            return 0.2; // Default VAT rate
        }

        // Check country-specific VAT rates
        if ($actor->country) {
            $countryVatRates = config('renewal.vat_rates', []);
            if (isset($countryVatRates[$actor->country])) {
                return $countryVatRates[$actor->country];
            }
        }

        return 0.2; // Default VAT rate
    }

    public function isSmallEntity(int $actorId): bool
    {
        $actor = Actor::find($actorId);

        if (!$actor) {
            return false;
        }

        return (bool) $actor->small_entity;
    }
}
