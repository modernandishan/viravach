<?php

namespace Tests\Feature;

use App\Jobs\GenerateAiChatReply;
use App\Models\AiAssistant;
use App\Models\User;
use App\Services\Chat\AiChatService;
use App\Settings\ChatSettings;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class ChatSettingsWiringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AiAssistant::query()->forceCreate(['name' => 'ViraBot']);

        // Same memoization pitfall as ChatBroadcastingTest — clear the
        // static assistant cache between tests.
        Closure::bind(function (): void {
            AiChatService::$assistant = null;
        }, null, AiChatService::class)();
    }

    public function test_ai_disabled_sends_fallback_message_immediately_and_skips_the_reply_job(): void
    {
        app(ChatSettings::class)->fill(['ai_enabled' => false])->save();

        Queue::fake();

        $user = User::factory()->create();
        app(AiChatService::class)->sendUserMessage($user, 'hello');

        Queue::assertNotPushed(GenerateAiChatReply::class);

        $conversation = app(AiChatService::class)->startOrGetConversation($user);
        $lastMessage = $conversation->messages()->latest('id')->first();

        $this->assertSame('ai_error', $lastMessage->type);
        $this->assertSame(__('chat.ai_error'), $lastMessage->body);
    }

    public function test_ai_enabled_still_dispatches_the_reply_job(): void
    {
        // ai_enabled defaults to true from the settings migration — confirm
        // the kill switch being off doesn't accidentally become the default.
        Queue::fake();

        $user = User::factory()->create();
        app(AiChatService::class)->sendUserMessage($user, 'hello');

        Queue::assertPushed(GenerateAiChatReply::class);
    }

    public function test_failed_ai_reply_job_tags_its_fallback_message_as_ai_error(): void
    {
        $user = User::factory()->create();
        $conversation = app(AiChatService::class)->startOrGetConversation($user);

        $job = new GenerateAiChatReply($conversation->id, $user, null, 'en');
        $job->failed(new \RuntimeException('provider unavailable'));

        $lastMessage = $conversation->messages()->latest('id')->first();

        $this->assertSame('ai_error', $lastMessage->type);
        $this->assertSame(__('chat.ai_error', [], 'en'), $lastMessage->body);
    }

    public function test_send_rate_limit_setting_overrides_the_hardcoded_default(): void
    {
        app(ChatSettings::class)->fill(['rate_limit_messages_per_minute' => 1])->save();

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::dashboard.chat')
            ->set('body', 'first message')
            ->call('sendMessage')
            ->assertSet('sendError', null)
            ->set('body', 'second message')
            ->call('sendMessage')
            ->assertSet('sendError', __('chat.rate_limited_send'));
    }
}
