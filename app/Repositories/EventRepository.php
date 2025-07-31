<?php

namespace App\Repositories;

use App\Repositories\Contracts\EventRepositoryInterface;
use App\Models\Event;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class EventRepository implements EventRepositoryInterface
{
    public function find(int $id): ?Event
    {
        return Event::find($id);
    }

    public function findByMatterAndCode(int $matterId, string $code): Collection
    {
        return Event::where('matter_id', $matterId)
            ->where('code', $code)
            ->orderBy('event_date', 'desc')
            ->get();
    }

    public function getRenewalEvents(int $matterId): Collection
    {
        return Event::where('matter_id', $matterId)
            ->whereHas('tasks', function($query) {
                $query->where('code', 'REN');
            })
            ->with(['tasks' => function($query) {
                $query->where('code', 'REN');
            }])
            ->orderBy('event_date', 'desc')
            ->get();
    }

    public function create(array $data): Event
    {
        return Event::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return Event::where('id', $id)->update($data) > 0;
    }

    public function delete(int $id): bool
    {
        $event = Event::find($id);
        
        if (!$event) {
            return false;
        }
        
        return $event->delete();
    }

    public function getByDateRange(\DateTime $from, \DateTime $to): Collection
    {
        return Event::whereBetween('event_date', [
            $from->format('Y-m-d'),
            $to->format('Y-m-d')
        ])->get();
    }

    public function getOverdueEvents(): Collection
    {
        return Event::whereHas('tasks', function($query) {
            $query->where('done', 0)
                ->where('due_date', '<', Carbon::now());
        })
        ->with(['tasks' => function($query) {
            $query->where('done', 0)
                ->where('due_date', '<', Carbon::now());
        }])
        ->get();
    }

    public function markAsCompleted(int $id, ?\DateTime $completedDate = null): bool
    {
        $event = Event::find($id);
        
        if (!$event) {
            return false;
        }
        
        // Mark associated tasks as done
        $date = $completedDate ? $completedDate->format('Y-m-d') : Carbon::now()->format('Y-m-d');
        
        return $event->tasks()->update([
            'done' => 1,
            'done_date' => $date
        ]) > 0;
    }

    public function getTemplates(): Collection
    {
        return Event::where('is_template', true)
            ->orderBy('code')
            ->get();
    }

    public function getForExport(array $filters = []): Collection
    {
        $query = Event::with(['matter', 'tasks']);
        
        if (!empty($filters['code'])) {
            $query->where('code', $filters['code']);
        }
        
        if (!empty($filters['matter_id'])) {
            $query->where('matter_id', $filters['matter_id']);
        }
        
        if (!empty($filters['from_date'])) {
            $query->where('event_date', '>=', $filters['from_date']);
        }
        
        if (!empty($filters['until_date'])) {
            $query->where('event_date', '<=', $filters['until_date']);
        }
        
        if (!empty($filters['done'])) {
            $query->whereHas('tasks', function($q) use ($filters) {
                $q->where('done', $filters['done']);
            });
        }
        
        return $query->orderBy('event_date', 'desc')->get();
    }
}