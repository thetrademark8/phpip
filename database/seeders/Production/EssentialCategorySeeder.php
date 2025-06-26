<?php

namespace Database\Seeders\Production;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EssentialCategorySeeder extends Seeder
{
    /**
     * Seed essential matter categories for production use.
     */
    public function run()
    {
        $categories = [
            [
                'code' => 'PAT',
                'ref_prefix' => 'P',
                'category' => '{"en": "Patent", "fr": "Brevet"}',
                'display_with' => 'PAT'
            ],
            [
                'code' => 'TM',
                'ref_prefix' => 'T',
                'category' => '{"en": "Trademark", "fr": "Marque"}',
                'display_with' => 'TM'
            ],
            [
                'code' => 'DES',
                'ref_prefix' => 'D',
                'category' => '{"en": "Design", "fr": "Dessin et modèle"}',
                'display_with' => 'PAT'
            ],
            [
                'code' => 'UM',
                'ref_prefix' => 'U',
                'category' => '{"en": "Utility Model", "fr": "Modèle d\'utilité"}',
                'display_with' => 'PAT'
            ],
            [
                'code' => 'COP',
                'ref_prefix' => 'C',
                'category' => '{"en": "Copyright", "fr": "Droit d\'auteur"}',
                'display_with' => null
            ],
            [
                'code' => 'DOM',
                'ref_prefix' => 'N',
                'category' => '{"en": "Domain Name", "fr": "Nom de domaine"}',
                'display_with' => null
            ],
        ];
        
        DB::table('matter_category')->insertOrIgnore($categories);
    }
}