<?php

namespace App\Http\Controllers;

use App\Models\Classifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ClassifierController extends Controller
{
    public function index()
    {
        //
    }

    public function store(Request $request)
    {
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $request->merge(['value' => $file->getMimeType()]);
            $request->merge(['img' => $file->openFile()->fread($file->getSize())]);
        }

        $this->validate($request, [
            'matter_id' => 'required',
            'type_code' => 'required',
            'value' => [
                'required_without_all:lnk_matter_id,image',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value === null || $value === '') {
                        return;
                    }

                    $exists = DB::table('classifier')
                        ->where('matter_id', $request->input('matter_id'))
                        ->where('type_code', $request->input('type_code'))
                        ->whereRaw('LEFT(`value`, 30) = LEFT(?, 30)', [$value])
                        ->exists();

                    if ($exists) {
                        $fail(__('A classifier with this value already exists for this matter and type.'));
                    }
                },
            ],
            'lnk_matter_id' => [
                'nullable',
                Rule::unique('classifier')
                    ->where(fn ($query) => $query
                        ->where('matter_id', $request->input('matter_id'))
                        ->where('type_code', $request->input('type_code'))
                    ),
            ],
            'image' => 'image|nullable|max:1024|required_if:type_code,IMG',
        ], [
            'lnk_matter_id.unique' => __('A classifier linked to this matter already exists for this type.'),
        ]);

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
        if ($classifier->type->main_display && !$request->filled('value')) {
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
