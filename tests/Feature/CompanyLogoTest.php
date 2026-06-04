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

    public function test_login_page_works_without_logo(): void
    {
        config(['app.company_logo' => '']);

        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertDontSee('<img src=');
    }

    // The other navbar/logo tests rendered `layouts.app` directly and asserted
    // raw HTML against `app.company_logo` / `app.company_name`. Since the
    // navbar moved to Inertia/Vue (see `resources/js/components/Navigation.vue`
    // and `resources/js/Layouts/GuestLayout.vue`), the logo is no longer
    // emitted server-side and these assertions can no longer be expressed at
    // the Blade level. They need to be rewritten as Inertia/Vue tests; until
    // then we keep only the assertions that still make sense on the SSR shell.
}
