<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Fix the French translation typo for the "Former Owner" actor role
     * ("Ancien titulairte" -> "Ancien titulaire").
     */
    public function up(): void
    {
        DB::table('actor_role')
            ->where('code', 'FOWN')
            ->where('name->fr', 'Ancien titulairte')
            ->update(['name->fr' => 'Ancien titulaire']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('actor_role')
            ->where('code', 'FOWN')
            ->where('name->fr', 'Ancien titulaire')
            ->update(['name->fr' => 'Ancien titulairte']);
    }
};
