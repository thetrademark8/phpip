<?php

namespace App\Services\Dolibarr;

use App\Contracts\Renewal\DolibarrClientInterface;
use App\Models\Actor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DolibarrActorSyncService
{
    public function __construct(private readonly DolibarrClientInterface $client) {}

    /**
     * Nonempty Dolibarr coordinates are authoritative. Local roles, relationships,
     * display names, billing addresses and credentials are never imported.
     *
     * @return array{status: string, remote_id: ?int, actor_id: ?int, reason: ?string}
     */
    public function sync(array $thirdParty, bool $dryRun = false): array
    {
        return $this->apply($thirdParty, $dryRun);
    }

    /**
     * An explicit association resolves an initial-import conflict. Fetch the
     * remote record first so arbitrary or non-customer IDs cannot be linked.
     *
     * @return array{status: string, remote_id: ?int, actor_id: ?int, reason: ?string}
     */
    public function link(int $socid, int $actorId, bool $dryRun = false): array
    {
        if ($socid < 1 || $actorId < 1) {
            return $this->result('invalid', null, null, 'invalid_id');
        }

        $thirdParty = $this->client->getThirdParty($socid);

        if ($thirdParty === null) {
            return $this->result('invalid', $socid, $actorId, 'third_party_not_found');
        }

        if ($this->remoteId($thirdParty) !== $socid) {
            return $this->result('invalid', $socid, $actorId, 'third_party_id_mismatch');
        }

        return $this->apply($thirdParty, $dryRun, $actorId);
    }

    private function apply(array $thirdParty, bool $dryRun, ?int $explicitActorId = null): array
    {
        $socid = $this->remoteId($thirdParty);

        if ($socid === null) {
            return $this->result('invalid', null, $explicitActorId, 'invalid_id');
        }

        if (!in_array($thirdParty['client'] ?? null, [1, 3, '1', '3'], true)) {
            return $this->result('invalid', $socid, $explicitActorId, 'not_customer');
        }

        $data = $this->map($thirdParty);

        if ($data === null) {
            return $this->result('invalid', $socid, $explicitActorId, 'invalid_coordinates');
        }

        return DB::transaction(function () use ($socid, $data, $dryRun, $explicitActorId): array {
            $actor = Actor::where('dolibarr_socid', $socid)->lockForUpdate()->first();

            if ($explicitActorId !== null) {
                if ($actor !== null && $actor->id !== $explicitActorId) {
                    return $this->result('conflict', $socid, $actor->id, 'third_party_already_linked');
                }

                $actor = Actor::whereKey($explicitActorId)->lockForUpdate()->first();

                if ($actor === null) {
                    return $this->result('invalid', $socid, $explicitActorId, 'actor_not_found');
                }

                if ($actor->dolibarr_socid !== null && $actor->dolibarr_socid !== $socid) {
                    return $this->result('conflict', $socid, $actor->id, 'actor_already_linked');
                }
            }

            if ($actor !== null && (filled($actor->login) || in_array($actor->default_role, ['DBA', 'DBRW', 'DBRO'], true))) {
                return $this->result('conflict', $socid, $actor->id, 'protected_actor');
            }

            if ($actor !== null && ($data['phy_person'] ?? $actor->phy_person) && filled($actor->first_name)) {
                $knownNames = [$actor->name, $actor->name . ' ' . $actor->first_name, $actor->first_name . ' ' . $actor->name];

                if (!in_array(self::normalizeIdentity($data['name']), array_map(self::normalizeIdentity(...), $knownNames), true)) {
                    return $this->result('conflict', $socid, $actor->id, 'individual_name_requires_review');
                }

                // Dolibarr's third-party name can contain the full name, while
                // phpIP stores the surname and given name separately.
                $data['name'] = $actor->name;
            }

            if ($actor === null) {
                $candidates = $this->candidates($data);

                if ($candidates !== []) {
                    return $this->result('conflict', $socid, count($candidates) === 1 ? $candidates[0] : null, 'existing_actor_requires_link')
                        + ['candidate_ids' => $candidates];
                }

                $actor = new Actor;
                $actor->ren_discount = 0;
                $actor->phy_person = 0;
            }

            $new = !$actor->exists;
            $actor->fill($data);
            $actor->dolibarr_socid = $socid;
            $status = $new ? 'created' : ($actor->isDirty() ? 'updated' : 'unchanged');

            if (!$dryRun && $status !== 'unchanged') {
                $actor->save();
            }

            return $this->result($status, $socid, $actor->id);
        });
    }

    /** @return array<string, mixed>|null */
    private function map(array $thirdParty): ?array
    {
        $data = [];
        $fields = [
            'name' => 'name',
            'email' => 'email',
            'phone' => 'phone',
            'country_code' => 'country',
            'tva_intra' => 'VAT_number',
            'idprof1' => 'registration_no',
        ];

        foreach ($fields as $remote => $local) {
            $value = $thirdParty[$remote] ?? ($remote === 'name' ? ($thirdParty['nom'] ?? null) : null);

            if ($value !== null && !is_string($value) && !is_int($value)) {
                return null;
            }

            if ($value !== null && trim((string) $value) !== '') {
                $data[$local] = trim((string) $value);
            }
        }

        $addressParts = [];

        foreach (['address', 'zip', 'town'] as $field) {
            $value = $thirdParty[$field] ?? null;

            if ($value !== null && !is_string($value) && !is_int($value)) {
                return null;
            }

            if ($value !== null && trim((string) $value) !== '') {
                $addressParts[] = trim((string) $value);
            }
        }

        if ($addressParts !== []) {
            $data['address'] = implode("\n", $addressParts);
        }

        if (isset($data['country'])) {
            $data['country'] = strtoupper($data['country']);
        }

        $language = $thirdParty['default_lang'] ?? null;

        if ($language !== null && $language !== '' && $language !== 'auto') {
            if (!is_string($language) || !preg_match('/^[a-z]{2}(?:[_-][a-z]{2})?$/i', $language)) {
                return null;
            }

            $data['language'] = strtolower(substr($language, 0, 2));
        }

        // Dolibarr 12 does not consistently populate particulier on fetch.
        // typent_code is the stored third-party classification; unknown values
        // must not change an existing actor's physical/legal-person setting.
        $type = $thirdParty['typent_code'] ?? null;

        if (in_array($thirdParty['particulier'] ?? null, [1, '1', true], true)) {
            $data['phy_person'] = 1;
        } elseif (is_string($type) && str_starts_with($type, 'TE_') && $type !== 'TE_UNKNOWN') {
            $data['phy_person'] = (int) str_starts_with($type, 'TE_PRIVATE');
        }

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['sometimes', 'email', 'max:45'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'address' => ['sometimes', 'string', 'max:256'],
            'country' => ['sometimes', 'size:2', 'exists:country,iso'],
            'VAT_number' => ['sometimes', 'string', 'max:45'],
            'registration_no' => ['sometimes', 'string', 'max:20'],
            'language' => ['sometimes', 'size:2'],
        ]);

        return $validator->fails() ? null : $data;
    }

    /**
     * A name or shared email is a candidate, never proof of identity. Even a
     * single candidate requires an explicit association before overwriting it.
     *
     * @return array<int, int>
     */
    private function candidates(array $data): array
    {
        $matches = [];
        $name = self::normalizeIdentity($data['name']);

        foreach (Actor::select(['id', 'name', 'first_name', 'display_name', 'email', 'VAT_number', 'registration_no'])->cursor() as $actor) {
            $names = [$actor->name, $actor->display_name];

            if (filled($actor->first_name)) {
                $names[] = $actor->name . ' ' . $actor->first_name;
                $names[] = $actor->first_name . ' ' . $actor->name;
            }

            $matched = in_array($name, array_map(self::normalizeIdentity(...), $names), true);

            foreach (['email', 'VAT_number', 'registration_no'] as $field) {
                if (!empty($data[$field]) && filled($actor->{$field})
                    && self::normalizeIdentity($data[$field]) === self::normalizeIdentity($actor->{$field})) {
                    $matched = true;
                }
            }

            if ($matched) {
                $matches[] = (int) $actor->id;
            }
        }

        return $matches;
    }

    public static function normalizeIdentity(?string $value): string
    {
        return mb_strtolower(preg_replace('/[^\p{L}\p{N}]/u', '', Str::ascii($value ?? '')));
    }

    private function remoteId(array $thirdParty): ?int
    {
        $id = $thirdParty['id'] ?? $thirdParty['socid'] ?? null;

        if ((!is_int($id) && !is_string($id)) || !ctype_digit((string) $id)) {
            return null;
        }

        return filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
    }

    private function result(string $status, ?int $remoteId, ?int $actorId = null, ?string $reason = null): array
    {
        return ['status' => $status, 'remote_id' => $remoteId, 'actor_id' => $actorId, 'reason' => $reason];
    }
}
