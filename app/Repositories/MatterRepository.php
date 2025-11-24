<?php

namespace App\Repositories;

use App\Models\Matter;
use App\Repositories\Contracts\MatterRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MatterRepository implements MatterRepositoryInterface
{
    /**
     * Create a new matter
     */
    public function create(array $data): Matter
    {
        return Matter::create($data);
    }

    /**
     * Update a matter
     */
    public function update(Matter $matter, array $data): Matter
    {
        $matter->update($data);
        return $matter->fresh();
    }

    /**
     * Delete a matter
     */
    public function delete(Matter $matter): bool
    {
        return $matter->delete();
    }

    /**
     * Get matters expiring within specified days
     */
    public function getExpiringWithinDays(int $days): Collection
    {
        return Matter::where('expire_date', '<=', now()->addDays($days))
            ->where('expire_date', '>=', now())
            ->where('dead', 0)
            ->get();
    }

    /**
     * Search matters with filters and pagination
     */
    public function searchWithFilters(
        array $filters,
        string $sortKey,
        string $sortDir,
        int $perPage,
        ?string $displayWith,
        bool $includeDead
    ): LengthAwarePaginator {
        $query = $this->buildFilterQuery($filters, $displayWith, $includeDead);
        
        // Map sort key to actual database column
        $actualSortKey = $this->mapSortKey($sortKey);
        
        // Add all GROUP BY fields needed for the aggregate functions
        $groupByFields = [
            'matter.id',
            'matter.uid',
            'matter.country',
            'country.name',
            'country.name_FR',
            'country.name_DE',
            'matter.category_code',
            'matter.origin',
            'matter.container_id',
            'matter.parent_id',
            'matter.type_code',
            'matter.responsible',
            'matter.dead',
            'matter.alt_ref',
            'matter.caseref',
            'matter.suffix',
            'matter.expire_date',
            'del.login',
            'tit1.value',
            'tit2.value',
            'tit3.value',
            'inv.name',
            'inv.first_name',
            'fil.event_date',
            'fil.detail',
            'pub.event_date',
            'pub.detail',
            'grt.event_date',
            'grt.detail',
            'reg.event_date',
            'reg.detail',
            'regdp.event_date',
            'img.id'
        ];

        // Group by the sort key and additional fields to match old implementation
        $query->groupBy(...$groupByFields)
              ->orderBy($actualSortKey, $sortDir);

        return $query->paginate($perPage);
    }

    /**
     * Get filtered matters for export
     */
    public function getFilteredForExport(
        array $filters,
        string $sortKey,
        string $sortDir,
        ?string $displayWith,
        bool $includeDead
    ): Collection {
        $query = $this->buildFilterQuery($filters, $displayWith, $includeDead);
        
        // Map sort key to actual database column
        $actualSortKey = $this->mapSortKey($sortKey);
        
        // Add all GROUP BY fields needed for the aggregate functions
        $groupByFields = [
            'matter.id',
            'matter.uid',
            'matter.country',
            'country.name',
            'country.name_FR',
            'country.name_DE',
            'matter.category_code',
            'matter.origin',
            'matter.container_id',
            'matter.parent_id',
            'matter.type_code',
            'matter.responsible',
            'matter.dead',
            'matter.alt_ref',
            'matter.caseref',
            'matter.suffix',
            'matter.expire_date',
            'del.login',
            'tit1.value',
            'tit2.value',
            'tit3.value',
            'inv.name',
            'inv.first_name',
            'fil.event_date',
            'fil.detail',
            'pub.event_date',
            'pub.detail',
            'grt.event_date',
            'grt.detail',
            'reg.event_date',
            'reg.detail',
            'regdp.event_date',
            'img.id'
        ];

        // Group by the sort key and additional fields to match old implementation
        $query->groupBy(...$groupByFields)
              ->orderBy($actualSortKey, $sortDir);

        return $query->get();
    }

    /**
     * Build the filter query
     */
    protected function buildFilterQuery(array $filters, ?string $displayWith, bool $includeDead): Builder
    {
        $locale = app()->getLocale();
        $baseLocale = substr($locale, 0, 2);

        $query = Matter::select(
            'matter.uid AS Ref',
            'matter.country AS country',
            DB::raw("COALESCE(country.name_$baseLocale, country.name) AS country_name"),
            'matter.category_code AS Cat',
            'matter.origin',
            DB::raw("GROUP_CONCAT(DISTINCT JSON_UNQUOTE(JSON_EXTRACT(event_name.name, '$.\"$baseLocale\"')) SEPARATOR '|') AS Status"),
            DB::raw('MIN(status.event_date) AS Status_date'),
            DB::raw("GROUP_CONCAT(DISTINCT COALESCE(cli.display_name, clic.display_name, cli.name, clic.name) SEPARATOR '; ') AS Client"),
            DB::raw("GROUP_CONCAT(DISTINCT COALESCE(clilnk.actor_ref, cliclnk.actor_ref) SEPARATOR '; ') AS ClRef"),
            DB::raw("GROUP_CONCAT(DISTINCT COALESCE(app.display_name, app.name) SEPARATOR '; ') AS Applicant"),
            DB::raw("GROUP_CONCAT(DISTINCT COALESCE(own.display_name, ownc.display_name, own.name, ownc.name) SEPARATOR '; ') AS Owner"),
            DB::raw("GROUP_CONCAT(DISTINCT COALESCE(agt.display_name, agtc.display_name, agt.name, agtc.name) SEPARATOR '; ') AS Agent"),
            DB::raw("GROUP_CONCAT(DISTINCT COALESCE(agtlnk.actor_ref, agtclnk.actor_ref) SEPARATOR '; ') AS AgtRef"),
            'tit1.value AS Title',
            DB::raw('COALESCE(tit2.value, tit1.value) AS Title2'),
            'tit3.value AS Title3',
            DB::raw("CONCAT_WS(' ', inv.name, inv.first_name) as Inventor1"),
            'fil.event_date AS Filed',
            'fil.detail AS FilNo',
            'pub.event_date AS Published',
            'pub.detail AS PubNo',
            DB::raw('COALESCE(grt.event_date, reg.event_date) AS Granted'),
            DB::raw('COALESCE(grt.detail, reg.detail) AS GrtNo'),
            'regdp.event_date AS Registration_DP',
            'img.id AS image_id',
            DB::raw("GROUP_CONCAT(DISTINCT tmcl.value ORDER BY tmcl.value SEPARATOR ', ') AS classes"),
            DB::raw("COALESCE((SELECT MIN(t.due_date) FROM task t INNER JOIN event e ON t.trigger_id = e.id WHERE e.matter_id = matter.id AND t.code = 'REN' AND t.done_date IS NULL), matter.expire_date) AS renewal_due"),
            'matter.id',
            'matter.container_id',
            'matter.parent_id',
            'matter.type_code',
            'matter.responsible',
            'del.login AS delegate',
            'matter.dead',
            DB::raw('isnull(matter.container_id) AS Ctnr'),
            'matter.alt_ref AS Alt_Ref'
        );

        // Add all the joins
        $this->addJoins($query);

        // Apply display category filter
        if ($displayWith) {
            $query->where('matter_category.display_with', $displayWith);
        }

        // Apply user role restrictions
        $this->applyUserRestrictions($query);

        // Apply filters
        $this->applyFilters($query, $filters);

        // Apply dead filter
        if (!$includeDead) {
            $query->whereRaw('(select count(1) from matter m where m.caseref = matter.caseref and m.dead = 0) > 0');
        }

        return $query;
    }

    /**
     * Add all necessary joins to the query
     */
    protected function addJoins(Builder $query): void
    {
        $query->join('matter_category', 'matter.category_code', 'matter_category.code')
            ->leftJoin('country', 'matter.country', 'country.iso')
            ->leftJoin(DB::raw('matter_actor_lnk clilnk JOIN actor cli ON cli.id = clilnk.actor_id'), function ($join) {
                $join->on('matter.id', 'clilnk.matter_id')->where('clilnk.role', 'CLI');
            })
            ->leftJoin(DB::raw('matter_actor_lnk cliclnk JOIN actor clic ON clic.id = cliclnk.actor_id'), function ($join) {
                $join->on('matter.container_id', 'cliclnk.matter_id')->where([
                    ['cliclnk.role', 'CLI'],
                    ['cliclnk.shared', 1],
                ]);
            })
            ->leftJoin(DB::raw('matter_actor_lnk ownlnk JOIN actor own ON own.id = ownlnk.actor_id'), function ($join) {
                $join->on('matter.id', 'ownlnk.matter_id')->where('ownlnk.role', 'OWN');
            })
            ->leftJoin(DB::raw('matter_actor_lnk ownclnk JOIN actor ownc ON ownc.id = ownclnk.actor_id'), function ($join) {
                $join->on('matter.container_id', 'ownclnk.matter_id')->where([
                    ['ownclnk.role', 'OWN'],
                    ['ownclnk.shared', 1],
                ]);
            })
            ->leftJoin(DB::raw('matter_actor_lnk agtlnk JOIN actor agt ON agt.id = agtlnk.actor_id'), function ($join) {
                $join->on('matter.id', 'agtlnk.matter_id')->where([
                    ['agtlnk.role', 'AGT'],
                    ['agtlnk.display_order', 1],
                ]);
            })
            ->leftJoin(DB::raw('matter_actor_lnk agtclnk JOIN actor agtc ON agtc.id = agtclnk.actor_id'), function ($join) {
                $join->on('matter.container_id', 'agtclnk.matter_id')->where([
                    ['agtclnk.role', 'AGT'],
                    ['agtclnk.shared', 1],
                ]);
            })
            ->leftJoin(DB::raw('matter_actor_lnk applnk JOIN actor app ON app.id = applnk.actor_id'), function ($join) {
                $join->on(DB::raw('ifnull(matter.container_id, matter.id)'), 'applnk.matter_id')->where('applnk.role', 'APP');
            })
            ->leftJoin(DB::raw('matter_actor_lnk dellnk JOIN actor del ON del.id = dellnk.actor_id'), function ($join) {
                $join->on(DB::raw('ifnull(matter.container_id, matter.id)'), 'dellnk.matter_id')->where('dellnk.role', 'DEL');
            })
            ->leftJoin('event AS fil', function ($join) {
                $join->on('matter.id', 'fil.matter_id')->where('fil.code', 'FIL');
            })
            ->leftJoin('event AS pub', function ($join) {
                $join->on('matter.id', 'pub.matter_id')->where('pub.code', 'PUB');
            })
            ->leftJoin('event AS grt', function ($join) {
                $join->on('matter.id', 'grt.matter_id')->where('grt.code', 'GRT');
            })
            ->leftJoin('event AS reg', function ($join) {
                $join->on('matter.id', 'reg.matter_id')->where('reg.code', 'REG');
            })
            ->leftJoin('event AS regdp', function ($join) {
                $join->on('matter.id', 'regdp.matter_id')->where('regdp.code', 'REGDP');
            })
            ->leftJoin('classifier AS img', function ($join) {
                $join->on('matter.id', 'img.matter_id')->where('img.type_code', 'IMG');
            })
            ->leftJoin('classifier AS tmcl', function ($join) {
                $join->on(DB::raw('IFNULL(matter.container_id, matter.id)'), '=', 'tmcl.matter_id')
                     ->where('tmcl.type_code', 'NICE');
            })
            ->leftJoin(DB::raw('event status JOIN event_name ON event_name.code = status.code AND event_name.status_event = 1'), 'matter.id', 'status.matter_id')
            ->leftJoin(DB::raw('event e2 JOIN event_name en2 ON e2.code = en2.code AND en2.status_event = 1'), function ($join) {
                $join->on('status.matter_id', 'e2.matter_id')->whereColumn('status.event_date', '<', 'e2.event_date');
            })
            ->leftJoin(DB::raw('classifier tit1 JOIN classifier_type ct1 ON tit1.type_code = ct1.code AND ct1.main_display = 1 AND ct1.display_order = 1'),
                DB::raw('IFNULL(matter.container_id, matter.id)'), 'tit1.matter_id')
            ->leftJoin(DB::raw('classifier tit2 JOIN classifier_type ct2 ON tit2.type_code = ct2.code AND ct2.main_display = 1 AND ct2.display_order = 2'),
                DB::raw('IFNULL(matter.container_id, matter.id)'), 'tit2.matter_id')
            ->leftJoin(DB::raw('classifier tit3 JOIN classifier_type ct3 ON tit3.type_code = ct3.code AND ct3.main_display = 1 AND ct3.display_order = 3'),
                DB::raw('IFNULL(matter.container_id, matter.id)'), 'tit3.matter_id')
            ->leftJoin(DB::raw('matter_actor_lnk invlnk JOIN actor inv ON inv.id = invlnk.actor_id'), function ($join) {
                $join->on(DB::raw('ifnull(matter.container_id, matter.id)'), 'invlnk.matter_id')->where([
                    ['invlnk.role', 'INV'],
                    ['invlnk.display_order', 1],
                ]);
            })
            ->where('e2.matter_id', null);
    }

    /**
     * Apply user role restrictions
     */
    protected function applyUserRestrictions(Builder $query): void
    {
        $authUserRole = Auth::user()->default_role;
        $authUserId = Auth::user()->id;

        if ($authUserRole == 'CLI' || empty($authUserRole)) {
            $query->where(function ($q) use ($authUserId) {
                $q->where('cli.id', $authUserId)
                    ->orWhere('clic.id', $authUserId);
            });
        }
    }

    /**
     * Apply filters to the query
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        foreach ($filters as $key => $value) {
            if (empty($value)) continue;

            match ($key) {
                'Ref' => $query->where(function ($q) use ($value) {
                    $q->whereLike('matter.uid', "$value%")
                        ->orWhereLike('matter.alt_ref', "$value%");
                }),
                'Cat' => $query->whereLike('matter.category_code', "$value%"),
                'country' => $query->whereLike('matter.country', "$value%"),
                'Status' => $query->whereJsonLike('event_name.name', $value),
                'Status_date' => $query->whereLike('status.event_date', "$value%"),
                'Client' => $query->whereLike(DB::raw('IFNULL(cli.name, clic.name)'), "$value%"),
                'ClRef' => $query->whereLike(DB::raw('IFNULL(clilnk.actor_ref, cliclnk.actor_ref)'), "$value%"),
                'Applicant' => $query->whereLike('app.name', "$value%"),
                'Agent' => $query->whereLike(DB::raw('IFNULL(agt.name, agtc.name)'), "$value%"),
                'AgtRef' => $query->whereLike(DB::raw('IFNULL(agtlnk.actor_ref, agtclnk.actor_ref)'), "$value%"),
                'Title' => $query->whereLike(DB::Raw('concat_ws(" ", tit1.value, tit2.value, tit3.value)'), "%$value%"),
                'Inventor1' => $query->whereLike('inv.name', "$value%"),
                'Filed' => $query->whereLike('fil.event_date', "$value%"),
                'FilNo' => $query->whereLike('fil.detail', "$value%"),
                'Published' => $query->whereLike('pub.event_date', "$value%"),
                'PubNo' => $query->whereLike('pub.detail', "$value%"),
                'Granted' => $query->where(function ($q) use ($value) {
                    $q->whereLike('grt.event_date', "$value%")
                        ->orWhereLike('reg.event_date', "$value%");
                }),
                'GrtNo' => $query->where(function ($q) use ($value) {
                    $q->whereLike('grt.detail', "$value%")
                        ->orWhereLike('reg.detail', "$value%");
                }),
                'responsible' => $query->where(function ($q) use ($value) {
                    $q->where('matter.responsible', $value)
                        ->orWhere('del.login', $value);
                }),
                'Ctnr' => $value ? $query->whereNull('matter.container_id') : null,
                default => null, // Ignore unknown filter keys to prevent SQL errors
            };
        }
    }

    /**
     * Map frontend sort keys to database columns
     */
    protected function mapSortKey(string $sortKey): string
    {
        return match ($sortKey) {
            'id' => 'matter.id',
            'caseref' => 'matter.caseref',
            'Ref' => 'matter.uid',
            'country' => 'matter.country',
            'Cat' => 'matter.category_code',
            'Status' => 'Status',
            'Status_date' => 'Status_date',
            'Client' => 'Client',
            'Applicant' => 'Applicant',
            'Agent' => 'Agent',
            'Title' => 'Title',
            'Inventor1' => 'Inventor1',
            'Filed' => 'fil.event_date',
            'FilNo' => 'fil.detail',
            'Published' => 'pub.event_date',
            'PubNo' => 'pub.detail',
            'Granted' => 'grt.event_date',
            'GrtNo' => 'grt.detail',
            default => 'matter.id'
        };
    }

    /**
     * Find a matter by ID
     */
    public function find(int $id): ?Matter
    {
        return Matter::find($id);
    }

    /**
     * Find matter with related data for renewal
     */
    public function findWithRenewalData(int $id): ?Matter
    {
        return Matter::with([
            'events' => function($query) {
                $query->whereHas('tasks', function($q) {
                    $q->where('code', 'REN');
                });
            },
            'classifiers' => function($query) {
                $query->whereIn('type_code', ['TIT', 'TITOF']);
            },
            'actors' => function($query) {
                $query->whereIn('role_code', ['CLI', 'APP', 'OWN']);
            }
        ])->find($id);
    }

    /**
     * Find multiple matters by IDs
     */
    public function findByIds(array $ids): Collection
    {
        return Matter::whereIn('id', $ids)->get();
    }

    /**
     * Get matter's current annuity
     */
    public function getCurrentAnnuity(int $matterId): ?int
    {
        $lastRenewal = Matter::find($matterId)
            ->events()
            ->whereHas('tasks', function($query) {
                $query->where('code', 'REN')
                    ->where('done', 1);
            })
            ->orderByDesc('event_date')
            ->first();
        
        if ($lastRenewal && $lastRenewal->tasks->first()) {
            $detail = json_decode($lastRenewal->tasks->first()->detail, true);
            return isset($detail['en']) ? (int) $detail['en'] : null;
        }
        
        return null;
    }

    /**
     * Get matter's applicants
     */
    public function getApplicants(int $matterId): Collection
    {
        $matter = Matter::find($matterId);
        if (!$matter) {
            return collect();
        }
        
        return $matter->actors()
            ->where('role_code', 'APP')
            ->get();
    }

    /**
     * Get matter's owner
     */
    public function getOwner(int $matterId)
    {
        $matter = Matter::find($matterId);
        if (!$matter) {
            return null;
        }
        
        return $matter->actors()
            ->where('role_code', 'OWN')
            ->first();
    }

    /**
     * Update matter's responsible
     */
    public function updateResponsible(int $matterId, string $responsible): bool
    {
        return Matter::where('id', $matterId)
            ->update(['responsible' => $responsible]) > 0;
    }

    /**
     * Get matters by client
     */
    public function getByClient(int $clientId): Collection
    {
        return Matter::whereHas('actors', function($query) use ($clientId) {
            $query->where('actor_id', $clientId)
                ->where('role_code', 'CLI');
        })->get();
    }

    /**
     * Get active matters count
     */
    public function getActiveCount(): int
    {
        return Matter::where('dead', 0)->count();
    }
}