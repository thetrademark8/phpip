<?php

use App\Models\User;
use App\DataTransferObjects\Renewal\RenewalFilterDTO;
use App\Services\Renewal\RenewalQueryService;
use Tests\Support\RenewalTestHelpers;

beforeEach(function () {
    $this->user = User::factory()->create(['default_role' => 'DBA']);
    $this->actingAs($this->user);
    
    $this->queryService = app(RenewalQueryService::class);
});

describe('Renewal Query Filtering', function () {
    
    it('filters renewals by step', function () {
        // Arrange
        RenewalTestHelpers::createRenewalInStep(0);
        RenewalTestHelpers::createRenewalInStep(1);
        RenewalTestHelpers::createRenewalInStep(2);
        
        // Act
        $filters = new RenewalFilterDTO();
        $filters->step = 1;
        $query = $this->queryService->buildQuery($filters);
        
        // Assert
        expect($query->count())->toBe(1);
        expect($query->first()->step)->toBe(1);
    });
    
    it('filters renewals by invoice step', function () {
        // Arrange
        RenewalTestHelpers::createRenewalInStep(3, ['invoice_step' => 0]);
        RenewalTestHelpers::createRenewalInStep(3, ['invoice_step' => 1]);
        RenewalTestHelpers::createRenewalInStep(3, ['invoice_step' => 2]);
        
        // Act
        $filters = new RenewalFilterDTO();
        $filters->invoiceStep = 1;
        $query = $this->queryService->buildQuery($filters);
        
        // Assert
        expect($query->count())->toBe(1);
        expect($query->first()->invoice_step)->toBe(1);
    });
    
    it('filters renewals by date range', function () {
        // Arrange
        RenewalTestHelpers::createRenewalWithClient(['due_date' => now()->addDays(10)]);
        RenewalTestHelpers::createRenewalWithClient(['due_date' => now()->addDays(30)]);
        RenewalTestHelpers::createRenewalWithClient(['due_date' => now()->addDays(60)]);
        
        // Act
        $filters = new RenewalFilterDTO();
        $filters->fromDate = now()->format('Y-m-d');
        $filters->untilDate = now()->addDays(40)->format('Y-m-d');
        $query = $this->queryService->buildQuery($filters);
        
        // Assert
        expect($query->count())->toBe(2);
    });
    
    it('filters my renewals by assigned user', function () {
        // Arrange
        $otherUser = User::factory()->create();
        
        RenewalTestHelpers::createRenewalWithClient(['assigned_to' => $this->user->login]);
        RenewalTestHelpers::createRenewalWithClient(['assigned_to' => $otherUser->login]);
        RenewalTestHelpers::createRenewalWithClient(['assigned_to' => null]);
        
        // Act
        $filters = new RenewalFilterDTO();
        $filters->myRenewals = true;
        $query = $this->queryService->buildQuery($filters);
        
        // Assert
        expect($query->count())->toBe(1);
    });
    
    it('applies step and invoice step filters mutually exclusively', function () {
        // Arrange
        RenewalTestHelpers::createRenewalInStep(2, ['invoice_step' => 0]);
        RenewalTestHelpers::createRenewalInStep(3, ['invoice_step' => 1]);
        
        // Act - Filter by invoice step
        $filters = new RenewalFilterDTO();
        $filters->invoiceStep = 1;
        $query = $this->queryService->buildQuery($filters);
        
        // Assert - Should ignore step filter when invoice_step is set
        expect($query->count())->toBe(1);
        expect($query->first()->invoice_step)->toBe(1);
    });
});

describe('Renewal Query Sorting', function () {
    
    it('sorts by due date ascending for normal steps', function () {
        // Arrange
        $renewal1 = RenewalTestHelpers::createRenewalInStep(2, ['due_date' => now()->addDays(30)]);
        $renewal2 = RenewalTestHelpers::createRenewalInStep(2, ['due_date' => now()->addDays(10)]);
        $renewal3 = RenewalTestHelpers::createRenewalInStep(2, ['due_date' => now()->addDays(20)]);
        
        // Act
        $filters = new RenewalFilterDTO();
        $filters->step = 2;
        $query = $this->queryService->buildQuery($filters);
        $results = $query->get();
        
        // Assert
        expect($results->first()->id)->toBe($renewal2->id);
        expect($results->last()->id)->toBe($renewal1->id);
    });
    
    it('sorts by due date descending for closed renewals', function () {
        // Arrange
        $renewal1 = RenewalTestHelpers::createRenewalInStep(10, ['due_date' => now()->subDays(30)]);
        $renewal2 = RenewalTestHelpers::createRenewalInStep(10, ['due_date' => now()->subDays(10)]);
        $renewal3 = RenewalTestHelpers::createRenewalInStep(10, ['due_date' => now()->subDays(20)]);
        
        // Act
        $filters = new RenewalFilterDTO();
        $filters->step = 10;
        $query = $this->queryService->buildQuery($filters);
        $results = $query->get();
        
        // Assert
        expect($results->first()->id)->toBe($renewal2->id);
        expect($results->last()->id)->toBe($renewal1->id);
    });
});

describe('Renewal Statistics', function () {
    
    it('calculates renewal statistics by step', function () {
        // Arrange
        RenewalTestHelpers::createBatchRenewals(3, ['step' => 0]);
        RenewalTestHelpers::createBatchRenewals(2, ['step' => 1]);
        RenewalTestHelpers::createBatchRenewals(5, ['step' => 2]);
        
        // Act
        $stats = $this->queryService->getRenewalStats();
        
        // Assert
        expect($stats['by_step']['step_0'])->toBe(3);
        expect($stats['by_step']['step_1'])->toBe(2);
        expect($stats['by_step']['step_2'])->toBe(5);
        expect($stats['total_active'])->toBe(10);
    });
    
    it('calculates renewal statistics by invoice step', function () {
        // Arrange
        RenewalTestHelpers::createBatchRenewals(4, ['invoice_step' => 0]);
        RenewalTestHelpers::createBatchRenewals(3, ['invoice_step' => 1]);
        RenewalTestHelpers::createBatchRenewals(2, ['invoice_step' => 2]);
        
        // Act
        $stats = $this->queryService->getRenewalStats();
        
        // Assert
        expect($stats['by_invoice_step']['invoice_step_0'])->toBeGreaterThanOrEqual(4);
        expect($stats['by_invoice_step']['invoice_step_1'])->toBeGreaterThanOrEqual(3);
        expect($stats['by_invoice_step']['invoice_step_2'])->toBeGreaterThanOrEqual(2);
    });
});

describe('Query Performance', function () {
    
    it('handles large datasets efficiently', function () {
        // Arrange
        RenewalTestHelpers::createBatchRenewals(100, ['step' => 2]);
        
        // Act
        $startTime = microtime(true);
        $filters = new RenewalFilterDTO();
        $filters->step = 2;
        $query = $this->queryService->buildQuery($filters);
        $count = $query->count();
        $executionTime = microtime(true) - $startTime;
        
        // Assert
        expect($count)->toBe(100);
        expect($executionTime)->toBeLessThan(1.0); // Should complete in under 1 second
    });
    
    it('avoids N+1 queries with eager loading', function () {
        // Arrange
        RenewalTestHelpers::createBatchRenewals(10, ['step' => 0]);
        
        // Act
        $filters = new RenewalFilterDTO();
        $query = $this->queryService->buildQuery($filters);
        
        // Enable query log
        \DB::enableQueryLog();
        $renewals = $query->get();
        
        // Access relationships that should be eager loaded
        foreach ($renewals as $renewal) {
            $matter = $renewal->matter;
            $trigger = $renewal->trigger;
        }
        
        $queryCount = count(\DB::getQueryLog());
        \DB::disableQueryLog();
        
        // Assert - Should have minimal queries, not 10+ for N+1
        expect($queryCount)->toBeLessThan(5);
    });
});

describe('Complex Query Scenarios', function () {
    
    it('filters by multiple criteria simultaneously', function () {
        // Arrange
        $client = \App\Models\Actor::factory()->create(['name' => 'Test Client']);
        $matter = \App\Models\Matter::factory()->create(['country' => 'FR']);
        $matter->actors()->attach($client->id, ['role' => 'CLI']);
        
        $renewal = \App\Models\Task::factory()->renewal()->create([
            'trigger_id' => \App\Models\Event::factory()->create(['matter_id' => $matter->id])->id,
            'step' => 2,
            'due_date' => now()->addDays(15),
        ]);
        
        // Create other renewals that don't match
        RenewalTestHelpers::createBatchRenewals(5, ['step' => 1]);
        
        // Act
        $filters = new RenewalFilterDTO();
        $filters->step = 2;
        $filters->country = 'FR';
        $filters->clientName = 'Test Client';
        $query = $this->queryService->buildQuery($filters);
        
        // Assert
        expect($query->count())->toBe(1);
        expect($query->first()->id)->toBe($renewal->id);
    });
    
    it('handles grace period filtering correctly', function () {
        // Arrange
        RenewalTestHelpers::createRenewalInStep(2, ['grace_period' => 0]);
        RenewalTestHelpers::createRenewalInStep(2, ['grace_period' => 1]);
        RenewalTestHelpers::createRenewalInStep(2, ['grace_period' => 2]);
        
        // Act
        $filters = new RenewalFilterDTO();
        $filters->step = 2;
        $filters->gracePeriod = 1;
        $query = $this->queryService->buildQuery($filters);
        
        // Assert
        expect($query->count())->toBe(1);
        expect($query->first()->grace_period)->toBe(1);
    });
});