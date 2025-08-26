<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Matter;
use App\Models\User;
use App\Notifications\TaskReminderNotification;
use App\Notifications\TasksSummaryNotification;
use App\Notifications\UrgentTasksNotification;
use Illuminate\Http\Request;

class EmailPreviewController extends Controller
{
    /**
     * Show email preview for testing templates
     * Only available in development environment
     */
    public function show(Request $request, $template)
    {
        if (!app()->environment('local')) {
            abort(404);
        }

        try {
            $html = match($template) {
                'task-reminder' => $this->previewTaskReminder(),
                'tasks-summary' => $this->previewTasksSummary(),
                'urgent-tasks' => $this->previewUrgentTasks(),
                default => abort(404, 'Template not found')
            };

            return response($html)->header('Content-Type', 'text/html');
        } catch (\Exception $e) {
            return response('<h1>Error</h1><p>' . $e->getMessage() . '</p><pre>' . $e->getTraceAsString() . '</pre>');
        }
    }

    private function previewTaskReminder()
    {
        // Create sample data for testing
        $matter = Matter::with(['client', 'classifiers'])->first();
        
        if (!$matter) {
            // Create fake matter data if none exists
            $matter = new Matter([
                'id' => 1,
                'uid' => 'TEST-001',
                'title' => 'Test Patent Application',
                'alt_ref' => 'CLIENT-REF-123'
            ]);
            $matter->exists = true;
        }

        $task = new Task([
            'id' => 1,
            'code' => 'REN',
            'due_date' => now()->addDays(2),
            'detail' => 'Renewal payment due',
            'matter_id' => $matter->id
        ]);
        $task->exists = true;
        $task->setRelation('matter', $matter);
        
        // Add fake info relation
        $taskInfo = (object) ['name' => 'Patent Renewal Payment'];
        $task->setRelation('info', $taskInfo);

        $user = User::first() ?? new User(['name' => 'Test User', 'email' => 'test@example.com']);

        $notification = new TaskReminderNotification($task, 'en');
        
        return $notification->toMail($user)->render();
    }

    private function previewTasksSummary()
    {
        $tasks = collect([
            (object) [
                'id' => 1,
                'code' => 'REN',
                'due_date' => now()->addDays(1),
                'detail' => 'Renewal payment',
                'info' => (object) ['name' => 'Patent Renewal Payment'],
                'matter' => (object) [
                    'id' => 1,
                    'uid' => 'TEST-001',
                    'title' => 'Test Patent',
                    'category_code' => 'PAT',
                    'responsible' => 'MP',
                    'alt_ref' => 'CLIENT-REF-001',
                    'client' => (object) ['name' => 'Test Client']
                ]
            ],
            (object) [
                'id' => 2,
                'code' => 'FIL',
                'due_date' => now()->addDays(5),
                'detail' => 'Filing deadline',
                'info' => (object) ['name' => 'Filing Deadline'],
                'matter' => (object) [
                    'id' => 2,
                    'uid' => 'TEST-002',
                    'title' => 'Another Patent',
                    'category_code' => 'TM',
                    'responsible' => 'KB',
                    'alt_ref' => 'CLIENT-REF-002',
                    'client' => (object) ['name' => 'Another Client']
                ]
            ]
        ]);

        $user = User::first() ?? new User(['name' => 'Test User', 'email' => 'test@example.com']);

        $notification = new TasksSummaryNotification($tasks, 'fr');
        
        return $notification->toMail($user)->render();
    }

    private function previewUrgentTasks()
    {
        // Create fake agent (Actor)
        $agent = new \App\Models\Actor([
            'id' => 1,
            'name' => 'Test Agent',
            'email' => 'agent@example.com',
            'login' => 'MP'
        ]);
        $agent->exists = true;

        $overdueTasks = [
            (object) [
                'id' => 1,
                'code' => 'REN',
                'due_date' => now()->subDays(1), // Overdue
                'detail' => 'Urgent renewal',
                'info' => (object) ['name' => 'Urgent Patent Renewal'],
                'matter' => (object) [
                    'id' => 1,
                    'uid' => 'URGENT-001',
                    'title' => 'Critical Patent',
                    'category_code' => 'PAT',
                    'responsible' => 'MP',
                    'alt_ref' => 'URGENT-REF-001',
                    'client' => (object) ['name' => 'Important Client']
                ]
            ]
        ];

        $dueSoonTasks = [
            (object) [
                'id' => 2,
                'code' => 'FIL',
                'due_date' => now()->addDays(1), // Due soon
                'detail' => 'Important filing',
                'info' => (object) ['name' => 'Filing Deadline'],
                'matter' => (object) [
                    'id' => 2,
                    'uid' => 'URGENT-002',
                    'title' => 'Important Trademark',
                    'category_code' => 'TM',
                    'responsible' => 'MP',
                    'alt_ref' => 'URGENT-REF-002',
                    'client' => (object) ['name' => 'Important Client']
                ]
            ]
        ];

        $user = User::first() ?? new User(['name' => 'Test User', 'email' => 'test@example.com']);

        $notification = new UrgentTasksNotification($agent, $overdueTasks, $dueSoonTasks, 'fr');
        
        return $notification->toMail($user)->render();
    }

    /**
     * Show all available templates for preview
     */
    public function index()
    {
        if (!app()->environment('local')) {
            abort(404);
        }

        $templates = [
            'task-reminder' => 'Single Task Reminder',
            'tasks-summary' => 'Tasks Summary',
            'urgent-tasks' => 'Urgent Tasks Alert'
        ];

        $html = '<h1>Email Template Previews</h1>';
        $html .= '<p>Available templates for testing:</p>';
        $html .= '<ul>';
        
        foreach ($templates as $key => $name) {
            $html .= '<li><a href="' . route('email.preview', $key) . '" target="_blank">' . $name . '</a></li>';
        }
        
        $html .= '</ul>';
        $html .= '<p><small>Note: These previews are only available in development environment.</small></p>';

        return response($html);
    }
}