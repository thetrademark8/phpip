<?php

namespace App\Services\Renewal\Contracts;

use App\DataTransferObjects\Renewal\ActionResultDTO;

interface RenewalPaymentServiceInterface
{
    /**
     * Mark renewals as paid
     *
     * @param array $ids Array of renewal task IDs
     * @return ActionResultDTO
     */
    public function markPaid(array $ids): ActionResultDTO;

    /**
     * Mark renewals as done/closed
     *
     * @param array $ids Array of renewal task IDs
     * @param string|null $doneDate Optional done date, defaults to now
     * @return ActionResultDTO
     */
    public function markDone(array $ids, ?string $doneDate = null): ActionResultDTO;

    /**
     * Mark renewals as lapsing (abandoned)
     *
     * @param array $ids Array of renewal task IDs
     * @return ActionResultDTO
     */
    public function markLapsing(array $ids): ActionResultDTO;
}