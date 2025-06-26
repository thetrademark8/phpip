<?php

namespace Database\Factories;

use App\Models\Classifier;
use App\Models\Matter;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassifierFactory extends Factory
{
    protected $model = Classifier::class;

    public function definition()
    {
        // Common classifier types
        $types = ['TITOF', 'TIT', 'DESCR', 'TITEN', 'LOGO', 'IMG', 'IPC', 'NICE'];

        return [
            'matter_id' => Matter::factory(),
            'type_code' => $this->faker->randomElement($types),
            'value' => $this->faker->sentence,
            'img' => null,
            'url' => null,
            'value_id' => null,
            'display_order' => 1,
            'lnk_matter_id' => null,
        ];
    }

    /**
     * Indicate that the classifier is a title
     */
    public function title(): static
    {
        return $this->state(fn (array $attributes) => [
            'type_code' => 'TIT',
            'value' => $this->faker->catchPhrase,
        ]);
    }

    /**
     * Indicate that the classifier is an official title
     */
    public function officialTitle(): static
    {
        return $this->state(fn (array $attributes) => [
            'type_code' => 'TITOF',
            'value' => $this->faker->catchPhrase,
        ]);
    }

    /**
     * Indicate that the classifier is a description/abstract
     */
    public function description(): static
    {
        return $this->state(fn (array $attributes) => [
            'type_code' => 'DESCR',
            'value' => $this->faker->paragraph,
        ]);
    }

    /**
     * Indicate that the classifier is an IPC classification (for patents)
     */
    public function ipc(): static
    {
        return $this->state(fn (array $attributes) => [
            'type_code' => 'IPC',
            'value' => $this->faker->regexify('[A-H][0-9]{2}[A-Z] [0-9]{1,3}/[0-9]{2,4}'),
        ]);
    }

    /**
     * Indicate that the classifier is a Nice classification (for trademarks)
     */
    public function nice(): static
    {
        $niceClasses = range(1, 45);

        return $this->state(fn (array $attributes) => [
            'type_code' => 'NICE',
            'value' => $this->faker->randomElement($niceClasses),
        ]);
    }

    /**
     * Indicate that the classifier is a logo/image
     */
    public function logo(): static
    {
        return $this->state(fn (array $attributes) => [
            'type_code' => 'LOGO',
            'value' => 'Logo',
            'img' => null, // In real usage, this would contain image data
        ]);
    }

    /**
     * Indicate that the classifier has a URL
     */
    public function withUrl(string $url): static
    {
        return $this->state(fn (array $attributes) => [
            'url' => $url,
        ]);
    }

    /**
     * Set the classifier for a specific matter
     */
    public function forMatter(Matter $matter): static
    {
        return $this->state(fn (array $attributes) => [
            'matter_id' => $matter->id,
        ]);
    }

    /**
     * Link the classifier to another matter
     */
    public function linkedTo(Matter $linkedMatter): static
    {
        return $this->state(fn (array $attributes) => [
            'lnk_matter_id' => $linkedMatter->id,
        ]);
    }

    /**
     * Set the display order
     */
    public function withOrder(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'display_order' => $order,
        ]);
    }
}
