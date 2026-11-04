<?php

use App\Contracts\Renewal\DolibarrClientInterface;
use App\Services\Dolibarr\DolibarrActorSyncService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    config()->set('dolibarr.sync.enabled', true);
    config()->set('cache.default', 'array');
});

it('does not call Dolibarr when customer synchronization is disabled', function () {
    config()->set('dolibarr.sync.enabled', false);
    $this->mock(DolibarrClientInterface::class)->shouldNotReceive('listCustomerThirdParties');
    $this->artisan('dolibarr:sync')->assertFailed();
});

it('previews every page by default without activating invoicing', function () {
    config()->set('renewal.invoice.backend', 'none');
    $client = $this->mock(DolibarrClientInterface::class);
    // Even a short page must not terminate enumeration: the server may cap page size.
    $client->shouldReceive('listCustomerThirdParties')->once()->with(0, 100)->andReturn([['id' => '42', 'name' => 'Example']]);
    $client->shouldReceive('listCustomerThirdParties')->once()->with(1, 100)->andReturn([]);
    $this->mock(DolibarrActorSyncService::class)->shouldReceive('sync')->once()
        ->with(['id' => '42', 'name' => 'Example'], true)
        ->andReturn(['status' => 'created', 'remote_id' => 42, 'actor_id' => null]);

    $this->artisan('dolibarr:sync')->expectsOutputToContain('PREVIEW')->assertSuccessful();
    expect(config('renewal.invoice.backend'))->toBe('none');
});

it('applies imports explicitly and returns failure for unresolved records while continuing', function () {
    $client = $this->mock(DolibarrClientInterface::class);
    $client->shouldReceive('listCustomerThirdParties')->with(0, 100)->once()->andReturn([['id' => 42], ['id' => 43]]);
    $client->shouldReceive('listCustomerThirdParties')->with(1, 100)->once()->andReturn([]);
    $sync = $this->mock(DolibarrActorSyncService::class);
    $sync->shouldReceive('sync')->once()->with(['id' => 42], false)->andReturn([
        'status' => 'conflict', 'remote_id' => 42, 'candidate_ids' => [5, 6], 'reason' => 'existing_actor',
    ]);
    $sync->shouldReceive('sync')->once()->with(['id' => 43], false)->andReturn([
        'status' => 'created', 'remote_id' => 43, 'actor_id' => 7,
    ]);

    $this->artisan('dolibarr:sync --apply')->expectsOutputToContain('candidates=5,6')->expectsOutputToContain('created=1')->assertFailed();
});

it('only processes explicitly requested associations when link options are supplied', function () {
    $this->mock(DolibarrClientInterface::class)->shouldNotReceive('listCustomerThirdParties');
    $this->mock(DolibarrActorSyncService::class)->shouldReceive('link')->once()->with(42, 5, false)
        ->andReturn(['status' => 'updated', 'remote_id' => 42, 'actor_id' => 5]);

    $this->artisan('dolibarr:sync --link=42:5 --apply')->assertSuccessful();
});

it('rejects malformed link arguments and conflicting execution modes', function (string $arguments) {
    $this->mock(DolibarrClientInterface::class)->shouldNotReceive('listCustomerThirdParties');
    $this->mock(DolibarrActorSyncService::class)->shouldNotReceive('link');
    $this->artisan('dolibarr:sync ' . $arguments)->assertFailed();
})->with(['--link=0:5', '--link=foo:5', '--link=42:5:6', '--apply --dry-run', '--link=42:5 --link=43:5', '--link=42:5 --link=42:6']);

it('refuses a concurrent synchronization', function () {
    $lock = Cache::lock('dolibarr-customer-sync', 3600);
    $lock->get();
    $this->mock(DolibarrClientInterface::class)->shouldNotReceive('listCustomerThirdParties');
    try {
        $this->artisan('dolibarr:sync')->expectsOutputToContain('Another Dolibarr')->assertFailed();
    } finally {
        $lock->release();
    }
});

it('aborts repeated pagination and releases its lock', function () {
    $client = $this->mock(DolibarrClientInterface::class);
    $client->shouldReceive('listCustomerThirdParties')->with(0, 100)->once()->andReturn([['id' => 42]]);
    $client->shouldReceive('listCustomerThirdParties')->with(1, 100)->once()->andReturn([['id' => 42]]);
    $this->mock(DolibarrActorSyncService::class)->shouldReceive('sync')->once()->andReturn([
        'status' => 'unchanged', 'remote_id' => 42, 'actor_id' => 5,
    ]);
    $this->artisan('dolibarr:sync')->assertFailed();
    $lock = Cache::lock('dolibarr-customer-sync', 3600);
    expect($lock->get())->toBeTrue();
    $lock->release();
});

it('does not expose transport details and logs an unsuccessful run', function () {
    Log::spy();
    $this->mock(DolibarrClientInterface::class)->shouldReceive('listCustomerThirdParties')
        ->andThrow(new RuntimeException('secret-api-key and customer data'));

    $this->artisan('dolibarr:sync')->doesntExpectOutputToContain('secret-api-key')->assertFailed();
    Log::shouldHaveReceived('warning')->with('Dolibarr customer synchronization interrupted', ['exception' => RuntimeException::class])->once();
    Log::shouldHaveReceived('info')->withArgs(fn ($message, $context) => $context['interrupted'] === true)->once();
});

it('reports collisions between new customers during a read-only preview', function () {
    $client = $this->mock(DolibarrClientInterface::class);
    $client->shouldReceive('listCustomerThirdParties')->with(0, 100)->andReturn([
        ['id' => 42, 'name' => 'Example-SAS'], ['id' => 43, 'nom' => 'Example SAS'],
    ]);
    $client->shouldReceive('listCustomerThirdParties')->with(1, 100)->andReturn([]);
    $this->mock(DolibarrActorSyncService::class)->shouldReceive('sync')->twice()->andReturn(
        ['status' => 'created', 'remote_id' => 42], ['status' => 'created', 'remote_id' => 43],
    );

    $this->artisan('dolibarr:sync')->expectsOutputToContain('matching_customer_in_same_import')->assertFailed();
});
