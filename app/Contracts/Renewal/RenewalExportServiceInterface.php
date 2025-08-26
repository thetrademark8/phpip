<?php

namespace App\Contracts\Renewal;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

interface RenewalExportServiceInterface
{
    /**
     * Export renewals to CSV format
     */
    public function exportToCsv(Collection $renewals): StreamedResponse;

    /**
     * Export renewals to XML format for payment orders
     */
    public function exportToXml(array $renewalIds, bool $markAsDone = false): StreamedResponse;

    /**
     * Generate invoice data for export
     */
    public function generateInvoiceData(Collection $renewals): array;

    /**
     * Validate renewals for XML export (single jurisdiction)
     */
    public function validateForXmlExport(Collection $renewals): bool;
}