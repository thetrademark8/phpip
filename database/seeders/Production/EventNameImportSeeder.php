<?php

namespace Database\Seeders\Production;

use App\Services\CsvImportService;
use Illuminate\Database\Seeder;

class EventNameImportSeeder extends Seeder
{
    private const CSV_FILE = 'imports/event_name_trademark8.csv';

    private const EXCLUDE_COLUMNS = [
        'creator',
        'updater',
        'created_at',
        'updated_at',
    ];

    private const TRANSLATABLE_COLUMNS = [
        'name',
    ];

    public function __construct(
        private readonly CsvImportService $importService
    ) {}

    public function run(): void
    {
        $filePath = database_path(self::CSV_FILE);

        if (!file_exists($filePath)) {
            $this->command?->warn('Event name CSV file not found, skipping import...');
            return;
        }

        $this->command?->info('Importing event names from CSV...');

        $result = $this->importService->importFromCsv(
            filePath: $filePath,
            table: 'event_name',
            uniqueKey: 'code',
            excludeColumns: self::EXCLUDE_COLUMNS,
            translatableColumns: self::TRANSLATABLE_COLUMNS
        );

        $this->command?->info(sprintf(
            'Event names import complete: %d inserted, %d skipped, %d errors',
            $result['inserted'],
            $result['skipped'],
            $result['errors']
        ));
    }
}
