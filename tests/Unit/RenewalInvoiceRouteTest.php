<?php

use Illuminate\Http\Request;
use Illuminate\Routing\Route;

test('renewal invoice creation requires read-write permission', function () {
    /** @var Route $route */
    $route = app('router')->getRoutes()->getByName('renewal.invoice');

    expect($route)->not->toBeNull()
        ->and($route->gatherMiddleware())->toContain('auth', 'can:readwrite')
        ->and($route->matches(Request::create('/renewal/invoice/0', 'POST')))->toBeTrue()
        ->and($route->matches(Request::create('/renewal/invoice/1', 'POST')))->toBeTrue()
        ->and($route->matches(Request::create('/renewal/invoice/2', 'POST')))->toBeFalse();

    /** @var Route $manualRoute */
    $manualRoute = app('router')->getRoutes()->getByName('renewal.renewalsInvoiced');

    expect($manualRoute)->not->toBeNull()
        ->and($manualRoute->gatherMiddleware())->toContain('auth', 'can:readwrite');
});
