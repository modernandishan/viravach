<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Events\TicketCreated;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Chat\TicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Musonza\Chat\Models\Conversation;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->staff = User::factory()->create();
        $this->staff->assignRole('support');
    }

    public function test_it_creates_a_ticket_with_reference_and_ticket_conversation(): void
    {
        Event::fake([TicketCreated::class]);

        $ticket = app(TicketService::class)->create($this->user, 'Broken export', 'The export does not finish.');

        // Human-readable reference: TICKET-YYYYMMDD-NNNN
        $this->assertMatchesRegularExpression('/^TICKET-\d{8}-\d{4}$/', $ticket->reference_number);
        $this->assertSame(TicketStatus::Open, $ticket->status);
        $this->assertSame($this->user->id, $ticket->user_id);

        // Private (NOT direct) conversation tagged type=ticket with the
        // creator as participant.
        $conversation = $ticket->conversation;
        $this->assertFalse((bool) $conversation->direct_message);
        $this->assertSame('ticket', $conversation->data['type']);
        $this->assertTrue($conversation->participants()
            ->where('messageable_type', $this->user->getMorphClass())
            ->where('messageable_id', $this->user->id)
            ->exists());

        // The first message is the user's body.
        $this->assertSame('The export does not finish.', $conversation->messages()->first()->body);

        Event::assertDispatched(TicketCreated::class);
    }

    public function test_reference_numbers_sequence_per_day(): void
    {
        $service = app(TicketService::class);

        $first = $service->create($this->user, 'One', 'One body.');
        $second = $service->create($this->user, 'Two', 'Two body.');

        $this->assertNotSame($first->reference_number, $second->reference_number);
        $this->assertSame(1, (int) substr($first->reference_number, -4));
        $this->assertSame(2, (int) substr($second->reference_number, -4));
    }

    public function test_staff_reply_claims_participation_and_answers(): void
    {
        $service = app(TicketService::class);
        $ticket = $service->create($this->user, 'One', 'One body.');

        $service->addStaffReply($ticket, $this->staff, 'We are looking into it.');

        $this->assertTrue($ticket->conversation->participants()
            ->where('messageable_type', $this->staff->getMorphClass())
            ->where('messageable_id', $this->staff->id)
            ->exists());
        $this->assertSame(TicketStatus::Answered, $ticket->fresh()->status);
    }

    public function test_concurrent_ticket_creations_get_distinct_reference_numbers(): void
    {
        // Simulate the race: both "workers" read the count BEFORE either
        // commits its insert, so both compute the same next number. One wins
        // the unique index; the loser must retry with a recomputed count and
        // still succeed — not bubble a QueryException to the user.
        $collidingReference = null;

        $service = new class extends TicketService
        {
            /** Next call returns this exact value once, then delegates — the one-shot race simulation. */
            public ?string $collideWith = null;

            protected function nextReferenceNumber(): string
            {
                if ($this->collideWith !== null) {
                    $forced = $this->collideWith;
                    $this->collideWith = null;

                    return $forced;
                }

                return parent::nextReferenceNumber();
            }
        };

        $first = $service->create($this->user, 'First', 'First body.');

        // Second creation's FIRST attempt is forced onto the same number as
        // the committed first ticket: unique violation on insert, the retry
        // recomputes the day's count (now 1 committed row) and succeeds with
        // the next sequence.
        $service->collideWith = $first->reference_number;

        $second = $service->create($this->user, 'Second', 'Second body.');

        $this->assertNotSame($first->reference_number, $second->reference_number);
        $this->assertSame(2, (int) substr($second->reference_number, -4));
        $this->assertSame(2, Ticket::query()->count());
    }

    public function test_user_reply_reopens_a_closed_ticket(): void
    {
        $service = app(TicketService::class);
        $ticket = $service->create($this->user, 'One', 'One body.');
        $ticket->update(['status' => TicketStatus::Closed]);

        $service->addUserReply($ticket, $this->user, 'Actually, still broken.');

        $this->assertSame(TicketStatus::Open, $ticket->fresh()->status);
    }

    public function test_user_reply_keeps_an_open_ticket_open(): void
    {
        $service = app(TicketService::class);
        $ticket = $service->create($this->user, 'One', 'One body.');

        $service->addUserReply($ticket, $this->user, 'More detail.');

        $this->assertSame(TicketStatus::Open, $ticket->fresh()->status);
    }

    public function test_staff_of_each_role_sees_tickets_in_the_shared_queue(): void
    {
        app(TicketService::class)->create($this->user, 'One', 'One body.');

        foreach (['support', 'admin', 'super_admin'] as $role) {
            $staff = User::factory()->create();
            $staff->assignRole($role);

            // The same query the support-chats inbox uses for the ticket queue.
            $visible = Ticket::query()
                ->whereIn('status', [TicketStatus::Open, TicketStatus::Answered])
                ->pluck('id');

            $this->assertSame(1, $visible->count(), "role {$role} should see the ticket");
        }
    }

    public function test_only_the_owner_can_open_a_ticket_thread(): void
    {
        $service = app(TicketService::class);
        $ticket = $service->create($this->user, 'One', 'One body.');

        $this->actingAs($this->user);

        $component = Livewire::test('pages::dashboard.tickets')
            ->call('selectTicket', $ticket->id);

        $component->assertStatus(200);
        $this->assertSame($ticket->id, $component->get('selectedTicketId'));

        $stranger = User::factory()->create();
        $this->actingAs($stranger);

        Livewire::test('pages::dashboard.tickets')
            ->call('selectTicket', $ticket->id)
            ->assertStatus(403);
    }

    public function test_the_tickets_page_renders_for_an_authenticated_user(): void
    {
        $this->actingAs($this->user);

        $service = app(TicketService::class);
        $ticket = $service->create($this->user, 'One', 'One body.');

        $this->get(route('tickets'))
            ->assertOk()
            ->assertSee('One')
            ->assertSee($ticket->reference_number);
    }

    public function test_a_user_with_no_company_and_no_subscription_can_create_a_ticket(): void
    {
        // Being logged in is the whole eligibility rule (see routes/app.php):
        // no plan feature, no company and no subscription is required.
        $newcomer = User::factory()->create();

        $this->assertFalse($newcomer->companies()->exists());
        $this->assertSame(0, $newcomer->tickets()->count());

        $this->actingAs($newcomer);

        Livewire::test('pages::dashboard.tickets')
            ->set('subject', 'Cannot sign in on mobile')
            ->set('createBody', 'The app logs me out immediately.')
            ->call('createTicket')
            ->assertHasNoErrors()
            ->assertDispatched('ticket-created');

        $this->assertDatabaseHas('tickets', [
            'user_id' => $newcomer->id,
            'subject' => 'Cannot sign in on mobile',
            'status' => TicketStatus::Open->value,
        ]);
    }

    public function test_a_guest_cannot_reach_the_ticket_form(): void
    {
        $this->get(route('tickets'))
            ->assertRedirect(route('auth.sign-in'));

        $this->assertGuest();
        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_the_tickets_page_renders_the_support_center_markup(): void
    {
        $this->actingAs($this->user);

        app(TicketService::class)->create($this->user, 'One', 'One body.');

        $response = $this->get(route('tickets'))->assertOk();

        // Support Center hero, the create-ticket modal and the queue row glyph.
        $response->assertSee('kt_modal_new_ticket', false)
            ->assertSee('ki-duotone ki-add-files', false)
            ->assertSee(__('tickets.hero_subtitle'), false)
            ->assertSee(__('tickets.my_tickets'), false);

        // Reference markup must never leak demo2 asset paths.
        $response->assertDontSee('/demo2/', false);
    }

    public function test_the_tickets_thread_renders_the_comment_card_markup(): void
    {
        $this->actingAs($this->user);

        $ticket = app(TicketService::class)->create($this->user, 'One', 'One body.');

        Livewire::test('pages::dashboard.tickets')
            ->call('selectTicket', $ticket->id)
            ->assertSee('card card-bordered', false)
            ->assertSee(__('tickets.reply_title'), false)
            ->assertSee(__('tickets.reference'), false);
    }

    public function test_the_tickets_page_renders_right_to_left_in_persian(): void
    {
        $this->actingAs($this->user);

        app(TicketService::class)->create($this->user, 'One', 'One body.');

        // Dashboard routes carry no locale prefix — SetLocaleFromSession
        // resolves the language from the shared session cookie instead.
        $this->withSession(['locale' => 'fa'])
            ->get(route('tickets'))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('style.bundle.rtl.css', false)
            ->assertDontSee('/demo2/', false);
    }

    public function test_the_staff_ticket_thread_keeps_the_translation_control(): void
    {
        $this->actingAs($this->staff);

        $ticket = app(TicketService::class)->create($this->user, 'Broken export', 'Details.');

        // musonza scopes a thread's messages to its participants, so staff only
        // see the history once addStaffReply() has joined them.
        app(TicketService::class)->addStaffReply($ticket, $this->staff, 'Looking into it.');
        app(TicketService::class)->addUserReply($ticket, $this->user, 'Any update?');

        Livewire::test('pages::dashboard.support-chats')
            ->call('selectConversation', $ticket->conversation_id, 'ticket')
            ->assertSee('card card-bordered', false)
            ->assertSee('toggleTranslation', false)
            ->assertSee(__('chat.translate_link'), false)
            ->assertSee(__('tickets.requester'), false);
    }

    public function test_staff_inbox_surfaces_ticket_conversations(): void
    {
        $this->actingAs($this->staff);

        $ticket = app(TicketService::class)->create($this->user, 'Broken export', 'Details.');

        Livewire::test('pages::dashboard.support-chats')
            ->assertSee('Broken export')
            ->assertSee($ticket->reference_number);
    }
}
