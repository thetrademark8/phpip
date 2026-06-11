<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Madrid Protocol members missing from the 2025_11_26 migration
     * Source: WIPO - https://www.wipo.int/en/web/madrid-system/members/index
     */
    private array $missingMadridProtocolCountries = [
        'CL', // Chile (member since 2022)
        'CV', // Cabo Verde (member since 2022)
        'JM', // Jamaica (member since 2022)
        'BZ', // Belize (member since 2023)
        'QA', // Qatar (member since 2024)
        'GD', // Grenada (member since 2026)
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('country')
            ->whereIn('iso', $this->missingMadridProtocolCountries)
            ->update(['wo' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('country')
            ->whereIn('iso', $this->missingMadridProtocolCountries)
            ->update(['wo' => 0]);
    }
};
