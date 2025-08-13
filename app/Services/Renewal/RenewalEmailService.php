<?php

namespace App\Services\Renewal;

use App\Services\Renewal\Contracts\RenewalEmailServiceInterface;
use App\DataTransferObjects\Renewal\ActionResultDTO;
use App\DataTransferObjects\Renewal\RenewalDTO;
use App\Repositories\Contracts\RenewalRepositoryInterface;
use App\Repositories\Contracts\ActorRepositoryInterface;
use App\Repositories\Contracts\MatterRepositoryInterface;
use App\Models\RenewalsLog;
use App\Models\Actor;
use App\Notifications\Renewal\RenewalFirstCallNotification;
use App\Notifications\Renewal\RenewalReminderCallNotification;
use App\Notifications\Renewal\RenewalLastCallNotification;
use App\Notifications\Renewal\RenewalInvoiceNotification;
use App\Notifications\Renewal\RenewalReportNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

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
        return $this->sendCalls($ids, ['first'], !$preview, 2);
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
                
                // Send invoice notification
                $result = $this->sendInvoiceNotification($renewalDTO, $invoiceData);
                
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
            
            // Send report notification
            $user = \App\Models\User::where('email', $recipient)->first();
            if ($user) {
                $user->notify(new RenewalReportNotification($renewals, $recipient));
            } else {
                // If recipient is not a user, create a temporary notifiable
                $tempNotifiable = new class($recipient) {
                    private $email;
                    public function __construct($email) { $this->email = $email; }
                    public function routeNotificationForMail() { return $this->email; }
                };
                Notification::send($tempNotifiable, new RenewalReportNotification($renewals, $recipient));
            }
            
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
        $renewalsByClient = $renewals->where('grace_period', $gracePeriod)->groupBy(function($renewal) {
            return $renewal->client_id;
        });
        
        foreach ($renewalsByClient as $clientId => $clientRenewals) {
            $clientData = $this->getClientDataById($clientId);
            
            // Skip only if sending real emails and no email address
            if ($send && !$clientData['email']) {
                continue;
            }
            
            // Prepare email data
            $emailData = $this->prepareCallData($clientRenewals, $clientData, $gracePeriod, $notifyType);
            
            if ($send && $clientData['email']) {
                // Send notification only if we have an email
                $client = Actor::find((int) $clientId);
                if ($client) {
                    // Send appropriate notification based on type
                    $this->sendNotification($client, $clientRenewals, $emailData, $notifyType, $gracePeriod);
                }
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
        // Calculate validity date (3 months from now) and instruction date (today)
        $validityDate = now()->addMonths(3)->format('d/m/Y');
        $instructionDate = now()->format('d/m/Y');
        
        // Calculate totals
        $totalHt = $renewals->sum(function($task) {
            return ($task->cost ?? 0) + ($task->fee ?? 0);
        });
        $total = $totalHt; // TODO: Add VAT calculation when implemented
        
        $data = [
            'client' => $clientData,
            'renewals' => $renewals,
            'grace_period' => $gracePeriod,
            'type' => $notifyType,
            'subject' => $this->notifyTypes[$notifyType]['subject'] ?? 'Patent Renewal Notice',
            'to' => $clientData['email'],
            'validity_date' => $validityDate,
            'instruction_date' => $instructionDate,
            'total' => $total,
            'total_ht' => $totalHt,
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

    private function getClientDataById(int|string $clientId): array
    {
        $client = $this->actorRepository->find((int) $clientId);
        
        if (!$client) {
            return [
                'name' => 'Unknown',
                'email' => null,
                'ref' => null,
            ];
        }
        
        $invoiceAddress = $this->actorRepository->getInvoicingAddress((int) $clientId);
        
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

    private function sendInvoiceNotification(RenewalDTO $renewalDTO, array $invoiceData): bool
    {
        try {
            $clientData = $this->getClientData($renewalDTO);
            $invoiceNumber = $this->generateInvoiceNumber($renewalDTO);
            
            // Try to find the client as a user first
            $client = Actor::find($renewalDTO->clientId);
            if ($client) {
                $client->notify(new RenewalInvoiceNotification($renewalDTO, $clientData, $invoiceNumber));
            } else {
                // If client is not found, create a temporary notifiable
                $tempNotifiable = new class($invoiceData['to']) {
                    private $email;
                    public function __construct($email) { $this->email = $email; }
                    public function routeNotificationForMail() { return $this->email; }
                };
                Notification::send($tempNotifiable, new RenewalInvoiceNotification($renewalDTO, $clientData, $invoiceNumber));
            }
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
            ]);
        }
    }
    
    private function sendNotification($client, $renewals, array $emailData, string $notifyType, int $gracePeriod): void
    {
        // Format renewals for email display
        $formattedRenewals = $this->formatRenewalsForEmail($renewals);
        
        // Calculate totals from formatted data
        $totalHt = $formattedRenewals->sum(function ($renewal) {
            return floatval(str_replace(',', '', $renewal['total_ht']));
        });
        $total = $formattedRenewals->sum(function ($renewal) {
            return floatval(str_replace(',', '', $renewal['total']));
        });
        
        // Prepare notification data from email data
        $notificationData = [
            'renewals' => $formattedRenewals,
            'validity_date' => $emailData['validity_date'] ?? now()->addMonths(3)->format('d/m/Y'),
            'instruction_date' => $emailData['instruction_date'] ?? now()->format('d/m/Y'),
            'total' => $total,
            'total_ht' => $totalHt,
            'subject' => $emailData['subject'],
            'dest' => $emailData['to'],
        ];
        
        // Send appropriate notification based on type
        switch ($notifyType) {
            case 'first':
                $client->notify(new RenewalFirstCallNotification(
                    $notificationData['renewals'],
                    $notificationData['validity_date'],
                    $notificationData['instruction_date'],
                    $notificationData['total'],
                    $notificationData['total_ht'],
                    $notificationData['subject'],
                    $notificationData['dest'],
                    $gracePeriod
                ));
                break;
            case 'warn':
                $client->notify(new RenewalReminderCallNotification(
                    $notificationData['renewals'],
                    $notificationData['validity_date'],
                    $notificationData['instruction_date'],
                    $notificationData['total'],
                    $notificationData['total_ht'],
                    $notificationData['subject'],
                    $notificationData['dest'],
                    $gracePeriod
                ));
                break;
            case 'last':
                $client->notify(new RenewalLastCallNotification(
                    $notificationData['renewals'],
                    $notificationData['validity_date'],
                    $notificationData['instruction_date'],
                    $notificationData['total'],
                    $notificationData['total_ht'],
                    $notificationData['subject'],
                    $notificationData['dest'],
                    $gracePeriod
                ));
                break;
            default:
                // Log unknown notification type
                \Log::warning('Unknown notification type in sendNotification: ' . $notifyType);
        }
    }
    
    private function formatRenewalsForEmail($renewals): Collection
    {
        return $renewals->map(function ($task) {
            $matter = $task->matter;
            $trigger = $task->trigger;
            
            // Build description from matter info
            $desc = $matter->caseref;
            if ($matter->suffix) {
                $desc .= $matter->suffix;
            }
            if ($matter->alt_ref) {
                $desc .= ' (' . $matter->alt_ref . ')';
            }
            
            // Get country from trigger's matter
            $country = $trigger->matter->country ?? '';
            
            // Get annuity year
            $annuity = is_array($task->detail) ? ($task->detail['en'] ?? '') : $task->detail;
            
            // Format date
            $dueDate = $task->due_date instanceof \Carbon\Carbon 
                ? $task->due_date->format('d/m/Y') 
                : \Carbon\Carbon::parse($task->due_date)->format('d/m/Y');
            
            // Get costs with defaults
            $cost = $task->cost ?? 0;
            $fee = $task->fee ?? 0;
            $totalHt = $cost + $fee;
            
            // TODO: Get VAT rate from client or config
            $vatRate = 0; // Default no VAT
            $total = $totalHt * (1 + $vatRate / 100);
            
            return [
                'id' => $task->id,
                'desc' => $desc,
                'country' => $country,
                'annuity' => $annuity,
                'due_date' => $dueDate,
                'cost' => number_format($cost, 2),
                'fee' => number_format($fee, 2),
                'total_ht' => number_format($totalHt, 2),
                'vat_rate' => $vatRate,
                'total' => number_format($total, 2),
                'caseref' => $matter->caseref,
                'language' => $task->language ?? app()->getLocale(),
            ];
        });
    }
}