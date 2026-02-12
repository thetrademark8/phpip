<?php

namespace App\Console\Commands;

use App\Services\TeamLeader\TeamLeaderService;
use Illuminate\Console\Command;

class TeamLeaderRefreshTokenCommand extends Command
{
    protected $signature = 'teamleader:refresh-token';

    protected $description = 'Refresh the TeamLeader access token if it is about to expire';

    public function handle(TeamLeaderService $service): int
    {
        if (!$service->isEnabled() || !$service->isConnected()) {
            return self::SUCCESS;
        }

        if (!$service->isTokenExpiringSoon()) {
            $this->info('Token is still valid, no refresh needed.');
            return self::SUCCESS;
        }

        $this->info('Token is expiring soon, refreshing...');

        if ($service->refreshAccessToken()) {
            $this->info('Token refreshed successfully.');
            return self::SUCCESS;
        }

        $this->error('Failed to refresh token. Manual reconnection required.');
        return self::FAILURE;
    }
}
