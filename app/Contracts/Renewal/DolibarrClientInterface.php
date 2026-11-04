<?php

namespace App\Contracts\Renewal;

interface DolibarrClientInterface
{
    public function assertInvoiceExternalReferenceSupported(): void;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function searchThirdParties(string $query): array;

    /**
     * Fetch one zero-based page of customers (including customer/prospects).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listCustomerThirdParties(int $page = 0, int $limit = 100): array;

    /**
     * @return array<string, mixed>|null
     */
    public function getThirdParty(int $id): ?array;

    /**
     * @return array<string, mixed>|null
     */
    public function getInvoiceByExternalReference(string $refExt): ?array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createDraftInvoice(array $payload): array;
}
