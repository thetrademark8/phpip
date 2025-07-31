<?php

namespace App\Services\Renewal\Contracts;

use App\DataTransferObjects\Renewal\RenewalDTO;
use App\DataTransferObjects\Renewal\RenewalFeeDTO;

interface RenewalFeeCalculatorInterface
{
    /**
     * Calculate fees for a renewal
     */
    public function calculate(RenewalDTO $renewal): RenewalFeeDTO;

    /**
     * Adjust fees for grace period
     */
    public function adjustForGracePeriod(RenewalFeeDTO $fee, bool $gracePeriod, ?string $doneDate = null): RenewalFeeDTO;

    /**
     * Apply discount to fees
     */
    public function applyDiscount(RenewalFeeDTO $fee, float $discount): RenewalFeeDTO;

    /**
     * Calculate fees for a collection of renewals
     */
    public function calculateBatch(array $renewals): array;
}