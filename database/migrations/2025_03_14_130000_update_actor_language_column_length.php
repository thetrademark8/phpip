<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip this migration for SQLite in testing
        if (DB::connection()->getDriverName() === 'sqlite' && app()->environment('testing')) {
            return;
        }
        
        Schema::table('actor', function (Blueprint $table) {
            // Update language column type from CHAR(2) to CHAR(5)
            $table->char('language', 5)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Skip this migration for SQLite in testing
        if (DB::connection()->getDriverName() === 'sqlite' && app()->environment('testing')) {
            return;
        }
        
        Schema::table('actor', function (Blueprint $table) {
            // Revert back to CHAR(2)
            $table->char('language', 2)->change();
        });
    }
};