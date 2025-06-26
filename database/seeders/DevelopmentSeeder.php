<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DevelopmentSeeder extends Seeder
{
    /**
     * Development seeder for creating sample data using factories.
     * Includes all production data plus sample matters, actors, and related data.
     */
    public function run()
    {
        $this->command->info('Starting development seeding...');
        
        // First, run all production seeders
        $this->call(ProductionSeeder::class);
        
        // Then add development-specific sample data
        $this->call(Development\SampleActorsSeeder::class);
        $this->call(Development\SampleMattersSeeder::class);
        
        // Skip events, tasks, and classifiers for now to keep seeding simple
        $this->command->warn('Note: Sample events, tasks, and classifiers have been skipped to keep the development data minimal.');
        $this->command->warn('You can uncomment the seeders below if you need more comprehensive test data.');
        
        // Uncomment these when trigger issues are resolved:
        $this->call(Development\SampleEventsSeeder::class);
        $this->call(Development\SampleTasksSeeder::class);
        $this->call(Development\SampleClassifiersSeeder::class);
        
        $this->command->info('Development seeding completed successfully!');
    }
}