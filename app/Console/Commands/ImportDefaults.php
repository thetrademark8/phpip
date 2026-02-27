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
            'delimiter' => ',',
            'translatableColumns' => ['type'],
            'extraExclude' => [],
        ],
        'event_name' => [
            'table' => 'event_name',
            'delimiter' => ',',
            'translatableColumns' => ['name'],
            'extraExclude' => [],
        ],
        'matter_category' => [
            'table' => 'matter_category',
            'delimiter' => ',',
            'translatableColumns' => ['category'],
            'extraExclude' => [],
        ],
        'matter_type' => [
            'table' => 'matter_type',
            'delimiter' => ',',
            'translatableColumns' => ['type'],
            'extraExclude' => [],
        ],
        'task_rules' => [
            'table' => 'task_rules',
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
                $result = $this->replaceTable($importService, $filePath, $config);

                $this->table(
                    ['Metric', 'Count'],
                    [
                        ['Deleted', $result['deleted']],
                        ['Inserted', $result['inserted']],
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

    private function replaceTable(CsvImportService $importService, string $filePath, array $config): array
    {
        $excludeColumns = array_merge(self::EXCLUDE_COLUMNS, $config['extraExclude']);

        return $importService->replaceFromCsv(
            filePath: $filePath,
            table: $config['table'],
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
        $this->info("  Existing DB records: {$existingCount} (will be deleted)");
    }
}
