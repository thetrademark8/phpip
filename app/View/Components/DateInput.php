<?php

namespace App\View\Components;

use App\Contracts\Services\DatePickerServiceInterface;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class DateInput extends Component
{
    public string $id;

    public string $displayValue;

    public array $config;

    private DatePickerServiceInterface $datePickerService;

    private array $inputAttributes;

    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $name,
        public mixed $value = null,
        public ?string $label = null,
        public bool $required = false,
        public ?string $placeholder = null,
        public ?string $class = null,
        public ?string $helpText = null,
        public bool $showLabel = true,
        array $inputAttributes = [],
        ?DatePickerServiceInterface $datePickerService = null
    ) {
        $this->datePickerService = $datePickerService ?? app(DatePickerServiceInterface::class);
        $this->inputAttributes = $inputAttributes;

        // Generate unique ID if not provided
        $this->id = $inputAttributes['id'] ?? 'date-input-'.uniqid();

        // Convert value to display format
        $this->displayValue = $this->convertValueForDisplay($value);

        // Get JavaScript configuration
        $this->config = $this->datePickerService->getJavaScriptConfig();

        // Set default placeholder
        $this->placeholder = $placeholder ?? $this->datePickerService->getDisplayFormat();
    }

    /**
     * Convert the value to display format for the user
     */
    private function convertValueForDisplay(mixed $value): string
    {
        if (empty($value)) {
            return '';
        }

        // If it's already in display format, keep it
        if ($this->datePickerService->isValidDate($value)) {
            $displayValue = $this->datePickerService->convertToDisplay($value);

            return $displayValue ?? $value;
        }

        return $value;
    }

    /**
     * Get the label text for the input
     */
    public function getLabelText(): string
    {
        if ($this->label !== null) {
            return $this->label;
        }

        // Generate label from field name
        return ucfirst(str_replace(['_', '-'], ' ', $this->name));
    }

    /**
     * Get CSS classes for the input
     */
    public function getInputClasses(): string
    {
        $baseClasses = 'form-control date-picker-input';

        if ($this->class) {
            return $baseClasses.' '.$this->class;
        }

        return $baseClasses;
    }

    /**
     * Get additional HTML attributes for the input
     */
    public function getInputAttributes(): array
    {
        $attrs = array_merge([
            'type' => 'text',
            'id' => $this->id,
            'name' => $this->name,
            'value' => $this->displayValue,
            'placeholder' => $this->placeholder,
            'class' => $this->getInputClasses(),
            'autocomplete' => 'off',
            'data-date-format' => $this->datePickerService->getDisplayFormat(),
        ], $this->inputAttributes);

        if ($this->required) {
            $attrs['required'] = 'required';
        }

        // Remove our component-specific attributes that shouldn't be on the HTML element
        unset($attrs['label'], $attrs['helpText']);

        return $attrs;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.date-input');
    }
}
