<?php

namespace App\Services\Renewal\Contracts;

use App\DataTransferObjects\Renewal\ServiceResultDTO;

interface RenewalInvoiceServiceInterface
{
    /**
     * Create invoices for selected renewals in Dolibarr
     *
     * @param array $ids Array of renewal task IDs
     * @param bool $toInvoice Whether to actually create invoices or just validate
     * @return ServiceResultDTO
     */
    public function createInvoices(array $ids, bool $toInvoice = true): ServiceResultDTO;

    /**
     * Search for a client in Dolibarr by name
     *
     * @param string $clientName Client name to search for
     * @return array Dolibarr API response
     */
    public function searchClient(string $clientName): array;

    /**
     * Create invoice in Dolibarr via API
     *
     * @param array $invoiceData Invoice data structure
     * @return array Result with status and response
     */
    public function createDolibarrInvoice(array $invoiceData): array;

    /**
     * Prepare invoice lines from renewals data
     *
     * @param array $renewals Renewal models with fee calculations
     * @param array $clientInfo Client information from Dolibarr
     * @return array Invoice lines structure
     */
    public function prepareInvoiceLines(array $renewals, array $clientInfo): array;
}