<?php

namespace App\DataTransferObjects\Renewal;

use Illuminate\Http\Request;

class RenewalFilterDTO
{
    public function __construct(
        public ?int $step = null,
        public ?int $invoiceStep = null,
        public bool $myRenewals = false,
        public ?string $title = null,
        public ?string $case = null,
        public ?string $qt = null,
        public ?string $fromDate = null,
        public ?string $untilDate = null,
        public ?string $name = null,
        public ?string $country = null,
        public ?int $gracePeriod = null,
        public int $perPage = 25,
        public int $page = 1,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $filters = $request->except(['my_renewals', 'page', 'step', 'invoice_step']);
        
        return new self(
            step: $request->input('step'),
            invoiceStep: $request->input('invoice_step'),
            myRenewals: (bool) $request->input('my_renewals'),
            title: $filters['Title'] ?? null,
            case: $filters['Case'] ?? null,
            qt: $filters['Qt'] ?? null,
            fromDate: $filters['Fromdate'] ?? null,
            untilDate: $filters['Untildate'] ?? null,
            name: $filters['Name'] ?? null,
            country: $filters['Country'] ?? null,
            gracePeriod: isset($filters['grace_period']) ? (int) $filters['grace_period'] : null,
            perPage: (int) config('renewal.general.paginate', 25),
            page: (int) $request->input('page', 1),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'step' => $this->step,
            'invoice_step' => $this->invoiceStep,
            'my_renewals' => $this->myRenewals,
            'Title' => $this->title,
            'Case' => $this->case,
            'Qt' => $this->qt,
            'Fromdate' => $this->fromDate,
            'Untildate' => $this->untilDate,
            'Name' => $this->name,
            'Country' => $this->country,
            'grace_period' => $this->gracePeriod,
        ], fn($value) => $value !== null && $value !== false && $value !== '');
    }

    public function hasTextFilters(): bool
    {
        return !empty($this->title) || !empty($this->name);
    }

    public function hasStepFilter(): bool
    {
        return $this->step !== null;
    }

    public function hasInvoiceStepFilter(): bool
    {
        return $this->invoiceStep !== null;
    }
}