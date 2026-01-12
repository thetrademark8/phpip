<?php

namespace App\Console\Commands;

use App\Services\CsvImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportTaskRules extends Command
{
    protected $signature = 'import:task-rules
        {--file= : Custom CSV file path (defaults to database/imports/task_rules_trademark8.csv)}
        {--force : Delete all existing task rules before importing}
        {--dry-run : Preview what would be imported without making changes}';

    protected $description = 'Import task rules from a CSV file into the database';

    private const DEFAULT_FILE = 'imports/task_rules_trademark8.csv';

    /**
     * Columns that define a unique rule (used for duplicate detection).
     */
    private const UNIQUE_KEY = [
        'task',
        'trigger_event',
        'for_category',
        'for_country',
        'for_origin',
        'for_type',
    ];

    /**
     * Columns to exclude from import.
     */
    private const EXCLUDE_COLUMNS = [
        'id',
        'uid',
        'creator',
        'updater',
        'created_at',
        'updated_at',
    ];

    /**
     * Columns that should be converted to JSON format for translations.
     */
    private const TRANSLATABLE_COLUMNS = [
        'detail',
    ];

    public function handle(CsvImportService $importService): int
    {
        $filePath = $this->option('file') ?? database_path(self::DEFAULT_FILE);

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return self::FAILURE;
        }

        $this->info("Reading from: {$filePath}");
        Log::info("ImportTaskRules: Starting import from {$filePath}");

        // Dry run mode - show preview
        if ($this->option('dry-run')) {
            return $this->showPreview($importService, $filePath);
        }

        // Force mode - truncate table first
        if ($this->option('force')) {
            $count = DB::table('task_rules')->count();

            if (!$this->confirm("This will delete all {$count} existing task rules. Continue?")) {
                $this->info('Import cancelled.');
                return self::SUCCESS;
            }

            $this->warn('Truncating task_rules table...');
            DB::table('task_rules')->truncate();
            Log::info('ImportTaskRules: Table truncated (--force option)');
        }

        // Perform import
        $this->info('Importing task rules...');

        $result = $importService->importFromCsv(
            filePath: $filePath,
            table: 'task_rules',
            uniqueKey: self::UNIQUE_KEY,
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

        Log::info('ImportTaskRules: Import completed', $result);

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
        $existingCount = DB::table('task_rules')->count();
        $this->info("Existing records in database: {$existingCount}");

        $this->newLine();
        $this->info('Sample of first 5 rows:');
        $this->table(
            ['Task', 'Trigger', 'Category', 'Country', 'Detail'],
            array_map(function ($row) {
                return [
                    $row['task'] ?? '-',
                    $row['trigger_event'] ?? '-',
                    $row['for_category'] ?? '-',
                    $row['for_country'] ?? '-',
                    substr($row['detail'] ?? '-', 0, 30),
                ];
            }, $preview['rows'])
        );

        $this->newLine();
        $this->comment('Run without --dry-run to perform the actual import.');

        return self::SUCCESS;
    }
}
