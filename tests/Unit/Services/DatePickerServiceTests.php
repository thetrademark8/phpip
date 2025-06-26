<?php

use App\Services\DatePickerService;
use App\Services\DateService;
use Carbon\Carbon;

beforeEach(function () {
    $this->dateService = new DateService;
    $this->datePickerService = new DatePickerService($this->dateService);
});

it('properly transforms slash separated european dates to iso format', function () {
    expect($this->datePickerService->convertToIso('25/12/2023'))->toBe('2023-12-25');
    expect($this->datePickerService->convertToIso('01/01/2024'))->toBe('2024-01-01');
});

it('properly transforms dash separated european dates to iso format', function () {
    expect($this->datePickerService->convertToIso('25-12-2023'))->toBe('2023-12-25');
    expect($this->datePickerService->convertToIso('01-01-2024'))->toBe('2024-01-01');
});

it('properly transforms dot separated european dates to iso format', function () {
    expect($this->datePickerService->convertToIso('25.12.2023'))->toBe('2023-12-25');
    expect($this->datePickerService->convertToIso('01.01.2024'))->toBe('2024-01-01');
});

it('preserves existing iso format dates unchanged', function () {
    expect($this->datePickerService->convertToIso('2023-12-25'))->toBe('2023-12-25');
    expect($this->datePickerService->convertToIso('2024-01-01'))->toBe('2024-01-01');
});

it('returns null for empty or malformed input', function () {
    expect($this->datePickerService->convertToIso(null))->toBeNull();
    expect($this->datePickerService->convertToIso(''))->toBeNull();
    expect($this->datePickerService->convertToIso('   '))->toBeNull();
    expect($this->datePickerService->convertToIso('invalid-date'))->toBeNull();
});

it('transforms iso dates to readable display format', function () {
    expect($this->datePickerService->convertToDisplay('2023-12-25'))->toBe('25/12/2023');
    expect($this->datePickerService->convertToDisplay('2024-01-01'))->toBe('01/01/2024');
});

it('transforms carbon instances to readable display format', function () {
    $carbon = Carbon::create(2023, 12, 25);
    expect($this->datePickerService->convertToDisplay($carbon))->toBe('25/12/2023');
});

it('correctly validates various common date formats', function () {
    expect($this->datePickerService->isValidDate('25/12/2023'))->toBeTrue();
    expect($this->datePickerService->isValidDate('25-12-2023'))->toBeTrue();
    expect($this->datePickerService->isValidDate('25.12.2023'))->toBeTrue();
    expect($this->datePickerService->isValidDate('2023-12-25'))->toBeTrue();
});

it('permits empty dates during validation', function () {
    expect($this->datePickerService->isValidDate(null))->toBeTrue();
    expect($this->datePickerService->isValidDate(''))->toBeTrue();
    expect($this->datePickerService->isValidDate('   '))->toBeTrue();
});

it('correctly rejects malformed dates during validation', function () {
    expect($this->datePickerService->isValidDate('invalid-date'))->toBeFalse();
    expect($this->datePickerService->isValidDate('32/13/2023'))->toBeFalse();
    expect($this->datePickerService->isValidDate('25/25/2023'))->toBeFalse();
});

it('accurately parses multiple date input formats', function () {
    $expected = Carbon::create(2023, 12, 25)->startOfDay();

    expect($this->datePickerService->parseFlexibleDate('25/12/2023'))->toEqual($expected);
    expect($this->datePickerService->parseFlexibleDate('25-12-2023'))->toEqual($expected);
    expect($this->datePickerService->parseFlexibleDate('25.12.2023'))->toEqual($expected);
    expect($this->datePickerService->parseFlexibleDate('2023-12-25'))->toEqual($expected);
});

it('provides correct javascript configuration settings', function () {
    $config = $this->datePickerService->getJavaScriptConfig();

    expect($config)->toBeArray();
    expect($config['dateFormat'])->toBe('Y-m-d');
    expect($config['altFormat'])->toBe('d/m/Y');
    expect($config['altInput'])->toBe(true);
    expect($config['allowInput'])->toBe(true);
    expect($config['clickOpens'])->toBe(true);
    expect($config['placeholder'])->toBe('dd/mm/yyyy');
});

it('provides correct display format string', function () {
    expect($this->datePickerService->getDisplayFormat())->toBe('dd/mm/yyyy');
});

it('provides correct iso format string', function () {
    expect($this->datePickerService->getIsoFormat())->toBe('Y-m-d');
});

it('can be properly resolved from service container', function () {
    $service = app(\App\Contracts\Services\DatePickerServiceInterface::class);
    expect($service)->toBeInstanceOf(\App\Services\DatePickerService::class);
});

it('integrates seamlessly with date service', function () {
    $isoDate = $this->datePickerService->convertToIso('25/12/2023');
    expect($isoDate)->toBe('2023-12-25');

    $displayDate = $this->datePickerService->convertToDisplay($isoDate);
    expect($displayDate)->toBe('25/12/2023');
});

it('follows solid principles via interface implementation', function () {
    $service = app(\App\Contracts\Services\DatePickerServiceInterface::class);
    expect($service)->toBeInstanceOf(\App\Contracts\Services\DatePickerServiceInterface::class);
});
