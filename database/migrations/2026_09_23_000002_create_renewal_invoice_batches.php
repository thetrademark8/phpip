<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('renewal_invoice_batches', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('ref_ext', 30)->unique();
            $table->unsignedInteger('actor_id');
            $table->unsignedBigInteger('dolibarr_socid');
            $table->string('status', 20)->index();
            $table->char('payload_hash', 64);
            $table->json('payload')->nullable();
            $table->unsignedBigInteger('external_invoice_id')->nullable();
            $table->string('external_invoice_ref', 255)->nullable();
            $table->string('error_code', 100)->nullable();
            $table->text('error_message')->nullable();
            $table->char('created_by', 16)->nullable();
            $table->timestamps();

            $table->foreign('actor_id')
                ->references('id')
                ->on('actor')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->index(['actor_id', 'status']);
            $table->index('external_invoice_id');
        });

        Schema::create('renewal_invoice_batch_tasks', function (Blueprint $table) {
            $table->ulid('batch_id');
            $table->unsignedInteger('task_id')->unique();
            $table->timestamps();

            $table->primary(['batch_id', 'task_id']);
            $table->foreign('batch_id')
                ->references('id')
                ->on('renewal_invoice_batches')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->foreign('task_id')
                ->references('id')
                ->on('task')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('renewal_invoice_batch_tasks');
        Schema::dropIfExists('renewal_invoice_batches');
    }
};
