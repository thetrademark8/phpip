<?php

namespace Tests\Support;

use App\Models\Task;
use App\Models\Matter;
use App\Models\Event;
use App\Models\Actor;
use App\Models\EventName;
use App\Models\Rule;
use App\Models\User;
use Carbon\Carbon;

class RenewalTestHelpers
{
    /**
     * Create a renewal task with complete relations.
     */
    public static function createRenewalWithClient(array $attributes = []): Task
    {
        // Create client
        $client = Actor::factory()->create([
            'phy_person' => 0,
            'small_entity' => $attributes['small_entity'] ?? false,
            'discount' => $attributes['discount'] ?? null,
        ]);
        
        // Create matter with client
        $matter = Matter::factory()->create([
            'category_code' => $attributes['category_code'] ?? 'PAT',
            'country' => $attributes['country'] ?? 'FR',
            'dead' => 0,
        ]);
        
        // Link client to matter
        $matter->actors()->attach($client->id, [
            'role' => 'CLI',
            'display' => 1,
            'shared' => 0,
        ]);
        
        // Create trigger event
        $trigger = Event::factory()->create([
            'matter_id' => $matter->id,
            'code' => 'FIL',
            'event_date' => Carbon::now()->subYears(10),
        ]);
        
        // Create renewal task
        return Task::factory()->renewal()->create(array_merge([
            'trigger_id' => $trigger->id,
            'code' => 'REN',
            'due_date' => Carbon::now()->addMonths(3),
            'done' => 0,
            'step' => $attributes['step'] ?? 0,
            'invoice_step' => $attributes['invoice_step'] ?? 0,
            'grace_period' => $attributes['grace_period'] ?? 0,
        ], $attributes));
    }
    
    /**
     * Create a renewal in a specific workflow step.
     */
    public static function createRenewalInStep(int $step, array $attributes = []): Task
    {
        return self::createRenewalWithClient(array_merge($attributes, ['step' => $step]));
    }
    
    /**
     * Create multiple renewals for batch testing.
     */
    public static function createBatchRenewals(int $count, array $attributes = []): array
    {
        $renewals = [];
        
        for ($i = 0; $i < $count; $i++) {
            $renewals[] = self::createRenewalWithClient($attributes);
        }
        
        return $renewals;
    }
    
    /**
     * Setup a complete renewal workflow scenario.
     */
    public static function setupRenewalWorkflow(): array
    {
        return [
            'open' => self::createRenewalInStep(0),
            'instructions' => self::createRenewalInStep(1),
            'reminder' => self::createRenewalInStep(2),
            'payment' => self::createRenewalInStep(3),
            'invoiced' => self::createRenewalInStep(4),
            'receipts' => self::createRenewalInStep(5),
            'closed' => self::createRenewalInStep(10),
            'abandoned' => self::createRenewalInStep(11),
        ];
    }
    
    /**
     * Create a renewal with specific fee configuration.
     */
    public static function createRenewalWithFees(float $cost, float $fee, array $attributes = []): Task
    {
        return self::createRenewalWithClient(array_merge($attributes, [
            'cost' => $cost,
            'fee' => $fee,
        ]));
    }
    
    /**
     * Create a renewal with grace period.
     */
    public static function createRenewalInGracePeriod(array $attributes = []): Task
    {
        return self::createRenewalWithClient(array_merge($attributes, [
            'grace_period' => 1,
            'due_date' => Carbon::now()->subDays(30), // Past due
        ]));
    }
    
    /**
     * Assert renewal is in expected step.
     */
    public static function assertRenewalInStep(Task $renewal, int $expectedStep): void
    {
        $renewal->refresh();
        expect($renewal->step)->toBe($expectedStep);
    }
    
    /**
     * Assert renewal has expected invoice step.
     */
    public static function assertRenewalInInvoiceStep(Task $renewal, int $expectedInvoiceStep): void
    {
        $renewal->refresh();
        expect($renewal->invoice_step)->toBe($expectedInvoiceStep);
    }
    
    /**
     * Assert renewal fees are calculated correctly.
     */
    public static function assertRenewalFeesCalculated(Task $renewal, float $expectedCost, float $expectedFee): void
    {
        expect($renewal->cost)->toBe($expectedCost);
        expect($renewal->fee)->toBe($expectedFee);
    }
    
    /**
     * Create user with specific role for testing.
     */
    public static function createUserWithRole(string $role = 'DBA'): User
    {
        return User::factory()->create([
            'default_role' => $role,
        ]);
    }
    
    /**
     * Get renewal IDs from collection.
     */
    public static function getRenewalIds($renewals): array
    {
        if (is_array($renewals)) {
            return array_map(fn($r) => $r->id, $renewals);
        }
        
        return $renewals->pluck('id')->toArray();
    }
}