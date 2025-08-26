<?php

namespace App\Services;

use App\Contracts\Services\NotificationServiceInterface;
use App\Models\Task;
use App\Models\User;
use App\Models\Actor;
use App\Notifications\UrgentTasksNotification;
use App\Notifications\TaskReminderNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

class NotificationService implements NotificationServiceInterface
{
    /**
     * Send task reminder email
     */
    public function sendTaskReminder(Task $task, User $recipient): bool
    {
        try {
            $language = $recipient->language ?? 'en';
            
            $recipient->notify(new TaskReminderNotification($task, $language));
            
            Log::info('Task reminder sent successfully', [
                'task_id' => $task->id,
                'recipient_email' => $recipient->email,
                'language' => $language
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send task reminder', [
                'task_id' => $task->id,
                'recipient_email' => $recipient->email,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send bulk task reminders for upcoming tasks
     */
    public function sendUpcomingTaskReminders(int $daysAhead = 7): int
    {
        $tasks = $this->getUrgentTasks($daysAhead);
        
        Log::info('Starting urgent task notifications', [
            'total_urgent_tasks' => $tasks->count(),
            'days_ahead' => $daysAhead
        ]);
        
        if ($tasks->isEmpty()) {
            Log::info('No urgent tasks found, nothing to send');
            return 0;
        }
        
        $tasksByAgent = $this->groupTasksByAgent($tasks);
        $sent = 0;
        $errors = [];

        foreach ($tasksByAgent as $agentLogin => $agentTasks) {
            // We already validated the agent exists and has email in groupTasksByAgent
            $agent = Actor::where('login', $agentLogin)->first();

            if ($this->sendUrgentTasksToAgent($agent, $agentTasks)) {
                $sent++;
                Log::info('Successfully sent notification', [
                    'agent_login' => $agentLogin,
                    'agent_email' => $agent->email,
                    'task_count' => count($agentTasks)
                ]);
            } else {
                $errors[] = $agentLogin;
                Log::error('Failed to send notification', [
                    'agent_login' => $agentLogin,
                    'agent_email' => $agent->email
                ]);
            }
        }

        Log::info('Completed urgent task notifications', [
            'sent_count' => $sent,
            'error_count' => count($errors),
            'errors' => $errors
        ]);

        return $sent;
    }

    /**
     * Send renewal notification
     */
    public function sendRenewalNotification(Task $renewalTask, array $recipients): bool
    {
        // Delegate to existing RenewalEmailService for renewal-specific notifications
        return true; // Placeholder - integrate with RenewalEmailService if needed
    }

    /**
     * Send matter status change notification
     */
    public function sendStatusChangeNotification(
        string $matterId,
        string $oldStatus,
        string $newStatus,
        array $recipients
    ): bool {
        try {
            $matter = \App\Models\Matter::find($matterId);
            if (!$matter) {
                return false;
            }

            foreach ($recipients as $recipient) {
                Mail::html(
                    view('email.status-change', [
                        'matter' => $matter,
                        'oldStatus' => $oldStatus,
                        'newStatus' => $newStatus,
                        'phpip_url' => config('app.url') . '/matter/' . $matterId,
                    ])->render(),
                    function ($message) use ($matter, $newStatus, $recipient) {
                        $message
                            ->from(config('mail.from.address'))
                            ->to($recipient)
                            ->subject('[phpIP] - Status change: ' . $matter->uid . ' is now ' . $newStatus);
                    }
                );
            }
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send status change notification', [
                'matter_id' => $matterId,
                'recipients' => $recipients,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get notification recipients for a task
     */
    public function getTaskRecipients(Task $task): Collection
    {
        $recipients = collect();
        
        // Add matter responsible
        if ($task->matter->responsible) {
            $responsible = Actor::where('login', $task->matter->responsible)->first();
            if ($responsible && $responsible->email) {
                $recipients->push($responsible->email);
            }
        }

        // Add agents (AGT, AGT2 roles)
        $agents = $task->matter->actors()
            ->whereIn('role_code', ['AGT', 'AGT2'])
            ->whereNotNull('email')
            ->get();
        
        foreach ($agents as $agent) {
            $recipients->push($agent->actor->email ?? $agent->email);
        }

        // Add delegate if defined
        $delegate = $task->matter->actors()
            ->where('role_code', 'DEL')
            ->whereNotNull('email')
            ->first();
        
        if ($delegate && $delegate->actor->email) {
            $recipients->push($delegate->actor->email);
        }

        return $recipients->unique()->filter();
    }

    /**
     * Send custom notification
     */
    public function sendCustomNotification(
        string $template,
        array $data,
        array $recipients,
        array $attachments = []
    ): bool {
        try {
            // For custom notifications, we still use Mail::html as a fallback
            // since creating dynamic Notification classes would be complex
            foreach ($recipients as $recipient) {
                Mail::html(
                    view($template, $data)->render(),
                    function ($message) use ($recipient, $data, $attachments) {
                        $message
                            ->from(config('mail.from.address'))
                            ->to($recipient)
                            ->subject($data['subject'] ?? '[phpIP] - Notification');
                            
                        foreach ($attachments as $attachment) {
                            $message->attach($attachment);
                        }
                    }
                );
            }
            
            Log::info('Custom notification sent successfully', [
                'template' => $template,
                'recipient_count' => count($recipients)
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send custom notification', [
                'template' => $template,
                'recipients' => $recipients,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Queue notification for later sending
     */
    public function queueNotification(string $type, array $data, string $sendAt): string
    {
        // For now, return a simple queue ID
        // In a full implementation, this would integrate with Laravel Queue
        $queueId = uniqid('notification_' . $type . '_');
        
        Log::info('Notification queued', [
            'queue_id' => $queueId,
            'type' => $type,
            'send_at' => $sendAt
        ]);
        
        return $queueId;
    }

    /**
     * Check if notification should be sent based on rules
     */
    public function shouldSendNotification(string $type, array $context): bool
    {
        // Basic rules - can be extended with more complex logic
        switch ($type) {
            case 'urgent_tasks':
                return !empty($context['tasks']);
            case 'status_change':
                return isset($context['matter']) && isset($context['newStatus']);
            case 'task_reminder':
                return isset($context['task']) && !$context['task']->done;
            default:
                return true;
        }
    }

    /**
     * Get urgent tasks (rouge/orange status)
     */
    private function getUrgentTasks(int $daysAhead = 7): Collection
    {
        return Task::query()
            ->whereHas('matter', function ($query) {
                $query->where('dead', 0);
            })
            ->where('code', '!=', 'REN')
            ->where('done', 0)
            ->where(function ($query) use ($daysAhead) {
                // Rouge: overdue tasks
                $query->where('due_date', '<', now())
                      // Orange: tasks due in next X days
                      ->orWhere('due_date', '<=', now()->addDays($daysAhead));
            })
            ->with('matter', 'info', 'matter.responsibleActor')
            ->orderBy('due_date')
            ->get();
    }

    /**
     * Group tasks by agent (responsible or delegate)
     */
    private function groupTasksByAgent(Collection $tasks): array
    {
        $tasksByAgent = [];

        foreach ($tasks as $task) {
            $agentLogin = $task->matter->responsible;
            
            if (!$agentLogin) {
                Log::debug('Task has no responsible agent', ['task_id' => $task->id, 'matter_id' => $task->matter->id]);
                continue;
            }

            // Check if agent exists and has email
            $agent = Actor::where('login', $agentLogin)->first();
            if (!$agent) {
                Log::warning('Responsible agent not found', ['login' => $agentLogin, 'task_id' => $task->id]);
                continue;
            }
            
            if (!$agent->email) {
                Log::warning('Agent has no email address', ['login' => $agentLogin, 'agent_id' => $agent->id]);
                continue;
            }

            if (!isset($tasksByAgent[$agentLogin])) {
                $tasksByAgent[$agentLogin] = [];
            }
            
            $tasksByAgent[$agentLogin][] = $task;
        }

        Log::info('Tasks grouped by agent', [
            'agent_count' => count($tasksByAgent),
            'agents' => array_keys($tasksByAgent),
            'total_tasks' => $tasks->count()
        ]);

        return $tasksByAgent;
    }

    /**
     * Send urgent tasks notification to a specific agent
     */
    private function sendUrgentTasksToAgent(Actor $agent, array $tasks): bool
    {
        if (!$agent->email) {
            Log::warning('Agent has no email address', [
                'agent_id' => $agent->id,
                'agent_name' => $agent->name
            ]);
            return false;
        }

        try {
            // Separate tasks by urgency
            $overdueTasks = [];
            $dueSoonTasks = [];
            
            foreach ($tasks as $task) {
                if ($task->due_date < now()) {
                    $overdueTasks[] = $task;
                } else {
                    $dueSoonTasks[] = $task;
                }
            }

            // Get agent's preferred language
            $language = $agent->language ?? 'en';
            
            // Send notification using the new UrgentTasksNotification class
            $agent->notify(new UrgentTasksNotification(
                $agent,
                $overdueTasks,
                $dueSoonTasks,
                $language
            ));
            
            Log::info('Urgent tasks notification sent successfully', [
                'agent_id' => $agent->id,
                'agent_email' => $agent->email,
                'overdue_count' => count($overdueTasks),
                'due_soon_count' => count($dueSoonTasks),
                'language' => $language
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send urgent tasks to agent', [
                'agent_id' => $agent->id,
                'agent_email' => $agent->email,
                'task_count' => count($tasks),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}