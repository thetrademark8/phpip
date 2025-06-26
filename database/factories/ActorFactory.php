<?php

namespace Database\Factories;

use App\Models\Actor;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActorFactory extends Factory
{
    protected $model = Actor::class;

    public function definition()
    {
        $isCompany = $this->faker->boolean(60); // 60% companies, 40% persons

        return [
            'name' => $isCompany ? $this->faker->company : $this->faker->lastName,
            'first_name' => $isCompany ? null : $this->faker->firstName,
            'display_name' => null,
            'login' => null,
            'password' => null,
            'default_role' => null,
            'function' => $isCompany ? $this->faker->randomElement(['CEO', 'Legal Department', 'IP Department']) : $this->faker->jobTitle,
            'parent_id' => null,
            'company_id' => null,
            'site_id' => null,
            'phy_person' => ! $isCompany,
            'nationality' => $this->faker->randomElement(['US', 'FR', 'DE', 'GB', 'JP', 'CN', 'CA', 'IT', 'ES']),
            'language' => $this->faker->randomElement(['en', 'fr', 'de']),
            'small_entity' => $this->faker->boolean(20), // 20% small entities
            'address' => $this->faker->streetAddress.', '.$this->faker->postcode.' '.$this->faker->city,
            'country' => $this->faker->randomElement(['US', 'FR', 'DE', 'GB', 'JP', 'CN', 'CA', 'IT', 'ES']),
            'address_mailing' => null,
            'country_mailing' => null,
            'address_billing' => null,
            'country_billing' => null,
            'email' => $this->faker->companyEmail,
            'phone' => $this->faker->phoneNumber,
            'legal_form' => $isCompany ? $this->faker->randomElement(['Inc.', 'LLC', 'GmbH', 'SA', 'Ltd.']) : null,
            'registration_no' => $isCompany ? $this->faker->numerify('#########') : null,
            'warn' => false,
            'ren_discount' => 0.00,
            'notes' => null,
            'VAT_number' => $isCompany ? $this->faker->optional(0.7)->numerify('##########') : null,
        ];
    }

    /**
     * Indicate that the actor is a company
     */
    public function company(): static
    {
        return $this->state(fn (array $attributes) => [
            'phy_person' => false,
            'first_name' => null,
            'name' => $this->faker->company,
            'legal_form' => $this->faker->randomElement(['Inc.', 'LLC', 'GmbH', 'SA', 'Ltd.']),
            'registration_no' => $this->faker->numerify('#########'),
            'VAT_number' => $this->faker->numerify('##########'),
            'function' => $this->faker->randomElement(['CEO', 'Legal Department', 'IP Department', 'R&D Department']),
        ]);
    }

    /**
     * Indicate that the actor is a person
     */
    public function person(): static
    {
        return $this->state(fn (array $attributes) => [
            'phy_person' => true,
            'name' => $this->faker->lastName,
            'first_name' => $this->faker->firstName,
            'legal_form' => null,
            'registration_no' => null,
            'VAT_number' => null,
            'function' => $this->faker->jobTitle,
        ]);
    }

    /**
     * Indicate that the actor is a patent/trademark agent
     */
    public function agent(): static
    {
        return $this->state(fn (array $attributes) => [
            'phy_person' => true,
            'name' => $this->faker->lastName,
            'first_name' => $this->faker->firstName,
            'function' => $this->faker->randomElement(['Patent Attorney', 'Trademark Attorney', 'IP Attorney']),
            'default_role' => 'AGT',
        ]);
    }

    /**
     * Indicate that the actor is an applicant/client
     */
    public function applicant(): static
    {
        return $this->company()->state(fn (array $attributes) => [
            'default_role' => 'APP',
        ]);
    }

    /**
     * Indicate that the actor is an inventor
     */
    public function inventor(): static
    {
        return $this->person()->state(fn (array $attributes) => [
            'default_role' => 'INV',
            'function' => $this->faker->randomElement(['Engineer', 'Researcher', 'Scientist', 'Developer']),
        ]);
    }

    /**
     * Indicate that the actor works for a specific company
     */
    public function worksFor(Actor $company): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $company->id,
        ]);
    }

    /**
     * Indicate that the actor is a subsidiary of another company
     */
    public function subsidiaryOf(Actor $parent): static
    {
        return $this->company()->state(fn (array $attributes) => [
            'parent_id' => $parent->id,
        ]);
    }
}
