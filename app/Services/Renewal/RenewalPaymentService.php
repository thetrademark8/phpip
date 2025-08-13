<?php

namespace App\Services\Renewal;

use App\Services\Renewal\Contracts\RenewalPaymentServiceInterface;
use App\Services\Renewal\Contracts\RenewalWorkflowServiceInterface;
use App\DataTransferObjects\Renewal\ActionResultDTO;

class RenewalPaymentService implements RenewalPaymentServiceInterface
{
    public function __construct(
        private RenewalWorkflowServiceInterface $workflowService
    ) {}

    public function markPaid(array $ids): ActionResultDTO
    {
        if (empty($ids)) {
            return ActionResultDTO::error('No renewals selected');
        }

        // Update invoice step to 3 (paid)
        $result = $this->workflowService->updateInvoiceStep($ids, 3);
        
        if ($result->success) {
            $count = $result->affectedCount ?? count($ids);
            return ActionResultDTO::success($count, "$count invoices paid");
        }

        return $result;
    }

    public function markDone(array $ids, ?string $doneDate = null): ActionResultDTO
    {
        if (empty($ids)) {
            return ActionResultDTO::error('No renewals selected');
        }

        // Mark renewals as done with optional done date
        $result = $this->workflowService->markAsDone($ids, $doneDate);
        
        if ($result->success) {
            $count = $result->affectedCount ?? count($ids);
            return ActionResultDTO::success($count, "$count renewals cleared");
        }

        return $result;
    }

    public function markLapsing(array $ids): ActionResultDTO
    {
        if (empty($ids)) {
            return ActionResultDTO::error('No renewals selected');
        }

        // Mark renewals as lapsing (abandoned)
        $result = $this->workflowService->markAsLapsing($ids);
        
        if ($result->success) {
            $count = $result->affectedCount ?? count($ids);
            return ActionResultDTO::success($count, "$count renewals marked as lapsing");
        }

        return $result;
    }
}