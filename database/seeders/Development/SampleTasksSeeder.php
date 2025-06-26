<?php

namespace Database\Seeders\Development;

use App\Models\Event;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SampleTasksSeeder extends Seeder
{
    /**
     * Create sample tasks for development events.
     */
    public function run()
    {
        $this->command->info('Creating sample tasks...');

        // Create renewal tasks for granted patents
        $this->createRenewalTasks();

        // Create deadline tasks for pending matters
        $this->createDeadlineTasks();

        // Create some completed tasks for history
        $this->createCompletedTasks();

        $this->command->info('Sample tasks created successfully!');
    }

    /**
     * Create renewal tasks for granted patents.
     */
    private function createRenewalTasks()
    {
        Event::where('code', 'GRT')
            ->whereHas('matter', function ($q) {
                $q->where('category_code', 'PAT');
            })
            ->get()
            ->each(function ($grantEvent) {
                $grantDate = Carbon::parse($grantEvent->event_date);
                $currentYear = Carbon::now()->year;
                $grantYear = $grantDate->year;

                // Create renewal tasks for past and upcoming years
                for ($year = 3; $year <= min(20, $currentYear - $grantYear + 2); $year++) {
                    $dueDate = $grantDate->copy()->addYears($year)->endOfMonth();

                    $task = Task::factory()->renewal()->create([
                        'trigger_id' => $grantEvent->id,
                        'due_date' => $dueDate->format('Y-m-d'),
                        'detail' => json_encode(['RYear' => $year]),
                        'done' => $dueDate->isPast() && rand(0, 100) > 20, // 80% of past renewals are done
                        'done_date' => $dueDate->isPast() && rand(0, 100) > 20
                            ? $dueDate->copy()->subDays(rand(10, 30))->format('Y-m-d')
                            : null,
                    ]);
                }
            });
    }

    /**
     * Create deadline tasks for pending matters.
     */
    private function createDeadlineTasks()
    {
        // Response deadlines for examination reports
        Event::where('code', 'EXA')->get()->each(function ($examEvent) {
            $dueDate = Carbon::parse($examEvent->event_date)->addMonths(rand(2, 4));

            Task::factory()->response()->create([
                'trigger_id' => $examEvent->id,
                'due_date' => $dueDate->format('Y-m-d'),
                'done' => $dueDate->isPast() && rand(0, 100) > 30,
                'assigned_to' => rand(0, 100) > 50 ? 'jdoe' : null,
            ]);
        });

        // Priority deadlines
        Event::where('code', 'FIL')
            ->whereHas('matter', function ($q) {
                $q->whereNull('parent_id');
            })
            ->get()
            ->each(function ($filingEvent) {
                $dueDate = Carbon::parse($filingEvent->event_date)->addYear();

                if ($dueDate->isFuture() || $dueDate->isToday()) {
                    Task::factory()->priorityDeadline()->create([
                        'trigger_id' => $filingEvent->id,
                        'due_date' => $dueDate->format('Y-m-d'),
                        'done' => false,
                    ]);
                }
            });
    }

    /**
     * Create some completed tasks for history.
     */
    private function createCompletedTasks()
    {
        // Create some completed payment tasks
        Event::where('code', 'FIL')->limit(10)->get()->each(function ($event) {
            Task::factory()->create([
                'trigger_id' => $event->id,
                'code' => 'PAY',
                'due_date' => Carbon::parse($event->event_date)->addDays(30)->format('Y-m-d'),
                'done' => true,
                'done_date' => Carbon::parse($event->event_date)->addDays(rand(15, 25))->format('Y-m-d'),
                'detail' => json_encode(['description' => 'Filing fee payment']),
                'cost' => rand(500, 2000),
                'fee' => rand(200, 500),
            ]);
        });

        // Create some completed reminder tasks
        Event::where('code', 'PUB')->limit(5)->get()->each(function ($event) {
            Task::factory()->create([
                'trigger_id' => $event->id,
                'code' => 'REM',
                'due_date' => Carbon::parse($event->event_date)->addDays(7)->format('Y-m-d'),
                'done' => true,
                'done_date' => Carbon::parse($event->event_date)->addDays(7)->format('Y-m-d'),
                'detail' => json_encode(['description' => 'Notify client of publication']),
                'notes' => 'Client notified by email',
                'time_spent' => rand(15, 45),
            ]);
        });
    }
}
