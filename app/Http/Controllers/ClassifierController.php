<?php

namespace App\Http\Controllers;

use App\Models\Classifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClassifierController extends Controller
{
    public function index()
    {
        //
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'matter_id' => 'required',
            'type_code' => 'required',
            'value' => 'required_without_all:lnk_matter_id,image',
            'image' => 'image|nullable|max:1024|required_if:type_code,IMG',
        ]);
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $request->merge(['value' => $file->getMimeType()]);
            $request->merge(['img' => $file->openFile()->fread($file->getSize())]);
        }
        $request->merge(['creator' => Auth::user()->login]);

        $classifier = Classifier::create($request->except(['_token', '_method', 'image']));

        return redirect()->back();
    }

    public function show(Classifier $classifier)
    {
        //
    }

    public function update(Request $request, Classifier $classifier)
    {
        if ($classifier->type->main_display && ! $request->filled('value')) {
            $classifier->delete();
        } else {
            $request->merge(['updater' => Auth::user()->login]);
            $classifier->update($request->except(['_token', '_method']));
        }

        // Handle Inertia requests
        if ($request->inertia()) {
            return redirect()->back();
        }

        return $classifier;
    }

    public function destroy(Classifier $classifier)
    {
        $classifier->delete();

        // Handle Inertia requests
        if (request()->inertia()) {
            return redirect()->back();
        }

        return $classifier;
    }

    public function showImage(Classifier $classifier)
    {
        return response($classifier->img)
            ->header('Content-Type', $classifier->value);
    }
}
