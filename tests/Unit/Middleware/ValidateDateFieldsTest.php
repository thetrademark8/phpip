<?php

use App\Http\Middleware\ValidateDateFields;
use App\Services\DatePickerService;
use App\Services\DateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

beforeEach(function () {
    $this->dateService = new DateService;
    $this->datePickerService = new DatePickerService($this->dateService);
    $this->middleware = new ValidateDateFields($this->datePickerService);
});

it('allows requests without date fields to pass through', function () {
    $request = Request::create('/test', 'POST', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $response = $this->middleware->handle($request, function ($req) {
        return response('success');
    });

    expect($response->getContent())->toBe('success');
});

it('allows GET requests to pass through without validation', function () {
    $request = Request::create('/test', 'GET', [
        'due_date' => 'invalid-date',
    ]);

    $response = $this->middleware->handle($request, function ($req) {
        return response('success');
    });

    expect($response->getContent())->toBe('success');
});

it('validates and converts valid date fields in POST requests', function () {
    $request = Request::create('/test', 'POST', [
        'due_date' => '25/12/2023',
        'start_date' => '01-01-2024',
    ]);

    $capturedRequest = null;
    $response = $this->middleware->handle($request, function ($req) use (&$capturedRequest) {
        $capturedRequest = $req;

        return response('success');
    });

    expect($response->getContent())->toBe('success');
    expect($capturedRequest->input('due_date'))->toBe('2023-12-25');
    expect($capturedRequest->input('start_date'))->toBe('2024-01-01');
});

it('returns validation errors for invalid date fields in form requests', function () {
    $request = Request::create('/test', 'POST', [
        'due_date' => 'invalid-date',
        'start_date' => '32/13/2023',
    ]);

    $response = $this->middleware->handle($request, function ($req) {
        return response('success');
    });

    expect($response)->toBeInstanceOf(RedirectResponse::class);
    expect($response->getSession()->get('errors'))->not->toBeNull();
});

it('returns json validation errors for ajax requests', function () {
    $request = Request::create('/test', 'POST', [
        'due_date' => 'invalid-date',
    ]);
    $request->headers->set('Accept', 'application/json');

    $response = $this->middleware->handle($request, function ($req) {
        return response('success');
    });

    expect($response)->toBeInstanceOf(JsonResponse::class);
    expect($response->getStatusCode())->toBe(422);

    $data = json_decode($response->getContent(), true);
    expect($data)->toHaveKey('errors');
    expect($data['errors'])->toHaveKey('due_date');
});

it('recognizes common date field names', function () {
    $request = Request::create('/test', 'POST', [
        'due_date' => '25/12/2023',
        'filing_date' => '01/01/2024',
        'priority_date' => '15.06.2023',
        'task_due_date' => '20-05-2024',
    ]);

    $capturedRequest = null;
    $response = $this->middleware->handle($request, function ($req) use (&$capturedRequest) {
        $capturedRequest = $req;

        return response('success');
    });

    expect($capturedRequest->input('due_date'))->toBe('2023-12-25');
    expect($capturedRequest->input('filing_date'))->toBe('2024-01-01');
    expect($capturedRequest->input('priority_date'))->toBe('2023-06-15');
    expect($capturedRequest->input('task_due_date'))->toBe('2024-05-20');
});

it('ignores non-date fields even if they contain date-like values', function () {
    $request = Request::create('/test', 'POST', [
        'name' => '25/12/2023',
        'description' => '01/01/2024',
        'notes' => 'Due by 15/06/2023',
    ]);

    $capturedRequest = null;
    $response = $this->middleware->handle($request, function ($req) use (&$capturedRequest) {
        $capturedRequest = $req;

        return response('success');
    });

    expect($capturedRequest->input('name'))->toBe('25/12/2023');
    expect($capturedRequest->input('description'))->toBe('01/01/2024');
    expect($capturedRequest->input('notes'))->toBe('Due by 15/06/2023');
});

it('handles empty date fields gracefully', function () {
    $request = Request::create('/test', 'POST', [
        'due_date' => '',
        'start_date' => null,
        'end_date' => '25/12/2023',
    ]);

    $capturedRequest = null;
    $response = $this->middleware->handle($request, function ($req) use (&$capturedRequest) {
        $capturedRequest = $req;

        return response('success');
    });

    expect($capturedRequest->input('due_date'))->toBe('');
    expect($capturedRequest->input('start_date'))->toBeNull();
    expect($capturedRequest->input('end_date'))->toBe('2023-12-25');
});

it('processes PUT and PATCH requests', function () {
    $putRequest = Request::create('/test', 'PUT', [
        'due_date' => '25/12/2023',
    ]);

    $patchRequest = Request::create('/test', 'PATCH', [
        'due_date' => '01/01/2024',
    ]);

    $capturedPutRequest = null;
    $capturedPatchRequest = null;

    $this->middleware->handle($putRequest, function ($req) use (&$capturedPutRequest) {
        $capturedPutRequest = $req;

        return response('success');
    });

    $this->middleware->handle($patchRequest, function ($req) use (&$capturedPatchRequest) {
        $capturedPatchRequest = $req;

        return response('success');
    });

    expect($capturedPutRequest->input('due_date'))->toBe('2023-12-25');
    expect($capturedPatchRequest->input('due_date'))->toBe('2024-01-01');
});

it('can provide list of recognized date fields', function () {
    $fields = ValidateDateFields::getRecognizedDateFields();

    expect($fields)->toBeArray();
    expect($fields)->toContain('due_date');
    expect($fields)->toContain('filing_date');
    expect($fields)->toContain('priority_date');
});

it('follows dependency injection principles', function () {
    expect($this->middleware)->toBeInstanceOf(ValidateDateFields::class);

    // Verify it's using the DatePickerServiceInterface
    $reflector = new ReflectionClass($this->middleware);
    $constructor = $reflector->getConstructor();
    $params = $constructor->getParameters();

    expect($params[0]->getType()->getName())->toBe('App\Contracts\Services\DatePickerServiceInterface');
});
