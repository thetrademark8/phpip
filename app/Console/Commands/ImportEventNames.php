<?php

namespace App\Console\Commands;

use App\Services\CsvImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportEventNames extends Command
{
    protected $signature = 'import:event-names
        {--file= : Custom CSV file path (defaults to database/imports/event_name_trademark8.csv)}
        {--force : Delete all existing event names before importing}
        {--dry-run : Preview what would be imported without making changes}';

    protected $description = 'Import event names from a CSV file into the database';

    private const DEFAULT_FILE = 'imports/event_name_trademark8.csv';

    private const EXCLUDE_COLUMNS = [
        'creator',
        'updater',
        'created_at',
        'updated_at',
    ];

    private const TRANSLATABLE_COLUMNS = [
        'name',
    ];

    public function handle(CsvImportService $importService): int
    {
        $filePath = $this->option('file') ?? database_path(self::DEFAULT_FILE);

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return self::FAILURE;
        }

        $this->info("Reading from: {$filePath}");
        Log::info("ImportEventNames: Starting import from {$filePath}");

        // Dry run mode - show preview
        if ($this->option('dry-run')) {
            return $this->showPreview($importService, $filePath);
        }

        // Force mode - truncate table first
        if ($this->option('force')) {
            $count = DB::table('event_name')->count();

            if (!$this->confirm("This will delete all {$count} existing event names. Continue?")) {
                $this->info('Import cancelled.');
                return self::SUCCESS;
            }

            $this->warn('Truncating event_name table...');
            DB::table('event_name')->truncate();
            Log::info('ImportEventNames: Table truncated (--force option)');
        }

        // Perform import
        $this->info('Importing event names...');

        $result = $importService->importFromCsv(
            filePath: $filePath,
            table: 'event_name',
            uniqueKey: 'code',
            excludeColumns: self::EXCLUDE_COLUMNS,
            translatableColumns: self::TRANSLATABLE_COLUMNS
        );

        // Display results
        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Inserted', $result['inserted']],
                ['Skipped (duplicates)', $result['skipped']],
                ['Errors', $result['errors']],
            ]
        );

        if ($result['errors'] > 0) {
            $this->warn('Some rows could not be imported. Check the logs for details.');
        }

        Log::info('ImportEventNames: Import completed', $result);

        $this->info('Import completed successfully.');

        return self::SUCCESS;
    }

    private function showPreview(CsvImportService $importService, string $filePath): int
    {
        $this->info('[DRY RUN] Preview of import:');
        $this->newLine();

        $preview = $importService->preview($filePath, 5);

        $this->info("Total rows in CSV: {$preview['total']}");
        $this->newLine();

        // Show existing count
        $existingCount = DB::table('event_name')->count();
        $this->info("Existing records in database: {$existingCount}");

        // Get existing codes for comparison
        $existingCodes = DB::table('event_name')->pluck('code')->toArray();
        $newCodes = [];
        $duplicateCodes = [];

        foreach ($preview['rows'] as $row) {
            $code = $row['code'] ?? null;
            if ($code) {
                if (in_array($code, $existingCodes)) {
                    $duplicateCodes[] = $code;
                } else {
                    $newCodes[] = $code;
                }
            }
        }

        $this->newLine();
        $this->info('Sample of first 5 rows:');
        $this->table(
            ['Code', 'Name', 'Status'],
            array_map(function ($row) use ($existingCodes) {
                $code = $row['code'] ?? '-';
                $name = $row['name'] ?? '-';
                $status = in_array($code, $existingCodes) ? '<fg=yellow>SKIP (duplicate)</>' : '<fg=green>NEW</>';
                return [$code, substr($name, 0, 40), $status];
            }, $preview['rows'])
        );

        $this->newLine();
        $this->comment('Run without --dry-run to perform the actual import.');

        return self::SUCCESS;
    }
}
