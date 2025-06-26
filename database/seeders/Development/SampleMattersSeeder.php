<?php

namespace Database\Seeders\Development;

use App\Models\Actor;
use App\Models\ActorPivot;
use App\Models\Matter;
use Illuminate\Database\Seeder;

class SampleMattersSeeder extends Seeder
{
    /**
     * Create sample matters for development environment.
     */
    public function run()
    {
        $this->command->info('Creating sample matters...');

        // Get some actors to work with
        $techCorp = Actor::where('name', 'TechCorp International')->first();
        $euroTech = Actor::where('name', 'EuroTech Solutions GmbH')->first();
        $fashionHouse = Actor::where('name', 'Fashion House Paris SA')->first();
        $usAgent = Actor::where('name', 'Smith & Jones Patent Law')->first();
        $euAgent = Actor::where('name', 'European IP Partners')->first();
        $jdoe = Actor::where('login', 'jdoe')->first();

        // Create patent family for TechCorp
        $this->createPatentFamily($techCorp, $usAgent, 'AI-VISION', 'Advanced AI Vision System');

        // Create patent family for EuroTech
        $this->createPatentFamily($euroTech, $euAgent, 'QUANTUM', 'Quantum Computing Interface');

        // Create trademark portfolio for Fashion House
        $this->createTrademarkPortfolio($fashionHouse, $euAgent);

        // Create some standalone patents
        $this->createStandalonePatents();

        $this->command->info('Sample matters created successfully!');
    }

    /**
     * Create a patent family with PCT and national phases.
     */
    private function createPatentFamily($client, $agent, $ref, $title)
    {
        // Create PCT application
        $pctData = array_merge(
            Matter::factory()->pct()->make()->toArray(),
            [
                'caseref' => $ref,
                'responsible' => 'jdoe',
            ]
        );

        // The unique constraint is on caseref + country + origin
        $pct = Matter::updateOrCreate(
            [
                'caseref' => $ref,
                'country' => 'WO',
                'origin' => 'WO',
            ],
            $pctData
        );

        // Link actors (using updateOrCreate to avoid duplicates)
        ActorPivot::updateOrCreate(
            ['matter_id' => $pct->id, 'actor_id' => $client->id, 'role' => 'CLI'],
            ['display_order' => 1]
        );
        ActorPivot::updateOrCreate(
            ['matter_id' => $pct->id, 'actor_id' => $client->id, 'role' => 'APP'],
            ['display_order' => 1]
        );
        ActorPivot::updateOrCreate(
            ['matter_id' => $pct->id, 'actor_id' => $agent->id, 'role' => 'AGT'],
            ['display_order' => 1]
        );

        // Create national phase entries
        $countries = ['US', 'EP', 'JP', 'CN'];
        foreach ($countries as $country) {
            $nationalPhaseData = array_merge(
                Matter::factory()->patent()->make()->toArray(),
                [
                    'caseref' => $ref,
                    'country' => $country,
                    'parent_id' => $pct->id,
                    'origin' => 'WO',
                    'type_code' => 'NAT',
                    'responsible' => 'jdoe',
                ]
            );

            $nationalPhase = Matter::updateOrCreate(
                [
                    'caseref' => $ref,
                    'country' => $country,
                    'origin' => 'WO',
                ],
                $nationalPhaseData
            );

            // Actors are inherited from parent, but we can add country-specific agents
            if ($country === 'US') {
                ActorPivot::updateOrCreate(
                    ['matter_id' => $nationalPhase->id, 'actor_id' => $agent->id, 'role' => 'AGT'],
                    ['display_order' => 1]
                );
            }
        }

        return $pct;
    }

    /**
     * Create a trademark portfolio.
     */
    private function createTrademarkPortfolio($client, $agent)
    {
        $marks = [
            ['name' => 'LUXURIA', 'classes' => [3, 18, 25]],
            ['name' => 'ELEGANZA', 'classes' => [14, 18, 25]],
            ['name' => 'FASHION FORWARD', 'classes' => [25, 35]],
        ];

        foreach ($marks as $mark) {
            $caseref = strtoupper(str_replace(' ', '', $mark['name']));

            // Create EU trademark
            $eutmData = array_merge(
                Matter::factory()->trademark()->make()->toArray(),
                [
                    'caseref' => $caseref,
                    'country' => 'EM',
                    'responsible' => 'msmith',
                ]
            );

            $eutm = Matter::updateOrCreate(
                [
                    'caseref' => $caseref,
                    'country' => 'EM',
                    'origin' => null,
                ],
                $eutmData
            );

            // Link actors
            ActorPivot::updateOrCreate(
                ['matter_id' => $eutm->id, 'actor_id' => $client->id, 'role' => 'CLI'],
                ['display_order' => 1]
            );
            ActorPivot::updateOrCreate(
                ['matter_id' => $eutm->id, 'actor_id' => $client->id, 'role' => 'OWN'],
                ['display_order' => 1]
            );
            ActorPivot::updateOrCreate(
                ['matter_id' => $eutm->id, 'actor_id' => $agent->id, 'role' => 'AGT'],
                ['display_order' => 1]
            );

            // Create national trademarks
            foreach (['FR', 'DE', 'IT'] as $country) {
                $nationalTmData = array_merge(
                    Matter::factory()->trademark()->make()->toArray(),
                    [
                        'caseref' => $caseref,
                        'country' => $country,
                        'parent_id' => $eutm->id,
                        'responsible' => 'msmith',
                    ]
                );

                Matter::updateOrCreate(
                    [
                        'caseref' => $caseref,
                        'country' => $country,
                        'origin' => null,
                    ],
                    $nationalTmData
                );
            }
        }
    }

    /**
     * Create some standalone patents.
     */
    private function createStandalonePatents()
    {
        // For standalone items, we'll check if we already have enough and skip if so
        $existingPatentCount = Matter::where('category_code', 'PAT')
            ->whereNull('parent_id')
            ->where('caseref', 'LIKE', 'PAT-%')
            ->count();

        $existingDesignCount = Matter::where('category_code', 'DES')
            ->whereNull('parent_id')
            ->where('caseref', 'LIKE', 'DES-%')
            ->count();

        // Create additional patents if needed
        $patsToCreate = max(0, 5 - $existingPatentCount);
        for ($i = 0; $i < $patsToCreate; $i++) {
            $patNum = $existingPatentCount + $i + 1;
            $matter = Matter::factory()->patent()->create([
                'caseref' => 'PAT-'.str_pad($patNum, 3, '0', STR_PAD_LEFT),
            ]);

            // Add random client
            $client = Actor::where('phy_person', 0)->inRandomOrder()->first()
                ?? Actor::factory()->company()->create();
            ActorPivot::updateOrCreate(
                ['matter_id' => $matter->id, 'actor_id' => $client->id, 'role' => 'CLI'],
                ['display_order' => 1]
            );
            ActorPivot::updateOrCreate(
                ['matter_id' => $matter->id, 'actor_id' => $client->id, 'role' => 'APP'],
                ['display_order' => 1]
            );

            // Add random inventors
            $inventorCount = rand(1, 3);
            $existingInventors = Actor::where('phy_person', 1)
                ->where('default_role', 'INV')
                ->inRandomOrder()
                ->limit($inventorCount)
                ->get();

            foreach ($existingInventors as $idx => $inventor) {
                ActorPivot::updateOrCreate(
                    ['matter_id' => $matter->id, 'actor_id' => $inventor->id, 'role' => 'INV'],
                    ['display_order' => $idx + 1]
                );
            }
        }

        // Create additional designs if needed
        $desToCreate = max(0, 3 - $existingDesignCount);
        for ($i = 0; $i < $desToCreate; $i++) {
            $desNum = $existingDesignCount + $i + 1;
            $matter = Matter::factory()->design()->create([
                'caseref' => 'DES-'.str_pad($desNum, 3, '0', STR_PAD_LEFT),
            ]);

            $client = Actor::where('phy_person', 0)->inRandomOrder()->first()
                ?? Actor::factory()->company()->create();
            ActorPivot::updateOrCreate(
                ['matter_id' => $matter->id, 'actor_id' => $client->id, 'role' => 'CLI'],
                ['display_order' => 1]
            );
            ActorPivot::updateOrCreate(
                ['matter_id' => $matter->id, 'actor_id' => $client->id, 'role' => 'APP'],
                ['display_order' => 1]
            );
        }
    }
}
