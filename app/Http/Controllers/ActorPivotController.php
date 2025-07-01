<?php

namespace App\Http\Controllers;

use App\Models\Actor;
use App\Models\ActorPivot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ActorPivotController extends Controller
{
    public function store(Request $request, $matter = null)
    {
        // If matter_id comes from route parameter, use it
        if ($matter) {
            $request->merge(['matter_id' => $matter]);
        }
        
        $request->validate([
            'matter_id' => 'required|numeric',
            'actor_id' => 'required|numeric',
            'role_code' => 'required',
            'date' => 'date',
        ]);

        // Fix display order indexes if wrong
        $roleGroup = ActorPivot::where('matter_id', $request->matter_id)->where('role', $request->role_code);
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

        $request->merge([
            'role' => $request->role_code,
            'display_order' => $max + 1,
            'creator' => Auth::user()->login,
            'company_id' => $addedActor->company_id,
            'date' => Now(),
        ]);

        $actorPivot = ActorPivot::create($request->except(['_token', '_method', 'role_code']));

        // Handle Inertia requests
        if ($request->inertia()) {
            return redirect()->back();
        }

        return $actorPivot;
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
