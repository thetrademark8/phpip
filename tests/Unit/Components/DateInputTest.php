<?php

use App\Services\DatePickerService;
use App\Services\DateService;
use App\View\Components\DateInput;

beforeEach(function () {
    $this->dateService = new DateService;
    $this->datePickerService = new DatePickerService($this->dateService);
});

it('creates date input component with basic properties', function () {
    $component = new DateInput(
        name: 'due_date',
        datePickerService: $this->datePickerService
    );

    expect($component->name)->toBe('due_date');
    expect($component->required)->toBeFalse();
    expect($component->displayValue)->toBe('');
    expect($component->showLabel)->toBeTrue();
    expect($component->id)->toStartWith('date-input-');
});

it('generates label text from field name when no label provided', function () {
    $component = new DateInput(
        name: 'due_date',
        datePickerService: $this->datePickerService
    );

    expect($component->getLabelText())->toBe('Due date');

    $component2 = new DateInput(
        name: 'priority_date',
        datePickerService: $this->datePickerService
    );

    expect($component2->getLabelText())->toBe('Priority date');
});

it('uses provided label when specified', function () {
    $component = new DateInput(
        name: 'due_date',
        label: 'Custom Label',
        datePickerService: $this->datePickerService
    );

    expect($component->getLabelText())->toBe('Custom Label');
});

it('converts iso date value to display format', function () {
    $component = new DateInput(
        name: 'due_date',
        value: '2023-12-25',
        datePickerService: $this->datePickerService
    );

    expect($component->displayValue)->toBe('25/12/2023');
});

it('preserves display format values', function () {
    $component = new DateInput(
        name: 'due_date',
        value: '25/12/2023',
        datePickerService: $this->datePickerService
    );

    expect($component->displayValue)->toBe('25/12/2023');
});

it('handles empty values gracefully', function () {
    $component = new DateInput(
        name: 'due_date',
        value: null,
        datePickerService: $this->datePickerService
    );

    expect($component->displayValue)->toBe('');

    $component2 = new DateInput(
        name: 'due_date',
        value: '',
        datePickerService: $this->datePickerService
    );

    expect($component2->displayValue)->toBe('');
});

it('generates proper input attributes', function () {
    $component = new DateInput(
        name: 'due_date',
        value: '2023-12-25',
        required: true,
        placeholder: 'Select date',
        class: 'custom-class',
        datePickerService: $this->datePickerService
    );

    $attrs = $component->getInputAttributes();

    expect($attrs['type'])->toBe('text');
    expect($attrs['name'])->toBe('due_date');
    expect($attrs['value'])->toBe('25/12/2023');
    expect($attrs['required'])->toBe('required');
    expect($attrs['placeholder'])->toBe('Select date');
    expect($attrs['class'])->toContain('form-control');
    expect($attrs['class'])->toContain('date-picker-input');
    expect($attrs['class'])->toContain('custom-class');
    expect($attrs['autocomplete'])->toBe('off');
    expect($attrs['data-date-format'])->toBe('dd/mm/yyyy');
});

it('uses default placeholder when none provided', function () {
    $component = new DateInput(
        name: 'due_date',
        datePickerService: $this->datePickerService
    );

    expect($component->placeholder)->toBe('dd/mm/yyyy');
});

it('generates proper css classes', function () {
    $component = new DateInput(
        name: 'due_date',
        datePickerService: $this->datePickerService
    );

    expect($component->getInputClasses())->toBe('form-control date-picker-input');

    $component2 = new DateInput(
        name: 'due_date',
        class: 'custom-class another-class',
        datePickerService: $this->datePickerService
    );

    expect($component2->getInputClasses())->toBe('form-control date-picker-input custom-class another-class');
});

it('includes javascript configuration', function () {
    $component = new DateInput(
        name: 'due_date',
        datePickerService: $this->datePickerService
    );

    $config = $component->config;

    expect($config)->toBeArray();
    expect($config['dateFormat'])->toBe('Y-m-d');
    expect($config['altFormat'])->toBe('d/m/Y');
    expect($config['altInput'])->toBe(true);
    expect($config['allowInput'])->toBe(true);
});

it('accepts additional html attributes', function () {
    $component = new DateInput(
        name: 'due_date',
        inputAttributes: [
            'data-test' => 'value',
            'aria-label' => 'Date picker',
            'tabindex' => '1',
        ],
        datePickerService: $this->datePickerService
    );

    $attrs = $component->getInputAttributes();

    expect($attrs['data-test'])->toBe('value');
    expect($attrs['aria-label'])->toBe('Date picker');
    expect($attrs['tabindex'])->toBe('1');
});

it('generates unique ids for multiple instances', function () {
    $component1 = new DateInput(
        name: 'due_date',
        datePickerService: $this->datePickerService
    );

    $component2 = new DateInput(
        name: 'start_date',
        datePickerService: $this->datePickerService
    );

    expect($component1->id)->not->toBe($component2->id);
    expect($component1->id)->toStartWith('date-input-');
    expect($component2->id)->toStartWith('date-input-');
});

it('can use custom id from attributes', function () {
    $component = new DateInput(
        name: 'due_date',
        inputAttributes: ['id' => 'custom-id'],
        datePickerService: $this->datePickerService
    );

    expect($component->id)->toBe('custom-id');
});

it('renders the correct blade view', function () {
    $component = new DateInput(
        name: 'due_date',
        datePickerService: $this->datePickerService
    );

    $view = $component->render();
    expect($view)->toBeInstanceOf(\Illuminate\Contracts\View\View::class);
});

it('can hide labels when showLabel is false', function () {
    $component = new DateInput(
        name: 'due_date',
        showLabel: false,
        datePickerService: $this->datePickerService
    );

    expect($component->showLabel)->toBeFalse();
});

it('shows labels by default when showLabel is not specified', function () {
    $component = new DateInput(
        name: 'due_date',
        datePickerService: $this->datePickerService
    );

    expect($component->showLabel)->toBeTrue();
});

it('follows dependency injection principles', function () {
    $component = new DateInput('test_date');

    // Component should be able to resolve DatePickerService from container
    expect($component)->toBeInstanceOf(DateInput::class);

    // Check if it properly uses the interface
    $reflector = new ReflectionClass($component);
    $constructor = $reflector->getConstructor();
    $params = $constructor->getParameters();

    // Find the datePickerService parameter
    $serviceParam = collect($params)->first(fn ($param) => $param->getName() === 'datePickerService');
    expect($serviceParam->getType()?->getName())->toBe('App\Contracts\Services\DatePickerServiceInterface');
});
