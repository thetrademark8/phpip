<?php

namespace App\Contracts\Renewal;

use App\DataTransferObjects\Renewal\ActionResultDTO;

interface RenewalInvoiceServiceInterface
{
    /**
     * Create invoices for selected renewals in Dolibarr
     *
     * @param  array  $ids  Array of renewal task IDs
     * @param  bool  $toInvoice  Create in Dolibarr, or record external invoicing when Dolibarr is disabled
     */
    public function createInvoices(array $ids, bool $toInvoice = true): ActionResultDTO;
}
