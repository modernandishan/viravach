<?php

namespace Tests\Feature\Dashboard;

use App\Enums\CompanyContentStatus;
use App\Enums\CompanyReviewStatus;
use App\Models\Company;
use App\Models\CompanyContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CompanyContentEditorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A schema-valid payload for one locale: every field respects
     * CompanyContentSchema's min/max, including the required 'v' version
     * marker and a non-empty markets.countries so we can assert it
     * survives an edit untouched.
     *
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'v' => 1,
            'hero' => [
                'headline' => str_repeat('H', 25),
                'subheadline' => str_repeat('S', 45),
                'image_alt' => str_repeat('A', 25),
            ],
            'about' => [
                'heading' => str_repeat('B', 15),
                'body' => str_repeat('About text. ', 70),
            ],
            'offerings' => [
                ['title' => 'Offering One', 'body' => str_repeat('O', 210)],
                ['title' => 'Offering Two', 'body' => str_repeat('O', 210)],
                ['title' => 'Offering Three', 'body' => str_repeat('O', 210)],
            ],
            'strengths' => [
                ['title' => 'Strength One', 'body' => str_repeat('S', 110)],
                ['title' => 'Strength Two', 'body' => str_repeat('S', 110)],
                ['title' => 'Strength Three', 'body' => str_repeat('S', 110)],
            ],
            'markets' => [
                'heading' => str_repeat('M', 15),
                'body' => str_repeat('Market. ', 30),
                'countries' => ['CN', 'AE'],
            ],
            'specs' => [
                ['label' => 'Weight', 'value' => '10kg'],
            ],
            'faq' => [
                ['q' => str_repeat('Q', 15), 'a' => str_repeat('A', 110)],
                ['q' => str_repeat('Q', 15), 'a' => str_repeat('A', 110)],
                ['q' => str_repeat('Q', 15), 'a' => str_repeat('A', 110)],
                ['q' => str_repeat('Q', 15), 'a' => str_repeat('A', 110)],
            ],
            'cta' => [
                'heading' => str_repeat('C', 15),
                'body' => str_repeat('Call to action. ', 5),
            ],
        ];
    }

    private function makeCompany(array $overrides = []): Company
    {
        return Company::factory()->create(array_merge([
            'content' => ['en' => $this->validPayload(), 'fa' => $this->validPayload()],
        ], $overrides));
    }

    public function test_the_owner_sees_the_editor(): void
    {
        $company = $this->makeCompany();

        Livewire::actingAs($company->user)
            ->test('pages::dashboard.edit-company', ['company' => $company->id])
            ->assertSee(__('companies.content_editor_title'));
    }

    public function test_the_editor_opens_on_the_persian_tab_when_the_ui_locale_is_fa(): void
    {
        app()->setLocale('fa');

        $company = $this->makeCompany();

        $html = Livewire::actingAs($company->user)
            ->test('company-content.content-editor', ['company' => $company])
            ->html();

        $this->assertStringContainsString('tab-pane fade show active" id="kt_content_editor_fa"', $html);
        $this->assertStringNotContainsString('tab-pane fade show active" id="kt_content_editor_en"', $html);
    }

    public function test_the_editor_opens_on_the_arabic_tab_when_the_ui_locale_is_ar(): void
    {
        app()->setLocale('ar');

        $company = $this->makeCompany();

        $html = Livewire::actingAs($company->user)
            ->test('company-content.content-editor', ['company' => $company])
            ->html();

        $this->assertStringContainsString('tab-pane fade show active" id="kt_content_editor_ar"', $html);
        $this->assertStringNotContainsString('tab-pane fade show active" id="kt_content_editor_en"', $html);
    }

    /**
     * Regression test for a binding bug: every wire:model path was
     * correct, but native wire:model-bound inputs never receive a
     * server-rendered value (Livewire fills them client-side from the
     * wire:snapshot JSON only), so the rendered HTML showed empty fields
     * even though the character counter — a plain Blade expression —
     * showed the real length. Asserting the property is set is not
     * enough to catch this; the value must actually appear in the markup.
     */
    public function test_the_rendered_html_contains_the_stored_value_for_every_field_type(): void
    {
        $payload = $this->validPayload();
        $company = $this->makeCompany();

        $html = Livewire::actingAs($company->user)
            ->test('company-content.content-editor', ['company' => $company])
            ->html();

        // Plain input: hero.headline.
        $this->assertStringContainsString(
            'value="'.$payload['hero']['headline'].'"',
            $html,
        );

        // Textarea: about.body.
        $this->assertStringContainsString(
            '>'.$payload['about']['body'].'</textarea>',
            $html,
        );

        // Repeater field: faq.0.q.
        $this->assertStringContainsString(
            'value="'.$payload['faq'][0]['q'].'"',
            $html,
        );
    }

    public function test_a_non_owner_gets_403(): void
    {
        $company = $this->makeCompany();
        $intruder = User::factory()->create();

        Livewire::actingAs($intruder)
            ->test('company-content.content-editor', ['company' => $company])
            ->assertForbidden();
    }

    public function test_editing_a_locale_persists_it_and_resets_review_status_to_pending(): void
    {
        $company = $this->makeCompany(['review_status' => CompanyReviewStatus::Approved, 'reviewed_at' => now()]);

        Livewire::actingAs($company->user)
            ->test('company-content.content-editor', ['company' => $company])
            ->set('content.en.hero.headline', str_repeat('New Headline ', 3))
            ->call('save')
            ->assertHasNoErrors();

        $company->refresh();

        $this->assertSame(str_repeat('New Headline ', 3), $company->content['en']['hero']['headline']);
        $this->assertSame(CompanyReviewStatus::PendingReview, $company->review_status);
        $this->assertNull($company->reviewed_at);
    }

    public function test_untouched_locales_are_left_byte_identical(): void
    {
        $company = $this->makeCompany();
        $originalFa = $company->content['fa'];

        Livewire::actingAs($company->user)
            ->test('company-content.content-editor', ['company' => $company])
            ->set('content.en.hero.headline', str_repeat('New Headline ', 3))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($originalFa, $company->fresh()->content['fa']);
    }

    public function test_a_save_violating_the_schema_is_rejected_with_errors_and_writes_nothing(): void
    {
        $company = $this->makeCompany();
        $original = $company->content;

        Livewire::actingAs($company->user)
            ->test('company-content.content-editor', ['company' => $company])
            ->set('content.en.hero.headline', 'too short')
            ->call('save')
            ->assertHasErrors(['content.en.hero.headline']);

        $this->assertSame($original, $company->fresh()->content);
    }

    public function test_markets_countries_and_the_schema_version_survive_an_edit(): void
    {
        $company = $this->makeCompany();

        Livewire::actingAs($company->user)
            ->test('company-content.content-editor', ['company' => $company])
            ->set('content.en.markets.heading', str_repeat('Updated Heading ', 2))
            ->call('save')
            ->assertHasNoErrors();

        $fresh = $company->fresh()->content['en'];

        $this->assertSame(1, $fresh['v']);
        $this->assertSame(['CN', 'AE'], $fresh['markets']['countries']);
    }

    public function test_saving_while_status_is_generating_is_rejected_with_409(): void
    {
        $company = $this->makeCompany();
        $originalContent = $company->content;

        CompanyContent::forceCreate([
            'company_id' => $company->id,
            'status' => CompanyContentStatus::Generating,
            'step' => 2,
        ]);

        Livewire::actingAs($company->user)
            ->test('company-content.content-editor', ['company' => $company])
            ->set('content.en.hero.headline', str_repeat('New Headline ', 3))
            ->call('save')
            ->assertStatus(409);

        $this->assertSame($originalContent, $company->fresh()->content);
    }

    public function test_the_editor_is_absent_when_no_content_exists(): void
    {
        $company = Company::factory()->create(['content' => null]);

        Livewire::actingAs($company->user)
            ->test('pages::dashboard.edit-company', ['company' => $company->id])
            ->assertDontSee(__('companies.content_editor_title'));
    }

    public function test_repeater_rows_respect_the_schemas_min_and_max_counts(): void
    {
        $company = $this->makeCompany();

        $component = Livewire::actingAs($company->user)
            ->test('company-content.content-editor', ['company' => $company]);

        // specs: min 0, max 12 — starts with 1 item from validPayload().
        $this->assertCount(1, $component->get('content.en.specs'));

        for ($i = 0; $i < 15; $i++) {
            $component->call('addItem', 'en', 'specs');
        }

        $this->assertCount(12, $component->get('content.en.specs'));

        for ($i = 0; $i < 15; $i++) {
            $component->call('removeItem', 'en', 'specs', 0);
        }

        $this->assertCount(0, $component->get('content.en.specs'));

        // strengths: min 3, max 6 — starts with 3 items from validPayload().
        $this->assertCount(3, $component->get('content.en.strengths'));

        $component->call('removeItem', 'en', 'strengths', 0);

        $this->assertCount(3, $component->get('content.en.strengths'));

        for ($i = 0; $i < 10; $i++) {
            $component->call('addItem', 'en', 'strengths');
        }

        $this->assertCount(6, $component->get('content.en.strengths'));
    }
}
