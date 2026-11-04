<?php

namespace App\Services\Renewal;

use App\Contracts\Renewal\RenewalFeeCalculatorInterface;
use App\DataTransferObjects\Renewal\RenewalDTO;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class RenewalInvoiceLineBuilder
{
    public function __construct(
        private RenewalFeeCalculatorInterface $feeCalculator
    ) {}

    /**
     * @param  Collection<int, object>  $renewals
     * @return array<int, array<string, int|float|string>>
     */
    public function build(Collection $renewals): array
    {
        $lines = [];

        foreach ($renewals as $renewal) {
            $renewalDTO = RenewalDTO::fromModel($renewal);
            $feeDTO = $this->feeCalculator->calculate($renewalDTO);
            $language = $this->normalizeLanguage($renewalDTO->language);
            $description = $this->description($renewal, $renewalDTO, $language, $feeDTO->cost !== 0.0);

            $lines[] = [
                'desc' => $description,
                'product_type' => 1,
                'tva_tx' => $feeDTO->vatRate * 100,
                'remise_percent' => 0,
                'qty' => 1,
                'subprice' => $feeDTO->fee,
                'total_tva' => $feeDTO->vat,
                'total_ttc' => $feeDTO->fee + $feeDTO->vat,
            ];

            if ($feeDTO->cost !== 0.0) {
                $lines[] = [
                    'product_type' => 1,
                    'desc' => (string) config("renewal.description.$language.tax", 'Tax'),
                    'tva_tx' => 0.0,
                    'remise_percent' => 0,
                    'qty' => 1,
                    'subprice' => $feeDTO->cost,
                    'total_tva' => 0.0,
                    'total_ttc' => $feeDTO->cost,
                ];
            }
        }

        return $lines;
    }

    private function description(object $renewal, RenewalDTO $dto, string $language, bool $hasCost): string
    {
        if (!$dto->eventDate) {
            throw new InvalidArgumentException("Renewal {$dto->id} has no event date.");
        }

        $line1 = (string) config("renewal.description.$language.line1");
        $description = sprintf($line1, $dto->uid, $dto->detail, $dto->number ?? '');

        if ($dto->eventName === 'FIL') {
            $description .= (string) config("renewal.description.$language.filed");
        } elseif (in_array($dto->eventName, ['GRT', 'PR'], true)) {
            $description .= (string) config("renewal.description.$language.granted");
        }

        $description .= $this->localizedDate($dto->eventDate, $language);
        $description .= (string) config("renewal.description.$language.country_prefix", ' ');
        $description .= $this->countryName($renewal, $language, $dto->country);

        if ($dto->title) {
            $description .= "\n" . sprintf(
                (string) config("renewal.description.$language.subject"),
                $dto->title
            );
        }

        if ($dto->clientRef) {
            $description .= "\n" . sprintf(
                (string) config("renewal.description.$language.client_reference"),
                $dto->clientRef
            );
        }

        $description .= "\n" . sprintf(
            (string) config("renewal.description.$language.due_date"),
            $this->localizedDate($dto->dueDate, $language)
        );

        $description .= "\n" . (string) config(
            "renewal.description.$language." . ($hasCost ? 'fees' : 'fees_and_tax')
        );

        return $description;
    }

    private function normalizeLanguage(?string $language): string
    {
        $language = strtolower(substr((string) $language, 0, 2));

        return in_array($language, ['fr', 'en', 'de'], true) ? $language : 'fr';
    }

    private function localizedDate(CarbonInterface $date, string $language): string
    {
        return $date->copy()->locale($language)->isoFormat('LL');
    }

    private function countryName(object $renewal, string $language, string $fallback): string
    {
        $attribute = match ($language) {
            'en' => 'country_EN',
            'de' => 'country_DE',
            default => 'country_FR',
        };

        return (string) ($renewal->{$attribute} ?? $fallback);
    }
}
