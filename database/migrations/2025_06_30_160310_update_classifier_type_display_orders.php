<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration updates the display_order of classifier types so that:
     * - Title (column 1) shows Official Title (TITOF)
     * - Title2 (column 2) shows Title (TIT)
     * - Title3 (column 3) shows Title English (TITEN)
     */
    public function up(): void
    {
        // Update TITOF (Official Title) to display_order = 1
        DB::table('classifier_type')
            ->where('code', 'TITOF')
            ->update(['display_order' => 1]);
            
        // Update TIT (Title) to display_order = 2
        DB::table('classifier_type')
            ->where('code', 'TIT')
            ->update(['display_order' => 2]);
            
        // TITEN (Title English) already has display_order = 3, so no change needed
        // Just ensure it's correct
        DB::table('classifier_type')
            ->where('code', 'TITEN')
            ->update(['display_order' => 3]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original order
        DB::table('classifier_type')
            ->where('code', 'TIT')
            ->update(['display_order' => 1]);
            
        DB::table('classifier_type')
            ->where('code', 'TITOF')
            ->update(['display_order' => 2]);
            
        DB::table('classifier_type')
            ->where('code', 'TITEN')
            ->update(['display_order' => 3]);
    }
};
