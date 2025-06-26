<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Matter;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition()
    {
        // Common event codes - these should exist in event_name table
        $eventCodes = ['FIL', 'PUB', 'GRT', 'REM', 'ALL', 'REQ', 'EXA', 'REP'];

        return [
            'code' => $this->faker->randomElement($eventCodes),
            'matter_id' => Matter::factory(),
            'event_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'alt_matter_id' => null,
            'detail' => $this->faker->optional(0.5)->numerify('########'),
            'notes' => $this->faker->optional(0.3)->sentence,
        ];
    }

    /**
     * Indicate that the event is a filing
     */
    public function filing(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'FIL',
            'detail' => $this->faker->numerify('########'),
        ]);
    }

    /**
     * Indicate that the event is a publication
     */
    public function publication(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'PUB',
            'event_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'detail' => $this->faker->numerify('########'),
        ]);
    }

    /**
     * Indicate that the event is a grant
     */
    public function grant(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'GRT',
            'event_date' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'detail' => $this->faker->numerify('########'),
        ]);
    }

    /**
     * Indicate that the event is an allowance
     */
    public function allowance(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'ALL',
            'event_date' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    /**
     * Indicate that the event is a request for examination
     */
    public function requestExamination(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'REQ',
            'event_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    /**
     * Indicate that the event is an examination report
     */
    public function examinationReport(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'EXA',
            'event_date' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    /**
     * Indicate that the event is a priority claim
     */
    public function priorityClaim(Matter $priorMatter): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'PRI',
            'alt_matter_id' => $priorMatter->id,
            'detail' => $priorMatter->uid,
        ]);
    }

    /**
     * Set a specific date for the event
     */
    public function onDate($date): static
    {
        return $this->state(fn (array $attributes) => [
            'event_date' => $date,
        ]);
    }

    /**
     * Associate the event with a specific matter
     */
    public function forMatter(Matter $matter): static
    {
        return $this->state(fn (array $attributes) => [
            'matter_id' => $matter->id,
        ]);
    }
}
