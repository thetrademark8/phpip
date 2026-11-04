<?php

namespace App\Services\Renewal;

use App\Contracts\Renewal\DolibarrClientInterface;
use App\Contracts\Renewal\RenewalInvoiceServiceInterface;
use App\Contracts\Renewal\RenewalLogServiceInterface;
use App\DataTransferObjects\Renewal\ActionResultDTO;
use App\Exceptions\Dolibarr\DolibarrException;
use App\Models\RenewalInvoiceBatch;
use App\Models\RenewalInvoiceBatchTask;
use App\Models\Task;
use App\Repositories\Contracts\RenewalRepositoryInterface;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class RenewalInvoiceService implements RenewalInvoiceServiceInterface
{
    public function __construct(
        private RenewalRepositoryInterface $renewalRepository,
        private RenewalInvoiceLineBuilder $lineBuilder,
        private DolibarrClientInterface $dolibarrClient,
        private RenewalLogServiceInterface $logService,
    ) {}

    public function createInvoices(array $ids, bool $toInvoice = true): ActionResultDTO
    {
        $ids = collect($ids)
            ->filter(static fn ($id): bool => filter_var($id, FILTER_VALIDATE_INT) !== false && (int) $id > 0)
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return ActionResultDTO::error(__('renewals.invoice.none_selected'));
        }

        $renewals = $this->renewalRepository->getRenewalsForInvoicing($ids)->keyBy('id');
        $missingIds = array_values(array_diff($ids, $renewals->keys()->map(fn ($id): int => (int) $id)->all()));

        if ($missingIds !== []) {
            return ActionResultDTO::error(__('renewals.invoice.missing_selected'), ['missing' => $missingIds]);
        }

        $backend = config('renewal.invoice.backend');

        if (!$toInvoice && $backend === 'dolibarr') {
            return ActionResultDTO::error(__('renewals.invoice.manual_disabled'));
        }

        if (!$toInvoice) {
            return $this->markAsExternallyInvoiced($renewals);
        }

        if ($backend !== 'dolibarr') {
            return ActionResultDTO::error(__('renewals.invoice.backend_disabled'));
        }

        $mappings = RenewalInvoiceBatchTask::query()
            ->with('batch')
            ->whereIn('task_id', $ids)
            ->get()
            ->keyBy('task_id');

        $skipped = 0;
        $renewalsToProcess = collect();

        foreach ($renewals as $renewal) {
            $mapping = $mappings->get($renewal->id);

            if ((int) $renewal->invoice_step === 2
                && $mapping?->batch?->status === RenewalInvoiceBatch::STATUS_CREATED) {
                $skipped++;

                continue;
            }

            if ((int) $renewal->invoice_step !== 1) {
                return ActionResultDTO::error(
                    __('renewals.invoice.not_ready', ['id' => $renewal->id]),
                    ['renewal_id' => (int) $renewal->id]
                );
            }

            $validationError = $this->validateRenewal($renewal);
            if ($validationError) {
                return ActionResultDTO::error($validationError, ['renewal_id' => (int) $renewal->id]);
            }

            $renewalsToProcess->push($renewal);
        }

        if ($renewalsToProcess->isEmpty()) {
            return ActionResultDTO::success(0, __('renewals.invoice.already_invoiced'), [
                'created' => 0,
                'skipped' => $skipped,
                'failed' => 0,
                'uncertain' => 0,
            ]);
        }

        try {
            $this->dolibarrClient->assertInvoiceExternalReferenceSupported();
        } catch (Throwable $exception) {
            return ActionResultDTO::error($this->safeExceptionMessage($exception));
        }

        // Build every new payload before the first external mutation so local data
        // errors cannot create an avoidable partial result.
        try {
            foreach ($renewalsToProcess->groupBy('client_id') as $clientRenewals) {
                $this->lineBuilder->build($clientRenewals);
            }
        } catch (Throwable $exception) {
            return ActionResultDTO::error($this->safeExceptionMessage($exception));
        }

        $outcomes = [
            'created' => 0,
            'skipped' => $skipped,
            'failed' => 0,
            'uncertain' => 0,
            'errors' => [],
        ];
        $processedBatchIds = [];

        foreach ($renewalsToProcess->groupBy('client_id') as $clientRenewals) {
            $existingBatchIds = $clientRenewals
                ->map(fn ($renewal) => $mappings->get($renewal->id)?->batch_id)
                ->filter()
                ->unique();

            foreach ($existingBatchIds as $batchId) {
                if (isset($processedBatchIds[$batchId])) {
                    continue;
                }

                $batch = RenewalInvoiceBatch::find($batchId);
                if ($batch) {
                    try {
                        $this->mergeOutcome($outcomes, $this->processBatch($batch));
                    } catch (Throwable $exception) {
                        $outcomes['uncertain'] += $batch->batchTasks()->count();
                        $outcomes['errors'][] = $this->safeExceptionMessage($exception);
                    }
                    $processedBatchIds[$batchId] = true;
                }
            }

            $unbatchedRenewals = $clientRenewals->reject(
                fn ($renewal): bool => $mappings->has($renewal->id)
            )->values();

            if ($unbatchedRenewals->isNotEmpty()) {
                $remainingRenewals = $unbatchedRenewals;

                while ($remainingRenewals->isNotEmpty()) {
                    try {
                        $batch = $this->reserveBatch($remainingRenewals);
                        if (!isset($processedBatchIds[$batch->id])) {
                            $this->mergeOutcome($outcomes, $this->processBatch($batch));
                            $processedBatchIds[$batch->id] = true;
                        }

                        $batchedTaskIds = $batch->batchTasks()->pluck('task_id');
                        $nextRemaining = $remainingRenewals
                            ->reject(fn ($renewal): bool => $batchedTaskIds->contains($renewal->id))
                            ->values();

                        if ($nextRemaining->count() === $remainingRenewals->count()) {
                            throw new InvalidArgumentException(__('renewals.invoice.reserve_failed'));
                        }

                        $remainingRenewals = $nextRemaining;
                    } catch (Throwable $exception) {
                        $outcomes['failed'] += $remainingRenewals->count();
                        $outcomes['errors'][] = $this->safeExceptionMessage($exception);
                        break;
                    }
                }
            }
        }

        $affectedCount = $outcomes['created'];
        $data = $outcomes;

        if ($outcomes['failed'] > 0 || $outcomes['uncertain'] > 0) {
            $message = __('renewals.invoice.partial', [
                'created' => $outcomes['created'],
                'failed' => $outcomes['failed'],
                'uncertain' => $outcomes['uncertain'],
            ]);

            if ($outcomes['created'] > 0 || $outcomes['skipped'] > 0) {
                return ActionResultDTO::success($affectedCount, $message, $data);
            }

            return ActionResultDTO::error($message, $data);
        }

        return ActionResultDTO::success(
            $affectedCount,
            __('renewals.invoice.created', ['count' => $affectedCount]),
            $data
        );
    }

    /**
     * @param  Collection<int, object>  $renewals
     */
    private function markAsExternallyInvoiced(Collection $renewals): ActionResultDTO
    {
        $invalid = $renewals->first(fn ($renewal): bool => (int) $renewal->invoice_step !== 1);
        if ($invalid) {
            return ActionResultDTO::error(__('renewals.invoice.not_ready', ['id' => $invalid->id]));
        }

        $ids = $renewals->keys()->map(fn ($id): int => (int) $id)->values()->all();

        $transitionedIds = DB::transaction(function () use ($ids): array {
            $transitionedIds = Task::query()
                ->whereIn('id', $ids)
                ->where('code', 'REN')
                ->where('invoice_step', 1)
                ->lockForUpdate()
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            if ($transitionedIds === []) {
                return [];
            }

            Task::query()->whereIn('id', $transitionedIds)->update(['invoice_step' => 2]);

            $this->logService->logInvoiceAction(
                $transitionedIds,
                1,
                2,
                'manual_invoice_marked',
                ['backend' => 'external']
            );

            return $transitionedIds;
        });

        $count = count($transitionedIds);

        return ActionResultDTO::success($count, __('renewals.invoice.recorded', ['count' => $count]), [
            'created' => $count,
            'skipped' => count($ids) - $count,
            'failed' => 0,
            'uncertain' => 0,
        ]);
    }

    private function validateRenewal(object $renewal): ?string
    {
        if (!$renewal->client_id || !$renewal->client_name) {
            return __('renewals.invoice.no_billing_client', ['id' => $renewal->id]);
        }

        if (!$renewal->dolibarr_socid) {
            return __('renewals.invoice.client_not_linked', ['client' => $renewal->client_name]);
        }

        if (!$renewal->uid || !$renewal->due_date || !$renewal->event_date) {
            return __('renewals.invoice.missing_data', ['id' => $renewal->id]);
        }

        return null;
    }

    /**
     * @param  Collection<int, object>  $renewals
     */
    private function reserveBatch(Collection $renewals): RenewalInvoiceBatch
    {
        $taskIds = $renewals->pluck('id')->map(fn ($id): int => (int) $id)->sort()->values()->all();
        $first = $renewals->first();

        try {
            return DB::transaction(function () use ($taskIds, $first): RenewalInvoiceBatch {
                $lockedTasks = Task::query()
                    ->whereIn('id', $taskIds)
                    ->where('code', 'REN')
                    ->lockForUpdate()
                    ->get(['id', 'invoice_step']);

                if ($lockedTasks->count() !== count($taskIds)
                    || $lockedTasks->contains(fn (Task $task): bool => (int) $task->invoice_step !== 1)) {
                    throw new InvalidArgumentException(__('renewals.invoice.not_ready', [
                        'id' => $lockedTasks->first(
                            fn (Task $task): bool => (int) $task->invoice_step !== 1
                        )?->id ?? '?',
                    ]));
                }

                $existing = RenewalInvoiceBatchTask::query()
                    ->whereIn('task_id', $taskIds)
                    ->first();

                if ($existing) {
                    return RenewalInvoiceBatch::findOrFail($existing->batch_id);
                }

                $id = (string) Str::ulid();
                $batch = RenewalInvoiceBatch::create([
                    'id' => $id,
                    'ref_ext' => 'PI' . $id,
                    'actor_id' => (int) $first->client_id,
                    'dolibarr_socid' => (int) $first->dolibarr_socid,
                    'status' => RenewalInvoiceBatch::STATUS_RESERVED,
                    'payload_hash' => hash('sha256', implode(',', $taskIds)),
                    'created_by' => Auth::user()?->login ?? 'system',
                ]);

                $now = now();
                RenewalInvoiceBatchTask::insert(array_map(
                    static fn (int $taskId): array => [
                        'batch_id' => $batch->id,
                        'task_id' => $taskId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    $taskIds
                ));

                return $batch;
            }, 3);
        } catch (QueryException $exception) {
            $mapping = RenewalInvoiceBatchTask::query()
                ->whereIn('task_id', $taskIds)
                ->first();

            if ($mapping) {
                return RenewalInvoiceBatch::findOrFail($mapping->batch_id);
            }

            throw $exception;
        }
    }

    /**
     * @return array{created: int, skipped: int, failed: int, uncertain: int, errors: array<int, string>}
     */
    private function processBatch(RenewalInvoiceBatch $batch): array
    {
        $count = $batch->batchTasks()->count();
        $minimumLockSeconds = (max(1, (int) config('renewal.api.timeout', 15))
            * (max(1, (int) config('renewal.api.retry_times', 2)) + 1)) + 10;
        $lock = Cache::lock(
            "dolibarr-invoice-batch:{$batch->id}",
            max($minimumLockSeconds, (int) config('renewal.api.lock_seconds', 60))
        );

        try {
            return $lock->block(
                max(1, (int) config('renewal.api.lock_wait_seconds', 20)),
                fn (): array => $this->processLockedBatch($batch->fresh())
            );
        } catch (LockTimeoutException) {
            $freshBatch = $batch->fresh();

            if ($freshBatch?->status === RenewalInvoiceBatch::STATUS_CREATED) {
                return $this->outcome(skipped: $count);
            }

            return $this->outcome(
                uncertain: $count,
                error: __('renewals.invoice.batch_processing')
            );
        }
    }

    /**
     * @return array{created: int, skipped: int, failed: int, uncertain: int, errors: array<int, string>}
     */
    private function processLockedBatch(RenewalInvoiceBatch $batch): array
    {
        $taskIds = $batch->batchTasks()->pluck('task_id')->map(fn ($id): int => (int) $id)->all();
        $count = count($taskIds);

        if ($batch->status === RenewalInvoiceBatch::STATUS_CREATED) {
            return $this->outcome(skipped: $count);
        }

        try {
            $existingInvoice = $this->dolibarrClient->getInvoiceByExternalReference($batch->ref_ext);
        } catch (Throwable $exception) {
            $status = $batch->status === RenewalInvoiceBatch::STATUS_UNCERTAIN
                ? RenewalInvoiceBatch::STATUS_UNCERTAIN
                : RenewalInvoiceBatch::STATUS_FAILED;
            $this->recordBatchError($batch, $status, $exception);

            return $status === RenewalInvoiceBatch::STATUS_UNCERTAIN
                ? $this->outcome(uncertain: $count, error: $this->safeExceptionMessage($exception))
                : $this->outcome(failed: $count, error: $this->safeExceptionMessage($exception));
        }

        if ($existingInvoice) {
            $this->finalizeBatch($batch, $existingInvoice);

            return $this->outcome(created: $count);
        }

        $postAttempted = false;

        try {
            $created = $this->createAndFinalizeBatch($batch, $taskIds, $postAttempted);

            return $created
                ? $this->outcome(created: $count)
                : $this->outcome(skipped: $count);
        } catch (Throwable $exception) {
            $statusCode = $exception instanceof DolibarrException ? $exception->statusCode() : null;
            $status = !$postAttempted
                || $exception instanceof InvalidArgumentException
                || ($statusCode !== null && $statusCode >= 400 && $statusCode < 500)
                ? RenewalInvoiceBatch::STATUS_FAILED
                : RenewalInvoiceBatch::STATUS_UNCERTAIN;
            $this->recordBatchError($batch, $status, $exception);

            return $status === RenewalInvoiceBatch::STATUS_FAILED
                ? $this->outcome(failed: $count, error: $this->safeExceptionMessage($exception))
                : $this->outcome(uncertain: $count, error: $this->safeExceptionMessage($exception));
        }
    }

    /**
     * Keep the selected renewals locked from the final state check until the
     * external result is persisted. The transaction is intentionally not
     * retried: after a successful POST, reconciliation must happen through
     * ref_ext rather than by replaying the POST.
     *
     * @param  array<int>  $taskIds
     */
    private function createAndFinalizeBatch(
        RenewalInvoiceBatch $batch,
        array $taskIds,
        bool &$postAttempted
    ): bool {
        return DB::transaction(function () use ($batch, $taskIds, &$postAttempted): bool {
            $lockedBatch = RenewalInvoiceBatch::query()
                ->lockForUpdate()
                ->findOrFail($batch->id);

            if ($lockedBatch->status === RenewalInvoiceBatch::STATUS_CREATED) {
                return false;
            }

            $lockedTasks = Task::query()
                ->whereIn('id', $taskIds)
                ->where('code', 'REN')
                ->lockForUpdate()
                ->get(['id', 'invoice_step']);

            $stateChanged = $lockedTasks->count() !== count($taskIds)
                || $lockedTasks->contains(
                    static fn (Task $task): bool => (int) $task->invoice_step !== 1
                );

            if ($stateChanged) {
                throw new InvalidArgumentException(__('renewals.invoice.state_changed'));
            }

            // The first lookup happened before the SQL locks were acquired. A
            // competing worker may have created the invoice while this worker
            // was waiting, so reconcile once more before the only POST.
            $existingInvoice = $this->dolibarrClient->getInvoiceByExternalReference(
                $lockedBatch->ref_ext
            );
            if ($existingInvoice) {
                $this->finalizeBatch($lockedBatch, $existingInvoice);

                return true;
            }

            $payload = $this->payloadFor($lockedBatch, $taskIds);
            $postAttempted = true;
            $invoice = $this->dolibarrClient->createDraftInvoice($payload);
            $this->finalizeBatch($lockedBatch, $invoice);

            return true;
        });
    }

    /**
     * @param  array<int>  $taskIds
     * @return array<string, mixed>
     */
    private function payloadFor(RenewalInvoiceBatch $batch, array $taskIds): array
    {
        if ($batch->status === RenewalInvoiceBatch::STATUS_UNCERTAIN && $batch->payload) {
            return $batch->payload;
        }

        $renewals = $this->renewalRepository->getRenewalsForInvoicing($taskIds);
        if ($renewals->count() !== count($taskIds)) {
            throw new InvalidArgumentException(__('renewals.invoice.batch_missing'));
        }

        foreach ($renewals as $renewal) {
            $error = $this->validateRenewal($renewal);
            if ($error) {
                throw new InvalidArgumentException($error);
            }

            if ((int) $renewal->client_id !== (int) $batch->actor_id) {
                throw new InvalidArgumentException(__('renewals.invoice.client_changed'));
            }
        }

        $payload = [
            'socid' => (int) $renewals->first()->dolibarr_socid,
            'date' => now()->timestamp,
            'cond_reglement_id' => (int) config('renewal.api.fk_cond_reglement', 1),
            'mode_reglement_id' => (int) config('renewal.api.fk_mode_reglement', 2),
            'lines' => $this->lineBuilder->build($renewals),
            'fk_account' => (int) config('renewal.api.fk_account', 2),
            'ref_ext' => $batch->ref_ext,
        ];

        $encoded = json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
        $batch->update([
            'dolibarr_socid' => $payload['socid'],
            'payload' => $payload,
            'payload_hash' => hash('sha256', $encoded),
            'status' => RenewalInvoiceBatch::STATUS_RESERVED,
            'error_code' => null,
            'error_message' => null,
        ]);

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $invoice
     */
    private function finalizeBatch(RenewalInvoiceBatch $batch, array $invoice): void
    {
        DB::transaction(function () use ($batch, $invoice): void {
            $lockedBatch = RenewalInvoiceBatch::query()->lockForUpdate()->findOrFail($batch->id);

            if ($lockedBatch->status === RenewalInvoiceBatch::STATUS_CREATED) {
                return;
            }

            $taskIds = $lockedBatch->batchTasks()->pluck('task_id')->map(fn ($id): int => (int) $id)->all();
            $transitionedIds = Task::query()
                ->whereIn('id', $taskIds)
                ->where('code', 'REN')
                ->where('invoice_step', 1)
                ->lockForUpdate()
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            if ($transitionedIds !== []) {
                Task::query()->whereIn('id', $transitionedIds)->update(['invoice_step' => 2]);
                $this->logService->logInvoiceAction(
                    $transitionedIds,
                    1,
                    2,
                    'dolibarr_invoice_created',
                    [
                        'batch_id' => $lockedBatch->id,
                        'ref_ext' => $lockedBatch->ref_ext,
                        'dolibarr_invoice_id' => $invoice['id'] ?? $invoice['rowid'] ?? null,
                        'dolibarr_invoice_ref' => $invoice['ref'] ?? null,
                    ]
                );
            }

            $lockedBatch->update([
                'status' => RenewalInvoiceBatch::STATUS_CREATED,
                'external_invoice_id' => $invoice['id'] ?? $invoice['rowid'] ?? null,
                'external_invoice_ref' => $invoice['ref'] ?? null,
                'error_code' => null,
                'error_message' => null,
            ]);
        }, 3);

        Log::info('Dolibarr invoice batch confirmed', [
            'batch_id' => $batch->id,
            'ref_ext' => $batch->ref_ext,
        ]);
    }

    private function recordBatchError(RenewalInvoiceBatch $batch, string $status, Throwable $exception): void
    {
        $statusCode = $exception instanceof DolibarrException ? $exception->statusCode() : null;

        RenewalInvoiceBatch::query()
            ->whereKey($batch->id)
            ->where('status', '!=', RenewalInvoiceBatch::STATUS_CREATED)
            ->update([
                'status' => $status,
                'error_code' => $statusCode ? (string) $statusCode : class_basename($exception),
                'error_message' => Str::limit($this->safeExceptionMessage($exception), 1000),
            ]);

        Log::warning('Dolibarr invoice batch failed', [
            'batch_id' => $batch->id,
            'status' => $status,
            'error_code' => $statusCode,
        ]);
    }

    /**
     * @return array{created: int, skipped: int, failed: int, uncertain: int, errors: array<int, string>}
     */
    private function outcome(
        int $created = 0,
        int $skipped = 0,
        int $failed = 0,
        int $uncertain = 0,
        ?string $error = null
    ): array {
        return [
            'created' => $created,
            'skipped' => $skipped,
            'failed' => $failed,
            'uncertain' => $uncertain,
            'errors' => $error ? [$error] : [],
        ];
    }

    /**
     * @param  array<string, mixed>  $totals
     * @param  array<string, mixed>  $outcome
     */
    private function mergeOutcome(array &$totals, array $outcome): void
    {
        foreach (['created', 'skipped', 'failed', 'uncertain'] as $key) {
            $totals[$key] += $outcome[$key];
        }

        $totals['errors'] = array_values(array_unique([
            ...$totals['errors'],
            ...$outcome['errors'],
        ]));
    }

    private function safeExceptionMessage(Throwable $exception): string
    {
        if ($exception instanceof DolibarrException || $exception instanceof InvalidArgumentException) {
            return $exception->getMessage();
        }

        return __('renewals.invoice.processing_failed');
    }
}
