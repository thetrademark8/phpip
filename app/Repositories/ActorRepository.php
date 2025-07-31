<?php

namespace App\Repositories;

use App\Repositories\Contracts\ActorRepositoryInterface;
use App\Models\Actor;
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
        $actor = Actor::with('feeStructure')->find($actorId);
        
        if (!$actor) {
            return null;
        }
        
        return $actor->feeStructure;
    }

    public function getByRole(string $role): Collection
    {
        return Actor::whereHas('roles', function($query) use ($role) {
            $query->where('role_code', $role);
        })->get();
    }

    public function getClients(int $actorId): Collection
    {
        $actor = Actor::find($actorId);
        
        if (!$actor) {
            return collect();
        }
        
        // Get matters where this actor is the agent
        return Actor::whereHas('linkedMatters', function($query) use ($actorId) {
            $query->where('actor_id', $actorId)
                ->where('role_code', 'AGT');
        })
        ->whereHas('linkedMatters', function($query) {
            $query->where('role_code', 'CLI');
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
                    'address' => $parent->address,
                    'address2' => $parent->address2,
                    'city' => $parent->city,
                    'postal_code' => $parent->postal_code,
                    'country' => $parent->country,
                ];
            }
        }
        
        // Return actor's own address
        return [
            'email' => $actor->email,
            'address' => $actor->address,
            'address2' => $actor->address2,
            'city' => $actor->city,
            'postal_code' => $actor->postal_code,
            'country' => $actor->country,
        ];
    }

    public function hasDiscount(int $actorId): bool
    {
        $actor = Actor::find($actorId);
        
        if (!$actor) {
            return false;
        }
        
        return $actor->discount !== null && $actor->discount > 0;
    }

    public function getDiscount(int $actorId): ?float
    {
        $actor = Actor::find($actorId);
        
        if (!$actor) {
            return null;
        }
        
        return $actor->discount;
    }

    public function getVatRate(int $actorId): float
    {
        $actor = Actor::find($actorId);
        
        if (!$actor) {
            return 0.2; // Default VAT rate
        }
        
        // Check if actor has specific VAT rate
        if ($actor->vat_rate !== null) {
            return $actor->vat_rate;
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