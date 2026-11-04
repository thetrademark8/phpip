<?php

use App\Contracts\Renewal\DolibarrClientInterface;
use App\Contracts\Renewal\RenewalLogServiceInterface;
use App\Exceptions\Dolibarr\DolibarrException;
use App\Models\RenewalInvoiceBatch;
use App\Models\RenewalInvoiceBatchTask;
use App\Repositories\Contracts\RenewalRepositoryInterface;
use App\Services\Renewal\RenewalInvoiceLineBuilder;
use App\Services\Renewal\RenewalInvoiceService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery\MockInterface;

function serviceRenewalRow(int $id, int $clientId = 10, array $attributes = []): object
{
    return (object) array_merge([
        'id' => $id,
        'invoice_step' => 1,
        'client_id' => $clientId,
        'client_name' => "Client $clientId",
        'dolibarr_socid' => 1000 + $clientId,
        'uid' => "TM-$id",
        'due_date' => '2026-10-15',
        'event_date' => '2016-10-15',
    ], $attributes);
}

beforeEach(function () {
    Schema::dropIfExists('renewal_invoice_batch_tasks');
    Schema::dropIfExists('renewal_invoice_batches');
    Schema::dropIfExists('task');

    Schema::create('task', function (Blueprint $table): void {
        $table->increments('id');
        $table->string('code', 10);
        $table->unsignedTinyInteger('invoice_step')->default(0);
        $table->timestamps();
    });

    Schema::create('renewal_invoice_batches', function (Blueprint $table): void {
        $table->ulid('id')->primary();
        $table->string('ref_ext', 30)->unique();
        $table->unsignedInteger('actor_id');
        $table->unsignedBigInteger('dolibarr_socid');
        $table->string('status', 20);
        $table->char('payload_hash', 64);
        $table->json('payload')->nullable();
        $table->unsignedBigInteger('external_invoice_id')->nullable();
        $table->string('external_invoice_ref')->nullable();
        $table->string('error_code')->nullable();
        $table->text('error_message')->nullable();
        $table->string('created_by')->nullable();
        $table->timestamps();
    });

    Schema::create('renewal_invoice_batch_tasks', function (Blueprint $table): void {
        $table->ulid('batch_id');
        $table->unsignedInteger('task_id')->unique();
        $table->timestamps();
        $table->primary(['batch_id', 'task_id']);
    });

    config()->set('renewal.invoice.backend', 'dolibarr');
});

afterEach(function () {
    Schema::dropIfExists('renewal_invoice_batch_tasks');
    Schema::dropIfExists('renewal_invoice_batches');
    Schema::dropIfExists('task');
});

/**
 * @param  Collection<int, object>  $renewals
 * @return array{0: RenewalInvoiceService, 1: MockInterface, 2: MockInterface, 3: MockInterface, 4: MockInterface}
 */
function makeInvoiceService(Collection $renewals): array
{
    foreach ($renewals as $renewal) {
        DB::table('task')->insert([
            'id' => $renewal->id,
            'code' => 'REN',
            'invoice_step' => $renewal->invoice_step,
        ]);
    }

    $repository = Mockery::mock(RenewalRepositoryInterface::class);
    $repository->shouldReceive('getRenewalsForInvoicing')
        ->andReturnUsing(function (array $ids) use ($renewals): Collection {
            return $renewals
                ->filter(fn ($renewal): bool => in_array((int) $renewal->id, $ids, true))
                ->values();
        });

    $lineBuilder = Mockery::mock(RenewalInvoiceLineBuilder::class);
    $lineBuilder->shouldReceive('build')
        ->with(Mockery::on(fn ($value): bool => $value instanceof Collection))
        ->andReturn([['desc' => 'Renewal', 'subprice' => 100.0]]);

    $dolibarr = Mockery::mock(DolibarrClientInterface::class);
    $dolibarr->shouldReceive('assertInvoiceExternalReferenceSupported')->byDefault();
    $logs = Mockery::mock(RenewalLogServiceInterface::class);
    $logs->shouldReceive('logInvoiceAction')->byDefault();

    return [
        new RenewalInvoiceService($repository, $lineBuilder, $dolibarr, $logs),
        $repository,
        $lineBuilder,
        $dolibarr,
        $logs,
    ];
}

it('creates one Dolibarr draft for one client and advances its renewals', function () {
    $renewals = collect([
        serviceRenewalRow(1),
        serviceRenewalRow(2),
    ]);
    [$service, , $lineBuilder, $dolibarr, $logs] = makeInvoiceService($renewals);

    $dolibarr->shouldReceive('getInvoiceByExternalReference')->twice()->andReturnNull();
    $dolibarr->shouldReceive('createDraftInvoice')
        ->once()
        ->with(Mockery::on(function (array $payload): bool {
            return $payload['socid'] === 1010
                && str_starts_with($payload['ref_ext'], 'PI')
                && strlen($payload['ref_ext']) === 28
                && count($payload['lines']) === 1;
        }))
        ->andReturn(['id' => 501, 'ref' => 'FA260001']);
    $logs->shouldReceive('logInvoiceAction')
        ->once()
        ->with([1, 2], 1, 2, 'dolibarr_invoice_created', Mockery::type('array'));

    $result = $service->createInvoices([1, 2]);
    $lineBuilder->shouldHaveReceived('build')->twice();

    expect($result->success)->toBeTrue(($result->error ?? '') . json_encode($result->data))
        ->and($result->count)->toBe(2)
        ->and($result->data['created'])->toBe(2)
        ->and($result->data['failed'])->toBe(0)
        ->and(DB::table('task')->whereIn('id', [1, 2])->pluck('invoice_step')->all())
        ->toBe([2, 2])
        ->and(RenewalInvoiceBatch::query()->first()->status)->toBe(RenewalInvoiceBatch::STATUS_CREATED)
        ->and(RenewalInvoiceBatch::query()->first()->external_invoice_ref)->toBe('FA260001');
});

it('keeps failed client renewals pending after a partial success', function () {
    $renewals = collect([
        serviceRenewalRow(1, 10),
        serviceRenewalRow(2, 20),
    ]);
    [$service, , , $dolibarr] = makeInvoiceService($renewals);

    $dolibarr->shouldReceive('getInvoiceByExternalReference')->times(4)->andReturnNull();
    $dolibarr->shouldReceive('createDraftInvoice')
        ->once()
        ->andReturn(['id' => 501, 'ref' => 'FA260001']);
    $dolibarr->shouldReceive('createDraftInvoice')
        ->once()
        ->andThrow(new DolibarrException('Rejected payload.', 'create invoice', 422));

    $result = $service->createInvoices([1, 2]);

    expect($result->success)->toBeTrue()
        ->and($result->count)->toBe(1)
        ->and($result->data['created'])->toBe(1)
        ->and($result->data['failed'])->toBe(1)
        ->and(DB::table('task')->where('id', 1)->value('invoice_step'))->toBe(2)
        ->and(DB::table('task')->where('id', 2)->value('invoice_step'))->toBe(1)
        ->and(RenewalInvoiceBatch::query()->orderBy('actor_id')->pluck('status')->all())
        ->toBe([RenewalInvoiceBatch::STATUS_CREATED, RenewalInvoiceBatch::STATUS_FAILED]);
});

it('reconciles an uncertain batch by external reference without creating a duplicate', function () {
    $renewals = collect([serviceRenewalRow(1)]);
    [$service, , , $dolibarr, $logs] = makeInvoiceService($renewals);

    $batch = RenewalInvoiceBatch::create([
        'id' => (string) Illuminate\Support\Str::ulid(),
        'ref_ext' => 'PI01TESTRECONCILIATION000001',
        'actor_id' => 10,
        'dolibarr_socid' => 1010,
        'status' => RenewalInvoiceBatch::STATUS_UNCERTAIN,
        'payload_hash' => hash('sha256', 'test'),
        'payload' => ['socid' => 1010, 'ref_ext' => 'PI01TESTRECONCILIATION000001'],
    ]);
    RenewalInvoiceBatchTask::create(['batch_id' => $batch->id, 'task_id' => 1]);

    $dolibarr->shouldReceive('getInvoiceByExternalReference')
        ->once()
        ->with('PI01TESTRECONCILIATION000001')
        ->andReturn(['id' => 777, 'ref' => 'FA-RECOVERED']);
    $dolibarr->shouldNotReceive('createDraftInvoice');
    $logs->shouldReceive('logInvoiceAction')->once();

    $result = $service->createInvoices([1]);

    expect($result->success)->toBeTrue()
        ->and($result->data['created'])->toBe(1)
        ->and(DB::table('task')->where('id', 1)->value('invoice_step'))->toBe(2)
        ->and($batch->fresh()->status)->toBe(RenewalInvoiceBatch::STATUS_CREATED)
        ->and($batch->fresh()->external_invoice_id)->toBe(777);
});

it('skips an already confirmed batch without calling Dolibarr again', function () {
    $renewals = collect([serviceRenewalRow(1, 10, ['invoice_step' => 2])]);
    [$service, , , $dolibarr] = makeInvoiceService($renewals);

    $batch = RenewalInvoiceBatch::create([
        'id' => (string) Illuminate\Support\Str::ulid(),
        'ref_ext' => 'PI01TESTALREADYCREATED000001',
        'actor_id' => 10,
        'dolibarr_socid' => 1010,
        'status' => RenewalInvoiceBatch::STATUS_CREATED,
        'payload_hash' => hash('sha256', 'test'),
        'external_invoice_id' => 501,
    ]);
    RenewalInvoiceBatchTask::create(['batch_id' => $batch->id, 'task_id' => 1]);

    $dolibarr->shouldNotReceive('getInvoiceByExternalReference');
    $dolibarr->shouldNotReceive('createDraftInvoice');

    $result = $service->createInvoices([1]);

    expect($result->success)->toBeTrue()
        ->and($result->count)->toBe(0)
        ->and($result->data['created'])->toBe(0)
        ->and($result->data['skipped'])->toBe(1)
        ->and(RenewalInvoiceBatch::query()->count())->toBe(1);
});

it('supports invoice zero by advancing locally without calling Dolibarr', function () {
    $renewals = collect([
        serviceRenewalRow(1),
        serviceRenewalRow(2),
    ]);
    [$service, , , $dolibarr, $logs] = makeInvoiceService($renewals);
    config()->set('renewal.invoice.backend', 'none');

    $dolibarr->shouldNotReceive('getInvoiceByExternalReference');
    $dolibarr->shouldNotReceive('createDraftInvoice');
    $logs->shouldReceive('logInvoiceAction')
        ->once()
        ->with([1, 2], 1, 2, 'manual_invoice_marked', ['backend' => 'external']);

    $result = $service->createInvoices([1, 2], false);

    expect($result->success)->toBeTrue()
        ->and($result->count)->toBe(2)
        ->and(DB::table('task')->whereIn('id', [1, 2])->pluck('invoice_step')->all())
        ->toBe([2, 2])
        ->and(RenewalInvoiceBatch::query()->count())->toBe(0);
});

it('blocks manual invoicing while the Dolibarr backend is active', function () {
    $renewals = collect([serviceRenewalRow(1)]);
    [$service, , , $dolibarr] = makeInvoiceService($renewals);

    $dolibarr->shouldNotReceive('getInvoiceByExternalReference');
    $dolibarr->shouldNotReceive('createDraftInvoice');

    $result = $service->createInvoices([1], false);

    expect($result->success)->toBeFalse()
        ->and(DB::table('task')->where('id', 1)->value('invoice_step'))->toBe(1);
});

it('does not silently mark renewals invoiced when Dolibarr is disabled', function () {
    $renewals = collect([serviceRenewalRow(1)]);
    [$service, , , $dolibarr] = makeInvoiceService($renewals);
    config()->set('renewal.invoice.backend', 'none');

    $dolibarr->shouldNotReceive('getInvoiceByExternalReference');
    $dolibarr->shouldNotReceive('createDraftInvoice');

    $result = $service->createInvoices([1], true);

    expect($result->success)->toBeFalse()
        ->and(DB::table('task')->where('id', 1)->value('invoice_step'))->toBe(1)
        ->and(RenewalInvoiceBatch::query()->count())->toBe(0);
});

it('refuses to post when a reserved renewal changed state', function () {
    $renewals = collect([serviceRenewalRow(1)]);
    [$service, , , $dolibarr] = makeInvoiceService($renewals);

    $dolibarr->shouldReceive('getInvoiceByExternalReference')
        ->once()
        ->andReturnUsing(function (): null {
            DB::table('task')->where('id', 1)->update(['invoice_step' => 3]);

            return null;
        });
    $dolibarr->shouldNotReceive('createDraftInvoice');

    $result = $service->createInvoices([1]);

    expect($result->success)->toBeFalse()
        ->and($result->data['failed'])->toBe(1)
        ->and($result->data['uncertain'])->toBe(0)
        ->and(RenewalInvoiceBatch::query()->firstOrFail()->status)
        ->toBe(RenewalInvoiceBatch::STATUS_FAILED);
});

it('classifies an invalid successful response as uncertain', function () {
    $renewals = collect([serviceRenewalRow(1)]);
    [$service, , , $dolibarr] = makeInvoiceService($renewals);

    $dolibarr->shouldReceive('getInvoiceByExternalReference')->twice()->andReturnNull();
    $dolibarr->shouldReceive('createDraftInvoice')
        ->once()
        ->andThrow(new DolibarrException('Invalid response.', 'create invoice', 200));

    $result = $service->createInvoices([1]);

    expect($result->success)->toBeFalse()
        ->and($result->data['failed'])->toBe(0)
        ->and($result->data['uncertain'])->toBe(1)
        ->and(RenewalInvoiceBatch::query()->firstOrFail()->status)
        ->toBe(RenewalInvoiceBatch::STATUS_UNCERTAIN);
});

it('does not overwrite a batch confirmed by a competing worker', function () {
    $renewals = collect([serviceRenewalRow(1)]);
    [$service, , , $dolibarr] = makeInvoiceService($renewals);

    $dolibarr->shouldReceive('getInvoiceByExternalReference')
        ->once()
        ->andReturnUsing(function (): null {
            DB::table('task')->where('id', 1)->update(['invoice_step' => 2]);
            RenewalInvoiceBatch::query()->update([
                'status' => RenewalInvoiceBatch::STATUS_CREATED,
                'external_invoice_id' => 999,
            ]);

            return null;
        });
    $dolibarr->shouldNotReceive('createDraftInvoice');

    $result = $service->createInvoices([1]);

    expect($result->success)->toBeTrue()
        ->and($result->data['created'])->toBe(0)
        ->and($result->data['skipped'])->toBe(1)
        ->and(RenewalInvoiceBatch::query()->firstOrFail()->status)
        ->toBe(RenewalInvoiceBatch::STATUS_CREATED);
});
