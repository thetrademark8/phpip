<?php

namespace App\Services\Renewal;

use App\Contracts\Renewal\RenewalFeeCalculatorInterface;
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

    /**
     * Calculate fees for multiple renewals in batch
     * Optimized version to avoid N+1 queries
     *
     * @param  \Illuminate\Support\Collection  $renewals  Collection of renewal models
     * @return array Associative array of renewal_id => RenewalFeeDTO
     */
    public function calculateBatch(\Illuminate\Support\Collection $renewals): array
    {
        $results = [];

        // Pre-load all necessary data to avoid N+1 queries
        // This could be optimized further with eager loading if needed
        foreach ($renewals as $renewal) {
            $renewalDTO = RenewalDTO::fromModel($renewal);
            $results[$renewal->id] = $this->calculate($renewalDTO);
        }

        return $results;
    }

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

        // adjustTableFees()/adjustTaskFees() and the multiplication above already
        // applied these modifiers. Keep the metadata without mutating totals twice.
        $feeDTO->discount = (float) ($renewal->discount ?? 0);
        $feeDTO->feeFactor = $feeFactor;
        $feeDTO->gracePeriodApplied = $renewal->isInGracePeriod() && $feeFactor !== 1.0;

        return $feeDTO;
    }

    public function calculateWithDiscount(RenewalDTO $renewal, float $discount): RenewalFeeDTO
    {
        $renewalWithDiscount = clone $renewal;
        $renewalWithDiscount->discount = $discount;

        return $this->calculate($renewalWithDiscount);
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
        if ($renewal->clientId) {
            return $this->actorRepository->getVatRate($renewal->clientId);
        }

        return (float) config('renewal.invoice.vat_rate', 0.2);
    }

    private function calculateFeeFactor(RenewalDTO $renewal): float
    {
        if ($renewal->isInGracePeriod()
            && (!$renewal->doneDate || Carbon::parse($renewal->doneDate)->lt($renewal->dueDate))) {
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
        $discount = (float) ($renewal->discount ?? 0);

        if ($discount > 1) {
            $fee += $discount;
        } else {
            $fee += (1.0 - $discount) * $defaultFee;
        }
    }
}
