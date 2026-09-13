<?php

namespace Tests\Feature\Notifications;

use App\Mail\NewRfqReceivedMail;
use App\Models\Company;
use App\Models\Rfq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class NewRfqReceivedMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_every_detail_the_owner_needs(): void
    {
        $company = Company::factory()->create(['name' => ['en' => 'Acme Tools']]);
        $rfq = Rfq::factory()->for($company)->create([
            'buyer_name' => 'Ada Lovelace',
            'buyer_email' => 'ada@example.com',
            'buyer_phone' => '+44 20 7946 0018',
            'buyer_country' => 'United Kingdom',
            'message' => "First line.\nSecond line.",
            'locale' => 'ru',
        ]);

        $html = (new NewRfqReceivedMail($rfq))->render();

        $this->assertStringContainsString('Ada Lovelace', $html);
        $this->assertStringContainsString('mailto:ada@example.com', $html);
        $this->assertStringContainsString('tel:+442079460018', $html);
        $this->assertStringContainsString('United Kingdom', $html);
        $this->assertStringContainsString('Acme Tools', $html);
        // Redundant with the render language today, but kept: it documents
        // for the owner which language the buyer actually wrote in.
        $this->assertStringContainsString('русский', $html);
        // Line breaks survive as markup: Outlook ignores white-space:pre-line.
        $this->assertStringContainsString('First line.<br />', $html);
        $this->assertStringContainsString($rfq->dashboardUrl(), $html);
    }

    public function test_optional_buyer_fields_leave_no_empty_rows(): void
    {
        $rfq = Rfq::factory()->create([
            'buyer_phone' => null,
            'buyer_country' => null,
            'locale' => 'en',
        ]);

        $html = (new NewRfqReceivedMail($rfq))->render();

        // Matched with the surrounding tags so the assertion cannot be
        // satisfied (or broken) by the word appearing in prose elsewhere.
        $this->assertStringNotContainsString('>'.__('rfq.buyer_phone', locale: 'en').'<', $html);
        $this->assertStringNotContainsString('>'.__('rfq.buyer_country', locale: 'en').'<', $html);
        $this->assertStringNotContainsString('tel:', $html);
    }

    public function test_a_company_named_in_no_relevant_locale_still_names_itself(): void
    {
        // Named in neither the buyer's language nor Persian: spatie falls
        // back to APP_FALLBACK_LOCALE (fa), finds nothing there either, and
        // resolves to an empty string. A blank after "New quote request for"
        // is worse than a name the reader has to squint at.
        $company = Company::factory()->create(['name' => ['tr' => 'Akme Aletleri']]);
        $rfq = Rfq::factory()->for($company)->create(['locale' => 'en']);

        $mailable = new NewRfqReceivedMail($rfq);
        $html = $mailable->render();

        $this->assertStringContainsString('Akme Aletleri', $html);
        $this->assertSame(
            __('rfq.mail_subject', ['company' => 'Akme Aletleri'], 'en'),
            $mailable->subject,
        );
    }

    public function test_it_renders_right_to_left_for_a_persian_buyer(): void
    {
        $rfq = Rfq::factory()->create(['locale' => 'fa']);

        $html = (new NewRfqReceivedMail($rfq))->render();

        $this->assertStringContainsString('dir="rtl"', $html);
        $this->assertStringContainsString('lang="fa"', $html);
        $this->assertStringContainsString(__('rfq.mail_heading', locale: 'fa'), $html);
        $this->assertStringContainsString('text-align:right', $html);
    }

    public function test_the_render_locale_is_the_buyers_and_not_the_ambient_one(): void
    {
        // The owner is a Persian-speaking user and the worker (or request)
        // that sends this is running in Persian, but the buyer wrote from the
        // Russian site — the owner has to answer in Russian, so the whole
        // mail arrives in Russian, laid out left to right.
        $rfq = Rfq::factory()->create(['locale' => 'ru']);

        App::setLocale('fa');
        $html = (new NewRfqReceivedMail($rfq))->render();

        $this->assertStringContainsString('lang="ru"', $html);
        $this->assertStringContainsString('dir="ltr"', $html);
        $this->assertStringNotContainsString('dir="rtl"', $html);
        $this->assertStringContainsString(__('rfq.mail_heading', locale: 'ru'), $html);
        $this->assertStringNotContainsString(__('rfq.mail_heading', locale: 'fa'), $html);
        // Rendering must not leave the ambient locale switched behind it.
        $this->assertSame('fa', App::getLocale());
    }

    public function test_it_renders_in_the_buyers_locale_for_every_supported_locale(): void
    {
        $directions = ['en' => 'ltr', 'fa' => 'rtl', 'ar' => 'rtl', 'ru' => 'ltr', 'tr' => 'ltr'];

        foreach ($directions as $locale => $direction) {
            $company = Company::factory()->create(['name' => ['en' => 'Acme Tools']]);
            $rfq = Rfq::factory()->for($company)->create(['locale' => $locale]);

            $mailable = new NewRfqReceivedMail($rfq);
            $html = $mailable->render();

            $this->assertStringContainsString('lang="'.$locale.'"', $html, "Wrong render locale for [{$locale}].");
            $this->assertStringContainsString('dir="'.$direction.'"', $html, "Wrong direction for [{$locale}].");
            $this->assertStringContainsString(__('rfq.mail_cta', locale: $locale), $html, "Untranslated CTA in [{$locale}].");
            $this->assertStringContainsString(__('rfq.mail_heading', locale: $locale), $html, "Untranslated heading in [{$locale}].");
            // A missing key renders as the raw dotted key, never as a sentence.
            $this->assertStringNotContainsString('rfq.mail_', $html, "Untranslated string in [{$locale}].");

            // The subject travels with the body: hydrated inside the same
            // locale switch, so it is the buyer's language too.
            $this->assertSame(
                __('rfq.mail_subject', ['company' => 'Acme Tools'], $locale),
                $mailable->subject,
                "Subject not rendered in [{$locale}].",
            );
        }
    }
}
