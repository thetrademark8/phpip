<?php

namespace App\Http\Controllers;

use App\Models\EmailSetting;
use App\Services\Email\EmailService;
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
            'company_logo' => EmailSetting::get('email_logo', config('app.company_logo')),
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
            'message' => __('Settings saved successfully'),
        ]);
    }

    /**
     * Upload a company logo for email signatures.
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,svg,gif|max:2048',
        ]);

        $file = $request->file('logo');
        $filename = 'logo-email.' . $file->getClientOriginalExtension();

        // Store in public/images directory
        $file->move(public_path('images'), $filename);

        $logoPath = 'images/' . $filename;

        // Persist logo path in email_settings
        EmailSetting::set('email_logo', $logoPath, 'text', 'branding');

        return response()->json([
            'success' => true,
            'path' => $logoPath,
            'url' => asset($logoPath),
            'message' => __('Logo uploaded successfully'),
        ]);
    }

    /**
     * Preview email with current branding settings.
     */
    public function preview(): JsonResponse
    {
        $signature = EmailService::renderSignature();
        $header = EmailSetting::get('email_header', '');
        $footer = EmailSetting::get('email_footer', '');

        $previewBody = '<p>' . __('This is a preview of your email branding.') . '</p>'
            . '<p>' . __('The header, footer and signature will appear on all outgoing emails.') . '</p>';

        $fullPreview = $header . $previewBody . $footer . $signature;

        return response()->json([
            'html' => $fullPreview,
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
