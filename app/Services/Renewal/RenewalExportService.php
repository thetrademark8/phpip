<?php

namespace App\Services\Renewal;

use App\Services\Renewal\Contracts\RenewalExportServiceInterface;
use App\Services\Renewal\Contracts\RenewalFeeCalculatorInterface;
use App\DataTransferObjects\Renewal\RenewalDTO;
use App\Repositories\Contracts\RenewalRepositoryInterface;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RenewalExportService implements RenewalExportServiceInterface
{
    public function __construct(
        private RenewalRepositoryInterface $renewalRepository,
        private RenewalFeeCalculatorInterface $feeCalculator
    ) {}

    public function exportToCsv(Collection $renewals): StreamedResponse
    {
        // Transform renewals to include calculated fees
        $renewals->transform(function ($renewal) {
            $renewalDTO = RenewalDTO::fromModel($renewal);
            $feeDTO = $this->feeCalculator->calculate($renewalDTO);
            
            $renewal->cost = $feeDTO->cost;
            $renewal->fee = $feeDTO->fee;
            
            return $renewal;
        });

        // Get export captions from config
        $captions = config('renewal.invoice.captions', [
            'ID', 'Case', 'Client', 'Title', 'Due Date', 'Cost', 'Fee', 'Total'
        ]);

        $filename = now()->format('YmdHis') . '_invoicing.csv';

        return response()->streamDownload(function () use ($renewals, $captions) {
            $output = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Write headers
            fputcsv($output, $captions, ';');
            
            // Write data rows
            foreach ($renewals as $renewal) {
                $row = $this->prepareExportRow($renewal);
                fputcsv($output, array_map('utf8_decode', $row), ';');
            }
            
            fclose($output);
        }, $filename, [
            'Content-Type' => 'application/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ]);
    }

    public function exportToXml(array $renewalIds, bool $markAsDone = false): StreamedResponse
    {
        $renewals = $this->renewalRepository->findByIds($renewalIds);
        
        if (!$this->validateForXmlExport($renewals)) {
            throw new \InvalidArgumentException('XML export requires all renewals to be from the same jurisdiction');
        }
        
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><payments></payments>');
        
        // Group renewals by client
        $groupedRenewals = $renewals->groupBy('client_id');
        
        foreach ($groupedRenewals as $clientId => $clientRenewals) {
            $payment = $xml->addChild('payment');
            $payment->addChild('client_id', $clientId);
            
            $total = 0;
            foreach ($clientRenewals as $renewal) {
                $item = $payment->addChild('renewal');
                $item->addChild('id', $renewal->id);
                $item->addChild('caseref', $renewal->caseref);
                $item->addChild('detail', $renewal->detail);
                $item->addChild('due_date', $renewal->due_date);
                
                $renewalDTO = RenewalDTO::fromModel($renewal);
                $feeDTO = $this->feeCalculator->calculate($renewalDTO);
                
                $item->addChild('cost', $feeDTO->cost);
                $item->addChild('fee', $feeDTO->fee);
                $item->addChild('total', $feeDTO->total);
                
                $total += $feeDTO->total;
            }
            
            $payment->addChild('total_amount', $total);
        }
        
        if ($markAsDone) {
            // Mark renewals as done after successful export
            $this->renewalRepository->markAsDone($renewalIds, now()->toDateString());
        }
        
        $filename = 'payment_' . now()->format('YmdHis') . '.xml';
        
        return response()->streamDownload(
            function() use ($xml) {
                echo $xml->asXML();
            },
            $filename,
            ['Content-Type' => 'text/xml']
        );
    }
    
    public function generateInvoiceData(Collection $renewals): array
    {
        $invoiceData = [];
        
        foreach ($renewals as $renewal) {
            $renewalDTO = RenewalDTO::fromModel($renewal);
            $feeDTO = $this->feeCalculator->calculate($renewalDTO);
            
            $invoiceData[] = [
                'renewal_id' => $renewal->id,
                'caseref' => $renewal->caseref,
                'client_name' => $renewal->client_name,
                'client_id' => $renewal->client_id,
                'title' => $renewal->title,
                'due_date' => $renewal->due_date,
                'cost' => $feeDTO->cost,
                'fee' => $feeDTO->fee,
                'vat' => $feeDTO->vat,
                'total' => $feeDTO->total,
                'country' => $renewal->country,
                'detail' => $renewal->detail,
                'grace_period' => $renewal->grace_period,
            ];
        }
        
        return $invoiceData;
    }
    
    public function validateForXmlExport(Collection $renewals): bool
    {
        if ($renewals->isEmpty()) {
            return false;
        }
        
        // Check if all renewals are from the same country/jurisdiction
        $countries = $renewals->pluck('country')->unique();
        
        return $countries->count() === 1;
    }

    private function prepareExportRow($renewal): array
    {
        return [
            $renewal->id,
            $renewal->caseref ?? '',
            $renewal->client_name ?? '',
            $renewal->title ?? '',
            $renewal->due_date ?? '',
            number_format($renewal->cost, 2, ',', ' '),
            number_format($renewal->fee, 2, ',', ' '),
            number_format($renewal->cost + $renewal->fee, 2, ',', ' '),
            $renewal->country ?? '',
            $renewal->detail ?? '',
            $renewal->step ?? '',
            $renewal->invoice_step ?? '',
            $renewal->assigned_to ?? '',
            $renewal->created_at ?? '',
            $renewal->updated_at ?? '',
        ];
    }
}