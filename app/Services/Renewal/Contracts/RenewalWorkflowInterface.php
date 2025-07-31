<?php

namespace App\Services\Renewal\Contracts;

use App\DataTransferObjects\Renewal\RenewalDTO;

interface RenewalWorkflowInterface
{
    /**
     * Transition a renewal to a new step
     */
    public function transitionStep(int $renewalId, int $toStep): bool;

    /**
     * Transition a renewal to a new invoice step
     */
    public function transitionInvoiceStep(int $renewalId, int $toInvoiceStep): bool;

    /**
     * Check if a transition is allowed
     */
    public function canTransition(RenewalDTO $renewal, int $toStep): bool;

    /**
     * Get available transitions for a renewal
     */
    public function getAvailableTransitions(RenewalDTO $renewal): array;

    /**
     * Bulk transition renewals to a new step
     */
    public function bulkTransitionStep(array $renewalIds, int $toStep): int;

    /**
     * Bulk transition renewals to a new invoice step
     */
    public function bulkTransitionInvoiceStep(array $renewalIds, int $toInvoiceStep): int;
}