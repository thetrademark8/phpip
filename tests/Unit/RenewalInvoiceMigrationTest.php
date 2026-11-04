<?php

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::disableForeignKeyConstraints();
    Schema::dropIfExists('renewal_invoice_batch_tasks');
    Schema::dropIfExists('renewal_invoice_batches');
    Schema::dropIfExists('renewals_logs');
    Schema::dropIfExists('task');
    Schema::dropIfExists('actor');

    Schema::create('actor', function (Blueprint $table): void {
        $table->increments('id');
        $table->string('VAT_number')->nullable();
    });
    Schema::create('task', function (Blueprint $table): void {
        $table->increments('id');
    });
    Schema::create('renewals_logs', function (Blueprint $table): void {
        $table->id();
        $table->unsignedInteger('task_id');
        $table->unsignedInteger('job_id');
        $table->string('creator', 16)->nullable();
    });

    Schema::enableForeignKeyConstraints();
});

afterEach(function () {
    Schema::disableForeignKeyConstraints();
    Schema::dropIfExists('renewal_invoice_batch_tasks');
    Schema::dropIfExists('renewal_invoice_batches');
    Schema::dropIfExists('renewals_logs');
    Schema::dropIfExists('task');
    Schema::dropIfExists('actor');
    Schema::enableForeignKeyConstraints();
});

it('creates durable invoice batches with unique actor and task mappings', function () {
    $billingFields = require database_path('migrations/2026_09_23_000001_add_dolibarr_billing_fields.php');
    $invoiceBatches = require database_path('migrations/2026_09_23_000002_create_renewal_invoice_batches.php');

    $billingFields->up();
    $invoiceBatches->up();

    expect(Schema::hasColumns('actor', ['dolibarr_socid']))->toBeTrue()
        ->and(Schema::hasColumns('renewals_logs', ['action', 'data']))->toBeTrue()
        ->and(Schema::hasColumns('renewal_invoice_batches', [
            'ref_ext',
            'status',
            'payload_hash',
            'payload',
            'external_invoice_id',
            'external_invoice_ref',
        ]))->toBeTrue();

    DB::table('actor')->insert([
        ['id' => 1, 'dolibarr_socid' => 42],
        ['id' => 2, 'dolibarr_socid' => null],
    ]);
    DB::table('task')->insert(['id' => 1]);

    $now = now();
    DB::table('renewal_invoice_batches')->insert([
        [
            'id' => '01AAAAAAAAAAAAAAAAAAAAAAAA',
            'ref_ext' => 'PI01AAAAAAAAAAAAAAAAAAAAAAAA',
            'actor_id' => 1,
            'dolibarr_socid' => 42,
            'status' => 'reserved',
            'payload_hash' => str_repeat('a', 64),
            'created_at' => $now,
            'updated_at' => $now,
        ],
        [
            'id' => '01BBBBBBBBBBBBBBBBBBBBBBBB',
            'ref_ext' => 'PI01BBBBBBBBBBBBBBBBBBBBBBBB',
            'actor_id' => 2,
            'dolibarr_socid' => 43,
            'status' => 'reserved',
            'payload_hash' => str_repeat('b', 64),
            'created_at' => $now,
            'updated_at' => $now,
        ],
    ]);
    DB::table('renewal_invoice_batch_tasks')->insert([
        'batch_id' => '01AAAAAAAAAAAAAAAAAAAAAAAA',
        'task_id' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    expect(fn () => DB::table('actor')->where('id', 2)->update(['dolibarr_socid' => 42]))
        ->toThrow(QueryException::class)
        ->and(fn () => DB::table('renewal_invoice_batch_tasks')->insert([
            'batch_id' => '01BBBBBBBBBBBBBBBBBBBBBBBB',
            'task_id' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]))->toThrow(QueryException::class);
});
