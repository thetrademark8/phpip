<?php

use App\Models\RenewalsLog;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Repositories\Contracts\RenewalRepositoryInterface;
use App\Services\Renewal\RenewalWorkflowService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('renewals_logs');
    Schema::dropIfExists('task');

    Schema::create('task', function (Blueprint $table): void {
        $table->increments('id');
        $table->string('code', 10);
        $table->unsignedTinyInteger('invoice_step')->default(0);
        $table->timestamps();
    });
    Schema::create('renewals_logs', function (Blueprint $table): void {
        $table->id();
        $table->unsignedInteger('task_id');
        $table->unsignedInteger('job_id');
        $table->unsignedTinyInteger('from_invoice')->nullable();
        $table->unsignedTinyInteger('to_invoice')->nullable();
        $table->string('action')->nullable();
        $table->json('data')->nullable();
        $table->string('creator')->nullable();
        $table->timestamp('created_at')->nullable();
        $table->timestamp('updated_at')->nullable();
    });
});

afterEach(function () {
    Schema::dropIfExists('renewals_logs');
    Schema::dropIfExists('task');
});

it('locks, updates and logs only genuine invoice transitions', function () {
    DB::table('task')->insert([
        ['id' => 1, 'code' => 'REN', 'invoice_step' => 1],
        ['id' => 2, 'code' => 'REN', 'invoice_step' => 2],
    ]);

    $service = new RenewalWorkflowService(
        Mockery::mock(RenewalRepositoryInterface::class),
        Mockery::mock(EventRepositoryInterface::class),
    );

    $result = $service->updateInvoiceStep([1, 2], 2);

    expect($result->success)->toBeTrue()
        ->and($result->count)->toBe(1)
        ->and(DB::table('task')->orderBy('id')->pluck('invoice_step')->all())->toBe([2, 2])
        ->and(RenewalsLog::query()->count())->toBe(1)
        ->and(RenewalsLog::query()->firstOrFail()->only([
            'task_id',
            'from_invoice',
            'to_invoice',
        ]))->toBe([
            'task_id' => 1,
            'from_invoice' => 1,
            'to_invoice' => 2,
        ]);
});
