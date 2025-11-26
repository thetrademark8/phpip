<?php

namespace App\Http\Controllers;

use App\Contracts\Services\MatterServiceInterface;
use App\Http\Requests\MatterExportRequest;
use App\Http\Requests\MatterFilterRequest;
use App\Http\Requests\MergeFileRequest;
use App\Models\Actor;
use App\Models\ActorPivot;
use App\Models\Event;
use App\Models\Matter;
use App\Services\DocumentMergeService;
use App\Services\MatterExportService;
use App\Services\OPSService;
use App\Services\SharePointService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class MatterController extends Controller
{
    public function __construct(
        protected MatterServiceInterface $matterService,
        protected DocumentMergeService $documentMergeService,
        protected MatterExportService $matterExportService,
        protected OPSService $opsService,
        protected SharePointService $sharePoint
    ) {}

    public function index(MatterFilterRequest $request)
    {
        $filters = $request->getFilters();
        $options = $request->getOptions();

        if ($request->wantsJson()) {
            $matters = $this->matterService->exportMatters($filters, $options);
            return response()->json($matters);
        }

        $matters = $this->matterService->searchMatters($filters, $options);
        $matters->withQueryString();

        return Inertia::render('Matter/Index', [
            'matters' => $matters,
            'filters' => $request->getAllParameters(),
            'sort' => $options['sortkey'],
            'direction' => $options['sortdir'],
        ]);
    }

    public function show(Matter $matter)
    {
        $this->authorize('view', $matter);
        
        // Load all necessary relationships with optimized queries
        $matter->load([
            'tasksPending.info',
            'renewalsPending',
            'events' => function ($query) {
                $query->with(['info', 'altMatter', 'link'])
                    ->orderBy('event_date');
            },
            'titles',
            'actors' => function ($query) {
                $query->with('actor')
                    ->orderBy('display_order');
            },
            'classifiers.linkedMatter',
            'linkedBy',
            'category',
            'type',
            'countryInfo',
            'container',
            'parent',
            'family',
            'priorityTo'
        ]);

        // Group data for the frontend
        $titles = $matter->titles->groupBy('type_name');
        $classifiers = $matter->classifiers->groupBy('type_code');
        $actors = $matter->actors->groupBy('role_name');
        $statusEvents = $matter->events->where('info.status_event', 1)->values();

        // Check SharePoint configuration
        $sharePointLink = null;
        if ($this->sharePoint->isEnabled()) {
            $sharePointLink = $this->sharePoint->findFolderLink(
                $matter->caseref,
                $matter->suffix,
                ''
            );
        }

        if (request()->wantsJson()) {
            return response()->json([
                'matter' => $matter,
                'titles' => $titles,
                'classifiers' => $classifiers,
                'actors' => $actors,
                'statusEvents' => $statusEvents,
                'sharePointLink' => $sharePointLink
            ]);
        }

        return Inertia::render('Matter/Show', [
            'matter' => $matter,
            'titles' => $titles,
            'classifiers' => $classifiers,
            'actors' => $actors,
            'statusEvents' => $statusEvents,
            'sharePointLink' => $sharePointLink,
            'canWrite' => Auth::user()->can('readwrite')
        ]);
    }

    /**
     * Return a JSON array with info of a matter. For use with API REST.
     *
     * @param int $id
     * @return Json
     **/
    public function info($id)
    {
        return Matter::with(
            ['tasksPending.info', 'renewalsPending', 'events.info', 'titles', 'actors', 'classifiers']
        )->find($id);
    }

    public function create(Request $request)
    {
        Gate::authorize('readwrite');
        $operation = $request->input('operation', 'new'); // new, clone, child, ops
        $category = [];
        $category_code = $request->input('category', 'PAT');
        if ($operation != 'new' && $operation != 'ops') {
            $parent_matter = Matter::with(
                'container',
                'countryInfo',
                'originInfo',
                'category',
                'type'
            )->find($request->matter_id);
            if ($operation == 'clone') {
                // Generate the next available caseref based on the prefix
                $parent_matter->caseref = Matter::where(
                    'caseref',
                    'like',
                    $parent_matter->category->ref_prefix . '%'
                )->max('caseref');
                $parent_matter->caseref++;
            }
        } else {
            $parent_matter = new Matter; // Create empty matter object to avoid undefined errors in view
            $ref_prefix = \App\Models\Category::find($category_code)['ref_prefix'];
            $category = [
                'code' => $category_code,
                'next_caseref' => Matter::where('caseref', 'like', $ref_prefix . '%')
                    ->max('caseref'),
                'name' => \App\Models\Category::find($category_code)['category'],
            ];
            $category['next_caseref']++;
        }

        // Check if this is an Inertia request
        if ($request->header('X-Inertia')) {
            return Inertia::render('Matter/Create', [
                'parentMatter' => $parent_matter,
                'operation' => $operation,
                'category' => $category,
                'currentUser' => Auth::user(),
            ]);
        }

        return view('matter.create', compact('parent_matter', 'operation', 'category'));
    }

    public function store(Request $request)
    {
        Gate::authorize('readwrite');
        $this->validate(
            $request,
            [
                'category_code' => 'required',
                'caseref' => 'required',
                'country' => 'required',
                'responsible' => 'required',
                'expire_date' => 'date',
            ]
        );

        // Unique UID handling
        $matters = Matter::where(
            [
                ['caseref', $request->caseref],
                ['country', $request->country],
                ['category_code', $request->category_code],
                ['origin', $request->origin],
                ['type_code', $request->type_code],
            ]
        );

        $request->merge(['creator' => Auth::user()->login]);

        $idx = $matters->count();

        if ($idx > 0) {
            $request->merge(['idx' => $idx + 1]);
        }

        $new_matter = Matter::create($request->except(['_token', '_method', 'operation', 'parent_id', 'priority']));

        switch ($request->operation) {
            case 'child':
                $parent_matter = Matter::with('priority', 'filing', 'classifiersNative', 'actorPivot')->find($request->parent_id);
                // Copy priority claims from original matter
                $new_matter->priority()->createMany($parent_matter->priority->toArray());
                $new_matter->container_id = $parent_matter->container_id ?? $request->parent_id;
                if ($request->priority) {
                    $new_matter->events()->create(['code' => 'PRI', 'alt_matter_id' => $request->parent_id]);
                } else {
                    // Copy Filing event from original matter
                    $new_matter->filing()->save($parent_matter->filing->replicate(['detail']));
                    $new_matter->parent_id = $request->parent_id;
                    $new_matter->events()->create(
                        [
                            'code' => 'ENT',
                            'event_date' => now(),
                            'detail' => 'Child filing date',
                        ]
                    );
                }
                // Copy actors from parent matter (same logic as clone)
                $actors = $parent_matter->actorPivot;
                $new_matter_id = $new_matter->id;
                $actors->each(function ($item) use ($new_matter_id) {
                    $item->matter_id = $new_matter_id;
                    $item->id = null;
                });
                ActorPivot::insertOrIgnore($actors->toArray());
                // Copy classifiers (including image) from container or parent
                if ($parent_matter->container_id) {
                    // Copy shared actors from container
                    $sharedActors = $parent_matter->container->actorPivot->where('shared', 1);
                    $sharedActors->each(function ($item) use ($new_matter_id) {
                        $item->matter_id = $new_matter_id;
                        $item->id = null;
                    });
                    ActorPivot::insertOrIgnore($sharedActors->toArray());
                    // Copy classifiers from container
                    $new_matter->classifiersNative()
                        ->createMany($parent_matter->container->classifiersNative->toArray());
                } else {
                    // Copy classifiers from parent matter
                    $new_matter->classifiersNative()->createMany($parent_matter->classifiersNative->toArray());
                }
                $new_matter->save();
                break;
            case 'clone':
                $parent_matter = Matter::with('priority', 'classifiersNative', 'actorPivot')->find($request->parent_id);
                // Copy priority claims from original matter
                $new_matter->priority()->createMany($parent_matter->priority->toArray());
                // Copy actors from original matter
                // Cannot use Eloquent relationships because they do not handle unique key constraints
                // - the issue arises for actors that are inserted upon matter creation by a trigger based
                //   on the default_actors table
                $actors = $parent_matter->actorPivot;
                $new_matter_id = $new_matter->id;
                $actors->each(function ($item) use ($new_matter_id) {
                    $item->matter_id = $new_matter_id;
                    $item->id = null;
                });
                ActorPivot::insertOrIgnore($actors->toArray());
                if ($parent_matter->container_id) {
                    // Copy shared actors and classifiers from original matter's container
                    $actors = $parent_matter->container->actorPivot->where('shared', 1);
                    $actors->each(function ($item) use ($new_matter_id) {
                        $item->matter_id = $new_matter_id;
                        $item->id = null;
                    });
                    ActorPivot::insertOrIgnore($actors->toArray());
                    $new_matter->classifiersNative()
                        ->createMany($parent_matter->container->classifiersNative->toArray());
                } else {
                    // Copy classifiers from original matter
                    $new_matter->classifiersNative()->createMany($parent_matter->classifiersNative->toArray());
                }
                break;
            case 'new':
                $new_matter->events()->create(['code' => 'REC', 'event_date' => now()]);
                break;
        }

        $message = match ($request->operation) {
            'child' => __('Child matter created successfully'),
            'clone' => __('Matter cloned successfully'),
            default => __('Matter created successfully'),
        };

        return to_route('matter.show', $new_matter)->with('success', $message);
    }

    public function storeN(Request $request)
    {
        Gate::authorize('readwrite');
        $this->validate(
            $request,
            ['ncountry' => 'required|array']
        );

        $parent_id = $request->parent_id;
        $parent_matter = Matter::with('priority', 'filing', 'publication', 'grant', 'classifiersNative')
            ->find($parent_id);

        foreach ($request->ncountry as $country) {
            $request->merge(
                [
                    'country' => $country,
                    'creator' => Auth::user()->login,
                ]
            );

            $new_matter = Matter::create($request->except(['_token', '_method', 'ncountry', 'parent_id']));

            // Copy shared events from original matter
            $new_matter->priority()->createMany($parent_matter->priority->toArray());
            // $new_matter->parentFiling()->createMany($parent_matter->parentFiling->toArray());
            $new_matter->filing()->save($parent_matter->filing->replicate());
            if ($parent_matter->publication()->exists()) {
                $new_matter->publication()->save($parent_matter->publication->replicate());
            }
            if ($parent_matter->grant()->exists()) {
                $new_matter->grant()->save($parent_matter->grant->replicate());
            }

            // Insert "entered" event tracing the actual date of the step
            $new_matter->events()->create(['code' => 'ENT', 'event_date' => now()]);
            // Insert "Parent filed" event tracing the filing number of the parent PCT or EP
            $new_matter->events()->create(['code' => 'PFIL', 'alt_matter_id' => $request->parent_id]);

            $new_matter->parent_id = $parent_id;
            $new_matter->container_id = $parent_matter->container_id ?? $parent_id;
            $new_matter->save();
        }

        return response()->json(['redirect' => "/matter?Ref=$request->caseref&origin=$parent_matter->country"]);
    }

    /**
     * Create national trademark matters from international WO registration
     */
    public function storeInternational(Request $request)
    {
        Gate::authorize('readwrite');
        
        $this->validate($request, [
            'parent_id' => 'required|exists:matter,id',
            'countries' => 'required|array|min:1',
            'countries.*' => 'string|exists:country,iso'
        ]);

        $parentMatter = Matter::findOrFail($request->parent_id);
        $service = app(\App\Services\InternationalTrademarkService::class);
        
        try {
            // Create national matters
            $results = $service->createCountryMatters($parentMatter, $request->countries);
            
            return response()->json([
                'success' => true,
                'message' => __('National trademark matters created successfully'),
                'results' => $results,
                'redirect' => route('matter.show', $parentMatter)
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);

        } catch (\Exception $e) {
            Log::error('International trademark creation failed', [
                'parent_id' => $request->parent_id,
                'countries' => $request->countries,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => __('An error occurred while creating national matters')
            ], 500);
        }
    }

    /**
     * Get available countries and existing national matters for international trademark
     */
    public function getInternationalCountries(Matter $matter)
    {
        Gate::authorize('readwrite');
        
        $service = app(\App\Services\InternationalTrademarkService::class);
        
        // Validate matter is eligible
        $validation = $service->validateInternationalMatter($matter);
        
        return response()->json([
            'validation' => $validation,
            'available_countries' => $service->getAvailableCountries(),
            'existing_matters' => $service->getExistingNationalMatters($matter),
            'estimation' => $service->estimateCreation($matter, $service->getAvailableCountries()->pluck('iso')->toArray())
        ]);
    }

    /**
     * Get official links for a matter
     */
    public function getOfficialLinks(Matter $matter)
    {
        $linkService = app(\App\Services\LinkGeneratorService::class);
        
        $links = $linkService->getAllLinksForMatter($matter);
        
        return response()->json([
            'links' => $links,
            'matter_info' => [
                'country' => $matter->country,
                'category' => $matter->category_code,
                'uid' => $matter->uid
            ]
        ]);
    }

    public function storeFamily(Request $request)
    {
        Gate::authorize('readwrite');
        $this->validate($request, [
            'docnum' => 'required',
            'caseref' => 'required',
            'category_code' => 'required',
            'client_id' => 'required',
        ]);

        $apps = collect($this->opsService->getFamilyMembers($request->docnum));
        if ($apps->has('errors') || $apps->has('exception')) {
            return response()->json($apps);
        }

        $container = [];
        $container_id = null;
        $matter_id_num = [];
        $existing_fam = Matter::where('caseref', $request->caseref)->get();
        if ($existing_fam->count()) {
            $container = $existing_fam->where('container_id', null)->first();
            $container_id = $container->id;
            foreach ($existing_fam as $existing_app) {
                $matter_id_num[$existing_app->filing->cleanNumber()] = $existing_app->id;
            }
        }
        foreach ($apps as $key => $app) {
            if (array_key_exists($app['app']['number'], $matter_id_num)) {
                // Member exists, do not create
                continue;
            }
            $request->merge(
                [
                    'country' => $app['app']['country'],
                    'creator' => Auth::user()->login,
                ]
            );
            // Remove if set from a previous iteration
            $request->request->remove('type_code');
            $request->request->remove('origin');
            $request->request->remove('idx');
            if ($app['app']['kind'] == 'P') {
                $request->merge(['type_code' => 'PRO']);
            }
            if ($app['pct'] != null) {
                $request->merge(['origin' => 'WO']);
            }
            $parent_num = '';
            if ($app['div'] != null) {
                $request->merge(['type_code' => 'DIV']);
                $parent_num = $app['div'];
            }
            if ($app['cnt'] != null) {
                $request->merge(['type_code' => 'CNT']);
                $parent_num = $app['cnt'];
            }

            // Unique UID handling
            $matters = Matter::where(
                [
                    ['caseref', $request->caseref],
                    ['country', $request->country],
                    ['category_code', $request->category_code],
                    ['origin', $request->origin],
                    ['type_code', $request->type_code],
                ]
            );

            $idx = $matters->count();

            if ($idx > 0) {
                $request->merge(['idx' => $idx + 1]);
            }

            $new_matter = Matter::create($request->except(['_token', '_method', 'docnum', 'client_id']));
            $matter_id_num[$app['app']['number']] = $new_matter->id;

            if ($key == 0) {
                $container_id = $new_matter->id;
                foreach ($app['pri'] as $pri) {
                    // Create priority filings that refer to applications not returned by OPS (US provisionals)
                    if ($pri['number'] != $app['app']['number']) {
                        $new_matter->events()->create(
                            [
                                'code' => 'PRI',
                                'detail' => $pri['country'] . $pri['number'],
                                'event_date' => $pri['date'],
                            ]
                        );
                    }
                }
                if (array_key_exists('title', $app)) {
                    $new_matter->classifiersNative()->create(['type_code' => 'TIT', 'value' => $app['title']]);
                }
                $new_matter->actorPivot()->create(['actor_id' => $request->client_id, 'role' => 'CLI', 'shared' => 1]);
                if (array_key_exists('applicants', $app)) {
                    if (strtolower($app['applicants'][0]) == strtolower(Actor::find($request->client_id)->name)) {
                        $new_matter->actorPivot()->create(
                            [
                                'actor_id' => $request->client_id,
                                'role' => 'APP',
                                'shared' => 1,
                            ]
                        );
                    }
                    foreach ($app['applicants'] as $applicant) {
                        // Search for phonetically equivalent in the actor table, and take first
                        if (substr($applicant, -1) == ',') {
                            // Remove ending comma
                            $applicant = substr($applicant, 0, -1);
                        }
                        if ($actor = Actor::whereRaw("name SOUNDS LIKE '$applicant'")->first()) {
                            // Some applicants are listed twice, with and without accents, so ignore unique key error for a second attempt
                            $new_matter->actorPivot()->firstOrCreate(
                                [
                                    'actor_id' => $actor->id,
                                    'role' => 'APP',
                                    'shared' => 1,
                                ]
                            );
                        } else {
                            $new_actor = Actor::create(
                                [
                                    'name' => $applicant,
                                    'default_role' => 'APP',
                                    'phy_person' => 0,
                                    'notes' => "Inserted by OPS family create tool for matter ID $new_matter->id",
                                ]
                            );
                            $new_matter->actorPivot()->firstOrCreate(
                                [
                                    'actor_id' => $new_actor->id,
                                    'role' => 'APP',
                                    'shared' => 1,
                                ]
                            );
                        }
                    }
                    $new_matter->notes = 'Applicants: ' . collect($app['applicants'])->implode('; ');
                }
                if (array_key_exists('inventors', $app)) {
                    foreach ($app['inventors'] as $inventor) {
                        // Search for phonetically equivalent in the actor table, and take first
                        if (substr($inventor, -1) == ',') {
                            // Remove ending comma
                            $inventor = substr($inventor, 0, -1);
                        }
                        if ($actor = Actor::whereRaw("name SOUNDS LIKE '$inventor'")->first()) {
                            // Some inventors are listed twice, with and without accents, so ignore second attempt
                            $new_matter->actorPivot()->firstOrCreate(
                                [
                                    'actor_id' => $actor->id,
                                    'role' => 'INV',
                                    'shared' => 1,
                                ]
                            );
                        } else {
                            $new_actor = Actor::create(
                                [
                                    'name' => $inventor,
                                    'default_role' => 'INV',
                                    'phy_person' => 1,
                                    'notes' => "Inserted by OPS family create tool for matter ID $new_matter->id",
                                ]
                            );
                            $new_matter->actorPivot()->firstOrCreate(
                                [
                                    'actor_id' => $new_actor->id,
                                    'role' => 'INV',
                                    'shared' => 1,
                                ]
                            );
                        }
                    }
                    $new_matter->notes .= "\nInventors: " . collect($app['inventors'])->implode(' - ');
                }
            } else {
                $new_matter->container_id = $container_id;
                foreach ($app['pri'] as $pri) {
                    // Create priority filings, excluding "auto" priority claim
                    if ($pri['number'] != $app['app']['number']) {
                        if (array_key_exists($pri['number'], $matter_id_num)) {
                            // The priority application is in the family
                            $new_matter->events()->create(
                                [
                                    'code' => 'PRI',
                                    'alt_matter_id' => $matter_id_num[$pri['number']],
                                ]
                            );
                        } else {
                            $new_matter->events()->create(
                                [
                                    'code' => 'PRI',
                                    'detail' => $pri['country'] . $pri['number'],
                                    'event_date' => $pri['date'],
                                ]
                            );
                        }
                    }
                }
            }
            if ($app['pct'] != null) {
                $new_matter->parent_id = $matter_id_num[$app['pct']];
                $new_matter->events()->create(
                    [
                        'code' => 'PFIL',
                        'alt_matter_id' => $new_matter->parent_id,
                    ]
                );
            }
            if ($parent_num) {
                // This app is a divisional or a continuation
                $new_matter->events()->create(
                    [
                        'code' => 'ENT',
                        'event_date' => $app['app']['date'],
                        'detail' => 'Child filing date',
                    ]
                );
                $parent = $apps->where('app.number', $parent_num)->first();
                // Change this app's filing date to the parent's filing date for potential children of this app
                $app['app']['date'] = $parent['app']['date'];
                $new_matter->parent_id = $matter_id_num["$parent_num"];
            }
            $new_matter->events()->create(['code' => 'FIL', 'event_date' => $app['app']['date'], 'detail' => $app['app']['number']]);
            if (array_key_exists('pub', $app)) {
                $new_matter->events()->create(
                    [
                        'code' => 'PUB',
                        'event_date' => $app['pub']['date'],
                        'detail' => $app['pub']['number'],
                    ]
                );
            }
            if (array_key_exists('grt', $app)) {
                $new_matter->events()->create(
                    [
                        'code' => 'GRT',
                        'event_date' => $app['grt']['date'],
                        'detail' => $app['grt']['number'],
                    ]
                );
            }
            if (array_key_exists('procedure', $app)) {
                foreach ($app['procedure'] as $step) {
                    switch ($step['code']) {
                        case 'EXRE':
                            // Exam report
                            $exa = $new_matter->events()->create(['code' => 'EXA', 'event_date' => $step['dispatched']]);
                            if (array_key_exists('replied', $step) && $exa->event_date < now()->subMonths(4)) {
                                $exa->tasks()->create(
                                    [
                                        'code' => 'REP',
                                        'due_date' => $exa->event_date->addMonths(4),
                                        'done_date' => $step['replied'],
                                        'done' => 1,
                                        'detail' => 'Exam Report',
                                    ]
                                );
                            }
                            break;
                        case 'RFEE':
                            // Renewals
                            $new_matter->filing->tasks()->updateOrCreate(
                                ['code' => 'REN', 'detail' => $step['ren_year']],
                                [
                                    'due_date' => $new_matter->filing->event_date->addYears($step['ren_year'] - 1)->lastOfMonth(),
                                    'done_date' => $step['ren_paid'],
                                    'done' => 1,
                                ]
                            );
                            break;
                        case 'IGRA':
                            // Intention to grant
                            if (array_key_exists('dispatched', $step)) {
                                // Sometimes the dispatch and the payment are in different steps
                                $grt = $new_matter->events()->create(['code' => 'ALL', 'event_date' => $step['dispatched']]);
                            }
                            if (array_key_exists('grt_paid', $step) && $grt->event_date < now()->subMonths(4)) {
                                $grt->tasks()->create(
                                    [
                                        'code' => 'PAY',
                                        'due_date' => $grt->event_date->addMonths(4),
                                        'done_date' => $step['grt_paid'],
                                        'done' => 1,
                                        'detail' => 'Grant Fee',
                                    ]
                                );
                            }
                            break;
                        case 'EXAM52':
                            // Filing request (useful for divisional actual filing date)
                            if ($new_matter->type_code == 'DIV') {
                                $new_matter->events->where('code', 'ENT')->first()->event_date = $step['request'];
                            }
                            break;
                    }
                }
            }
            // The push() method saves the model with all its relationships
            $new_matter->push();
        }

        return response()->json(['redirect' => "/matter?Ref=$request->caseref&tab=1"]);
    }

    public function edit(Matter $matter)
    {
        Gate::authorize('readwrite');
        $matter->load(
            'container',
            'parent',
            'countryInfo:iso,name',
            'originInfo:iso,name',
            'category',
            'type',
            'filing'
        );
        $country_edit = $matter->tasks()->whereHas(
                'rule',
                function (Builder $q) {
                    $q->whereNotNull('for_country');
                }
            )->count() == 0;
        $cat_edit = $matter->tasks()->whereHas(
                'rule',
                function (Builder $q) {
                    $q->whereNotNull('for_category');
                }
            )->count() == 0;

        return view('matter.edit', compact(['matter', 'cat_edit', 'country_edit']));
    }

    public function update(Request $request, Matter $matter)
    {
        Gate::authorize('readwrite');
        $request->validate(
            [
                'term_adjust' => 'numeric',
                'idx' => 'numeric|nullable',
                'expire_date' => 'date',
            ]
        );
        $request->merge(['updater' => Auth::user()->login]);
        $matter->update($request->except(['_token', '_method']));

        // Always return Inertia response for AJAX requests
        if ($request->ajax() || $request->wantsJson() || $request->inertia()) {
            return redirect()->back();
        }

        return $matter;
    }

    public function destroy(Matter $matter)
    {
        Gate::authorize('readwrite');
        $matter->delete();

        return $matter;
    }

    /**
     * Exports Matters list.
     *
     * This method exports a list of matters based on the provided filters and returns
     * a streamed response for downloading the file in CSV format.
     *
     * @param MatterExportRequest $request The request object containing the filters for exporting matters.
     * @return \Symfony\Component\HttpFoundation\StreamedResponse The streamed response for the CSV file download.
     */
    public function export(MatterExportRequest $request)
    {
        // Extract filters from the request, excluding certain parameters.
        $filters = $request->except(
            [
                'display_with',
                'page',
                'filter',
                'value',
                'sortkey',
                'sortdir',
                'sort',
                'direction',
                'per_page',
                'tab',
                'include_dead',
            ]
        );

        // @TODO rewrite the filter method to use the new query builder
        // Retrieve the filtered matters and convert them to an array.
        $export = Matter::filter(
            $request->input('sortkey', 'caseref'),
            $request->input('sortdir', 'asc'),
            $filters,
            $request->display_with,
            $request->include_dead
        )->get()->toArray();

        // Export the matters array to a CSV file and return the streamed response.
        return $this->matterExportService->export($export);
    }

    /**
     * Export a single matter
     * 
     * @param Matter $matter
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportSingle(Matter $matter)
    {
        // Check if user can view this matter
        Gate::authorize('view', $matter);
        
        // Export this specific matter
        $matterData = [$matter->toArray()];
        return $this->matterExportService->export($matterData);
    }

    /**
     * Generate merged document on the fly from uploaded template
     */
    public function mergeFile(Matter $matter, MergeFileRequest $request)
    {
        $file = $request->file('file');
        $template = $this->documentMergeService
            ->setMatter($matter)
            ->merge($file->path());

        return response()->streamDownload(function () use ($template) {
            $template->saveAs('php://output');
        }, 'merged-' . $file->getClientOriginalName(), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Transfer-Encoding' => 'binary',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }

    /**
     * Get family members from OPS for a given document number
     *
     * @return array
     */
    public function getOPSfamily(string $docnum)
    {
        return $this->opsService->getFamilyMembers($docnum);
    }

    public function events(Matter $matter)
    {
        $events = $matter->events->load('info');

        return view('matter.events', compact('events', 'matter'));
    }

    public function tasks(Matter $matter)
    {
        // All events and their tasks, excepting renewals
        $events = $matter->events()->with(['tasks' => function (HasMany $query) {
            $query->where('code', '!=', 'REN');
        }, 'info:code,name', 'tasks.info:code,name'])->get();
        $is_renewals = 0;

        return view('matter.tasks', compact('events', 'matter', 'is_renewals'));
    }

    public function renewals(Matter $matter)
    {
        // The renewal trigger event and its renewals
        $events = $matter->events()->whereHas('tasks', function (Builder $query) {
            $query->where('code', 'REN');
        })->with(['tasks' => function (HasMany $query) {
            $query->where('code', 'REN');
        }, 'info:code,name', 'tasks.info:code,name'])->get();
        $is_renewals = 1;

        return view('matter.tasks', compact('events', 'matter', 'is_renewals'));
    }

    public function actors(Matter $matter, $role)
    {
        $role_group = $matter->actors->where('role_code', $role);

        return view('matter.roleActors', compact('role_group', 'matter'));
    }

    public function classifiers(Matter $matter)
    {
        $matter->load(['classifiers']);

        return view('matter.classifiers', compact('matter'));
    }

    public function description(Matter $matter, $lang)
    {
        $description = $matter->getDescription($lang);

        return view('matter.summary', compact('description'));
    }

    public function search(Request $request)
    {
        $this->authorize('readMatter', Matter::class);

        $query = Matter::query();

        // Basic search query
        if ($request->filled('q')) {
            $searchTerm = $request->input('q');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('uid', 'like', "%{$searchTerm}%")
                    ->orWhere('title', 'like', "%{$searchTerm}%")
                    ->orWhere('alt_ref', 'like', "%{$searchTerm}%")
                    ->orWhereHas('actors', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', "%{$searchTerm}%")
                            ->whereIn('role_code', ['CLI', 'APP', 'OWN']);
                    });
            });
        }

        // Category filter
        if ($request->filled('category')) {
            $query->where('category_code', $request->input('category'));
        }

        // Status filter
        if ($request->filled('status')) {
            $status = $request->input('status');
            switch ($status) {
                case 'active':
                    $query->whereNull('dead')->orWhere('dead', 0);
                    break;
                case 'inactive':
                    $query->where('dead', 1);
                    break;
                case 'pending':
                    $query->whereHas('status', function ($q) {
                        $q->where('code', 'Pending');
                    });
                    break;
                case 'dead':
                    $query->where('dead', 1);
                    break;
            }
        }

        // Responsible filter
        if ($request->filled('responsible')) {
            $query->where('responsible', 'like', "%{$request->input('responsible')}%");
        }

        // Limit results and order by relevance
        $matters = $query->with(['category', 'actors' => function ($q) {
            $q->where('role_code', 'CLI')->orderBy('display_order');
        }])
            ->orderBy('updated_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'data' => $matters->map(function ($matter) {
                return [
                    'id' => $matter->id,
                    'uid' => $matter->uid,
                    'title' => $matter->title,
                    'category' => $matter->category?->category ?? $matter->category_code,
                    'status' => $matter->dead ? 'inactive' : 'active',
                    'client' => [
                        'name' => $matter->actors->where('role_code', 'CLI')->first()?->name,
                    ],
                ];
            }),
        ]);
    }
}
