<?php

use App\Models\Task;
use App\Models\User;
use App\Services\Renewal\RenewalWorkflowService;
use Tests\Support\RenewalTestHelpers;

beforeEach(function () {
    $this->user = User::factory()->create(['default_role' => 'DBA']);
    $this->actingAs($this->user);
    
    $this->workflowService = app(RenewalWorkflowService::class);
});

describe('Renewal Workflow Transitions', function () {
    
    it('transitions renewal from open to instructions sent', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalInStep(0);
        
        // Act
        $result = $this->workflowService->updateStep([$renewal->id], 1);
        
        // Assert
        expect($result->success)->toBeTrue();
        RenewalTestHelpers::assertRenewalInStep($renewal, 1);
    });
    
    it('transitions renewal through complete workflow', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalInStep(0);
        $transitions = [
            0 => 1, // Open → Instructions
            1 => 2, // Instructions → Reminder
            2 => 3, // Reminder → Payment
            3 => 4, // Payment → Invoiced
            4 => 5, // Invoiced → Receipts
            5 => 10, // Receipts → Closed
        ];
        
        // Act & Assert
        foreach ($transitions as $from => $to) {
            $renewal->update(['step' => $from]);
            $result = $this->workflowService->updateStep([$renewal->id], $to);
            
            expect($result->success)->toBeTrue();
            RenewalTestHelpers::assertRenewalInStep($renewal, $to);
        }
    });
    
    it('can abandon renewal from any step', function () {
        $steps = [0, 1, 2, 3, 4, 5];
        
        foreach ($steps as $step) {
            // Arrange
            $renewal = RenewalTestHelpers::createRenewalInStep($step);
            
            // Act
            $result = $this->workflowService->abandon([$renewal->id]);
            
            // Assert
            expect($result->success)->toBeTrue();
            RenewalTestHelpers::assertRenewalInStep($renewal, 11);
            expect($renewal->fresh()->done)->toBe(1);
        }
    });
    
    it('updates both step and invoice step simultaneously', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalInStep(2);
        
        // Act
        $result = $this->workflowService->updateStepAndInvoiceStep([$renewal->id], 4, 1);
        
        // Assert
        expect($result->success)->toBeTrue();
        RenewalTestHelpers::assertRenewalInStep($renewal, 4);
        RenewalTestHelpers::assertRenewalInInvoiceStep($renewal, 1);
    });
    
    it('processes batch updates for multiple renewals', function () {
        // Arrange
        $renewals = RenewalTestHelpers::createBatchRenewals(10, ['step' => 0]);
        $ids = RenewalTestHelpers::getRenewalIds($renewals);
        
        // Act
        $result = $this->workflowService->updateStep($ids, 2);
        
        // Assert
        expect($result->success)->toBeTrue();
        expect($result->affectedCount)->toBe(10);
        
        foreach ($renewals as $renewal) {
            RenewalTestHelpers::assertRenewalInStep($renewal, 2);
        }
    });
    
    it('marks renewals as done with done date', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalInStep(5);
        $doneDate = now()->format('Y-m-d');
        
        // Act
        $result = $this->workflowService->markAsDone([$renewal->id], $doneDate);
        
        // Assert
        expect($result->success)->toBeTrue();
        $renewal->refresh();
        expect($renewal->done)->toBe(1);
        expect($renewal->done_date->format('Y-m-d'))->toBe($doneDate);
        expect($renewal->step)->toBe(10);
    });
    
    it('marks renewals as lapsing', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalInStep(2);
        
        // Act
        $result = $this->workflowService->markAsLapsing([$renewal->id]);
        
        // Assert
        expect($result->success)->toBeTrue();
        RenewalTestHelpers::assertRenewalInStep($renewal, 11);
    });
    
    it('sets grace period for renewals', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalInStep(2);
        
        // Act
        $result = $this->workflowService->setGracePeriod([$renewal->id], 1);
        
        // Assert
        expect($result->success)->toBeTrue();
        expect($renewal->fresh()->grace_period)->toBe(1);
    });
    
    it('handles invalid step values gracefully', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalInStep(0);
        
        // Act
        $result = $this->workflowService->updateStep([$renewal->id], 99);
        
        // Assert
        expect($result->success)->toBeFalse();
        expect($result->error)->toContain('Invalid step');
        RenewalTestHelpers::assertRenewalInStep($renewal, 0);
    });
});

describe('Invoice Step Management', function () {
    
    it('updates invoice step independently', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalInStep(3, ['invoice_step' => 0]);
        
        // Act
        $result = $this->workflowService->updateInvoiceStep([$renewal->id], 1);
        
        // Assert
        expect($result->success)->toBeTrue();
        RenewalTestHelpers::assertRenewalInInvoiceStep($renewal, 1);
        RenewalTestHelpers::assertRenewalInStep($renewal, 3); // Step unchanged
    });
    
    it('transitions through invoice workflow', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalInStep(3, ['invoice_step' => 0]);
        $invoiceTransitions = [
            0 => 1, // Not invoiced → Invoice sent
            1 => 2, // Invoice sent → Invoice paid
            2 => 3, // Invoice paid → Receipt issued
        ];
        
        // Act & Assert
        foreach ($invoiceTransitions as $from => $to) {
            $renewal->update(['invoice_step' => $from]);
            $result = $this->workflowService->updateInvoiceStep([$renewal->id], $to);
            
            expect($result->success)->toBeTrue();
            RenewalTestHelpers::assertRenewalInInvoiceStep($renewal, $to);
        }
    });
    
    it('marks renewals as payment order received', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalInStep(2);
        
        // Act
        $result = $this->workflowService->markAsPaymentOrderReceived([$renewal->id]);
        
        // Assert
        expect($result->success)->toBeTrue();
        RenewalTestHelpers::assertRenewalInStep($renewal, 4);
    });
});

describe('Workflow Service Methods from merged services', function () {
    
    it('creates renewal orders', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalInStep(2);
        
        // Act
        $result = $this->workflowService->createOrders([$renewal->id]);
        
        // Assert
        expect($result->success)->toBeTrue();
        RenewalTestHelpers::assertRenewalInStep($renewal, 4);
        RenewalTestHelpers::assertRenewalInInvoiceStep($renewal, 1);
    });
    
    it('marks renewals as invoiced', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalInStep(4, ['invoice_step' => 1]);
        
        // Act
        $result = $this->workflowService->markInvoiced([$renewal->id]);
        
        // Assert
        expect($result->success)->toBeTrue();
        RenewalTestHelpers::assertRenewalInInvoiceStep($renewal, 2);
    });
    
    it('marks renewals as paid', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalInStep(4, ['invoice_step' => 2]);
        
        // Act
        $result = $this->workflowService->markPaid([$renewal->id]);
        
        // Assert
        expect($result->success)->toBeTrue();
        RenewalTestHelpers::assertRenewalInInvoiceStep($renewal, 3);
    });
});

describe('Workflow Permissions and Security', function () {
    
    it('respects user permissions for workflow updates', function () {
        // Arrange
        $clientUser = User::factory()->create(['default_role' => 'CLI']);
        $this->actingAs($clientUser);
        
        $renewal = RenewalTestHelpers::createRenewalInStep(0);
        
        // Act - Client users should not be able to update workflow
        $response = $this->post('/renewal/topay', ['task_ids' => [$renewal->id]]);
        
        // Assert
        expect($response->status())->toBe(403);
        RenewalTestHelpers::assertRenewalInStep($renewal, 0);
    });
    
    it('prevents SQL injection in batch updates', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalInStep(0);
        $maliciousIds = ["1; DROP TABLE task; --", "' OR 1=1 --"];
        
        // Act & Assert - Should handle malicious input safely
        foreach ($maliciousIds as $maliciousId) {
            $result = $this->workflowService->updateStep([$maliciousId], 2);
            expect($result->success)->toBeFalse();
        }
        
        // Original renewal should be unchanged
        RenewalTestHelpers::assertRenewalInStep($renewal, 0);
    });
});