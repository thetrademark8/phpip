<?php

use App\Contracts\Renewal\DolibarrClientInterface;
use App\Exceptions\Dolibarr\DolibarrConfigurationException;
use App\Exceptions\Dolibarr\DolibarrRequestException;
use App\Services\Renewal\DolibarrClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set([
        'renewal.api.dolibarr_url' => 'https://dolibarr.test/api/index.php',
        'renewal.api.DOLAPIKEY' => 'top-secret-api-key',
        'renewal.api.connect_timeout' => 3,
        'renewal.api.timeout' => 9,
        'renewal.api.retry_times' => 2,
        'renewal.api.retry_sleep_ms' => 0,
    ]);

    Http::preventStrayRequests();
});

test('the Dolibarr client is registered in the service container', function () {
    expect(app(DolibarrClientInterface::class))->toBeInstanceOf(DolibarrClient::class);
});

test('it verifies ref_ext compatibility from the Dolibarr status endpoint', function () {
    Http::fake(['*' => Http::response([
        'success' => ['dolibarr_version' => '12.0.0'],
    ])]);

    app(DolibarrClientInterface::class)->assertInvoiceExternalReferenceSupported();

    Http::assertSent(fn (Request $request): bool => $request->url()
        === 'https://dolibarr.test/api/index.php/status');
});

test('it rejects Dolibarr versions without invoice ref_ext lookup', function () {
    Http::fake(['*' => Http::response([
        'success' => ['dolibarr_version' => '11.0.5'],
    ])]);

    expect(fn () => app(DolibarrClientInterface::class)->assertInvoiceExternalReferenceSupported())
        ->toThrow(DolibarrConfigurationException::class);
});

test('it searches third parties with authentication and configured timeouts', function () {
    $options = [];

    Http::fake(function (Request $request, array $requestOptions) use (&$options) {
        $options = $requestOptions;

        return Http::response([
            ['id' => '12', 'name' => 'Acme & Partners'],
        ]);
    });

    $thirdParties = app(DolibarrClientInterface::class)->searchThirdParties('Acme "100%"_');

    expect($thirdParties)->toBe([
        ['id' => '12', 'name' => 'Acme & Partners'],
    ])->and($options['connect_timeout'])->toBe(3.0)
        ->and($options['timeout'])->toBe(9.0);

    Http::assertSent(function (Request $request) {
        return $request->method() === 'GET'
            && $request->url() === 'https://dolibarr.test/api/index.php/thirdparties?mode=1&sqlfilters=%28t.nom%3Alike%3A%22%25Acme%20%5C%22100%5C%25%5C%22%5C_%25%22%29'
            && $request->hasHeader('DOLAPIKEY', 'top-secret-api-key');
    });
});

test('it normalises a single third party object as a list', function () {
    Http::fake([
        '*' => Http::response(['id' => 12, 'name' => 'Acme']),
    ]);

    expect(app(DolibarrClientInterface::class)->searchThirdParties('Acme'))
        ->toBe([['id' => 12, 'name' => 'Acme']]);
});

test('it finds an invoice through the external reference endpoint', function () {
    Http::fake([
        '*' => Http::response([
            ['id' => 91, 'ref_ext' => 'REN/01'],
        ]),
    ]);

    $invoice = app(DolibarrClientInterface::class)->getInvoiceByExternalReference('REN/01');

    expect($invoice)->toBe(['id' => 91, 'ref_ext' => 'REN/01']);

    Http::assertSent(fn (Request $request) => $request->url() === 'https://dolibarr.test/api/index.php/invoices/ref_ext/REN%2F01');
});

test('it returns null when the external reference endpoint returns 404', function () {
    Http::fake(['*' => Http::response(['error' => ['message' => 'not found']], 404)]);

    expect(app(DolibarrClientInterface::class)->getInvoiceByExternalReference('REN-404'))->toBeNull();
});

test('it normalises scalar and object invoice creation responses', function (mixed $response, array $expected) {
    Http::fake(['*' => Http::response($response)]);

    $invoice = app(DolibarrClientInterface::class)->createDraftInvoice([
        'socid' => 12,
        'ref_ext' => 'REN-01',
        'lines' => [],
    ]);

    expect($invoice)->toBe($expected);
})->with([
    'integer identifier' => [91, ['id' => 91]],
    'numeric string identifier' => ['91', ['id' => 91]],
    'object with id' => [['id' => '91', 'ref' => '(PROV91)'], ['id' => 91, 'ref' => '(PROV91)']],
    'object with rowid' => [['rowid' => '91', 'ref' => '(PROV91)'], ['rowid' => '91', 'ref' => '(PROV91)', 'id' => 91]],
]);

test('it never retries invoice creation', function () {
    $calls = 0;

    Http::fake(function () use (&$calls) {
        $calls++;

        return Http::response(['error' => ['message' => 'secret upstream detail']], 503);
    });

    try {
        app(DolibarrClientInterface::class)->createDraftInvoice(['socid' => 12]);
    } catch (DolibarrRequestException $exception) {
        expect($exception->statusCode())->toBe(503);
    }

    expect($calls)->toBe(1);
});

test('it retries transient GET failures only', function () {
    Http::fakeSequence()
        ->push(['error' => ['message' => 'temporary']], 503)
        ->push([['id' => 12]], 200);

    expect(app(DolibarrClientInterface::class)->searchThirdParties('Acme'))
        ->toBe([['id' => 12]]);

    Http::assertSentCount(2);
});

test('it does not retry permanent GET failures', function () {
    Http::fake(['*' => Http::response(['error' => ['message' => 'forbidden']], 403)]);

    try {
        app(DolibarrClientInterface::class)->searchThirdParties('Acme');
    } catch (DolibarrRequestException $exception) {
        expect($exception->statusCode())->toBe(403);
    }

    Http::assertSentCount(1);
});

test('it retries GET connection failures and hides transport details', function () {
    Http::fakeSequence()
        ->pushFailedConnection('top-secret-api-key at https://internal.example.test')
        ->pushFailedConnection('top-secret-api-key at https://internal.example.test');

    try {
        app(DolibarrClientInterface::class)->searchThirdParties('Acme');
        $this->fail('A Dolibarr exception should have been thrown.');
    } catch (DolibarrRequestException $exception) {
        expect($exception->getMessage())->toBe('Dolibarr could not be reached. Please retry later.')
            ->not->toContain('top-secret-api-key')
            ->not->toContain('internal.example.test')
            ->and($exception->statusCode())->toBeNull();
    }

    Http::assertSentCount(2);
});

test('it exposes validation status without exposing the remote response', function () {
    Http::fake(['*' => Http::response([
        'error' => ['message' => 'socid contains top-secret-api-key'],
    ], 422)]);

    try {
        app(DolibarrClientInterface::class)->createDraftInvoice(['socid' => null]);
        $this->fail('A Dolibarr exception should have been thrown.');
    } catch (DolibarrRequestException $exception) {
        expect($exception->getMessage())->toBe('Dolibarr rejected the request.')
            ->not->toContain('top-secret-api-key')
            ->and($exception->statusCode())->toBe(422);
    }
});

test('it sanitises remote and connection errors', function () {
    Http::fake(['*' => Http::response([
        'error' => ['message' => 'top-secret-api-key at https://internal.example.test'],
    ], 401)]);

    try {
        app(DolibarrClientInterface::class)->searchThirdParties('Acme');
        $this->fail('A Dolibarr exception should have been thrown.');
    } catch (DolibarrRequestException $exception) {
        expect($exception->getMessage())->toBe('Dolibarr authentication failed.')
            ->not->toContain('top-secret-api-key')
            ->not->toContain('internal.example.test')
            ->and($exception->operation())->toBe('search third parties')
            ->and($exception->statusCode())->toBe(401);
    }
});

test('it rejects invalid JSON and embedded API errors', function (mixed $response, int $status) {
    Http::fake(['*' => Http::response($response, $status)]);

    expect(fn () => app(DolibarrClientInterface::class)->searchThirdParties('Acme'))
        ->toThrow(DolibarrRequestException::class);
})->with([
    'invalid JSON' => ['not-json', 200],
    'embedded error' => [['error' => ['message' => 'failure']], 200],
]);

test('it fails safely when mandatory configuration is missing', function (string $setting) {
    config()->set($setting, null);

    expect(fn () => app(DolibarrClientInterface::class)->searchThirdParties('Acme'))
        ->toThrow(DolibarrConfigurationException::class);
})->with([
    'API URL' => ['renewal.api.dolibarr_url'],
    'API key' => ['renewal.api.DOLAPIKEY'],
]);

test('it lists customers with explicit zero-based pagination and stable ordering', function () {
    $customers = [['id' => '42', 'name' => 'Customer', 'client' => '3']];
    Http::fake(['*' => Http::response($customers)]);

    expect(app(DolibarrClientInterface::class)->listCustomerThirdParties(2, 50))->toBe($customers);

    Http::assertSent(function (Request $request) {
        parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);

        return $request->method() === 'GET'
            && $request->hasHeader('DOLAPIKEY', 'top-secret-api-key')
            && $query === ['mode' => '1', 'sortfield' => 't.rowid', 'sortorder' => 'ASC', 'page' => '2', 'limit' => '50'];
    });
});

test('it recognises empty customer pages across supported Dolibarr versions', function (string $message) {
    Http::fake(['*' => Http::response(['error' => ['code' => 404, 'message' => $message]], 404)]);

    expect(app(DolibarrClientInterface::class)->listCustomerThirdParties())->toBe([]);
})->with(['Thirdparties not found', 'No customers found']);

test('it rejects invalid customer list responses instead of reporting an empty sync', function (mixed $body, int $status) {
    Http::fake(['*' => Http::response($body, $status)]);

    expect(fn () => app(DolibarrClientInterface::class)->listCustomerThirdParties())
        ->toThrow(DolibarrRequestException::class);
})->with([
    'missing route' => [['error' => ['message' => 'Not Found']], 404],
    'html error page' => ['<html>Not Found</html>', 404],
    'invalid JSON' => ['not-json', 200],
    'unexpected envelope' => [['data' => [], 'pagination' => []], 200],
    'invalid item' => [[42], 200],
    'forbidden' => [['error' => ['message' => 'Forbidden']], 403],
]);

test('it accepts a successful empty customer list', function () {
    Http::fake(['*' => Http::response([])]);

    expect(app(DolibarrClientInterface::class)->listCustomerThirdParties())->toBe([]);
});

test('it rejects unsafe pagination before sending a request', function (int $page, int $limit) {
    Http::fake();

    expect(fn () => app(DolibarrClientInterface::class)->listCustomerThirdParties($page, $limit))
        ->toThrow(InvalidArgumentException::class);

    Http::assertNothingSent();
})->with([[-1, 100], [0, 0], [0, -1], [0, 1001]]);

test('it fetches full third-party details by ID', function () {
    $customer = ['id' => '42', 'name' => 'Customer', 'address' => 'Street', 'country_code' => 'FR'];
    Http::fake(['*' => Http::response($customer)]);

    expect(app(DolibarrClientInterface::class)->getThirdParty(42))->toBe($customer);

    Http::assertSent(fn (Request $request) => $request->method() === 'GET'
        && $request->url() === 'https://dolibarr.test/api/index.php/thirdparties/42');
});

test('it returns null for a missing third party', function () {
    Http::fake(['*' => Http::response(['error' => ['message' => 'Thirdparty not found']], 404)]);

    expect(app(DolibarrClientInterface::class)->getThirdParty(42))->toBeNull();
});

test('it rejects incorrect third-party detail responses', function (array $body) {
    Http::fake(['*' => Http::response($body)]);

    expect(fn () => app(DolibarrClientInterface::class)->getThirdParty(42))
        ->toThrow(DolibarrRequestException::class);
})->with([
    'empty' => [[]],
    'wrong ID' => [['id' => 43]],
    'missing ID' => [['name' => 'Customer']],
    'invalid ID type' => [['id' => [42]]],
    'list' => [[['id' => 42]]],
]);

test('it rejects a non-positive third-party ID before sending a request', function () {
    Http::fake();

    expect(fn () => app(DolibarrClientInterface::class)->getThirdParty(0))
        ->toThrow(InvalidArgumentException::class);

    Http::assertNothingSent();
});

test('customer search returns no matches for Dolibarr empty-result responses', function (string $message) {
    Http::fake(['*' => Http::response(['error' => ['message' => $message]], 404)]);

    expect(app(DolibarrClientInterface::class)->searchThirdParties('Unknown customer'))->toBe([]);
})->with(['Thirdparties not found', 'No customers found']);

test('customer search still fails when the API route is missing', function () {
    Http::fake(['*' => Http::response(['error' => ['message' => 'Not Found']], 404)]);

    expect(fn () => app(DolibarrClientInterface::class)->searchThirdParties('Customer'))
        ->toThrow(DolibarrRequestException::class);
});
