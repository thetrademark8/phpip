<?php

namespace App\Services\TeamLeader\Handlers;

use App\Models\Actor;
use App\Models\Role;
use App\Models\TeamleaderReference;
use App\Services\TeamLeader\TeamLeaderService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ContactHandler
{
    public static function added(array $data, TeamLeaderService $service): ?Actor
    {
        $response = self::fetchContactWithRetry($data['id'], $service);

        if (!$response) {
            return null;
        }

        return self::upsert($response, $data['id'], $service);
    }

    public static function updated(array $data, TeamLeaderService $service): ?Actor
    {
        return self::added($data, $service);
    }

    public static function deleted(array $data, TeamLeaderService $service): bool
    {
        $reference = TeamleaderReference::where('teamleader_id', $data['id'])->first();

        if ($reference) {
            $actor = $reference->actor;
            $reference->delete();

            if ($actor) {
                $actor->delete();
            }

            return true;
        }

        return false;
    }

    public static function linkedToCompany(array $data, TeamLeaderService $service): ?Actor
    {
        return self::added($data, $service);
    }

    public static function unlinkedFromCompany(array $data, TeamLeaderService $service): ?Actor
    {
        return self::added($data, $service);
    }

    public static function upsert(array $data, string $teamleaderId, TeamLeaderService $service): Actor
    {
        $primaryEmail = collect($data['emails'] ?? [])->where('type', 'primary')->first();
        $primaryPhone = collect($data['telephones'] ?? [])->where('type', 'phone')->first();

        $fallbackAddress = isset($data['addresses'])
            ? collect($data['addresses'])->where('type', 'primary')->first()
            : null;

        $primaryAddress = $data['primary_address'] ?? ($fallbackAddress['address'] ?? null);

        $companies = $data['companies'] ?? [];

        // Truncate display_name to 30 characters (DB constraint)
        $fullDisplayName = trim(($data['last_name'] ?? '') . ' ' . ($data['first_name'] ?? ''));
        $displayName = mb_substr($fullDisplayName, 0, 30);

        $actorData = [
            'first_name' => $data['first_name'] ?? null,
            'name' => $data['last_name'] ?? null,
            'display_name' => $displayName,
            'email' => $primaryEmail['email'] ?? null,
            'VAT_number' => $data['vat_number'] ?? null,
            'phone' => $primaryPhone['number'] ?? null,
            'default_role' => Role::where('name', 'Contact')->first()?->code,
            'language' => $data['language'] ?? null,
            'address' => self::formatAddress($primaryAddress),
            'phy_person' => true,
        ];

        $reference = TeamleaderReference::where('teamleader_id', $teamleaderId)->first();
        $actor = $reference?->actor;

        if (!$actor) {
            // Search using truncated display_name (exact match for unique constraint)
            $actor = Actor::where('display_name', $displayName)->first();
        }

        try {
            if ($actor) {
                $actor->update($actorData);
            } else {
                $actor = Actor::create($actorData);
            }
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle duplicate key - find existing and update
            if ($e->getCode() === '23000') {
                $actor = Actor::where('display_name', $displayName)->first();
                if ($actor) {
                    $actor->update($actorData);
                } else {
                    throw $e;
                }
            } else {
                throw $e;
            }
        }

        if (count($companies)) {
            foreach ($companies as $entry) {
                $companyData = $entry['company'] ?? null;
                if (!$companyData) {
                    continue;
                }

                $companyReference = TeamleaderReference::where('teamleader_id', $companyData['id'])->first();

                if (!$companyReference) {
                    $companyResponse = $service->prepareRequest()->get(TeamLeaderService::BASE_URL . 'companies.info', [
                        'id' => $companyData['id'],
                    ]);

                    if ($companyResponse->successful()) {
                        $companyInfo = $companyResponse->json()['data'] ?? null;
                        if ($companyInfo) {
                            $company = CompanyHandler::upsert($companyInfo, $companyData['id']);
                            $companyReference = TeamleaderReference::where('teamleader_id', $companyData['id'])->first();
                        }
                    }
                }

                if ($companyReference && $companyReference->actor) {
                    $actor->function = $entry['position'] ?? null;
                    $actor->company_id = $companyReference->actor->id;
                    $actor->save();
                }
            }
        }

        if (!$reference) {
            TeamleaderReference::create([
                'teamleader_id' => $teamleaderId,
                'actor_id' => $actor->id,
            ]);
        } elseif ($reference->actor_id !== $actor->id) {
            $reference->update(['actor_id' => $actor->id]);
        }

        return $actor;
    }

    protected static function fetchContactWithRetry(string $id, TeamLeaderService $service): ?array
    {
        $maxRetries = 3;
        $retries = 0;

        while ($retries < $maxRetries) {
            $response = $service->prepareRequest()->get(TeamLeaderService::BASE_URL . 'contacts.info', [
                'id' => $id,
            ]);

            $json = $response->json();

            if (isset($json['data'])) {
                return $json['data'];
            }

            $limitRemaining = $response->header('X-RateLimit-Remaining');
            $limitReset = $response->header('X-RateLimit-Reset');

            if ($limitRemaining == 0 && $limitReset) {
                $resetTime = Carbon::parse($limitReset);
                $now = Carbon::now();
                $waitSeconds = max(0, $now->diffInSeconds($resetTime, false));

                if ($waitSeconds > 0 && $waitSeconds < 120) {
                    Log::info("TeamLeader: Rate limited, waiting {$waitSeconds} seconds");
                    sleep($waitSeconds);
                }

                $service->refreshAccessToken();
            }

            $retries++;
        }

        Log::error('TeamLeader: Failed to fetch contact info after retries', ['id' => $id]);
        return null;
    }

    protected static function formatAddress(?array $address): ?string
    {
        if (!$address) {
            return null;
        }

        $parts = array_filter([
            $address['line_1'] ?? null,
            $address['postal_code'] ?? null,
            $address['city'] ?? null,
            $address['country'] ?? null,
        ]);

        return implode(' ', $parts) ?: null;
    }
}
