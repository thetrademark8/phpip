<?php

namespace App\Http\Controllers;

use App\Events\MatterStatusChanged;
use App\Models\Event;
use App\Models\EventName;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    public function store(Request $request, $matter = null)
    {
        // If matter_id comes from route parameter, use it
        if ($matter) {
            $request->merge(['matter_id' => $matter]);
        }
        
        $this->validate($request, [
            'code' => 'required',
            'matter_id' => 'required|numeric',
            'event_date' => 'required_without:alt_matter_id',
        ]);
        // Date conversion removed - ValidateDateFields middleware now provides ISO format dates
        $request->merge(['creator' => Auth::user()->login]);

        $event = Event::create($request->except(['_token', '_method']));

        // Check if this is a status-changing event and trigger MatterStatusChanged
        $this->checkAndTriggerStatusChange($event);

        // Handle Inertia requests
        if ($request->inertia()) {
            return redirect()->back();
        }

        return $event;
    }

    public function show(Event $event)
    {
        //
    }

    public function update(Request $request, Event $event)
    {
        $this->validate($request, [
            'alt_matter_id' => 'nullable|numeric',
            'event_date' => 'sometimes|required_without:alt_matter_id',
        ]);
        // Date conversion removed - ValidateDateFields middleware now provides ISO format dates
        $request->merge(['updater' => Auth::user()->login]);
        // Get the old status before updating
        $oldStatus = $this->getCurrentMatterStatus($event->matter);
        
        $event->update($request->except(['_token', '_method']));

        // Check if this update changed the status and trigger MatterStatusChanged
        $this->checkAndTriggerStatusChange($event, $oldStatus);

        // Handle Inertia requests
        if ($request->inertia()) {
            return redirect()->back();
        }

        return $event;
    }

    public function destroy(Event $event)
    {
        $event->delete();

        // Handle Inertia requests
        if (request()->inertia()) {
            return redirect()->back();
        }

        return $event;
    }

    /**
     * Check if the event is a status-changing event and trigger MatterStatusChanged if needed
     */
    private function checkAndTriggerStatusChange(Event $event, ?string $oldStatus = null): void
    {
        // Get the event info to check if it's a status event
        $eventName = EventName::where('code', $event->code)->first();
        
        if ($eventName && $eventName->status_event) {
            $newStatus = $eventName->getTranslation('name', app()->getLocale());
            
            // If we don't have an old status, get the current one (excluding this new event)
            if (!$oldStatus) {
                $oldStatus = $this->getCurrentMatterStatus($event->matter, $event->id);
            }
            
            // Only trigger if status actually changed
            if ($oldStatus !== $newStatus) {
                MatterStatusChanged::dispatch($event->matter, $oldStatus ?? 'Unknown', $newStatus);
            }
        }
    }

    /**
     * Get the current status of a matter (most recent status event)
     */
    private function getCurrentMatterStatus($matter, ?int $excludeEventId = null): ?string
    {
        $query = $matter->events()
            ->join('event_name', 'event.code', '=', 'event_name.code')
            ->where('event_name.status_event', 1)
            ->orderByDesc('event_date');
            
        if ($excludeEventId) {
            $query->where('event.id', '!=', $excludeEventId);
        }
        
        $latestStatusEvent = $query->first();
        
        if ($latestStatusEvent) {
            $eventName = EventName::where('code', $latestStatusEvent->code)->first();
            return $eventName?->getTranslation('name', app()->getLocale());
        }
        
        return null;
    }
}
