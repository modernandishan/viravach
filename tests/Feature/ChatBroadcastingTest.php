<?php

namespace Tests\Feature;

use App\Events\ChatConversationStarted;
use App\Http\Middleware\DetectLocaleFromIp;
use App\Http\Middleware\DiscardInvalidBroadcastSocketId;
use App\Models\AiAssistant;
use App\Models\Company;
use App\Models\Guest;
use App\Models\User;
use App\Services\Chat\AiChatService;
use App\Services\Chat\CompanyChatService;
use App\Services\Chat\SupportTransferService;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Musonza\Chat\Facades\ChatFacade as Chat;
use Musonza\Chat\Models\Conversation;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ChatBroadcastingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AiAssistant::query()->forceCreate(['name' => 'ViraBot']);

        // AiChatService memoizes the assistant in a static property, which
        // survives between tests in the same process while RefreshDatabase
        // resets the rows — clear it so each test resolves a fresh model.
        Closure::bind(function (): void {
            AiChatService::$assistant = null;
        }, null, AiChatService::class)();
    }

    public function test_support_transfer_broadcasts_conversation_started_to_the_agent_only_on_creation(): void
    {
        $user = User::factory()->create();
        $agent = User::factory()->create();
        Role::findOrCreate('support');
        $agent->assignRole('support');

        $this->makeEligibleForSupport($user);

        Event::fake([ChatConversationStarted::class]);

        $service = app(SupportTransferService::class);
        $conversation = $service->transfer($user);

        Event::assertDispatched(
            ChatConversationStarted::class,
            fn (ChatConversationStarted $event): bool => $event->broadcastOn()->name === 'private-chat-participant.user.'.$agent->id
                && $event->conversationId === (int) $conversation->id
                && $event->conversationType === 'support'
        );

        // Reusing the existing conversation must not announce it again.
        $service->transfer($user);

        Event::assertDispatchedTimes(ChatConversationStarted::class, 1);
    }

    public function test_company_transfer_broadcasts_conversation_started_to_the_company_only_on_creation(): void
    {
        $company = Company::factory()->create();
        $guest = Guest::create(['token' => (string) Str::uuid()]);

        $this->makeEligibleForCompany($guest, $company);

        Event::fake([ChatConversationStarted::class]);

        $service = app(CompanyChatService::class);
        $conversation = $service->transfer($guest, $company);

        Event::assertDispatched(
            ChatConversationStarted::class,
            fn (ChatConversationStarted $event): bool => $event->broadcastOn()->name === 'private-chat-participant.company.'.$company->id
                && $event->conversationId === (int) $conversation->id
                && $event->conversationType === 'company'
        );

        // Reusing the existing conversation must not announce it again.
        $service->transfer($guest, $company);

        Event::assertDispatchedTimes(ChatConversationStarted::class, 1);
    }

    public function test_new_ai_conversation_broadcasts_conversation_started_only_on_creation(): void
    {
        $user = User::factory()->create();

        Event::fake([ChatConversationStarted::class]);

        app(AiChatService::class)->startOrGetConversation($user);
        app(AiChatService::class)->startOrGetConversation($user);

        Event::assertDispatchedTimes(ChatConversationStarted::class, 1);
    }

    public function test_dashboard_chat_page_renders_with_echo_listeners(): void
    {
        $user = User::factory()->create();
        Company::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get('/dashboard/chat')->assertOk();
    }

    public function test_support_chats_page_renders_with_echo_listeners(): void
    {
        $agent = User::factory()->create();
        Role::findOrCreate('support');
        $agent->assignRole('support');

        $this->actingAs($agent)->get('/dashboard/support-chats')->assertOk();
    }

    public function test_company_chat_drawer_connect_button_transfers_to_the_company_conversation(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create();

        $this->makeEligibleForCompany($user, $company);

        Livewire::actingAs($user)
            ->test('company-elements.company-chat', [
                'companyId' => $company->id,
                'companyName' => 'Test Co',
            ])
            ->call('openDrawer')
            ->assertSet('companyEligible', true)
            ->assertSee(__('chat.company_drawer_hint'))
            ->call('connectToCompany')
            ->assertDontSee(__('chat.company_drawer_hint'))
            ->assertSet('activeConversation', 'company')
            ->assertSet('transferError', null)
            ->assertNotSet('companyConversationId', null)
            ->set('body', 'direct hello')
            ->call('sendMessage')
            ->assertHasNoErrors();

        $conversation = app(CompanyChatService::class)->existingConversation($user->fresh(), $company);

        $this->assertNotNull($conversation);
        $this->assertTrue(
            $conversation->messages()->where('body', 'direct hello')->exists(),
        );
    }

    public function test_dashboard_chat_appends_incoming_message_when_echo_event_arrives_for_open_thread(): void
    {
        $user = User::factory()->create();
        $assistant = AiAssistant::query()->firstOrFail();
        $conversation = app(AiChatService::class)->startOrGetConversation($user);

        $component = Livewire::actingAs($user)->test('pages::dashboard.chat');

        // A counterpart reply lands while the page is open...
        Chat::message('incoming reply')->from($assistant)->to($conversation)->send();

        // ...and the exact event name Livewire registered via getListeners()
        // is dispatched, as the browser would after the Echo push.
        $component
            ->dispatch(
                "echo-private:mc-chat-conversation.{$conversation->id},.Musonza\\Chat\\Eventing\\MessageWasSent",
                ['message' => ['conversation_id' => $conversation->id]],
            )
            ->assertSee('incoming reply');
    }

    public function test_dashboard_chat_gains_a_contact_row_when_conversation_started_event_arrives(): void
    {
        $user = User::factory()->create();
        $agent = User::factory()->create(['name' => ['en' => 'Support Agent Zed']]);
        Role::findOrCreate('support');
        $agent->assignRole('support');

        $this->makeEligibleForSupport($user);

        $component = Livewire::actingAs($user)->test('pages::dashboard.chat');

        // A new conversation involving this user is created AFTER mount...
        app(SupportTransferService::class)->transfer($user->fresh());

        // ...then the participant-channel event triggers the list refresh.
        $component
            ->dispatch('echo-private:chat-participant.user.'.$user->id.',ChatConversationStarted')
            ->assertSee('Support Agent Zed');
    }

    public function test_support_inbox_appends_incoming_message_when_echo_event_arrives(): void
    {
        $user = User::factory()->create();
        $agent = User::factory()->create();
        Role::findOrCreate('support');
        $agent->assignRole('support');

        $this->makeEligibleForSupport($user);
        $conversation = app(SupportTransferService::class)->transfer($user);

        $component = Livewire::actingAs($agent)
            ->test('pages::dashboard.support-chats')
            ->call('selectConversation', $conversation->id);

        Chat::message('customer follow-up')->from($user->fresh())->to($conversation)->send();

        $component
            ->dispatch(
                "echo-private:mc-chat-conversation.{$conversation->id},.Musonza\\Chat\\Eventing\\MessageWasSent",
                ['message' => ['conversation_id' => $conversation->id]],
            )
            ->assertSee('customer follow-up');
    }

    public function test_locale_detection_never_redirects_post_requests(): void
    {
        $middleware = new DetectLocaleFromIp;

        $post = Request::create('/broadcasting/auth', 'POST');

        $response = $middleware->handle($post, fn (): Response => response('passed'));

        $this->assertSame('passed', $response->getContent());
    }

    public function test_invalid_socket_id_header_is_discarded_before_reaching_broadcasting(): void
    {
        $middleware = new DiscardInvalidBroadcastSocketId;

        $invalid = Request::create('/', 'POST');
        $invalid->headers->set('X-Socket-ID', 'undefined');

        $middleware->handle($invalid, function ($request) {
            $this->assertNull($request->header('X-Socket-ID'));

            return response('ok');
        });

        $valid = Request::create('/', 'POST');
        $valid->headers->set('X-Socket-ID', '123.456');

        $middleware->handle($valid, function ($request) {
            $this->assertSame('123.456', $request->header('X-Socket-ID'));

            return response('ok');
        });
    }

    public function test_conversation_participant_can_authorize_the_conversation_channel(): void
    {
        $user = User::factory()->create();
        $conversation = app(AiChatService::class)->startOrGetConversation($user);

        $this->authorizeChannel($user, 'private-mc-chat-conversation.'.$conversation->id)->assertOk();
    }

    public function test_company_owner_can_authorize_an_owned_company_conversation_channel(): void
    {
        $company = Company::factory()->create();
        $guest = Guest::create(['token' => (string) Str::uuid()]);

        $this->makeEligibleForCompany($guest, $company);
        $conversation = app(CompanyChatService::class)->transfer($guest, $company);

        $this->authorizeChannel($company->user, 'private-mc-chat-conversation.'.$conversation->id)->assertOk();
    }

    public function test_non_participant_cannot_authorize_the_conversation_channel(): void
    {
        $user = User::factory()->create();
        $conversation = app(AiChatService::class)->startOrGetConversation($user);

        $this->authorizeChannel(User::factory()->create(), 'private-mc-chat-conversation.'.$conversation->id)
            ->assertForbidden();
    }

    public function test_guests_cannot_authorize_any_chat_channel(): void
    {
        $guest = Guest::create(['token' => (string) Str::uuid()]);
        $conversation = app(AiChatService::class)->startOrGetConversation($guest);

        $response = $this->authorizeChannel(null, 'private-mc-chat-conversation.'.$conversation->id);

        $this->assertContains($response->getStatusCode(), [401, 403]);
    }

    public function test_only_the_user_themselves_can_authorize_their_participant_channel(): void
    {
        $user = User::factory()->create();

        $this->authorizeChannel($user, 'private-chat-participant.user.'.$user->id)->assertOk();
        $this->authorizeChannel(User::factory()->create(), 'private-chat-participant.user.'.$user->id)
            ->assertForbidden();
    }

    public function test_only_the_owner_can_authorize_a_company_participant_channel(): void
    {
        $company = Company::factory()->create();

        $this->authorizeChannel($company->user, 'private-chat-participant.company.'.$company->id)->assertOk();
        $this->authorizeChannel(User::factory()->create(), 'private-chat-participant.company.'.$company->id)
            ->assertForbidden();
    }

    /**
     * Hit the real /broadcasting/auth endpoint. The suite runs on the "null"
     * broadcaster (which skips channel callbacks entirely), so this switches
     * to the reverb driver with inert credentials and re-registers the
     * channel callbacks on it — the auth signature is computed locally, no
     * Reverb server is contacted.
     */
    protected function authorizeChannel(?User $actingAs, string $channelName): TestResponse
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => '12345',
            'broadcasting.connections.reverb.options' => [
                'host' => 'localhost',
                'port' => 8080,
                'scheme' => 'http',
                'useTLS' => false,
            ],
        ]);

        require base_path('routes/channels.php');

        if ($actingAs) {
            $this->actingAs($actingAs);
        }

        return $this->postJson('/broadcasting/auth', [
            'channel_name' => $channelName,
            'socket_id' => '123.456',
        ]);
    }

    /**
     * Support transfers require at least one message in the participant's
     * general AI conversation. Chat::message() is used directly instead of
     * AiChatService::sendUserMessage() so no AI-reply job is dispatched.
     */
    protected function makeEligibleForSupport(Model $participant): void
    {
        $conversation = app(AiChatService::class)->startOrGetConversation($participant);

        Chat::message('hello')->from($participant)->to($conversation)->send();
    }

    /**
     * Company transfers require at least one message in the participant's
     * AI conversation scoped to that company.
     */
    protected function makeEligibleForCompany(Model $participant, Company $company): Conversation
    {
        $conversation = app(AiChatService::class)->startOrGetConversation($participant, $company->id);

        Chat::message('hello')->from($participant)->to($conversation)->send();

        return $conversation;
    }
}
