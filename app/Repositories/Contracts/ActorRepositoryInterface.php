<?php

namespace App\Repositories\Contracts;

use App\Models\Actor;
use Illuminate\Support\Collection;

interface ActorRepositoryInterface
{
    /**
     * Find an actor by ID
     */
    public function find(int $id): ?Actor;

    /**
     * Find actor by email
     */
    public function findByEmail(string $email): ?Actor;

    /**
     * Find multiple actors by IDs
     */
    public function findByIds(array $ids): Collection;

    /**
     * Get actor's fee structure
     */
    public function getFeeStructure(int $actorId);

    /**
     * Get actors by role
     */
    public function getByRole(string $role): Collection;

    /**
     * Get actor's clients
     */
    public function getClients(int $actorId): Collection;

    /**
     * Get actor's invoicing address
     */
    public function getInvoicingAddress(int $actorId): ?array;

    /**
     * Check if actor has discount
     */
    public function hasDiscount(int $actorId): bool;

    /**
     * Get actor's discount
     */
    public function getDiscount(int $actorId): ?float;

    /**
     * Get actor's VAT rate
     */
    public function getVatRate(int $actorId): float;

    /**
     * Check if actor is small entity
     */
    public function isSmallEntity(int $actorId): bool;
}