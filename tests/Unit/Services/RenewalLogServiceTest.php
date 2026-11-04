<?php

use App\Models\RenewalsLog;
use App\Services\Renewal\RenewalLogService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('renewals_logs');
    Schema::create('renewals_logs', function (Blueprint $table): void {
        $table->id();
        $table->unsignedInteger('task_id');
        $table->unsignedInteger('job_id');
        $table->string('action')->nullable();
        $table->json('data')->nullable();
        $table->string('creator')->nullable();
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('renewals_logs');
});

it('stores structured action data without double encoding it', function () {
    (new RenewalLogService)->logAction(7, 'dolibarr_invoice_created', [
        'ref_ext' => 'PI01TEST',
    ]);

    $log = RenewalsLog::query()->firstOrFail();

    expect($log->data)->toBe(['ref_ext' => 'PI01TEST']);
});
