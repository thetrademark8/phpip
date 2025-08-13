<?php

namespace App\Services\Renewal;

use App\Services\Renewal\Contracts\RenewalInvoiceServiceInterface;
use App\Services\Renewal\Contracts\RenewalFeeCalculatorInterface;
use App\DataTransferObjects\Renewal\ServiceResultDTO;
use App\DataTransferObjects\Renewal\RenewalDTO;
use App\Repositories\Contracts\RenewalRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class RenewalInvoiceService implements RenewalInvoiceServiceInterface
{
    public function __construct(
        private RenewalRepositoryInterface $renewalRepository,
        private RenewalFeeCalculatorInterface $feeCalculator
    ) {}

    public function createInvoices(array $ids, bool $toInvoice = true): ServiceResultDTO
    {
        if (empty($ids)) {
            return new ServiceResultDTO(false, 'No renewal selected.');
        }

        $renewals = $this->renewalRepository->getRenewalsForInvoicing($ids);
        
        if ($renewals->isEmpty()) {
            return new ServiceResultDTO(false, 'No renewals found for invoicing.');
        }

        $num = 0;
        
        if (config('renewal.invoice.backend') === 'dolibarr' && $toInvoice) {
            $result = $this->processDolibarrInvoices($renewals);
            if (!$result['success']) {
                return new ServiceResultDTO(false, $result['error']);
            }
            $num = $result['count'];
        } else {
            $num = $renewals->count();
        }

        // Update invoice step to 2 (invoiced)
        $this->renewalRepository->updateInvoiceStep($ids, 2);

        return new ServiceResultDTO(true, "Invoices created for $num renewals");
    }

    public function searchClient(string $clientName): array
    {
        $apikey = config('renewal.api.DOLAPIKEY');
        if (!$apikey) {
            return ['error' => ['code' => 500, 'message' => 'API key not configured']];
        }

        $curl = curl_init();
        $httpheader = ['DOLAPIKEY: ' . $apikey];
        $data = ['sqlfilters' => '(t.nom:like:"' . $clientName . '%")'];
        $url = config('renewal.api.dolibarr_url') . '/thirdparties?' . http_build_query($data);
        
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $httpheader);
        
        $result = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $decoded = json_decode($result, true);
        
        if ($httpCode >= 400) {
            return ['error' => ['code' => $httpCode, 'message' => $decoded['error']['message'] ?? 'Client not found']];
        }

        return $decoded;
    }

    public function createDolibarrInvoice(array $invoiceData): array
    {
        $apikey = config('renewal.api.DOLAPIKEY');
        if (!$apikey) {
            return ['success' => false, 'error' => 'API key not configured'];
        }

        $curl = curl_init();
        $url = config('renewal.api.dolibarr_url') . '/invoices';
        $httpheader = [
            'DOLAPIKEY: ' . $apikey,
            'Content-Type: application/json'
        ];
        
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($invoiceData));
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $httpheader);
        
        $result = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        $decoded = json_decode($result, true);

        if (isset($decoded['error']) || $status >= 400) {
            return ['success' => false, 'error' => $decoded['error']['message'] ?? 'Invoice creation failed'];
        }

        return ['success' => true, 'data' => $decoded];
    }

    public function prepareInvoiceLines(array $renewals, array $clientInfo): array
    {
        $lines = [];
        
        // Determine VAT rate based on client's country
        $vatRate = ($clientInfo['tva_intra'] === '' || str_starts_with($clientInfo['tva_intra'], 'FR')) ? 0.2 : 0.0;

        foreach ($renewals as $renewal) {
            $renewalDTO = RenewalDTO::fromModel($renewal);
            $feeDTO = $this->feeCalculator->calculate($renewalDTO);

            // Create description
            $desc = "{$renewal->uid} : Annuité pour l'année {$renewal->detail} du titre {$renewal->number}";
            
            if ($renewal->event_name === 'FIL') {
                $desc .= ' déposé le ';
            } elseif (in_array($renewal->event_name, ['GRT', 'PR'])) {
                $desc .= ' délivré le ';
            }
            
            $desc .= Carbon::parse($renewal->event_date)->isoFormat('LL');
            $desc .= ' en ' . $renewal->country_FR;
            
            if ($renewal->title) {
                $desc .= "\nSujet : {$renewal->title}";
            }
            
            if ($renewal->client_ref) {
                $desc .= " ({$renewal->client_ref})";
            }
            
            $desc .= "\nÉchéance le " . Carbon::parse($renewal->due_date)->isoFormat('LL');

            // Add fee line
            if ($feeDTO->cost != 0) {
                $desc .= "\nHonoraires pour la surveillance et le paiement";
            } else {
                $desc .= "\nHonoraires et taxe";
            }

            $lines[] = [
                'desc' => $desc,
                'product_type' => 1,
                'tva_tx' => $vatRate * 100,
                'remise_percent' => 0,
                'qty' => 1,
                'subprice' => $feeDTO->fee,
                'total_tva' => $feeDTO->fee * $vatRate,
                'total_ttc' => $feeDTO->fee * (1.0 + $vatRate),
            ];

            // Add cost line if applicable
            if ($feeDTO->cost != 0) {
                $lines[] = [
                    'product_type' => 1,
                    'desc' => 'Taxe',
                    'tva_tx' => 0.0,
                    'remise_percent' => 0,
                    'qty' => 1,
                    'subprice' => $feeDTO->cost,
                    'total_tva' => 0,
                    'total_ttc' => $feeDTO->cost,
                ];
            }
        }

        return $lines;
    }

    private function processDolibarrInvoices($renewals): array
    {
        $apikey = config('renewal.api.DOLAPIKEY');
        if (!$apikey) {
            return ['success' => false, 'error' => 'API is not configured'];
        }

        Log::info('Facturation dans Dolibarr');
        
        $renewalsByClient = $renewals->groupBy('client_name');
        $processedCount = 0;

        foreach ($renewalsByClient as $clientName => $clientRenewals) {
            // Search for client in Dolibarr
            $clientResult = $this->searchClient($clientName);
            
            if (isset($clientResult['error']) && $clientResult['error']['code'] >= 404) {
                return ['success' => false, 'error' => "$clientName not found in Dolibarr"];
            }
            
            if (empty($clientResult)) {
                return ['success' => false, 'error' => "$clientName not found in Dolibarr"];
            }

            $clientInfo = $clientResult[0];
            
            // Prepare invoice lines
            $lines = $this->prepareInvoiceLines($clientRenewals->toArray(), $clientInfo);
            
            // Create invoice
            $invoiceData = [
                'socid' => $clientInfo['id'],
                'date' => time(),
                'cond_reglement_id' => 1,
                'mode_reglement_id' => 2,
                'lines' => $lines,
                'fk_account' => config('renewal.api.fk_account'),
            ];

            $result = $this->createDolibarrInvoice($invoiceData);
            
            if (!$result['success']) {
                return ['success' => false, 'error' => $result['error']];
            }

            $processedCount += $clientRenewals->count();
        }

        return ['success' => true, 'count' => $processedCount];
    }
}