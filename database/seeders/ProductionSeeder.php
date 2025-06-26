<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    /**
     * Production seeder containing only essential data required for the application to function.
     * This seeder should be run only once during initial production deployment.
     */
    public function run()
    {
        $this->command->info('Starting production seeding with essential data only...');

        // Essential reference data
        $this->call(Production\EssentialCountrySeeder::class);
        $this->call(Production\FullCountrySeeder::class); // Full country list
        $this->call(Production\EssentialRoleSeeder::class);
        $this->call(Production\EssentialCategorySeeder::class);
        $this->call(Production\EssentialEventSeeder::class);
        $this->call(Production\EssentialClassifierTypeSeeder::class);
        $this->call(Production\EssentialMatterTypeSeeder::class);

        // Additional production data
        $this->call(Production\DefaultActorsSeeder::class); // Default system actors
        $this->call(Production\FeesTableSeeder::class); // Fee schedules
        $this->call(Production\TemplateClassesTableSeeder::class); // Document templates
        $this->call(Production\TemplateMembersTableSeeder::class); // Template components
        $this->call(Production\TranslatedAttributesSeeder::class); // Translations

        // Admin user
        $this->call(Production\AdminUserSeeder::class);

        $this->command->info('Production seeding completed successfully!');
    }
}
