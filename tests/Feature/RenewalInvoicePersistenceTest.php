<?php

use App\Models\Actor;
use App\Models\RenewalInvoiceBatch;
use App\Models\RenewalInvoiceBatchTask;
use App\Models\RenewalsLog;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Tests\Support\RenewalTestHelpers;

it('persists the Dolibarr link and an invoice batch with its tasks', function () {
    $actor = Actor::factory()->create(['dolibarr_socid' => 12345]);
    $renewal = RenewalTestHelpers::createRenewalInStep(4, ['invoice_step' => 1]);
    $payload = ['socid' => 12345, 'lines' => [['subprice' => 145.0]]];

    $batch = RenewalInvoiceBatch::factory()->create([
        'actor_id' => $actor->id,
        'dolibarr_socid' => 12345,
        'payload' => $payload,
        'payload_hash' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
    ]);
    $batch->tasks()->attach($renewal->id);

    expect($batch->id)->toHaveLength(26)
        ->and($batch->fresh()->payload)->toBe($payload)
        ->and($batch->actor->is($actor))->toBeTrue()
        ->and($batch->tasks)->toHaveCount(1)
        ->and($batch->tasks->first()->is($renewal))->toBeTrue()
        ->and($renewal->renewalInvoiceBatches()->first()->is($batch))->toBeTrue()
        ->and(RenewalInvoiceBatchTask::query()->where('task_id', $renewal->id)->exists())->toBeTrue();
});

it('prevents a renewal task from being reserved in two invoice batches', function () {
    $actor = Actor::factory()->create(['dolibarr_socid' => 12346]);
    $renewal = RenewalTestHelpers::createRenewalInStep(4, ['invoice_step' => 1]);
    $firstBatch = RenewalInvoiceBatch::factory()->create([
        'actor_id' => $actor->id,
        'dolibarr_socid' => 12346,
    ]);
    $secondBatch = RenewalInvoiceBatch::factory()->create([
        'actor_id' => $actor->id,
        'dolibarr_socid' => 12346,
    ]);

    $firstBatch->tasks()->attach($renewal->id);

    expect(fn () => $secondBatch->tasks()->attach($renewal->id))
        ->toThrow(QueryException::class);
});

it('stores structured renewal audit fields', function () {
    $renewal = RenewalTestHelpers::createRenewalInStep(4, ['invoice_step' => 1]);

    RenewalsLog::create([
        'task_id' => $renewal->id,
        'job_id' => 1,
        'action' => 'dolibarr_invoice_created',
        'data' => ['ref_ext' => 'REN-TEST'],
        'creator' => 'system',
    ]);

    expect(Schema::hasColumns('renewals_logs', ['action', 'data']))->toBeTrue();
    $this->assertDatabaseHas('renewals_logs', [
        'task_id' => $renewal->id,
        'action' => 'dolibarr_invoice_created',
    ]);
    expect(RenewalsLog::query()->firstOrFail()->data)->toBe(['ref_ext' => 'REN-TEST']);
});

it('enforces a unique Dolibarr third-party mapping per actor', function () {
    Actor::factory()->create(['dolibarr_socid' => 67890]);

    expect(fn () => Actor::factory()->create(['dolibarr_socid' => 67890]))
        ->toThrow(QueryException::class);
});
