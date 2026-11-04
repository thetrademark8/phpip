<?php

use App\Contracts\Renewal\DolibarrClientInterface;
use App\Models\Actor;
use App\Services\Dolibarr\DolibarrActorSyncService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    // Other focused tests use smaller actor schemas in the same PHP process.
    // Eloquent caches column listings statically, beyond application lifetimes.
    (new ReflectionProperty(Illuminate\Database\Eloquent\Model::class, 'guardableColumns'))->setValue(null, []);
    config()->set('database.default', 'sqlite');
    config()->set('database.connections.sqlite.database', ':memory:');
    DB::purge('sqlite');

    Schema::create('country', function (Blueprint $table): void {
        $table->string('iso', 2)->primary();
    });
    DB::table('country')->insert(['iso' => 'FR']);

    Schema::create('actor', function (Blueprint $table): void {
        $table->increments('id');
        $table->string('name', 100);
        $table->string('first_name', 60)->nullable();
        $table->string('display_name', 30)->nullable()->unique();
        $table->string('login', 16)->nullable();
        $table->string('default_role', 5)->nullable();
        $table->string('email', 45)->nullable();
        $table->string('phone', 20)->nullable();
        $table->string('address', 256)->nullable();
        $table->string('address_billing', 256)->nullable();
        $table->string('country', 2)->nullable();
        $table->string('language', 2)->nullable();
        $table->string('VAT_number', 45)->nullable();
        $table->string('registration_no', 20)->nullable();
        $table->boolean('phy_person')->default(true);
        $table->decimal('ren_discount', 8, 2);
        $table->unsignedInteger('company_id')->nullable();
        $table->text('notes')->nullable();
        $table->unsignedBigInteger('dolibarr_socid')->nullable()->unique();
        $table->timestamps();
    });

    $this->client = Mockery::mock(DolibarrClientInterface::class);
    $this->sync = new DolibarrActorSyncService($this->client);
});

afterEach(function () {
    DB::disconnect('sqlite');
});

function dolibarrCustomer(array $overrides = []): array
{
    return array_replace([
        'id' => '42',
        'client' => '1',
        'name' => 'Example SARL',
        'email' => 'contact@example.test',
        'phone' => '0123456789',
        'address' => '10 rue des Fleurs',
        'zip' => '75001',
        'town' => 'Paris',
        'country_code' => 'FR',
        'tva_intra' => 'FR12345678901',
        'idprof1' => '123456789',
        'default_lang' => 'fr_FR',
        'typent_code' => 'TE_SMALL',
    ], $overrides);
}

function localDolibarrActor(array $overrides = []): Actor
{
    return Actor::create(array_replace(['name' => 'Local actor', 'ren_discount' => 0], $overrides));
}

it('runs the complete command from paginated HTTP responses to idempotent actor persistence', function () {
    config()->set([
        'dolibarr.sync.enabled' => true,
        'cache.default' => 'array',
        'renewal.api.dolibarr_url' => 'https://dolibarr.test/api/index.php',
        'renewal.api.DOLAPIKEY' => 'test-key',
        'renewal.invoice.backend' => 'none',
    ]);
    Http::preventStrayRequests();
    Http::fake(fn ($request) => Http::response(
        (int) $request['page'] === 0 ? [dolibarrCustomer()] : [],
    ));

    $this->artisan('dolibarr:sync')->assertSuccessful();
    expect(Actor::count())->toBe(0);
    $this->artisan('dolibarr:sync --apply')->assertSuccessful();
    expect(Actor::count())->toBe(1)
        ->and(Actor::first()->dolibarr_socid)->toBe(42)
        ->and(Actor::first()->email)->toBe('contact@example.test')
        ->and(Actor::first()->country)->toBe('FR');
    $this->artisan('dolibarr:sync --apply')->expectsOutputToContain('unchanged=1')->assertSuccessful();
    expect(Actor::count())->toBe(1)
        ->and(config('renewal.invoice.backend'))->toBe('none');
    Http::assertSentCount(6);
});

it('rejects non-integer remote identities instead of coercing them', function ($id) {
    expect($this->sync->sync(dolibarrCustomer(['id' => $id]))['reason'])->toBe('invalid_id')
        ->and(Actor::count())->toBe(0);
})->with([true, 1.5, '1.0', '+1', ' 1']);

it('imports customer coordinates once and preserves its identity on subsequent runs', function () {
    $result = $this->sync->sync(dolibarrCustomer());
    $actor = Actor::findOrFail($result['actor_id']);

    expect($result['status'])->toBe('created')
        ->and($actor->dolibarr_socid)->toBe(42)
        ->and($actor->name)->toBe('Example SARL')
        ->and($actor->address)->toBe("10 rue des Fleurs\n75001\nParis")
        ->and($actor->country)->toBe('FR')
        ->and($actor->language)->toBe('fr')
        ->and($actor->registration_no)->toBe('123456789')
        ->and($actor->display_name)->toBeNull()
        ->and((bool) $actor->phy_person)->toBeFalse()
        ->and((float) $actor->ren_discount)->toBe(0.0);

    expect($this->sync->sync(dolibarrCustomer())['status'])->toBe('unchanged')
        ->and(Actor::count())->toBe(1);
});

it('updates linked coordinates but preserves local data and fields empty upstream', function () {
    $actor = localDolibarrActor([
        'dolibarr_socid' => 42,
        'email' => 'local@example.test',
        'phone' => '0987654321',
        'display_name' => 'My display name',
        'first_name' => 'Alice',
        'notes' => 'Internal notes',
        'company_id' => 77,
        'address_billing' => 'Separate billing address',
        'ren_discount' => 15,
    ]);

    $result = $this->sync->sync(dolibarrCustomer(['email' => '', 'phone' => null]));
    $actor->refresh();

    expect($result['status'])->toBe('updated')
        ->and($actor->name)->toBe('Example SARL')
        ->and($actor->email)->toBe('local@example.test')
        ->and($actor->phone)->toBe('0987654321')
        ->and($actor->display_name)->toBe('My display name')
        ->and($actor->first_name)->toBe('Alice')
        ->and($actor->notes)->toBe('Internal notes')
        ->and($actor->company_id)->toBe(77)
        ->and($actor->address_billing)->toBe('Separate billing address')
        ->and((float) $actor->ren_discount)->toBe(15.0);
});

it('does not write creates or updates during a simulation', function () {
    expect($this->sync->sync(dolibarrCustomer(), true)['status'])->toBe('created')
        ->and(Actor::count())->toBe(0);

    $actor = localDolibarrActor(['dolibarr_socid' => 42]);

    expect($this->sync->sync(dolibarrCustomer(), true)['status'])->toBe('updated')
        ->and($actor->refresh()->name)->toBe('Local actor');
});

it('requires explicit linkage for an existing actor with matching normalized identity', function (array $attributes) {
    $actor = localDolibarrActor($attributes);
    $result = $this->sync->sync(dolibarrCustomer());

    expect($result['status'])->toBe('conflict')
        ->and($result['reason'])->toBe('existing_actor_requires_link')
        ->and($result['candidate_ids'])->toBe([$actor->id])
        ->and($actor->refresh()->dolibarr_socid)->toBeNull()
        ->and(Actor::count())->toBe(1);
})->with([
    'name' => [['name' => 'Éxample S.A.R.L.']],
    'display name' => [['display_name' => 'Example SARL']],
    'email' => [['email' => 'CONTACT@example.test']],
    'VAT' => [['VAT_number' => 'FR 12 345 678 901']],
    'registration' => [['registration_no' => '123 456 789']],
    'person full name' => [['name' => 'Example', 'first_name' => 'SARL']],
]);

it('reports all ambiguous candidates without modifying any of them', function () {
    $first = localDolibarrActor(['name' => 'Example SARL']);
    $second = localDolibarrActor(['email' => 'contact@example.test']);
    $result = $this->sync->sync(dolibarrCustomer());

    expect($result['status'])->toBe('conflict')
        ->and($result['actor_id'])->toBeNull()
        ->and($result['candidate_ids'])->toBe([$first->id, $second->id])
        ->and(Actor::whereNotNull('dolibarr_socid')->count())->toBe(0);
});

it('rejects invalid remote coordinates without partially updating an actor', function (array $overrides) {
    $actor = localDolibarrActor(['dolibarr_socid' => 42]);

    expect($this->sync->sync(dolibarrCustomer($overrides))['status'])->toBe('invalid')
        ->and($actor->refresh()->name)->toBe('Local actor')
        ->and($actor->email)->toBeNull();
})->with([
    'invalid email' => [['email' => 'not-an-email']],
    'too long email' => [['email' => str_repeat('a', 40) . '@example.test']],
    'too long phone' => [['phone' => str_repeat('1', 21)]],
    'too long name' => [['name' => str_repeat('a', 101)]],
    'missing name' => [['name' => '']],
    'unknown country' => [['country_code' => 'XX']],
    'structured value' => [['email' => ['contact@example.test']]],
    'supplier only' => [['client' => '0']],
    'prospect only' => [['client' => '2']],
    'invalid id' => [['id' => '42oops']],
]);

it('uses the explicit physical-person type while preserving an unknown existing type', function () {
    $this->sync->sync(dolibarrCustomer(['typent_code' => 'TE_PRIVATE']));
    $actor = Actor::firstOrFail();

    expect((bool) $actor->phy_person)->toBeTrue();

    $this->sync->sync(dolibarrCustomer(['typent_code' => 'TE_UNKNOWN', 'particulier' => 0, 'default_lang' => 'auto']));

    expect((bool) $actor->refresh()->phy_person)->toBeTrue()
        ->and($actor->language)->toBe('fr');
});

it('explicitly links and updates an existing customer after remote verification', function () {
    $actor = localDolibarrActor();
    $this->client->shouldReceive('getThirdParty')->with(42)->twice()->andReturn(dolibarrCustomer());

    expect($this->sync->link(42, $actor->id, true)['status'])->toBe('updated')
        ->and($actor->refresh()->dolibarr_socid)->toBeNull();

    expect($this->sync->link(42, $actor->id)['status'])->toBe('updated')
        ->and($actor->refresh()->dolibarr_socid)->toBe(42)
        ->and($actor->name)->toBe('Example SARL')
        ->and(Actor::count())->toBe(1);
});

it('preserves split individual names when Dolibarr provides a full name', function () {
    $actor = localDolibarrActor(['name' => 'Smith', 'first_name' => 'John', 'phy_person' => true, 'dolibarr_socid' => 42]);

    expect($this->sync->sync(dolibarrCustomer(['name' => 'John Smith', 'typent_code' => 'TE_PRIVATE']))['status'])->toBe('updated')
        ->and($actor->refresh()->name)->toBe('Smith')
        ->and($actor->first_name)->toBe('John');

    expect($this->sync->sync(dolibarrCustomer(['name' => 'James Smith', 'typent_code' => 'TE_PRIVATE']))['reason'])->toBe('individual_name_requires_review')
        ->and($actor->refresh()->name)->toBe('Smith')
        ->and($actor->first_name)->toBe('John');
});

it('refuses relinking either an actor or a third party already associated elsewhere', function () {
    $actor = localDolibarrActor(['dolibarr_socid' => 99]);
    $this->client->shouldReceive('getThirdParty')->with(42)->twice()->andReturn(dolibarrCustomer());

    expect($this->sync->link(42, $actor->id)['reason'])->toBe('actor_already_linked');

    localDolibarrActor(['dolibarr_socid' => 42]);

    expect($this->sync->link(42, $actor->id)['reason'])->toBe('third_party_already_linked')
        ->and($actor->refresh()->dolibarr_socid)->toBe(99);
});

it('does not modify authentication actors even if previously linked', function (array $attributes) {
    $actor = localDolibarrActor($attributes);
    $this->client->shouldReceive('getThirdParty')->with(42)->once()->andReturn(dolibarrCustomer());

    expect($this->sync->link(42, $actor->id)['reason'])->toBe('protected_actor');

    $actor->update(['dolibarr_socid' => 42]);

    expect($this->sync->sync(dolibarrCustomer())['reason'])->toBe('protected_actor')
        ->and($actor->refresh()->name)->toBe('Local actor');
})->with([
    'login' => [['login' => 'testuser']],
    'system role' => [['default_role' => 'DBRW']],
]);

it('does not link a nonexistent or mismatched remote record', function (?array $remote) {
    $actor = localDolibarrActor();
    $this->client->shouldReceive('getThirdParty')->with(42)->once()->andReturn($remote);

    expect($this->sync->link(42, $actor->id)['status'])->toBe('invalid')
        ->and($actor->refresh()->dolibarr_socid)->toBeNull();
})->with([
    'missing' => [null],
    'different remote ID' => [dolibarrCustomer(['id' => 43])],
]);
