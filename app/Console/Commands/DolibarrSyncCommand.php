<?php

namespace App\Console\Commands;

use App\Contracts\Renewal\DolibarrClientInterface;
use App\Services\Dolibarr\DolibarrActorSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class DolibarrSyncCommand extends Command
{
    protected $signature = 'dolibarr:sync
        {--dry-run : Preview changes without writing (default)}
        {--apply : Write changes to phpIP}
        {--link=* : Explicit association Dolibarr-ID:actor-ID; process only these associations}';

    protected $description = 'Import Dolibarr customers into phpIP; defaults to a read-only preview';

    public function handle(DolibarrClientInterface $client, DolibarrActorSyncService $sync): int
    {
        if (!config('dolibarr.sync.enabled')) {
            $this->error('Enable DOLIBARR_SYNC_ENABLED before synchronizing customers.');

            return self::FAILURE;
        }

        if ($this->option('apply') && $this->option('dry-run')) {
            $this->error('Choose either --apply or --dry-run.');

            return self::FAILURE;
        }

        $links = [];
        $linkedCustomers = [];
        $linkedActors = [];
        foreach ($this->option('link') as $value) {
            if (!preg_match('/^([1-9][0-9]*):([1-9][0-9]*)$/D', $value, $matches)
                || !filter_var($matches[1], FILTER_VALIDATE_INT)
                || !filter_var($matches[2], FILTER_VALIDATE_INT)) {
                $this->error('Each --link must contain two positive integer IDs: Dolibarr-ID:actor-ID.');

                return self::FAILURE;
            }
            if (isset($linkedCustomers[$matches[1]]) || isset($linkedActors[$matches[2]])) {
                $this->error('Each Dolibarr customer and actor may occur only once in --link options.');

                return self::FAILURE;
            }
            $linkedCustomers[$matches[1]] = true;
            $linkedActors[$matches[2]] = true;
            $links[] = [(int) $matches[1], (int) $matches[2]];
        }

        $dryRun = !$this->option('apply');
        $lock = Cache::lock('dolibarr-customer-sync', 3600);
        if (!$lock->get()) {
            $this->error('Another Dolibarr customer synchronization is running.');

            return self::FAILURE;
        }

        $counts = array_fill_keys(['created', 'updated', 'unchanged', 'conflict', 'invalid'], 0);
        $failed = false;
        // Stop well before the lock expires, including time spent in API retries.
        $deadline = microtime(true) + 1800;
        $this->info($dryRun ? 'PREVIEW: no changes will be saved.' : 'Importing Dolibarr customers into phpIP.');

        try {
            if ($links !== []) {
                foreach ($links as [$remoteId, $actorId]) {
                    $this->checkDeadline($deadline);
                    $this->report($sync->link($remoteId, $actorId, $dryRun), $counts);
                }
            } else {
                $seen = [];
                $previewIdentities = [];
                $limit = max(1, min(100, (int) config('dolibarr.sync.page_size', 100)));
                $maxPages = max(1, (int) config('dolibarr.sync.max_pages', 10000));
                for ($page = 0; $page < $maxPages; $page++) {
                    $this->checkDeadline($deadline);
                    $customers = $client->listCustomerThirdParties($page, $limit);
                    if ($customers === []) {
                        break;
                    }

                    foreach ($customers as $customer) {
                        $this->checkDeadline($deadline);
                        $id = filter_var($customer['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                        if ($id !== false && isset($seen[$id])) {
                            throw new \RuntimeException('Repeated customer in paginated response.');
                        }
                        if ($id !== false) {
                            $seen[$id] = true;
                        }

                        $result = $sync->sync($customer, $dryRun);
                        // Mirror collisions between would-be inserts without writing during preview.
                        if ($dryRun && $result['status'] === 'created') {
                            $identities = $this->identities($customer);
                            if (array_intersect_key($previewIdentities, $identities) !== []) {
                                $result['status'] = 'conflict';
                                $result['reason'] = 'matching_customer_in_same_import';
                            } else {
                                $previewIdentities += $identities;
                            }
                        }
                        $this->report($result, $counts);
                    }

                    if ($page === $maxPages - 1) {
                        throw new \RuntimeException('Maximum number of pages reached.');
                    }
                }
            }
        } catch (Throwable $exception) {
            $failed = true;
            // Do not include transport errors, remote payloads or credentials in output/logs.
            $this->error('Synchronization interrupted. Check API access and application logs, then retry; completed records are retained.');
            Log::warning('Dolibarr customer synchronization interrupted', ['exception' => $exception::class]);
        } finally {
            $lock->release();
        }

        $summary = implode(', ', array_map(fn ($status, $count) => "$status=$count", array_keys($counts), $counts));
        $this->line($summary);
        Log::info('Dolibarr customer synchronization finished', [
            'dry_run' => $dryRun,
            'counts' => $counts,
            'interrupted' => $failed,
        ]);

        return $failed || $counts['conflict'] || $counts['invalid'] ? self::FAILURE : self::SUCCESS;
    }

    private function report(array $result, array &$counts): void
    {
        $counts[$result['status']]++;
        $remote = $result['remote_id'] ?? '?';
        $actor = $result['actor_id'] ?? '-';
        $candidates = implode(',', $result['candidate_ids'] ?? []);
        $reason = $result['reason'] ?? '';
        $this->line("Dolibarr #$remote -> actor #$actor: {$result['status']} $reason" . ($candidates !== '' ? " candidates=$candidates" : ''));
        if (in_array($result['status'], ['conflict', 'invalid'], true)) {
            Log::warning('Dolibarr customer requires review', [
                'dry_run' => !$this->option('apply'),
                'remote_id' => $result['remote_id'] ?? null,
                'actor_id' => $result['actor_id'] ?? null,
                'candidate_ids' => $result['candidate_ids'] ?? [],
                'reason' => $reason,
            ]);
        }
    }

    private function checkDeadline(float $deadline): void
    {
        if (microtime(true) >= $deadline) {
            throw new \RuntimeException('Synchronization time limit reached.');
        }
    }

    private function identities(array $customer): array
    {
        $identities = [];
        foreach (['name', 'email', 'tva_intra', 'idprof1'] as $field) {
            $value = $customer[$field] ?? ($field === 'name' ? ($customer['nom'] ?? '') : '');
            if ((is_string($value) || is_int($value)) && trim((string) $value) !== '') {
                $normalised = DolibarrActorSyncService::normalizeIdentity((string) $value);
                $identities[$field . ':' . $normalised] = true;
            }
        }

        return $identities;
    }
}
