<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->in('Feature');

pest()->extend(Tests\TestCase::class)
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeIsoDate', function () {
    return $this->toMatch('/^\d{4}-\d{2}-\d{2}$/');
});

expect()->extend('toBeDateTime', function () {
    return $this->toMatch('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/');
});

expect()->extend('toFollowSolid', function () {
    // This is a placeholder - we'll implement SOLID checks with architecture tests
    return $this;
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Create and authenticate a user for testing
 */
function actingAs(?User $user = null): User
{
    $user = $user ?? User::factory()->create();
    test()->actingAs($user);
    return $user;
}

/**
 * Create a user with a specific role
 */
function actingAsRole(string $role): User
{
    $user = User::factory()->create(['default_role' => $role]);
    test()->actingAs($user);
    return $user;
}

/**
 * Assert that a response is a successful JSON response
 */
function assertSuccessfulJson($response): void
{
    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'application/json');
}

/**
 * Helper to create test data following SOLID principles
 */
function createTestMatter(array $attributes = []): \App\Models\Matter
{
    return \App\Models\Matter::factory()->create($attributes);
}

/**
 * Helper to create test actor
 */
function createTestActor(array $attributes = []): \App\Models\Actor
{
    return \App\Models\Actor::factory()->create($attributes);
}

/*
|--------------------------------------------------------------------------
| Architecture Testing
|--------------------------------------------------------------------------
|
| Architecture tests ensure your code follows SOLID principles and maintains
| clean architecture patterns.
|
*/

arch('controllers follow single responsibility')
    ->expect('App\Http\Controllers')
    ->toHaveSuffix('Controller')
    ->not->toHaveMethod('__invoke')->ignoring('App\Http\Controllers\Api');

arch('models are not bloated')
    ->expect('App\Models')
    ->toHaveMethodsCountLessThan(30);

arch('services follow interface segregation')
    ->expect('App\Services')
    ->toImplement('App\Contracts\Services');

arch('no direct database queries in controllers')
    ->expect('App\Http\Controllers')
    ->not->toUse(['DB', 'Illuminate\Support\Facades\DB']);

arch('dependency injection is used')
    ->expect('App\Services')
    ->toOnlyDependOn([
        'App\Models',
        'App\Contracts',
        'Illuminate',
        'Carbon',
    ]);
