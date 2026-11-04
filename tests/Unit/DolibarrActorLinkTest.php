<?php

use App\Contracts\Renewal\DolibarrClientInterface;
use App\Exceptions\Dolibarr\DolibarrRequestException;
use App\Http\Controllers\ActorController;
use App\Models\Actor;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    (new ReflectionProperty(Illuminate\Database\Eloquent\Model::class, 'guardableColumns'))->setValue(null, []);
    $this->withoutMiddleware(App\Http\Middleware\HandleInertiaRequests::class);
    Schema::create('actor', function (Blueprint $table): void {
        $table->increments('id');
        $table->string('name');
        $table->string('login')->nullable();
        $table->unsignedBigInteger('dolibarr_socid')->nullable()->unique();
        $table->string('updater')->nullable();
        $table->timestamps();
    });
    $user = new User(['login' => 'tester', 'default_role' => 'DBRW']);
    $user->id = 123;
    $this->actingAs($user);
});

afterEach(function () {
    Schema::dropIfExists('actor');
});

it('validates a customer remotely before associating an actor', function ($customerType) {
    $actor = Actor::create(['name' => 'Local client']);
    $this->mock(DolibarrClientInterface::class)->shouldReceive('getThirdParty')
        ->once()->with(42)->andReturn(['id' => 42, 'client' => $customerType]);

    $this->patchJson(route('actor.update', $actor), ['dolibarr_socid' => 42])
        ->assertOk()->assertJsonPath('actor.dolibarr_socid', 42);
    expect($actor->refresh()->dolibarr_socid)->toBe(42);
})->with([1, 3, '1', '3']);

it('rejects missing third parties and non-customers without changing the actor', function ($thirdParty) {
    $actor = Actor::create(['name' => 'Local client']);
    $this->mock(DolibarrClientInterface::class)->shouldReceive('getThirdParty')
        ->once()->with(42)->andReturn($thirdParty);

    $this->patchJson(route('actor.update', $actor), ['dolibarr_socid' => 42])
        ->assertUnprocessable()->assertJsonValidationErrors('dolibarr_socid');
    expect($actor->refresh()->dolibarr_socid)->toBeNull();
})->with([null, [['id' => 42, 'client' => 0]], [['id' => 42, 'client' => 2]], [['id' => 42]]]);

it('returns a sanitized validation error when Dolibarr cannot verify the customer', function () {
    $actor = Actor::create(['name' => 'Local client']);
    $this->mock(DolibarrClientInterface::class)->shouldReceive('getThirdParty')
        ->once()->with(42)->andThrow(DolibarrRequestException::connection('fetch third party'));

    $this->patchJson(route('actor.update', $actor), ['dolibarr_socid' => 42])
        ->assertUnprocessable()
        ->assertJsonPath('errors.dolibarr_socid.0', __('dolibarr.errors.third_party_verification_failed'));
    expect($actor->refresh()->dolibarr_socid)->toBeNull();
});

it('can clear or retain an existing link without contacting Dolibarr', function () {
    $actor = Actor::create(['name' => 'Local client', 'dolibarr_socid' => 42]);
    $this->mock(DolibarrClientInterface::class)->shouldNotReceive('getThirdParty');

    $this->patchJson(route('actor.update', $actor), ['dolibarr_socid' => 42])->assertOk();
    $this->patchJson(route('actor.update', $actor), ['dolibarr_socid' => null])->assertOk();
    expect($actor->refresh()->dolibarr_socid)->toBeNull();
});

it('rejects duplicate associations before contacting Dolibarr', function () {
    Actor::create(['name' => 'Linked client', 'dolibarr_socid' => 42]);
    $actor = Actor::create(['name' => 'Other client']);
    $this->mock(DolibarrClientInterface::class)->shouldNotReceive('getThirdParty');

    $this->patchJson(route('actor.update', $actor), ['dolibarr_socid' => 42])
        ->assertUnprocessable()->assertJsonValidationErrors('dolibarr_socid');
});

it('exposes the actor link independently of the invoice backend', function ($syncEnabled, $backend, $expected) {
    config()->set('dolibarr.sync.enabled', $syncEnabled);
    config()->set('renewal.invoice.backend', $backend);
    $request = Request::create('/actor/1', 'GET');
    $request->headers->set('Accept', 'application/json');
    app()->instance('request', $request);
    $actor = Mockery::mock(Actor::class)->makePartial();
    Illuminate\Support\Facades\Gate::policy($actor::class, App\Policies\ActorPolicy::class);
    $actor->shouldReceive('load')->once()->andReturnSelf();
    $actor->shouldReceive('getTableComments')->once()->andReturn([]);

    $response = (new ActorController)->show($actor);
    expect($response->getData(true)['dolibarr_enabled'])->toBe($expected);
})->with([
    [true, 'none', true],
    [false, 'dolibarr', true],
    [false, 'none', false],
]);
