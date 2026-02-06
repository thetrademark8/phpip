<?php

namespace App\Services\TeamLeader\Handlers;

use App\Models\Actor;
use App\Models\TeamleaderReference;
use App\Services\TeamLeader\TeamLeaderService;
use Illuminate\Support\Facades\Log;

class CompanyHandler
{
    public static function added(array $data, TeamLeaderService $service): ?Actor
    {
        $response = $service->prepareRequest()->get(TeamLeaderService::BASE_URL . 'companies.info', [
            'id' => $data['id'],
        ]);

        if (!$response->successful()) {
            Log::error('TeamLeader: Failed to fetch company info', [
                'id' => $data['id'],
                'status' => $response->status(),
            ]);
            return null;
        }

        $companyData = $response->json()['data'] ?? null;

        if (!$companyData) {
            return null;
        }

        return self::upsert($companyData, $data['id']);
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

    public static function upsert(array $data, string $teamleaderId): Actor
    {
        $primaryEmail = collect($data['emails'] ?? [])->where('type', 'primary')->first();
        $primaryPhone = collect($data['telephones'] ?? [])->where('type', 'phone')->first();

        $fallbackAddress = isset($data['addresses'])
            ? collect($data['addresses'])->where('type', 'primary')->first()
            : null;

        $primaryAddress = $data['primary_address'] ?? ($fallbackAddress['address'] ?? null);

        // Truncate display_name to 30 characters (DB constraint)
        $displayName = mb_substr($data['name'], 0, 30);

        $actorData = [
            'name' => $data['name'],
            'display_name' => $displayName,
            'email' => $primaryEmail['email'] ?? null,
            'VAT_number' => $data['vat_number'] ?? null,
            'phone' => $primaryPhone['number'] ?? null,
            'language' => $data['language'] ?? null,
            'address' => self::formatAddress($primaryAddress),
            'phy_person' => false,
        ];

        $reference = TeamleaderReference::where('teamleader_id', $teamleaderId)->first();
        $actor = $reference?->actor;

        if (!$actor) {
            // Search using truncated display_name (exact match for unique constraint)
            $actor = Actor::where('display_name', $displayName)->first();

            // If not found by display_name, try by full name
            if (!$actor) {
                $actor = Actor::where('name', $data['name'])->first();
            }
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
