<?php

use App\Models\User;
use App\Services\Renewal\RenewalExportService;
use Tests\Support\RenewalTestHelpers;

beforeEach(function () {
    $this->user = User::factory()->create(['default_role' => 'DBA']);
    $this->actingAs($this->user);
    
    $this->exportService = app(RenewalExportService::class);
});

describe('CSV Export', function () {
    
    it('exports renewals to CSV format', function () {
        // Arrange
        $renewals = collect(RenewalTestHelpers::createBatchRenewals(5, ['step' => 2]));
        
        // Act
        $response = $this->exportService->exportToCsv($renewals);
        
        // Assert
        expect($response)->toBeInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class);
        expect($response->headers->get('content-type'))->toContain('text/csv');
    });
    
    it('includes calculated fees in CSV export', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalWithFees(1000, 500);
        $renewals = collect([$renewal]);
        
        // Act
        ob_start();
        $response = $this->exportService->exportToCsv($renewals);
        $response->sendContent();
        $content = ob_get_clean();
        
        // Assert
        expect($content)->toContain('1000'); // Cost
        expect($content)->toContain('500');  // Fee
    });
    
    it('handles special characters in CSV export', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalWithClient(['caseref' => 'TEST-123/ÉÀÇ']);
        $renewals = collect([$renewal]);
        
        // Act
        ob_start();
        $response = $this->exportService->exportToCsv($renewals);
        $response->sendContent();
        $content = ob_get_clean();
        
        // Assert - BOM should be present for UTF-8
        expect(substr($content, 0, 3))->toBe(chr(0xEF).chr(0xBB).chr(0xBF));
    });
});

describe('XML Payment Export', function () {
    
    it('generates payment XML for renewals', function () {
        // Arrange
        $renewals = RenewalTestHelpers::createBatchRenewals(3, ['step' => 4]);
        $ids = RenewalTestHelpers::getRenewalIds($renewals);
        
        // Act
        $xml = $this->exportService->generatePaymentXml($ids);
        
        // Assert
        expect($xml)->toContain('<?xml');
        expect($xml)->toContain('<payments>');
        expect($xml)->toContain('<payment>');
    });
    
    it('groups renewals by client in XML', function () {
        // Arrange
        $client = \App\Models\Actor::factory()->create();
        $matter1 = \App\Models\Matter::factory()->create();
        $matter2 = \App\Models\Matter::factory()->create();
        
        $matter1->actors()->attach($client->id, ['role' => 'CLI']);
        $matter2->actors()->attach($client->id, ['role' => 'CLI']);
        
        $renewal1 = \App\Models\Task::factory()->renewal()->create([
            'trigger_id' => \App\Models\Event::factory()->create(['matter_id' => $matter1->id])->id,
            'cost' => 1000,
            'fee' => 500,
        ]);
        $renewal2 = \App\Models\Task::factory()->renewal()->create([
            'trigger_id' => \App\Models\Event::factory()->create(['matter_id' => $matter2->id])->id,
            'cost' => 2000,
            'fee' => 750,
        ]);
        
        // Act
        $xml = $this->exportService->generatePaymentXml([$renewal1->id, $renewal2->id]);
        $xmlObj = simplexml_load_string($xml);
        
        // Assert
        expect($xmlObj->payment)->toHaveCount(1); // One payment group for same client
        expect((float)$xmlObj->payment[0]->total_amount)->toBe(4250.0);
    });
    
    it('calculates correct totals in XML', function () {
        // Arrange
        $renewal = RenewalTestHelpers::createRenewalWithFees(1000, 500);
        
        // Act
        $xml = $this->exportService->generatePaymentXml([$renewal->id]);
        $xmlObj = simplexml_load_string($xml);
        
        // Assert
        expect((float)$xmlObj->payment[0]->renewal[0]->cost)->toBe(1000.0);
        expect((float)$xmlObj->payment[0]->renewal[0]->fee)->toBe(500.0);
        expect((float)$xmlObj->payment[0]->renewal[0]->total)->toBe(1500.0);
        expect((float)$xmlObj->payment[0]->total_amount)->toBe(1500.0);
    });
});

describe('Export Performance', function () {
    
    it('handles large datasets efficiently', function () {
        // Arrange
        $renewals = collect(RenewalTestHelpers::createBatchRenewals(100, ['step' => 2]));
        
        // Act
        $startTime = microtime(true);
        $response = $this->exportService->exportToCsv($renewals);
        $executionTime = microtime(true) - $startTime;
        
        // Assert
        expect($response)->toBeInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class);
        expect($executionTime)->toBeLessThan(2.0); // Should complete in under 2 seconds
    });
});

describe('Export Error Handling', function () {
    
    it('handles empty renewal list gracefully', function () {
        // Act
        $xml = $this->exportService->generatePaymentXml([]);
        
        // Assert
        expect($xml)->toContain('<?xml');
        expect($xml)->toContain('<payments></payments>');
    });
    
    it('handles invalid renewal IDs', function () {
        // Act
        $xml = $this->exportService->generatePaymentXml([999999]);
        
        // Assert
        expect($xml)->toContain('<?xml');
        expect($xml)->toContain('<payments></payments>');
    });
});