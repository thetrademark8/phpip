<?php

use App\Models\User;

test('actor index page is accessible to authenticated users', function () {
    // Create and authenticate a user with readonly access
    $user = User::factory()->create(['default_role' => 'DBRO']);
    $this->actingAs($user);
    
    // Main page with actors list
    $response = $this->get('/actor');
    
    $response->assertStatus(200)
        ->assertViewHas('actorslist');
});

test('actor index page requires authentication', function () {
    // Try to access without authentication
    $response = $this->get('/actor');
    
    $response->assertRedirect('/login');
});
