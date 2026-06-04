<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('actor index page is accessible to authenticated users', function () {
    // Create and authenticate a user with readonly access
    $user = User::factory()->create(['default_role' => 'DBRO']);
    $this->actingAs($user);

    // Main page with actors list — controller returns Inertia (`Actor/Index`)
    $response = $this->get('/actor');

    $response->assertStatus(200)
        ->assertInertia(fn (Assert $page) => $page->component('Actor/Index')->has('actors'));
});

test('actor index page requires authentication', function () {
    // Try to access without authentication
    $response = $this->get('/actor');

    $response->assertRedirect('/login');
});
