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
use App\Http\Controllers\OptionsController;
use App\Http\Controllers\EmailSettingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MatterAttachmentController;
use App\Http\Controllers\MatterController;
use App\Http\Controllers\MatterEmailController;
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
    // Matter routes group
    Route::controller(MatterController::class)->prefix('matter')->name('matter.')->group(function () {
        Route::get('autocomplete', [AutocompleteController::class, 'matter'])->name('autocomplete');
        Route::get('new-caseref', [AutocompleteController::class, 'newCaseref'])->name('new-caseref');
        Route::get('form-options', 'getFormOptions')->name('form-options');
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
    Route::can('readwrite')->group(function () {
        Route::post('matter/{matter}/actors', [App\Http\Controllers\ActorPivotController::class, 'store'])->name('matter.actors.store');
        Route::post('matter/{matter}/events', [App\Http\Controllers\EventController::class, 'store'])->name('matter.events.store');
        Route::post('matter/{matter}/classifiers', [App\Http\Controllers\ClassifierController::class, 'store'])->name('matter.classifiers.store');
    });

    // Matter Email routes
    Route::prefix('matter/{matter}')->name('matter.')->group(function () {
        Route::get('email/compose', [MatterEmailController::class, 'compose'])->name('email.compose');
        Route::post('email/preview', [MatterEmailController::class, 'preview'])->name('email.preview');
        Route::post('email/send', [MatterEmailController::class, 'send'])->name('email.send');
        Route::get('email/history', [MatterEmailController::class, 'history'])->name('email.history');
        Route::get('email/{emailLog}', [MatterEmailController::class, 'showEmail'])->name('email.show');

        // Matter Attachments
        Route::get('attachments', [MatterAttachmentController::class, 'index'])->name('attachments.index');
        Route::post('attachments', [MatterAttachmentController::class, 'store'])->name('attachments.store');
        Route::get('attachments/{attachment}/download', [MatterAttachmentController::class, 'download'])->name('attachments.download');
        Route::get('attachments/{attachment}/url', [MatterAttachmentController::class, 'temporaryUrl'])->name('attachments.url');
        Route::delete('attachments/{attachment}', [MatterAttachmentController::class, 'destroy'])->name('attachments.destroy');
    });

    // Email Settings (Admin only)
    Route::middleware('can:admin')->prefix('settings')->name('settings.')->group(function () {
        Route::get('email', [EmailSettingController::class, 'index'])->name('email');
        Route::put('email', [EmailSettingController::class, 'update'])->name('email.update');
        Route::post('email/upload-logo', [EmailSettingController::class, 'uploadLogo'])->name('email.uploadLogo');
        Route::get('email/preview', [EmailSettingController::class, 'preview'])->name('email.preview');

        // TeamLeader Integration
        Route::get('teamleader', [App\Http\Controllers\TeamLeaderController::class, 'index'])->name('teamleader.index');
        Route::get('teamleader/authenticate', [App\Http\Controllers\TeamLeaderController::class, 'authenticate'])->name('teamleader.authenticate');
        Route::get('teamleader/callback', [App\Http\Controllers\TeamLeaderController::class, 'callback'])->name('teamleader.callback');
        Route::post('teamleader/disconnect', [App\Http\Controllers\TeamLeaderController::class, 'disconnect'])->name('teamleader.disconnect');
        Route::post('teamleader/test', [App\Http\Controllers\TeamLeaderController::class, 'testConnection'])->name('teamleader.test');
        Route::get('teamleader/diagnostics', [App\Http\Controllers\TeamLeaderController::class, 'diagnostics'])->name('teamleader.diagnostics');
        Route::post('teamleader/re-register-webhook', [App\Http\Controllers\TeamLeaderController::class, 'reRegisterWebhook'])->name('teamleader.reRegisterWebhook');
        Route::post('teamleader/sync', [App\Http\Controllers\TeamLeaderController::class, 'triggerSync'])->name('teamleader.sync');
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

    // Options routes for Select/Combobox components (full lists for dropdowns)
    Route::prefix('options')->name('options.')->group(function () {
        Route::get('categories', [OptionsController::class, 'categories'])->name('categories');
        Route::get('types', [OptionsController::class, 'types'])->name('types');
        Route::get('countries', [OptionsController::class, 'countries'])->name('countries');
        Route::get('users', [OptionsController::class, 'users'])->name('users');
        Route::get('event-names/{isTask?}', [OptionsController::class, 'eventNames'])->name('event-names');
        Route::get('classifier-types/{mainDisplay?}', [OptionsController::class, 'classifierTypes'])->name('classifier-types');
        Route::get('statuses', [OptionsController::class, 'statuses'])->name('statuses');
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
    Route::get('matter/{matter}/export', [MatterController::class, 'exportSingle'])->name('matter.export.single');
    Route::resource('actor', App\Http\Controllers\ActorController::class);
    Route::resource('user', App\Http\Controllers\UserController::class);
    Route::get('/profile', [App\Http\Controllers\UserController::class, 'profile'])->name('user.profile');
    Route::put('/profile/update', [App\Http\Controllers\UserController::class, 'updateProfile'])->name('profile.update');
    Route::apiResource('task', App\Http\Controllers\TaskController::class);

    // The following resources are not accessible to clients
    Route::middleware('can:except_client')->group(function () {
        Route::post('matter/clear-tasks', [HomeController::class, 'clearTasks']);
        Route::get('matter/{parent_matter}/createN', fn (Matter $parent_matter) => view('matter.createN', compact('parent_matter')));
        
        // Nested routes for matter relationships
        // Route::post('matter/{matter}/actors', [App\Http\Controllers\ActorPivotController::class, 'store']);
        // Route::post('matter/{matter}/events', [App\Http\Controllers\EventController::class, 'store']);
        
        // International trademark automation routes
        Route::post('matter/{matter}/create-national', [App\Http\Controllers\MatterController::class, 'storeInternational'])->name('matter.create-national');
        Route::get('matter/{matter}/international-countries', [App\Http\Controllers\MatterController::class, 'getInternationalCountries'])->name('matter.international-countries');
        
        // Official links route
        Route::get('matter/{matter}/official-links', [App\Http\Controllers\MatterController::class, 'getOfficialLinks'])->name('matter.official-links');
        
        Route::apiResource('event', App\Http\Controllers\EventController::class);
        Route::post('event/{event}/recalculate-tasks', [App\Http\Controllers\EventController::class, 'recalculateTasks'])->name('event.recalculate-tasks');
        Route::resource('category', App\Http\Controllers\CategoryController::class);
        Route::resource('classifier_type', App\Http\Controllers\ClassifierTypeController::class);
        Route::resource('role', App\Http\Controllers\RoleController::class);
        Route::resource('type', App\Http\Controllers\MatterTypeController::class);
        Route::resource('default_actor', App\Http\Controllers\DefaultActorController::class);
        Route::get('actor/{actor}/usedin', [App\Http\Controllers\ActorPivotController::class, 'usedIn']);
        Route::resource('eventname', App\Http\Controllers\EventNameController::class);
        Route::resource('rule', App\Http\Controllers\RuleController::class);
        Route::apiResource('actor-pivot', App\Http\Controllers\ActorPivotController::class);
        Route::apiResource('classifier', App\Http\Controllers\ClassifierController::class);
        Route::resource('renewal', RenewalController::class);
        Route::resource('fee', App\Http\Controllers\FeeController::class);
        Route::get('template-member/placeholders', [App\Http\Controllers\TemplateMemberController::class, 'placeholders'])->name('template-member.placeholders');
        Route::resource('template-member', App\Http\Controllers\TemplateMemberController::class);
        Route::resource('document', DocumentController::class)->parameters(['document' => 'class']);
        Route::resource('event-class', App\Http\Controllers\EventClassController::class);
    });
});

// Email preview routes (development only)
if (app()->environment('local')) {
    Route::prefix('email-preview')->name('email.')->group(function () {
        Route::get('/', [App\Http\Controllers\EmailPreviewController::class, 'index'])->name('preview.index');
        Route::get('/{template}', [App\Http\Controllers\EmailPreviewController::class, 'show'])->name('preview');
    });
}
