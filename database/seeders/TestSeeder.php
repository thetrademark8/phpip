<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\Testing\MinimalDataSeeder;

class TestSeeder extends Seeder
{
    /**
     * Test seeder for automated testing.
     * Creates minimal data required for tests to run.
     * Each test should create its own specific test data using factories.
     */
    public function run()
    {
        $this->command->info('Starting test seeding with minimal data...');
        
        // Only seed the absolute minimum required for tests
        $this->call(MinimalDataSeeder::class);
        
        $this->command->info('Test seeding completed successfully!');
    }
}