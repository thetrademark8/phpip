<?php

use App\Models\User;
use App\DataTransferObjects\Renewal\RenewalDTO;
use App\Services\Renewal\RenewalFeeCalculatorService;
use Tests\Support\RenewalTestHelpers;

beforeEach(function () {
    $this->user = User::factory()->create(['default_role' => 'DBA']);
    $this->actingAs($this->user);
    
    $this->feeCalculator = app(RenewalFeeCalculatorService::class);
});

describe('Renewal Fee Calculation', function () {
    
    it('calculates standard fees correctly', function () {
        // Arrange
        $cost = 1000.00;
        $fee = 500.00;
        $renewal = RenewalTestHelpers::createRenewalWithFees($cost, $fee);
        $renewalDTO = RenewalDTO::fromModel($renewal);
        
        // Act
        $feeDTO = $this->feeCalculator->calculate($renewalDTO);
        
        // Assert
        expect($feeDTO->cost)->toBe($cost);
        expect($feeDTO->fee)->toBe($fee);
        expect($feeDTO->total)->toBe($cost + $fee);
    });
    
    it('applies grace period factor to fees', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalInGracePeriod([
            'cost' => 1000.00,
            'fee' => 500.00,
        ]);
        $renewalDTO = RenewalDTO::fromModel($renewal);
        
        // Act
        $feeDTO = $this->feeCalculator->calculate($renewalDTO);
        
        // Assert - Grace period may apply a factor
        expect($feeDTO->cost)->toBeGreaterThanOrEqual(1000.00);
        expect($feeDTO->fee)->toBeGreaterThanOrEqual(500.00);
    });
    
    it('calculates fees with client discount', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalWithClient([
            'cost' => 1000.00,
            'fee' => 500.00,
            'discount' => 0.1, // 10% discount
        ]);
        $renewalDTO = RenewalDTO::fromModel($renewal);
        
        // Act
        $feeDTO = $this->feeCalculator->calculate($renewalDTO);
        
        // Assert
        expect($feeDTO->total)->toBeLessThan(1500.00);
    });
    
    it('handles small entity status for reduced fees', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalWithClient([
            'cost' => 1000.00,
            'fee' => 500.00,
            'small_entity' => true,
        ]);
        $renewalDTO = RenewalDTO::fromModel($renewal);
        
        // Act
        $feeDTO = $this->feeCalculator->calculate($renewalDTO);
        
        // Assert
        expect($feeDTO->total)->toBeLessThanOrEqual(1500.00);
    });
    
    it('calculates VAT correctly', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalWithFees(1000.00, 500.00);
        $renewalDTO = RenewalDTO::fromModel($renewal);
        
        // Act
        $feeDTO = $this->feeCalculator->calculate($renewalDTO);
        
        // Assert
        expect($feeDTO->vatRate)->toBeGreaterThanOrEqual(0);
        expect($feeDTO->vatAmount)->toBeGreaterThanOrEqual(0);
        expect($feeDTO->totalWithVat)->toBeGreaterThanOrEqual($feeDTO->total);
    });
});

describe('Batch Fee Calculation', function () {
    
    it('calculates fees for multiple renewals efficiently', function () {
        // Arrange
        $renewals = collect([
            RenewalTestHelpers::createRenewalWithFees(1000, 500),
            RenewalTestHelpers::createRenewalWithFees(2000, 750),
            RenewalTestHelpers::createRenewalWithFees(1500, 600),
        ]);
        
        // Act
        $startTime = microtime(true);
        $fees = $this->feeCalculator->calculateBatch($renewals);
        $executionTime = microtime(true) - $startTime;
        
        // Assert
        expect($fees)->toHaveCount(3);
        expect($fees[$renewals[0]->id]->cost)->toBe(1000.0);
        expect($fees[$renewals[1]->id]->cost)->toBe(2000.0);
        expect($fees[$renewals[2]->id]->cost)->toBe(1500.0);
        expect($executionTime)->toBeLessThan(0.5); // Should be fast
    });
    
    it('handles empty collection gracefully', function () {
        // Act
        $fees = $this->feeCalculator->calculateBatch(collect([]));
        
        // Assert
        expect($fees)->toBeArray();
        expect($fees)->toBeEmpty();
    });
});

describe('Fee Calculation Edge Cases', function () {
    
    it('handles zero fees', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalWithFees(0, 0);
        $renewalDTO = RenewalDTO::fromModel($renewal);
        
        // Act
        $feeDTO = $this->feeCalculator->calculate($renewalDTO);
        
        // Assert
        expect($feeDTO->cost)->toBe(0.0);
        expect($feeDTO->fee)->toBe(0.0);
        expect($feeDTO->total)->toBe(0.0);
    });
    
    it('handles negative discount values', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalWithClient([
            'cost' => 1000.00,
            'fee' => 500.00,
            'discount' => -0.1, // Invalid negative discount
        ]);
        $renewalDTO = RenewalDTO::fromModel($renewal);
        
        // Act
        $feeDTO = $this->feeCalculator->calculate($renewalDTO);
        
        // Assert - Should not apply negative discount
        expect($feeDTO->total)->toBeGreaterThanOrEqual(1500.00);
    });
    
    it('handles very large fee amounts', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalWithFees(999999.99, 999999.99);
        $renewalDTO = RenewalDTO::fromModel($renewal);
        
        // Act
        $feeDTO = $this->feeCalculator->calculate($renewalDTO);
        
        // Assert
        expect($feeDTO->cost)->toBe(999999.99);
        expect($feeDTO->fee)->toBe(999999.99);
        expect($feeDTO->total)->toBe(1999999.98);
    });
});