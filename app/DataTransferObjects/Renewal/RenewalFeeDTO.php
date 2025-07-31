<?php

namespace App\DataTransferObjects\Renewal;

class RenewalFeeDTO
{
    public function __construct(
        public float $cost,
        public float $fee,
        public float $vatRate,
        public float $vat,
        public float $totalHt,
        public float $total,
        public float $discount = 0,
        public bool $gracePeriodApplied = false,
        public float $feeFactor = 1.0,
    ) {}

    public static function create(float $cost, float $fee, float $vatRate = 0.2): self
    {
        $vat = $fee * $vatRate;
        $totalHt = $cost + $fee;
        $total = $totalHt + $vat;

        return new self(
            cost: $cost,
            fee: $fee,
            vatRate: $vatRate,
            vat: $vat,
            totalHt: $totalHt,
            total: $total,
        );
    }

    public function applyDiscount(float $discount): self
    {
        if ($discount > 1) {
            // Fixed discount
            $this->fee = $discount;
        } else {
            // Percentage discount
            $this->fee *= (1.0 - $discount);
        }
        
        $this->discount = $discount;
        $this->recalculate();
        
        return $this;
    }

    public function applyGracePeriodFactor(float $factor): self
    {
        $this->fee *= $factor;
        $this->feeFactor = $factor;
        $this->gracePeriodApplied = true;
        $this->recalculate();
        
        return $this;
    }

    private function recalculate(): void
    {
        $this->vat = $this->fee * $this->vatRate;
        $this->totalHt = $this->cost + $this->fee;
        $this->total = $this->totalHt + $this->vat;
    }

    public function toArray(): array
    {
        return [
            'cost' => $this->cost,
            'fee' => $this->fee,
            'vat_rate' => $this->vatRate * 100, // Convert to percentage
            'vat' => $this->vat,
            'total_ht' => $this->totalHt,
            'total' => $this->total,
            'discount' => $this->discount,
            'grace_period_applied' => $this->gracePeriodApplied,
        ];
    }

    public function toFormattedArray(): array
    {
        return [
            'cost' => number_format($this->cost, 2, ',', ' '),
            'fee' => number_format($this->fee, 2, ',', ' '),
            'vat_rate' => $this->vatRate * 100,
            'tva' => $this->vat,
            'total_ht' => number_format($this->totalHt, 2, ',', ' '),
            'total' => number_format($this->total, 2, ',', ' '),
        ];
    }
}