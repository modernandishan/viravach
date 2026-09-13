<?php

namespace Tests\Feature\Dashboard;

use App\Enums\RfqStatus;
use App\Models\Company;
use App\Models\Rfq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InfobarNavGroupsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The dashboard's shared tab strip groups related items under Metronic
     * `data-kt-menu` dropdown parents. These tests lock the structure and
     * the link preservation so a future refactor can't silently drop a
     * route or break the active-state highlight on the parent.
     */
    public function test_standalone_tabs_render_as_direct_links(): void
    {
        $user = User::factory()->create();

        $html = Livewire::actingAs($user)
            ->test('dashboard-elements.infobar')
            ->html();

        // The four standalone items stay as plain nav-links.
        $this->assertStringContainsString('href="'.route('dashboard').'"', $html);
        $this->assertStringContainsString('href="'.route('my-companies').'"', $html);
        $this->assertStringContainsString('href="'.route('chat').'"', $html);
        $this->assertStringContainsString('href="'.route('tickets').'"', $html);
    }

    public function test_dropdown_groups_contain_all_six_child_routes(): void
    {
        $user = User::factory()->create();

        $html = Livewire::actingAs($user)
            ->test('dashboard-elements.infobar')
            ->html();

        // Billing dropdown children.
        $this->assertStringContainsString('href="'.route('subscriptions').'"', $html);
        $this->assertStringContainsString('href="'.route('payments').'"', $html);

        // Content dropdown children.
        $this->assertStringContainsString('href="'.route('wordpress-content').'"', $html);
        $this->assertStringContainsString('href="'.route('company-views').'"', $html);

        // Account dropdown children.
        $this->assertStringContainsString('href="'.route('profile').'"', $html);
        $this->assertStringContainsString('href="'.route('settings').'"', $html);
    }

    public function test_dropdown_triggers_use_the_kt_menu_attributes(): void
    {
        $user = User::factory()->create();

        $html = Livewire::actingAs($user)
            ->test('dashboard-elements.infobar')
            ->html();

        // The strip ships exactly three dropdown parents (Billing, Content, Account).
        $this->assertSame(3, substr_count($html, 'data-kt-menu-trigger="click"'));
        $this->assertSame(3, substr_count($html, 'data-kt-menu="true"'));

        // Each parent uses the project-standard `attach=parent` pattern.
        $this->assertSame(3, substr_count($html, 'data-kt-menu-attach="parent"'));
    }

    public function test_dropdown_parent_labels_render_in_translated_form(): void
    {
        $user = User::factory()->create();

        $html = Livewire::actingAs($user)
            ->test('dashboard-elements.infobar')
            ->html();

        $this->assertStringContainsString(__('menu.nav_billing'), $html);
        $this->assertStringContainsString(__('menu.nav_content'), $html);
        $this->assertStringContainsString(__('menu.nav_account'), $html);
    }

    public function test_no_dropdown_parent_is_active_when_no_child_route_matches(): void
    {
        $user = User::factory()->create();

        // The infobar is mounted in isolation here, so request()->routeIs()
        // never matches any of subscriptions/payments/wordpress-content/...
        // None of the three parents should carry the `active` modifier on
        // its nav-link trigger.
        $html = Livewire::actingAs($user)
            ->test('dashboard-elements.infobar')
            ->html();

        foreach (['nav_billing', 'nav_content', 'nav_account'] as $key) {
            $label = __('menu.'.$key);
            // Look for a parent <a> with `active` in its nav-link class set,
            // ending at the translated label. Regex is the cleanest way to
            // tolerate the whitespace Blade emits between > and the label.
            $activePattern = '/class="nav-link text-active-primary ms-0 me-10 py-5 active"[^>]*>\s*'.preg_quote($label, '/').'/u';
            $this->assertDoesNotMatchRegularExpression(
                $activePattern,
                $html,
                "Parent '{$key}' should not be active on a non-matching route.",
            );

            $this->assertStringContainsString($label, $html);
        }
    }

    public function test_the_requests_tab_carries_a_badge_counting_pending_requests(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->for($user)->create();

        Rfq::factory()->count(2)->for($company)->create();
        Rfq::factory()->for($company)->create(['status' => RfqStatus::Closed]);

        // Another owner's pending request must not reach this badge.
        Rfq::factory()->for(Company::factory()->for(User::factory()))->create();

        $html = Livewire::actingAs($user)
            ->test('dashboard-elements.infobar')
            ->html();

        $this->assertStringContainsString(
            '<span class="badge badge-sm badge-circle badge-light-warning ms-1">2</span>',
            $html,
        );
    }

    /** A standing "0" reads as a broken counter, so nothing renders at all. */
    public function test_the_requests_tab_has_no_badge_without_pending_requests(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->for($user)->create();

        Rfq::factory()->for($company)->create(['status' => RfqStatus::Closed]);

        $html = Livewire::actingAs($user)
            ->test('dashboard-elements.infobar')
            ->html();

        $this->assertStringNotContainsString('badge-light-warning', $html);
        $this->assertStringNotContainsString('>0</span>', $html);
        // The tab itself is still there.
        $this->assertStringContainsString('href="'.route('rfqs').'"', $html);
    }

    public function test_strip_has_no_overflow_ancestor_that_would_clip_the_dropdowns(): void
    {
        $user = User::factory()->create();

        $html = Livewire::actingAs($user)
            ->test('dashboard-elements.infobar')
            ->html();

        // The dropdown panels are absolutely positioned inside their <li>,
        // so an overflow-auto/overflow-hidden strip would clip or scroll
        // them instead of letting them fly out. Regression guard.
        $this->assertStringNotContainsString('overflow-auto', $html);
        $this->assertStringNotContainsString('overflow-hidden', $html);
    }
}
