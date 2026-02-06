<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MatterExportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     *
     * This method merges default values for 'sortkey' and 'sortdir' if they are missing.
     *
     * @return void
     */
    public function prepareForValidation()
    {
        $this->mergeIfMissing([
            'sortkey' => 'id',
            'sortdir' => 'desc',
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'Ref' => 'nullable|string',
            'Cat' => 'nullable|string',
            'country' => 'nullable|string',
            'Status' => 'nullable|string',
            'Status_date' => 'nullable|string',
            'Client' => 'nullable|string',
            'ClRef' => 'nullable|string',
            'Applicant' => 'nullable|string',
            'Owner' => 'nullable|string',
            'Agent' => 'nullable|string',
            'AgtRef' => 'nullable|string',
            'Title' => 'nullable|string',
            'Inventor1' => 'nullable|string',
            'FilNo' => 'nullable|string',
            'PubNo' => 'nullable|string',
            'GrtNo' => 'nullable|string',
            'responsible' => 'nullable|string',
            'Ctnr' => 'nullable|string',
            'classes' => 'nullable|string',
            'registration_number' => 'nullable|string',

            // Date range filters (support both single date and { from, to } object)
            'Filed' => 'nullable|array',
            'Filed.from' => 'nullable|date',
            'Filed.to' => 'nullable|date',
            'Published' => 'nullable|array',
            'Published.from' => 'nullable|date',
            'Published.to' => 'nullable|date',
            'Granted' => 'nullable|array',
            'Granted.from' => 'nullable|date',
            'Granted.to' => 'nullable|date',
            'registration_date' => 'nullable|array',
            'registration_date.from' => 'nullable|date',
            'registration_date.to' => 'nullable|date',

            // Boolean filters
            'include_dead' => 'nullable|boolean',
        ];
    }
}
