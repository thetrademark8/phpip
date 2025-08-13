<?php

namespace App\Services\Renewal\Contracts;

use App\DataTransferObjects\Renewal\ServiceResultDTO;

interface RenewalOrderServiceInterface
{
    /**
     * Create renewal orders for selected renewals
     *
     * @param array $ids Array of renewal task IDs
     * @return ServiceResultDTO
     */
    public function createOrders(array $ids): ServiceResultDTO;

    /**
     * Mark renewals as invoiced
     *
     * @param array $ids Array of renewal task IDs
     * @return ServiceResultDTO
     */
    public function markInvoiced(array $ids): ServiceResultDTO;
}