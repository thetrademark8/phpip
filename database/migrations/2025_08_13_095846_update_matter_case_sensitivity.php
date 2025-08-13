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
        // Update classifier value column to use case-sensitive collation (contains titles)
        DB::statement("ALTER TABLE classifier 
            MODIFY value TEXT 
            CHARACTER SET utf8mb4 
            COLLATE utf8mb4_bin"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to case-insensitive collation
        DB::statement("ALTER TABLE classifier 
            MODIFY value TEXT 
            CHARACTER SET utf8mb4 
            COLLATE utf8mb4_unicode_ci"
        );
    }
};
