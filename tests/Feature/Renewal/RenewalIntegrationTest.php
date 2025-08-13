<?php

use App\Models\User;
use App\Services\Renewal\RenewalWorkflowService;
use App\Services\Renewal\RenewalEmailService;
use App\Services\Renewal\RenewalFeeCalculatorService;
use App\Services\Renewal\RenewalQueryService;
use Illuminate\Support\Facades\Notification;
use Tests\Support\RenewalTestHelpers;

beforeEach(function () {
    $this->user = User::factory()->create(['default_role' => 'DBA']);
    $this->actingAs($this->user);
    
    $this->workflowService = app(RenewalWorkflowService::class);
    $this->emailService = app(RenewalEmailService::class);
    $this->feeCalculator = app(RenewalFeeCalculatorService::class);
    $this->queryService = app(RenewalQueryService::class);
    
    Notification::fake();
});

describe('Complete Renewal Workflow', function () {
    
    it('processes renewal through complete lifecycle', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalWithFees(1000, 500, ['step' => 0]);
        
        // Step 1: Send first call
        $result = $this->emailService->sendFirstCall([$renewal->id], false);
        expect($result->success)->toBeTrue();
        
        // Step 2: Update to reminder sent
        $result = $this->workflowService->updateStep([$renewal->id], 2);
        expect($result->success)->toBeTrue();
        RenewalTestHelpers::assertRenewalInStep($renewal, 2);
        
        // Step 3: Send reminder
        $result = $this->emailService->sendReminderCall([$renewal->id]);
        expect($result->success)->toBeTrue();
        
        // Step 4: Mark as payment order received
        $result = $this->workflowService->markAsPaymentOrderReceived([$renewal->id]);
        expect($result->success)->toBeTrue();
        RenewalTestHelpers::assertRenewalInStep($renewal, 4);
        
        // Step 5: Send invoice
        $result = $this->emailService->sendInvoice([$renewal->id]);
        expect($result->success)->toBeTrue();
        
        // Step 6: Mark as paid
        $result = $this->workflowService->markPaid([$renewal->id]);
        expect($result->success)->toBeTrue();
        RenewalTestHelpers::assertRenewalInInvoiceStep($renewal, 3);
        
        // Step 7: Mark as done
        $result = $this->workflowService->markAsDone([$renewal->id]);
        expect($result->success)->toBeTrue();
        RenewalTestHelpers::assertRenewalInStep($renewal, 10);
        
        // Verify final state
        $renewal->refresh();
        expect($renewal->done)->toBe(1);
        expect($renewal->step)->toBe(10);
    });
    
    it('handles workflow abandonment correctly', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalInStep(2);
        
        // Act - Abandon the renewal
        $result = $this->workflowService->abandon([$renewal->id]);
        
        // Assert
        expect($result->success)->toBeTrue();
        RenewalTestHelpers::assertRenewalInStep($renewal, 11);
        expect($renewal->fresh()->done)->toBe(1);
        
        // Verify abandon event was created
        $abandonEvent = \App\Models\Event::where('matter_id', $renewal->matter->id)
            ->where('code', 'ABA')
            ->first();
        expect($abandonEvent)->not->toBeNull();
    });
});

describe('Batch Processing', function () {
    
    it('processes multiple renewals through workflow efficiently', function () {
        // Arrange
        $renewals = RenewalTestHelpers::createBatchRenewals(50, ['step' => 0]);
        $ids = RenewalTestHelpers::getRenewalIds($renewals);
        
        // Act - Process batch through workflow
        $startTime = microtime(true);
        
        // Send first calls
        $result = $this->emailService->sendFirstCall($ids, false);
        expect($result->success)->toBeTrue();
        
        // Update to payment stage
        $result = $this->workflowService->updateStep($ids, 3);
        expect($result->success)->toBeTrue();
        
        // Mark as done
        $result = $this->workflowService->markAsDone($ids);
        expect($result->success)->toBeTrue();
        
        $executionTime = microtime(true) - $startTime;
        
        // Assert
        expect($executionTime)->toBeLessThan(5.0); // Should complete in under 5 seconds
        
        foreach ($renewals as $renewal) {
            $renewal->refresh();
            expect($renewal->step)->toBe(10);
            expect($renewal->done)->toBe(1);
        }
    });
});

describe('End-to-End Controller Tests', function () {
    
    it('handles complete workflow through controller endpoints', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalInStep(0);
        
        // Test index endpoint
        $response = $this->get('/renewal');
        $response->assertOk();
        
        // Test first call endpoint
        $response = $this->post('/renewal/firstcall/1', ['task_ids' => [$renewal->id]]);
        $response->assertRedirect();
        
        // Test update fees endpoint
        $response = $this->post('/renewal/updatefees', [
            'task_id' => $renewal->id,
            'cost' => 1500,
            'fee' => 750,
        ]);
        $response->assertRedirect();
        
        // Verify fee update
        $renewal->refresh();
        expect($renewal->cost)->toBe(1500.0);
        expect($renewal->fee)->toBe(750.0);
        
        // Test abandon endpoint
        $response = $this->post('/renewal/abandon', ['task_ids' => [$renewal->id]]);
        $response->assertRedirect();
        
        // Verify abandonment
        $renewal->refresh();
        expect($renewal->step)->toBe(11);
    });
    
    it('handles dashboard statistics correctly', function () {
        // Arrange
        RenewalTestHelpers::createBatchRenewals(5, ['step' => 0]);
        RenewalTestHelpers::createBatchRenewals(3, ['step' => 1]);
        RenewalTestHelpers::createBatchRenewals(7, ['step' => 2]);
        
        // Act
        $response = $this->get('/renewal/dashboard');
        
        // Assert
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Renewal/Dashboard')
            ->has('stats')
            ->where('stats.by_step.step_0', 5)
            ->where('stats.by_step.step_1', 3)
            ->where('stats.by_step.step_2', 7)
            ->where('stats.total_active', 15)
        );
    });
});

describe('Data Integrity', function () {
    
    it('maintains referential integrity through workflow', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalWithClient();
        $matterId = $renewal->matter->id;
        $triggerId = $renewal->trigger_id;
        
        // Act - Process through workflow
        $this->workflowService->updateStep([$renewal->id], 2);
        $this->workflowService->updateInvoiceStep([$renewal->id], 1);
        $this->workflowService->markAsDone([$renewal->id]);
        
        // Assert - Verify relationships intact
        $renewal->refresh();
        expect($renewal->matter->id)->toBe($matterId);
        expect($renewal->trigger_id)->toBe($triggerId);
        expect($renewal->matter)->not->toBeNull();
        expect($renewal->trigger)->not->toBeNull();
    });
    
    it('handles concurrent updates correctly', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalInStep(0);
        
        // Act - Simulate concurrent updates
        $result1 = $this->workflowService->updateStep([$renewal->id], 1);
        $result2 = $this->workflowService->updateStep([$renewal->id], 2);
        
        // Assert - Last update should win
        expect($result1->success)->toBeTrue();
        expect($result2->success)->toBeTrue();
        RenewalTestHelpers::assertRenewalInStep($renewal, 2);
    });
});

describe('Performance Benchmarks', function () {
    
    it('handles 1000+ renewals with acceptable performance', function () {
        // This test is marked as slow and should only run in CI or when specifically requested
        if (!env('RUN_SLOW_TESTS', false)) {
            $this->markTestSkipped('Slow test skipped. Set RUN_SLOW_TESTS=true to run.');
        }
        
        // Arrange
        $renewals = RenewalTestHelpers::createBatchRenewals(1000, ['step' => 0]);
        $ids = RenewalTestHelpers::getRenewalIds($renewals);
        
        // Act
        $startTime = microtime(true);
        
        // Get statistics
        $stats = $this->queryService->getRenewalStats();
        
        // Calculate fees in batch
        $fees = $this->feeCalculator->calculateBatch(collect($renewals));
        
        // Update workflow
        $this->workflowService->updateStep($ids, 2);
        
        $executionTime = microtime(true) - $startTime;
        
        // Assert
        expect($executionTime)->toBeLessThan(30.0); // Should complete in under 30 seconds
        expect($stats['total_active'])->toBeGreaterThanOrEqual(1000);
        expect($fees)->toHaveCount(1000);
    });
});