<?php

namespace App\Http\Controllers;

use App\Models\Matter;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        // Count matters per categories
        $categories = Matter::getCategoryMatterCount();
        $taskscount = Task::getUsersOpenTaskCount();

        // Get filter parameters
        $whatTasks = $request->input('what_tasks', 0);
        $userDashboard = $request->input('user_dashboard', null);
        $clientId = $request->input('client_id', null);

        // Build tasks query (non-renewals)
        $tasksQuery = Task::with(['info', 'matter.titles', 'matter.client'])
            ->where('done', 0)
            ->whereHas('matter', function (Builder $q) {
                $q->where('dead', 0);
            })
            ->where('code', '!=', 'REN');
        
        // Apply filters for tasks
        if ($whatTasks == 1) {
            $tasksQuery->where('assigned_to', auth()->user()->login);
        } elseif ($whatTasks == 2 && $clientId) {
            $tasksQuery->whereHas('matter.client', function ($q) use ($clientId) {
                $q->where('actor_id', $clientId);
            });
        } elseif ($whatTasks > 2) {
            $tasksQuery->whereHas('matter.client', function ($q) use ($whatTasks) {
                $q->where('actor_id', $whatTasks);
            });
        }

        // Apply user dashboard filter
        if ($userDashboard) {
            $tasksQuery->where(function ($query) use ($userDashboard) {
                $query->where('assigned_to', $userDashboard)
                    ->orWhereHas('matter', function ($q) use ($userDashboard) {
                        $q->where('responsible', $userDashboard);
                    });
            });
        }

        // Apply client filter for CLI users
        if (auth()->user()->default_role == 'CLI' || empty(auth()->user()->default_role)) {
            $tasksQuery->whereHas('matter.client', function ($q) {
                $q->where('actor_id', auth()->user()->id);
            });
        }

        // Fetch tasks with relationships
        $tasks = $tasksQuery
            ->with(['matter', 'info:code,name'])
            ->orderBy('due_date')
            ->take(50)
            ->get();

        // Build renewals query
        $renewalsQuery = Task::with(['info', 'matter.titles', 'matter.client'])
            ->where('done', 0)
            ->whereHas('matter', function (Builder $q) {
                $q->where('dead', 0);
            })
            ->where('code', 'REN');
        
        // Apply same filters for renewals
        if ($whatTasks == 1) {
            $renewalsQuery->where('assigned_to', auth()->user()->login);
        } elseif ($whatTasks == 2 && $clientId) {
            $renewalsQuery->whereHas('matter.client', function ($q) use ($clientId) {
                $q->where('actor_id', $clientId);
            });
        } elseif ($whatTasks > 2) {
            $renewalsQuery->whereHas('matter.client', function ($q) use ($whatTasks) {
                $q->where('actor_id', $whatTasks);
            });
        }

        if ($userDashboard) {
            $renewalsQuery->where(function ($query) use ($userDashboard) {
                $query->where('assigned_to', $userDashboard)
                    ->orWhereHas('matter', function ($q) use ($userDashboard) {
                        $q->where('responsible', $userDashboard);
                    });
            });
        }

        if (auth()->user()->default_role == 'CLI' || empty(auth()->user()->default_role)) {
            $renewalsQuery->whereHas('matter.client', function ($q) {
                $q->where('actor_id', auth()->user()->id);
            });
        }

        // Fetch renewals with relationships
        $renewals = $renewalsQuery
            ->with(['matter', 'info:code,name'])
            ->orderBy('due_date')
            ->take(50)
            ->get();

        // Prepare filters for frontend
        $filters = [
            'what_tasks' => $whatTasks,
            'user_dashboard' => $userDashboard,
            'client_id' => $clientId,
        ];

        // Calculate metrics for dashboard
        $metrics = $this->calculateDashboardMetrics();

        return Inertia::render('Home', [
            'categories' => $categories,
            'tasksCount' => $taskscount,
            'tasks' => $tasks,
            'renewals' => $renewals,
            'filters' => $filters,
            'metrics' => $metrics,
            'permissions' => [
                'canWrite' => auth()->user()->can('readwrite'),
            ],
        ]);
    }

    /**
     * Calculate dashboard metrics.
     */
    private function calculateDashboardMetrics()
    {
        $now = Carbon::now();
        $lastMonth = $now->copy()->subMonth();
        
        // Total active matters
        $totalActiveMatters = Matter::where('dead', 0)->count();
        
        // Active matters change from last month
        $lastMonthActiveMatters = Matter::where('dead', 0)
            ->where('created_at', '<=', $lastMonth)
            ->count();
        
        $activeMattersChange = $lastMonthActiveMatters > 0 
            ? round((($totalActiveMatters - $lastMonthActiveMatters) / $lastMonthActiveMatters) * 100, 1)
            : 0;
        
        // Overdue tasks
        $overdueTasks = Task::where('done', 0)
            ->where('due_date', '<', $now)
            ->whereHas('matter', function (Builder $q) {
                $q->where('dead', 0);
            })
            ->count();
        
        // Upcoming renewals (next 30 days)
        $upcomingRenewals = Task::where('done', 0)
            ->where('code', 'REN')
            ->whereBetween('due_date', [$now, $now->copy()->addDays(30)])
            ->whereHas('matter', function (Builder $q) {
                $q->where('dead', 0);
            })
            ->count();
        
        // Task completion rate (last 30 days)
        $completedTasks = Task::where('done', 1)
            ->where('done_date', '>=', $now->copy()->subDays(30))
            ->count();
        
        $totalTasksLast30Days = Task::where(function ($query) use ($now) {
                $query->where('done', 1)
                    ->where('done_date', '>=', $now->copy()->subDays(30));
            })
            ->orWhere(function ($query) use ($now) {
                $query->where('done', 0)
                    ->where('created_at', '>=', $now->copy()->subDays(30));
            })
            ->count();
        
        $taskCompletionRate = $totalTasksLast30Days > 0 
            ? round(($completedTasks / $totalTasksLast30Days) * 100)
            : 0;
        
        return [
            'totalActiveMatters' => $totalActiveMatters,
            'activeMattersChange' => $activeMattersChange,
            'overdueTasks' => $overdueTasks,
            'upcomingRenewals' => $upcomingRenewals,
            'taskCompletionRate' => $taskCompletionRate,
        ];
    }

    /**
     * Clear selected tasks.
     */
    public function clearTasks(Request $request)
    {
        $validated = $request->validate([
            'done_date' => 'required|date',
            'task_ids' => 'required|array',
            'task_ids.*' => 'exists:task,id',
        ]);

        $tids = $request->task_ids;
        $done_date = Carbon::createFromFormat('Y-m-d', $request->done_date);
        $updated = 0;
        
        foreach ($tids as $id) {
            $task = Task::find($id);
            $task->done_date = $done_date;
            $returncode = $task->save();
            if ($returncode) {
                $updated++;
            }
        }

        return response()->json([
            'success' => true,
            'updated' => $updated,
            'not_updated' => (count($tids) - $updated),
            'message' => "$updated tasks cleared successfully"
        ]);
    }
}
