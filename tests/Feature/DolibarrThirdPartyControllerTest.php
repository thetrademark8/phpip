<?php

use App\Contracts\Renewal\DolibarrClientInterface;
use App\Models\Actor;
use App\Models\User;

test('dolibarr third-party autocomplete requires authentication', function () {
    $this->getJson(route('dolibarr.third-parties.autocomplete', ['term' => 'Acme']))
        ->assertUnauthorized();
});

test('dolibarr third-party autocomplete requires read-write permission', function () {
    $user = User::factory()->create(['default_role' => 'DBRO']);

    $this->actingAs($user)
        ->getJson(route('dolibarr.third-parties.autocomplete', ['term' => 'Acme']))
        ->assertForbidden();
});

test('dolibarr third-party autocomplete returns only useful identification fields', function () {
    $user = User::factory()->create(['default_role' => 'DBRW']);
    $client = $this->mock(DolibarrClientInterface::class);
    $client->shouldReceive('searchThirdParties')
        ->once()
        ->with('Acme')
        ->andReturn([
            [
                'id' => '42',
                'name' => 'Acme GmbH',
                'code_client' => 'CU-0042',
                'email' => 'accounts@acme.test',
                'address' => 'must not be exposed',
            ],
        ]);

    $this->actingAs($user)
        ->getJson(route('dolibarr.third-parties.autocomplete', ['term' => ' Acme ']))
        ->assertOk()
        ->assertExactJson([
            [
                'id' => 42,
                'value' => 42,
                'name' => 'Acme GmbH',
                'label' => 'Acme GmbH · CU-0042 · accounts@acme.test',
                'subtitle' => '#42',
                'code_client' => 'CU-0042',
                'email' => 'accounts@acme.test',
            ],
        ]);
});

test('dolibarr third-party autocomplete does not issue broad searches', function () {
    $user = User::factory()->create(['default_role' => 'DBA']);
    $client = $this->mock(DolibarrClientInterface::class);
    $client->shouldNotReceive('searchThirdParties');

    $this->actingAs($user)
        ->getJson(route('dolibarr.third-parties.autocomplete', ['term' => 'A']))
        ->assertOk()
        ->assertExactJson([]);
});

test('read-write users can associate and clear an actor dolibarr third party', function () {
    $this->mock(DolibarrClientInterface::class)->shouldReceive('getThirdParty')
        ->once()->with(42)->andReturn(['id' => 42, 'client' => 1]);
    $user = User::factory()->create(['default_role' => 'DBRW']);
    $actor = Actor::factory()->create();

    $this->actingAs($user)
        ->patchJson(route('actor.update', $actor), ['dolibarr_socid' => 42])
        ->assertOk()
        ->assertJsonPath('actor.dolibarr_socid', 42);

    expect($actor->refresh()->dolibarr_socid)->toBe(42);

    $this->actingAs($user)
        ->patchJson(route('actor.update', $actor), ['dolibarr_socid' => null])
        ->assertOk();

    expect($actor->refresh()->dolibarr_socid)->toBeNull();
});

test('an actor dolibarr third-party association must be unique', function () {
    $user = User::factory()->create(['default_role' => 'DBA']);
    Actor::factory()->create(['dolibarr_socid' => 42]);
    $actor = Actor::factory()->create();

    $this->actingAs($user)
        ->patchJson(route('actor.update', $actor), ['dolibarr_socid' => 42])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('dolibarr_socid');
});

test('read-only users cannot update an actor dolibarr association', function () {
    $user = User::factory()->create(['default_role' => 'DBRO']);
    $actor = Actor::factory()->create();

    $this->actingAs($user)
        ->patchJson(route('actor.update', $actor), ['dolibarr_socid' => 42])
        ->assertForbidden();

    expect($actor->refresh()->dolibarr_socid)->toBeNull();
});
