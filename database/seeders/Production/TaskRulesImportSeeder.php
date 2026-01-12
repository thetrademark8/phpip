<?php

namespace Database\Seeders\Production;

use App\Services\CsvImportService;
use Illuminate\Database\Seeder;

class TaskRulesImportSeeder extends Seeder
{
    private const CSV_FILE = 'imports/task_rules_trademark8.csv';

    /**
     * Columns that define a unique rule (used for duplicate detection).
     * The `uid` column is a generated virtual column in MySQL, so we use
     * the combination of columns that make a rule unique.
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
     * - id: auto-increment
     * - uid: generated virtual column (MD5 hash)
     * - audit fields: auto-managed by the application
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

    public function __construct(
        private readonly CsvImportService $importService
    ) {}

    public function run(): void
    {
        $filePath = database_path(self::CSV_FILE);

        if (!file_exists($filePath)) {
            $this->command?->warn('Task rules CSV file not found, skipping import...');
            return;
        }

        $this->command?->info('Importing task rules from CSV...');

        $result = $this->importService->importFromCsv(
            filePath: $filePath,
            table: 'task_rules',
            uniqueKey: self::UNIQUE_KEY,
            excludeColumns: self::EXCLUDE_COLUMNS,
            translatableColumns: self::TRANSLATABLE_COLUMNS
        );

        $this->command?->info(sprintf(
            'Task rules import complete: %d inserted, %d skipped, %d errors',
            $result['inserted'],
            $result['skipped'],
            $result['errors']
        ));
    }
}
