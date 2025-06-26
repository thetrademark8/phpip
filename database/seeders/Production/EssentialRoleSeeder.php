<?php

namespace Database\Seeders\Production;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EssentialRoleSeeder extends Seeder
{
    /**
     * Seed essential actor roles for production use.
     */
    public function run()
    {
        $roles = [
            ['code' => 'APP', 'name' => '{"en": "Applicant", "fr": "Déposant"}', 'display_order' => 1, 'shareable' => 1, 'show_ref' => 0, 'show_company' => 0, 'show_rate' => 0, 'show_date' => 0],
            ['code' => 'AGT', 'name' => '{"en": "Primary Agent", "fr": "Agent principal"}', 'display_order' => 10, 'shareable' => 1, 'show_ref' => 1, 'show_company' => 0, 'show_rate' => 0, 'show_date' => 0],
            ['code' => 'AGT2', 'name' => '{"en": "Secondary Agent", "fr": "Agent secondaire"}', 'display_order' => 11, 'shareable' => 1, 'show_ref' => 1, 'show_company' => 0, 'show_rate' => 0, 'show_date' => 0],
            ['code' => 'ANN', 'name' => '{"en": "Annuity Agent", "fr": "Agent annuités"}', 'display_order' => 21, 'shareable' => 1, 'show_ref' => 1, 'show_company' => 0, 'show_rate' => 0, 'show_date' => 0],
            ['code' => 'CLI', 'name' => '{"en": "Client", "fr": "Client"}', 'display_order' => 1, 'shareable' => 1, 'show_ref' => 1, 'show_company' => 0, 'show_rate' => 1, 'show_date' => 1],
            ['code' => 'CNT', 'name' => '{"en": "Contact", "fr": "Contact"}', 'display_order' => 30, 'shareable' => 0, 'show_ref' => 0, 'show_company' => 0, 'show_rate' => 0, 'show_date' => 0],
            ['code' => 'DEL', 'name' => '{"en": "Delegate", "fr": "Délégué"}', 'display_order' => 31, 'shareable' => 0, 'show_ref' => 0, 'show_company' => 0, 'show_rate' => 0, 'show_date' => 0],
            ['code' => 'INV', 'name' => '{"en": "Inventor", "fr": "Inventeur"}', 'display_order' => 1, 'shareable' => 1, 'show_ref' => 0, 'show_company' => 0, 'show_rate' => 0, 'show_date' => 0],
            ['code' => 'LCN', 'name' => '{"en": "Licensee", "fr": "Licencié"}', 'display_order' => 20, 'shareable' => 0, 'show_ref' => 0, 'show_company' => 0, 'show_rate' => 0, 'show_date' => 0],
            ['code' => 'OFF', 'name' => '{"en": "Patent Office", "fr": "Office des brevets"}', 'display_order' => 0, 'shareable' => 0, 'show_ref' => 0, 'show_company' => 0, 'show_rate' => 0, 'show_date' => 0],
            ['code' => 'OPP', 'name' => '{"en": "Opponent", "fr": "Opposant"}', 'display_order' => 12, 'shareable' => 0, 'show_ref' => 1, 'show_company' => 0, 'show_rate' => 0, 'show_date' => 0],
            ['code' => 'OWN', 'name' => '{"en": "Owner", "fr": "Propriétaire"}', 'display_order' => 2, 'shareable' => 1, 'show_ref' => 0, 'show_company' => 1, 'show_rate' => 0, 'show_date' => 1],
            ['code' => 'PAY', 'name' => '{"en": "Payor", "fr": "Payeur"}', 'display_order' => 3, 'shareable' => 1, 'show_ref' => 1, 'show_company' => 0, 'show_rate' => 1, 'show_date' => 0],
            ['code' => 'WRT', 'name' => '{"en": "Writer", "fr": "Rédacteur"}', 'display_order' => 4, 'shareable' => 1, 'show_ref' => 0, 'show_company' => 0, 'show_rate' => 1, 'show_date' => 0],
            
            // System roles
            ['code' => 'DBA', 'name' => '{"en": "DB Admin", "fr": "Admin BD"}', 'display_order' => 127, 'shareable' => 0, 'show_ref' => 0, 'show_company' => 0, 'show_rate' => 0, 'show_date' => 0],
        ];
        
        DB::table('actor_role')->insertOrIgnore($roles);
    }
}