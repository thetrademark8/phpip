<?php

namespace App\Services;

class MatterExportService
{
    /**
     * Export the matters to a CSV file.
     *
     * This method exports the provided matters array to a CSV file and returns
     * a streamed response for downloading the file. The exported columns exactly
     * match the interface columns in the same order.
     *
     * @param  array  $matters  The array of matters to be exported.
     * @return \Symfony\Component\HttpFoundation\StreamedResponse The streamed response for the CSV file download.
     */
    public function export(array $matters): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        // Define the column captions for the CSV file - matching the table interface columns
        // Same order as displayed in Matter/Index.vue
        $captions = [
            __('matter.columns.reference'),           // Ref
            __('matter.columns.category'),            // Cat
            __('matter.columns.country'),             // Country (full name)
            __('matter.columns.title'),               // Title
            __('matter.columns.classes'),             // Classes
            __('matter.columns.client'),              // Client
            __('matter.columns.owner'),               // Owner
            __('matter.columns.status'),              // Status
            __('matter.columns.filedDate'),           // Filed Date
            __('matter.columns.filedNumber'),         // Filed Number
            __('matter.columns.registrationDate'),    // Registration Date
            __('matter.columns.registrationNumber'),  // Registration Number
            __('matter.columns.renewalDue'),          // Renewal Due
        ];

        // Open a memory stream for the CSV file.
        $export_csv = fopen('php://memory', 'w');

        // Write BOM for UTF-8 encoding to ensure proper display of accented characters
        fprintf($export_csv, chr(0xEF).chr(0xBB).chr(0xBF));

        // Write the column captions to the CSV file.
        fputcsv($export_csv, $captions, ';');

        // Write each row of matters to the CSV file.
        // Extract only the columns that match the table interface, in the same order
        foreach ($matters as $matter) {
            // Determine registration date - handle both naming conventions
            // Matter::filter() uses 'registration_date', MatterRepository uses 'Granted'
            $registrationDate = $matter['Granted'] ?? $matter['registration_date'] ?? $matter['Registration_DP'] ?? '';

            // Determine registration number - handle both naming conventions
            // Matter::filter() uses 'registration_number', MatterRepository uses 'GrtNo'
            $registrationNumber = $matter['GrtNo'] ?? $matter['registration_number'] ?? '';

            // Owner - handle both naming conventions
            $owner = $matter['owner_name'] ?? $matter['Owner'] ?? '';

            // Country - prefer full name, fallback to ISO code
            $country = $matter['country_name'] ?? $matter['country'] ?? '';

            $row = [
                $matter['Ref'] ?? '',                    // Reference
                $matter['Cat'] ?? '',                    // Category
                $country,                                // Country (full name or code)
                $matter['Title'] ?? ($matter['Title2'] ?? ''), // Title
                $matter['classes'] ?? '',                // Classes
                $matter['Client'] ?? '',                 // Client
                $owner,                                  // Owner
                $matter['Status'] ?? '',                 // Status
                $matter['Filed'] ?? '',                  // Filed Date
                $matter['FilNo'] ?? '',                  // Filed Number
                $registrationDate,                       // Registration Date
                $registrationNumber,                     // Registration Number
                $matter['renewal_due'] ?? '',            // Renewal Due
            ];
            // Remove utf8_decode to preserve UTF-8 encoding (BOM handles encoding)
            fputcsv($export_csv, $row, ';');
        }

        // Rewind the memory stream to the beginning.
        rewind($export_csv);

        // Generate the filename for the CSV file.
        $filename = Now()->isoFormat('YMMDDHHmmss').'_matters.csv';

        // Return a streamed response for downloading the CSV file.
        return response()->stream(
            function () use ($export_csv) {
                fpassthru($export_csv);
            },
            200,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename='.$filename
            ]
        );
    }
}
