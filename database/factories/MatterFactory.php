<?php

namespace Database\Factories;

use App\Models\Matter;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MatterFactory extends Factory
{
    protected $model = Matter::class;

    public function definition()
    {
        $categories = ['PAT', 'TM', 'DES', 'DOM', 'COP'];
        $category = $this->faker->randomElement($categories);

        return [
            'category_code' => $category,
            'caseref' => strtoupper($this->faker->bothify('??###')),
            'country' => $this->faker->randomElement(['US', 'EP', 'FR', 'DE', 'GB', 'JP', 'CN', 'CA']),
            'origin' => null,
            'type_code' => null,
            'idx' => null,
            'parent_id' => null,
            'container_id' => null,
            'responsible' => User::first()->login ?? 'phpipuser',
            'dead' => false,
            'notes' => $this->faker->optional(0.3)->sentence,
            'expire_date' => $this->faker->optional(0.5)->dateTimeBetween('+1 year', '+20 years'),
            'term_adjust' => 0,
            'alt_ref' => '',
        ];
    }

    /**
     * Indicate that the matter is a patent
     */
    public function patent(): static
    {
        return $this->state(fn (array $attributes) => [
            'category_code' => 'PAT',
            'expire_date' => $this->faker->dateTimeBetween('+15 years', '+20 years'),
        ]);
    }

    /**
     * Indicate that the matter is a trademark
     */
    public function trademark(): static
    {
        return $this->state(fn (array $attributes) => [
            'category_code' => 'TM',
            'expire_date' => $this->faker->dateTimeBetween('+5 years', '+10 years'),
        ]);
    }

    /**
     * Indicate that the matter is a design
     */
    public function design(): static
    {
        return $this->state(fn (array $attributes) => [
            'category_code' => 'DES',
            'expire_date' => $this->faker->dateTimeBetween('+10 years', '+15 years'),
        ]);
    }

    /**
     * Indicate that the matter is dead/abandoned
     */
    public function dead(): static
    {
        return $this->state(fn (array $attributes) => [
            'dead' => true,
            'expire_date' => null,
        ]);
    }

    /**
     * Indicate that the matter is a PCT application
     */
    public function pct(): static
    {
        return $this->patent()->state(fn (array $attributes) => [
            'country' => 'WO',
            'origin' => 'WO',
        ]);
    }

    /**
     * Indicate that the matter is a European patent
     */
    public function european(): static
    {
        return $this->patent()->state(fn (array $attributes) => [
            'country' => 'EP',
            'origin' => 'EP',
        ]);
    }

    /**
     * Set the matter as part of a family
     */
    public function inFamily(string $caseref): static
    {
        return $this->state(fn (array $attributes) => [
            'caseref' => $caseref,
        ]);
    }

    /**
     * Set the matter as a child of another matter
     */
    public function childOf(Matter $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent->id,
            'caseref' => $parent->caseref,
        ]);
    }

    /**
     * Set the matter as contained by another matter
     */
    public function containedBy(Matter $container): static
    {
        return $this->state(fn (array $attributes) => [
            'container_id' => $container->id,
        ]);
    }

    /**
     * Configure the model factory to create matters with a sequence number
     */
    public function withSequentialCaseref(string $prefix = 'TEST'): static
    {
        static $sequence = 1;

        return $this->state(fn (array $attributes) => [
            'caseref' => $prefix.str_pad($sequence++, 4, '0', STR_PAD_LEFT),
        ]);
    }
}
