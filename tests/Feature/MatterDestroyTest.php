<?php

use App\Models\Matter;
use App\Models\User;

// Pin category_code and country to values guaranteed to exist in the test
// database (see Database\Seeders\Testing\MinimalDataSeeder). The factory picks
// them at random from a wider set, some of which are not seeded and would break
// the foreign keys on insert.
function seededMatter(array $attributes = []): \Illuminate\Database\Eloquent\Factories\Factory
{
    return Matter::factory()->state(array_merge([
        'category_code' => 'TM',
        'country' => 'FR',
    ], $attributes));
}

test('a matter without dependent matters can be deleted', function () {
    $user = User::factory()->create(['default_role' => 'DBRW']);
    $this->actingAs($user);

    $matter = seededMatter()->create();

    $response = $this->delete("/matter/{$matter->id}");

    $response->assertRedirect(route('matter.index'));
    $response->assertSessionHas('success');
    $this->assertDatabaseMissing('matter', ['id' => $matter->id]);
});

test('a matter with child matters cannot be deleted and returns a friendly error', function () {
    $user = User::factory()->create(['default_role' => 'DBRW']);
    $this->actingAs($user);

    $parent = seededMatter()->create();
    seededMatter(['parent_id' => $parent->id, 'caseref' => $parent->caseref])->create();

    $response = $this->delete("/matter/{$parent->id}");

    $response->assertRedirect(route('matter.index'));
    $response->assertSessionHas('error');
    // The parent must still exist — no 500 from the foreign key constraint.
    $this->assertDatabaseHas('matter', ['id' => $parent->id]);
});

test('a container matter cannot be deleted while it holds contained matters', function () {
    $user = User::factory()->create(['default_role' => 'DBRW']);
    $this->actingAs($user);

    $container = seededMatter()->create();
    seededMatter(['container_id' => $container->id])->create();

    $response = $this->delete("/matter/{$container->id}");

    $response->assertRedirect(route('matter.index'));
    $response->assertSessionHas('error');
    $this->assertDatabaseHas('matter', ['id' => $container->id]);
});
