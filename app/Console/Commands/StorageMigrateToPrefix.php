<?php

namespace App\Console\Commands;

use App\Models\MatterAttachment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class StorageMigrateToPrefix extends Command
{
    protected $signature = 'storage:migrate-to-prefix {--dry-run : List actions without copying any object}';

    protected $description = 'Copy existing S3 files (matter attachments) under the configured per-instance prefix.';

    public function handle(): int
    {
        $prefix = config('filesystems.disks.s3.root');
        $bucket = config('filesystems.disks.s3.bucket');

        if (! $prefix) {
            $this->error('No S3 prefix configured (filesystems.disks.s3.root is empty). Set AWS_BUCKET_PREFIX or APP_URL before running this command.');

            return self::FAILURE;
        }

        if (! $bucket) {
            $this->error('No S3 bucket configured.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $this->info(sprintf('Bucket: %s | Prefix: %s | Dry-run: %s', $bucket, $prefix, $dryRun ? 'yes' : 'no'));

        $legacy = Storage::disk('s3_legacy');
        $prefixed = Storage::disk('s3');
        $client = $prefixed->getClient();
        $prefixClean = trim($prefix, '/');

        $stats = ['migrated' => 0, 'already_migrated' => 0, 'missing' => 0, 'errors' => 0];

        $attachments = MatterAttachment::where('disk', 's3')->get();
        $this->info(sprintf('Processing %d matter attachments...', $attachments->count()));

        foreach ($attachments as $attachment) {
            $oldKey = $attachment->path;
            $newKey = $prefixClean.'/'.ltrim($oldKey, '/');

            try {
                $existsOld = $legacy->exists($oldKey);
                $existsNew = $prefixed->exists($oldKey);

                if ($existsNew) {
                    $stats['already_migrated']++;
                    $this->line(sprintf('  [skip] already at %s', $newKey));

                    continue;
                }

                if (! $existsOld) {
                    $stats['missing']++;
                    $this->warn(sprintf('  [missing] %s (attachment id=%d)', $oldKey, $attachment->id));

                    continue;
                }

                $this->line(sprintf('  [copy] %s -> %s', $oldKey, $newKey));

                if (! $dryRun) {
                    $client->copyObject([
                        'Bucket' => $bucket,
                        'CopySource' => urlencode($bucket.'/'.$oldKey),
                        'Key' => $newKey,
                    ]);
                }

                $stats['migrated']++;
            } catch (Throwable $e) {
                $stats['errors']++;
                $this->error(sprintf('  [error] attachment id=%d path=%s : %s', $attachment->id, $oldKey, $e->getMessage()));
            }
        }

        $this->newLine();
        $this->info('Summary:');
        $this->table(
            ['Migrated', 'Already migrated', 'Missing source', 'Errors'],
            [[$stats['migrated'], $stats['already_migrated'], $stats['missing'], $stats['errors']]]
        );

        if ($dryRun) {
            $this->comment('Dry-run complete. Re-run without --dry-run to perform the copies.');
        } else {
            $this->comment('Migration complete. Old (non-prefixed) objects were left in place; remove them once all instances have migrated.');
        }

        return $stats['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
