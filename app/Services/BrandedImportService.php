<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BrandedImportService
{
    private array $actorCache = [];

    private array $matterCache = [];

    private array $warnings = [];

    private array $importedMatterIds = [];

    private array $generatedCaserefCache = [];

    /**
     * Maps the original caseref as it appears in the CSV (before stripping the country
     * suffix) to the resulting matter id. Used by resolveParentRefs() to translate the
     * parent_ref column (which uses the old convention) into the actual matter row.
     */
    private array $caserefMatterMap = [];

    private array $stats = [
        'actors_created' => 0,
        'actors_updated' => 0,
        'matters_created' => 0,
        'matters_updated' => 0,
        'events_upserted' => 0,
        'classifiers_upserted' => 0,
        'actor_links_upserted' => 0,
        'renewals_cleaned' => 0,
        'caserefs_generated' => 0,
        'warnings' => 0,
    ];

    public function __construct()
    {
        $this->actorCache = [];
        $this->matterCache = [];
        $this->warnings = [];
        $this->generatedCaserefCache = [];
        $this->caserefMatterMap = [];
        $this->stats = [
            'actors_created' => 0,
            'actors_updated' => 0,
            'matters_created' => 0,
            'matters_updated' => 0,
            'events_upserted' => 0,
            'classifiers_upserted' => 0,
            'actor_links_upserted' => 0,
            'caserefs_generated' => 0,
            'warnings' => 0,
        ];
    }

    /**
     * Run the full branded import inside a DB transaction.
     *
     * @return array{stats: array, warnings: string[]}
     */
    private ?string $responsibleLogin = null;

    public function import(string $actorsFile, string $mattersFile, ?string $responsibleLogin = null): array
    {
        $this->responsibleLogin = $responsibleLogin;

        if ($responsibleLogin !== null) {
            DB::table('task_rules')->whereNotNull('responsible')->update(['responsible' => $responsibleLogin]);
            DB::table('event_name')->whereNotNull('default_responsible')->update(['default_responsible' => $responsibleLogin]);
        }

        DB::transaction(function () use ($actorsFile, $mattersFile) {
            $this->importActors($actorsFile);
            $this->buildActorCache();
            $this->importMatters($mattersFile);
        });

        $this->cleanupPastRenewals();

        return [
            'stats' => $this->stats,
            'warnings' => $this->warnings,
        ];
    }

    /**
     * Preview the import without making changes (dry-run).
     *
     * @return array{actors_count: int, matters_count: int, actors_preview: array, matters_preview: array}
     */
    public function preview(string $actorsFile, string $mattersFile): array
    {
        $actorRows = $this->parseCsv($actorsFile);
        $matterRows = $this->parseCsv($mattersFile, false);

        $actorsPreview = [];
        foreach (array_slice($actorRows, 0, 5) as $row) {
            $actorsPreview[] = [
                'name' => $row['name'] ?? '',
                'display_name' => $row['display_name'] ?? '',
            ];
        }

        $mattersPreview = [];
        foreach (array_slice($matterRows, 0, 5) as $row) {
            $mattersPreview[] = [
                'caseref' => $row[2] ?? '',
                'category' => $row[1] ?? '',
                'country' => $row[3] ?? '',
            ];
        }

        return [
            'actors_count' => count($actorRows),
            'matters_count' => count($matterRows),
            'actors_preview' => $actorsPreview,
            'matters_preview' => $mattersPreview,
        ];
    }

    private function importActors(string $filePath): void
    {
        $rows = $this->parseCsv($filePath);

        // First pass: create all actors without company_id
        foreach ($rows as $row) {
            $name = $this->nullIfEmpty($row['name']);

            if ($name === null) {
                $this->warnings[] = 'Skipping actor row with empty name';
                $this->stats['warnings']++;

                continue;
            }

            $addressParts = array_filter([
                $this->nullIfEmpty($row['address']),
                $this->nullIfEmpty($row['address2']),
                $this->nullIfEmpty($row['postal_code']),
                $this->nullIfEmpty($row['city']),
            ]);

            $data = [
                'display_name' => $this->nullIfEmpty($row['display_name']),
                'first_name' => $this->nullIfEmpty($row['first_name']),
                'email' => $this->nullIfEmpty($row['email']),
                'country' => $this->nullIfEmpty($row['country']),
                'phone' => $this->nullIfEmpty($row['phone']),
                'phy_person' => $row['phy_person'] !== '' ? (int) $row['phy_person'] : 1,
                'function' => $this->nullIfEmpty($row['function']),
                'language' => 'fr',
                'address' => count($addressParts) > 0 ? implode("\n", $addressParts) : null,
            ];

            // Match canonically by display_name (the unique-indexed column) when
            // available, fall back to name. This avoids unique-constraint violations
            // when an actor was previously auto-created with name == display_name.
            $displayName = $this->nullIfEmpty($row['display_name']);
            $existing = null;
            if ($displayName !== null) {
                $existing = DB::table('actor')->where('display_name', $displayName)->first();
            }
            if ($existing === null) {
                $existing = DB::table('actor')->where('name', $name)->first();
            }

            if ($existing) {
                DB::table('actor')->where('id', $existing->id)->update(array_merge(['name' => $name], $data));
                $this->stats['actors_updated']++;
            } else {
                DB::table('actor')->insert(array_merge(['name' => $name], $data));
                $this->stats['actors_created']++;
            }
        }

        // Second pass: resolve company_id references
        foreach ($rows as $row) {
            $companyDisplayName = $this->nullIfEmpty($row['company_display_name']);

            if ($companyDisplayName === null) {
                continue;
            }

            $name = $this->nullIfEmpty($row['name']);
            $company = DB::table('actor')->where('display_name', $companyDisplayName)->first();

            if ($company) {
                DB::table('actor')->where('name', $name)->update(['company_id' => $company->id]);
            } else {
                $this->warnings[] = "Actor '{$name}': company '{$companyDisplayName}' not found";
                $this->stats['warnings']++;
                Log::warning('ImportBranded: company not found for actor', ['actor' => $name, 'company_display_name' => $companyDisplayName]);
            }
        }
    }

    private function buildActorCache(): void
    {
        $actors = DB::table('actor')->select('id', 'display_name')->whereNotNull('display_name')->get();

        foreach ($actors as $actor) {
            $this->actorCache[$actor->display_name] = $actor->id;
        }
    }

    private function importMatters(string $mattersFile): void
    {
        $rows = $this->parseCsv($mattersFile, false);

        foreach ($rows as $row) {
            // Pad row to ensure all columns exist
            $row = array_pad($row, 47, '');

            $importRef = $this->nullIfEmpty($row[0]);
            $caseref = $this->nullIfEmpty($row[2]);

            $country = $this->mapCountry($this->nullIfEmpty($row[3]));

            if ($country === null) {
                $this->warnings[] = 'Skipping matter row with empty country';
                $this->stats['warnings']++;

                continue;
            }

            if (! DB::table('country')->where('iso', $country)->exists()) {
                $this->warnings[] = "Skipping matter row with unknown country code '{$country}'";
                $this->stats['warnings']++;

                continue;
            }

            $categoryCode = $this->mapCategory($this->nullIfEmpty($row[1]));

            if ($categoryCode === null || ! DB::table('matter_category')->where('code', $categoryCode)->exists()) {
                $this->warnings[] = "Skipping matter row with missing or unknown category '{$categoryCode}'";
                $this->stats['warnings']++;

                continue;
            }

            if ($caseref === null) {
                $caseref = $this->generateNextCaseref($categoryCode);
                $row[2] = $caseref;
                $this->stats['caserefs_generated']++;
            }

            // Preserve the caseref as it appears in the CSV so parent_ref entries that use
            // the old convention can still be resolved after we strip the country suffix.
            $originalCaseref = $caseref;

            $origin = $this->mapCountry($this->nullIfEmpty($row[4]));
            $stripped = $this->stripCountryFromCaseref($caseref, $country, $origin);
            $row[2] = $stripped['caseref'];
            $idx = $stripped['idx'];

            $matterId = $this->upsertMatter($row, $idx);

            if ($matterId === null) {
                continue;
            }

            if ($importRef !== null) {
                $this->matterCache[$importRef] = $matterId;
            }

            $this->caserefMatterMap[$originalCaseref] = $matterId;

            $this->upsertEvents($matterId, $row);
            $this->upsertClassifiers($matterId, $row);
            $this->upsertActorLinks($matterId, $row);
        }

        // Second pass: resolve parent_ref references
        $this->resolveParentRefs($mattersFile);
    }

    /**
     * Strip a trailing country (and optional origin) code, plus any digits that follow,
     * from a caseref. Returns the cleaned caseref and the captured digits parsed as idx.
     *
     * Examples (country=EM, origin=null):  TM1008EM00 -> ["TM1008", 0]
     * Examples (country=GB, origin=WO):    TM1008WOGB -> ["TM1008", null]
     */
    private function stripCountryFromCaseref(string $caseref, string $country, ?string $origin): array
    {
        $suffix = ($origin ?? '').$country;
        if ($suffix === '') {
            return ['caseref' => $caseref, 'idx' => null];
        }

        $pattern = '/'.preg_quote($suffix, '/').'(\d*)$/i';

        if (preg_match($pattern, $caseref, $matches)) {
            $newCaseref = substr($caseref, 0, -strlen($matches[0]));

            if ($newCaseref === '') {
                return ['caseref' => $caseref, 'idx' => null];
            }

            $idx = $matches[1] !== '' ? (int) $matches[1] : null;

            return ['caseref' => $newCaseref, 'idx' => $idx];
        }

        return ['caseref' => $caseref, 'idx' => null];
    }

    private function upsertMatter(array $row, ?int $idx = null): ?int
    {
        $caseref = $this->nullIfEmpty($row[2]);
        $country = $this->mapCountry($this->nullIfEmpty($row[3]));
        $origin = $this->mapCountry($this->nullIfEmpty($row[4]));

        $data = array_filter([
            'category_code' => $this->mapCategory($this->nullIfEmpty($row[1])),
            'type_code' => $this->nullIfEmpty($row[5]),
            'alt_ref' => $this->nullIfEmpty($row[6]),
            'responsible' => $this->responsibleLogin ?? $this->nullIfEmpty($row[7]),
            'expire_date' => $this->nullIfEmpty($row[14]),
            'notes' => $this->nullIfEmpty($row[15]),
        ], fn ($value) => $value !== null);

        if ($idx !== null) {
            $data['idx'] = $idx;
        }

        $existing = DB::table('matter')
            ->where('caseref', $caseref)
            ->where('country', $country);

        if ($origin !== null) {
            $existing->where('origin', $origin);
        } else {
            $existing->whereNull('origin');
        }

        if ($idx !== null) {
            $existing->where('idx', $idx);
        } else {
            $existing->whereNull('idx');
        }

        $existing = $existing->first();

        if ($existing) {
            DB::table('matter')->where('id', $existing->id)->update($data);
            $this->stats['matters_updated']++;
            $this->importedMatterIds[] = $existing->id;

            return $existing->id;
        }

        $insertData = array_merge([
            'caseref' => $caseref,
            'country' => $country,
            'origin' => $origin,
        ], $data);

        $id = DB::table('matter')->insertGetId($insertData);
        $this->stats['matters_created']++;
        $this->importedMatterIds[] = $id;

        return $id;
    }

    private function upsertEvents(int $matterId, array $row): void
    {
        $events = [
            ['code' => 'FIL', 'dateCol' => 8, 'detailCol' => 9],
            ['code' => 'REG', 'dateCol' => 10, 'detailCol' => 11],
            ['code' => 'PUB', 'dateCol' => 12, 'detailCol' => 13],
        ];

        foreach ($events as $event) {
            $eventDate = $this->nullIfEmpty($row[$event['dateCol']]);

            if ($eventDate === null) {
                continue;
            }

            DB::table('event')->updateOrInsert(
                ['matter_id' => $matterId, 'code' => $event['code']],
                [
                    'event_date' => $eventDate,
                    'detail' => $this->nullIfEmpty($row[$event['detailCol']]),
                ]
            );

            $this->stats['events_upserted']++;
        }
    }

    private function upsertClassifiers(int $matterId, array $row): void
    {
        $classifiers = [
            ['typeCol' => 18, 'valueCol' => 19, 'orderCol' => 21],
            ['typeCol' => 22, 'valueCol' => 23, 'orderCol' => 25],
        ];

        foreach ($classifiers as $classifier) {
            $value = $this->nullIfEmpty($row[$classifier['valueCol']]);

            if ($value === null) {
                continue;
            }

            $typeCode = $this->mapClassifierType($this->nullIfEmpty($row[$classifier['typeCol']]));

            if ($typeCode === null) {
                continue;
            }

            $displayOrder = $this->nullIfEmpty($row[$classifier['orderCol']]);

            DB::table('classifier')->updateOrInsert(
                [
                    'matter_id' => $matterId,
                    'type_code' => $typeCode,
                    'display_order' => $displayOrder ?? 1,
                ],
                ['value' => trim($value)]
            );

            $this->stats['classifiers_upserted']++;
        }
    }

    private function upsertActorLinks(int $matterId, array $row): void
    {
        $blocks = [
            ['nameCol' => 27, 'roleCol' => 28, 'orderCol' => 29, 'rateCol' => 30, 'refCol' => 31, 'dateCol' => 32],
            ['nameCol' => 34, 'roleCol' => 35, 'orderCol' => 36, 'rateCol' => 37, 'refCol' => 38, 'dateCol' => 39],
            ['nameCol' => 41, 'roleCol' => 42, 'orderCol' => 43, 'rateCol' => 44, 'refCol' => 45, 'dateCol' => 46],
        ];

        foreach ($blocks as $block) {
            $actorDisplayName = $this->nullIfEmpty($row[$block['nameCol']]);

            if ($actorDisplayName === null) {
                continue;
            }

            $actorId = $this->actorCache[$actorDisplayName] ?? null;

            if ($actorId === null) {
                $actorId = $this->createActorFromName($actorDisplayName);
            }

            $role = $this->nullIfEmpty($row[$block['roleCol']]);

            if ($role === null) {
                continue;
            }

            $displayOrder = $this->nullIfEmpty($row[$block['orderCol']]) ?? 1;

            $data = ['actor_id' => $actorId];

            $rate = $this->nullIfEmpty($row[$block['rateCol']]);
            if ($rate !== null) {
                $data['rate'] = $rate;
            }

            $actorRef = $this->nullIfEmpty($row[$block['refCol']]);
            if ($actorRef !== null) {
                $data['actor_ref'] = $actorRef;
            }

            $date = $this->nullIfEmpty($row[$block['dateCol']]);
            if ($date !== null) {
                $data['date'] = $date;
            }

            DB::table('matter_actor_lnk')->updateOrInsert(
                [
                    'matter_id' => $matterId,
                    'role' => $role,
                    'display_order' => $displayOrder,
                ],
                $data
            );

            $this->stats['actor_links_upserted']++;
        }
    }

    private function resolveParentRefs(string $mattersFile): void
    {
        $rows = $this->parseCsv($mattersFile, false);

        foreach ($rows as $row) {
            $row = array_pad($row, 47, '');
            $parentRef = $this->nullIfEmpty($row[16]);

            if ($parentRef === null) {
                continue;
            }

            $childOriginalCaseref = $this->nullIfEmpty($row[2]);
            $childId = $this->caserefMatterMap[$childOriginalCaseref] ?? null;
            $parentId = $this->caserefMatterMap[$parentRef] ?? null;

            if ($parentId === null) {
                // Fallback: parent may have been imported in a previous run with its
                // current (post-strip) caseref, so try a direct database lookup.
                $parent = DB::table('matter')->where('caseref', $parentRef)->first();
                $parentId = $parent?->id;
            }

            if ($childId === null || $parentId === null) {
                $this->warnings[] = "Matter '{$childOriginalCaseref}': parent_ref '{$parentRef}' not found";
                $this->stats['warnings']++;
                Log::warning('ImportBranded: parent_ref not found', ['caseref' => $childOriginalCaseref, 'parent_ref' => $parentRef]);

                continue;
            }

            DB::table('matter')->where('id', $childId)->update(['parent_id' => $parentId]);
        }
    }

    private function parseCsv(string $filePath, bool $associative = true): array
    {
        $rows = [];
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            $this->warnings[] = "Cannot open file: {$filePath}";

            return [];
        }

        $header = fgetcsv($handle, 0, ';');

        if ($header === false) {
            fclose($handle);

            return [];
        }

        // Clean BOM from first header
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if ($associative && count($header) === count($row)) {
                $rows[] = array_combine($header, $row);
            } else {
                $rows[] = $row;
            }
        }

        fclose($handle);

        return $rows;
    }

    private const COUNTRY_MAP = [
        'UE' => 'EM',
        'UK' => 'GB',
        'UAE' => 'AE',
    ];

    private const CATEGORY_MAP = [
        'BT' => 'PAT',
        'DM' => 'DES',
        'DP' => 'DES',
        'ND' => 'DOM',
        'NDD' => 'DOM',
    ];

    private const CLASSIFIER_TYPE_MAP = [
        'CL' => 'TMCL',
    ];

    private function createActorFromName(string $displayName): int
    {
        // Recheck DB: the in-memory cache uses byte-exact keys but MySQL's
        // utf8mb4_unicode_ci collation treats Unicode equivalents (e.g. NFC vs NFD)
        // as equal, so a cache miss can still hit the display_name unique index.
        $existing = DB::table('actor')->where('display_name', $displayName)->value('id');

        if ($existing !== null) {
            $this->actorCache[$displayName] = $existing;

            return $existing;
        }

        $id = DB::table('actor')->insertGetId([
            'name' => $displayName,
            'display_name' => $displayName,
        ]);

        $this->actorCache[$displayName] = $id;
        $this->stats['actors_created']++;

        return $id;
    }

    private function generateNextCaseref(?string $categoryCode): string
    {
        $prefix = null;

        if ($categoryCode !== null) {
            $category = DB::table('matter_category')->where('code', $categoryCode)->first();
            $prefix = $category->ref_prefix ?? null;
        }

        $prefix = $prefix ?: ($categoryCode ?: 'X');

        // Check if we already generated caserefs with this prefix in this import
        if (isset($this->generatedCaserefCache[$prefix])) {
            $lastGenerated = $this->generatedCaserefCache[$prefix];
            $nextCaseref = ++$lastGenerated;
        } else {
            // Find the max existing caseref with this prefix in the database
            $maxCaseref = DB::table('matter')
                ->where('caseref', 'like', $prefix.'%')
                ->max('caseref');

            if ($maxCaseref !== null && $maxCaseref !== $prefix) {
                $nextCaseref = ++$maxCaseref;
            } else {
                $nextCaseref = strtoupper($prefix);
            }
        }

        $this->generatedCaserefCache[$prefix] = $nextCaseref;

        return $nextCaseref;
    }

    private function cleanupPastRenewals(): void
    {
        if (empty($this->importedMatterIds)) {
            return;
        }

        $deleted = DB::table('task')
            ->join('event', 'event.id', '=', 'task.trigger_id')
            ->whereIn('event.matter_id', $this->importedMatterIds)
            ->where('task.code', 'REN')
            ->where('task.done', 0)
            ->where('task.due_date', '<', now())
            ->delete();

        $this->stats['renewals_cleaned'] = $deleted;
    }

    private function mapCategory(?string $code): ?string
    {
        if ($code === null) {
            return null;
        }

        $upper = strtoupper($code);

        return self::CATEGORY_MAP[$upper] ?? $upper;
    }

    private function mapCountry(?string $code): ?string
    {
        if ($code === null) {
            return null;
        }

        $upper = strtoupper($code);

        return self::COUNTRY_MAP[$upper] ?? $upper;
    }

    private function mapClassifierType(?string $typeCode): ?string
    {
        if ($typeCode === null) {
            return null;
        }

        return self::CLASSIFIER_TYPE_MAP[$typeCode] ?? $typeCode;
    }

    private function nullIfEmpty(string $value): ?string
    {
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
