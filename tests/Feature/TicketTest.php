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

    public function test_staff_inbox_surfaces_ticket_conversations(): void
    {
        $this->actingAs($this->staff);

        $ticket = app(TicketService::class)->create($this->user, 'Broken export', 'Details.');

        Livewire::test('pages::dashboard.support-chats')
            ->assertSee('Broken export')
            ->assertSee($ticket->reference_number);
    }
}
