<?php

use App\Contracts\Renewal\RenewalFeeCalculatorInterface;
use App\DataTransferObjects\Renewal\RenewalDTO;
use App\DataTransferObjects\Renewal\RenewalFeeDTO;
use App\Repositories\ActorRepository;
use App\Repositories\Contracts\ActorRepositoryInterface;
use App\Repositories\Contracts\RenewalRepositoryInterface;
use App\Services\Renewal\RenewalFeeCalculatorService;
use App\Services\Renewal\RenewalInvoiceLineBuilder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery\MockInterface;

function invoiceRenewalRow(array $attributes = []): object
{
    return (object) array_merge([
        'id' => 101,
        'matter_id' => 201,
        'uid' => 'TM-2026-001',
        'caseref' => 'CASE-001',
        'country' => 'FR',
        'country_FR' => 'France',
        'country_EN' => 'France',
        'country_DE' => 'Frankreich',
        'category' => 'TM',
        'detail' => 10,
        'due_date' => '2026-10-15',
        'done' => 0,
        'done_date' => null,
        'cost' => 75.0,
        'fee' => 100.0,
        'step' => 4,
        'grace_period' => 0,
        'invoice_step' => 1,
        'responsible' => 'agent',
        'client_name' => 'Example Client',
        'client_ref' => 'CLIENT-REF',
        'short_title' => 'Short title',
        'title' => 'Example title',
        'number' => '123456',
        'event_name' => 'FIL',
        'event_date' => '2016-10-15',
        'language' => 'fr',
        'discount' => null,
        'small_entity' => false,
        'table_fee' => 1.0,
        'cost_sup_reduced' => null,
        'cost_sup' => 75.0,
        'fee_sup_reduced' => null,
        'fee_sup' => 100.0,
        'cost_reduced' => 75.0,
        'fee_reduced' => 100.0,
        'applicant_name' => 'Applicant',
        'pub_num' => null,
        'fil_num' => null,
        'origin' => null,
        'client_id' => 301,
        'client_vat_number' => null,
        'dolibarr_socid' => 401,
    ], $attributes);
}

it('builds invoice lines from a collection of model-like objects', function () {
    $calculator = Mockery::mock(RenewalFeeCalculatorInterface::class, function (MockInterface $mock): void {
        $mock->shouldReceive('calculate')
            ->once()
            ->with(Mockery::on(fn ($value): bool => $value instanceof RenewalDTO && $value->id === 101))
            ->andReturn(RenewalFeeDTO::create(75.0, 100.0, 0.2));
    });

    $lines = (new RenewalInvoiceLineBuilder($calculator))->build(
        collect([invoiceRenewalRow()])
    );

    expect($lines)->toHaveCount(2)
        ->and($lines[0]['desc'])->toContain('TM-2026-001')
        ->and($lines[0]['subprice'])->toBe(100.0)
        ->and($lines[1]['subprice'])->toBe(75.0);
});

it('uses the client language and falls back to French', function () {
    $calculator = Mockery::mock(RenewalFeeCalculatorInterface::class);
    $calculator->shouldReceive('calculate')
        ->times(4)
        ->andReturn(RenewalFeeDTO::create(0.0, 100.0, 0.2));

    $lines = (new RenewalInvoiceLineBuilder($calculator))->build(collect([
        invoiceRenewalRow(['id' => 1, 'language' => 'fr']),
        invoiceRenewalRow(['id' => 2, 'language' => 'en-US']),
        invoiceRenewalRow(['id' => 3, 'language' => 'de']),
        invoiceRenewalRow(['id' => 4, 'language' => 'it']),
    ]));

    expect($lines)->toHaveCount(4)
        ->and($lines[0]['desc'])->toContain("Annuité pour l'année", 'Sujet :', 'France')
        ->and($lines[1]['desc'])->toContain('Renewal fee for year', 'Subject:', 'France')
        ->and($lines[2]['desc'])->toContain('Jahresgebühr für das Jahr', 'Betreff:', 'Frankreich')
        ->and($lines[3]['desc'])->toContain("Annuité pour l'année", 'Sujet :');
});

it('uses the calculated VAT rate only on each fee line', function () {
    $calculator = Mockery::mock(RenewalFeeCalculatorInterface::class);
    $calculator->shouldReceive('calculate')->twice()
        ->andReturn(
            RenewalFeeDTO::create(75.0, 100.0, 0.2),
            RenewalFeeDTO::create(80.0, 100.0, 0.0),
        );

    $lines = (new RenewalInvoiceLineBuilder($calculator))->build(
        collect([
            invoiceRenewalRow(),
            invoiceRenewalRow(['id' => 102, 'client_vat_number' => 'BE0123456789']),
        ])
    );

    expect($lines[0]['tva_tx'])->toBe(20.0)
        ->and($lines[0]['total_tva'])->toBe(20.0)
        ->and($lines[0]['total_ttc'])->toBe(120.0)
        ->and($lines[1]['tva_tx'])->toBe(0.0)
        ->and($lines[1]['total_tva'])->toBe(0.0)
        ->and($lines[1]['total_ttc'])->toBe(75.0)
        ->and($lines[2]['tva_tx'])->toBe(0.0)
        ->and($lines[2]['total_tva'])->toBe(0.0)
        ->and($lines[2]['total_ttc'])->toBe(100.0)
        ->and($lines[3]['total_ttc'])->toBe(80.0);
});

it('selects domestic or foreign VAT from the billing actor VAT number', function () {
    Schema::dropIfExists('actor');
    Schema::create('actor', function (Blueprint $table): void {
        $table->increments('id');
        $table->string('VAT_number')->nullable();
    });

    try {
        DB::table('actor')->insert([
            ['id' => 1, 'VAT_number' => null],
            ['id' => 2, 'VAT_number' => ' FR 12 345 '],
            ['id' => 3, 'VAT_number' => 'BE0123456789'],
        ]);
        config()->set('renewal.invoice.vat_rate', 0.2);
        config()->set('renewal.invoice.foreign_vat_rate', 0.0);

        $repository = new ActorRepository;

        expect($repository->getVatRate(1))->toBe(0.2)
            ->and($repository->getVatRate(2))->toBe(0.2)
            ->and($repository->getVatRate(3))->toBe(0.0);
    } finally {
        Schema::dropIfExists('actor');
    }
});

it('applies percentage discount and grace factor exactly once', function () {
    config()->set('renewal.validity.fee_factor', 1.2);

    $actorRepository = Mockery::mock(ActorRepositoryInterface::class);
    $actorRepository->shouldReceive('getVatRate')->once()->with(301)->andReturn(0.2);

    $calculator = new RenewalFeeCalculatorService(
        $actorRepository,
        Mockery::mock(RenewalRepositoryInterface::class)
    );

    $renewal = RenewalDTO::fromModel(invoiceRenewalRow([
        'grace_period' => 1,
        'discount' => 0.1,
        'cost_sup' => 1000.0,
        'fee_sup' => 500.0,
    ]));

    $fees = $calculator->calculate($renewal);

    // 500 - 10%, then the 1.2 grace factor: 500 * 0.9 * 1.2 = 540.
    expect($fees->cost)->toBe(1000.0)
        ->and($fees->fee)->toBe(540.0)
        ->and($fees->vat)->toBe(108.0)
        ->and($fees->total)->toBe(1648.0)
        ->and($fees->discount)->toBe(0.1)
        ->and($fees->feeFactor)->toBe(1.2)
        ->and($fees->gracePeriodApplied)->toBeTrue();
});

it('applies a fixed client fee and grace factor exactly once', function () {
    config()->set('renewal.validity.fee_factor', 1.2);

    $actorRepository = Mockery::mock(ActorRepositoryInterface::class);
    $actorRepository->shouldReceive('getVatRate')->once()->with(301)->andReturn(0.0);

    $calculator = new RenewalFeeCalculatorService(
        $actorRepository,
        Mockery::mock(RenewalRepositoryInterface::class)
    );

    $renewal = RenewalDTO::fromModel(invoiceRenewalRow([
        'grace_period' => 1,
        'discount' => 300.0,
        'cost_sup' => 1000.0,
        'fee_sup' => 500.0,
    ]));

    $fees = $calculator->calculate($renewal);

    expect($fees->cost)->toBe(1000.0)
        ->and($fees->fee)->toBe(360.0)
        ->and($fees->vat)->toBe(0.0)
        ->and($fees->total)->toBe(1360.0)
        ->and($fees->discount)->toBe(300.0)
        ->and($fees->feeFactor)->toBe(1.2);
});

it('preserves task fees when no discount applies', function () {
    config()->set('renewal.invoice.default_fee', 145);

    $actorRepository = Mockery::mock(ActorRepositoryInterface::class);
    $actorRepository->shouldReceive('getVatRate')->once()->andReturn(0.2);
    $calculator = new RenewalFeeCalculatorService(
        $actorRepository,
        Mockery::mock(RenewalRepositoryInterface::class)
    );

    $renewal = RenewalDTO::fromModel(invoiceRenewalRow([
        'table_fee' => null,
        'fee' => 245.0,
        'discount' => null,
    ]));

    expect($calculator->calculate($renewal)->fee)->toBe(245.0);
});

it('uses reduced table amounts for small entities from the renewal query alias', function () {
    $actorRepository = Mockery::mock(ActorRepositoryInterface::class);
    $actorRepository->shouldReceive('getVatRate')->once()->andReturn(0.2);
    $calculator = new RenewalFeeCalculatorService(
        $actorRepository,
        Mockery::mock(RenewalRepositoryInterface::class)
    );

    $renewal = RenewalDTO::fromModel(invoiceRenewalRow([
        'small_entity' => 1,
        'cost' => 1000.0,
        'fee' => 500.0,
        'cost_reduced' => 400.0,
        'fee_reduced' => 250.0,
    ]));

    $fees = $calculator->calculate($renewal);

    expect($fees->cost)->toBe(400.0)
        ->and($fees->fee)->toBe(250.0);
});
