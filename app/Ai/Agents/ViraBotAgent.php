<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;

/**
 * ViraBot, the Viravach AI chat assistant. Pinned to the zai/GLM-4.7-Flash
 * model with thinking disabled (see providerOptions()); the system prompt
 * comes from config('viravach_chat.system_prompt') with its {context}
 * placeholder substituted by the page-specific context passed in.
 */
class ViraBotAgent implements Agent, Conversational, HasProviderOptions
{
    use Promptable;

    /**
     * @param  iterable<int, Message>  $history  prior conversation messages
     * @param  string|null  $context  substituted into the {context} placeholder
     */
    public function __construct(
        protected iterable $history = [],
        protected ?string $context = null,
    ) {}

    public function instructions(): string
    {
        return str_replace('{context}', $this->context ?? '', (string) config('viravach_chat.system_prompt'));
    }

    public function messages(): iterable
    {
        return $this->history;
    }

    public function providerOptions(Lab|string $provider): array
    {
        return ['thinking' => ['type' => 'disabled']];
    }

    public function provider(): string
    {
        return 'zai';
    }

    public function model(): string
    {
        return 'GLM-4.7-Flash';
    }
}
