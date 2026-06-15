<?php

namespace App\Console\Commands;

use App\Models\Actor;
use App\Models\Task;
use App\Notifications\SystemTasksSummaryNotification;
use App\Notifications\UrgentTasksNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;

class SendTasksRecapTestEmail extends Command
{
    protected $signature = 'tasks:send-recap-test
        {email : Destination email address for the test recap}
        {--type=summary : Notification template to test: "summary" (system-wide) or "urgent" (per-agent)}
        {--language= : Force language (en, fr, de). Defaults to app locale}
        {--limit=20 : Maximum number of tasks to include}
        {--agent= : Optional agent login. For --type=urgent, restricts to this agent\'s urgent tasks}';

    protected $description = 'Send a tasks recap email to a single address for testing (does not run the full notification flow).';

    public function handle(): int
    {
        $email = $this->argument('email');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("Invalid email address: {$email}");

            return self::FAILURE;
        }

        $type = strtolower((string) $this->option('type'));
        if (!in_array($type, ['summary', 'urgent'], true)) {
            $this->error("Invalid --type '{$type}'. Use 'summary' or 'urgent'.");

            return self::FAILURE;
        }

        $language = $this->option('language') ?: Config::get('app.locale', 'en');
        $supported = ['en', 'fr', 'de'];
        if (!in_array($language, $supported, true)) {
            $this->warn("Unsupported language '{$language}', falling back to 'en'.");
            $language = 'en';
        }

        $limit = max(1, (int) $this->option('limit'));

        return match ($type) {
            'summary' => $this->sendSummary($email, $language, $limit),
            'urgent' => $this->sendUrgent($email, $language, $limit, $this->option('agent')),
        };
    }

    private function sendSummary(string $email, string $language, int $limit): int
    {
        $tasks = $this->baseTaskQuery()
            ->orderBy('due_date')
            ->limit($limit)
            ->get();

        $this->info('Preparing system summary recap email…');
        $this->line('  Template  : tasks-summary (SystemTasksSummaryNotification)');
        $this->line("  Recipient : {$email}");
        $this->line("  Language  : {$language}");
        $this->line("  Tasks     : {$tasks->count()}");

        try {
            $notification = new SystemTasksSummaryNotification($tasks, $language);

            // Bypass the queue so the test command always sends immediately,
            // even if QUEUE_CONNECTION is configured for async processing.
            Notification::route('mail', $email)->notifyNow($notification);
        } catch (\Throwable $e) {
            $this->error('Failed to send recap email: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info("✅ Recap email sent to {$email}");

        return self::SUCCESS;
    }

    private function sendUrgent(string $email, string $language, int $limit, ?string $agentLogin): int
    {
        $query = $this->baseTaskQuery();

        if ($agentLogin) {
            $query->whereHas('matter', fn ($q) => $q->where('responsible', $agentLogin));
        }

        $tasks = $query->orderBy('due_date')->limit($limit)->get();

        // Pick an agent: explicit --agent, else the responsible of the first task, else any actor.
        $agent = $this->resolveAgent($agentLogin, $tasks);

        if (!$agent) {
            $this->error('No agent could be resolved. Provide --agent=<login> or seed at least one actor.');

            return self::FAILURE;
        }

        $now = now();
        $overdueTasks = $tasks->filter(fn ($task) => $task->due_date < $now)->values()->all();
        $dueSoonTasks = $tasks->filter(fn ($task) => $task->due_date >= $now)->values()->all();

        $this->info('Preparing per-agent urgent tasks email…');
        $this->line('  Template  : urgent-tasks (UrgentTasksNotification)');
        $this->line("  Recipient : {$email}");
        $this->line("  Agent     : {$agent->login} ({$agent->name})");
        $this->line("  Language  : {$language}");
        $this->line('  Overdue   : ' . count($overdueTasks));
        $this->line('  Due soon  : ' . count($dueSoonTasks));

        try {
            $notification = new UrgentTasksNotification($agent, $overdueTasks, $dueSoonTasks, $language);

            Notification::route('mail', $email)->notifyNow($notification);
        } catch (\Throwable $e) {
            $this->error('Failed to send urgent tasks email: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info("✅ Urgent tasks email sent to {$email}");

        return self::SUCCESS;
    }

    private function baseTaskQuery()
    {
        return Task::query()
            ->whereHas('matter', fn ($query) => $query->where('dead', 0))
            ->where('code', '!=', 'REN')
            ->where('done', 0)
            ->where(function ($query) {
                $query->where('due_date', '<', now())
                    ->orWhere('due_date', '<=', now()->addDays(7));
            })
            ->with('matter', 'info', 'matter.client', 'matter.responsibleActor', 'matter.countryInfo', 'matter.titles');
    }

    private function resolveAgent(?string $agentLogin, $tasks): ?Actor
    {
        if ($agentLogin) {
            return Actor::where('login', $agentLogin)->first();
        }

        $firstTaskAgent = optional($tasks->first()?->matter)->responsibleActor;
        if ($firstTaskAgent) {
            return $firstTaskAgent;
        }

        return Actor::whereNotNull('email')->first();
    }
}
