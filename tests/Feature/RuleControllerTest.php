<?php

use App\Models\User;

test('rule index page is accessible to users with DBRW role', function () {
    // Create a user with proper role/permissions
    $user = User::factory()->create(['default_role' => 'DBRW']);
    $this->actingAs($user);
    
    // Main page with rules list
    $response = $this->get('/rule');
    
    $response->assertStatus(200)
        ->assertViewHas('ruleslist');
});

test('rule index page requires proper authorization', function () {
    // Create a user without proper role
    $user = User::factory()->create(['default_role' => 'DRO']);
    $this->actingAs($user);
    
    // Try to access rules page
    $response = $this->get('/rule');
    
    $response->assertStatus(403);
});
