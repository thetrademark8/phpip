<?php

namespace App\Services\Renewal;

use App\Services\Renewal\Contracts\RenewalWorkflowServiceInterface;
use App\DataTransferObjects\Renewal\ActionResultDTO;
use App\Repositories\Contracts\RenewalRepositoryInterface;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Models\RenewalsLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Service responsible for managing renewal workflow transitions and state changes.
 *
 * This service handles the business logic for moving renewals through their workflow
 * steps, managing invoice steps, grace periods, and completion/abandonment states.
 * All operations are wrapped in database transactions to ensure data consistency.
 *
 * Workflow steps:
 * - 0: Open
 * - 1: Instructions sent
 * - 2: Reminder sent
 * - 3: Payment requested
 * - 4: Invoiced
 * - 5: Receipts issued
 * - 10: Closed (completed)
 * - 11: Abandoned
 *
 * @package App\Services\Renewal
 */
class RenewalWorkflowService implements RenewalWorkflowServiceInterface
{
    /**
     * Create a new RenewalWorkflowService instance.
     *
     * @param RenewalRepositoryInterface $renewalRepository Repository for renewal data access
     * @param EventRepositoryInterface $eventRepository Repository for event management
     */
    public function __construct(
        private RenewalRepositoryInterface $renewalRepository,
        private EventRepositoryInterface $eventRepository
    ) {}

    /**
     * Update the workflow step for multiple renewals.
     *
     * Validates the step value and updates all specified renewals to the new step.
     * The operation is wrapped in a database transaction and logged for audit purposes.
     *
     * @param array<int> $ids Array of renewal IDs to update
     * @param int $step The target workflow step (0-5, 10, 11)
     * @return ActionResultDTO Result containing success status, affected count, and message
     * @throws \Exception If database transaction fails
     */
    public function updateStep(array $ids, int $step): ActionResultDTO
    {
        try {
            DB::beginTransaction();
            
            // Validate step
            if (!$this->isValidStep($step)) {
                DB::rollback();
                return ActionResultDTO::error('Invalid step value');
            }
            
            $count = $this->renewalRepository->updateStep($ids, $step);
            
            // Log the action if needed
            if ($count > 0) {
                $this->logAction($ids, "Updated to step $step", 'step_update');
            }
            
            DB::commit();
            
            return ActionResultDTO::success($count, "Updated $count renewals to step $step");
        } catch (\Exception $e) {
            DB::rollback();
            return ActionResultDTO::error('Failed to update step: ' . $e->getMessage());
        }
    }

    /**
     * Update the invoice step for multiple renewals.
     *
     * Invoice steps track the billing status independently from workflow steps:
     * - 0: Not invoiced
     * - 1: Invoice sent
     * - 2: Invoice paid
     * - 3: Receipt issued
     *
     * @param array<int> $ids Array of renewal IDs to update
     * @param int $invoiceStep The target invoice step (0-3)
     * @return ActionResultDTO Result containing success status, affected count, and message
     * @throws \Exception If database transaction fails
     */
    public function updateInvoiceStep(array $ids, int $invoiceStep): ActionResultDTO
    {
        try {
            DB::beginTransaction();
            
            // Validate invoice step
            if (!$this->isValidInvoiceStep($invoiceStep)) {
                DB::rollback();
                return ActionResultDTO::error('Invalid invoice step value');
            }
            
            $count = $this->renewalRepository->updateInvoiceStep($ids, $invoiceStep);
            
            // Log the action if needed
            if ($count > 0) {
                $this->logAction($ids, "Updated to invoice step $invoiceStep", 'invoice_step_update');
            }
            
            DB::commit();
            
            return ActionResultDTO::success($count, "Updated $count renewals to invoice step $invoiceStep");
        } catch (\Exception $e) {
            DB::rollback();
            return ActionResultDTO::error('Failed to update invoice step: ' . $e->getMessage());
        }
    }

    /**
     * Set the grace period for multiple renewals.
     *
     * Grace period determines the extended deadline for renewal actions:
     * - 0: No grace period
     * - 1: 1 month grace period
     * - 2: 2 months grace period
     * - 3: 3 months grace period
     *
     * This affects financial calculations by extending payment deadlines and
     * potentially reducing late fees during the grace period.
     *
     * @param array<int> $ids Array of renewal IDs to update
     * @param int $gracePeriod Grace period value (0-3)
     * @return ActionResultDTO Result containing success status, affected count, and message
     * @throws \Exception If database transaction fails
     */
    public function setGracePeriod(array $ids, int $gracePeriod): ActionResultDTO
    {
        try {
            DB::beginTransaction();
            
            // Validate grace period
            if ($gracePeriod < 0 || $gracePeriod > 3) {
                DB::rollback();
                return ActionResultDTO::error('Invalid grace period value');
            }
            
            $count = $this->renewalRepository->updateGracePeriod($ids, $gracePeriod);
            
            // Log the action if needed
            if ($count > 0) {
                $this->logAction($ids, "Set grace period to $gracePeriod", 'grace_period_update');
            }
            
            DB::commit();
            
            return ActionResultDTO::success($count, "Updated $count renewals with grace period $gracePeriod");
        } catch (\Exception $e) {
            DB::rollback();
            return ActionResultDTO::error('Failed to set grace period: ' . $e->getMessage());
        }
    }

    /**
     * Mark multiple renewals as completed.
     *
     * Sets the renewal done date and automatically transitions to step 10 (Closed).
     * This indicates successful completion of the renewal process. The done date
     * represents when the renewal was officially filed or completed.
     *
     * @param array<int> $ids Array of renewal IDs to mark as done
     * @param string|null $doneDate Completion date in Y-m-d format (defaults to today)
     * @return ActionResultDTO Result containing success status, affected count, and message
     * @throws \Exception If database transaction fails
     */
    public function markAsDone(array $ids, ?string $doneDate = null): ActionResultDTO
    {
        try {
            DB::beginTransaction();
            
            // Default to today if no date provided
            $date = $doneDate ?? Carbon::now()->format('Y-m-d');
            
            // Validate date
            if (!$this->isValidDate($date)) {
                DB::rollback();
                return ActionResultDTO::error('Invalid date format');
            }
            
            $count = $this->renewalRepository->markAsDone($ids, $date);
            
            // Update to step 10 (closed) automatically
            if ($count > 0) {
                $this->renewalRepository->updateStep($ids, 10);
                $this->logAction($ids, "Marked as done on $date", 'marked_done');
            }
            
            DB::commit();
            
            return ActionResultDTO::success($count, "Marked $count renewals as done");
        } catch (\Exception $e) {
            DB::rollback();
            return ActionResultDTO::error('Failed to mark as done: ' . $e->getMessage());
        }
    }

    /**
     * Abandon multiple renewals.
     *
     * Marks renewals as abandoned by setting today as the done date and transitioning
     * to step 11 (Abandoned). Creates an abandon event (ABA) for each renewal to
     * maintain proper audit trail. This action is irreversible and indicates the
     * renewal process was terminated without completion.
     *
     * @param array<int> $ids Array of renewal IDs to abandon
     * @return ActionResultDTO Result containing success status, affected count, and message
     * @throws \Exception If database transaction fails or event creation fails
     */
    public function abandon(array $ids): ActionResultDTO
    {
        try {
            DB::beginTransaction();
            
            // Mark as done with today's date
            $count = $this->renewalRepository->markAsDone($ids, Carbon::now()->format('Y-m-d'));
            
            // Set step to 11 (abandoned)
            if ($count > 0) {
                $this->renewalRepository->updateStep($ids, 11);
                $this->logAction($ids, "Abandoned renewals", 'abandoned');
                
                // Create abandon events if needed
                foreach ($ids as $id) {
                    $renewal = $this->renewalRepository->findById($id);
                    if ($renewal) {
                        $this->createAbandonEvent($renewal);
                    }
                }
            }
            
            DB::commit();
            
            return ActionResultDTO::success($count, "Abandoned $count renewals");
        } catch (\Exception $e) {
            DB::rollback();
            return ActionResultDTO::error('Failed to abandon renewals: ' . $e->getMessage());
        }
    }

    /**
     * Mark renewals as payment order received.
     * Transitions from step 2 to step 4 and sets invoice_step to 1.
     *
     * @param array<int> $ids Array of renewal IDs to update
     * @return ActionResultDTO Result containing success status, affected count, and message
     * @throws \Exception If database transaction fails
     */
    public function markAsPaymentOrderReceived(array $ids): ActionResultDTO
    {
        try {
            DB::beginTransaction();
            
            // Get current states for logging
            $renewals = $this->renewalRepository->findByIds($ids);
            
            // Update step to 4 and invoice_step to 1
            $stepCount = $this->renewalRepository->updateStep($ids, 4);
            $invoiceCount = $this->renewalRepository->updateInvoiceStep($ids, 1);
            
            if ($stepCount > 0) {
                // Create proper logs with correct from/to values
                $jobId = RenewalsLog::max('job_id') + 1;
                $logs = [];
                
                foreach ($renewals as $renewal) {
                    $logs[] = [
                        'task_id' => $renewal->id,
                        'job_id' => $jobId,
                        'from_step' => $renewal->step ?? 2,
                        'to_step' => 4,
                        'from_invoice' => $renewal->invoice_step ?? 0,
                        'to_invoice' => 1,
                        'creator' => auth()->user()->login ?? 'system',
                        'created_at' => now(),
                    ];
                }
                
                RenewalsLog::insert($logs);
            }
            
            DB::commit();
            
            return ActionResultDTO::success($stepCount, "Marked $stepCount renewals as payment order received");
        } catch (\Exception $e) {
            DB::rollback();
            return ActionResultDTO::error('Failed to mark as payment order received: ' . $e->getMessage());
        }
    }

    /**
     * Check if a workflow transition is valid.
     *
     * Validates whether a renewal can transition from one step to another based on
     * predefined workflow rules. Some transitions can skip steps (e.g., direct to
     * abandoned), while others must follow sequential progression.
     *
     * @param int $fromStep Current workflow step
     * @param int $toStep Target workflow step
     * @return bool True if the transition is allowed, false otherwise
     */
    /**
     * Validate if a workflow step value is valid.
     *
     * @param int $step Step value to validate
     * @return bool True if step is valid (0-5, 10, 11), false otherwise
     */
    private function isValidStep(int $step): bool
    {
        return in_array($step, [0, 1, 2, 3, 4, 5, 10, 11]);
    }

    /**
     * Validate if an invoice step value is valid.
     *
     * @param int $invoiceStep Invoice step value to validate
     * @return bool True if invoice step is valid (0-3), false otherwise
     */
    private function isValidInvoiceStep(int $invoiceStep): bool
    {
        return in_array($invoiceStep, [0, 1, 2, 3]);
    }

    /**
     * Validate if a date string is in valid format.
     *
     * Uses Carbon to parse the date string and verify it's valid.
     *
     * @param string $date Date string to validate
     * @return bool True if date can be parsed, false otherwise
     */
    private function isValidDate(string $date): bool
    {
        try {
            Carbon::parse($date);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Log renewal workflow actions for audit trail.
     *
     * Creates entries in the renewals_log table for each affected renewal.
     * Groups related actions under the same job_id for batch operations.
     *
     * @param array<int> $ids Array of renewal IDs that were affected
     * @param string $action Human-readable description of the action
     * @param string $type Action type identifier (e.g., 'step_update', 'abandoned')
     * @return void
     */
    private function logAction(array $ids, string $action, string $type): void
    {
        // Create renewal log entry
        $jobId = RenewalsLog::max('job_id') + 1;
        
        foreach ($ids as $id) {
            RenewalsLog::create([
                'task_id' => $id,
                'job_id' => $jobId,
                'from_step' => 0, // Would need to get current step
                'to_step' => 0, // Would need to get new step
                'creator' => auth()->user()->login ?? 'system',
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Update both step and invoice step simultaneously
     *
     * @param array<int> $ids Array of renewal IDs to update
     * @param int $step The target workflow step
     * @param int $invoiceStep The target invoice step
     * @return ActionResultDTO Result containing success status, affected count, and message
     * @throws \Exception If database transaction fails
     */
    public function updateStepAndInvoiceStep(array $ids, int $step, int $invoiceStep): ActionResultDTO
    {
        try {
            DB::beginTransaction();
            
            // Validate both steps
            if (!$this->isValidStep($step)) {
                DB::rollback();
                return ActionResultDTO::error('Invalid step value');
            }
            
            if (!$this->isValidInvoiceStep($invoiceStep)) {
                DB::rollback();
                return ActionResultDTO::error('Invalid invoice step value');
            }
            
            $stepCount = $this->renewalRepository->updateStep($ids, $step);
            $invoiceCount = $this->renewalRepository->updateInvoiceStep($ids, $invoiceStep);
            
            // Log the action if needed
            if ($stepCount > 0) {
                $this->logAction($ids, "Updated to step $step and invoice step $invoiceStep", 'step_invoice_update');
            }
            
            DB::commit();
            
            return ActionResultDTO::success($stepCount, "Updated $stepCount renewals to step $step and invoice step $invoiceStep");
        } catch (\Exception $e) {
            DB::rollback();
            return ActionResultDTO::error('Failed to update steps: ' . $e->getMessage());
        }
    }

    /**
     * Mark renewals as lapsing
     *
     * Sets renewals to step 11 (abandoned) without setting done_date.
     * This indicates the renewals will lapse due to non-payment or client decision.
     *
     * @param array<int> $ids Array of renewal IDs to mark as lapsing
     * @return ActionResultDTO Result containing success status, affected count, and message
     * @throws \Exception If database transaction fails
     */
    public function markAsLapsing(array $ids): ActionResultDTO
    {
        try {
            DB::beginTransaction();
            
            // Set step to 11 (abandoned/lapsing) but don't set done_date
            $count = $this->renewalRepository->updateStep($ids, 11);
            
            if ($count > 0) {
                $this->logAction($ids, "Marked as lapsing", 'lapsing');
            }
            
            DB::commit();
            
            return ActionResultDTO::success($count, "Marked $count renewals as lapsing");
        } catch (\Exception $e) {
            DB::rollback();
            return ActionResultDTO::error('Failed to mark as lapsing: ' . $e->getMessage());
        }
    }

    /**
     * Create an abandon event for a renewal.
     *
     * Creates an 'ABA' event in the matter's event history to record
     * the abandonment. This maintains consistency with the matter
     * event tracking system.
     *
     * @param mixed $renewal Renewal model instance with matter relation
     * @return void
     * @throws \Exception If matter relation is not loaded or not found
     */
    private function createAbandonEvent($renewal): void
    {
        // Verify that the matter relation exists
        if (!$renewal->matter) {
            throw new \Exception('Cannot create abandon event: matter not found for renewal ' . $renewal->id);
        }
        
        // Create abandon event in the event table
        $this->eventRepository->create([
            'matter_id' => $renewal->matter->id,
            'code' => 'ABA',
            'event_date' => Carbon::now(),
            'detail' => 'Renewal abandoned',
            'notes' => 'Renewal ' . $renewal->id . ' abandoned',
        ]);
    }

    /**
     * Create renewal orders for selected renewals.
     * Moves renewals to step 4 (payment order) and invoice step 1 (invoiced).
     * 
     * Previously in RenewalOrderService, now integrated directly.
     *
     * @param array<int> $ids Array of renewal IDs
     * @return ActionResultDTO Result of the operation
     */
    public function createOrders(array $ids): ActionResultDTO
    {
        if (empty($ids)) {
            return ActionResultDTO::error('No renewals selected');
        }

        // Move to step 4 (payment order) and invoice step 1 (invoiced)
        $result = $this->updateStepAndInvoiceStep($ids, 4, 1);
        
        if ($result->success) {
            return ActionResultDTO::success($result->affectedCount ?? count($ids), 'Renewal orders created successfully');
        }

        return $result;
    }

    /**
     * Mark renewals as invoiced.
     * Updates invoice step to 2 (invoiced).
     * 
     * Previously in RenewalOrderService, now integrated directly.
     *
     * @param array<int> $ids Array of renewal IDs
     * @return ActionResultDTO Result of the operation
     */
    public function markInvoiced(array $ids): ActionResultDTO
    {
        if (empty($ids)) {
            return ActionResultDTO::error('No renewals selected');
        }

        // Update invoice step to 2 (invoiced)
        $result = $this->updateInvoiceStep($ids, 2);
        
        if ($result->success) {
            return ActionResultDTO::success($result->affectedCount ?? count($ids), 'Renewals marked as invoiced');
        }

        return $result;
    }

    /**
     * Mark renewals as paid.
     * Updates invoice step to 3 (paid).
     * 
     * Previously in RenewalPaymentService, now integrated directly.
     *
     * @param array<int> $ids Array of renewal IDs
     * @return ActionResultDTO Result of the operation
     */
    public function markPaid(array $ids): ActionResultDTO
    {
        if (empty($ids)) {
            return ActionResultDTO::error('No renewals selected');
        }

        // Update invoice step to 3 (paid)
        $result = $this->updateInvoiceStep($ids, 3);
        
        if ($result->success) {
            $count = $result->affectedCount ?? count($ids);
            return ActionResultDTO::success($count, "$count invoices paid");
        }

        return $result;
    }
}