<?php

namespace App\Repositories;

use App\Repositories\Contracts\RenewalRepositoryInterface;
use App\DataTransferObjects\Renewal\RenewalFilterDTO;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class RenewalRepository implements RenewalRepositoryInterface
{
    public function findByIds(array $ids): Collection
    {
        return Task::whereIn('id', $ids)
            ->where('code', 'REN')
            ->with(['matter', 'matter.client', 'trigger'])
            ->get();
    }

    public function findById(int $id)
    {
        return Task::where('id', $id)
            ->where('code', 'REN')
            ->with(['matter', 'trigger'])
            ->first();
    }

    public function paginate(Builder $query, int $perPage = 25): LengthAwarePaginator
    {
        return $query->paginate($perPage);
    }

    public function getByFilters(RenewalFilterDTO $filters): Collection
    {
        $query = Task::where('code', 'REN');
        
        if ($filters->step !== null) {
            $query->where('step', $filters->step);
        }
        
        if ($filters->invoiceStep !== null) {
            $query->where('invoice_step', $filters->invoiceStep);
        }
        
        if ($filters->gracePeriod !== null) {
            $query->where('grace_period', $filters->gracePeriod);
        }
        
        if ($filters->fromDate) {
            $query->where('due_date', '>=', $filters->fromDate);
        }
        
        if ($filters->untilDate) {
            $query->where('due_date', '<=', $filters->untilDate);
        }
        
        return $query->with(['matter', 'event'])->get();
    }

    public function updateStep(array $ids, int $step): int
    {
        return Task::whereIn('id', $ids)
            ->where('code', 'REN')
            ->update(['step' => $step]);
    }

    public function updateInvoiceStep(array $ids, int $invoiceStep): int
    {
        return Task::whereIn('id', $ids)
            ->where('code', 'REN')
            ->update(['invoice_step' => $invoiceStep]);
    }

    public function updateGracePeriod(array $ids, int $gracePeriod): int
    {
        return Task::whereIn('id', $ids)
            ->where('code', 'REN')
            ->update(['grace_period' => $gracePeriod]);
    }

    public function markAsDone(array $ids, ?string $doneDate = null): int
    {
        $date = $doneDate ?? Carbon::now()->format('Y-m-d');
        
        return Task::whereIn('id', $ids)
            ->where('code', 'REN')
            ->update([
                'done' => 1,
                'done_date' => $date
            ]);
    }

    public function updateFees(int $id, float $cost, float $fee): bool
    {
        return Task::where('id', $id)
            ->where('code', 'REN')
            ->update([
                'cost' => $cost,
                'fee' => $fee
            ]) > 0;
    }

    public function getForExport(array $filters = []): Collection
    {
        $query = Task::where('code', 'REN')
            ->with(['matter', 'trigger', 'matter.actors']);
        
        if (!empty($filters['step'])) {
            $query->where('step', $filters['step']);
        }
        
        if (!empty($filters['invoice_step'])) {
            $query->where('invoice_step', $filters['invoice_step']);
        }
        
        if (!empty($filters['from_date'])) {
            $query->where('due_date', '>=', $filters['from_date']);
        }
        
        if (!empty($filters['until_date'])) {
            $query->where('due_date', '<=', $filters['until_date']);
        }
        
        return $query->get();
    }

    public function getGroupedByClient(array $ids): Collection
    {
        return Task::whereIn('id', $ids)
            ->where('code', 'REN')
            ->with(['matter', 'matter.actors' => function($query) {
                $query->where('role_code', 'CLI');
            }])
            ->get()
            ->groupBy(function($task) {
                $client = $task->matter->actors->where('role_code', 'CLI')->first();
                return $client ? $client->id : 0;
            });
    }

    public function getRenewalsForInvoicing(array $ids): Collection
    {
        return Task::renewals()
            ->whereIn('task.id', $ids)
            ->orderBy('client_name')
            ->get();
    }

    public function getRenewalsForExport(?array $filters = null): Collection
    {
        $query = Task::renewals();
        
        // Default filter for export: invoice_step = 1 (invoiced)
        if (!$filters || !isset($filters['invoice_step'])) {
            $query->where('invoice_step', 1);
        }
        
        if ($filters) {
            foreach ($filters as $key => $value) {
                if ($value !== null && $value !== '') {
                    match ($key) {
                        'step' => $query->where('step', $value),
                        'invoice_step' => $query->where('invoice_step', $value),
                        'grace_period' => $query->where('grace_period', $value),
                        'client_id' => $query->where('pmal_cli.actor_id', $value),
                        default => null
                    };
                }
            }
        }
        
        return $query->orderBy('pmal_cli.actor_id')->get();
    }
}