<?php

namespace App\Http\Controllers;

use App\Contracts\Renewal\DolibarrClientInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class DolibarrThirdPartyController extends Controller
{
    public function __construct(
        private readonly DolibarrClientInterface $dolibarrClient,
    ) {}

    /**
     * Return a deliberately small representation of matching Dolibarr third parties.
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'term' => ['nullable', 'string', 'max:100'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $query = trim((string) ($validated['term'] ?? $validated['search'] ?? ''));

        // Do not expose an unfiltered customer directory.
        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        try {
            $thirdParties = $this->dolibarrClient->searchThirdParties($query);
        } catch (Throwable $exception) {
            Log::warning('Dolibarr third-party autocomplete failed', [
                'exception' => $exception::class,
            ]);

            return response()->json([
                'message' => __('dolibarr.errors.third_party_search_failed'),
            ], 502);
        }

        $options = collect($thirdParties)
            ->map(function (mixed $thirdParty): ?array {
                $id = data_get($thirdParty, 'id') ?? data_get($thirdParty, 'socid');
                $name = data_get($thirdParty, 'name') ?? data_get($thirdParty, 'nom');

                if (!is_numeric($id) || !is_string($name) || trim($name) === '') {
                    return null;
                }

                $customerCode = data_get($thirdParty, 'code_client')
                    ?? data_get($thirdParty, 'customer_code');
                $email = data_get($thirdParty, 'email');
                $identifiers = array_values(array_filter([
                    is_scalar($customerCode) ? (string) $customerCode : null,
                    is_scalar($email) ? (string) $email : null,
                ]));
                $label = trim($name);

                if ($identifiers !== []) {
                    $label .= ' · ' . implode(' · ', $identifiers);
                }

                return [
                    // AutocompleteInput consumes id/label/subtitle. value is also supplied
                    // so this endpoint remains compatible with the shared Combobox.
                    'id' => (int) $id,
                    'value' => (int) $id,
                    'name' => trim($name),
                    'label' => $label,
                    'subtitle' => '#' . (int) $id,
                    'code_client' => is_scalar($customerCode) ? (string) $customerCode : null,
                    'email' => is_scalar($email) ? (string) $email : null,
                ];
            })
            ->filter()
            ->values();

        return response()->json($options);
    }
}
