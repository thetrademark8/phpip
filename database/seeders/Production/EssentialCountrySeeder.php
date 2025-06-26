<?php

namespace Database\Seeders\Production;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EssentialCountrySeeder extends Seeder
{
    /**
     * Seed essential countries for production use.
     * Includes major jurisdictions for patents and trademarks.
     */
    public function run()
    {
        $countries = [
            // Major patent/trademark offices
            ['iso' => 'US', 'iso3' => 'USA', 'name' => 'United States', 'name_FR' => 'États-Unis', 'name_DE' => 'Vereinigte Staaten'],
            ['iso' => 'EP', 'iso3' => 'EPO', 'name' => 'European Patent Office', 'name_FR' => 'Office européen des brevets', 'name_DE' => 'Europäisches Patentamt'],
            ['iso' => 'WO', 'iso3' => 'WO', 'name' => 'WIPO', 'name_FR' => 'OMPI', 'name_DE' => 'WIPO'],
            ['iso' => 'EM', 'iso3' => 'EM', 'name' => 'EUIPO', 'name_FR' => 'EUIPO', 'name_DE' => 'EUIPO'],

            // European countries
            ['iso' => 'FR', 'iso3' => 'FRA', 'name' => 'France', 'name_FR' => 'France', 'name_DE' => 'Frankreich'],
            ['iso' => 'DE', 'iso3' => 'DEU', 'name' => 'Germany', 'name_FR' => 'Allemagne', 'name_DE' => 'Deutschland'],
            ['iso' => 'GB', 'iso3' => 'GBR', 'name' => 'United Kingdom', 'name_FR' => 'Royaume-Uni', 'name_DE' => 'Vereinigtes Königreich'],
            ['iso' => 'IT', 'iso3' => 'ITA', 'name' => 'Italy', 'name_FR' => 'Italie', 'name_DE' => 'Italien'],
            ['iso' => 'ES', 'iso3' => 'ESP', 'name' => 'Spain', 'name_FR' => 'Espagne', 'name_DE' => 'Spanien'],
            ['iso' => 'NL', 'iso3' => 'NLD', 'name' => 'Netherlands', 'name_FR' => 'Pays-Bas', 'name_DE' => 'Niederlande'],
            ['iso' => 'BE', 'iso3' => 'BEL', 'name' => 'Belgium', 'name_FR' => 'Belgique', 'name_DE' => 'Belgien'],
            ['iso' => 'CH', 'iso3' => 'CHE', 'name' => 'Switzerland', 'name_FR' => 'Suisse', 'name_DE' => 'Schweiz'],
            ['iso' => 'AT', 'iso3' => 'AUT', 'name' => 'Austria', 'name_FR' => 'Autriche', 'name_DE' => 'Österreich'],
            ['iso' => 'SE', 'iso3' => 'SWE', 'name' => 'Sweden', 'name_FR' => 'Suède', 'name_DE' => 'Schweden'],

            // Major Asian markets
            ['iso' => 'JP', 'iso3' => 'JPN', 'name' => 'Japan', 'name_FR' => 'Japon', 'name_DE' => 'Japan'],
            ['iso' => 'CN', 'iso3' => 'CHN', 'name' => 'China', 'name_FR' => 'Chine', 'name_DE' => 'China'],
            ['iso' => 'KR', 'iso3' => 'KOR', 'name' => 'South Korea', 'name_FR' => 'Corée du Sud', 'name_DE' => 'Südkorea'],
            ['iso' => 'IN', 'iso3' => 'IND', 'name' => 'India', 'name_FR' => 'Inde', 'name_DE' => 'Indien'],

            // Other major markets
            ['iso' => 'CA', 'iso3' => 'CAN', 'name' => 'Canada', 'name_FR' => 'Canada', 'name_DE' => 'Kanada'],
            ['iso' => 'AU', 'iso3' => 'AUS', 'name' => 'Australia', 'name_FR' => 'Australie', 'name_DE' => 'Australien'],
            ['iso' => 'BR', 'iso3' => 'BRA', 'name' => 'Brazil', 'name_FR' => 'Brésil', 'name_DE' => 'Brasilien'],
            ['iso' => 'MX', 'iso3' => 'MEX', 'name' => 'Mexico', 'name_FR' => 'Mexique', 'name_DE' => 'Mexiko'],
        ];

        DB::table('country')->insertOrIgnore($countries);
    }
}
