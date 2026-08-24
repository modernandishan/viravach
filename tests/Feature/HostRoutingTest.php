<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Host routing guarantees.
 *
 * Domain-scoped routing fails quietly: a route registered on the wrong host
 * still resolves in development, and a duplicate registration only surfaces
 * when route:cache runs. These tests pin the host boundaries so a regression
 * fails here rather than in production.
 *
 * Every assertion below deliberately targets a response that redirects or is
 * generated without rendering a view, so the suite stays free of database
 * and template dependencies and can run anywhere.
 */
class HostRoutingTest extends TestCase
{
    private function publicUrl(string $path = '/'): string
    {
        return 'https://'.config('domains.public').$path;
    }

    private function appUrl(string $path = '/'): string
    {
        return 'https://'.config('domains.app').$path;
    }

    private function adminUrl(string $path = '/'): string
    {
        return 'https://'.config('domains.admin').$path;
    }

    public function test_legacy_dashboard_paths_redirect_to_the_app_host(): void
    {
        $this->get($this->publicUrl('/dashboard/profile'))
            ->assertStatus(301)
            ->assertRedirect('https://'.config('domains.app').'/profile');
    }

    public function test_legacy_localized_dashboard_paths_redirect_to_the_app_host(): void
    {
        $this->get($this->publicUrl('/fa/dashboard/settings'))
            ->assertStatus(301)
            ->assertRedirect('https://'.config('domains.app').'/settings');
    }

    public function test_legacy_admin_path_redirects_to_the_admin_host(): void
    {
        $this->get($this->publicUrl('/admin'))
            ->assertStatus(301)
            ->assertRedirect('https://'.config('domains.admin'));
    }

    public function test_dashboard_routes_require_authentication_on_the_app_host(): void
    {
        $this->get($this->appUrl('/profile'))->assertRedirect();
    }

    public function test_the_panel_requires_authentication_on_the_admin_host(): void
    {
        $this->get($this->adminUrl('/'))->assertRedirect();
    }

    public function test_private_hosts_are_marked_noindex(): void
    {
        $expected = 'noindex, nofollow, noarchive';

        $this->get($this->appUrl('/profile'))
            ->assertHeader('X-Robots-Tag', $expected);

        $this->get($this->adminUrl('/'))
            ->assertHeader('X-Robots-Tag', $expected);
    }

    public function test_the_public_host_stays_indexable(): void
    {
        $response = $this->get($this->publicUrl('/admin'));

        $this->assertFalse(
            $response->headers->has('X-Robots-Tag'),
            'The public host must never send a robots header.',
        );
    }

    public function test_each_route_generates_a_url_on_its_owning_host(): void
    {
        $this->assertStringStartsWith('https://'.config('domains.public'), route('home'));
        $this->assertStringStartsWith('https://'.config('domains.public'), route('pricing'));
        $this->assertStringStartsWith('https://'.config('domains.app'), route('dashboard'));
        $this->assertStringStartsWith('https://'.config('domains.app'), route('profile'));
        $this->assertStringStartsWith('https://'.config('domains.admin'), route('filament.admin.auth.login'));
    }

    public function test_no_route_name_is_registered_twice(): void
    {
        // Guards against the duplicate-registration class of bug, which
        // route:cache rejects but the development router silently tolerates.
        $duplicates = collect(app('router')->getRoutes()->getRoutes())
            ->map(fn ($route) => $route->getName())
            ->filter()
            ->countBy()
            ->filter(fn (int $count) => $count > 1)
            ->keys()
            ->all();

        $this->assertEmpty(
            $duplicates,
            'Duplicate route names: '.implode(', ', $duplicates),
        );
    }
}
