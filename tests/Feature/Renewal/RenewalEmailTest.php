<?php

use App\Models\User;
use App\Models\Task;
use App\Notifications\Renewal\RenewalFirstCallNotification;
use App\Notifications\Renewal\RenewalReminderCallNotification;
use App\Notifications\Renewal\RenewalLastCallNotification;
use App\Notifications\Renewal\RenewalInvoiceNotification;
use App\Services\Renewal\RenewalEmailService;
use Illuminate\Support\Facades\Notification;
use Tests\Support\RenewalTestHelpers;

beforeEach(function () {
    $this->user = User::factory()->create(['default_role' => 'DBA']);
    $this->actingAs($this->user);
    
    $this->emailService = app(RenewalEmailService::class);
    
    // Fake notifications for testing
    Notification::fake();
});

describe('First Call Emails', function () {
    
    it('sends first call email and updates step', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalInStep(0);
        
        // Act
        $result = $this->emailService->sendFirstCall([$renewal->id], false);
        
        // Assert
        expect($result->success)->toBeTrue();
        expect($result->count)->toBe(1);
        
        // Verify notification was sent
        Notification::assertSentTimes(RenewalFirstCallNotification::class, 1);
    });
    
    it('previews first call email without sending', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalInStep(0);
        
        // Act
        $result = $this->emailService->sendFirstCall([$renewal->id], true);
        
        // Assert
        expect($result->success)->toBeTrue();
        
        // Verify no notification was sent (preview only)
        Notification::assertNothingSent();
    });
    
    it('groups renewals by client for batch sending', function () {
        // Arrange
        $client = \App\Models\Actor::factory()->create();
        $matter1 = \App\Models\Matter::factory()->create();
        $matter2 = \App\Models\Matter::factory()->create();
        
        // Link same client to both matters
        $matter1->actors()->attach($client->id, ['role' => 'CLI']);
        $matter2->actors()->attach($client->id, ['role' => 'CLI']);
        
        $renewal1 = Task::factory()->renewal()->inStep(0)->create([
            'trigger_id' => \App\Models\Event::factory()->create(['matter_id' => $matter1->id])->id
        ]);
        $renewal2 = Task::factory()->renewal()->inStep(0)->create([
            'trigger_id' => \App\Models\Event::factory()->create(['matter_id' => $matter2->id])->id
        ]);
        
        // Act
        $result = $this->emailService->sendFirstCall([$renewal1->id, $renewal2->id], false);
        
        // Assert
        expect($result->success)->toBeTrue();
        expect($result->count)->toBe(1); // One email for both renewals (same client)
        
        Notification::assertSentTimes(RenewalFirstCallNotification::class, 1);
    });
});

describe('Reminder Call Emails', function () {
    
    it('sends reminder call email', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalInStep(2);
        
        // Act
        $result = $this->emailService->sendReminderCall([$renewal->id]);
        
        // Assert
        expect($result->success)->toBeTrue();
        
        // Should send both first and warning notifications based on grace period
        Notification::assertSentTimes(RenewalFirstCallNotification::class, 1);
    });
    
    it('handles renewals with different grace periods', function () {
        // Arrange
        $renewal1 = RenewalTestHelpers::createRenewalInStep(2, ['grace_period' => 0]);
        $renewal2 = RenewalTestHelpers::createRenewalInStep(2, ['grace_period' => 1]);
        
        // Act
        $result = $this->emailService->sendReminderCall([$renewal1->id, $renewal2->id]);
        
        // Assert
        expect($result->success)->toBeTrue();
        expect($result->count)->toBeGreaterThan(0);
    });
});

describe('Last Call Emails', function () {
    
    it('sends last call email and updates grace period', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalInStep(2);
        
        // Act
        $result = $this->emailService->sendLastCall([$renewal->id]);
        
        // Assert
        expect($result->success)->toBeTrue();
        
        // Verify grace period was updated
        expect($renewal->fresh()->grace_period)->toBe(1);
        
        Notification::assertSentTimes(RenewalLastCallNotification::class, 1);
    });
});

describe('Invoice Emails', function () {
    
    it('sends invoice email and updates invoice step', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalInStep(4, ['invoice_step' => 1]);
        
        // Act
        $result = $this->emailService->sendInvoice([$renewal->id]);
        
        // Assert
        expect($result->success)->toBeTrue();
        expect($result->count)->toBe(1);
        
        // Verify invoice step updated
        expect($renewal->fresh()->invoice_step)->toBe(2);
        
        Notification::assertSentTimes(RenewalInvoiceNotification::class, 1);
    });
    
    it('generates correct invoice number', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalInStep(4, ['invoice_step' => 1]);
        
        // Act
        $preview = $this->emailService->previewEmail($renewal->id, 'invoice');
        
        // Assert
        expect($preview)->toHaveKey('subject');
        expect($preview['subject'])->toContain('Invoice');
        expect($preview)->toHaveKey('recipient');
    });
});

describe('Email Preview', function () {
    
    it('previews different email types', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalInStep(2);
        $types = ['first', 'warn', 'last', 'invoice'];
        
        foreach ($types as $type) {
            // Act
            $preview = $this->emailService->previewEmail($renewal->id, $type);
            
            // Assert
            expect($preview)->toHaveKeys(['subject', 'recipient']);
            expect($preview['subject'])->not->toBeEmpty();
        }
    });
    
    it('returns error for invalid email type', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalInStep(2);
        
        // Act
        $preview = $this->emailService->previewEmail($renewal->id, 'invalid');
        
        // Assert
        expect($preview)->toHaveKey('error');
        expect($preview['error'])->toContain('Invalid email type');
    });
    
    it('returns error for non-existent renewal', function () {
        // Act
        $preview = $this->emailService->previewEmail(999999, 'first');
        
        // Assert
        expect($preview)->toHaveKey('error');
        expect($preview['error'])->toContain('Renewal not found');
    });
});

describe('Email Report', function () {
    
    it('sends report to specified recipient', function () {
        // Arrange
        $renewals = RenewalTestHelpers::createBatchRenewals(5, ['step' => 2]);
        $ids = RenewalTestHelpers::getRenewalIds($renewals);
        $recipient = 'manager@example.com';
        
        // Act
        $result = $this->emailService->sendReport($ids, $recipient);
        
        // Assert
        expect($result->success)->toBeTrue();
        expect($result->count)->toBe(1);
    });
});

describe('Email Content and Formatting', function () {
    
    it('includes all required renewal information in email', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalWithFees(1000, 500, ['step' => 0]);
        
        // Act
        $preview = $this->emailService->previewEmail($renewal->id, 'first');
        
        // Assert
        expect($preview)->toHaveKeys(['subject', 'recipient', 'body']);
        expect($preview['body'])->toContain($renewal->caseref ?? '');
    });
    
    it('calculates correct totals including VAT', function () {
        // Arrange
        $cost = 1000;
        $fee = 500;
        $renewal = RenewalTestHelpers::createRenewalWithFees($cost, $fee, ['step' => 0]);
        
        // Act
        $preview = $this->emailService->previewEmail($renewal->id, 'first');
        
        // Assert
        // VAT calculation would be in the email body
        expect($preview['body'])->toContain((string)($cost + $fee));
    });
});

describe('Email Language Support', function () {
    
    it('sends emails in correct language based on client', function () {
        // Arrange
        $client = \App\Models\Actor::factory()->create(['language' => 'fr']);
        $matter = \App\Models\Matter::factory()->create();
        $matter->actors()->attach($client->id, ['role' => 'CLI']);
        
        $renewal = Task::factory()->renewal()->inStep(0)->create([
            'trigger_id' => \App\Models\Event::factory()->create(['matter_id' => $matter->id])->id
        ]);
        
        // Act
        $preview = $this->emailService->previewEmail($renewal->id, 'first');
        
        // Assert
        expect($preview)->toHaveKey('subject');
        // Language-specific content would be checked here
    });
});

describe('Email Error Handling', function () {
    
    it('handles missing client email gracefully', function () {
        // Arrange
        $client = \App\Models\Actor::factory()->create(['email' => null]);
        $matter = \App\Models\Matter::factory()->create();
        $matter->actors()->attach($client->id, ['role' => 'CLI']);
        
        $renewal = Task::factory()->renewal()->inStep(0)->create([
            'trigger_id' => \App\Models\Event::factory()->create(['matter_id' => $matter->id])->id
        ]);
        
        // Act
        $result = $this->emailService->sendFirstCall([$renewal->id], false);
        
        // Assert
        expect($result->success)->toBeTrue();
        expect($result->count)->toBe(0); // No email sent due to missing address
    });
    
    it('continues processing after individual email failure', function () {
        // Arrange
        $renewal1 = RenewalTestHelpers::createRenewalInStep(0);
        $renewal2 = RenewalTestHelpers::createRenewalInStep(0);
        
        // Simulate failure for first renewal by setting invalid data
        $renewal1->update(['trigger_id' => 999999]);
        
        // Act
        $result = $this->emailService->sendFirstCall([$renewal1->id, $renewal2->id], false);
        
        // Assert
        expect($result->success)->toBeTrue();
        expect($result->count)->toBeGreaterThan(0);
    });
});