<?php

namespace App\Services\Renewal;

use App\Services\Renewal\Contracts\RenewalOrderServiceInterface;
use App\Services\Renewal\Contracts\RenewalWorkflowServiceInterface;
use App\DataTransferObjects\Renewal\ServiceResultDTO;

class RenewalOrderService implements RenewalOrderServiceInterface
{
    public function __construct(
        private RenewalWorkflowServiceInterface $workflowService
    ) {}

    public function createOrders(array $ids): ServiceResultDTO
    {
        if (empty($ids)) {
            return new ServiceResultDTO(false, 'No renewals selected');
        }

        // Move to step 4 (payment order) and invoice step 1 (invoiced)
        $result = $this->workflowService->updateStepAndInvoiceStep($ids, 4, 1);
        
        if ($result->success) {
            return new ServiceResultDTO(true, 'Renewal orders created successfully');
        }

        return $result;
    }

    public function markInvoiced(array $ids): ServiceResultDTO
    {
        if (empty($ids)) {
            return new ServiceResultDTO(false, 'No renewals selected');
        }

        // Update invoice step to 2 (invoiced)
        $result = $this->workflowService->updateInvoiceStep($ids, 2);
        
        if ($result->success) {
            return new ServiceResultDTO(true, 'Renewals marked as invoiced');
        }

        return $result;
    }
}