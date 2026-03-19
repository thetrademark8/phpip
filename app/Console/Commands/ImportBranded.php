<?php

namespace App\Console\Commands;

use App\Services\BrandedImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ImportBranded extends Command
{
    protected $signature = 'import:branded
        {folder : Name of the branded folder (e.g. ines-pi)}
        {--dry-run : Preview without making changes}';

    protected $description = 'Import branded actors and matters from CSV files in database/imports/branded/{folder}/';

    public function handle(): int
    {
        $folder = $this->argument('folder');
        $dryRun = $this->option('dry-run');
        $basePath = database_path("imports/branded/{$folder}");

        if (! is_dir($basePath)) {
            $this->error("Folder not found: {$basePath}");

            return self::FAILURE;
        }

        $actorsFile = "{$basePath}/actors.csv";
        $mattersFile = "{$basePath}/matters.csv";

        if (! file_exists($actorsFile)) {
            $this->error("Actors file not found: {$actorsFile}");

            return self::FAILURE;
        }

        if (! file_exists($mattersFile)) {
            $this->error("Matters file not found: {$mattersFile}");

            return self::FAILURE;
        }

        $service = new BrandedImportService();

        if ($dryRun) {
            $this->info("[DRY RUN] Previewing branded import from '{$folder}'...");
            $this->newLine();

            $preview = $service->preview($actorsFile, $mattersFile);

            $this->info("Actors CSV: {$preview['actors_count']} rows");
            $this->info("Matters CSV: {$preview['matters_count']} rows");
            $this->newLine();

            if (count($preview['actors_preview']) > 0) {
                $this->info('First 5 actors:');
                foreach ($preview['actors_preview'] as $actor) {
                    $this->line("  - {$actor['name']} ({$actor['display_name']})");
                }
                $this->newLine();
            }

            if (count($preview['matters_preview']) > 0) {
                $this->info('First 5 matters:');
                foreach ($preview['matters_preview'] as $matter) {
                    $this->line("  - {$matter['caseref']} ({$matter['category']}, {$matter['country']})");
                }
            }

            $this->newLine();
            $this->info('Dry run complete.');

            return self::SUCCESS;
        }

        $this->info("Importing branded data from '{$folder}'...");
        $this->newLine();

        try {
            $result = $service->import($actorsFile, $mattersFile);
        } catch (\Throwable $e) {
            $this->error("Import failed: {$e->getMessage()}");
            Log::error('ImportBranded failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Import completed.');

        $this->table(
            ['Metric', 'Count'],
            collect($result['stats'])->map(fn ($count, $metric) => [
                str_replace('_', ' ', ucfirst($metric)),
                $count,
            ])->values()->toArray()
        );

        if (count($result['warnings']) > 0) {
            $this->warn("There were " . count($result['warnings']) . " warning(s). Check the logs for details.");
        }

        return self::SUCCESS;
    }
}
