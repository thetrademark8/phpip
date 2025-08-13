<?php

namespace App\Services\Renewal;

use App\Services\Renewal\Contracts\RenewalQueryServiceInterface;
use App\DataTransferObjects\Renewal\RenewalFilterDTO;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Matter;
use App\Models\Task;

class RenewalQueryService implements RenewalQueryServiceInterface
{
    public function buildQuery(RenewalFilterDTO $filters): Builder
    {
        $query = $this->createBaseQuery();
        $query = $this->applyStepFilters($query, $filters);
        $query = $this->applyUserFilter($query, $filters);
        $query = $this->applyOptimizations($query, $filters);
        $query = $this->applyFilters($query, $filters->toArray());
        $query = $this->applySorting($query, $filters->step, $filters->invoiceStep);
        
        return $query;
    }

    public function applyFilters(Builder $query, array $filters): Builder
    {
        foreach ($filters as $key => $value) {
            if ($value === '' || $value === null) {
                continue;
            }

            switch ($key) {
                case 'Title':
                    $query->where('tit.value', 'LIKE', "%$value%");
                    break;
                case 'Case':
                    $query->where('matter.caseref', 'LIKE', "$value%");
                    break;
                case 'Qt':
                    $query->where('task.detail->en', $value);
                    break;
                case 'Fromdate':
                    $query->where('task.due_date', '>=', $value);
                    break;
                case 'Untildate':
                    $query->where('task.due_date', '<=', $value);
                    break;
                case 'Name':
                    $query->where(function($q) use ($value) {
                        $q->where('pa_cli.name', 'LIKE', "$value%")
                          ->orWhere('clic.name', 'LIKE', "$value%");
                    });
                    break;
                case 'Country':
                    $query->where('matter.country', 'LIKE', "$value%");
                    break;
                case 'grace_period':
                    $query->where('task.grace_period', $value);
                    break;
                case 'step':
                    $query->where('task.step', $value);
                    break;
                case 'invoice_step':
                    $query->where('task.invoice_step', $value);
                    break;
            }
        }

        return $query;
    }

    public function applyOptimizations(Builder $query, RenewalFilterDTO $filters): Builder
    {
        // Task::renewals() already includes all necessary joins and fields
        // No additional optimizations needed as the base query is comprehensive
        return $query;
    }

    public function applySorting(Builder $query, ?int $step, ?int $invoiceStep): Builder
    {
        // The old controller doesn't apply ORDER BY for step=0
        // Only apply ORDER BY for specific steps
        if ($step == 10 || $invoiceStep == 3) {
            $query->orderByDesc('task.due_date');
        } else if ($step !== 0) {
            // Only apply ascending order if not step 0
            $query->orderBy('task.due_date');
        }
        // No ORDER BY for step=0 to match old behavior

        return $query;
    }

    private function createBaseQuery(): Builder
    {
        // Use the same base query as the old controller - Task::renewals()
        return Task::renewals();
    }

    private function applyStepFilters(Builder $query, RenewalFilterDTO $filters): Builder
    {
        $step = $filters->step ?? 0;

        // Use whereRaw without table prefix and as string to match old query
        $query->where('step', $step);

        // Only add matter.dead = 0 when explicitly filtering by step=0
        if ($step == 0) {
            $query->where('matter.dead', 0);
        }
        
        if ($filters->invoiceStep !== null) {
            // Use whereRaw without table prefix and as string to match old query
            $query->whereRaw('invoice_step = ?', [(string)$filters->invoiceStep]);
            
            // When filtering by invoice_step, also add matter.dead = 0
            $query->where('matter.dead', 0);
        }

        // Check if we have step or invoice filters from the filters array
        $with_step = false;
        $with_invoice = false;
        $filterArray = $filters->toArray();
        if (!empty($filterArray)) {
            if (!empty($filterArray['step']) && $step != 0) {
                $with_step = true;
            }
            if (!empty($filterArray['invoice_step']) && $filterArray['invoice_step'] != 0) {
                $with_invoice = true;
            }
        }

        // Only display pending renewals at the beginning of the pipeline
        if (!($with_step || $with_invoice)) {
            // Use whereRaw without table prefix to match old query
            $query->whereRaw('done = 0');
        }

        return $query;
    }

    private function applyUserFilter(Builder $query, RenewalFilterDTO $filters): Builder
    {
        if ($filters->myRenewals) {
            $query->where('task.assigned_to', Auth::user()->login);
        }

        return $query;
    }
}