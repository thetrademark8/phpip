<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Services\TaskEmailService;
use Illuminate\Console\Command;

class SendTasksDueEmail extends Command
{
    protected $signature = 'tasks:send-urgent-notifications';

    protected $description = 'Send email notifications for urgent tasks (red/orange status) due in the next 7 days';

    public function __construct(
        private TaskEmailService $taskEmailService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $tasks = Task::query()
            ->whereHas('matter', function ($query) {
                $query->where('dead', 0);
            })
            ->where('code', '!=', 'REN')
            ->where('done', 0)
            ->where(function ($query) {
                // Rouge: overdue tasks
                $query->where('due_date', '<', now())
                      // Orange: tasks due in next 7 days  
                      ->orWhere('due_date', '<=', now()->addDays(7));
            })
            ->with('matter', 'info', 'matter.client', 'matter.responsibleActor')
            ->orderBy('due_date')
            ->get();

        // Send individual notifications to agents via NotificationService
        $notificationService = app(\App\Contracts\Services\NotificationServiceInterface::class);
        $sentCount = $notificationService->sendUpcomingTaskReminders(7);
        
        $this->info("Sent urgent task notifications to {$sentCount} agents");
        
        // Send system summary email using clean Laravel service with intelligent language detection
        try {
            $this->taskEmailService->sendSystemTasksSummary($tasks);
            
            if ($this->taskEmailService->isSystemEmailConfigured()) {
                $recipients = $this->taskEmailService->getSystemEmailRecipients();
                $this->info("Sent professional summary email to " . $recipients['to']);
                
                if ($recipients['bcc'] && $recipients['bcc'] !== $recipients['to']) {
                    $this->info("Sent summary BCC to " . $recipients['bcc']);
                }
            } else {
                $this->info($tasks->count() === 0 
                    ? "No urgent tasks found" 
                    : "No summary email configured (tasks-email.email_to not set)"
                );
            }
            
        } catch (\Exception $e) {
            $this->error("Failed to send summary email: " . $e->getMessage());
        }
    }
}
