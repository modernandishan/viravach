<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;

/**
 * ViraBot, the Viravach AI chat assistant, with thinking disabled (see
 * providerOptions()). The provider and model default to
 * config('viravach_chat.ai_provider') / config('viravach_chat.ai_model'), and
 * the system prompt defaults to config('viravach_chat.system_prompts') keyed
 * by the locale that was active when the user sent their message, unless the
 * caller passes admin-edited overrides (see App\Settings\ChatSettings via
 * GenerateAiChatReply). Either way, the {context} placeholder is substituted
 * by the page-specific context passed in.
 */
class ViraBotAgent implements Agent, Conversational, HasProviderOptions
{
    use Promptable;

    /**
     * @param  iterable<int, Message>  $history  prior conversation messages
     * @param  string|null  $context  substituted into the {context} placeholder
     * @param  string|null  $locale  site locale at send time, selects the system prompt
     * @param  string|null  $systemPrompt  admin-edited override (ChatSettings) for this
     *                                     locale's prompt template; null falls back to config
     * @param  string|null  $providerName  admin-edited override (ChatSettings) for the AI
     *                                     provider; null falls back to config
     * @param  string|null  $modelName  admin-edited override (ChatSettings) for the AI
     *                                  model; null falls back to config
     */
    public function __construct(
        protected iterable $history = [],
        protected ?string $context = null,
        protected ?string $locale = null,
        protected ?string $systemPrompt = null,
        protected ?string $providerName = null,
        protected ?string $modelName = null,
    ) {}

    public function instructions(): string
    {
        return str_replace('{context}', $this->context ?? '', $this->systemPrompt ?? $this->configPrompt());
    }

    protected function configPrompt(): string
    {
        /** @var array<string, string> $prompts */
        $prompts = config('viravach_chat.system_prompts');

        return $prompts[$this->locale]
            ?? $prompts[config('app.fallback_locale')]
            ?? $prompts['en'];
    }

    public function messages(): iterable
    {
        return $this->history;
    }

    public function providerOptions(Lab|string $provider): array
    {
        $name = $provider instanceof Lab ? $provider->value : $provider;

        // `thinking` is GLM/zai-specific. Other gateways reject unknown fields
        // outright (Arvan's returns HTTP 400), so it must only be sent to zai.
        // Reasoning depth for openwebui is set in the Custom Model definition.
        return match ($name) {
            'zai' => ['thinking' => ['type' => 'disabled']],
            default => [],
        };
    }

    public function provider(): string
    {
        return $this->providerName ?? config('viravach_chat.ai_provider');
    }

    public function model(): string
    {
        return $this->modelName ?? config('viravach_chat.ai_model');
    }
}
