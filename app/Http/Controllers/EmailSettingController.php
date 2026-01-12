<?php

namespace App\Http\Controllers;

use App\Models\EmailSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailSettingController extends Controller
{
    /**
     * Show the email settings page.
     */
    public function index(): Response
    {
        $settings = EmailSetting::getAllGrouped();

        return Inertia::render('Settings/Email', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update email settings.
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable|string',
        ]);

        foreach ($request->settings as $setting) {
            EmailSetting::set(
                $setting['key'],
                $setting['value'] ?? '',
                $setting['type'] ?? 'text',
                $setting['group'] ?? 'general'
            );
        }

        return response()->json([
            'success' => true,
            'message' => __('email.settingsUpdated'),
        ]);
    }

    /**
     * Get a specific setting value (API endpoint).
     */
    public function getSetting(string $key): JsonResponse
    {
        $value = EmailSetting::get($key);

        return response()->json([
            'key' => $key,
            'value' => $value,
        ]);
    }
}
