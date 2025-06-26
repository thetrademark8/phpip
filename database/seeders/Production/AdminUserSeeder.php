<?php

namespace Database\Seeders\Production;

use App\Models\Actor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Create the default admin user for production.
     * This should be run only once during initial deployment.
     * The admin should change the password immediately after first login.
     */
    public function run()
    {
        // Check if admin user already exists
        if (Actor::where('login', 'admin')->exists()) {
            $this->command->info('Admin user already exists, skipping...');

            return;
        }

        // Create or update the admin actor
        // Note: 'users' is a view based on the actor table where login IS NOT NULL
        Actor::updateOrCreate(
            ['login' => 'admin'],
            [
                'name' => 'System Administrator',
                'first_name' => 'Admin',
                'display_name' => 'Admin',
                'password' => Hash::make('changeme'),
                'default_role' => 'DBA',
                'function' => 'System Administrator',
                'company_id' => null,
                'email' => 'admin@example.com',
                'phone' => null,
                'address' => null,
                'country' => null,
                'phy_person' => true,
                'small_entity' => false,
            ]
        );

        $this->command->warn('Admin user created with login: admin');
        $this->command->warn('Default password: changeme');
        $this->command->warn('IMPORTANT: Change this password immediately after first login!');
    }
}
