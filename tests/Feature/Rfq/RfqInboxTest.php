<?php

namespace Tests\Feature\Rfq;

use App\Enums\RfqStatus;
use App\Models\Company;
use App\Models\Rfq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use Tests\TestCase;

class RfqInboxTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Rfq $ownRfq;

    private Rfq $strangersRfq;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();

        $this->ownRfq = Rfq::factory()
            ->for(Company::factory()->for($this->owner))
            ->create(['buyer_name' => 'Ada Lovelace']);

        // A different owner's company entirely.
        $this->strangersRfq = Rfq::factory()
            ->for(Company::factory()->for(User::factory()))
            ->create(['buyer_name' => 'Grace Hopper']);
    }

    public function test_the_inbox_lists_only_the_users_own_companies_requests(): void
    {
        $this->actingAs($this->owner);

        // Rows are identified by "#id" now; matched with their tags so a
        // request numbered #1 cannot be satisfied by #12 rendering.
        Livewire::test('pages::dashboard.rfqs')
            ->assertSee($this->rowMarker($this->ownRfq), false)
            ->assertDontSee($this->rowMarker($this->strangersRfq), false);
    }

    /**
     * A list row is a pointer, not a preview: the buyer's identity and
     * contact details render only in the pane the owner deliberately opens.
     */
    public function test_the_list_shows_no_buyer_details_until_a_request_is_opened(): void
    {
        $this->actingAs($this->owner);

        $this->ownRfq->update([
            'buyer_email' => 'ada@example.com',
            'buyer_phone' => '+49 40 1234567',
        ]);

        Livewire::test('pages::dashboard.rfqs')
            ->assertSee($this->rowMarker($this->ownRfq), false)
            ->assertDontSee('Ada Lovelace')
            ->assertDontSee('ada@example.com')
            ->assertDontSee('+49 40 1234567')
            // Still one click away, unchanged.
            ->call('selectRfq', $this->ownRfq->id)
            ->assertSee('Ada Lovelace')
            ->assertSee('ada@example.com');
    }

    public function test_the_list_pages_at_ten_and_page_two_is_reachable(): void
    {
        $this->actingAs($this->owner);

        // 12 older requests plus setUp's newest one: 13 rows, 10 to a page.
        $oldest = $this->seedOlderRequests(12)->last();

        Livewire::test('pages::dashboard.rfqs')
            ->assertSee($this->rowMarker($this->ownRfq), false)
            ->assertDontSee($this->rowMarker($oldest), false)
            ->call('gotoPage', 2)
            ->assertSee($this->rowMarker($oldest), false)
            ->assertDontSee($this->rowMarker($this->ownRfq), false);
    }

    /**
     * Page 2 of the old filter is usually past the end of the new one, which
     * would leave the owner staring at an empty list with no way back.
     */
    public function test_changing_the_filter_returns_to_the_first_page(): void
    {
        $this->actingAs($this->owner);

        $this->seedOlderRequests(12);

        Livewire::test('pages::dashboard.rfqs')
            ->call('gotoPage', 2)
            ->set('statusFilter', RfqStatus::Pending->value)
            ->assertSee($this->rowMarker($this->ownRfq), false);
    }

    public function test_sorting_returns_to_the_first_page_too(): void
    {
        $this->actingAs($this->owner);

        $oldest = $this->seedOlderRequests(12)->last();

        Livewire::test('pages::dashboard.rfqs')
            ->call('gotoPage', 2)
            ->call('toggleSort')
            // Oldest first, page 1.
            ->assertSee($this->rowMarker($oldest), false);
    }

    /**
     * Responded and Expired stay on the enum and stay filterable by value,
     * but nothing the app writes today can produce them, so the dropdown
     * does not offer them as choices.
     */
    public function test_the_status_filter_offers_only_pending_and_closed(): void
    {
        $this->actingAs($this->owner);

        $component = Livewire::test('pages::dashboard.rfqs');

        $this->assertSame(
            ['', RfqStatus::Pending->value, RfqStatus::Closed->value],
            array_keys($component->instance()->statusOptions()),
        );

        $component
            ->assertSee(__('rfq.status_pending'))
            ->assertSee(__('rfq.status_closed'))
            ->assertDontSee(__('rfq.status_responded'))
            ->assertDontSee(__('rfq.status_expired'));
    }

    public function test_another_companys_request_cannot_be_opened_by_id(): void
    {
        $this->actingAs($this->owner);

        Livewire::test('pages::dashboard.rfqs')
            ->call('selectRfq', $this->strangersRfq->id)
            ->assertStatus(404);
    }

    public function test_another_companys_request_cannot_be_closed_by_id(): void
    {
        $this->actingAs($this->owner);

        // selectedRfqId is set directly, bypassing selectRfq's own check, to
        // prove the action re-scopes rather than trusting component state.
        Livewire::test('pages::dashboard.rfqs')
            ->set('selectedRfqId', $this->strangersRfq->id)
            ->call('closeRfq')
            ->assertStatus(404);

        $this->assertSame(RfqStatus::Pending, $this->strangersRfq->fresh()->status);
    }

    public function test_the_open_request_shows_the_buyers_message_and_details(): void
    {
        $this->actingAs($this->owner);

        $this->ownRfq->update([
            'buyer_country' => 'Germany',
            'message' => 'We need 500 units delivered to Hamburg.',
        ]);

        Livewire::test('pages::dashboard.rfqs')
            ->call('selectRfq', $this->ownRfq->id)
            ->assertSee('We need 500 units delivered to Hamburg.')
            ->assertSee('Germany')
            // Matched with its tag: a bare "EN" is a substring of half the
            // surrounding copy and would pass without rendering anything.
            ->assertSee('<bdi>'.strtoupper($this->ownRfq->locale).'</bdi>', false);
    }

    public function test_the_buyers_email_is_shown_as_a_mailto_link(): void
    {
        $this->actingAs($this->owner);

        $this->ownRfq->update(['buyer_email' => 'ada@example.com']);

        Livewire::test('pages::dashboard.rfqs')
            ->call('selectRfq', $this->ownRfq->id)
            ->assertSee('mailto:ada@example.com', false)
            ->assertSee('ada@example.com');
    }

    public function test_an_international_phone_is_shown_as_a_tel_link_and_a_whatsapp_link(): void
    {
        $this->actingAs($this->owner);

        $this->ownRfq->update(['buyer_phone' => '+49 40 1234567']);

        Livewire::test('pages::dashboard.rfqs')
            ->call('selectRfq', $this->ownRfq->id)
            // Layout whitespace is stripped for the href, kept for display.
            ->assertSee('tel:+49401234567', false)
            ->assertSee('https://wa.me/49401234567', false);
    }

    public function test_a_double_zero_prefixed_phone_becomes_a_whatsapp_link_too(): void
    {
        $this->actingAs($this->owner);

        $this->ownRfq->update(['buyer_phone' => '0049 40 1234567']);

        Livewire::test('pages::dashboard.rfqs')
            ->call('selectRfq', $this->ownRfq->id)
            ->assertSee('https://wa.me/49401234567', false);
    }

    /**
     * A bare local number carries no country code, and this buyer is
     * international by definition — guessing one would open a chat with a
     * stranger, so only the tel: link is offered. See Rfq::whatsappNumber().
     */
    public function test_a_local_phone_gets_a_tel_link_but_no_whatsapp_link(): void
    {
        $this->actingAs($this->owner);

        $this->ownRfq->update(['buyer_phone' => '020 7946 0018']);

        Livewire::test('pages::dashboard.rfqs')
            ->call('selectRfq', $this->ownRfq->id)
            ->assertSee('tel:02079460018', false)
            ->assertDontSee('wa.me', false);
    }

    public function test_a_request_without_a_phone_renders_no_phone_links(): void
    {
        $this->actingAs($this->owner);

        $this->ownRfq->update(['buyer_phone' => null]);

        Livewire::test('pages::dashboard.rfqs')
            ->call('selectRfq', $this->ownRfq->id)
            ->assertDontSee('tel:', false)
            ->assertDontSee('wa.me', false);
    }

    public function test_the_owner_can_close_their_own_request(): void
    {
        $this->actingAs($this->owner);

        Livewire::test('pages::dashboard.rfqs')
            ->call('selectRfq', $this->ownRfq->id)
            ->call('closeRfq');

        $this->assertSame(RfqStatus::Closed, $this->ownRfq->fresh()->status);
    }

    public function test_a_closed_request_no_longer_offers_the_close_action(): void
    {
        $this->actingAs($this->owner);

        $this->ownRfq->update(['status' => RfqStatus::Closed]);

        Livewire::test('pages::dashboard.rfqs')
            ->call('selectRfq', $this->ownRfq->id)
            ->assertDontSee(__('rfq.close_action'));
    }

    public function test_the_status_filter_narrows_the_list(): void
    {
        $this->actingAs($this->owner);

        $closed = Rfq::factory()
            ->for(Company::factory()->for($this->owner))
            ->create(['status' => RfqStatus::Closed]);

        Livewire::test('pages::dashboard.rfqs')
            ->set('statusFilter', RfqStatus::Closed->value)
            ->assertSee($this->rowMarker($closed), false)
            ->assertDontSee($this->rowMarker($this->ownRfq), false);

        $this->assertSame(RfqStatus::Closed, $closed->fresh()->status);
    }

    /**
     * Responded is no longer written by anything (see closeRfq()'s note), but
     * rows created before the in-platform reply was removed still carry it,
     * so the filter must keep reaching them.
     */
    public function test_legacy_responded_requests_remain_filterable(): void
    {
        $this->actingAs($this->owner);

        $responded = Rfq::factory()
            ->for(Company::factory()->for($this->owner))
            ->responded()
            ->create();

        Livewire::test('pages::dashboard.rfqs')
            ->set('statusFilter', RfqStatus::Responded->value)
            ->assertSee($this->rowMarker($responded), false)
            ->assertDontSee($this->rowMarker($this->ownRfq), false);
    }

    /** The list row's identifier, matched with its tags. */
    private function rowMarker(Rfq $rfq): string
    {
        return '<bdi>#'.$rfq->id.'</bdi>';
    }

    /**
     * Requests older than setUp's, newest first, so the ordering under test
     * is the component's and not the insertion order's.
     *
     * @return Collection<int, Rfq>
     */
    private function seedOlderRequests(int $count): Collection
    {
        $company = Company::factory()->for($this->owner)->create();

        return collect(range(1, $count))->map(
            fn (int $minutes): Rfq => Rfq::factory()->for($company)->create([
                'created_at' => now()->subMinutes($minutes),
            ]),
        );
    }
}
