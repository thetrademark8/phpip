<?php

namespace App\Contracts\Renewal;

use App\DataTransferObjects\Renewal\ActionResultDTO;

interface RenewalWorkflowServiceInterface
{
    /**
     * Update renewal step
     */
    public function updateStep(array $ids, int $step): ActionResultDTO;

    /**
     * Update invoice step
     */
    public function updateInvoiceStep(array $ids, int $invoiceStep): ActionResultDTO;

    /**
     * Set grace period for renewals
     */
    public function setGracePeriod(array $ids, int $gracePeriod): ActionResultDTO;

    /**
     * Mark renewals as done
     */
    public function markAsDone(array $ids, ?string $doneDate = null): ActionResultDTO;

    /**
     * Abandon renewals
     */
    public function abandon(array $ids): ActionResultDTO;

    /**
     * Mark renewals as payment order received
     */
    public function markAsPaymentOrderReceived(array $ids): ActionResultDTO;

    /**
     * Update both step and invoice step simultaneously
     */
    public function updateStepAndInvoiceStep(array $ids, int $step, int $invoiceStep): ActionResultDTO;

    /**
     * Mark renewals as lapsing
     */
    public function markAsLapsing(array $ids): ActionResultDTO;
}