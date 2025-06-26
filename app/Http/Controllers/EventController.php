<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    public function store(Request $request)
    {
        $this->validate($request, [
            'code' => 'required',
            'eventName' => 'required',
            'matter_id' => 'required|numeric',
            'event_date' => 'required_without:alt_matter_id',
        ]);
        // Date conversion removed - ValidateDateFields middleware now provides ISO format dates
        $request->merge(['creator' => Auth::user()->login]);

        return Event::create($request->except(['_token', '_method', 'eventName']));
    }

    public function show(Event $event)
    {
        //
    }

    public function update(Request $request, Event $event)
    {
        $this->validate($request, [
            'alt_matter_id' => 'nullable|numeric',
            'event_date' => 'sometimes|required_without:alt_matter_id',
        ]);
        // Date conversion removed - ValidateDateFields middleware now provides ISO format dates
        $request->merge(['updater' => Auth::user()->login]);
        $event->update($request->except(['_token', '_method']));

        return $event;
    }

    public function destroy(Event $event)
    {
        $event->delete();

        return $event;
    }
}
