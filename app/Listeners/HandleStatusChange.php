<?php

namespace App\Listeners;

use App\Events\MatterStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class HandleStatusChange implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(MatterStatusChanged $event): void
    {
        // Get the new status from the event
        $newStatus = $event->newStatus;
        $matter = $event->matter;

        // Check if the status is one that should cancel renewals
        // Compare by event codes instead of translated names to avoid language issues
        $cancellingStatuses = ['REF', 'ABA', 'EXP', 'WIT']; // REF=Refused, ABA=Abandoned, EXP=Expired, WIT=Withdrawn
        $eventCode = $this->getEventCodeFromStatus($matter, $newStatus);
        
        if (in_array($eventCode, $cancellingStatuses)) {
            Log::info("Matter {$matter->id} status changed to {$newStatus} - processing renewal cancellation");
            
            // Cancel all pending renewal tasks for this matter
            $renewalTasks = $matter->tasks()
                ->where('task.code', 'REN')
                ->where('task.done', 0)
                ->get();

            foreach ($renewalTasks as $task) {
                $task->update([
                    'done' => 1,
                    'done_date' => now(),
                    'notes' => $task->notes ? $task->notes . "\nAuto-cancelled due to matter status: {$newStatus}" : "Auto-cancelled due to matter status: {$newStatus}"
                ]);
            }

            Log::info("Cancelled {$renewalTasks->count()} renewal tasks for matter {$matter->id}");
        }
    }

    /**
     * Get the event code from the status name by finding the most recent status event
     */
    private function getEventCodeFromStatus($matter, $statusName): ?string
    {
        // Get the most recent status event for this matter
        // Use direct query to avoid the default orderBy in the events() relation
        $latestStatusEvent = \App\Models\Event::where('matter_id', $matter->id)
            ->join('event_name', 'event.code', '=', 'event_name.code')
            ->where('event_name.status_event', 1)
            ->orderByDesc('event.event_date')
            ->first();

        return $latestStatusEvent?->code;
    }
}