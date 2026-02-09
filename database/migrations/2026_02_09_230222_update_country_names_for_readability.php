<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Update country names for better readability.
     */
    public function up(): void
    {
        // OAPI - Organisation Africaine de la Propriété Intellectuelle
        DB::table('country')->where('iso', 'OA')->update([
            'name' => json_encode([
                'en' => 'OAPI',
                'fr' => 'OAPI',
                'de' => 'OAPI',
            ]),
        ]);

        // EUIPO - Office de l'Union Européenne pour la propriété intellectuelle
        DB::table('country')->where('iso', 'EM')->update([
            'name' => json_encode([
                'en' => 'EUIPO',
                'fr' => 'EUIPO',
                'de' => 'EUIPO',
            ]),
        ]);

        // OMPI - Organisation Mondiale de la propriété intellectuelle
        DB::table('country')->where('iso', 'WO')->update([
            'name' => json_encode([
                'en' => 'WIPO',
                'fr' => 'OMPI',
                'de' => 'WIPO',
            ]),
        ]);
    }

    /**
     * Reverse the migrations.
     * Restore original country names.
     */
    public function down(): void
    {
        // OAPI
        DB::table('country')->where('iso', 'OA')->update([
            'name' => json_encode([
                'en' => 'African Intellectual Property Organization',
                'fr' => 'Organisation Africaine de la Propriété Intellectuelle',
                'de' => 'Afrikanische Organisation für geistiges Eigentum',
            ]),
        ]);

        // EUIPO
        DB::table('country')->where('iso', 'EM')->update([
            'name' => json_encode([
                'en' => 'European Union Intellectual Property Office',
                'fr' => 'Office de l\'Union européenne pour la propriété intellectuelle',
                'de' => 'Amt der Europäischen Union für geistiges Eigentum',
            ]),
        ]);

        // OMPI/WIPO
        DB::table('country')->where('iso', 'WO')->update([
            'name' => json_encode([
                'en' => 'World Intellectual Property Organization',
                'fr' => 'Organisation Mondiale de la Propriété Intellectuelle',
                'de' => 'Weltorganisation für geistiges Eigentum',
            ]),
        ]);
    }
};
