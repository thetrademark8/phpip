<?php

namespace Database\Factories;

use App\Models\Actor;
use App\Models\RenewalInvoiceBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RenewalInvoiceBatch>
 */
class RenewalInvoiceBatchFactory extends Factory
{
    protected $model = RenewalInvoiceBatch::class;

    public function definition(): array
    {
        $payload = [
            'socid' => $this->faker->numberBetween(1, 999999),
            'date' => now()->timestamp,
            'lines' => [],
        ];

        return [
            'ref_ext' => 'REN-' . strtoupper($this->faker->unique()->bothify('########????')),
            'actor_id' => Actor::factory(),
            'dolibarr_socid' => $payload['socid'],
            'status' => RenewalInvoiceBatch::STATUS_RESERVED,
            'payload_hash' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
            'payload' => $payload,
            'external_invoice_id' => null,
            'external_invoice_ref' => null,
            'error_code' => null,
            'error_message' => null,
            'created_by' => null,
        ];
    }
}
