<?php

namespace App\Services\Renewal;

use App\Services\Renewal\Contracts\RenewalLogServiceInterface;
use App\Models\RenewalsLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class RenewalLogService implements RenewalLogServiceInterface
{
    public function getLogs(?array $filters = null, int $perPage = 25): LengthAwarePaginator
    {
        $query = RenewalsLog::query()
            ->with(['task', 'creator'])
            ->orderByDesc('created_at');

        if ($filters) {
            $query = $this->applyFilters($query, $filters);
        }

        return $query->paginate($perPage);
    }

    public function getLogsByTaskId(int $taskId, int $perPage = 25): LengthAwarePaginator
    {
        return RenewalsLog::query()
            ->with(['creator'])
            ->where('task_id', $taskId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getLogsByJobId(int $jobId): \Illuminate\Database\Eloquent\Collection
    {
        return RenewalsLog::query()
            ->with(['task', 'creator'])
            ->where('job_id', $jobId)
            ->orderByDesc('created_at')
            ->get();
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        foreach ($filters as $key => $value) {
            if (empty($value)) {
                continue;
            }

            match ($key) {
                'task_id' => $query->where('task_id', $value),
                'job_id' => $query->where('job_id', $value),
                'creator' => $query->where('creator', $value),
                'from_step' => $query->where('from_step', $value),
                'to_step' => $query->where('to_step', $value),
                'from_invoice' => $query->where('from_invoice', $value),
                'to_invoice' => $query->where('to_invoice', $value),
                'date_from' => $query->where('created_at', '>=', $value),
                'date_to' => $query->where('created_at', '<=', $value),
                default => null
            };
        }

        return $query;
    }

    /**
     * Log a single renewal action
     */
    public function logAction(int $taskId, string $action, array $data = []): void
    {
        RenewalsLog::create([
            'task_id' => $taskId,
            'job_id' => $this->getNextJobId(),
            'action' => $action,
            'data' => json_encode($data),
            'creator' => Auth::user()->login ?? 'system',
            'created_at' => now(),
        ]);
    }

    /**
     * Log a bulk renewal action
     */
    public function logBulkAction(string $action, Collection $renewals, array $additionalData = []): void
    {
        $jobId = $this->getNextJobId();
        $logs = [];
        
        foreach ($renewals as $renewal) {
            $logs[] = [
                'task_id' => $renewal->id,
                'job_id' => $jobId,
                'action' => $action,
                'data' => json_encode($additionalData),
                'creator' => Auth::user()->login ?? 'system',
                'created_at' => now(),
            ];
        }
        
        if (!empty($logs)) {
            RenewalsLog::insert($logs);
        }
    }

    /**
     * Log a step transition
     */
    public function logStepTransition(int $taskId, int $fromStep, int $toStep): void
    {
        RenewalsLog::create([
            'task_id' => $taskId,
            'job_id' => $this->getNextJobId(),
            'from_step' => $fromStep,
            'to_step' => $toStep,
            'creator' => Auth::user()->login ?? 'system',
            'created_at' => now(),
        ]);
    }

    /**
     * Log an invoice step transition
     */
    public function logInvoiceStepTransition(int $taskId, int $fromStep, int $toStep): void
    {
        RenewalsLog::create([
            'task_id' => $taskId,
            'job_id' => $this->getNextJobId(),
            'from_invoice' => $fromStep,
            'to_invoice' => $toStep,
            'creator' => Auth::user()->login ?? 'system',
            'created_at' => now(),
        ]);
    }

    /**
     * Get the next job ID for batch operations
     */
    public function getNextJobId(): int
    {
        return (int) RenewalsLog::max('job_id') + 1;
    }

    /**
     * Create log entries for email actions
     */
    public function logEmailAction(array $taskIds, string $emailType, int $fromStep, int $toStep): void
    {
        $jobId = $this->getNextJobId();
        $logs = [];
        
        // Determine grace period transitions based on email type
        $fromGrace = null;
        $toGrace = null;
        
        if ($emailType === 'last') {
            $fromGrace = 0;
            $toGrace = 1;
        }
        
        foreach ($taskIds as $taskId) {
            $logs[] = [
                'task_id' => $taskId,
                'job_id' => $jobId,
                'from_step' => $fromStep,
                'to_step' => $toStep,
                'from_grace' => $fromGrace,
                'to_grace' => $toGrace,
                'action' => 'email_' . $emailType,
                'creator' => Auth::user()->login ?? 'system',
                'created_at' => now(),
            ];
        }
        
        if (!empty($logs)) {
            RenewalsLog::insert($logs);
        }
    }
}