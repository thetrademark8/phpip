<?php

namespace Tests;

use Database\Seeders\Testing\MinimalDataSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected $dropViews = true;

    // `LazilyRefreshDatabase` calls `migrate:fresh` between tests, so any seed
    // applied once before the suite would vanish. Seeding the FK-reference
    // tables (actor_role, classifier_type, event_name, …) is required for
    // factories like `UserFactory` that hit those constraints.
    protected $seed = true;

    protected $seeder = MinimalDataSeeder::class;

    public function resetDatabase()
    {

        $this->artisan('migrate:rollback');
        $this->artisan('migrate');
        $this->artisan('db:seed');
    }

    public function resetDatabaseAndSeed()
    {
        $this->resetDatabase();
        $this->artisan('db:seed --class=SampleSeeder');
    }
}
