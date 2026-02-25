<?php

namespace App\Console\Commands;

use App\Services\CsvImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportDefaults extends Command
{
    protected $signature = 'import:defaults
        {--table= : Import only a specific table (e.g. event_name, classifier_type)}
        {--dry-run : Preview without making changes}';

    protected $description = 'Upsert default data from CSV files in database/imports/defaults/';

    private const EXCLUDE_COLUMNS = [
        'creator',
        'updater',
        'created_at',
        'updated_at',
    ];

    /**
     * Table definitions: csv filename (without .csv) => config.
     */
    private const TABLES = [
        'classifier_type' => [
            'table' => 'classifier_type',
            'uniqueKey' => 'code',
            'delimiter' => ',',
            'translatableColumns' => ['type'],
            'extraExclude' => [],
        ],
        'event_name' => [
            'table' => 'event_name',
            'uniqueKey' => 'code',
            'delimiter' => ',',
            'translatableColumns' => ['name'],
            'extraExclude' => [],
        ],
        'matter_category' => [
            'table' => 'matter_category',
            'uniqueKey' => 'code',
            'delimiter' => ',',
            'translatableColumns' => ['category'],
            'extraExclude' => [],
        ],
        'matter_type' => [
            'table' => 'matter_type',
            'uniqueKey' => 'code',
            'delimiter' => ',',
            'translatableColumns' => ['type'],
            'extraExclude' => [],
        ],
        'task_rules' => [
            'table' => 'task_rules',
            'uniqueKey' => [
                'task', 'trigger_event', 'clear_task', 'delete_task',
                'for_category', 'for_country', 'for_origin', 'for_type',
                'days', 'months', 'years', 'recurring',
                'abort_on', 'condition_event', 'use_priority',
            ],
            'delimiter' => ';',
            'translatableColumns' => ['detail'],
            'extraExclude' => ['id', 'uid', 'detail_fr', 'detail_de'],
        ],
    ];

    public function handle(CsvImportService $importService): int
    {
        $targetTable = $this->option('table');
        $dryRun = $this->option('dry-run');

        $tables = self::TABLES;

        if ($targetTable) {
            if (!isset($tables[$targetTable])) {
                $this->error("Unknown table: {$targetTable}");
                $this->info('Available tables: ' . implode(', ', array_keys($tables)));

                return self::FAILURE;
            }
            $tables = [$targetTable => $tables[$targetTable]];
        }

        $this->info($dryRun ? '[DRY RUN] Previewing default imports...' : 'Importing defaults...');
        $this->newLine();

        $hasErrors = false;

        foreach ($tables as $name => $config) {
            $filePath = database_path("imports/defaults/{$name}.csv");

            if (!file_exists($filePath)) {
                $this->warn("File not found: {$filePath} — skipping {$name}");
                continue;
            }

            $this->info("Processing: {$name}");

            if ($dryRun) {
                $this->showPreview($importService, $filePath, $config);
            } else {
                $result = $this->upsertTable($importService, $filePath, $config);

                $this->table(
                    ['Metric', 'Count'],
                    [
                        ['Inserted', $result['inserted']],
                        ['Updated', $result['updated']],
                        ['Errors', $result['errors']],
                    ]
                );

                if ($result['errors'] > 0) {
                    $hasErrors = true;
                    $this->warn("Some rows in {$name} had errors. Check the logs for details.");
                }

                Log::info("ImportDefaults: {$name} completed", $result);
            }

            $this->newLine();
        }

        if ($hasErrors) {
            $this->warn('Import completed with some errors. Check the logs.');
        } else {
            $this->info($dryRun ? 'Dry run complete.' : 'All imports completed successfully.');
        }

        return $hasErrors ? self::FAILURE : self::SUCCESS;
    }

    private function upsertTable(CsvImportService $importService, string $filePath, array $config): array
    {
        $excludeColumns = array_merge(self::EXCLUDE_COLUMNS, $config['extraExclude']);

        return $importService->upsertFromCsv(
            filePath: $filePath,
            table: $config['table'],
            uniqueKey: $config['uniqueKey'],
            excludeColumns: $excludeColumns,
            translatableColumns: $config['translatableColumns'],
            delimiter: $config['delimiter'],
        );
    }

    private function showPreview(CsvImportService $importService, string $filePath, array $config): void
    {
        $preview = $importService->preview($filePath, 5, $config['delimiter']);

        $existingCount = DB::table($config['table'])->count();

        $this->info("  CSV rows: {$preview['total']}");
        $this->info("  Existing DB records: {$existingCount}");

        $existingKeys = $importService->getExistingKeys($config['table'], $config['uniqueKey']);
        $keyColumn = is_array($config['uniqueKey']) ? $config['uniqueKey'][0] : $config['uniqueKey'];

        $newCount = 0;
        $updateCount = 0;

        foreach ($preview['rows'] as $row) {
            $key = $row[$keyColumn] ?? null;
            if ($key && $existingKeys->contains($key)) {
                $updateCount++;
            } else {
                $newCount++;
            }
        }

        $this->info("  Sample: {$newCount} new, {$updateCount} existing (from first 5 rows)");
    }
}
