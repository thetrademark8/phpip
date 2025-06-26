<?php

namespace Database\Seeders\Development;

use App\Models\Classifier;
use App\Models\Matter;
use Illuminate\Database\Seeder;

class SampleClassifiersSeeder extends Seeder
{
    /**
     * Create sample classifiers for development matters.
     */
    public function run()
    {
        $this->command->info('Creating sample classifiers...');

        // Add classifiers to all matters
        Matter::all()->each(function ($matter) {
            $this->addClassifiersToMatter($matter);
        });

        $this->command->info('Sample classifiers created successfully!');
    }

    /**
     * Add appropriate classifiers to a matter based on its type.
     */
    private function addClassifiersToMatter(Matter $matter)
    {
        // Add titles to all matters
        $this->addTitles($matter);

        // Add category-specific classifiers
        switch ($matter->category_code) {
            case 'PAT':
                $this->addPatentClassifiers($matter);
                break;
            case 'TM':
                $this->addTrademarkClassifiers($matter);
                break;
            case 'DES':
                $this->addDesignClassifiers($matter);
                break;
        }
    }

    /**
     * Add title classifiers.
     */
    private function addTitles(Matter $matter)
    {
        // Main title
        $titles = [
            'PAT' => [
                'Advanced Machine Learning System for Real-Time Data Analysis',
                'Quantum Computing Interface with Error Correction',
                'Biodegradable Polymer Composition for Medical Applications',
                'High-Efficiency Solar Cell with Nanostructured Surface',
                'Autonomous Vehicle Navigation System',
                'Blockchain-Based Supply Chain Management Platform',
                'Graphene-Enhanced Battery Technology',
                'AI-Powered Diagnostic Medical Device',
                'Smart Home Energy Management System',
                'Wireless Power Transfer Device',
            ],
            'TM' => [
                'INNOVATECH',
                'QUANTUM LEAP',
                'ECOGREEN',
                'SMARTLIFE',
                'NEXGEN SOLUTIONS',
                'FUTUREWARE',
                'TECHPRO',
                'DIGITREND',
                'CYBERGUARD',
                'CLOUDMASTER',
            ],
            'DES' => [
                'Smartphone Case Design',
                'Ergonomic Chair',
                'LED Lamp Design',
                'Watch Face Pattern',
                'Bottle Shape',
                'Textile Pattern',
                'User Interface Design',
                'Jewelry Design',
                'Packaging Design',
                'Furniture Design',
            ],
        ];

        $category = $matter->category_code;
        $title = $titles[$category][array_rand($titles[$category])];

        // For family members, use the same title
        if ($matter->parent_id) {
            $parentTitle = $matter->parent->titles()->where('type_code', 'TIT')->first();
            if ($parentTitle) {
                $title = $parentTitle->value;
            }
        }

        Classifier::factory()->title()->create([
            'matter_id' => $matter->id,
            'value' => $title,
        ]);

        // Add official title (slightly different)
        if (rand(0, 100) > 50) {
            Classifier::factory()->officialTitle()->create([
                'matter_id' => $matter->id,
                'value' => strtoupper($title),
            ]);
        }

        // Add English title for non-English matters
        if ($matter->country !== 'US' && $matter->country !== 'GB' && rand(0, 100) > 30) {
            Classifier::factory()->create([
                'matter_id' => $matter->id,
                'type_code' => 'TITEN',
                'value' => $title.' (English Translation)',
            ]);
        }

        // Add description/abstract
        if ($matter->category_code === 'PAT' && rand(0, 100) > 40) {
            Classifier::factory()->description()->create([
                'matter_id' => $matter->id,
            ]);
        }
    }

    /**
     * Add patent-specific classifiers.
     */
    private function addPatentClassifiers(Matter $matter)
    {
        // IPC classifications
        $ipcClasses = [
            'H04L 29/06', 'G06F 21/00', 'H04W 12/00', // Cybersecurity
            'G06N 20/00', 'G06N 3/02', 'G06N 5/04', // AI/ML
            'H01L 31/00', 'H02J 3/00', 'H02S 40/00', // Solar/Energy
            'C08L 23/00', 'A61K 47/00', 'B32B 27/00', // Materials
            'G05D 1/00', 'B60W 30/00', 'G08G 1/00', // Autonomous vehicles
        ];

        // Add 1-3 IPC classifications
        $numClasses = rand(1, 3);
        $selectedClasses = array_rand(array_flip($ipcClasses), $numClasses);
        if (! is_array($selectedClasses)) {
            $selectedClasses = [$selectedClasses];
        }

        foreach ($selectedClasses as $index => $ipcClass) {
            Classifier::factory()->ipc()->create([
                'matter_id' => $matter->id,
                'value' => $ipcClass,
                'display_order' => $index + 1,
            ]);
        }
    }

    /**
     * Add trademark-specific classifiers.
     */
    private function addTrademarkClassifiers(Matter $matter)
    {
        // Nice classes
        $niceClassGroups = [
            'tech' => [9, 35, 38, 42], // Technology services
            'fashion' => [3, 14, 18, 25], // Fashion and beauty
            'food' => [29, 30, 31, 32, 33, 43], // Food and beverages
            'medical' => [5, 10, 44], // Medical and pharmaceutical
            'education' => [16, 41], // Education and entertainment
        ];

        // Select a group and use 1-3 classes from it
        $group = array_rand($niceClassGroups);
        $classes = $niceClassGroups[$group];
        $numClasses = rand(1, min(3, count($classes)));
        $selectedClasses = array_rand(array_flip($classes), $numClasses);
        if (! is_array($selectedClasses)) {
            $selectedClasses = [$selectedClasses];
        }

        foreach ($selectedClasses as $index => $niceClass) {
            Classifier::factory()->nice()->create([
                'matter_id' => $matter->id,
                'value' => (string) $niceClass,
                'display_order' => $index + 1,
            ]);

            // Add goods/services description
            Classifier::factory()->create([
                'matter_id' => $matter->id,
                'type_code' => 'GOODS',
                'value' => $this->getGoodsDescription($niceClass),
                'display_order' => $index + 1,
            ]);
        }

        // Add logo for some trademarks
        if (rand(0, 100) > 60) {
            Classifier::factory()->logo()->create([
                'matter_id' => $matter->id,
            ]);
        }
    }

    /**
     * Add design-specific classifiers.
     */
    private function addDesignClassifiers(Matter $matter)
    {
        // Locarno classes
        $locarnoClasses = [
            '14-01' => 'Recording and data processing equipment',
            '06-01' => 'Seats',
            '26-04' => 'Lighting apparatus',
            '10-02' => 'Watches',
            '09-01' => 'Bottles',
            '05-05' => 'Textile fabrics',
            '14-04' => 'Screen displays and icons',
            '11-01' => 'Jewelry',
            '09-05' => 'Packaging',
            '06-03' => 'Tables and desks',
        ];

        $selectedClass = array_rand($locarnoClasses);

        Classifier::factory()->create([
            'matter_id' => $matter->id,
            'type_code' => 'LOCARNO',
            'value' => $selectedClass,
        ]);
    }

    /**
     * Get goods/services description for a Nice class.
     */
    private function getGoodsDescription($niceClass)
    {
        $descriptions = [
            3 => 'Cosmetics; perfumery; essential oils; cleaning preparations',
            5 => 'Pharmaceutical preparations; medical devices; dietary supplements',
            9 => 'Computer software; mobile applications; electronic devices',
            14 => 'Jewelry; precious metals; watches; ornamental pins',
            16 => 'Printed matter; instructional materials; stationery',
            18 => 'Leather goods; bags; wallets; umbrellas',
            25 => 'Clothing; footwear; headgear',
            29 => 'Meat; fish; preserved foods; dairy products',
            30 => 'Coffee; tea; bread; pastries; confectionery',
            31 => 'Fresh fruits and vegetables; natural plants',
            32 => 'Beers; non-alcoholic beverages; fruit juices',
            33 => 'Alcoholic beverages; wines; spirits',
            35 => 'Advertising; business management; retail services',
            38 => 'Telecommunications services; broadcasting',
            41 => 'Education; training; entertainment services',
            42 => 'Scientific and technological services; software development',
            43 => 'Restaurant services; catering; temporary accommodation',
            44 => 'Medical services; veterinary services; beauty care',
        ];

        return $descriptions[$niceClass] ?? 'Various goods and services in class '.$niceClass;
    }
}
