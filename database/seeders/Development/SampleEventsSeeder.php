<?php

namespace Database\Seeders\Development;

use App\Models\Event;
use App\Models\Matter;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SampleEventsSeeder extends Seeder
{
    /**
     * Create sample events for development matters.
     */
    public function run()
    {
        $this->command->info('Creating sample events...');

        // Process all matters
        Matter::all()->each(function ($matter) {
            $this->createEventsForMatter($matter);
        });

        $this->command->info('Sample events created successfully!');
    }

    /**
     * Create appropriate events for a matter based on its type.
     */
    private function createEventsForMatter(Matter $matter)
    {
        if ($matter->category_code === 'PAT') {
            $this->createPatentEvents($matter);
        } elseif ($matter->category_code === 'TM') {
            $this->createTrademarkEvents($matter);
        } elseif ($matter->category_code === 'DES') {
            $this->createDesignEvents($matter);
        }
    }

    /**
     * Create patent events.
     */
    private function createPatentEvents(Matter $matter)
    {
        $baseDate = Carbon::now()->subYears(rand(1, 3));

        // Filing event
        $filing = Event::factory()->filing()->create([
            'matter_id' => $matter->id,
            'event_date' => $baseDate->format('Y-m-d'),
            'detail' => $this->generatePatentNumber($matter->country, $baseDate),
        ]);

        // Priority claim for national phases
        if ($matter->parent_id && $matter->type_code === 'NAT') {
            $parentFiling = $matter->parent->filing;
            if ($parentFiling) {
                Event::factory()->create([
                    'matter_id' => $matter->id,
                    'code' => 'PRI',
                    'event_date' => $parentFiling->event_date,
                    'alt_matter_id' => $matter->parent_id,
                    'detail' => $parentFiling->detail,
                ]);
            }
        }

        // Publication (12-18 months after filing)
        if (rand(0, 100) > 20) { // 80% chance of publication
            $pubDate = $baseDate->copy()->addMonths(rand(12, 18));
            if ($pubDate->isPast()) {
                Event::factory()->publication()->create([
                    'matter_id' => $matter->id,
                    'event_date' => $pubDate->format('Y-m-d'),
                    'detail' => $this->generatePatentNumber($matter->country, $pubDate, 'A'),
                ]);
            }
        }

        // Grant (2-4 years after filing)
        if (rand(0, 100) > 40) { // 60% chance of grant
            $grantDate = $baseDate->copy()->addYears(rand(2, 4));
            if ($grantDate->isPast()) {
                Event::factory()->grant()->create([
                    'matter_id' => $matter->id,
                    'event_date' => $grantDate->format('Y-m-d'),
                    'detail' => $this->generatePatentNumber($matter->country, $grantDate, 'B'),
                ]);
            }
        }

        // Add some examination reports
        if ($matter->country === 'EP' || $matter->country === 'US') {
            $examDate = $baseDate->copy()->addMonths(rand(6, 24));
            if ($examDate->isPast() && rand(0, 100) > 50) {
                Event::factory()->examinationReport()->create([
                    'matter_id' => $matter->id,
                    'event_date' => $examDate->format('Y-m-d'),
                ]);
            }
        }
    }

    /**
     * Create trademark events.
     */
    private function createTrademarkEvents(Matter $matter)
    {
        $baseDate = Carbon::now()->subMonths(rand(6, 24));

        // Filing
        $filing = Event::factory()->filing()->create([
            'matter_id' => $matter->id,
            'event_date' => $baseDate->format('Y-m-d'),
            'detail' => $this->generateTrademarkNumber($matter->country, $baseDate),
        ]);

        // Publication (2-4 months after filing)
        $pubDate = $baseDate->copy()->addMonths(rand(2, 4));
        if ($pubDate->isPast()) {
            Event::factory()->publication()->create([
                'matter_id' => $matter->id,
                'event_date' => $pubDate->format('Y-m-d'),
                'detail' => $filing->detail,
            ]);

            // Opposition period
            $oppDate = $pubDate->copy()->addDays(1);
            Event::factory()->create([
                'matter_id' => $matter->id,
                'code' => 'OPT',
                'event_date' => $oppDate->format('Y-m-d'),
            ]);
        }

        // Registration (6-12 months after filing)
        if (rand(0, 100) > 20) { // 80% chance of registration
            $regDate = $baseDate->copy()->addMonths(rand(6, 12));
            if ($regDate->isPast()) {
                Event::factory()->create([
                    'matter_id' => $matter->id,
                    'code' => 'REG',
                    'event_date' => $regDate->format('Y-m-d'),
                    'detail' => $filing->detail,
                ]);
            }
        }
    }

    /**
     * Create design events.
     */
    private function createDesignEvents(Matter $matter)
    {
        $baseDate = Carbon::now()->subMonths(rand(6, 18));

        // Filing
        $filing = Event::factory()->filing()->create([
            'matter_id' => $matter->id,
            'event_date' => $baseDate->format('Y-m-d'),
            'detail' => $this->generateDesignNumber($matter->country, $baseDate),
        ]);

        // Registration (usually quick for designs)
        $regDate = $baseDate->copy()->addMonths(rand(1, 3));
        if ($regDate->isPast()) {
            Event::factory()->create([
                'matter_id' => $matter->id,
                'code' => 'REG',
                'event_date' => $regDate->format('Y-m-d'),
                'detail' => $filing->detail,
            ]);
        }
    }

    /**
     * Generate a realistic patent number.
     */
    private function generatePatentNumber($country, $date, $kind = null)
    {
        $year = $date->format('Y');
        $number = rand(100000, 999999);

        switch ($country) {
            case 'US':
                return $year.'/'.$number;
            case 'EP':
                return substr($year, -2).$number;
            case 'WO':
                return $year.'/'.str_pad(rand(1, 99999), 6, '0', STR_PAD_LEFT);
            default:
                return $year.'-'.$number;
        }
    }

    /**
     * Generate a realistic trademark number.
     */
    private function generateTrademarkNumber($country, $date)
    {
        $year = $date->format('Y');
        $number = rand(10000, 99999);

        switch ($country) {
            case 'EM':
                return str_pad($number * 100 + rand(10, 99), 9, '0', STR_PAD_LEFT);
            case 'US':
                return rand(86, 90).'/'.rand(100000, 999999);
            default:
                return $year.$number;
        }
    }

    /**
     * Generate a realistic design number.
     */
    private function generateDesignNumber($country, $date)
    {
        return 'D'.$date->format('Y').rand(1000, 9999);
    }
}
