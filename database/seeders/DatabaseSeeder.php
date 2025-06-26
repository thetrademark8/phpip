<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Default to development seeding for backwards compatibility
        // Production environments should explicitly use ProductionSeeder
        $this->call(DevelopmentSeeder::class);
    }
}
