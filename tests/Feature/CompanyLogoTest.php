<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyLogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_logo_configuration_is_accessible(): void
    {
        config(['app.company_logo' => 'images/logos/sample-logo.svg']);

        $logoPath = config('app.company_logo');

        expect($logoPath)->toBe('images/logos/sample-logo.svg');
    }

    public function test_login_page_displays_logo_when_configured(): void
    {
        config(['app.company_logo' => 'images/logos/sample-logo.svg']);
        config(['app.company_name' => 'Test IP Firm']);

        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('images/logos/sample-logo.svg');
        $response->assertSee('Test IP Firm');
    }

    public function test_login_page_works_without_logo(): void
    {
        config(['app.company_logo' => '']);

        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertDontSee('<img src=');
    }

    public function test_navbar_displays_logo_when_configured(): void
    {
        config(['app.company_logo' => 'images/logos/sample-logo.svg']);
        config(['app.company_name' => 'Test IP Firm']);

        // Create a simple view that uses the app layout
        $testView = view('layouts.app')->render();

        expect($testView)->toContain('images/logos/sample-logo.svg');
        expect($testView)->toContain('Test IP Firm');
    }
}
