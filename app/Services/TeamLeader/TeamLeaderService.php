<?php

namespace App\Services\TeamLeader;

use App\Models\TeamleaderReference;
use App\Models\TeamleaderToken;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class TeamLeaderService
{
    public const BASE_URL = 'https://api.focus.teamleader.eu/';
    public const AUTH_URL = 'https://focus.teamleader.eu/oauth2/authorize';
    public const TOKEN_URL = 'https://focus.teamleader.eu/oauth2/access_token';

    protected ?TeamleaderToken $token = null;

    public function __construct()
    {
        try {
            if (Schema::hasTable('teamleader_tokens')) {
                $this->token = TeamleaderToken::first();
            }
        } catch (\Exception $e) {
            // Table doesn't exist yet (during migrations)
            $this->token = null;
        }
    }

    public function isEnabled(): bool
    {
        return config('services.teamleader.enabled', false);
    }

    public function isConnected(): bool
    {
        return $this->token && $this->token->refresh_token;
    }

    public function isTokenValid(): bool
    {
        return $this->token && $this->token->isValid();
    }

    public function getConnectionStatus(): array
    {
        return [
            'enabled' => $this->isEnabled(),
            'connected' => $this->isConnected(),
            'token_valid' => $this->isTokenValid(),
            'expires_at' => $this->token?->expires_at?->toIso8601String(),
            'webhook_id' => $this->token?->webhook_id,
            'updated_at' => $this->token?->updated_at?->toIso8601String(),
        ];
    }

    public function getAuthenticationUrl(): string
    {
        $clientId = config('services.teamleader.client_id');
        $redirectUri = route('settings.teamleader.callback');

        return self::AUTH_URL . "?client_id={$clientId}&redirect_uri={$redirectUri}&response_type=code";
    }

    public function exchangeCodeForToken(string $code): bool
    {
        $response = Http::asForm()->post(self::TOKEN_URL, [
            'client_id' => config('services.teamleader.client_id'),
            'client_secret' => config('services.teamleader.client_secret'),
            'redirect_uri' => route('settings.teamleader.callback'),
            'code' => $code,
            'grant_type' => 'authorization_code',
        ]);

        if (!$response->successful()) {
            Log::error('TeamLeader: Failed to exchange code for token', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        }

        $data = $response->json();

        $this->storeTokens(
            $data['access_token'],
            $data['refresh_token'],
            $data['expires_in']
        );

        return true;
    }

    public function refreshAccessToken(): bool
    {
        if (!$this->token || !$this->token->refresh_token) {
            Log::error('TeamLeader: No refresh token available');
            return false;
        }

        $response = Http::asForm()->post(self::TOKEN_URL, [
            'client_id' => config('services.teamleader.client_id'),
            'client_secret' => config('services.teamleader.client_secret'),
            'refresh_token' => $this->token->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if (!$response->successful()) {
            Log::error('TeamLeader: Failed to refresh token', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        }

        $data = $response->json();

        $this->storeTokens(
            $data['access_token'],
            $data['refresh_token'],
            $data['expires_in']
        );

        return true;
    }

    public function prepareRequest(): PendingRequest
    {
        if ($this->token && $this->token->isExpired()) {
            $this->refreshAccessToken();
            $this->token->refresh();
        }

        $accessToken = $this->token?->access_token;

        return Http::withHeaders([
            'Authorization' => "Bearer {$accessToken}",
        ]);
    }

    public function createWebhook(): ?string
    {
        $webhookUrl = route('teamleader.webhook');

        $response = $this->prepareRequest()->post(self::BASE_URL . 'webhooks.list');

        if (!$response->successful()) {
            Log::error('TeamLeader: Failed to list webhooks', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        }

        $webhooks = $response->json()['data'] ?? [];

        foreach ($webhooks as $webhook) {
            if ($webhook['url'] === $webhookUrl) {
                $this->token->update(['webhook_id' => $webhook['id'] ?? 'existing']);
                return $webhook['id'] ?? 'existing';
            }
        }

        $response = $this->prepareRequest()->post(self::BASE_URL . 'webhooks.register', [
            'url' => $webhookUrl,
            'types' => [
                'company.added',
                'company.updated',
                'company.deleted',
                'contact.added',
                'contact.updated',
                'contact.deleted',
                'contact.linkedToCompany',
                'contact.unlinkedFromCompany',
            ],
        ]);

        if (!$response->successful()) {
            Log::error('TeamLeader: Failed to register webhook', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        }

        $webhookId = $response->json()['data']['id'] ?? 'registered';
        $this->token->update(['webhook_id' => $webhookId]);

        return $webhookId;
    }

    public function deleteWebhook(): bool
    {
        if (!$this->token || !$this->token->webhook_id) {
            return true;
        }

        $webhookUrl = route('teamleader.webhook');

        $response = $this->prepareRequest()->post(self::BASE_URL . 'webhooks.unregister', [
            'url' => $webhookUrl,
            'types' => [
                'company.added',
                'company.updated',
                'company.deleted',
                'contact.added',
                'contact.updated',
                'contact.deleted',
                'contact.linkedToCompany',
                'contact.unlinkedFromCompany',
            ],
        ]);

        if (!$response->successful()) {
            Log::warning('TeamLeader: Failed to unregister webhook', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

        $this->token->update(['webhook_id' => null]);

        return true;
    }

    public function disconnect(): bool
    {
        $this->deleteWebhook();

        if ($this->token) {
            $this->token->delete();
            $this->token = null;
        }

        return true;
    }

    public function testConnection(): array
    {
        if (!$this->isConnected()) {
            return [
                'success' => false,
                'message' => 'Not connected to TeamLeader',
            ];
        }

        $response = $this->prepareRequest()->get(self::BASE_URL . 'users.me');

        if (!$response->successful()) {
            return [
                'success' => false,
                'message' => 'Failed to connect to TeamLeader API',
                'error' => $response->body(),
            ];
        }

        $user = $response->json()['data'] ?? [];

        return [
            'success' => true,
            'message' => 'Successfully connected to TeamLeader',
            'user' => [
                'id' => $user['id'] ?? null,
                'email' => $user['email'] ?? null,
                'first_name' => $user['first_name'] ?? null,
                'last_name' => $user['last_name'] ?? null,
            ],
        ];
    }

    public function getDiagnostics(): array
    {
        $diagnostics = [
            'token' => $this->diagnoseToken(),
            'webhook' => $this->diagnoseWebhook(),
            'api' => $this->diagnoseApi(),
            'sync_stats' => $this->getSyncStats(),
        ];

        return $diagnostics;
    }

    protected function diagnoseToken(): array
    {
        if (!$this->token) {
            return [
                'status' => 'missing',
                'message' => 'No token found in database',
            ];
        }

        $info = [
            'status' => $this->token->isValid() ? 'valid' : 'expired',
            'expires_at' => $this->token->expires_at?->toIso8601String(),
            'expires_in_minutes' => $this->token->expires_at ? (int) now()->diffInMinutes($this->token->expires_at, false) : null,
            'has_refresh_token' => (bool) $this->token->refresh_token,
            'last_updated' => $this->token->updated_at?->toIso8601String(),
        ];

        // Try to refresh if expired
        if ($this->token->isExpired()) {
            $refreshResult = $this->refreshAccessToken();
            $info['refresh_attempted'] = true;
            $info['refresh_success'] = $refreshResult;
            if ($refreshResult) {
                $this->token->refresh();
                $info['status'] = 'refreshed';
                $info['new_expires_at'] = $this->token->expires_at?->toIso8601String();
            } else {
                $info['message'] = 'Token expired and refresh failed - reconnection required';
            }
        }

        return $info;
    }

    protected function diagnoseWebhook(): array
    {
        if (!$this->isConnected()) {
            return [
                'status' => 'disconnected',
                'message' => 'Not connected - cannot check webhooks',
            ];
        }

        $storedWebhookId = $this->token?->webhook_id;

        if (!$storedWebhookId) {
            return [
                'status' => 'not_registered',
                'message' => 'No webhook ID stored locally',
            ];
        }

        // Check if webhook still exists on Teamleader side
        try {
            $response = $this->prepareRequest()->post(self::BASE_URL . 'webhooks.list');

            if (!$response->successful()) {
                return [
                    'status' => 'error',
                    'message' => 'Failed to list webhooks from Teamleader API',
                    'http_status' => $response->status(),
                    'response_body' => $response->body(),
                ];
            }

            $webhooks = $response->json()['data'] ?? [];
            $webhookUrl = route('teamleader.webhook');

            $found = null;
            foreach ($webhooks as $webhook) {
                if ($webhook['url'] === $webhookUrl) {
                    $found = $webhook;
                    break;
                }
            }

            if (!$found) {
                return [
                    'status' => 'missing_remote',
                    'message' => 'Webhook registered locally but NOT found on Teamleader side - this is likely why sync stopped',
                    'expected_url' => $webhookUrl,
                    'registered_webhooks' => collect($webhooks)->pluck('url')->toArray(),
                ];
            }

            return [
                'status' => 'active',
                'message' => 'Webhook is registered and found on Teamleader',
                'url' => $found['url'],
                'types' => $found['types'] ?? [],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Exception checking webhooks: ' . $e->getMessage(),
            ];
        }
    }

    protected function diagnoseApi(): array
    {
        if (!$this->isConnected()) {
            return [
                'status' => 'disconnected',
                'message' => 'Not connected',
            ];
        }

        try {
            // Test basic API access
            $response = $this->prepareRequest()->get(self::BASE_URL . 'users.me');

            if (!$response->successful()) {
                return [
                    'status' => 'error',
                    'message' => 'API call failed',
                    'http_status' => $response->status(),
                    'response_body' => mb_substr($response->body(), 0, 500),
                ];
            }

            $user = $response->json()['data'] ?? [];

            // Also check rate limit headers
            $rateLimitInfo = [
                'remaining' => $response->header('X-RateLimit-Remaining'),
                'limit' => $response->header('X-RateLimit-Limit'),
                'reset' => $response->header('X-RateLimit-Reset'),
            ];

            // Test contacts.list to specifically check contact access
            $contactsResponse = $this->prepareRequest()->post(self::BASE_URL . 'contacts.list', [
                'page' => ['size' => 1, 'number' => 1],
            ]);

            $contactsOk = $contactsResponse->successful();
            $contactsTotal = null;
            if ($contactsOk) {
                $contactsTotal = $contactsResponse->json()['meta']['matches'] ?? null;
            }

            return [
                'status' => 'ok',
                'message' => 'API is responding correctly',
                'user' => ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''),
                'rate_limit' => $rateLimitInfo,
                'contacts_accessible' => $contactsOk,
                'contacts_total_on_teamleader' => $contactsTotal,
                'contacts_error' => !$contactsOk ? mb_substr($contactsResponse->body(), 0, 300) : null,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    protected function getSyncStats(): array
    {
        $totalReferences = TeamleaderReference::count();
        $recentReferences = TeamleaderReference::where('created_at', '>=', now()->subDays(7))->count();
        $lastSync = TeamleaderReference::orderBy('updated_at', 'desc')->first();

        return [
            'total_synced_records' => $totalReferences,
            'synced_last_7_days' => $recentReferences,
            'last_sync_at' => $lastSync?->updated_at?->toIso8601String(),
            'last_sync_actor_id' => $lastSync?->actor_id,
            'last_sync_teamleader_id' => $lastSync?->teamleader_id,
        ];
    }

    public function reRegisterWebhook(): array
    {
        if (!$this->isConnected()) {
            return ['success' => false, 'message' => 'Not connected'];
        }

        // Delete existing webhook first
        $this->deleteWebhook();

        // Re-create
        $webhookId = $this->createWebhook();

        if ($webhookId) {
            return ['success' => true, 'message' => 'Webhook re-registered successfully', 'webhook_id' => $webhookId];
        }

        return ['success' => false, 'message' => 'Failed to re-register webhook'];
    }

    protected function storeTokens(string $accessToken, string $refreshToken, int $expiresIn): void
    {
        $data = [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_at' => now()->addSeconds($expiresIn),
        ];

        if ($this->token) {
            $this->token->update($data);
        } else {
            $this->token = TeamleaderToken::create($data);
        }
    }
}
