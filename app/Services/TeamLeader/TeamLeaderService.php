<?php

namespace App\Services\TeamLeader;

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
