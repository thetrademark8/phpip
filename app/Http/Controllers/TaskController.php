<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $task = new Task;
        $isrenewals = $request->isrenewals;
        $tasks = $task->openTasks();

        // $what_tasks, by default 0, is changed to 1 to see the "assigned_to" tasks or to the id of the client to see client specific tasks
        if ($request->what_tasks == 1) {
            $tasks->where('assigned_to', Auth::user()->login);
        }

        if ($request->what_tasks > 1) {
            $tasks->whereHas('matter.client', function (Builder $q) use ($request) {
                $q->where('actor_id', $request->what_tasks);
            });
        }

        if ($isrenewals) {
            $tasks->where('code', 'REN');
        } else {
            $tasks->where('code', '!=', 'REN');
        }

        if (Auth::user()->default_role == 'CLI' || empty(Auth::user()->default_role)) {
            $tasks->whereHas('matter.client', function (Builder $q) {
                $q->where('actor_id', Auth::user()->id);
            });
        }

        if ($request->user_dashboard) {
            $tasks
                // Where needs encapsulation to avaid interference with others where conditions (caused by orWhere)
                ->where(function (Builder $query) use ($request) {
                    $query
                        ->where('assigned_to', $request->user_dashboard)
                        ->orWhereHas('matter', function (Builder $q) use ($request) {
                            $q->where('responsible', $request->user_dashboard);
                        });
                });
        }

        $query = $tasks->orderBy('due_date');

        // For AJAX/JSON requests, return paginated JSON data
        if ($request->wantsJson() || $request->ajax()) {
            $paginatedTasks = $query->with(['matter:id,uid', 'info:id,name,code'])
                ->simplePaginate(18)
                ->appends($request->input());
            
            return response()->json($paginatedTasks);
        }

        $tasks = $query->simplePaginate(18)
            ->appends($request->input());

        return view('task.index', compact('tasks', 'isrenewals'));
    }

    public function store(Request $request)
    {
        Gate::authorize('readwrite');
        $request->validate([
            'trigger_id' => 'required|numeric',
            'due_date' => 'required',
            'cost' => 'nullable|numeric',
            'fee' => 'nullable|numeric',
        ]);
        // Date conversion removed - ValidateDateFields middleware now provides ISO format dates
        $request->merge(['creator' => Auth::user()->login]);

        $task = Task::create($request->except(['_token', '_method']));

        // Return Inertia redirect for AJAX/JSON requests
        if ($request->ajax() || $request->wantsJson() || $request->inertia()) {
            return redirect()->back();
        }

        return $task;
    }

    public function show(Task $task)
    {
        return $task;
    }

    public function update(Request $request, Task $task)
    {
        Gate::authorize('readwrite');
        $this->validate($request, [
            'due_date' => 'sometimes|filled',
            'cost' => 'nullable|numeric',
            'fee' => 'nullable|numeric',
            'detail' => 'nullable|string',
        ]);
        $request->merge(['updater' => Auth::user()->login]);
        // Date conversion removed - ValidateDateFields middleware now provides ISO format dates

        // Handle detail field
        if ($request->has('detail')) {
            if (! $task->getTranslation('detail', 'en', false)) {
                // If setting a non-empty value and there was no previous English translation,
                // ensure it's set for both current locale and fallback
                $task->setTranslation('detail', 'en', $request->detail);
            }
        }
        // Remove task rule when due date is manually changed
        if ($request->filled('due_date')) {
            // Date conversion removed - ValidateDateFields middleware now provides ISO format dates
            $request->merge(['rule_used' => null]);
        }
        // Remove renewal from renewal management pipeline
        if (($request->filled('done_date') || $request->done) && $task->code == 'REN') {
            $request->merge(['step' => -1]);
        }
        $task->update($request->except(['_token', '_method']));

        // Return Inertia redirect for AJAX/JSON requests
        if ($request->ajax() || $request->wantsJson() || $request->inertia()) {
            return redirect()->back();
        }

        return $task;
    }

    public function destroy(Task $task)
    {
        Gate::authorize('readwrite');
        $task->delete();

        // Return Inertia redirect for AJAX/JSON requests
        if (request()->ajax() || request()->wantsJson() || request()->inertia()) {
            return redirect()->back();
        }

        return $task;
    }
}
