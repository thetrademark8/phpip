<?php

namespace App\Http\Middleware;

use App\Contracts\Services\DatePickerServiceInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateDateFields
{
    /**
     * Common date field names to automatically validate
     */
    private const DATE_FIELDS = [
        'date',
        'due_date',
        'start_date',
        'end_date',
        'expire_date',
        'filing_date',
        'priority_date',
        'publication_date',
        'registration_date',
        'renewal_date',
        'deadline_date',
        'created_date',
        'updated_date',
        'birth_date',
        'hired_date',
        'terminated_date',
    ];

    public function __construct(
        private DatePickerServiceInterface $datePickerService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only process POST, PUT, PATCH requests with form data
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            return $next($request);
        }

        // Get all request data
        $data = $request->all();
        $errors = [];
        $convertedData = [];

        // Check for date fields and validate them
        foreach ($data as $key => $value) {
            if ($this->isDateField($key) && ! empty($value)) {
                if (! $this->datePickerService->isValidDate($value)) {
                    $errors[$key] = "The {$key} field must be a valid date in format dd/mm/yyyy.";
                } else {
                    // Convert to ISO format for storage
                    $isoDate = $this->datePickerService->convertToIso($value);
                    if ($isoDate) {
                        $convertedData[$key] = $isoDate;
                    }
                }
            }
        }

        // If there are validation errors, return them
        if (! empty($errors)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $errors,
                ], 422);
            }

            return back()->withErrors($errors)->withInput();
        }

        // Merge converted dates back into request
        if (! empty($convertedData)) {
            $request->merge($convertedData);
        }

        return $next($request);
    }

    /**
     * Determine if a field name suggests it's a date field
     */
    private function isDateField(string $fieldName): bool
    {
        $fieldName = strtolower($fieldName);

        // Check exact matches
        if (in_array($fieldName, self::DATE_FIELDS)) {
            return true;
        }

        // Check for patterns like task_date, matter_due_date, etc.
        foreach (self::DATE_FIELDS as $dateField) {
            if (str_contains($fieldName, $dateField)) {
                return true;
            }
        }

        // Check for _at fields that might be dates (but exclude timestamps)
        if (str_ends_with($fieldName, '_at') && ! in_array($fieldName, ['created_at', 'updated_at', 'deleted_at'])) {
            return true;
        }

        return false;
    }

    /**
     * Get the list of date fields this middleware recognizes
     */
    public static function getRecognizedDateFields(): array
    {
        return self::DATE_FIELDS;
    }
}
