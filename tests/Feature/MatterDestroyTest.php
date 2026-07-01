<?php

use App\Models\Matter;
use App\Models\User;

test('a matter without dependent matters can be deleted', function () {
    $user = User::factory()->create(['default_role' => 'DBRW']);
    $this->actingAs($user);

    $matter = Matter::factory()->create();

    $response = $this->delete("/matter/{$matter->id}");

    $response->assertRedirect(route('matter.index'));
    $response->assertSessionHas('success');
    $this->assertDatabaseMissing('matter', ['id' => $matter->id]);
});

test('a matter with child matters cannot be deleted and returns a friendly error', function () {
    $user = User::factory()->create(['default_role' => 'DBRW']);
    $this->actingAs($user);

    $parent = Matter::factory()->create();
    Matter::factory()->childOf($parent)->create();

    $response = $this->delete("/matter/{$parent->id}");

    $response->assertRedirect(route('matter.index'));
    $response->assertSessionHas('error');
    // The parent must still exist — no 500 from the foreign key constraint.
    $this->assertDatabaseHas('matter', ['id' => $parent->id]);
});

test('a container matter cannot be deleted while it holds contained matters', function () {
    $user = User::factory()->create(['default_role' => 'DBRW']);
    $this->actingAs($user);

    $container = Matter::factory()->create();
    Matter::factory()->containedBy($container)->create();

    $response = $this->delete("/matter/{$container->id}");

    $response->assertRedirect(route('matter.index'));
    $response->assertSessionHas('error');
    $this->assertDatabaseHas('matter', ['id' => $container->id]);
});
