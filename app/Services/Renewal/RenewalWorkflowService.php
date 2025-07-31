<?php

namespace App\Services\Renewal;

use App\Services\Renewal\Contracts\RenewalWorkflowServiceInterface;
use App\DataTransferObjects\Renewal\ActionResultDTO;
use App\Repositories\Contracts\RenewalRepositoryInterface;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Models\RenewalsLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RenewalWorkflowService implements RenewalWorkflowServiceInterface
{
    public function __construct(
        private RenewalRepositoryInterface $renewalRepository,
        private EventRepositoryInterface $eventRepository
    ) {}

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

    public function canTransition(int $fromStep, int $toStep): bool
    {
        // Define valid transitions
        $validTransitions = [
            0 => [1, 2, 10, 11],    // Open can go to Instructions, Reminder, Closed, Abandoned
            1 => [2, 3, 10, 11],    // Instructions can go to Reminder, Payment, Closed, Abandoned
            2 => [3, 4, 10, 11],    // Reminder can go to Payment, Invoiced, Closed, Abandoned
            3 => [4, 10, 11],       // Payment can go to Invoiced, Closed, Abandoned
            4 => [5, 10, 11],       // Invoiced can go to Receipts, Closed, Abandoned
            5 => [10, 11],          // Receipts can go to Closed, Abandoned
            10 => [],               // Closed is final
            11 => [],               // Abandoned is final
        ];
        
        if (!isset($validTransitions[$fromStep])) {
            return false;
        }
        
        return in_array($toStep, $validTransitions[$fromStep]);
    }

    public function getNextStep(int $currentStep): ?int
    {
        $nextSteps = [
            0 => 1,  // Open -> Instructions
            1 => 2,  // Instructions -> Reminder
            2 => 3,  // Reminder -> Payment
            3 => 4,  // Payment -> Invoiced
            4 => 5,  // Invoiced -> Receipts
            5 => 10, // Receipts -> Closed
        ];
        
        return $nextSteps[$currentStep] ?? null;
    }

    private function isValidStep(int $step): bool
    {
        return in_array($step, [0, 1, 2, 3, 4, 5, 10, 11]);
    }

    private function isValidInvoiceStep(int $invoiceStep): bool
    {
        return in_array($invoiceStep, [0, 1, 2, 3]);
    }

    private function isValidDate(string $date): bool
    {
        try {
            Carbon::parse($date);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

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
                'note' => $action,
            ]);
        }
    }

    private function createAbandonEvent($renewal): void
    {
        // Create abandon event in the event table
        $this->eventRepository->create([
            'matter_id' => $renewal->matter_id,
            'code' => 'ABA',
            'event_date' => Carbon::now(),
            'detail' => 'Renewal abandoned',
            'notes' => 'Renewal ' . $renewal->id . ' abandoned',
        ]);
    }
}