<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition()
    {
        // Task codes that exist in event_name table
        $taskCodes = ['REN', 'REP', 'PAY', 'REM', 'PRI', 'REQ', 'EXA'];

        return [
            'trigger_id' => Event::factory(),
            'code' => $this->faker->randomElement($taskCodes),
            'due_date' => $this->faker->dateTimeBetween('now', '+2 years'),
            'assigned_to' => null,
            'detail' => json_encode(['RYear' => $this->faker->numberBetween(1, 20)]),
            'done' => false,
            'done_date' => null,
            'rule_used' => null,
            'time_spent' => null,
            'notes' => $this->faker->optional(0.2)->sentence,
            'cost' => $this->faker->optional(0.3)->randomFloat(2, 100, 5000),
            'fee' => $this->faker->optional(0.3)->randomFloat(2, 50, 2000),
            'currency' => $this->faker->randomElement(['EUR', 'USD', 'GBP']),
            'step' => 0,
            'invoice_step' => 0,
            'grace_period' => 0,
        ];
    }

    /**
     * Indicate that the task is a renewal
     */
    public function renewal(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'REN',
            'detail' => json_encode(['RYear' => $this->faker->numberBetween(1, 20)]),
            'cost' => $this->faker->randomFloat(2, 500, 5000),
            'fee' => $this->faker->randomFloat(2, 200, 2000),
        ]);
    }

    /**
     * Indicate that the task is a priority deadline
     */
    public function priorityDeadline(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'PRI',
            'due_date' => $this->faker->dateTimeBetween('+11 months', '+12 months'),
        ]);
    }

    /**
     * Indicate that the task is a response deadline
     */
    public function response(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'REP',
            'due_date' => $this->faker->dateTimeBetween('+1 month', '+3 months'),
        ]);
    }

    /**
     * Indicate that the task is completed
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'done' => true,
            'done_date' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    /**
     * Indicate that the task is overdue
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => $this->faker->dateTimeBetween('-3 months', '-1 day'),
            'done' => false,
        ]);
    }

    /**
     * Indicate that the task is due soon (within 30 days)
     */
    public function dueSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => $this->faker->dateTimeBetween('now', '+30 days'),
            'done' => false,
        ]);
    }

    /**
     * Assign the task to a specific user
     */
    public function assignedTo(string $userLogin): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_to' => $userLogin,
        ]);
    }

    /**
     * Set the task for a specific event
     */
    public function forEvent(Event $event): static
    {
        return $this->state(fn (array $attributes) => [
            'trigger_id' => $event->id,
        ]);
    }

    /**
     * Set renewal for a specific year
     */
    public function forRenewalYear(int $year): static
    {
        return $this->renewal()->state(fn (array $attributes) => [
            'detail' => json_encode(['RYear' => $year]),
        ]);
    }
    
    /**
     * Set the renewal in a specific workflow step
     */
    public function inStep(int $step): static
    {
        return $this->state(fn (array $attributes) => [
            'step' => $step,
        ]);
    }
    
    /**
     * Set the renewal in a specific invoice step
     */
    public function inInvoiceStep(int $invoiceStep): static
    {
        return $this->state(fn (array $attributes) => [
            'invoice_step' => $invoiceStep,
        ]);
    }
    
    /**
     * Set the renewal in grace period
     */
    public function inGracePeriod(int $gracePeriod = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'grace_period' => $gracePeriod,
            'due_date' => $this->faker->dateTimeBetween('-60 days', '-1 day'),
        ]);
    }
    
    /**
     * Set the renewal as abandoned
     */
    public function abandoned(): static
    {
        return $this->state(fn (array $attributes) => [
            'step' => 11,
            'done' => 1,
            'done_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }
    
    /**
     * Set the renewal as closed
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'step' => 10,
            'done' => 1,
            'done_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }
    
    /**
     * Set specific cost and fee
     */
    public function withFees(float $cost, float $fee): static
    {
        return $this->state(fn (array $attributes) => [
            'cost' => $cost,
            'fee' => $fee,
        ]);
    }
}
