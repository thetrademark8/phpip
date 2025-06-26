<?php

namespace Database\Seeders\Production;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EssentialClassifierTypeSeeder extends Seeder
{
    /**
     * Seed essential classifier types for production use.
     */
    public function run()
    {
        $classifierTypes = [
            // Title classifiers
            ['code' => 'TIT', 'type' => '{"en": "Title", "fr": "Titre"}', 'main_display' => 1, 'for_category' => null, 'display_order' => 1],
            ['code' => 'TITOF', 'type' => '{"en": "Official Title", "fr": "Titre officiel"}', 'main_display' => 1, 'for_category' => null, 'display_order' => 2],
            ['code' => 'TITEN', 'type' => '{"en": "Title (English)", "fr": "Titre (anglais)"}', 'main_display' => 1, 'for_category' => null, 'display_order' => 3],
            ['code' => 'TITFR', 'type' => '{"en": "Title (French)", "fr": "Titre (français)"}', 'main_display' => 1, 'for_category' => null, 'display_order' => 4],
            
            // Description classifiers
            ['code' => 'DESCR', 'type' => '{"en": "Description", "fr": "Description"}', 'main_display' => 0, 'for_category' => null, 'display_order' => 10],
            ['code' => 'ABSTR', 'type' => '{"en": "Abstract", "fr": "Abrégé"}', 'main_display' => 0, 'for_category' => null, 'display_order' => 11],
            
            // Patent classification
            ['code' => 'IPC', 'type' => '{"en": "IPC", "fr": "CIB"}', 'main_display' => 0, 'for_category' => 'PAT', 'display_order' => 20],
            ['code' => 'CPC', 'type' => '{"en": "CPC", "fr": "CPC"}', 'main_display' => 0, 'for_category' => 'PAT', 'display_order' => 21],
            ['code' => 'USPC', 'type' => '{"en": "US Class", "fr": "Classe US"}', 'main_display' => 0, 'for_category' => 'PAT', 'display_order' => 22],
            
            // Trademark classification
            ['code' => 'NICE', 'type' => '{"en": "Nice Class", "fr": "Classe de Nice"}', 'main_display' => 0, 'for_category' => 'TM', 'display_order' => 30],
            ['code' => 'GOODS', 'type' => '{"en": "Goods/Services", "fr": "Produits/Services"}', 'main_display' => 0, 'for_category' => 'TM', 'display_order' => 31],
            
            // Image/Logo classifiers
            ['code' => 'IMG', 'type' => '{"en": "Image", "fr": "Image"}', 'main_display' => 0, 'for_category' => null, 'display_order' => 40],
            ['code' => 'LOGO', 'type' => '{"en": "Logo", "fr": "Logo"}', 'main_display' => 0, 'for_category' => 'TM', 'display_order' => 41],
            
            // Design classification
            ['code' => 'LOCARNO', 'type' => '{"en": "Locarno Class", "fr": "Classe de Locarno"}', 'main_display' => 0, 'for_category' => 'DES', 'display_order' => 50],
            
            // References
            ['code' => 'REF', 'type' => '{"en": "Reference", "fr": "Référence"}', 'main_display' => 0, 'for_category' => null, 'display_order' => 60],
            ['code' => 'LINK', 'type' => '{"en": "Link", "fr": "Lien"}', 'main_display' => 0, 'for_category' => null, 'display_order' => 61],
        ];
        
        DB::table('classifier_type')->insertOrIgnore($classifierTypes);
    }
}