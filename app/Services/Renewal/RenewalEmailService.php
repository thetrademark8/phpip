<?php

namespace App\Services\Renewal;

use App\Services\Renewal\Contracts\RenewalEmailServiceInterface;
use App\DataTransferObjects\Renewal\ActionResultDTO;
use App\DataTransferObjects\Renewal\RenewalDTO;
use App\Repositories\Contracts\RenewalRepositoryInterface;
use App\Repositories\Contracts\ActorRepositoryInterface;
use App\Repositories\Contracts\MatterRepositoryInterface;
use App\Mail\sendCall;
use App\Models\RenewalsLog;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class RenewalEmailService implements RenewalEmailServiceInterface
{
    private array $notifyTypes = [
        'first' => ['template' => 'first_call', 'subject' => 'Patent Renewal - First Notice'],
        'warn' => ['template' => 'warning_call', 'subject' => 'Patent Renewal - Warning'],
        'last' => ['template' => 'last_call', 'subject' => 'Patent Renewal - Final Notice'],
    ];

    public function __construct(
        private RenewalRepositoryInterface $renewalRepository,
        private ActorRepositoryInterface $actorRepository,
        private MatterRepositoryInterface $matterRepository
    ) {}

    public function sendFirstCall(array $ids, bool $preview = false): ActionResultDTO
    {
        return $this->sendCalls($ids, ['first'], $preview, 2);
    }

    public function sendReminderCall(array $ids): ActionResultDTO
    {
        return $this->sendCalls($ids, ['first', 'warn'], true, null);
    }

    public function sendLastCall(array $ids): ActionResultDTO
    {
        $result = $this->sendCalls($ids, ['last'], true, null);
        
        // If successful, update grace period
        if ($result->success && $result->count > 0) {
            $this->renewalRepository->updateGracePeriod($ids, 1);
        }
        
        return $result;
    }

    public function sendFormalCall(array $ids): ActionResultDTO
    {
        return $this->sendCalls($ids, ['formal'], true, 6);
    }

    public function sendInvoice(array $ids): ActionResultDTO
    {
        try {
            DB::beginTransaction();
            
            $processedCount = 0;
            $errors = [];
            
            foreach ($ids as $id) {
                $renewal = $this->renewalRepository->findById($id);
                if (!$renewal) {
                    $errors[] = "Renewal $id not found";
                    continue;
                }
                
                $renewalDTO = RenewalDTO::fromModel($renewal);
                
                // Generate invoice data
                $invoiceData = $this->prepareInvoiceData($renewalDTO);
                
                // Send invoice email
                $result = $this->sendInvoiceEmail($invoiceData);
                
                if ($result) {
                    $processedCount++;
                    // Update invoice step
                    $this->renewalRepository->updateInvoiceStep([$id], 2);
                } else {
                    $errors[] = "Failed to send invoice for renewal $id";
                }
            }
            
            DB::commit();
            
            if ($processedCount > 0) {
                $message = "Sent $processedCount invoices";
                if (!empty($errors)) {
                    $message .= " (with " . count($errors) . " errors)";
                }
                return ActionResultDTO::success($processedCount, $message, ['errors' => $errors]);
            } else {
                return ActionResultDTO::error('Failed to send any invoices', ['errors' => $errors]);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return ActionResultDTO::error('Failed to send invoices: ' . $e->getMessage());
        }
    }

    public function sendReport(array $ids, string $recipient): ActionResultDTO
    {
        try {
            // Get renewals
            $renewals = $this->renewalRepository->findByIds($ids);
            
            if ($renewals->isEmpty()) {
                return ActionResultDTO::error('No renewals found');
            }
            
            // Generate report data
            $reportData = $this->prepareReportData($renewals);
            
            // Send report email
            Mail::to($recipient)->send(new \App\Mail\RenewalReport($reportData));
            
            return ActionResultDTO::success(1, 'Report sent successfully');
        } catch (\Exception $e) {
            return ActionResultDTO::error('Failed to send report: ' . $e->getMessage());
        }
    }

    public function previewEmail(int $id, string $type): array
    {
        $renewal = $this->renewalRepository->findById($id);
        if (!$renewal) {
            return ['error' => 'Renewal not found'];
        }
        
        $renewalDTO = RenewalDTO::fromModel($renewal);
        
        // Get client and contact information
        $clientData = $this->getClientData($renewalDTO);
        
        // Prepare email data based on type
        $emailData = match($type) {
            'first' => $this->prepareFirstCallData($renewalDTO, $clientData, 0),
            'warn' => $this->prepareWarningCallData($renewalDTO, $clientData, 1),
            'last' => $this->prepareLastCallData($renewalDTO, $clientData, 2),
            'invoice' => $this->prepareInvoiceData($renewalDTO),
            default => null
        };
        
        if (!$emailData) {
            return ['error' => 'Invalid email type'];
        }
        
        return [
            'subject' => $emailData['subject'],
            'recipient' => $emailData['to'],
            'cc' => $emailData['cc'] ?? null,
            'body' => view('emails.' . $this->notifyTypes[$type]['template'], $emailData)->render(),
        ];
    }

    private function sendCalls(array $ids, array $notifyTypes, bool $send, ?int $nextStep): ActionResultDTO
    {
        if (empty($ids)) {
            return ActionResultDTO::error('No renewals selected');
        }
        
        try {
            DB::beginTransaction();
            
            $totalSent = 0;
            $jobId = RenewalsLog::max('job_id') + 1;
            
            for ($grace = 0; $grace < count($notifyTypes); $grace++) {
                $renewalsData = $this->processRenewals($ids, $grace, $notifyTypes[$grace], $send);
                
                if (empty($renewalsData['renewals'])) {
                    continue;
                }
                
                // Log the calls
                $this->logCalls($renewalsData['renewals'], $jobId, $notifyTypes[$grace]);
                
                $totalSent += $renewalsData['count'];
            }
            
            // Update step if specified
            if ($nextStep !== null && $totalSent > 0) {
                $this->renewalRepository->updateStep($ids, $nextStep);
            }
            
            DB::commit();
            
            $action = $send ? 'sent' : 'created';
            return ActionResultDTO::success($totalSent, "Calls $action for $totalSent renewals");
        } catch (\Exception $e) {
            DB::rollback();
            return ActionResultDTO::error('Failed to process calls: ' . $e->getMessage());
        }
    }

    private function processRenewals(array $ids, int $gracePeriod, string $notifyType, bool $send): array
    {
        $renewals = $this->renewalRepository->findByIds($ids);
        $processedRenewals = [];
        $count = 0;
        
        // Group by client
        $renewalsByClient = $renewals->where('grace_period', $gracePeriod)->groupBy('client_id');
        
        foreach ($renewalsByClient as $clientId => $clientRenewals) {
            $clientData = $this->getClientDataById($clientId);
            
            if (!$clientData['email']) {
                continue;
            }
            
            // Prepare email data
            $emailData = $this->prepareCallData($clientRenewals, $clientData, $gracePeriod, $notifyType);
            
            if ($send) {
                // Send email
                Mail::to($clientData['email'])->send(new sendCall($emailData));
            }
            
            $processedRenewals = array_merge($processedRenewals, $clientRenewals->pluck('id')->toArray());
            $count++;
        }
        
        return [
            'renewals' => $processedRenewals,
            'count' => $count,
        ];
    }

    private function prepareCallData($renewals, array $clientData, int $gracePeriod, string $notifyType): array
    {
        $data = [
            'client' => $clientData,
            'renewals' => $renewals,
            'grace_period' => $gracePeriod,
            'type' => $notifyType,
            'subject' => $this->notifyTypes[$notifyType]['subject'] ?? 'Patent Renewal Notice',
            'to' => $clientData['email'],
        ];
        
        // Add CC if configured
        if (config('renewal.mail.cc_renewals')) {
            $data['cc'] = config('renewal.mail.renewals_email');
        }
        
        return $data;
    }

    private function prepareFirstCallData(RenewalDTO $renewal, array $clientData, int $gracePeriod): array
    {
        return $this->prepareCallData(collect([$renewal]), $clientData, $gracePeriod, 'first');
    }

    private function prepareWarningCallData(RenewalDTO $renewal, array $clientData, int $gracePeriod): array
    {
        return $this->prepareCallData(collect([$renewal]), $clientData, $gracePeriod, 'warn');
    }

    private function prepareLastCallData(RenewalDTO $renewal, array $clientData, int $gracePeriod): array
    {
        return $this->prepareCallData(collect([$renewal]), $clientData, $gracePeriod, 'last');
    }

    private function prepareInvoiceData(RenewalDTO $renewal): array
    {
        $clientData = $this->getClientData($renewal);
        
        return [
            'renewal' => $renewal,
            'client' => $clientData,
            'invoice_number' => $this->generateInvoiceNumber($renewal),
            'subject' => 'Invoice for Patent Renewal - ' . $renewal->caseref,
            'to' => $clientData['invoice_email'] ?? $clientData['email'],
        ];
    }

    private function prepareReportData($renewals): array
    {
        return [
            'renewals' => $renewals,
            'total_count' => $renewals->count(),
            'total_cost' => $renewals->sum('cost'),
            'total_fee' => $renewals->sum('fee'),
            'generated_at' => now(),
        ];
    }

    private function getClientData(RenewalDTO $renewal): array
    {
        if (!$renewal->clientId) {
            return [
                'name' => $renewal->clientName ?? 'Unknown',
                'email' => null,
                'ref' => $renewal->clientRef,
            ];
        }
        
        return $this->getClientDataById($renewal->clientId);
    }

    private function getClientDataById(int $clientId): array
    {
        $client = $this->actorRepository->find($clientId);
        
        if (!$client) {
            return [
                'name' => 'Unknown',
                'email' => null,
                'ref' => null,
            ];
        }
        
        $invoiceAddress = $this->actorRepository->getInvoicingAddress($clientId);
        
        return [
            'id' => $client->id,
            'name' => $client->name,
            'email' => $client->email,
            'invoice_email' => $invoiceAddress['email'] ?? $client->email,
            'ref' => $client->ref,
            'address' => $invoiceAddress ?? $client->address,
            'vat_number' => $client->vat_number,
        ];
    }

    private function sendInvoiceEmail(array $invoiceData): bool
    {
        try {
            Mail::to($invoiceData['to'])->send(new \App\Mail\RenewalInvoice($invoiceData));
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function generateInvoiceNumber(RenewalDTO $renewal): string
    {
        return sprintf(
            '%s-%s-%s',
            date('Y'),
            str_pad($renewal->id, 6, '0', STR_PAD_LEFT),
            $renewal->caseref
        );
    }

    private function logCalls(array $renewalIds, int $jobId, string $notifyType): void
    {
        foreach ($renewalIds as $id) {
            RenewalsLog::create([
                'task_id' => $id,
                'job_id' => $jobId,
                'creator' => auth()->user()->login ?? 'system',
                'created_at' => now(),
                'note' => "Email sent: $notifyType",
            ]);
        }
    }
}