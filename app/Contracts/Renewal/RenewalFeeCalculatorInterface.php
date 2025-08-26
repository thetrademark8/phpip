<?php

namespace App\Contracts\Renewal;

use App\DataTransferObjects\Renewal\RenewalDTO;
use App\DataTransferObjects\Renewal\RenewalFeeDTO;

interface RenewalFeeCalculatorInterface
{
    /**
     * Calculate fees for a renewal
     */
    public function calculate(RenewalDTO $renewal): RenewalFeeDTO;

    /**
     * Calculate fees for a collection of renewals
     */
    public function calculateBatch(\Illuminate\Support\Collection $renewals): array;
}