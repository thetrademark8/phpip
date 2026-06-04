<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('rule index page is accessible to users with DBRW role', function () {
    // Create a user with proper role/permissions
    $user = User::factory()->create(['default_role' => 'DBRW']);
    $this->actingAs($user);

    // Main page with rules list — controller returns Inertia (`Rule/Index`)
    $response = $this->get('/rule');

    $response->assertStatus(200)
        ->assertInertia(fn (Assert $page) => $page->component('Rule/Index')->has('rules'));
});

test('rule index page is accessible to read-only users', function () {
    // DBRO has read access through the `readonly` Gate; previous version of
    // this test expected a 403, which never reflected actual app behavior.
    $user = User::factory()->create(['default_role' => 'DBRO']);
    $this->actingAs($user);

    $response = $this->get('/rule');

    $response->assertStatus(200);
});

test('rule index page redirects unauthenticated users', function () {
    $response = $this->get('/rule');

    $response->assertRedirect('/login');
});
