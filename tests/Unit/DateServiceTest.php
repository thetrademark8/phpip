<?php

use App\Services\DateService;
use Carbon\Carbon;

beforeEach(function () {
    $this->dateService = new DateService();
});

test('converts carbon date to ISO format', function () {
    $carbon = Carbon::create(2025, 6, 26);
    $result = $this->dateService->toIso($carbon);
    
    expect($result)->toBe('2025-06-26');
});

test('converts string date to ISO format', function () {
    $result = $this->dateService->toIso('2025-06-26');
    
    expect($result)->toBe('2025-06-26');
});

test('returns null for null date', function () {
    $result = $this->dateService->toIso(null);
    
    expect($result)->toBeNull();
});

test('parses ISO date to Carbon', function () {
    $result = $this->dateService->parseIso('2025-06-26');
    
    expect($result)->toBeInstanceOf(Carbon::class)
        ->and($result->format('Y-m-d'))->toBe('2025-06-26');
});

test('validates ISO format correctly', function () {
    expect($this->dateService->isIsoFormat('2025-06-26'))->toBeTrue()
        ->and($this->dateService->isIsoFormat('26/06/2025'))->toBeFalse()
        ->and($this->dateService->isIsoFormat('06-26-2025'))->toBeFalse();
});

test('normalizes various date formats to ISO', function () {
    // European format
    expect($this->dateService->normalizeToIso('26/06/2025'))->toBe('2025-06-26')
        // American format
        ->and($this->dateService->normalizeToIso('06/26/2025'))->toBe('2025-06-26')
        // German format
        ->and($this->dateService->normalizeToIso('26.06.2025'))->toBe('2025-06-26')
        // ISO format (should remain unchanged)
        ->and($this->dateService->normalizeToIso('2025-06-26'))->toBe('2025-06-26');
});

test('returns todays date in ISO format', function () {
    $today = $this->dateService->today();
    
    expect($today)->toBeIsoDate()
        ->and($today)->toBe(Carbon::today()->format('Y-m-d'));
});

test('adds days to date and returns ISO format', function () {
    $date = '2025-06-26';
    $result = $this->dateService->addDays($date, 7);
    
    expect($result)->toBe('2025-07-03');
});

test('formats datetime with time', function () {
    $carbon = Carbon::create(2025, 6, 26, 14, 30, 45);
    $result = $this->dateService->toIsoDateTime($carbon);
    
    expect($result)->toBe('2025-06-26 14:30:45');
});

test('handles invalid dates gracefully', function () {
    expect($this->dateService->toIso('invalid-date'))->toBeNull()
        ->and($this->dateService->parseIso('invalid-date'))->toBeNull()
        ->and($this->dateService->normalizeToIso('not-a-date'))->toBeNull();
});