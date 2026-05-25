<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\SystemTasksSummaryNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;

class SendTasksRecapTestEmail extends Command
{
    protected $signature = 'tasks:send-recap-test
        {email : Destination email address for the test recap}
        {--language= : Force language (en, fr, de). Defaults to app locale}
        {--limit=20 : Maximum number of tasks to include}';

    protected $description = 'Send a tasks recap email to a single address for testing (does not run the full notification flow).';

    public function handle(): int
    {
        $email = $this->argument('email');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("Invalid email address: {$email}");

            return self::FAILURE;
        }

        $language = $this->option('language') ?: Config::get('app.locale', 'en');
        $supported = ['en', 'fr', 'de'];
        if (! in_array($language, $supported, true)) {
            $this->warn("Unsupported language '{$language}', falling back to 'en'.");
            $language = 'en';
        }

        $limit = max(1, (int) $this->option('limit'));

        $tasks = Task::query()
            ->whereHas('matter', fn ($query) => $query->where('dead', 0))
            ->where('code', '!=', 'REN')
            ->where('done', 0)
            ->where(function ($query) {
                $query->where('due_date', '<', now())
                    ->orWhere('due_date', '<=', now()->addDays(7));
            })
            ->with('matter', 'info', 'matter.client', 'matter.responsibleActor')
            ->orderBy('due_date')
            ->limit($limit)
            ->get();

        $this->info("Preparing recap email…");
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
}
