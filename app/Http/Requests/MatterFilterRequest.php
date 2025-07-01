<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MatterFilterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Text filters
            'Ref' => 'nullable|string|max:50',
            'Cat' => 'nullable|string|max:10',
            'Status' => 'nullable|string|max:100',
            'Client' => 'nullable|string|max:100',
            'ClRef' => 'nullable|string|max:50',
            'Applicant' => 'nullable|string|max:100',
            'Agent' => 'nullable|string|max:100',
            'AgtRef' => 'nullable|string|max:50',
            'Title' => 'nullable|string|max:255',
            'Inventor1' => 'nullable|string|max:100',
            'FilNo' => 'nullable|string|max:50',
            'PubNo' => 'nullable|string|max:50',
            'GrtNo' => 'nullable|string|max:50',
            'responsible' => 'nullable|string|max:50',
            'country' => 'nullable|string|size:2',
            
            // Date filters
            'Status_date' => 'nullable|date',
            'Filed' => 'nullable|date',
            'Published' => 'nullable|date',
            'Granted' => 'nullable|date',
            
            // Boolean filters
            'Ctnr' => 'nullable|boolean',
            'include_dead' => 'nullable|boolean',
            
            // Display options
            'display_with' => 'nullable|string|max:10',
            'tab' => 'nullable|integer|in:0,1',
            
            // Sorting options
            'sortkey' => 'nullable|string|in:id,Ref,Cat,Status,Client,Filed,Published,Granted,caseref',
            'sortdir' => 'nullable|string|in:asc,desc',
            'sort' => 'nullable|string',
            'direction' => 'nullable|string|in:asc,desc',
            
            // Pagination
            'per_page' => 'nullable|integer|min:10|max:100',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'country.size' => 'Country must be a 2-letter ISO code.',
            'tab.in' => 'Tab must be either 0 (Actor view) or 1 (Status view).',
            'sortdir.in' => 'Sort direction must be either asc or desc.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert boolean string values to actual booleans
        $this->merge([
            'Ctnr' => $this->convertToBoolean($this->Ctnr),
            'include_dead' => $this->convertToBoolean($this->include_dead),
        ]);
    }

    /**
     * Convert various boolean representations to boolean
     */
    protected function convertToBoolean($value): ?bool
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        return in_array($value, ['1', 1, 'true', 'on'], true);
    }

    /**
     * Get the filters from the validated data
     */
    public function getFilters(): array
    {
        return $this->only([
            'Ref', 'Cat', 'Status', 'Client', 'ClRef',
            'Applicant', 'Agent', 'AgtRef', 'Title',
            'Inventor1', 'Status_date', 'Filed', 'FilNo',
            'Published', 'PubNo', 'Granted', 'GrtNo',
            'responsible', 'Ctnr', 'country'
        ]);
    }

    /**
     * Get the search options from the validated data
     */
    public function getOptions(): array
    {
        // Use new sort/direction parameters if available, fallback to sortkey/sortdir
        $sortField = $this->input('sort') ?: $this->input('sortkey', 'caseref');
        $sortDirection = $this->input('direction') ?: $this->input('sortdir', 'asc');
        
        return [
            'sortkey' => $sortField,
            'sortdir' => $sortDirection,
            'per_page' => $this->input('per_page', 25),
            'display_with' => $this->input('display_with'),
            'include_dead' => $this->boolean('include_dead'),
        ];
    }

    /**
     * Get all parameters for response (including tab)
     */
    public function getAllParameters(): array
    {
        return array_merge(
            $this->getFilters(),
            $this->getOptions(),
            ['tab' => $this->input('tab', 0)]
        );
    }
}