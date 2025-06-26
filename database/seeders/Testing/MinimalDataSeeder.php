<?php

namespace Database\Seeders\Testing;

use App\Models\Actor;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MinimalDataSeeder extends Seeder
{
    /**
     * Seed minimal data required for tests.
     * Most tests should create their own test data using factories.
     */
    public function run()
    {
        // Essential countries for tests
        $this->seedEssentialCountries();

        // Essential roles
        $this->seedEssentialRoles();

        // Essential categories
        $this->seedEssentialCategories();

        // Essential events
        $this->seedEssentialEvents();

        // Essential classifier types
        $this->seedEssentialClassifierTypes();

        // Test user
        $this->seedTestUser();
    }

    /**
     * Seed only the most essential countries for testing.
     */
    private function seedEssentialCountries()
    {
        $countries = [
            ['iso' => 'US', 'iso3' => 'USA', 'name' => 'United States', 'name_FR' => 'États-Unis', 'name_DE' => 'Vereinigte Staaten'],
            ['iso' => 'EP', 'iso3' => 'EPO', 'name' => 'European Patent Office', 'name_FR' => 'Office européen des brevets', 'name_DE' => 'Europäisches Patentamt'],
            ['iso' => 'FR', 'iso3' => 'FRA', 'name' => 'France', 'name_FR' => 'France', 'name_DE' => 'Frankreich'],
            ['iso' => 'DE', 'iso3' => 'DEU', 'name' => 'Germany', 'name_FR' => 'Allemagne', 'name_DE' => 'Deutschland'],
            ['iso' => 'WO', 'iso3' => 'WO', 'name' => 'WIPO', 'name_FR' => 'OMPI', 'name_DE' => 'WIPO'],
            ['iso' => 'EM', 'iso3' => 'EM', 'name' => 'EUIPO', 'name_FR' => 'EUIPO', 'name_DE' => 'EUIPO'],
        ];

        DB::table('country')->insertOrIgnore($countries);
    }

    /**
     * Seed only the most essential roles for testing.
     */
    private function seedEssentialRoles()
    {
        $roles = [
            ['code' => 'APP', 'name' => '{"en": "Applicant", "fr": "Déposant"}', 'display_order' => 1, 'shareable' => 1, 'show_ref' => 0, 'show_company' => 0, 'show_rate' => 0, 'show_date' => 0],
            ['code' => 'CLI', 'name' => '{"en": "Client", "fr": "Client"}', 'display_order' => 1, 'shareable' => 1, 'show_ref' => 1, 'show_company' => 0, 'show_rate' => 1, 'show_date' => 1],
            ['code' => 'AGT', 'name' => '{"en": "Agent", "fr": "Agent"}', 'display_order' => 10, 'shareable' => 1, 'show_ref' => 1, 'show_company' => 0, 'show_rate' => 0, 'show_date' => 0],
            ['code' => 'INV', 'name' => '{"en": "Inventor", "fr": "Inventeur"}', 'display_order' => 1, 'shareable' => 1, 'show_ref' => 0, 'show_company' => 0, 'show_rate' => 0, 'show_date' => 0],
            ['code' => 'OWN', 'name' => '{"en": "Owner", "fr": "Propriétaire"}', 'display_order' => 2, 'shareable' => 1, 'show_ref' => 0, 'show_company' => 1, 'show_rate' => 0, 'show_date' => 1],
        ];

        DB::table('actor_role')->insertOrIgnore($roles);
    }

    /**
     * Seed only the most essential categories for testing.
     */
    private function seedEssentialCategories()
    {
        $categories = [
            ['code' => 'PAT', 'ref_prefix' => 'P', 'category' => '{"en": "Patent", "fr": "Brevet"}', 'display_with' => 'PAT'],
            ['code' => 'TM', 'ref_prefix' => 'T', 'category' => '{"en": "Trademark", "fr": "Marque"}', 'display_with' => 'TM'],
            ['code' => 'DES', 'ref_prefix' => 'D', 'category' => '{"en": "Design", "fr": "Dessin et modèle"}', 'display_with' => 'PAT'],
        ];

        DB::table('matter_category')->insertOrIgnore($categories);
    }

    /**
     * Seed only the most essential events for testing.
     */
    private function seedEssentialEvents()
    {
        $events = [
            ['code' => 'CRE', 'name' => '{"en": "Created", "fr": "Créé"}', 'is_task' => 0, 'status_event' => 0, 'use_matter_resp' => 0, 'unique' => 1],
            ['code' => 'FIL', 'name' => '{"en": "Filed", "fr": "Déposé"}', 'is_task' => 0, 'status_event' => 1, 'use_matter_resp' => 0, 'unique' => 1],
            ['code' => 'PUB', 'name' => '{"en": "Published", "fr": "Publié"}', 'is_task' => 0, 'status_event' => 1, 'use_matter_resp' => 0, 'unique' => 1],
            ['code' => 'GRT', 'name' => '{"en": "Granted", "fr": "Délivré"}', 'is_task' => 0, 'status_event' => 1, 'use_matter_resp' => 0, 'unique' => 1],
            ['code' => 'REG', 'name' => '{"en": "Registered", "fr": "Enregistré"}', 'is_task' => 0, 'status_event' => 1, 'use_matter_resp' => 0, 'unique' => 1],
            ['code' => 'REN', 'name' => '{"en": "Renewal", "fr": "Annuité"}', 'is_task' => 1, 'status_event' => 0, 'use_matter_resp' => 0, 'unique' => 0],
            ['code' => 'PRI', 'name' => '{"en": "Priority Claimed", "fr": "Priorité revendiquée"}', 'is_task' => 0, 'status_event' => 0, 'use_matter_resp' => 0, 'unique' => 0],
        ];

        DB::table('event_name')->insertOrIgnore($events);
    }

    /**
     * Seed only the most essential classifier types for testing.
     */
    private function seedEssentialClassifierTypes()
    {
        $classifierTypes = [
            ['code' => 'TIT', 'type' => '{"en": "Title", "fr": "Titre"}', 'main_display' => 1, 'for_category' => null, 'display_order' => 1],
            ['code' => 'TITOF', 'type' => '{"en": "Official Title", "fr": "Titre officiel"}', 'main_display' => 1, 'for_category' => null, 'display_order' => 2],
            ['code' => 'IPC', 'type' => '{"en": "IPC", "fr": "CIB"}', 'main_display' => 0, 'for_category' => 'PAT', 'display_order' => 20],
            ['code' => 'NICE', 'type' => '{"en": "Nice Class", "fr": "Classe de Nice"}', 'main_display' => 0, 'for_category' => 'TM', 'display_order' => 30],
        ];

        DB::table('classifier_type')->insertOrIgnore($classifierTypes);
    }

    /**
     * Create a test user for authentication tests.
     */
    private function seedTestUser()
    {
        // Create or update test actor
        // Note: 'users' is a view based on the actor table where login IS NOT NULL
        Actor::updateOrCreate(
            ['login' => 'testuser'],
            [
                'name' => 'Test',
                'first_name' => 'User',
                'display_name' => 'Test User',
                'password' => Hash::make('password'),
                'email' => 'test@example.com',
                'phy_person' => true,
            ]
        );
    }
}
