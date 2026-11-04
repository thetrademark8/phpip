<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actor', function (Blueprint $table) {
            $table->unsignedBigInteger('dolibarr_socid')
                ->nullable()
                ->unique()
                ->after('VAT_number')
                ->comment('Linked Dolibarr third-party ID');
        });

        Schema::table('renewals_logs', function (Blueprint $table) {
            $table->string('action', 100)->nullable()->after('job_id');
            $table->json('data')->nullable()->after('action');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::table('renewals_logs', function (Blueprint $table) {
            $table->dropIndex(['action']);
            $table->dropColumn(['action', 'data']);
        });

        Schema::table('actor', function (Blueprint $table) {
            $table->dropUnique(['dolibarr_socid']);
            $table->dropColumn('dolibarr_socid');
        });
    }
};
