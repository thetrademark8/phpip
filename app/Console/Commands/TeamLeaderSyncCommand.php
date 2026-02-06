<?php

namespace App\Console\Commands;

use App\Models\TeamleaderReference;
use App\Services\TeamLeader\Handlers\CompanyHandler;
use App\Services\TeamLeader\Handlers\ContactHandler;
use App\Services\TeamLeader\TeamLeaderService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TeamLeaderSyncCommand extends Command
{
    protected $signature = 'teamleader:sync
                            {--companies-only : Only sync companies}
                            {--contacts-only : Only sync contacts}
                            {--force : Force sync even if reference exists}';

    protected $description = 'Synchronize all companies and contacts from TeamLeader';

    protected TeamLeaderService $service;
    protected int $syncedCompanies = 0;
    protected int $syncedContacts = 0;
    protected int $skipped = 0;

    public function __construct(TeamLeaderService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function handle(): int
    {
        if (!$this->service->isEnabled()) {
            $this->error('TeamLeader integration is not enabled.');
            return self::FAILURE;
        }

        if (!$this->service->isConnected()) {
            $this->error('Not connected to TeamLeader. Please authenticate first via the web interface.');
            return self::FAILURE;
        }

        $this->info('Starting TeamLeader synchronization...');
        $this->newLine();

        $syncCompanies = !$this->option('contacts-only');
        $syncContacts = !$this->option('companies-only');
        $force = $this->option('force');

        if ($syncCompanies) {
            $this->syncCompanies($force);
        }

        if ($syncContacts) {
            $this->syncOrphanedContacts($force);
        }

        $this->newLine();
        $this->info('Synchronization complete!');
        $this->table(
            ['Type', 'Count'],
            [
                ['Companies synced', $this->syncedCompanies],
                ['Contacts synced', $this->syncedContacts],
                ['Skipped (already exists)', $this->skipped],
            ]
        );

        return self::SUCCESS;
    }

    protected function syncCompanies(bool $force): void
    {
        $this->info('Syncing companies...');

        $page = 1;
        $hasMore = true;

        while ($hasMore) {
            $response = $this->makeRequest('companies.list', [
                'status' => 'active',
                'page' => [
                    'size' => 100,
                    'number' => $page,
                ],
            ]);

            if (!$response) {
                $this->error('Failed to fetch companies list');
                return;
            }

            $companies = $response['data'] ?? [];

            if (empty($companies)) {
                $hasMore = false;
                continue;
            }

            $this->info("Processing page {$page} ({$companies[0]['name']} ...)");

            foreach ($companies as $company) {
                $this->processCompany($company, $force);
            }

            $page++;
        }
    }

    protected function processCompany(array $company, bool $force): void
    {
        $reference = TeamleaderReference::where('teamleader_id', $company['id'])->first();

        if ($reference && !$force) {
            $this->skipped++;
            return;
        }

        $this->line("  Syncing company: {$company['name']}");
        CompanyHandler::upsert($company, $company['id']);
        $this->syncedCompanies++;

        $this->syncCompanyContacts($company, $force);
    }

    protected function syncCompanyContacts(array $company, bool $force): void
    {
        $page = 1;
        $hasMore = true;

        while ($hasMore) {
            $response = $this->makeRequest('contacts.list', [
                'status' => 'active',
                'page' => [
                    'size' => 100,
                    'number' => $page,
                ],
                'filter' => [
                    'company_id' => $company['id'],
                ],
            ]);

            if (!$response) {
                return;
            }

            $contacts = $response['data'] ?? [];

            if (empty($contacts)) {
                $hasMore = false;
                continue;
            }

            foreach ($contacts as $contact) {
                $this->processContact($contact, $force);
            }

            $page++;
        }
    }

    protected function syncOrphanedContacts(bool $force): void
    {
        $this->info('Syncing contacts without company...');

        $page = 1;
        $hasMore = true;

        while ($hasMore) {
            $response = $this->makeRequest('contacts.list', [
                'status' => 'active',
                'page' => [
                    'size' => 100,
                    'number' => $page,
                ],
            ]);

            if (!$response) {
                $this->error('Failed to fetch contacts list');
                return;
            }

            $contacts = $response['data'] ?? [];

            if (empty($contacts)) {
                $hasMore = false;
                continue;
            }

            $this->info("Processing contacts page {$page}");

            foreach ($contacts as $contact) {
                $this->processContact($contact, $force);
            }

            $page++;
        }
    }

    protected function processContact(array $contact, bool $force): void
    {
        $reference = TeamleaderReference::where('teamleader_id', $contact['id'])->first();

        if ($reference && !$force) {
            $this->skipped++;
            return;
        }

        $name = trim(($contact['first_name'] ?? '') . ' ' . ($contact['last_name'] ?? ''));
        $this->line("  Syncing contact: {$name}");

        ContactHandler::added(['id' => $contact['id']], $this->service);
        $this->syncedContacts++;
    }

    protected function makeRequest(string $endpoint, array $data): ?array
    {
        $maxRetries = 3;
        $retries = 0;

        while ($retries < $maxRetries) {
            $response = $this->service->prepareRequest()
                ->post(TeamLeaderService::BASE_URL . $endpoint, $data);

            $json = $response->json();

            if (isset($json['data'])) {
                return $json;
            }

            $limitRemaining = $response->header('X-RateLimit-Remaining');
            $limitReset = $response->header('X-RateLimit-Reset');

            if ($limitRemaining == 0 && $limitReset) {
                $resetTime = Carbon::parse($limitReset);
                $waitSeconds = max(0, now()->diffInSeconds($resetTime, false));

                if ($waitSeconds > 0 && $waitSeconds < 120) {
                    $this->warn("Rate limited. Waiting {$waitSeconds} seconds...");
                    sleep($waitSeconds);
                }

                $this->service->refreshAccessToken();
            }

            $retries++;
        }

        return null;
    }
}
