<?php

namespace App\Http\Controllers;

use App\Models\Actor;
use App\Models\ActorPivot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ActorPivotController extends Controller
{
    public function store(Request $request, $matter = null)
    {
        try {
            // If matter_id comes from route parameter, use it
            if ($matter) {
                $request->merge(['matter_id' => $matter]);
            }

            Log::info('ActorPivot store request', ['data' => $request->all()]);

            $request->validate([
                'matter_id' => 'required|numeric',
                'actor_id' => 'required|numeric',
                'role' => 'required|string',
                'actor_ref' => 'nullable|string|max:255',
                'date' => 'nullable|date',
                'rate' => 'nullable|numeric|min:0|max:100',
                'display_order' => 'nullable|numeric|min:0',
            ]);

        // Fix display order indexes if wrong
        $roleGroup = ActorPivot::where('matter_id', $request->matter_id)->where('role', $request->role);
        $max = $roleGroup->max('display_order');
        $count = $roleGroup->count();
        if ($count < $max) {
            $i = 0;
            $actors = $roleGroup->orderBy('display_order')->get();
            foreach ($actors as $actor) {
                $i++;
                $actor->display_order = $i;
                $actor->save();
            }
            $max = $i;
        }

            $addedActor = Actor::find($request->actor_id);
            if (!$addedActor) {
                Log::error('Actor not found', ['actor_id' => $request->actor_id]);
                return response()->json(['error' => 'Actor not found'], 404);
            }

            $request->merge([
                'display_order' => $request->display_order ?: ($max + 1),
                'creator' => Auth::user()->login,
                'company_id' => $addedActor->company_id,
                'date' => $request->date ?: now(),
            ]);

            $actorPivot = ActorPivot::create($request->except(['_token', '_method']));

            Log::info('ActorPivot created successfully', ['id' => $actorPivot->id]);

            // Handle Inertia requests
            if ($request->inertia()) {
                return redirect()->back()->with('success', 'Actor added successfully');
            }

            return response()->json($actorPivot, 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed', ['errors' => $e->errors()]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error creating ActorPivot', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->inertia()) {
                return redirect()->back()->with('error', 'Failed to add actor');
            }

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    public function update(Request $request, ActorPivot $actorPivot)
    {
        $request->validate([
            'date' => 'date',
        ]);
        $request->merge(['updater' => Auth::user()->login]);
        $actorPivot->update($request->except(['_token', '_method']));

        // Handle Inertia requests
        if ($request->inertia()) {
            return redirect()->back();
        }

        return $actorPivot;
    }

    public function destroy(ActorPivot $actorPivot)
    {
        $matter_id = $actorPivot->matter_id;
        $role = $actorPivot->role;

        $actorPivot->delete();

        // Reorganize remaining items in role
        $actors = ActorPivot::where('matter_id', $matter_id)->where('role', $role)->orderBy('display_order')->get();
        $i = 0;
        foreach ($actors as $actor) {
            $i++;
            $actor->display_order = $i;
            $actor->save();
        }

        // Handle Inertia requests
        if (request()->inertia()) {
            return redirect()->back();
        }

        return $actorPivot;
    }

    public function usedIn(int $actor)
    {
        $actorpivot = new ActorPivot;
        $matter_dependencies = $actorpivot->with('matter', 'role')->where('actor_id', $actor)->get()->take(50);
        $actor_model = new Actor;
        $other_dependencies = $actor_model->select('id', DB::Raw("concat_ws(' ', name, first_name) as Actor"), DB::Raw("(
          case $actor
            when parent_id then 'Parent'
            when company_id then 'Company'
            when site_id then 'Site'
          end) as Dependency"))->where('parent_id', $actor)->orWhere('company_id', $actor)->orWhere('site_id', $actor)->get()->take(30);

        // Return JSON for AJAX requests
        if (request()->wantsJson()) {
            return response()->json([
                'matter_dependencies' => $matter_dependencies,
                'other_dependencies' => $other_dependencies
            ]);
        }

        return view('actor.usedin', compact(['matter_dependencies', 'other_dependencies']));
    }
}
