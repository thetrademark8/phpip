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
        // Define the column captions for the CSV file - exactly matching the interface
        // Using the same column order as displayed in the user interface
        $captions = [
            __('matter.columns.reference'),      // Ref
            __('matter.columns.category'),       // Cat  
            __('matter.columns.status'),         // Status
            __('matter.columns.client'),         // Client
            __('matter.columns.clientReference'), // ClRef
            __('matter.columns.applicant'),      // Applicant
            __('matter.columns.agent'),          // Agent
            __('matter.columns.agentRef'),       // AgtRef
            __('matter.columns.title'),          // Title
            __('matter.columns.inventor'),       // Inventor1
            __('matter.columns.statusDate'),     // Status_date
            __('matter.columns.filedDate'),      // Filed
            __('matter.columns.filedNumber'),    // FilNo
            __('matter.columns.publishedDate'),  // Published
            __('matter.columns.publishedNumber'), // PubNo
            __('matter.columns.grantedDate'),    // Granted
            __('matter.columns.grantedNumber'),  // GrtNo
        ];

        // Open a memory stream for the CSV file.
        $export_csv = fopen('php://memory', 'w');

        // Write the column captions to the CSV file.
        fputcsv($export_csv, $captions, ';');

        // Write each row of matters to the CSV file.
        // Extract only the columns that match the interface, in the same order
        foreach ($matters as $matter) {
            $row = [
                $matter['Ref'] ?? '',              // Reference
                $matter['Cat'] ?? '',              // Category  
                $matter['Status'] ?? '',           // Status
                $matter['Client'] ?? '',           // Client
                $matter['ClRef'] ?? '',            // Client Reference
                $matter['Applicant'] ?? '',        // Applicant
                $matter['Agent'] ?? '',            // Agent
                $matter['AgtRef'] ?? '',           // Agent Reference
                $matter['Title'] ?? ($matter['Title2'] ?? ''), // Title (fallback to Title2 like interface)
                $matter['Inventor1'] ?? '',        // Inventor
                $matter['Status_date'] ?? '',      // Status Date
                $matter['Filed'] ?? '',            // Filed Date
                $matter['FilNo'] ?? '',            // Filed Number
                $matter['Published'] ?? '',        // Published Date
                $matter['PubNo'] ?? '',            // Published Number
                $matter['Granted'] ?? '',          // Granted Date
                $matter['GrtNo'] ?? '',            // Granted Number
            ];
            fputcsv($export_csv, array_map('utf8_decode', $row), ';');
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
            ['Content-Type' => 'application/csv', 'Content-Disposition' => 'attachment; filename='.$filename]
        );
    }
}
