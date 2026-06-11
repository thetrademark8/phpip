<?php

use App\Models\Event;
use App\Models\Matter;
use App\Models\Task;
use App\Services\Email\PlaceholderService;
use Carbon\Carbon;

function resolveMatterPlaceholder(Matter $matter, string $placeholder): string
{
    return app(PlaceholderService::class)
        ->setMatter($matter)
        ->resolve($placeholder);
}

it('resolves the opposition deadline from the WAT task triggered by the publication event', function () {
    $matter = Matter::factory()->trademark()->create(['country' => 'FR']);
    $publication = Event::factory()->publication()->forMatter($matter)->create();
    Task::factory()->forEvent($publication)->create([
        'code' => 'WAT',
        'due_date' => '2026-09-15',
        'detail' => null,
    ]);

    expect(resolveMatterPlaceholder($matter, '{{matter.opposition_deadline}}'))
        ->toBe(Carbon::parse('2026-09-15')->isoFormat('L'));
});

it('ignores WAT tasks triggered by events other than publication for the opposition deadline', function () {
    $matter = Matter::factory()->trademark()->create(['country' => 'FR']);
    $filing = Event::factory()->filing()->forMatter($matter)->create();
    Task::factory()->forEvent($filing)->create([
        'code' => 'WAT',
        'due_date' => '2026-09-15',
        'detail' => null,
    ]);

    expect(resolveMatterPlaceholder($matter, '{{matter.opposition_deadline}}'))->toBe('');
});

it('prefers the FOP task over the WAT task for the opposition deadline', function () {
    $matter = Matter::factory()->trademark()->create(['country' => 'FR']);
    $filing = Event::factory()->filing()->forMatter($matter)->create();
    $publication = Event::factory()->publication()->forMatter($matter)->create();
    Task::factory()->forEvent($filing)->create([
        'code' => 'FOP',
        'due_date' => '2026-05-01',
        'detail' => null,
    ]);
    Task::factory()->forEvent($publication)->create([
        'code' => 'WAT',
        'due_date' => '2026-09-15',
        'detail' => null,
    ]);

    expect(resolveMatterPlaceholder($matter, '{{matter.opposition_deadline}}'))
        ->toBe(Carbon::parse('2026-05-01')->isoFormat('L'));
});

it('resolves the priority deadline from the PRID task on the matter itself', function () {
    $matter = Matter::factory()->trademark()->create(['country' => 'FR']);
    $filing = Event::factory()->filing()->forMatter($matter)->create();
    Task::factory()->forEvent($filing)->create([
        'code' => 'PRID',
        'due_date' => '2026-07-01',
        'detail' => null,
    ]);

    expect(resolveMatterPlaceholder($matter, '{{matter.priority_deadline}}'))
        ->toBe(Carbon::parse('2026-07-01')->isoFormat('L'));
});

it('falls back to the container matter for the priority deadline', function () {
    $container = Matter::factory()->trademark()->create(['country' => 'FR']);
    $containerFiling = Event::factory()->filing()->forMatter($container)->create();
    Task::factory()->forEvent($containerFiling)->create([
        'code' => 'PRID',
        'due_date' => '2026-07-01',
        'detail' => null,
    ]);

    $child = Matter::factory()->trademark()->create([
        'country' => 'DE',
        'caseref' => $container->caseref,
        'container_id' => $container->id,
    ]);
    Event::factory()->filing()->forMatter($child)->create();

    expect(resolveMatterPlaceholder($child, '{{matter.priority_deadline}}'))
        ->toBe(Carbon::parse('2026-07-01')->isoFormat('L'));
});

it('uses the matter own renewal task even when the matter has a container', function () {
    $container = Matter::factory()->trademark()->create(['country' => 'FR']);
    $containerFiling = Event::factory()->filing()->forMatter($container)->create();
    Task::factory()->forEvent($containerFiling)->create([
        'code' => 'REN',
        'due_date' => '2026-03-01',
        'detail' => null,
    ]);

    $child = Matter::factory()->trademark()->create([
        'country' => 'DE',
        'caseref' => $container->caseref,
        'container_id' => $container->id,
    ]);
    $childFiling = Event::factory()->filing()->forMatter($child)->create();
    Task::factory()->forEvent($childFiling)->create([
        'code' => 'REN',
        'due_date' => '2026-12-01',
        'detail' => null,
    ]);

    expect(resolveMatterPlaceholder($child, '{{matter.next_renewal}}'))
        ->toBe(Carbon::parse('2026-12-01')->isoFormat('L'));
});
