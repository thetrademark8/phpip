<?php

namespace App\Services\Renewal;

use App\Services\Renewal\Contracts\RenewalFeeCalculatorInterface;
use App\DataTransferObjects\Renewal\RenewalDTO;
use App\DataTransferObjects\Renewal\RenewalFeeDTO;
use App\Repositories\Contracts\ActorRepositoryInterface;
use App\Repositories\Contracts\RenewalRepositoryInterface;
use Carbon\Carbon;

class RenewalFeeCalculatorService implements RenewalFeeCalculatorInterface
{
    public function __construct(
        private ActorRepositoryInterface $actorRepository,
        private RenewalRepositoryInterface $renewalRepository
    ) {}

    public function calculate(RenewalDTO $renewal): RenewalFeeDTO
    {
        // Get base cost and fee
        $cost = $renewal->cost;
        $fee = $renewal->fee;

        // Apply fee factor for grace period
        $feeFactor = $this->calculateFeeFactor($renewal);

        // Adjust fees based on table fee or task fee
        if ($renewal->tableFee) {
            $this->adjustTableFees($renewal, $cost, $fee);
        } else {
            $this->adjustTaskFees($renewal, $cost, $fee);
        }

        // Apply fee factor
        $fee *= $feeFactor;

        // Get VAT rate
        $vatRate = $this->getVatRate($renewal);

        // Create fee DTO
        $feeDTO = RenewalFeeDTO::create($cost, $fee, $vatRate);

        // Apply discount if any
        if ($renewal->discount) {
            $feeDTO->applyDiscount($renewal->discount);
        }

        // Apply grace period factor if applicable
        if ($renewal->isInGracePeriod() && $feeFactor !== 1.0) {
            $feeDTO->applyGracePeriodFactor($feeFactor);
        }

        return $feeDTO;
    }

    public function calculateBatch(array $renewals): array
    {
        $results = [];
        
        foreach ($renewals as $renewal) {
            if ($renewal instanceof RenewalDTO) {
                $results[$renewal->id] = $this->calculate($renewal);
            } else {
                $results[$renewal->id] = $this->calculate(RenewalDTO::fromModel($renewal));
            }
        }
        
        return $results;
    }

    public function calculateWithDiscount(RenewalDTO $renewal, float $discount): RenewalFeeDTO
    {
        $feeDTO = $this->calculate($renewal);
        
        if ($discount > 0) {
            $feeDTO->applyDiscount($discount);
        }
        
        return $feeDTO;
    }

    public function calculateForClient(int $clientId, array $renewalIds): array
    {
        // Get client discount
        $discount = $this->actorRepository->getDiscount($clientId) ?? 0;
        
        // Get renewals
        $renewals = $this->renewalRepository->findByIds($renewalIds);
        
        $results = [];
        foreach ($renewals as $renewal) {
            $renewalDTO = RenewalDTO::fromModel($renewal);
            $results[$renewal->id] = $this->calculateWithDiscount($renewalDTO, $discount);
        }
        
        return $results;
    }

    public function getFeeFactor(RenewalDTO $renewal): float
    {
        return $this->calculateFeeFactor($renewal);
    }

    public function getVatRate(RenewalDTO $renewal): float
    {
        // Default VAT rate
        $vatRate = config('renewal.invoice.default_vat_rate', 0.2);
        
        // Check if client has specific VAT rate
        if ($renewal->clientId) {
            $clientVatRate = $this->actorRepository->getVatRate($renewal->clientId);
            if ($clientVatRate !== null) {
                $vatRate = $clientVatRate;
            }
        }
        
        return $vatRate;
    }

    public function adjustForGracePeriod(RenewalFeeDTO $fee, bool $gracePeriod, ?string $doneDate = null): RenewalFeeDTO
    {
        if ($gracePeriod && $doneDate && Carbon::parse($doneDate)->lt(Carbon::now())) {
            $feeFactor = config('renewal.validity.fee_factor', 1.0);
            return $fee->applyGracePeriodFactor($feeFactor);
        }
        
        return $fee;
    }

    public function applyDiscount(RenewalFeeDTO $fee, float $discount): RenewalFeeDTO
    {
        return $fee->applyDiscount($discount);
    }

    private function calculateFeeFactor(RenewalDTO $renewal): float
    {
        if ($renewal->isInGracePeriod() && $renewal->doneDate && Carbon::parse($renewal->doneDate)->lt($renewal->dueDate)) {
            return config('renewal.validity.fee_factor', 1.0);
        }
        
        return 1.0;
    }

    private function adjustTableFees(RenewalDTO $renewal, &$cost, &$fee): void
    {
        if ($renewal->isInGracePeriod()) {
            $cost = $renewal->smeStatus ? ($renewal->costSupReduced ?? $renewal->costSup) : $renewal->costSup;
            $fee = $renewal->smeStatus ? ($renewal->feeSupReduced ?? $renewal->feeSup) : $renewal->feeSup;
        } else {
            $cost = $renewal->smeStatus ? ($renewal->costReduced ?? $renewal->cost) : $renewal->cost;
            $fee = $renewal->smeStatus ? ($renewal->feeReduced ?? $renewal->fee) : $renewal->fee;
        }

        // Apply discount
        if ($renewal->discount) {
            if ($renewal->discount > 1) {
                $fee = $renewal->discount;
            } else {
                $fee *= (1.0 - $renewal->discount);
            }
        }
    }

    private function adjustTaskFees(RenewalDTO $renewal, &$cost, &$fee): void
    {
        $defaultFee = config('renewal.invoice.default_fee', 145);
        $cost = $renewal->cost;
        $fee = $renewal->fee - $defaultFee;

        // Apply discount
        if ($renewal->discount) {
            if ($renewal->discount > 1) {
                $fee += $renewal->discount;
            } else {
                $fee += (1.0 - $renewal->discount) * $defaultFee;
            }
        }
    }
}