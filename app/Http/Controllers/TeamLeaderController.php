<?php

namespace App\Http\Controllers;

use App\Services\TeamLeader\TeamLeaderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamLeaderController extends Controller
{
    public function __construct(
        protected TeamLeaderService $teamLeaderService
    ) {}

    public function index(): Response
    {
        return Inertia::render('Settings/TeamLeader/Index', [
            'status' => $this->teamLeaderService->getConnectionStatus(),
            'enabled' => $this->teamLeaderService->isEnabled(),
        ]);
    }

    public function authenticate(): RedirectResponse
    {
        if (!$this->teamLeaderService->isEnabled()) {
            return redirect()->route('settings.teamleader.index')
                ->with('error', 'TeamLeader integration is not enabled');
        }

        return redirect($this->teamLeaderService->getAuthenticationUrl());
    }

    public function callback(Request $request): RedirectResponse
    {
        $code = $request->query('code');

        if (!$code) {
            return redirect()->route('settings.teamleader.index')
                ->with('error', 'No authorization code received');
        }

        $success = $this->teamLeaderService->exchangeCodeForToken($code);

        if (!$success) {
            return redirect()->route('settings.teamleader.index')
                ->with('error', 'Failed to authenticate with TeamLeader');
        }

        $this->teamLeaderService->createWebhook();

        return redirect()->route('settings.teamleader.index')
            ->with('success', 'Successfully connected to TeamLeader');
    }

    public function disconnect(): RedirectResponse
    {
        $this->teamLeaderService->disconnect();

        return redirect()->route('settings.teamleader.index')
            ->with('success', 'Disconnected from TeamLeader');
    }

    public function testConnection(): RedirectResponse
    {
        $result = $this->teamLeaderService->testConnection();

        if ($result['success']) {
            return redirect()->route('settings.teamleader.index')
                ->with('success', $result['message']);
        }

        return redirect()->route('settings.teamleader.index')
            ->with('error', $result['message']);
    }
}
