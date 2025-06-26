<?php

namespace Database\Seeders\Development;

use Illuminate\Database\Seeder;
use App\Models\Actor;
use Illuminate\Support\Facades\Hash;

class SampleActorsSeeder extends Seeder
{
    /**
     * Create sample actors for development environment.
     */
    public function run()
    {
        $this->command->info('Creating sample actors...');
        
        // Create sample companies
        $techCorp = Actor::updateOrCreate(
            ['display_name' => 'TechCorp'],
            array_merge(
                Actor::factory()->company()->make()->toArray(),
                [
                    'name' => 'TechCorp International',
                    'address' => "123 Innovation Drive\nSilicon Valley, CA 94025",
                    'country' => 'US',
                    'email' => 'ip@techcorp.com',
                    'legal_form' => 'Inc.',
                ]
            )
        );
        
        $euroTech = Actor::updateOrCreate(
            ['display_name' => 'EuroTech'],
            array_merge(
                Actor::factory()->company()->make()->toArray(),
                [
                    'name' => 'EuroTech Solutions GmbH',
                    'address' => "Hauptstraße 45\n80331 München",
                    'country' => 'DE',
                    'email' => 'patents@eurotech.de',
                    'legal_form' => 'GmbH',
                ]
            )
        );
        
        $fashionHouse = Actor::updateOrCreate(
            ['display_name' => 'Fashion House'],
            array_merge(
                Actor::factory()->company()->make()->toArray(),
                [
                    'name' => 'Fashion House Paris SA',
                    'address' => "10 Rue de la Mode\n75001 Paris",
                    'country' => 'FR',
                    'email' => 'trademarks@fashionhouse.fr',
                    'legal_form' => 'SA',
                ]
            )
        );
        
        // Create sample patent firms
        $usPatentFirm = Actor::updateOrCreate(
            ['display_name' => 'Smith & Jones'],
            array_merge(
                Actor::factory()->company()->make()->toArray(),
                [
                    'name' => 'Smith & Jones Patent Law',
                    'address' => "500 Patent Plaza\nAlexandria, VA 22314",
                    'country' => 'US',
                    'email' => 'docketing@smithjones.com',
                    'default_role' => 'AGT',
                ]
            )
        );
        
        $euPatentFirm = Actor::updateOrCreate(
            ['display_name' => 'EU IP Partners'],
            array_merge(
                Actor::factory()->company()->make()->toArray(),
                [
                    'name' => 'European IP Partners',
                    'address' => "Bayerstraße 20\n80335 München",
                    'country' => 'DE',
                    'email' => 'info@euip.com',
                    'default_role' => 'AGT',
                ]
            )
        );
        
        // Create sample inventors
        $inventors = [
            Actor::updateOrCreate(
                ['email' => 'alice.johnson@techcorp.com'],
                array_merge(
                    Actor::factory()->person()->make()->toArray(),
                    [
                        'name' => 'Johnson',
                        'first_name' => 'Alice',
                        'company_id' => $techCorp->id,
                        'default_role' => 'INV',
                    ]
                )
            ),
            Actor::updateOrCreate(
                ['email' => 'bob.smith@techcorp.com'],
                array_merge(
                    Actor::factory()->person()->make()->toArray(),
                    [
                        'name' => 'Smith',
                        'first_name' => 'Bob',
                        'company_id' => $techCorp->id,
                        'default_role' => 'INV',
                    ]
                )
            ),
            Actor::updateOrCreate(
                ['email' => 'klaus.schmidt@eurotech.de'],
                array_merge(
                    Actor::factory()->person()->make()->toArray(),
                    [
                        'name' => 'Schmidt',
                        'first_name' => 'Klaus',
                        'company_id' => $euroTech->id,
                        'default_role' => 'INV',
                    ]
                )
            ),
        ];
        
        // Create sample users (who can log in)
        $this->createUserActor('jdoe', 'John', 'Doe', 'jdoe@example.com', 'password');
        $this->createUserActor('msmith', 'Mary', 'Smith', 'msmith@example.com', 'password');
        $this->createUserActor('pmartin', 'Pierre', 'Martin', 'pmartin@example.com', 'password', 'fr');
        
        $this->command->info('Sample actors created successfully!');
    }
    
    /**
     * Create a user actor (can log into the system).
     */
    private function createUserActor($login, $firstName, $lastName, $email, $password, $language = 'en')
    {
        // Create actor with login - this will automatically appear in the users view
        $actor = Actor::updateOrCreate(
            ['login' => $login],
            array_merge(
                Actor::factory()->person()->make()->toArray(),
                [
                    'name' => $lastName,
                    'first_name' => $firstName,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'language' => $language,
                ]
            )
        );
        
        return $actor;
    }
}