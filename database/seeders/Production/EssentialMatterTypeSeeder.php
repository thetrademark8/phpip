<?php

namespace Database\Seeders\Production;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EssentialMatterTypeSeeder extends Seeder
{
    /**
     * Seed essential matter types for production use.
     */
    public function run()
    {
        $matterTypes = [
            // Patent types
            ['code' => 'APP', 'type' => '{"en": "Application", "fr": "Demande"}'],
            ['code' => 'PRO', 'type' => '{"en": "Provisional", "fr": "Provisoire"}'],
            ['code' => 'DIV', 'type' => '{"en": "Divisional", "fr": "Divisionnaire"}'],
            ['code' => 'CON', 'type' => '{"en": "Continuation", "fr": "Continuation"}'],
            ['code' => 'CIP', 'type' => '{"en": "Continuation in Part", "fr": "Continuation partielle"}'],
            ['code' => 'REI', 'type' => '{"en": "Reissue", "fr": "Réédition"}'],
            ['code' => 'PCT', 'type' => '{"en": "PCT Application", "fr": "Demande PCT"}'],
            ['code' => 'NAT', 'type' => '{"en": "National Phase", "fr": "Phase nationale"}'],
            
            // Trademark types
            ['code' => 'WORD', 'type' => '{"en": "Word Mark", "fr": "Marque verbale"}'],
            ['code' => 'FIG', 'type' => '{"en": "Figurative Mark", "fr": "Marque figurative"}'],
            ['code' => 'COMB', 'type' => '{"en": "Combined Mark", "fr": "Marque combinée"}'],
            ['code' => '3D', 'type' => '{"en": "3D Mark", "fr": "Marque 3D"}'],
            ['code' => 'SOUND', 'type' => '{"en": "Sound Mark", "fr": "Marque sonore"}'],
            ['code' => 'COLOR', 'type' => '{"en": "Color Mark", "fr": "Marque de couleur"}'],
            ['code' => 'MADRID', 'type' => '{"en": "Madrid Application", "fr": "Demande Madrid"}'],
            
            // Design types
            ['code' => 'DES', 'type' => '{"en": "Design Application", "fr": "Demande de dessin"}'],
            ['code' => 'HAGUE', 'type' => '{"en": "Hague Application", "fr": "Demande de La Haye"}'],
            
            // Opposition/Litigation
            ['code' => 'OPP', 'type' => '{"en": "Opposition", "fr": "Opposition"}'],
            ['code' => 'LIT', 'type' => '{"en": "Litigation", "fr": "Litige"}'],
            ['code' => 'CANC', 'type' => '{"en": "Cancellation", "fr": "Annulation"}'],
        ];
        
        DB::table('matter_type')->insertOrIgnore($matterTypes);
    }
}