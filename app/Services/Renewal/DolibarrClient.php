<?php

namespace App\Services\Renewal;

use App\Contracts\Renewal\DolibarrClientInterface;
use App\Exceptions\Dolibarr\DolibarrConfigurationException;
use App\Exceptions\Dolibarr\DolibarrRequestException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use JsonException;
use Throwable;

class DolibarrClient implements DolibarrClientInterface
{
    public function assertInvoiceExternalReferenceSupported(): void
    {
        $operation = 'check Dolibarr compatibility';
        $response = $this->sendGet($operation, 'status', []);
        $decoded = $this->decode($response, $operation);
        $version = is_array($decoded) ? data_get($decoded, 'success.dolibarr_version') : null;

        if (!is_string($version) || $version === '') {
            throw DolibarrRequestException::invalidResponse($operation, $response->status());
        }

        if (version_compare($version, '12.0.0', '<')) {
            throw DolibarrConfigurationException::unsupportedVersion($version);
        }
    }

    public function searchThirdParties(string $query): array
    {
        $query = trim($query);

        if ($query === '') {
            throw new InvalidArgumentException('The Dolibarr search query cannot be empty.');
        }

        $response = $this->performGet('search third parties', 'thirdparties', [
            'mode' => 1,
            'sqlfilters' => sprintf('(t.nom:like:"%%%s%%")', $this->escapeFilterValue($query)),
        ]);

        if ($this->isEmptyCustomerResponse($response)) {
            return [];
        }

        $this->guardResponse($response, 'search third parties');

        return $this->normaliseList($response, 'search third parties');
    }

    public function listCustomerThirdParties(int $page = 0, int $limit = 100): array
    {
        if ($page < 0 || $limit < 1 || $limit > 1000) {
            throw new InvalidArgumentException('Dolibarr pagination requires a non-negative page and a limit between 1 and 1000.');
        }

        $operation = 'list customer third parties';
        $response = $this->performGet($operation, 'thirdparties', [
            'mode' => 1,
            'sortfield' => 't.rowid',
            'sortorder' => 'ASC',
            'page' => $page,
            'limit' => $limit,
        ]);

        // Dolibarr returns 404 for empty pages. Do not hide an invalid API URL
        // or a missing route by treating every 404 as an empty directory.
        if ($this->isEmptyCustomerResponse($response)) {
            return [];
        }

        $this->guardResponse($response, $operation);
        $customers = $this->decode($response, $operation);

        if (!is_array($customers) || !array_is_list($customers)) {
            throw DolibarrRequestException::invalidResponse($operation, $response->status());
        }

        foreach ($customers as $customer) {
            if (!is_array($customer) || array_is_list($customer)) {
                throw DolibarrRequestException::invalidResponse($operation, $response->status());
            }
        }

        return $customers;
    }

    private function isEmptyCustomerResponse(Response $response): bool
    {
        return $response->status() === 404 && in_array(data_get($response->json(), 'error.message'), [
            'Thirdparties not found',
            'No customers found',
        ], true);
    }

    public function getThirdParty(int $id): ?array
    {
        if ($id < 1) {
            throw new InvalidArgumentException('The Dolibarr third-party ID must be positive.');
        }

        $operation = 'get third party';
        $response = $this->sendGetAllowingNotFound($operation, 'thirdparties/' . $id);

        if ($response === null) {
            return null;
        }

        $customer = $this->decode($response, $operation);

        if (!is_array($customer) || array_is_list($customer)
            || !isset($customer['id']) || !(is_int($customer['id']) || is_string($customer['id']))
            || !ctype_digit((string) $customer['id'])
            || (int) $customer['id'] !== $id) {
            throw DolibarrRequestException::invalidResponse($operation, $response->status());
        }

        return $customer;
    }

    public function getInvoiceByExternalReference(string $refExt): ?array
    {
        $refExt = trim($refExt);

        if ($refExt === '') {
            throw new InvalidArgumentException('The Dolibarr external reference cannot be empty.');
        }

        $operation = 'find invoice by external reference';
        $response = $this->sendGetAllowingNotFound(
            $operation,
            'invoices/ref_ext/' . rawurlencode($refExt),
        );

        if ($response === null) {
            return null;
        }

        $invoices = $this->normaliseList($response, $operation);

        return $invoices[0] ?? null;
    }

    public function createDraftInvoice(array $payload): array
    {
        $operation = 'create draft invoice';
        $request = $this->request();

        try {
            $response = $request->post('invoices', $payload);
        } catch (ConnectionException) {
            throw DolibarrRequestException::connection($operation);
        }

        $this->guardResponse($response, $operation);
        $decoded = $this->decode($response, $operation);

        if (is_int($decoded) || (is_string($decoded) && ctype_digit($decoded))) {
            return ['id' => (int) $decoded];
        }

        if (!is_array($decoded) || array_is_list($decoded)) {
            throw DolibarrRequestException::invalidResponse($operation, $response->status());
        }

        if (!array_key_exists('id', $decoded) && array_key_exists('rowid', $decoded)) {
            $decoded['id'] = $decoded['rowid'];
        }

        if (!isset($decoded['id']) || !is_numeric($decoded['id'])) {
            throw DolibarrRequestException::invalidResponse($operation, $response->status());
        }

        $decoded['id'] = (int) $decoded['id'];

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function sendGet(string $operation, string $path, array $query): Response
    {
        $response = $this->performGet($operation, $path, $query);
        $this->guardResponse($response, $operation);

        return $response;
    }

    private function sendGetAllowingNotFound(string $operation, string $path): ?Response
    {
        $response = $this->performGet($operation, $path, []);

        if ($response->status() === 404) {
            return null;
        }

        $this->guardResponse($response, $operation);

        return $response;
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function performGet(string $operation, string $path, array $query): Response
    {
        $request = $this->request();
        $attempts = max(1, (int) config('renewal.api.retry_times', 2));

        if ($attempts > 1) {
            $request->retry(
                $attempts,
                max(0, (int) config('renewal.api.retry_sleep_ms', 100)),
                static function (Throwable $exception): bool {
                    if ($exception instanceof ConnectionException) {
                        return true;
                    }

                    if (!$exception instanceof RequestException) {
                        return false;
                    }

                    $status = $exception->response->status();

                    return $status === 429 || $status >= 500;
                },
                throw: false,
            );
        }

        try {
            $response = $request->get($path, $query);
        } catch (ConnectionException) {
            throw DolibarrRequestException::connection($operation);
        }

        return $response;
    }

    private function request(): PendingRequest
    {
        $url = trim((string) config('renewal.api.dolibarr_url'));
        $apiKey = trim((string) config('renewal.api.DOLAPIKEY'));

        if ($url === '') {
            throw DolibarrConfigurationException::missing('DOLIBARR_URL');
        }

        if (!filter_var($url, FILTER_VALIDATE_URL) || !in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
            throw DolibarrConfigurationException::invalidUrl();
        }

        if ($apiKey === '') {
            throw DolibarrConfigurationException::missing('DOLIBARR_API_KEY');
        }

        return Http::baseUrl(rtrim($url, '/'))
            ->acceptJson()
            ->asJson()
            ->withHeaders(['DOLAPIKEY' => $apiKey])
            ->connectTimeout(max(0.1, (float) config('renewal.api.connect_timeout', 5)))
            ->timeout(max(0.1, (float) config('renewal.api.timeout', 15)));
    }

    private function guardResponse(Response $response, string $operation): void
    {
        if ($response->failed()) {
            throw DolibarrRequestException::http($operation, $response->status());
        }

        $decoded = $this->decode($response, $operation);

        if (is_array($decoded) && array_key_exists('error', $decoded)) {
            throw DolibarrRequestException::http($operation, $response->status());
        }
    }

    private function decode(Response $response, string $operation): mixed
    {
        try {
            return json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw DolibarrRequestException::invalidResponse($operation, $response->status());
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normaliseList(Response $response, string $operation): array
    {
        $decoded = $this->decode($response, $operation);

        if ($decoded === []) {
            return [];
        }

        if (!is_array($decoded)) {
            throw DolibarrRequestException::invalidResponse($operation, $response->status());
        }

        if (!array_is_list($decoded)) {
            return [$decoded];
        }

        foreach ($decoded as $item) {
            if (!is_array($item) || array_is_list($item)) {
                throw DolibarrRequestException::invalidResponse($operation, $response->status());
            }
        }

        return array_values($decoded);
    }

    private function escapeFilterValue(string $value): string
    {
        return str_replace(
            ['\\', '"', '%', '_'],
            ['\\\\', '\\"', '\\%', '\\_'],
            $value,
        );
    }
}
