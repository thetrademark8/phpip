<?php

/*
  |--------------------------------------------------------------------------
  | Web Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register web routes for your application. These
  | routes are loaded by the RouteServiceProvider within a group which
  | contains the "web" middleware group. Now create something great!
  |
 */

use App\Http\Controllers\AutocompleteController;
use App\Http\Controllers\ClassifierController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MatterController;
use App\Http\Controllers\MatterSearchController;
use App\Http\Controllers\RenewalController;
use App\Models\Matter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes(['register' => false]);

Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::middleware(['auth'])->group(function () {
    // Test route for Inertia setup
    Route::get('/test-inertia', function () {
        return inertia('Test');
    })->name('test.inertia');
    
    // Test route for DatePicker
    Route::get('/test-datepicker', function () {
        return inertia('DatePickerTest');
    })->name('test.datepicker');
    
    // Components showcase
    Route::get('/test-components', function () {
        return inertia('ComponentsShowcase');
    })->name('test.components');
    
    // Forms test page
    Route::get('/test-forms', function () {
        return inertia('Test/Forms');
    })->name('test.forms');
    
    // Dialog system test page
    Route::get('/test-dialogs', function () {
        return inertia('DialogTest');
    })->name('test.dialogs');
    
    // Test form submission
    Route::post('/test-date-submit', function () {
        request()->validate([
            'due_date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);
        
        return back()->with('success', 'Dates saved successfully!');
    })->name('test.date.submit');
    
    // Matter routes group
    Route::controller(MatterController::class)->prefix('matter')->name('matter.')->group(function () {
        Route::get('autocomplete', [AutocompleteController::class, 'matter'])->name('autocomplete');
        Route::get('new-caseref', [AutocompleteController::class, 'newCaseref'])->name('new-caseref');
        Route::post('search', [MatterSearchController::class, 'search'])->name('search');
        Route::get('search', 'search')->name('search.ajax');
        Route::get('export', 'export')->name('export');
        Route::post('{matter}/mergeFile', 'mergeFile')->name('mergeFile');
        Route::get('{matter}/events', 'events')->name('events');
        Route::get('{matter}/tasks', 'tasks')->name('tasks');
        Route::get('{matter}/classifiers', 'classifiers')->name('classifiers');
        Route::get('{matter}/renewals', 'renewals')->name('renewals');
        Route::get('{matter}/roleActors/{role}', 'actors')->name('actors');
        Route::get('{matter}/description/{lang}', 'description')->name('description');
        Route::get('{matter}/info', 'info')->name('info');
        Route::post('storeN', 'storeN')->name('storeN');
        Route::get('getOPSfamily/{docnum}', 'getOPSfamily')->name('getOPSfamily');
        Route::post('storeFamily', 'storeFamily')->name('storeFamily');
    });

    // Nested matter routes for creating related records
    Route::middleware('can:readwrite')->group(function () {
        Route::post('matter/{matter}/actors', [App\Http\Controllers\ActorPivotController::class, 'store'])->name('matter.actors.store');
        Route::post('matter/{matter}/events', [App\Http\Controllers\EventController::class, 'store'])->name('matter.events.store');
        Route::post('matter/{matter}/classifiers', [App\Http\Controllers\ClassifierController::class, 'store'])->name('matter.classifiers.store');
    });

    // Autocomplete routes - some are public for filtering, others require readwrite
    // Public autocomplete routes (for filtering)
    Route::get('status-event/autocomplete', [AutocompleteController::class, 'statusEventName']);
    Route::get('category/autocomplete', [AutocompleteController::class, 'category']);
    Route::get('actor/autocomplete/{create_option?}', [AutocompleteController::class, 'actor']);
    
    // Protected autocomplete routes - require readwrite permission
    Route::middleware('can:readwrite')->group(function () {
        Route::get('event-name/autocomplete/{is_task}', [AutocompleteController::class, 'eventName']);
        Route::get('classifier-type/autocomplete/{main_display}', [AutocompleteController::class, 'classifierType']);
        Route::get('user/autocomplete', [AutocompleteController::class, 'user']);
        Route::get('role/autocomplete', [AutocompleteController::class, 'role']);
        Route::get('dbrole/autocomplete', [AutocompleteController::class, 'dbrole']);
        Route::get('country/autocomplete', [AutocompleteController::class, 'country']);
        Route::get('type/autocomplete', [AutocompleteController::class, 'type']);
        Route::get('template-category/autocomplete', [AutocompleteController::class, 'templateCategory']);
        Route::get('template-class/autocomplete', [AutocompleteController::class, 'templateClass']);
        Route::get('template-style/autocomplete', [AutocompleteController::class, 'templateStyle']);
    });

    // Classifier routes
    Route::get('classifier/{classifier}/img', [ClassifierController::class, 'showImage'])->name('classifier.image');

    // Renewal routes
    Route::controller(RenewalController::class)->prefix('renewal')->name('renewal.')->group(function () {
        Route::post('order', 'renewalOrder')->name('order');
        Route::post('call/{send}', 'firstcall')->name('firstcall');
        Route::post('reminder', 'remindercall')->name('reminder');
        Route::post('invoice/{toinvoice}', 'invoice')->name('invoice');
        Route::post('renewalsInvoiced', 'renewalsInvoiced')->name('renewalsInvoiced');
        Route::post('topay', 'topay')->name('topay');
        Route::post('paid', 'paid')->name('paid');
        Route::post('done', 'done')->name('done');
        Route::post('lastcall', 'lastcall')->name('lastcall');
        Route::post('receipt', 'receipt')->name('receipt');
        Route::post('closing', 'closing')->name('closing');
        Route::post('abandon', 'abandon')->name('abandon');
        Route::post('lapsing', 'lapsing')->name('lapsing');
        Route::get('export', 'export')->name('export');
        Route::get('logs', 'logs')->name('logs');
    });

    // Document routes
    Route::controller(DocumentController::class)->prefix('document')->name('document.')->group(function () {
        Route::post('mailto/{member}', 'mailto');
        Route::get('select/{matter}', 'select');
    });

    Route::post('event/{event}/recreateTasks', fn (App\Models\Event $event) => DB::statement('CALL recreate_tasks(?, ?)', [$event->id, Auth::user()->login]));

    // Resource routes
    Route::resource('matter', MatterController::class);
    Route::resource('actor', App\Http\Controllers\ActorController::class);
    Route::resource('user', App\Http\Controllers\UserController::class);
    Route::get('/profile', [App\Http\Controllers\UserController::class, 'profile'])->name('user.profile');
    Route::put('/profile/update', [App\Http\Controllers\UserController::class, 'updateProfile'])->name('user.updateProfile');
    Route::apiResource('task', App\Http\Controllers\TaskController::class);

    // The following resources are not accessible to clients
    Route::middleware('can:except_client')->group(function () {
        Route::post('matter/clear-tasks', [HomeController::class, 'clearTasks']);
        Route::get('matter/{parent_matter}/createN', fn (Matter $parent_matter) => view('matter.createN', compact('parent_matter')));
        
        // Nested routes for matter relationships
        Route::post('matter/{matter}/actors', [App\Http\Controllers\ActorPivotController::class, 'store']);
        Route::post('matter/{matter}/events', [App\Http\Controllers\EventController::class, 'store']);
        
        Route::apiResource('event', App\Http\Controllers\EventController::class);
        Route::get('category/autocomplete', [App\Http\Controllers\CategoryController::class, 'autocomplete']);
        Route::resource('category', App\Http\Controllers\CategoryController::class);
        Route::resource('classifier_type', App\Http\Controllers\ClassifierTypeController::class);
        Route::get('role/autocomplete', [App\Http\Controllers\RoleController::class, 'autocomplete']);
        Route::resource('role', App\Http\Controllers\RoleController::class);
        Route::resource('type', App\Http\Controllers\MatterTypeController::class);
        Route::get('default_actor/autocomplete', [App\Http\Controllers\DefaultActorController::class, 'autocomplete']);
        Route::resource('default_actor', App\Http\Controllers\DefaultActorController::class);
        Route::get('country/autocomplete', function (Illuminate\Http\Request $request) {
            $query = $request->get('query', '');
            $countries = App\Models\Country::where('name', 'like', "%{$query}%")
                ->take(10)
                ->get()
                ->map(function ($country) {
                    return [
                        'id' => $country->iso,
                        'name' => $country->name,
                    ];
                });
            return response()->json($countries);
        });
        Route::get('actor/{actor}/usedin', [App\Http\Controllers\ActorPivotController::class, 'usedIn']);
        Route::resource('eventname', App\Http\Controllers\EventNameController::class);
        Route::resource('rule', App\Http\Controllers\RuleController::class);
        Route::apiResource('actor-pivot', App\Http\Controllers\ActorPivotController::class);
        Route::apiResource('classifier', App\Http\Controllers\ClassifierController::class);
        Route::resource('renewal', RenewalController::class);
        Route::resource('fee', App\Http\Controllers\FeeController::class);
        Route::resource('template-member', App\Http\Controllers\TemplateMemberController::class);
        Route::resource('document', DocumentController::class)->parameters(['document' => 'class']);
        Route::resource('event-class', App\Http\Controllers\EventClassController::class);
    });
});
